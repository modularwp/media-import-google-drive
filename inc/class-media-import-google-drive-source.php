<?php
/**
 * Google Drive source class
 *
 * @package Media_Import_Google_Drive
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class to handle Google Drive media source
 */
class Media_Import_Google_Drive_Source extends Media_Import_Source {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        add_action( 'media_import_content_templates', array( $this, 'render_templates' ) );
        add_action( 'wp_ajax_media_import_google_drive_exchange_token', array( $this, 'handle_token_exchange' ) );
        add_action( 'wp_ajax_media_import_google_drive_oauth', array( $this, 'handle_oauth_redirect' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Get source ID
     *
     * @return string
     */
    public function get_id() {
        return 'google-drive';
    }

    /**
     * Get source label
     *
     * @return string
     */
    public function get_label() {
        return __( 'Google Drive', 'media-import-google-drive' );
    }

    /**
     * Render templates
     */
    public function render_templates() {
        $client_id = $this->get_client_id();
        $api_key = $this->get_api_key();
        ?>
        <div class="media-import-source-content" data-source="google-drive">
            <?php if ( ! $client_id || ! $api_key ) : ?>
                <div class="notice notice-warning">
                    <p>
                        <?php 
                        printf(
                            /* translators: %s: Settings page URL */
                            esc_html__( 'Google Drive integration requires configuration. Please visit the %s to set it up.', 'media-import-google-drive' ),
                            '<a href="' . esc_url( admin_url( 'options-general.php?page=media-import#google-drive' ) ) . '">' . 
                            esc_html__( 'settings page', 'media-import-google-drive' ) . 
                            '</a>'
                        );
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <div class="media-import-google-drive-content">
                    <div class="media-import-google-drive-intro">
                        <h2><?php esc_html_e( 'Import from Google Drive', 'media-import-google-drive' ); ?></h2>
                        <p><?php esc_html_e( 'Click the button below to open Google Drive and select files to import.', 'media-import-google-drive' ); ?></p>
                        <button type="button" class="button button-hero media-import-google-drive-select">
                            <span class="dashicons dashicons-google" style="margin-top: 4px;"></span>
                            <?php esc_html_e( 'Select from Google Drive', 'media-import-google-drive' ); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get the configured client ID
     * 
     * @return string
     */
    public function get_client_id() {
        if ( defined( 'MEDIA_IMPORT_GOOGLE_DRIVE_CLIENT_ID' ) ) {
            return MEDIA_IMPORT_GOOGLE_DRIVE_CLIENT_ID;
        }
        $options = get_option( 'media_import_options', array() );
        return isset( $options['google_drive_client_id'] ) ? $options['google_drive_client_id'] : '';
    }

    /**
     * Get the configured API key
     * 
     * @return string
     */
    public function get_api_key() {
        if ( defined( 'MEDIA_IMPORT_GOOGLE_DRIVE_API_KEY' ) ) {
            return MEDIA_IMPORT_GOOGLE_DRIVE_API_KEY;
        }
        $options = get_option( 'media_import_options', array() );
        return isset( $options['google_drive_api_key'] ) ? $options['google_drive_api_key'] : '';
    }

    /**
     * Get the configured client secret
     * 
     * @return string
     */
    public function get_client_secret() {
        if ( defined( 'MEDIA_IMPORT_GOOGLE_DRIVE_CLIENT_SECRET' ) ) {
            return MEDIA_IMPORT_GOOGLE_DRIVE_CLIENT_SECRET;
        }
        $options = get_option( 'media_import_options', array() );
        return isset( $options['google_drive_client_secret'] ) ? $options['google_drive_client_secret'] : '';
    }

    /**
     * Handle token exchange
     */
    public function handle_token_exchange() {
        check_ajax_referer( 'media_import', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
        if ( empty( $code ) ) {
            wp_send_json_error( 'No authorization code provided' );
        }

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code'          => $code,
                'client_id'     => $this->get_client_id(),
                'client_secret' => $this->get_client_secret(),
                'redirect_uri'  => admin_url(),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $body['error'] ) ) {
            wp_send_json_error( $body );
        }

        wp_send_json_success( $body );
    }

    /**
     * Handle OAuth redirect
     */
    public function handle_oauth_redirect() {
        require_once MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'inc/oauth-redirect.php';
        exit;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'media-import-google-drive',
            MEDIA_IMPORT_GOOGLE_DRIVE_URL . 'css/media-import-google-drive.css',
            array(),
            file_exists( MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'css/media-import-google-drive.css' ) ? filemtime( MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'css/media-import-google-drive.css' ) : null
        );

        wp_enqueue_script(
            'media-import-google-drive',
            MEDIA_IMPORT_GOOGLE_DRIVE_URL . 'js/media-import-google-drive.js',
            array( 'jquery', 'media-import' ),
            file_exists( MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'js/media-import-google-drive.js' ) ? filemtime( MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'js/media-import-google-drive.js' ) : null,
            true
        );

        wp_localize_script(
            'media-import-google-drive',
            'wpMediaImportGoogleDrive',
            array(
                'clientId' => $this->get_client_id(),
                'apiKey'   => $this->get_api_key(),
                'nonce'    => wp_create_nonce( 'media_import' ),
            )
        );
    }

    /**
     * Handle file import
     *
     * @param array $file File data from the frontend.
     * @return array|WP_Error Import result or error.
     */
    public function handle_import( $file ) {
        if ( empty( $file['url'] ) ) {
            return new WP_Error( 'missing_data', __( 'Missing required Google Drive data', 'media-import-google-drive' ) );
        }

        // Get auth token from file info.
        $auth_token = null;
        if ( ! empty( $file['file_info']['authorization'] ) ) {
            $auth_token = $file['file_info']['authorization'];
        } elseif ( ! empty( $file['file_info']['auth_header'] ) ) {
            $auth_token = str_replace( 'Bearer ', '', $file['file_info']['auth_header'] );
        }

        if ( empty( $auth_token ) ) {
            return new WP_Error( 'missing_auth', __( 'Missing authorization token', 'media-import-google-drive' ) );
        }

        // Get filename from file info or fallback to customFilename.
        $filename = ! empty( $file['file_info']['filename'] ) ? $file['file_info']['filename'] : 
                   ( ! empty( $file['customFilename'] ) ? $file['customFilename'] : '' );

        if ( empty( $filename ) ) {
            $filename = basename( parse_url( $file['url'], PHP_URL_PATH ) );
        }

        // Return just the necessary file info for the parent plugin.
        return array(
            'url'      => $file['url'],
            'filename' => sanitize_file_name( $filename ),
            'type'     => $file['mimeType'],
            'headers'  => array(
                'Authorization'   => 'Bearer ' . $auth_token,
                'Accept'         => '*/*',
                'Accept-Encoding' => 'gzip, deflate, br',
            ),
        );
    }

    /**
     * Perform search
     * 
     * Not used for Google Drive since we use the Google Picker UI
     *
     * @param string $query Search query.
     * @param int    $page  Page number.
     * @return array Empty array since search is handled by Google Picker.
     */
    public function perform_search($query, $page) {
        // Search is handled by Google Drive Picker UI
        return array(
            'items' => array(),
            'total' => 0,
            'hasMore' => false
        );
    }
} 