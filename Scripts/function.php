function your_function_name() {

    wp_enqueue_script(
        'unique-script-handle',  // Handle (unique name)
        'script-source-or-path', // Source (local or CDN)
        array(),                 // Dependencies (e.g., ['jquery'])
        '1.0.0',                 // Version (optional)
        true                     // Load in footer (true) or head (false)
    );

}

add_action('wp_enqueue_scripts', 'your_function_name');

// 'wp_enqueue_scripts' is called a default hook; you must read how many default hooks there are in WP.
