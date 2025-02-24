(function($) {
    'use strict';

    var GoogleDriveManager = {
        pickerApiLoaded: false,
        tokenClient: null,
        accessToken: null,
        initialized: false,

        initGoogleDriveSource: function() {
            var $container = $('.media-import-source-content[data-source="google-drive"]');
            
            if (!$container.length) {
                return;
            }

            // Check for required configuration
            if (!wpMediaImportGoogleDrive?.clientId || !wpMediaImportGoogleDrive?.apiKey) {
                console.error('Missing required Google Drive configuration');
                return;
            }

            // Load Google APIs if not already loaded
            if (!window.gapi) {
                this.loadGoogleApis();
            } else if (!this.tokenClient) {
                this.initializeAuth();
            }
            
            // Set up button handler
            var $button = $container.find('.media-import-google-drive-select');
            $button.off('click').on('click', this.handleButtonClick.bind(this));
            $button.css('cursor', 'pointer');
        },

        loadGoogleApis: function() {
            var self = this;
            
            // First load Identity Services (gsi)
            const gsiScript = document.createElement('script');
            gsiScript.src = 'https://accounts.google.com/gsi/client';
            gsiScript.onload = function() {
                // Then load the Google API client
                const gapiScript = document.createElement('script');
                gapiScript.src = 'https://apis.google.com/js/api.js';
                gapiScript.onload = function() {
                    // Load client and picker
                    gapi.load('client:picker', function() {
                        gapi.client.load('https://www.googleapis.com/discovery/v1/apis/drive/v3/rest')
                            .then(function() {
                                self.pickerApiLoaded = true;
                                self.initializeAuth();
                            })
                            .catch(function(error) {
                                console.error('Error loading Drive API client:', error);
                            });
                    });
                };
                document.body.appendChild(gapiScript);
            };
            document.body.appendChild(gsiScript);
        },

        initializeAuth: function() {
            if (!window.google || !window.google.accounts) {
                console.error('Google Identity Services not loaded');
                return;
            }

            try {
                this.tokenClient = google.accounts.oauth2.initTokenClient({
                    client_id: wpMediaImportGoogleDrive.clientId,
                    scope: 'https://www.googleapis.com/auth/drive.readonly',
                    callback: (response) => {
                        if (response.error !== undefined) {
                            console.error('Token error:', response);
                            return;
                        }
                        this.accessToken = response.access_token;
                        this.createPicker();
                    }
                });
            } catch (error) {
                console.error('Error initializing auth:', error);
            }
        },

        handleButtonClick: function(e) {
            e.preventDefault();
            
            if (!this.tokenClient) {
                this.initGoogleDriveSource();
                return;
            }

            this.tokenClient.requestAccessToken();
        },

        createPicker: function() {
            if (!this.pickerApiLoaded || !this.accessToken) {
                console.error('Picker API not loaded or access token not available');
                return;
            }

            const docsView = new google.picker.DocsView()
                .setIncludeFolders(true)
                .setMimeTypes('image/png,image/jpeg,image/gif,video/mp4,video/quicktime,video/x-msvideo,audio/mpeg,audio/mp3,audio/wav')
                .setSelectFolderEnabled(false);

            const picker = new google.picker.PickerBuilder()
                .addView(docsView)
                .setOAuthToken(this.accessToken)
                .setDeveloperKey(wpMediaImportGoogleDrive.apiKey)
                .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                .setTitle('Select Files from Google Drive')
                .setCallback((data) => {
                    if (data[google.picker.Response.ACTION] === google.picker.Action.PICKED) {
                        const docs = data[google.picker.Response.DOCUMENTS];
                        this.loadThumbnails(docs);
                    }
                })
                .build();

            picker.setVisible(true);
        },

        loadThumbnails: function(documents) {
            documents.forEach((doc) => {
                if (doc.mimeType === 'application/vnd.google-apps.folder' || 
                    doc.mimeType.includes('google-apps')) {
                    return;
                }

                // Request file metadata using Drive API
                gapi.client.drive.files.get({
                    fileId: doc.id,
                    fields: 'id, name, mimeType, size, imageMediaMetadata',
                    supportsAllDrives: true
                }).then((response) => {
                    const file = response.result;

                    const fileUrl = `https://www.googleapis.com/drive/v3/files/${file.id}?alt=media&key=${wpMediaImportGoogleDrive.apiKey}`;
                    const authHeaders = {
                        'Authorization': 'Bearer ' + this.accessToken
                    };
                    
                    const itemData = {
                        sourceId: 'google-drive',
                        sourceItemId: file.id,
                        title: file.name,
                        url: fileUrl,
                        type: this.detectFileType(file.mimeType),
                        mimeType: file.mimeType,
                        filesize: file.size ? this.formatSize(parseInt(file.size)) : '',
                        thumbnail: fileUrl,
                        width: file.imageMediaMetadata?.width || null,
                        height: file.imageMediaMetadata?.height || null,
                        customFilename: file.name,
                        // Structure file_info to match what the server expects
                        file_info: {
                            type: file.mimeType,
                            filename: file.name,
                            headers: authHeaders,
                            mimeType: file.mimeType,
                            customFilename: file.name,
                            // Add these to ensure they're passed through
                            authorization: this.accessToken,
                            auth_header: 'Bearer ' + this.accessToken
                        }
                    };

                    // Add a custom attribute that won't be stripped
                    itemData._googleDriveAuth = this.accessToken;
                    
                    // Store the token in a way that persists through the AJAX request
                    window._googleDriveAuthToken = this.accessToken;
                    
                    SelectionManager.addItem(itemData);
                }).catch((error) => {
                    console.error('Error loading file metadata:', error);
                });
            });
        },

        detectFileType: function(mimeType) {
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'audio';
            return 'unknown';
        },

        formatSize: function(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            while (bytes >= 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return Math.round(bytes * 100) / 100 + ' ' + units[i];
        }
    };

    // Initialize when the view is ready
    $(document).on('media-import:view-ready', function() {
        setTimeout(() => GoogleDriveManager.initGoogleDriveSource(), 100);
    });

    // Initialize when our tab is activated
    $(document).on('media-import:source-activated', function(e, sourceId) {
        if (sourceId === 'google-drive') {
            setTimeout(() => GoogleDriveManager.initGoogleDriveSource(), 100);
        }
    });

    // Initialize when tab button is clicked
    $(document).on('click', '.media-import-tab-button[data-source="google-drive"]', function() {
        setTimeout(() => GoogleDriveManager.initGoogleDriveSource(), 100);
    });

})(jQuery); 