// resources/js/print-resources-view-manager.js

/**
 * Unified Print Resources View Manager
 * Handles view toggling (table/card) across all print resource pages
 * Supports multiple view contexts with localStorage persistence
 */
(function(window, document) {
    'use strict';

    const PrintResourcesViewManager = {
        // Configuration
        config: {
            storagePrefix: 'print_resources',
            viewContexts: {
                // For print-resource-account.blade.php (district view)
                default: {
                    storageKey: 'print-resources-view',
                    containerId: 'table-results-container',
                    viewInputId: 'view-input',
                    viewTargets: ['table-view', 'card-view']
                },
                // For print-resource-division-account.blade.php
                division: {
                    storageKey: 'print-resources-division-view',
                    containerId: 'division-results-container',
                    viewInputId: 'division-view-input',
                    viewTargets: ['division-table-view', 'division-card-view']
                },
                // For print-resource-school-account.blade.php (school tab)
                school: {
                    storageKey: 'print-resources-school-view',
                    containerId: 'school-results-container',
                    viewInputId: 'school-view-input',
                    viewTargets: ['school-table-view', 'school-card-view']
                },
                // For print-resource-region-account.blade.php
                region: {
                    storageKey: 'print-resources-region-view',
                    containerId: 'table-results-container',
                    viewInputId: 'view-input',
                    viewTargets: ['table-view', 'card-view']
                }
            }
        },

        /**
         * Initialize the view manager
         */
        init: function() {
            this.detectAndRestoreViews();
            this.attachEventListeners();
            this.setupMutationObservers();
        },

        /**
         * Detect which view context we're in and restore all views
         */
        detectAndRestoreViews: function() {
            // Check for specific page contexts
            const contexts = this.detectActiveContexts();
            
            contexts.forEach(context => {
                this.restoreViewForContext(context);
            });
        },

        /**
         * Detect which contexts are present on the current page
         */
        detectActiveContexts: function() {
            const activeContexts = [];
            
            for (const [key, context] of Object.entries(this.config.viewContexts)) {
                const container = document.getElementById(context.containerId);
                if (container) {
                    activeContexts.push({
                        key: key,
                        config: context
                    });
                }
            }
            
            return activeContexts;
        },

        /**
         * Restore view for a specific context
         */
        restoreViewForContext: function(context) {
            const { config } = context;
            
            // Priority 1: URL/request param via hidden input (set by Blade)
            const input = document.getElementById(config.viewInputId);
            const fromUrl = input && input.value ? input.value : null;
            
            // Priority 2: localStorage (survives pagination when param might be absent)
            let fromStorage = null;
            try {
                fromStorage = localStorage.getItem(config.storageKey);
            } catch (e) {
                console.warn('localStorage access failed:', e);
            }
            
            // Determine view (default to 'table')
            const view = (fromUrl && ['table', 'card'].includes(fromUrl)) ? fromUrl :
                        (fromStorage && ['table', 'card'].includes(fromStorage)) ? fromStorage :
                        'table';
            
            this.applyView(context, view, false);
            
            // Sync hidden input so any subsequent form submit sends the correct value
            if (input && input.value !== view) {
                input.value = view;
            }
        },

        /**
         * Apply view for a specific context
         */
        applyView: function(context, view, persist = true) {
            const { config, key } = context;
            
            // Find the view containers
            const [tableViewId, cardViewId] = config.viewTargets;
            const tableView = document.getElementById(tableViewId);
            const cardView = document.getElementById(cardViewId);
            
            // Apply visibility
            if (tableView && cardView) {
                if (view === 'card') {
                    tableView.classList.add('hidden');
                    cardView.classList.remove('hidden');
                } else {
                    cardView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                }
            }
            
            // Update hidden input
            const input = document.getElementById(config.viewInputId);
            if (input && input.value !== view) {
                input.value = view;
            }
            
            // Persist to localStorage
            if (persist) {
                try {
                    localStorage.setItem(config.storageKey, view);
                } catch (e) {
                    console.warn('localStorage save failed:', e);
                }
            }
            
            // Update toggle button styles for this context
            this.updateToggleStyles(context, view);
            
            // Dispatch custom event for other scripts that might need to know
            const event = new CustomEvent('print-resources-view-changed', {
                detail: {
                    context: key,
                    view: view,
                    timestamp: Date.now()
                }
            });
            document.dispatchEvent(event);
        },

        /**
         * Update toggle button styles for a context
         */
        updateToggleStyles: function(context, activeView) {
            const { config, key } = context;
            
            // Find all view toggle buttons that target this context
            const selectors = [
                `.view-toggle-btn[data-target="${key}"]`,
                `.view-toggle-btn[data-view]` // Fallback for pages without data-target
            ];
            
            let buttons = [];
            selectors.forEach(selector => {
                const found = document.querySelectorAll(selector);
                if (found.length) {
                    buttons = [...buttons, ...found];
                }
            });
            
            // For pages with data-target (school/division), filter by target
            buttons = buttons.filter(btn => {
                const target = btn.getAttribute('data-target');
                return !target || target === key;
            });
            
            buttons.forEach(btn => {
                const view = btn.getAttribute('data-view');
                const isActive = view === activeView;
                
                // Toggle classes for active state
                if (isActive) {
                    btn.classList.add('bg-white', 'shadow', 'text-blue-600');
                    btn.classList.remove('text-gray-500', 'hover:text-gray-700');
                } else {
                    btn.classList.remove('bg-white', 'shadow', 'text-blue-600');
                    btn.classList.add('text-gray-500', 'hover:text-gray-700');
                }
            });
        },

        /**
         * Attach event listeners for view toggle buttons
         */
        attachEventListeners: function() {
            // Use event delegation for dynamically added buttons
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.view-toggle-btn');
                if (!btn) return;
                
                e.preventDefault();
                
                const view = btn.getAttribute('data-view');
                if (!view || !['table', 'card'].includes(view)) return;
                
                // Determine which context this button belongs to
                const target = btn.getAttribute('data-target');
                let contextToApply = null;
                
                if (target) {
                    // Button has explicit target
                    const contextConfig = this.config.viewContexts[target];
                    if (contextConfig) {
                        contextToApply = {
                            key: target,
                            config: contextConfig
                        };
                    }
                } else {
                    // Auto-detect context based on container
                    const activeContexts = this.detectActiveContexts();
                    if (activeContexts.length === 1) {
                        contextToApply = activeContexts[0];
                    } else {
                        // Try to find closest context container
                        const container = btn.closest('[data-view-context]');
                        if (container) {
                            const contextKey = container.getAttribute('data-view-context');
                            const contextConfig = this.config.viewContexts[contextKey];
                            if (contextConfig) {
                                contextToApply = {
                                    key: contextKey,
                                    config: contextConfig
                                };
                            }
                        }
                    }
                }
                
                if (contextToApply) {
                    this.applyView(contextToApply, view, true);
                } else {
                    // Fallback: try to update all contexts (legacy support)
                    this.updateAllViews(view);
                }
            });
            
            // Listen for form submissions to preserve view
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.hasAttribute('data-ajax')) {
                    // For AJAX forms, ensure view input is up to date
                    const activeContexts = this.detectActiveContexts();
                    activeContexts.forEach(context => {
                        const input = document.getElementById(context.config.viewInputId);
                        if (input) {
                            const currentView = localStorage.getItem(context.config.storageKey) || 'table';
                            if (input.value !== currentView) {
                                input.value = currentView;
                            }
                        }
                    });
                }
            });
        },

        /**
         * Legacy fallback: update all views (for pages without context detection)
         */
        updateAllViews: function(view) {
            const allTableViews = document.querySelectorAll('[id$="-table-view"], [id="table-view"]');
            const allCardViews = document.querySelectorAll('[id$="-card-view"], [id="card-view"]');
            
            allTableViews.forEach(el => {
                if (view === 'card') {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            
            allCardViews.forEach(el => {
                if (view === 'card') {
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });
            
            // Update all hidden inputs
            const viewInputs = document.querySelectorAll('[id$="-view-input"], [id="view-input"]');
            viewInputs.forEach(input => {
                if (input.value !== view) {
                    input.value = view;
                }
            });
            
            // Persist to localStorage (legacy key)
            try {
                localStorage.setItem('print-resources-view', view);
            } catch (e) {}
            
            // Update all toggle buttons
            document.querySelectorAll('.view-toggle-btn').forEach(btn => {
                const btnView = btn.getAttribute('data-view');
                const isActive = btnView === view;
                if (isActive) {
                    btn.classList.add('bg-white', 'shadow', 'text-blue-600');
                    btn.classList.remove('text-gray-500', 'hover:text-gray-700');
                } else {
                    btn.classList.remove('bg-white', 'shadow', 'text-blue-600');
                    btn.classList.add('text-gray-500', 'hover:text-gray-700');
                }
            });
        },

        /**
         * Setup mutation observers to restore views after AJAX content updates
         */
        setupMutationObservers: function() {
            const activeContexts = this.detectActiveContexts();
            
            activeContexts.forEach(context => {
                const container = document.getElementById(context.config.containerId);
                if (container && !container.hasAttribute('data-view-observer')) {
                    container.setAttribute('data-view-observer', 'true');
                    
                    const observer = new MutationObserver(() => {
                        this.restoreViewForContext(context);
                    });
                    
                    observer.observe(container, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        },

        /**
         * Public API: Force refresh view for a specific context
         */
        refreshView: function(contextKey) {
            const contextConfig = this.config.viewContexts[contextKey];
            if (contextConfig) {
                this.restoreViewForContext({
                    key: contextKey,
                    config: contextConfig
                });
            }
        },

        /**
         * Public API: Get current view for a context
         */
        getCurrentView: function(contextKey) {
            try {
                const contextConfig = this.config.viewContexts[contextKey];
                if (contextConfig) {
                    const stored = localStorage.getItem(contextConfig.storageKey);
                    if (stored && ['table', 'card'].includes(stored)) {
                        return stored;
                    }
                }
            } catch (e) {}
            return 'table';
        },

        /**
         * Public API: Set view for a context programmatically
         */
        setView: function(contextKey, view) {
            if (!['table', 'card'].includes(view)) return false;
            
            const contextConfig = this.config.viewContexts[contextKey];
            if (contextConfig) {
                this.applyView({
                    key: contextKey,
                    config: contextConfig
                }, view, true);
                return true;
            }
            return false;
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => PrintResourcesViewManager.init());
    } else {
        PrintResourcesViewManager.init();
    }

    // Expose globally for backward compatibility and external use
    window.PrintResourcesViewManager = PrintResourcesViewManager;
    window.PrintResourcesViewToggle = PrintResourcesViewManager; // Legacy alias

})(window, document);