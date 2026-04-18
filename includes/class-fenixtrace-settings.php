<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_Settings {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            'FenixTrace Settings',
            'FenixTrace',
            'manage_woocommerce',
            'fenixtrace-settings',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Force the Integration Kit URL to use the http / https scheme.
     * esc_url_raw accepts schemes like javascript:, tel:, ftp:, etc.
     */
    public static function sanitize_kit_url( $value ) {
        $raw = esc_url_raw( (string) $value, array( 'http', 'https' ) );
        if ( empty( $raw ) ) {
            return 'http://localhost:3005';
        }
        $parts = wp_parse_url( $raw );
        if ( empty( $parts['host'] ) ) {
            return 'http://localhost:3005';
        }
        return $raw;
    }

    public static function register_settings() {
        register_setting( 'fenixtrace_settings', 'fenixtrace_kit_url', array(
            'sanitize_callback' => array( __CLASS__, 'sanitize_kit_url' ),
            'default'           => 'http://localhost:3005',
        ) );
        register_setting( 'fenixtrace_settings', 'fenixtrace_upload_dir', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );
        register_setting( 'fenixtrace_settings', 'fenixtrace_auto_sync', array(
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );
        register_setting( 'fenixtrace_settings', 'fenixtrace_template', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'generic',
        ) );

        add_settings_section( 'fenixtrace_main', 'Integration Kit Connection', null, 'fenixtrace-settings' );

        add_settings_field( 'fenixtrace_kit_url', 'Integration Kit URL', array( __CLASS__, 'field_kit_url' ), 'fenixtrace-settings', 'fenixtrace_main' );
        add_settings_field( 'fenixtrace_upload_dir', 'Upload Directory', array( __CLASS__, 'field_upload_dir' ), 'fenixtrace-settings', 'fenixtrace_main' );
        add_settings_field( 'fenixtrace_auto_sync', 'Auto-sync on Publish', array( __CLASS__, 'field_auto_sync' ), 'fenixtrace-settings', 'fenixtrace_main' );
        add_settings_field( 'fenixtrace_template', 'Product Template', array( __CLASS__, 'field_template' ), 'fenixtrace-settings', 'fenixtrace_main' );
    }

    public static function field_kit_url() {
        $val = esc_attr( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ) );
        echo "<input type='url' name='fenixtrace_kit_url' value='{$val}' class='regular-text' placeholder='http://localhost:3005' />";
        echo "<p class='description'>URL where the FenixTrace Integration Kit is running.</p>";
    }

    public static function field_upload_dir() {
        $val = esc_attr( get_option( 'fenixtrace_upload_dir', '' ) );
        echo "<input type='text' name='fenixtrace_upload_dir' value='{$val}' class='regular-text' placeholder='/opt/fenixtrace-kit/uploads' />";
        echo "<p class='description'>Optional. Local path to the Integration Kit's uploads/ folder for file-based sync.</p>";
    }

    public static function field_auto_sync() {
        $checked = checked( get_option( 'fenixtrace_auto_sync', 0 ), 1, false );
        echo "<label><input type='checkbox' name='fenixtrace_auto_sync' value='1' {$checked} /> Automatically send products to FenixTrace when published</label>";
    }

    public static function field_template() {
        $val = get_option( 'fenixtrace_template', 'generic' );
        $templates = array( 'generic', 'agro', 'pharma', 'fashion', 'logistics', 'electronics', 'art', 'automotive', 'cosmetics', 'chemicals', 'machinery', 'custom' );
        echo "<select name='fenixtrace_template'>";
        foreach ( $templates as $t ) {
            $selected = selected( $val, $t, false );
            echo "<option value='" . esc_attr( $t ) . "' {$selected}>" . esc_html( ucfirst( $t ) ) . "</option>";
        }
        echo "</select>";
        echo "<p class='description'>Product category template for blockchain metadata.</p>";
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $status = FenixTrace_API::check_status();
        ?>
        <div class="wrap">
            <h1>FenixTrace Settings</h1>

            <div class="fenixtrace-status-card" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin:16px 0;">
                <strong>Integration Kit Status:</strong>
                <?php if ( ! empty( $status['connected'] ) ) : ?>
                    <span style="color:#16a34a;">Connected</span>
                    <?php if ( ! empty( $status['data']['wallet']['address'] ) ) : ?>
                        <br><small>Wallet: <?php echo esc_html( $status['data']['wallet']['address'] ); ?></small>
                    <?php endif; ?>
                <?php else : ?>
                    <span style="color:#dc2626;">Disconnected</span>
                    <?php if ( ! empty( $status['error'] ) ) : ?>
                        <br><small><?php echo esc_html( $status['error'] ); ?></small>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'fenixtrace_settings' );
                do_settings_sections( 'fenixtrace-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
