# Creating Media Import Source Plugins

This guide explains how to create a plugin that adds a new media source to the Media Import plugin.

## Overview

To add a new media source, you'll need to:
1. Create a class that extends `Media_Import_Source`
2. Implement the required abstract methods
3. Register your source with the Media Import plugin
4. Create necessary UI templates

## Basic Implementation

Here's a minimal example of a source plugin:

```php
class My_Custom_Source extends Media_Import_Source {
    /**
     * Get unique identifier for this source
     */
    public function get_id() {
        return 'my-custom-source';
    }

    /**
     * Get human-readable label for this source
     */
    public function get_label() {
        return __('My Custom Source', 'my-plugin');
    }

    /**
     * Register this source's tab in the media library
     */
    public function register_tab() {
        // The parent class already handles basic tab rendering
        // Override this method if you need custom tab behavior
    }

    /**
     * Perform media search
     */
    public function perform_search($query, $page) {
        // Implement your search logic here
        return [
            'items' => [], // Array of media items
            'total' => 0,  // Total number of results
        ];
    }

    /**
     * Render source-specific templates
     */
    public function render_templates() {
        ?>
        <script type="text/html" id="tmpl-media-import-<?php echo esc_attr($this->get_id()); ?>">
            <!-- Your source's main UI template -->
        </script>
        <?php
    }

    /**
     * Handle file import
     */
    public function handle_import($file) {
        // Implement your import logic here
        // Return array with:
        // - name: filename
        // - type: mime type
        // - content: file contents
        // Or return WP_Error on failure
    }
}
```

## Registering Your Source

Register your source using the `media_import_sources` filter:

```php
add_filter('media_import_sources', function($sources) {
    $sources['my-custom-source'] = new My_Custom_Source();
    return $sources;
});
```

## UI Templates

Your source needs at least one template for its main UI. The template ID should follow this format: `tmpl-media-import-{source-id}`.

Example template:

```html
<script type="text/html" id="tmpl-media-import-my-custom-source">
    <div class="media-import-source-content">
        <div class="media-import-search">
            <input type="text" 
                   class="media-import-search-input" 
                   placeholder="<?php esc_attr_e('Search...', 'my-plugin'); ?>">
        </div>
        <div class="media-import-results"></div>
    </div>
</script>
```

## Search Results

Your `perform_search()` method should return items in this format:

```php
[
    'items' => [
        [
            'id' => 'unique-id',
            'title' => 'Image Title',
            'description' => 'Image Description',
            'thumbnail' => 'https://example.com/thumb.jpg',
            'url' => 'https://example.com/image.jpg',
            'type' => 'image/jpeg',
            'dimensions' => [
                'width' => 800,
                'height' => 600
            ],
            'size' => 123456 // in bytes
        ],
        // ... more items ...
    ],
    'total' => 100 // Total number of available results
]
```

## Optional Methods

### handle_import($file)

Override this method only if your source needs custom import handling. By default,
the parent plugin will handle downloading files directly from the URLs provided
in your search results.

If implemented, this method receives a file details array and should return:

```php
[
    'name' => 'filename.jpg',     // Desired filename
    'type' => 'image/jpeg',       // File mime type
    'content' => $file_contents   // Raw file data
]
```

Most sources can rely on the default import handling and don't need to implement
this method.

## Best Practices

1. Use unique prefixes for your source ID and class names
2. Properly sanitize and validate all data
3. Use WordPress coding standards
4. Include proper error handling
5. Add appropriate loading states in your UI
6. Support pagination in your search results
7. Include proper translation support

## See Also

- [Source Settings Integration](source-settings-integration.md) - How to add settings for your source
- [WordPress Media Library Integration](https://developer.wordpress.org/plugins/media/working-with-media-library/) 