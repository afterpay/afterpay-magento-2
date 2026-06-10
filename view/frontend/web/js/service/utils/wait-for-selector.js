/**
 * Utility function to wait for a selector to appear in the DOM using MutationObserver
 * This replaces setInterval polling for better performance and responsiveness
 * Waits indefinitely for the selector to appear - no timeout
 * Supports both RequireJS and plain JavaScript usage
 *
 * @param {string} selector - CSS selector to wait for
 * @param {Object} options - Configuration options
 * @param {Element} options.container - Container element to observe (default: document.body)
 * @param {boolean} options.observeSubtree - Whether to observe subtree changes (default: true)
 * @returns {Promise<Element>} Promise that resolves with the found element
 */
(function (factory) {
    'use strict';

    window.waitForSelector = factory();
}(function () {
    'use strict';

    return function (selector, options) {
        options = options || {};
        const container = options.container || document.body;
        const observeSubtree = options.observeSubtree !== false;

        return new Promise(function (resolve) {
            const existingElement = document.querySelector(selector);
            if (existingElement) {
                resolve(existingElement);
                return;
            }

            let observer;
            let isResolved = false;

            observer = new MutationObserver(function (mutations) {
                if (isResolved) {
                    return;
                }

                // Check if the selector exists after DOM changes
                const element = document.querySelector(selector);
                if (element) {
                    isResolved = true;
                    observer.disconnect();
                    resolve(element);
                }
            });

            // Observe DOM changes: child nodes being added/removed, subtree changes
            observer.observe(container, {
                childList: true,
                subtree: observeSubtree,
                attributes: false,
                characterData: false
            });
        });
    };
}));
