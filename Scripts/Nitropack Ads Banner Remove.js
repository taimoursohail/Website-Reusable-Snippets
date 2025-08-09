// Add this into Function.php

function hide_nitropack_template() {
    ?>
    <script>
    (function() {
        // Configuration object for easier future updates
        const config = {
            targetSelector: 'template[id^="nitro-"]', // More specific selector for NitroPack templates
            delay: 100, // Initial delay in ms
            maxRetries: 3, // Maximum retry attempts
            retryInterval: 50 // Interval between retries in ms
        };

        // Utility to hide an element safely
        const hideElement = (element) => {
            if (element && typeof element.style !== 'undefined') {
                element.style.display = 'none';
            }
        };

        // Main function to find and hide the target template
        const hideTemplate = () => {
            let retries = 0;

            const attemptHide = () => {
                // Find all template elements matching the selector
                const templates = document.querySelectorAll(config.targetSelector);
                const lastTemplate = templates[templates.length - 1];

                if (!lastTemplate) {
                    if (retries < config.maxRetries) {
                        retries++;
                        setTimeout(attemptHide, config.retryInterval);
                    }
                    return;
                }

                const id = lastTemplate.getAttribute('id');
                if (!id) return;

                const targetElement = document.getElementById(id);
                if (!targetElement) {
                    if (retries < config.maxRetries) {
                        retries++;
                        setTimeout(attemptHide, config.retryInterval);
                    }
                    return;
                }

                // Hide the target element and its related siblings
                hideElement(targetElement);
                
                // Safely handle next siblings
                const nextSibling = targetElement.nextElementSibling;
                if (nextSibling && nextSibling.nextElementSibling) {
                    hideElement(nextSibling.nextElementSibling);
                }
            };

            // Use MutationObserver to handle dynamic DOM changes
            const observer = new MutationObserver((mutations, obs) => {
                if (document.querySelector(config.targetSelector)) {
                    attemptHide();
                    obs.disconnect(); // Disconnect after successful execution
                }
            });

            // Start observing DOM changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Initial attempt after delay
            setTimeout(attemptHide, config.delay);
        };

        // Execute when DOM is fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', hideTemplate);
        } else {
            hideTemplate();
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'hide_nitropack_template');
