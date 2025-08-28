<?php
/**
 * Add a search field to product categories in WooCommerce quick edit and product edit screens.
 */
 
function add_product_category_search() {
    global $pagenow, $post_type;

    // Only run on relevant admin pages for products
    if ( ( $pagenow === 'edit.php' || $pagenow === 'post.php' || $pagenow === 'post-new.php' ) && $post_type === 'product' ) {
        // Internationalized strings for accessibility and translation
        $search_placeholder = esc_attr__( 'Search categories...', 'your-text-domain' );
        $clear_search_text = esc_html__( 'Clear Search', 'your-text-domain' );

        // Enqueue custom admin styles for better maintainability
        wp_enqueue_style(
            'product-cat-search-style',
            get_stylesheet_directory_uri() . '/admin-product-cat-search.css',
            [],
            '1.0.0'
        );

        ?>
        <script type="text/javascript">
            (function($) {
                // Debug to confirm script is running
                console.log('Product category search script loaded for <?php echo $pagenow; ?> (WooCommerce version: <?php echo WC()->version; ?>)');

                // Function to add and initialize search functionality
                function initializeCategorySearch(containerSelector, context) {
                    var categoryContainer = $(containerSelector);

                    // Check if the container exists
                    if (categoryContainer.length === 0) {
                        console.warn('Category checklist not found for selector: ' + containerSelector + ' in ' + context);
                        // Log nearby DOM for debugging
                        var parentContainer = context === 'quick-edit' ? $('.inline-edit-row') : $('#product_catdiv');
                        if (parentContainer.length) {
                            console.log(context + ' container HTML:', parentContainer.html());
                        } else {
                            console.warn(context + ' parent container not found');
                        }
                        return false;
                    }

                    // Prevent duplicate search fields
                    if (categoryContainer.siblings('.product-cat-search').length === 0) {
                        categoryContainer.before(`
                            <input type="text" class="product-cat-search" placeholder="<?php echo $search_placeholder; ?>" aria-label="<?php echo $search_placeholder; ?>" />
                            <button type="button" class="button clear-product-cat-search" aria-label="<?php echo $clear_search_text; ?>"><?php echo $clear_search_text; ?></button>
                        `);
                        console.log('Search field added for selector: ' + containerSelector + ' in ' + context);
                    }

                    var debounceTimeout;

                    // Live search with debounce
                    categoryContainer.siblings('.product-cat-search').off('keyup.search').on('keyup.search', function() {
                        var searchTerm = $(this).val().toLowerCase();
                        clearTimeout(debounceTimeout);
                        debounceTimeout = setTimeout(function() {
                            categoryContainer.find('li').each(function() {
                                var categoryName = $(this).text().toLowerCase();
                                var isChecked = $(this).find('input[type="checkbox"]').is(':checked');
                                if (categoryName.indexOf(searchTerm) !== -1 || isChecked) {
                                    $(this).show();
                                } else {
                                    $(this).hide();
                                }
                            });
                        }, 200); // Reduced debounce for responsiveness
                    });

                    // Clear search functionality
                    categoryContainer.siblings('.clear-product-cat-search').off('click.search').on('click.search', function() {
                        $(this).siblings('.product-cat-search').val('');
                        categoryContainer.find('li').show();
                    });

                    return true;
                }

                // Initialize for quick edit
                if ('<?php echo $pagenow; ?>' === 'edit.php') {
                    // Comprehensive list of possible selectors
                    var selectors = [
                        '.inline-edit-taxonomy-product_cat-wrap .categorychecklist',
                        'ul.product_catchecklist',
                        'ul.cat-checklist',
                        'ul.cat-checklist.product_catchecklist',
                        '.inline-edit-taxonomy-product_cat-wrap ul',
                        'ul.product_cat-checklist',
                        'ul.taxonomy-product_cat-checklist',
                        '.inline-edit-col-right ul.categorychecklist' // Fallback for custom layouts
                    ];

                    // Function to try initializing with available selectors
                    function tryInitializeQuickEdit() {
                        for (var i = 0; i < selectors.length; i++) {
                            if (initializeCategorySearch(selectors[i], 'quick-edit')) {
                                return true;
                            }
                        }
                        console.warn('No quick edit category checklist found. Tried selectors:', selectors);
                        return false;
                    }

                    // Run on quick edit click
                    $(document).on('click', '.editinline', function() {
                        var quickEditRow = $('.inline-edit-row');
                        if (quickEditRow.length === 0) {
                            console.warn('Quick edit row (.inline-edit-row) not found');
                            return;
                        }

                        // Try initializing immediately
                        if (tryInitializeQuickEdit()) {
                            return;
                        }

                        // Use MutationObserver for dynamic loading
                        var observer = new MutationObserver(function(mutations, obs) {
                            if (tryInitializeQuickEdit()) {
                                obs.disconnect(); // Stop observing once found
                            }
                        });
                        observer.observe(document.body, { childList: true, subtree: true });
                    });
                }

                // Initialize for product edit screen
                if ('<?php echo $pagenow; ?>' === 'post.php' || '<?php echo $pagenow; ?>' === 'post-new.php') {
                    $(document).ready(function() {
                        initializeCategorySearch('#product_catdiv .categorychecklist', 'product-edit');
                    });
                }
            })(jQuery);
        </script>
        <?php
    }
}
// Hook into admin_print_footer_scripts for reliable script injection
add_action( 'admin_print_footer_scripts', 'add_product_category_search', 20 );

// Enqueue admin styles separately for maintainability
function enqueue_product_category_search_styles() {
    global $pagenow, $post_type;
    if ( ( $pagenow === 'edit.php' || $pagenow === 'post.php' || $pagenow === 'post-new.php' ) && $post_type === 'product' ) {
        $css = '
            .product-cat-search {
                width: 100%;
                border: 1px solid #ddd;
                padding: 8px;
                font-size: 14px;
                margin-bottom: 10px;
                box-sizing: border-box;
            }
            .clear-product-cat-search {
                background: #f0f0f0;
                border: 1px solid #ddd;
                margin-bottom: 10px;
            }
        ';
        wp_add_inline_style( 'wp-admin', $css );
    }
}
add_action( 'admin_enqueue_scripts', 'enqueue_product_category_search_styles' );
?>
