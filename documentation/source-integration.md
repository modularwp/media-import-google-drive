# Source Integration Documentation

This document explains how to integrate a new media source into the WordPress Media Import plugin.

## Basic Integration

### 1. Register Your Source
Add your source in `media-import.js`:

```javascript
// Register your source
MediaImportSources['your-source-id'] = {
    name: 'Your Source Name',
    icon: 'dashicons-your-icon',
    initialize: function() {
        // Setup your source
        this.setupListeners();
        this.setupUI();
    }
};
```

### 2. Create Source Tab
Your source needs a tab in the import interface:
```html
<div class="media-import-source-content" data-source="your-source-id">
    <div class="media-import-search">
        <!-- Your search controls -->
    </div>
    <div class="media-import-grid">
        <!-- Your items will go here -->
    </div>
</div>
```

### 3. Handle Source Activation
```javascript
$(document).on('media-import:source-activated', function(e, sourceId) {
    if (sourceId === 'your-source-id') {
        // Initialize your source UI
    }
});
```

## Required Files
1. PHP Class: `inc/your-source/class-media-import-your-source.php`
2. JavaScript: `inc/your-source/media-import-your-source.js`
3. CSS: `inc/your-source/media-import-your-source.css`

## Example Implementation
See `inc/url-import/` for a complete example of source integration. 