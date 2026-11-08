document.addEventListener("DOMContentLoaded", () => {
  fetchKegs();
  fetchBeverages();
  document.getElementById("kegForm").addEventListener("submit", saveKeg);
});

function fetchKegs() {
  fetch('backend.php?action=get_kegs')
      .then(response => response.json())
      .then(data => {
          const kegList = document.getElementById('kegList');
          kegList.innerHTML = '';
          data.kegs.forEach(keg => {
              kegList.innerHTML += `<div>${keg.name} (${keg.volume}L) - ${keg.price} <button onclick="editKeg(${keg.id})">Edit</button></div>`;
          });
      });
}

function fetchBeverages() {
  fetch('backend.php?action=get_beverages')
      .then(response => response.json())
      .then(data => {
          const kegBeverage = document.getElementById('kegBeverage');
          kegBeverage.innerHTML = '';
          data.beverages.forEach(beverage => {
              kegBeverage.innerHTML += `<option value="${beverage.id}">${beverage.name}</option>`;
          });
      });
}

function searchKegs() {
  const searchQuery = document.getElementById('searchKeg').value;
  fetch(`backend.php?action=search_kegs&query=${searchQuery}`)
      .then(response => response.json())
      .then(data => {
          const kegList = document.getElementById('kegList');
          kegList.innerHTML = '';
          data.kegs.forEach(keg => {
              kegList.innerHTML += `<div>${keg.name} (${keg.volume}L) - ${keg.price} <button onclick="editKeg(${keg.id})">Edit</button></div>`;
          });
      });
}

function saveKeg(event) {
  event.preventDefault();
  const kegId = document.getElementById('kegId').value;
  const kegName = document.getElementById('kegName').value;
  const kegVolume = document.getElementById('kegVolume').value;
  const kegBeverage = document.getElementById('kegBeverage').value;
  const kegPrice = document.getElementById('kegPrice').value;
  const kegHalf = document.getElementById('kegHalf').value;
  const kegHandle = document.getElementById('kegHandle').value;
  const kegJug = document.getElementById('kegJug').value;

  fetch('backend.php?action=save_keg', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
          id: kegId, 
          name: kegName, 
          volume: kegVolume, 
          beverage_id: kegBeverage, 
          price: kegPrice, 
          half: kegHalf, 
          handle: kegHandle, 
          jug: kegJug 
      })
  }).then(response => response.json())
  .then(data => {
      fetchKegs();
      document.getElementById('kegForm').reset();
  });
}

function editKeg(id) {
  fetch(`backend.php?action=get_keg&id=${id}`)
      .then(response => response.json())
      .then(data => {
          document.getElementById('kegId').value = data.keg.id;
          document.getElementById('kegName').value = data.keg.name;
          document.getElementById('kegVolume').value = data.keg.volume;
          document.getElementById('kegBeverage').value = data.keg.beverage_id;
          document.getElementById('kegPrice').value = data.keg.price;
          document.getElementById('kegHalf').value = data.keg.half;
          document.getElementById('kegHandle').value = data.keg.handle;
          document.getElementById('kegJug').value = data.keg.jug;
        });
}