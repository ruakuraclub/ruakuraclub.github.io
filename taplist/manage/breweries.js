document.addEventListener("DOMContentLoaded", () => {
  fetchBreweries();
  document.getElementById("breweryForm").addEventListener("submit", saveBrewery);
});

function fetchBreweries() {
  fetch('backend.php?action=get_breweries')
      .then(response => response.json())
      .then(data => {
          const breweryList = document.getElementById('breweryList');
          breweryList.innerHTML = '';
          data.breweries.forEach(brewery => {
              breweryList.innerHTML += `<div>${brewery.name} (${brewery.location}) <button onclick="editBrewery(${brewery.id})">Edit</button></div>`;
          });
      });
}

function searchBreweries() {
  const searchQuery = document.getElementById('searchBrewery').value;
  fetch(`backend.php?action=search_breweries&query=${searchQuery}`)
      .then(response => response.json())
      .then(data => {
          const breweryList = document.getElementById('breweryList');
          breweryList.innerHTML = '';
          data.breweries.forEach(brewery => {
              breweryList.innerHTML += `<div>${brewery.name} (${brewery.location}) <button onclick="editBrewery(${brewery.id})">Edit</button></div>`;
          });
      });
}

function saveBrewery(event) {
  event.preventDefault();
  const breweryId = document.getElementById('breweryId').value;
  const breweryName = document.getElementById('breweryName').value;
  const breweryLocation = document.getElementById('breweryLocation').value;
  fetch('backend.php?action=save_brewery', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: breweryId, name: breweryName, location: breweryLocation })
  }).then(response => response.json())
  .then(data => {
      fetchBreweries();
      document.getElementById('breweryForm').reset();
  });
}

function editBrewery(id) {
  fetch(`backend.php?action=get_brewery&id=${id}`)
      .then(response => response.json())
      .then(data => {
          document.getElementById('breweryId').value = data.brewery.id;
          document.getElementById('breweryName').value = data.brewery.name;
          document.getElementById('breweryLocation').value = data.brewery.location;
      });
}
