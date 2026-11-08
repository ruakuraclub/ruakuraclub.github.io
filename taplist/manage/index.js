document.addEventListener("DOMContentLoaded", () => {
  loadPage('taps.html');
});

function loadPage(page) {
  fetch(page)
      .then(response => response.text())
      .then(data => {
          document.getElementById('content').innerHTML = data;
          // Re-attach scripts dynamically if the loaded page has any
          const scriptTags = document.getElementById('content').getElementsByTagName('script');
          for (let i = 0; i < scriptTags.length; i++) {
              eval(scriptTags[i].text);
          }
      });
}

const links = document.querySelectorAll('nav ul li a');
links.forEach(link => {
  link.addEventListener('click', (event) => {
      event.preventDefault();
      const page = event.target.getAttribute('href');
      loadPage(page);
  });
});
