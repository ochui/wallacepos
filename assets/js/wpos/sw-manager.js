/**
 * Service Worker Registration and Cache Management
 * Replacement for applicationCache functionality
 */

function ServiceWorkerManager() {
    var isSupported = 'serviceWorker' in navigator;
    var registration = null;
    var isUpdating = false;
    
    // Callback functions for various events
    var callbacks = {
        onCacheReady: null,
        onUpdateAvailable: null,
        onUpdateApplied: null,
        onError: null,
        onProgress: null
    };
    
    this.init = function(config) {
        if (config) {
            callbacks = Object.assign(callbacks, config);
        }
        
        if (!isSupported) {
            console.log('Service Workers not supported');
            if (callbacks.onError) {
                callbacks.onError('Service Workers not supported');
            }
            return Promise.reject('Service Workers not supported');
        }
        
        return this.register();
    };
    
    this.register = function() {
        return navigator.serviceWorker.register('/sw.js')
            .then(function(reg) {
                registration = reg;
                console.log('Service Worker registered');
                
                // Check for initial installation
                if (reg.installing) {
                    console.log('Service Worker installing');
                    if (callbacks.onProgress) {
                        callbacks.onProgress('Installing...');
                    }
                }
                
                // Handle updates
                reg.addEventListener('updatefound', function() {
                    console.log('Service Worker update found');
                    var newWorker = reg.installing;
                    
                    if (callbacks.onProgress) {
                        callbacks.onProgress('Updating application...');
                    }
                    
                    newWorker.addEventListener('statechange', function() {
                        if (newWorker.state === 'installed') {
                            if (navigator.serviceWorker.controller) {
                                // Update available
                                console.log('Update available');
                                if (callbacks.onUpdateAvailable) {
                                    callbacks.onUpdateAvailable();
                                }
                            } else {
                                // First time installation
                                console.log('Cache ready');
                                if (callbacks.onCacheReady) {
                                    callbacks.onCacheReady();
                                }
                            }
                        }
                    });
                });
                
                // If there's already a waiting worker, notify about update
                if (reg.waiting) {
                    if (callbacks.onUpdateAvailable) {
                        callbacks.onUpdateAvailable();
                    }
                }
                
                // Listen for controlling worker changes
                navigator.serviceWorker.addEventListener('controllerchange', function() {
                    if (isUpdating) {
                        console.log('Update applied, reloading...');
                        if (callbacks.onUpdateApplied) {
                            callbacks.onUpdateApplied();
                        } else {
                            window.location.reload();
                        }
                    }
                });
                
                return reg;
            })
            .catch(function(error) {
                console.error('Service Worker registration failed:', error);
                if (callbacks.onError) {
                    callbacks.onError(error);
                }
                throw error;
            });
    };
    
    this.applyUpdate = function() {
        if (registration && registration.waiting) {
            isUpdating = true;
            registration.waiting.postMessage({ action: 'skipWaiting' });
        }
    };
    
    this.clearCache = function() {
        if (registration && registration.active) {
            return new Promise(function(resolve, reject) {
                var messageChannel = new MessageChannel();
                messageChannel.port1.onmessage = function(event) {
                    if (event.data.success) {
                        resolve();
                    } else {
                        reject(event.data.error);
                    }
                };
                
                registration.active.postMessage(
                    { action: 'clearCache' },
                    [messageChannel.port2]
                );
            });
        }
        return Promise.reject('No active service worker');
    };
    
    this.isSupported = function() {
        return isSupported;
    };
    
    this.getRegistration = function() {
        return registration;
    };
    
    return this;
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ServiceWorkerManager;
}