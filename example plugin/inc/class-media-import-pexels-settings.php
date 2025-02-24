<?php
/**
 * Pexels settings class
 *
 * @package Media_Import_Pexels
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class to handle Pexels API settings
 */
class Media_Import_Pexels_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'media_import_register_settings_sections', array( $this, 'register_settings' ) );
        add_filter( 'media_import_sanitize_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Add section.
        add_settings_section(
            'media_import_pexels_section',
            esc_html__( 'Pexels Settings', 'media-import-pexels' ),
            array( $this, 'render_section_description' ),
            'media-import'
        );

        // Add API key field.
        add_settings_field(
            'media_import_pexels_api_key',
            esc_html__( 'API Key', 'media-import-pexels' ),
            array( $this, 'render_api_key_field' ),
            'media-import',
            'media_import_pexels_section',
            array( 'label_for' => 'media_import_pexels_api_key' )
        );
    }

    /**
     * Render section description
     */
    public function render_section_description() {
        ?>
        <p>
            <?php
            printf(
                /* translators: %s: Pexels API URL */
                esc_html__( 'Configure your Pexels API settings. You can get an API key from %s.', 'media-import-pexels' ),
                '<a href="https://www.pexels.com/api/" target="_blank">Pexels</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $options = get_option( 'media_import_options', array() );
        $api_key = isset( $options['pexels_api_key'] ) ? $options['pexels_api_key'] : '';
        ?>
        <input type="text" 
               id="media_import_pexels_api_key"
               name="media_import_options[pexels_api_key]"
               value="<?php echo esc_attr( $api_key ); ?>"
               class="regular-text">
        <p class="description">
            <?php esc_html_e( 'Enter your Pexels API key to enable image search and import.', 'media-import-pexels' ); ?>
        </p>
        <?php
    }

    /**
     * Sanitize settings
     *
     * @param array $input The settings array.
     * @return array The sanitized settings array.
     */
    public function sanitize_settings( $input ) {
        if ( isset( $input['pexels_api_key'] ) ) {
            $input['pexels_api_key'] = sanitize_text_field( $input['pexels_api_key'] );
        }
        return $input;
    }
} 