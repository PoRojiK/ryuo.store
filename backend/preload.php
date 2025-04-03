<link rel="preload" href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@^2.0/dist/tailwind.min.css"></noscript>
<style>
  /* Скрываем страницу до загрузки Tailwind */
  body {
    margin: 0;
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
  }
  body.ready {
    opacity: 1;
  }
</style>
<script>
  // Убираем скрытие после загрузки Tailwind
  document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('ready');
  });
</script>
