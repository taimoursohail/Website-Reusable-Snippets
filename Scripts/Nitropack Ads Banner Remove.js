// Add this into Function.php

function hide_last_template_script() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(function() {
            var templates = document.querySelectorAll("template");
            var lastTemplate = templates[templates.length - 1];
            if (!lastTemplate) return;
            var id = lastTemplate.getAttribute("id");
            console.log(id);
            var el = document.getElementById(id);
            if (el) {
                el.style.display = "none";
                if (el.nextElementSibling && el.nextElementSibling.nextElementSibling) {
                    el.nextElementSibling.nextElementSibling.style.display = "none";
                }
            }
        }, 100);
    });
    </script>
    <?php
}
add_action('wp_footer', 'hide_last_template_script');
