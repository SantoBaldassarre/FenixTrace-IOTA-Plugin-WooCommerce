<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_Product {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
        add_action( 'wp_ajax_fenixtrace_sync_product', array( __CLASS__, 'ajax_sync_product' ) );
        add_action( 'transition_post_status', array( __CLASS__, 'auto_sync_on_publish' ), 10, 3 );

        // Bulk action
        add_filter( 'bulk_actions-edit-product', array( __CLASS__, 'register_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( __CLASS__, 'handle_bulk_action' ), 10, 3 );
        add_action( 'admin_notices', array( __CLASS__, 'bulk_action_notice' ) );
    }

    /**
     * Add FenixTrace meta box on product edit page.
     */
    public static function add_meta_box() {
        add_meta_box(
            'fenixtrace_blockchain',
            'FenixTrace Blockchain',
            array( __CLASS__, 'render_meta_box' ),
            'product',
            'side',
            'default'
        );
    }

    public static function render_meta_box( $post ) {
        $product_id = $post->ID;
        $state      = get_post_meta( $product_id, '_fenixtrace_state', true ) ?: 'draft';
        $tx_hash    = get_post_meta( $product_id, '_fenixtrace_tx_hash', true );
        $notarization = get_post_meta( $product_id, '_fenixtrace_notarization_tx', true );
        $last_sync  = get_post_meta( $product_id, '_fenixtrace_last_sync', true );
        $last_error = get_post_meta( $product_id, '_fenixtrace_last_error', true );

        $state_labels = array(
            'draft'  => array( 'label' => 'Draft', 'class' => 'fenixtrace-badge-draft' ),
            'queued' => array( 'label' => 'Queued', 'class' => 'fenixtrace-badge-queued' ),
            'synced' => array( 'label' => 'Synced', 'class' => 'fenixtrace-badge-synced' ),
            'error'  => array( 'label' => 'Error', 'class' => 'fenixtrace-badge-error' ),
        );
        $badge = $state_labels[ $state ] ?? $state_labels['draft'];

        wp_nonce_field( 'fenixtrace_sync', 'fenixtrace_nonce' );
        ?>
        <div class="fenixtrace-metabox">
            <p><strong>Status:</strong> <span class="fenixtrace-badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span></p>

            <?php if ( $tx_hash ) : ?>
                <p><strong>TX Hash:</strong><br><code class="fenixtrace-hash"><?php echo esc_html( $tx_hash ); ?></code></p>
            <?php endif; ?>

            <?php if ( $notarization ) : ?>
                <p><strong>Notarization:</strong><br><code class="fenixtrace-hash"><?php echo esc_html( $notarization ); ?></code></p>
            <?php endif; ?>

            <?php if ( $last_sync ) : ?>
                <p><strong>Last Sync:</strong> <?php echo esc_html( $last_sync ); ?></p>
            <?php endif; ?>

            <?php if ( $state === 'error' && $last_error ) : ?>
                <p class="fenixtrace-error"><strong>Error:</strong> <?php echo esc_html( $last_error ); ?></p>
            <?php endif; ?>

            <button type="button" class="button button-primary fenixtrace-sync-btn" data-product-id="<?php echo esc_attr( $product_id ); ?>">
                <?php echo $state === 'error' ? 'Retry FenixTrace' : 'Send to FenixTrace'; ?>
            </button>
        </div>

        <script>
        jQuery(function($) {
            $('.fenixtrace-sync-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var productId = btn.data('product-id');
                btn.prop('disabled', true).text('Syncing...');

                $.post(ajaxurl, {
                    action: 'fenixtrace_sync_product',
                    product_id: productId,
                    nonce: $('#fenixtrace_nonce').val()
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('FenixTrace Error: ' + (response.data || 'Unknown error'));
                        btn.prop('disabled', false).text('Retry');
                    }
                }).fail(function() {
                    alert('FenixTrace: Connection failed');
                    btn.prop('disabled', false).text('Retry');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for single product sync.
     */
    public static function ajax_sync_product() {
        check_ajax_referer( 'fenixtrace_sync', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $product_id = absint( $_POST['product_id'] ?? 0 );
        if ( ! $product_id ) {
            wp_send_json_error( 'Invalid product ID' );
        }

        $result = self::sync_product( $product_id );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result['error'] ?? 'Sync failed' );
        }
    }

    /**
     * Sync a single product to FenixTrace.
     */
    public static function sync_product( int $product_id ): array {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return array( 'success' => false, 'error' => 'Product not found' );
        }

        $payload  = self::build_payload( $product );
        $filename = self::generate_filename( $product );

        update_post_meta( $product_id, '_fenixtrace_state', 'queued' );
        update_post_meta( $product_id, '_fenixtrace_last_error', '' );

        $result = FenixTrace_API::send_product( $payload, $filename );

        if ( $result['success'] ) {
            update_post_meta( $product_id, '_fenixtrace_state', 'synced' );
            update_post_meta( $product_id, '_fenixtrace_tx_hash', $result['txHash'] );
            update_post_meta( $product_id, '_fenixtrace_notarization_tx', $result['notarizationTxHash'] );
            update_post_meta( $product_id, '_fenixtrace_last_sync', current_time( 'mysql' ) );
            update_post_meta( $product_id, '_fenixtrace_last_error', '' );
        } else {
            update_post_meta( $product_id, '_fenixtrace_state', 'error' );
            update_post_meta( $product_id, '_fenixtrace_last_error', $result['error'] ?? 'Unknown error' );
        }

        return $result;
    }

    /**
     * Build JSON payload from WooCommerce product.
     */
    public static function build_payload( $product ): array {
        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

        return array(
            'name'     => $product->get_name(),
            'company'  => get_bloginfo( 'name' ),
            'template' => get_option( 'fenixtrace_template', 'generic' ),
            'product'  => array(
                'name'        => $product->get_name(),
                'sku'         => $product->get_sku(),
                'price'       => $product->get_price(),
                'regularPrice' => $product->get_regular_price(),
                'category'    => is_array( $categories ) ? implode( ', ', $categories ) : '',
                'description' => wp_strip_all_tags( $product->get_short_description() ),
                'weight'      => $product->get_weight(),
                'dimensions'  => array(
                    'length' => $product->get_length(),
                    'width'  => $product->get_width(),
                    'height' => $product->get_height(),
                ),
            ),
            'source'      => 'woocommerce_plugin',
            'createdAt'   => gmdate( 'c' ),
            'woocommerce' => array(
                'product_id'  => $product->get_id(),
                'product_url' => get_permalink( $product->get_id() ),
                'store_name'  => get_bloginfo( 'name' ),
                'store_url'   => home_url(),
            ),
        );
    }

    /**
     * Generate unique filename for the JSON payload. sanitize_title() is a
     * decent first pass but does not guarantee path-safety, so we also
     * strip anything that is not [A-Za-z0-9._-] before joining the parts.
     */
    public static function generate_filename( $product ): string {
        $slug = sanitize_title( $product->get_sku() ?: $product->get_name() );
        $slug = preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $slug );
        if ( empty( $slug ) ) $slug = 'product-' . $product->get_id();
        return $slug . '_' . (int) $product->get_id() . '_' . gmdate( 'YmdHis' ) . '.json';
    }

    /**
     * Auto-sync when a product is published (if enabled).
     */
    public static function auto_sync_on_publish( $new_status, $old_status, $post ) {
        if ( $post->post_type !== 'product' || $new_status !== 'publish' || $old_status === 'publish' ) {
            return;
        }
        if ( ! get_option( 'fenixtrace_auto_sync', 0 ) ) {
            return;
        }
        self::sync_product( $post->ID );
    }

    /**
     * Register bulk action.
     */
    public static function register_bulk_action( $actions ) {
        $actions['fenixtrace_sync'] = 'Send to FenixTrace';
        return $actions;
    }

    /**
     * Handle bulk action.
     */
    public static function handle_bulk_action( $redirect_to, $action, $post_ids ) {
        if ( $action !== 'fenixtrace_sync' ) {
            return $redirect_to;
        }

        if ( ! current_user_can( 'edit_products' ) ) {
            return add_query_arg( 'fenixtrace_error', 'permission', $redirect_to );
        }

        // WooCommerce bulk actions go through the standard WP edit screen,
        // which stamps a _wpnonce bound to "bulk-posts". Reject submissions
        // that arrive without a valid nonce (e.g. forged cross-site requests).
        check_admin_referer( 'bulk-posts' );

        $success = 0;
        $errors  = 0;

        foreach ( $post_ids as $post_id ) {
            $result = self::sync_product( absint( $post_id ) );
            if ( $result['success'] ) {
                $success++;
            } else {
                $errors++;
            }
        }

        return add_query_arg( array(
            'fenixtrace_synced'  => $success,
            'fenixtrace_errors'  => $errors,
        ), $redirect_to );
    }

    /**
     * Show bulk action result notice.
     */
    public static function bulk_action_notice() {
        if ( empty( $_GET['fenixtrace_synced'] ) && empty( $_GET['fenixtrace_errors'] ) ) {
            return;
        }
        $synced = absint( $_GET['fenixtrace_synced'] ?? 0 );
        $errors = absint( $_GET['fenixtrace_errors'] ?? 0 );
        $class  = $errors ? 'notice-warning' : 'notice-success';
        echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
        printf( 'FenixTrace: %d product(s) synced, %d error(s).', $synced, $errors );
        echo '</p></div>';
    }
}
