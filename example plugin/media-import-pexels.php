<?php
/**
 * Plugin Name: Media Import Pexels Source
 * Description: Adds Pexels integration to Media Import plugin
 * Version: 0.1.0
 * Author: ModularWP
 * License: GPL v2 or later
 * Text Domain: media-import-pexels
 * Domain Path: /languages
 *
 * @package Media_Import_Pexels
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MEDIA_IMPORT_PEXELS_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEDIA_IMPORT_PEXELS_URL', plugin_dir_url( __FILE__ ) );

// Require only the settings class early.
require_once MEDIA_IMPORT_PEXELS_PATH . 'inc/class-media-import-pexels-settings.php';

/**
 * Main plugin class
 */
class Media_Import_Pexels_Plugin {

    /**
     * Plugin instance.
     *
     * @var Media_Import_Pexels_Plugin
     */
    private static $instance = null;

    /**
     * Source instance.
     *
     * @var Media_Import_Pexels_Source
     */
    private $source = null;

    /**
     * Settings instance.
     *
     * @var Media_Import_Pexels_Settings
     */
    private $settings = null;

    /**
     * Get plugin instance.
     *
     * @return Media_Import_Pexels_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        if ( ! $this->check_dependencies() ) {
            return;
        }

        // Load the source class after parent plugin is loaded.
        require_once MEDIA_IMPORT_PEXELS_PATH . 'inc/class-media-import-pexels-source.php';
        
        // Create instances of our classes.
        $this->source   = new Media_Import_Pexels_Source();
        $this->settings = new Media_Import_Pexels_Settings();
        
        // Register source at a later priority.
        add_filter( 'media_import_sources', array( $this, 'register_source' ), 20 );
    }

    /**
     * Register the Pexels source.
     *
     * @param array $sources List of registered sources.
     * @return array Modified list of sources.
     */
    public function register_source( $sources ) {
        $sources[ $this->source->get_id() ] = $this->source;
        return $sources;
    }

    /**
     * Check plugin dependencies.
     *
     * @return bool True if dependencies are met.
     */
    private function check_dependencies() {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $parent_plugin = 'media-import/media-import.php';
        
        if ( ! is_plugin_active( $parent_plugin ) || ! class_exists( 'Media_Import_Source' ) ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return false;
        }
        return true;
    }

    /**
     * Display dependency notice.
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Media Import Pexels Source requires the Media Import plugin to be installed and activated.', 'media-import-pexels' ); ?></p>
        </div>
        <?php
    }
}

// Initialize plugin with high priority to ensure parent plugin loads first.
add_action( 'plugins_loaded', function() {
    Media_Import_Pexels_Plugin::get_instance();
}, 20 ); 