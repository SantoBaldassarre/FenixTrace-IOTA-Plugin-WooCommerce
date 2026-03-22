<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FenixTrace_API {

    /**
     * Send a product to the Integration Kit.
     */
    public static function send_product( array $payload, string $filename ): array {
        $kit_url  = rtrim( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ), '/' );
        $upload_dir = get_option( 'fenixtrace_upload_dir', '' );

        // Write JSON file if upload dir is configured
        if ( $upload_dir && is_dir( $upload_dir ) && is_writable( $upload_dir ) ) {
            $filepath = trailingslashit( $upload_dir ) . $filename;
            file_put_contents( $filepath, wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        }

        // POST to Integration Kit
        $response = wp_remote_post( $kit_url . '/process/' . rawurlencode( $filename ), array(
            'timeout' => 60,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => '',
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 || empty( $body ) ) {
            $error = isset( $body['error'] ) ? $body['error'] : "HTTP $code";
            return array( 'success' => false, 'error' => $error );
        }

        $result = isset( $body['result'] ) && is_array( $body['result'] ) ? $body['result'] : $body;

        return array(
            'success'              => true,
            'txHash'               => isset( $result['txHash'] ) ? sanitize_text_field( $result['txHash'] ) : '',
            'notarizationTxHash'   => isset( $result['notarizationTxHash'] ) ? sanitize_text_field( $result['notarizationTxHash'] ) : '',
            'ipfsHash'             => isset( $result['ipfsHash'] ) ? sanitize_text_field( $result['ipfsHash'] ) : '',
        );
    }

    /**
     * Check Integration Kit status.
     */
    public static function check_status(): array {
        $kit_url = rtrim( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ), '/' );
        $response = wp_remote_get( $kit_url . '/status', array( 'timeout' => 10 ) );

        if ( is_wp_error( $response ) ) {
            return array( 'connected' => false, 'error' => $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        return array( 'connected' => true, 'data' => $body );
    }

    /**
     * Check wallet balance.
     */
    public static function check_balance( int $files = 1 ): array {
        $kit_url = rtrim( get_option( 'fenixtrace_kit_url', 'http://localhost:3005' ), '/' );
        $response = wp_remote_get( $kit_url . '/balance?files=' . $files, array( 'timeout' => 10 ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        return json_decode( wp_remote_retrieve_body( $response ), true ) ?: array();
    }
}
