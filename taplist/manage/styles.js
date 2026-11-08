document.addEventListener("DOMContentLoaded", () => {
  fetchStyles();
  document.getElementById("styleForm").addEventListener("submit", saveStyle);
});

function fetchStyles() {
  fetch('backend.php?action=get_styles')
      .then(response => response.json())
      .then(data => {
          const styleList = document.getElementById('styleList');
          styleList.innerHTML = '';
          data.styles.forEach(style => {
              styleList.innerHTML += `<div>${style.name} <button onclick="editStyle(${style.id})">Edit</button></div>`;
          });
      });
}

function searchStyles() {
  const searchQuery = document.getElementById('searchStyle').value;
  fetch(`backend.php?action=search_styles&query=${searchQuery}`)
      .then(response => response.json())
      .then(data => {
          const styleList = document.getElementById('styleList');
          styleList.innerHTML = '';
          data.styles.forEach(style => {
              styleList.innerHTML += `<div>${style.name} <button onclick="editStyle(${style.id})">Edit</button></div>`;
          });
      });
}

function saveStyle(event) {
  event.preventDefault();
  const styleId = document.getElementById('styleId').value;
  const styleName = document.getElementById('styleName').value;
  fetch('backend.php?action=save_style', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: styleId, name: styleName })
  }).then(response => response.json())
  .then(data => {
      fetchStyles();
      document.getElementById('styleForm').reset();
  });
}

function editStyle(id) {
  fetch(`backend.php?action=get_style&id=${id}`)
      .then(response => response.json())
      .then(data => {
          document.getElementById('styleId').value = data.style.id;
          document.getElementById('styleName').value = data.style.name;
      });
}
