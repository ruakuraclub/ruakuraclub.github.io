document.addEventListener("DOMContentLoaded", () => {
  fetchInServiceTaps();
});

function fetchInServiceTaps() {
  fetch('backend.php?action=get_in_service_taps')
      .then(response => response.json())
      .then(data => {
          const template = document.getElementById('tap-template').innerHTML;
          const rendered = Mustache.render(template, data);
          document.getElementById('tapList').innerHTML = rendered;
      });
}
