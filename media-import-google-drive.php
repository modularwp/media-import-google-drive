<?php
/**
 * Google Drive integration for Media Import plugin
 *
 * @package    Media_Import_Google_Drive
 * @author     ModularWP
 * @copyright  2024 ModularWP
 * @license    GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Media Import Google Drive Source
 * Description:       Adds Google Drive integration to Media Import plugin
 * Version:          0.1.0
 * Author:           ModularWP
 * License:          GPL v2 or later
 * Text Domain:      media-import-google-drive
 * Domain Path:      /languages
 * Requires PHP:     7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MEDIA_IMPORT_GOOGLE_DRIVE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MEDIA_IMPORT_GOOGLE_DRIVE_URL', plugin_dir_url( __FILE__ ) );

// Require only the settings class early.
require_once MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'inc/class-media-import-google-drive-settings.php';

/**
 * Main plugin class
 */
class Media_Import_Google_Drive_Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Media_Import_Google_Drive_Plugin
	 */
	private static $instance = null;

	/**
	 * Source instance.
	 *
	 * @var Media_Import_Google_Drive_Source
	 */
	private $source = null;

	/**
	 * Settings instance.
	 *
	 * @var Media_Import_Google_Drive_Settings
	 */
	private $settings = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Media_Import_Google_Drive_Plugin
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

		// Load the source class after parent plugin is loaded
		require_once MEDIA_IMPORT_GOOGLE_DRIVE_PATH . 'inc/class-media-import-google-drive-source.php';
		
		// Create instances of our classes
		$this->source = new Media_Import_Google_Drive_Source();
		$this->settings = new Media_Import_Google_Drive_Settings();

		// Check if we're on a relevant admin page
		add_action('admin_init', array($this, 'maybe_init_admin'));

		// Register source at a later priority
		add_filter('media_import_sources', array($this, 'register_source'), 20);

		// Add filter for download args
		add_filter('media_import_download_args', array($this, 'filter_download_args'), 10, 2);
	}

	/**
	 * Initialize admin functionality if on relevant pages
	 */
	public function maybe_init_admin() {
		$screen = get_current_screen();
		if (!$screen || !in_array($screen->base, array('upload', 'post', 'post-new'), true)) {
			return;
		}

		// Add scripts only on relevant pages
		add_action('admin_enqueue_scripts', array($this->source, 'enqueue_scripts'));
	}

	/**
	 * Register the Google Drive source.
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
			<p><?php esc_html_e( 'Media Import Google Drive Source requires the Media Import plugin to be installed and activated.', 'media-import-google-drive' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Filter download arguments for Google Drive files.
	 *
	 * @param array $args      Download arguments.
	 * @param array $file_info File information.
	 * @return array Modified download arguments.
	 */
	public function filter_download_args( $args, $file_info ) {
		if ( ! empty( $file_info['authorization'] ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $file_info['authorization'];
			
			// Ensure we follow redirects and handle binary data properly.
			$args['redirection']  = 5;
			$args['timeout']      = 60;
			$args['stream']       = true;
			$args['filename']     = $file_info['filename'];
			$args['sslverify']    = true;
			$args['headers']['Accept'] = '*/*';
			$args['headers']['Accept-Encoding'] = 'gzip, deflate, br';
			
			// Remove any existing Content-Type header to let the response set it.
			if ( isset( $args['headers']['Content-Type'] ) ) {
				unset( $args['headers']['Content-Type'] );
			}
		}
		return $args;
	}
}

// Initialize plugin with high priority to ensure parent plugin loads first.
add_action(
	'plugins_loaded',
	function() {
		Media_Import_Google_Drive_Plugin::get_instance();
	},
	20
);

// Add CSP headers for Google APIs.
add_action(
	'send_headers',
	function() {
		if ( is_admin() ) {
			$domains = array(
				"'self'",
				'https://*.google.com',
				'https://*.googleapis.com',
				'https://www.gstatic.com',
				'https://*.googlevideo.com',
				'https://csp.withgoogle.com',
				'https://drive-thirdparty.googleusercontent.com',
				'https://lh3.googleusercontent.com',
				'https://*.googleusercontent.com',
				'https://accounts.google.com',
				'https://oauth2.googleapis.com',
				'https://www.googleapis.com',
			);
			
			$csp = array(
				'default-src' => $domains,
				'connect-src' => $domains,
				'script-src'  => array_merge( $domains, array( "'unsafe-inline'", "'unsafe-eval'" ) ),
				'style-src'   => array_merge( $domains, array( "'unsafe-inline'" ) ),
				'img-src'     => array_merge( $domains, array( 'data:', 'blob:' ) ),
			);

			$header = '';
			foreach ( $csp as $directive => $sources ) {
				$header .= $directive . ' ' . implode( ' ', $sources ) . '; ';
			}

			header( 'Content-Security-Policy: ' . trim( $header ) );
		}
	}
); 