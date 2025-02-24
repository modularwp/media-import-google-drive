# Grid Template Documentation

The grid template system provides a standardized way to display and interact with media items. It handles all selection management automatically.

## Usage

### 1. Initialize Grid
```javascript
// Initialize a grid with configuration
MediaImportGrid.init({
    container: '.media-import-grid',
    sourceId: 'your-source-id',
    loadMore: function(page) {
        // Return promise that resolves with:
        // { 
        //     items: [...],  // Array of items to add
        //     hasMore: true  // Whether more items exist
        // }
        return yourApi.getItems(page);
    }
});
```

### 2. Add Items to Grid
```javascript
// Add single item
MediaImportGrid.addItem({
    sourceId: 'your-source-id',
    sourceItemId: 'unique-item-id',
    title: 'Item Title',
    thumbnail: 'thumbnail-url.jpg',
    url: 'full-file-url.jpg',
    type: 'image',
    mimeType: 'image/jpeg',
    filesize: '1.2 MB'  // Optional
});

// Add multiple items
MediaImportGrid.addItems([/* array of items */]);
```

### 3. Clear Grid
```javascript
MediaImportGrid.clear();
```

## Item Properties
Same properties required by SelectionManager:
- `sourceId` (string): Unique identifier for your import source
- `sourceItemId` (string): Unique identifier for the item within your source
- `title` (string): Item title/name
- `thumbnail` (string): URL to item thumbnail
- `url` (string): URL to the full item
- `type` (string): One of: 'image', 'video', 'audio'
- `mimeType` (string): The item's MIME type

Optional properties:
- `filesize` (string): Formatted file size
- `width` (number): Image width (for images)
- `height` (number): Image height (for images)

## Example Implementation
```javascript
// Initialize grid for your source
MediaImportGrid.init({
    container: '.your-source-grid',
    sourceId: 'your-source-id'
});

// Add items from your API response
yourApi.getItems().then(function(response) {
    const items = response.items.map(item => ({
        sourceId: 'your-source-id',
        sourceItemId: item.id,
        title: item.name,
        thumbnail: item.thumb_url,
        url: item.download_url,
        type: 'image',
        mimeType: item.mime_type,
        filesize: item.size
    }));
    
    MediaImportGrid.addItems(items);
});
```

Note: The grid automatically:
1. Handles click events on grid items
2. Adds/removes items from SelectionManager
3. Updates UI when items are selected/deselected
4. Syncs with SelectionManager state 