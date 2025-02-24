<?php
/**
 * Google Drive settings class
 *
 * @package Media_Import_Google_Drive
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class to handle Google Drive API settings
 *
 * @since 1.0.0
 */
class Media_Import_Google_Drive_Settings {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'media_import_register_settings_sections', array( $this, 'register_settings' ) );
        add_filter( 'media_import_sanitize_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
     * Register settings sections and fields
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings() {
        add_settings_section(
            'media_import_google_drive',
            __( 'Google Drive Settings', 'media-import-google-drive' ),
            array( $this, 'render_section_description' ),
            'media-import'
        );

        add_settings_field(
            'google_drive_api_key',
            __( 'API Key', 'media-import-google-drive' ),
            array( $this, 'render_api_key_field' ),
            'media-import',
            'media_import_google_drive'
        );

        add_settings_field(
            'google_drive_client_id',
            __( 'Client ID', 'media-import-google-drive' ),
            array( $this, 'render_client_id_field' ),
            'media-import',
            'media_import_google_drive'
        );

        add_settings_field(
            'google_drive_client_secret',
            __( 'Client Secret', 'media-import-google-drive' ),
            array( $this, 'render_client_secret_field' ),
            'media-import',
            'media_import_google_drive'
        );
    }

    /**
     * Render section description with setup instructions
     */
    public function render_section_description() {
        ?>
        <p>
            <?php 
            printf(
                /* translators: %s: Google Cloud Console URL */
                esc_html__( 'Configure your Google Drive integration using the %s.', 'media-import-google-drive' ),
                '<a href="https://console.cloud.google.com/" target="_blank">' . 
                esc_html__( 'Google Cloud Console', 'media-import-google-drive' ) . 
                '</a>'
            );
            ?>
            <a href="#" class="google-drive-show-instructions">
                <?php esc_html_e('Show setup instructions', 'media-import-google-drive'); ?>
            </a>
        </p>

        <div class="google-drive-setup-instructions" style="display: none;">
            <h4><?php esc_html_e( 'Setup Instructions:', 'media-import-google-drive' ); ?></h4>
            <ol>
                <li>
                    <strong><?php esc_html_e( 'Enable Required APIs:', 'media-import-google-drive' ); ?></strong>
                    <ul>
                        <li><?php esc_html_e( 'Go to "APIs & Services" > "Library"', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Search for and enable "Google Picker API"', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Search for and enable "Google Drive API"', 'media-import-google-drive' ); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php esc_html_e( 'Configure API Key:', 'media-import-google-drive' ); ?></strong>
                    <ul>
                        <li><?php esc_html_e( 'Go to "APIs & Services" > "Credentials"', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Create or select an API key', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Under "API restrictions":', 'media-import-google-drive' ); ?>
                            <ul>
                                <li><?php esc_html_e( 'Choose "Restrict key"', 'media-import-google-drive' ); ?></li>
                                <li><?php esc_html_e( 'Select both "Google Picker API" and "Google Drive API"', 'media-import-google-drive' ); ?></li>
                            </ul>
                        </li>
                        <li><?php esc_html_e( 'Under "Application restrictions":', 'media-import-google-drive' ); ?>
                            <ul>
                                <li><?php esc_html_e( 'Choose "HTTP referrers (websites)"', 'media-import-google-drive' ); ?></li>
                                <li><?php esc_html_e( 'Add your domain (e.g., http://localhost/* for local development)', 'media-import-google-drive' ); ?></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                <li>
                    <strong><?php esc_html_e( 'Configure OAuth 2.0 Client ID:', 'media-import-google-drive' ); ?></strong>
                    <ul>
                        <li><?php esc_html_e( 'Create or select an OAuth 2.0 Client ID', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Under "Authorized JavaScript origins", add your domain (e.g., http://localhost)', 'media-import-google-drive' ); ?></li>
                        <li><?php esc_html_e( 'Under "Authorized redirect URIs", add your admin URL (e.g., http://localhost/wp-admin/)', 'media-import-google-drive' ); ?></li>
                    </ul>
                </li>
            </ol>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.google-drive-show-instructions').on('click', function(e) {
                e.preventDefault();
                var $instructions = $('.google-drive-setup-instructions');
                var $link = $(this);
                
                if ($instructions.is(':visible')) {
                    $instructions.slideUp();
                    $link.text(<?php echo wp_json_encode(__('Show setup instructions', 'media-import-google-drive')); ?>);
                } else {
                    $instructions.slideDown();
                    $link.text(<?php echo wp_json_encode(__('Hide setup instructions', 'media-import-google-drive')); ?>);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Sanitize settings before saving
     *
     * @since 1.0.0
     * @param array $input The settings array.
     * @return array The sanitized settings array.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = array();

        if ( ! empty( $input['google_drive_client_id'] ) ) {
            $sanitized_input['google_drive_client_id'] = sanitize_text_field( $input['google_drive_client_id'] );
        }

        if ( ! empty( $input['google_drive_api_key'] ) ) {
            $sanitized_input['google_drive_api_key'] = sanitize_text_field( $input['google_drive_api_key'] );
        }

        if ( ! empty( $input['google_drive_client_secret'] ) ) {
            $sanitized_input['google_drive_client_secret'] = sanitize_text_field( $input['google_drive_client_secret'] );
        }

        return $sanitized_input;
    }

    /**
     * Render Client ID field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_client_id_field() {
        $options = get_option( 'media_import_options', array() );
        $client_id = isset( $options['google_drive_client_id'] ) ? $options['google_drive_client_id'] : '';
        ?>
        <input 
            type="text" 
            name="media_import_options[google_drive_client_id]" 
            value="<?php echo esc_attr( $client_id ); ?>"
            class="regular-text"
        />
        <p class="description">
            <?php esc_html_e( 'Enter your Google OAuth 2.0 Client ID', 'media-import-google-drive' ); ?>
        </p>
        <?php
    }

    /**
     * Render API Key field
     */
    public function render_api_key_field() {
        $options = get_option( 'media_import_options', array() );
        $api_key = isset( $options['google_drive_api_key'] ) ? $options['google_drive_api_key'] : '';
        ?>
        <input 
            type="text" 
            name="media_import_options[google_drive_api_key]" 
            value="<?php echo esc_attr( $api_key ); ?>"
            class="regular-text"
        >
        <p class="description">
            <?php esc_html_e( 'Enter your Google API Key', 'media-import-google-drive' ); ?>
        </p>
        <?php
    }

    /**
     * Render Client Secret field
     */
    public function render_client_secret_field() {
        $options = get_option( 'media_import_options', array() );
        $client_secret = isset( $options['google_drive_client_secret'] ) ? $options['google_drive_client_secret'] : '';
        ?>
        <input 
            type="text" 
            name="media_import_options[google_drive_client_secret]" 
            value="<?php echo esc_attr( $client_secret ); ?>"
            class="regular-text"
        >
        <p class="description">
            <?php esc_html_e( 'Enter your Google OAuth 2.0 Client Secret', 'media-import-google-drive' ); ?>
        </p>
        <?php
    }
} 