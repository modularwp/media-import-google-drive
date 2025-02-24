<?php
/**
 * Pexels source class
 *
 * @package Media_Import_Pexels
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class to handle Pexels media source
 */
class Media_Import_Pexels_Source extends Media_Import_Source {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        add_action( 'media_import_content_templates', array( $this, 'render_templates' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_media_import_pexels_search', array( $this, 'handle_ajax_search' ) );
    }

    /**
     * Get source ID
     *
     * @return string
     */
    public function get_id() {
        return 'pexels';
    }

    /**
     * Get source label
     *
     * @return string
     */
    public function get_label() {
        return __( 'Pexels', 'media-import-pexels' );
    }

    /**
     * Render templates
     */
    public function render_templates() {
        ?>
        <div class="media-import-source-content" data-source="pexels">
            <div class="media-import-pexels-controls">
                <select class="media-import-pexels-type">
                    <option value="photo" selected><?php esc_html_e( 'Photos', 'media-import-pexels' ); ?></option>
                    <option value="video"><?php esc_html_e( 'Videos', 'media-import-pexels' ); ?></option>
                </select>
                <input type="text" 
                       class="media-import-pexels-search" 
                       placeholder="<?php esc_attr_e( 'Search Pexels...', 'media-import-pexels' ); ?>">
            </div>
            
            <div class="media-import-pexels-errors"></div>
            <div class="media-import-pexels-grid"></div>
            <div class="media-import-pexels-loading" style="display: none;">
                <?php esc_html_e( 'Loading...', 'media-import-pexels' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Perform search
     *
     * @param string $query Search query.
     * @param int    $page  Page number.
     * @return array|WP_Error Search results or error.
     */
    public function perform_search( $query, $page ) {
        $options = get_option( 'media_import_options', array() );
        $api_key = isset( $options['pexels_api_key'] ) ? $options['pexels_api_key'] : '';
        $type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'photo';
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Please configure your Pexels API key in the settings.', 'media-import-pexels' ) );
        }

        $base_url = 'video' === $type 
            ? 'https://api.pexels.com/videos/'
            : 'https://api.pexels.com/v1/';

        $endpoint = empty( $query ) 
            ? ( 'video' === $type ? 'popular' : 'curated' )
            : 'search';
        $url = $base_url . $endpoint;
        
        $args = array(
            'headers' => array(
                'Authorization' => $api_key,
            ),
            'body' => array(
                'page'     => $page,
                'per_page' => 30,
            ),
        );

        if ( 'search' === $endpoint ) {
            $args['body']['query'] = $query;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        
        if ( 200 !== $status ) {
            return new WP_Error(
                'api_error', 
                sprintf( 
                    /* translators: %d: HTTP status code */
                    __( 'Pexels API error (status %d)', 'media-import-pexels' ),
                    $status
                )
            );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( ! $body || ( ! isset( $body['photos'] ) && ! isset( $body['videos'] ) ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from Pexels API', 'media-import-pexels' ) );
        }

        // Format items based on type.
        if ( 'video' === $type ) {
            $items = array_map( array( $this, 'format_video' ), $body['videos'] );
        } else {
            $items = array_map( array( $this, 'format_photo' ), $body['photos'] );
        }

        return array(
            'items'   => $items,
            'total'   => isset( $body['total_results'] ) ? $body['total_results'] : count( $items ),
            'hasMore' => $body['page'] * $body['per_page'] < ( isset( $body['total_results'] ) ? $body['total_results'] : PHP_INT_MAX ),
        );
    }

    /**
     * Format video data
     *
     * @param array $video Video data from API.
     * @return array Formatted video data.
     */
    private function format_video( $video ) {
        $video_file = $this->get_best_quality_video( $video['video_files'] );
        
        return array(
            'id'            => $video['id'],
            'sourceId'      => 'pexels',
            'sourceItemId'  => (string) $video['id'],
            'title'        => empty( $video['user']['name'] ) ? 'Pexels Video' : $video['user']['name'],
            'thumbnail'    => $video['image'],
            'videoThumbnail' => $video['image'],
            'url'          => $video_file['link'],
            'type'         => 'video',
            'mimeType'     => $video_file['file_type'],
            'width'        => $video_file['width'],
            'height'       => $video_file['height'],
            'duration'     => $video['duration'],
            'fps'          => isset( $video_file['fps'] ) ? $video_file['fps'] : null,
            'quality'      => isset( $video_file['quality'] ) ? $video_file['quality'] : 'hd',
            'attribution'  => sprintf(
                /* translators: %s: Video author name */
                __( 'Video by %s on Pexels', 'media-import-pexels' ),
                $video['user']['name']
            ),
        );
    }

    /**
     * Format photo data
     *
     * @param array $photo Photo data from API.
     * @return array Formatted photo data.
     */
    private function format_photo( $photo ) {
        return array(
            'id'           => $photo['id'],
            'sourceId'     => $this->get_id(),
            'sourceItemId' => $photo['id'],
            'title'        => $photo['photographer'],
            'thumbnail'    => $photo['src']['medium'],
            'url'          => $photo['src']['original'],
            'type'         => 'image',
            'mimeType'     => 'image/jpeg',
            'width'        => $photo['width'],
            'height'       => $photo['height'],
            'attribution'  => sprintf(
                /* translators: %s: Photographer name */
                __( 'Photo by %s on Pexels', 'media-import-pexels' ),
                $photo['photographer']
            ),
        );
    }

    /**
     * Handle AJAX search request
     */
    public function handle_ajax_search() {
        try {
            // Check if the request is actually reaching this handler
            if (!isset($_REQUEST['action']) || $_REQUEST['action'] !== 'media_import_pexels_search') {
                wp_send_json_error('Invalid action');
                return;
            }

            // Verify nonce with more details
            $nonce = isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '';
            
            if (!wp_verify_nonce($nonce, 'media_import_pexels')) {
                wp_send_json_error('Invalid security token');
                return;
            }

            // Verify we have required data
            if (!isset($_POST['source']) || $_POST['source'] !== 'pexels') {
                wp_send_json_error('Invalid source');
                return;
            }

            // Get and sanitize parameters
            $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
            $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
            
            // Perform the search
            $results = $this->perform_search($query, $page);
            
            if (is_wp_error($results)) {
                wp_send_json_error($results->get_error_message());
            } else {
                wp_send_json_success($results);
            }
        } catch (Exception $e) {
            wp_send_json_error('Server error occurred');
        }
        
        // Ensure we always exit
        wp_die();
    }

    private function get_best_quality_video($video_files) {
        // Sort by quality (height) in descending order
        usort($video_files, function($a, $b) {
            return $b['height'] - $a['height'];
        });

        // Find the first MP4 file
        foreach ($video_files as $file) {
            if ($file['file_type'] === 'video/mp4') {
                return $file;
            }
        }

        return $video_files[0];
    }

    public function enqueue_scripts($hook) {
        if (!in_array($hook, ['upload.php', 'post.php', 'post-new.php', 'media-upload.php'], true) 
            && !(defined('IFRAME_REQUEST') && IFRAME_REQUEST)) {
            return;
        }
        
        $css_file = MEDIA_IMPORT_PEXELS_PATH . 'assets/css/media-import-pexels.css';
        $js_file = MEDIA_IMPORT_PEXELS_PATH . 'assets/js/media-import-pexels.js';

        wp_enqueue_style(
            'media-import-pexels',
            MEDIA_IMPORT_PEXELS_URL . 'assets/css/media-import-pexels.css',
            ['media-import'],
            file_exists($css_file) ? filemtime($css_file) : '1.0.0'
        );

        wp_enqueue_script(
            'media-import-pexels',
            MEDIA_IMPORT_PEXELS_URL . 'assets/js/media-import-pexels.js',
            ['jquery', 'wp-util', 'media-import'],
            file_exists($js_file) ? filemtime($js_file) : '1.0.0',
            true
        );

        wp_localize_script('media-import-pexels', 'wpMediaImportPexels', [
            'nonce' => wp_create_nonce('media_import_pexels'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'strings' => [
                'searchPlaceholder' => __('Search Pexels photos and videos...', 'media-import-pexels'),
                'noResults' => __('No results found', 'media-import-pexels'),
                'error' => __('Error loading results', 'media-import-pexels'),
                'videoError' => __('Error processing video.', 'media-import-pexels')
            ]
        ]);
    }
} 