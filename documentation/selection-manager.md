# Selection Manager Documentation

The Selection Manager handles the state and UI for selected media items across all import sources.

## Usage

### Adding Items to Selection
```javascript
// Add item through SelectionManager.addItem()
SelectionManager.addItem({
    sourceId: 'your-source-id',
    sourceItemId: 'unique-item-id',
    title: 'File Name',
    url: 'full-file-url.jpg',
    type: 'image',
    mimeType: 'image/jpeg'
});
```

### Required Item Properties
- `sourceId` (string): Unique identifier for your import source
- `sourceItemId` (string): Unique identifier for the item within your source
- `title` (string): Item title/name
- `url` (string): URL to the full item
- `type` (string): One of: 'image', 'video', 'audio'
- `mimeType` (string): The item's MIME type

### Optional Item Properties
- `thumbnail` (string): URL to item thumbnail (for preview only)
- `filesize` (string): Formatted file size
- `width` (number): Image width (for images)
- `height` (number): Image height (for images)
- `customFilename` (string): Custom filename for import

### Events
Listen for these events to handle selection changes:
```javascript
// When an item is removed from selection
$(document).on('media-import:item-removed', function(e, item) {
    if (item.sourceId === 'your-source-id') {
        // Update your source's UI
    }
});

// When all items are cleared
$(document).on('media-import:selection-cleared', function() {
    // Reset your source's selection UI
});

// When source-specific items are cleared
$(document).on('media-import:source-items-cleared', function(e, sourceId) {
    if (sourceId === 'your-source-id') {
        // Reset only your source's items
    }
});
```

### Utility Methods
```javascript
// Get clean filename from URL
SelectionManager.getCleanFilename(url);

// Detect file type from URL
SelectionManager.detectFileType(url);

// Format bytes to human-readable size
SelectionManager.formatFileSize(bytes);

// Validate URL
SelectionManager.validateUrl(url).then(function(result) {
    // result.valid: boolean
    // result.metadata: object (if valid)
    // result.reason: string (if invalid)
});
```

Note: Thumbnails are used only for preview purposes and are not imported to the media library. 