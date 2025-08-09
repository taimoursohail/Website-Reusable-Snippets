<script>
document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        var templates = document.querySelectorAll("template");
        var lastTemplate = templates[templates.length - 1];
        var id = lastTemplate.getAttribute("id");
        console.log(id);
        document.getElementById(id).style.display = "none";
        document.getElementById(id).nextElementSibling.nextElementSibling.style.display = "none";
    }, 100);
});
</script>
