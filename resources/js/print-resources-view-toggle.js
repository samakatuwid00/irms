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
                },
                // For Library Hub tab in print-resource-region-account.blade.php
                hub: {
                    storageKey: 'print-resources-hub-view',
                    containerId: 'hub-results-container',
                    viewInputId: 'hub-view-input',
                    viewTargets: ['hub-table-view', 'hub-card-view']
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

            // Restore per-page selector from localStorage (only when the URL doesn't already
            // have an explicit per_page param — the server-rendered selected option takes precedence)
            const urlPerPage = new URLSearchParams(window.location.search).get('per_page');
            if (!urlPerPage) {
                try {
                    const saved = localStorage.getItem('print-resources-per-page');
                    if (saved) {
                        document.querySelectorAll('.per-page-select').forEach(select => {
                            if ([...select.options].some(o => o.value === saved)) {
                                select.value = saved;
                            }
                        });
                        document.querySelectorAll('.per-page-hidden-input').forEach(input => {
                            input.value = saved;
                        });
                    }
                } catch (_) {}
            }
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
                        'card';
            
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
            
            // Per-page selector: AJAX-reload only the results container, same as pagination clicks
            document.addEventListener('change', (e) => {
                const select = e.target.closest('.per-page-select');
                if (!select) return;

                const perPage = select.value;

                // Sync all hidden per-page inputs so subsequent form submits carry the value
                document.querySelectorAll('.per-page-hidden-input').forEach(input => {
                    input.value = perPage;
                });

                // Persist choice
                try {
                    localStorage.setItem('print-resources-per-page', perPage);
                } catch (_) {}

                // Determine which results container this selector belongs to
                const contextKey = select.dataset.context; // 'division' | 'school' | etc.
                const contextConfig = this.config.viewContexts[contextKey];
                const containerId = contextConfig
                    ? contextConfig.containerId
                    : (select.closest('[data-view-context]')?.getAttribute('data-view-context')
                        ? this.config.viewContexts[select.closest('[data-view-context]').getAttribute('data-view-context')]?.containerId
                        : null);

                // Build fetch URL from the nearest form, resetting to page 1
                const form = select.closest('form[data-ajax]') || document.querySelector('form[data-ajax]');
                if (!form) return;

                const params = new URLSearchParams(new FormData(form));
                params.set('per_page', perPage);
                // Reset pagination to page 1 when entries-per-page changes
                params.delete('page');
                params.delete('division_page');

                this._fetchIntoContainer(
                    `${form.action || window.location.pathname}?${params.toString()}`,
                    containerId
                );
            });

            
            // Intercept data-ajax form submissions — fetch instead of full reload
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form.hasAttribute('data-ajax')) return;

                e.preventDefault();

                // Ensure view inputs are up to date before serialising
                const activeContexts = this.detectActiveContexts();
                activeContexts.forEach(context => {
                    const input = document.getElementById(context.config.viewInputId);
                    if (input) {
                        const currentView = (() => {
                            try { return localStorage.getItem(context.config.storageKey); } catch(_) { return null; }
                        })() || 'card';
                        if (input.value !== currentView) input.value = currentView;
                    }
                });

                const params = new URLSearchParams(new FormData(form));
                const url = `${form.action || window.location.pathname}?${params.toString()}`;

                // Determine target container: explicit attribute > tab hidden input map > nearest DOM fallback
                let targetId = form.dataset.targetContainer || null;

                if (!targetId) {
                    // Map the form's tab value to its results container id
                    const tabInput = form.querySelector('input[name="tab"]');
                    const tabValue = tabInput ? tabInput.value : null;
                    const tabContainerMap = {
                        'school':      'school-results-container',
                        'library-hub': 'hub-results-container',
                        'division':    'division-results-container',
                        'region':      'table-results-container',
                    };
                    targetId = (tabValue && tabContainerMap[tabValue]) ? tabContainerMap[tabValue] : null;
                }

                // Last-resort: walk up the DOM from the form
                if (!targetId) {
                    targetId = form.closest('.tab-content')?.querySelector('[id$="-results-container"]')?.id || null;
                }

                this._fetchIntoContainer(url, targetId);
            });

            // Intercept pagination link clicks — same AJAX swap used by per-page changes
            document.addEventListener('click', (e) => {
                const link = e.target.closest('nav[role="navigation"] a, .pagination a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href === '#') return;

                e.preventDefault();

                // Walk up to find the results container that owns this paginator
                const container = link.closest('[id$="-results-container"]');
                const containerId = container ? container.id : null;

                // Preserve the current per-page value in the paginated URL
                const url = new URL(href, window.location.href);
                const savedPerPage = (() => {
                    try { return localStorage.getItem('print-resources-per-page'); } catch(_) { return null; }
                })();
                if (savedPerPage) url.searchParams.set('per_page', savedPerPage);

                this._fetchIntoContainer(url.toString(), containerId);
            });
        },
         
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
         * Fetch a URL via AJAX and swap only the given results container.
         * The server returns the full partial view; we extract the container's
         * innerHTML from the response HTML so the surrounding chrome (tabs,
         * toolbar) is never re-rendered.
         */
        _fetchIntoContainer: function(url, containerId) {
            const container = containerId ? document.getElementById(containerId) : null;

            // Show a subtle loading state
            if (container) container.style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                }
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.text();
            })
            .then(html => {
                // Parse the partial HTML returned by the server
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                if (container && containerId) {
                    // Extract only the matching container from the response
                    const incoming = doc.getElementById(containerId);
                    if (incoming) {
                        container.innerHTML = incoming.innerHTML;
                        if (typeof initCoverImages === 'function') initCoverImages();
                    } else {
                        // Fallback: the server returned a bare fragment — use it whole
                        container.innerHTML = html;
                    }
                    container.style.opacity = '';
                } else {
                    // No specific container — replace the whole partial wrapper
                    const wrapper = doc.querySelector('.tab-content-wrapper');
                    const localWrapper = document.querySelector('.tab-content-wrapper');
                    if (wrapper && localWrapper) {
                        localWrapper.innerHTML = wrapper.innerHTML;
                    }
                }

                // Re-run view restoration so table/card state is preserved after the swap
                // Use a short delay to let the DOM settle before restoring views
                setTimeout(() => {
                    this.detectAndRestoreViews();
                    // Re-attach observers for any newly injected containers
                    this.setupMutationObservers();
                }, 0);

                // Update the browser URL without a reload so pagination links stay bookmarkable
                window.history.pushState({}, '', url);
            })
            .catch(err => {
                console.error('Print resources AJAX load failed:', err);
                if (container) container.style.opacity = '';
            });
        },

        /**
         */
        setupMutationObservers: function() {
            const activeContexts = this.detectActiveContexts();
            
            activeContexts.forEach(context => {
                const container = document.getElementById(context.config.containerId);
                if (container && !container.hasAttribute('data-view-observer')) {
                    container.setAttribute('data-view-observer', 'true');

                    let debounceTimer = null;
                    const observer = new MutationObserver(() => {
                        // Skip if the container is in the middle of an AJAX swap (opacity dimmed)
                        if (container.style.opacity === '0.5') return;
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(() => {
                            this.restoreViewForContext(context);
                        }, 50);
                    });
                    
                    observer.observe(container, {
                        childList: true,
                        subtree: false  // only direct children — avoids re-firing on every inner DOM change
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
            return 'card';
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