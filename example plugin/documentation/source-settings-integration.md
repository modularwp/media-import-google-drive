# Media Import Source Settings Integration

This guide explains how to integrate your custom media source with the Media Import plugin's settings system. This is useful when your source needs to store configuration options or API keys.

## Overview

The Media Import plugin provides hooks that allow you to:
1. Register your own settings section
2. Add custom fields to that section
3. Sanitize and save your settings

## Integration Steps

### 1. Register Your Settings Section

Hook into `media_import_register_settings_sections` to add your settings section:

```php
add_action('media_import_register_settings_sections', function() {
    add_settings_section(
        'my_source_section',                    // Section ID
        __('My Source Settings', 'my-plugin'),  // Section title
        'my_source_section_callback',           // Callback for section description
        'media-import'                          // Page slug
    );
});
```

### 2. Add Settings Fields

Add your settings fields within your section:

```php
add_action('media_import_register_settings_sections', function() {
    // Add your API key field
    add_settings_field(
        'my_source_api_key',                    // Field ID
        __('API Key', 'my-plugin'),             // Field label
        'my_source_api_key_callback',           // Field rendering callback
        'media-import',                         // Page slug
        'my_source_section',                    // Section ID
        [
            'label_for' => 'my_source_api_key'  // Makes label clickable
        ]
    );
});
```

### 3. Create Rendering Callbacks

Create callbacks to render your section description and fields:

```php
function my_source_section_callback() {
    echo '<p>' . esc_html__('Configure settings for My Source integration.', 'my-plugin') . '</p>';
}

function my_source_api_key_callback() {
    $options = get_option('media_import_options', []);
    $api_key = $options['my_source_api_key'] ?? '';
    ?>
    <input type="text" 
           id="my_source_api_key"
           name="media_import_options[my_source_api_key]"
           value="<?php echo esc_attr($api_key); ?>"
           class="regular-text">
    <?php
}
```

### 4. Sanitize Settings

Hook into `media_import_sanitize_settings` to sanitize your settings:

```php
add_filter('media_import_sanitize_settings', function($input) {
    if (isset($input['my_source_api_key'])) {
        $input['my_source_api_key'] = sanitize_text_field($input['my_source_api_key']);
    }
    return $input;
});
```

### 5. Using Your Settings

Retrieve your settings in your source class:

```php
class My_Source extends Media_Import_Source {
    private function get_api_key() {
        $options = get_option('media_import_options', []);
        return $options['my_source_api_key'] ?? '';
    }
}
```

## Complete Example

Here's a complete example of integrating settings for a custom source:

```php
class My_Source extends Media_Import_Source {
    public function __construct() {
        parent::__construct();
        add_action('media_import_register_settings_sections', [$this, 'register_settings']);
        add_filter('media_import_sanitize_settings', [$this, 'sanitize_settings']);
    }

    public function register_settings() {
        // Add section
        add_settings_section(
            'my_source_section',
            __('My Source Settings', 'my-plugin'),
            [$this, 'render_section_description'],
            'media-import'
        );

        // Add API key field
        add_settings_field(
            'my_source_api_key',
            __('API Key', 'my-plugin'),
            [$this, 'render_api_key_field'],
            'media-import',
            'my_source_section',
            ['label_for' => 'my_source_api_key']
        );
    }

    public function render_section_description() {
        echo '<p>' . esc_html__('Configure settings for My Source integration.', 'my-plugin') . '</p>';
    }

    public function render_api_key_field() {
        $options = get_option('media_import_options', []);
        $api_key = $options['my_source_api_key'] ?? '';
        ?>
        <input type="text" 
               id="my_source_api_key"
               name="media_import_options[my_source_api_key]"
               value="<?php echo esc_attr($api_key); ?>"
               class="regular-text">
        <p class="description">
            <?php esc_html_e('Enter your API key from My Source service.', 'my-plugin'); ?>
        </p>
        <?php
    }

    public function sanitize_settings($input) {
        if (isset($input['my_source_api_key'])) {
            $input['my_source_api_key'] = sanitize_text_field($input['my_source_api_key']);
        }
        return $input;
    }

    private function get_api_key() {
        $options = get_option('media_import_options', []);
        return $options['my_source_api_key'] ?? '';
    }
}
```

## Best Practices

1. Always sanitize your settings using appropriate WordPress sanitization functions
2. Use unique, prefixed keys for your settings to avoid conflicts
3. Provide clear descriptions for your settings fields
4. Use translation functions for all user-facing strings
5. Consider adding validation for required fields or specific formats
6. Use secure methods to store sensitive data like API keys

## Security Considerations

- Never store sensitive API keys or credentials in plain text
- Consider encrypting sensitive data before storing
- Validate and sanitize all input data
- Use WordPress nonces and capability checks
- Follow WordPress coding standards and security best practices
```

</rewritten_file>