document.addEventListener("DOMContentLoaded", () => {
  fetchConsumption();
});

function fetchConsumption() {
  fetch('backend.php?action=get_consumption')
      .then(response => response.json())
      .then(data => {
          const consumptionList = document.getElementById('consumptionList');
          consumptionList.innerHTML = '';
          data.kegs.forEach(keg => {
              consumptionList.innerHTML += `
                  <div>
                      <h2>${keg.name}</h2>
                      <p>Served: ${keg.date_served}</p>
                      <label for="halves_${keg.id}">Halves Served:</label>
                      <input type="number" id="halves_${keg.id}" value="${keg.halves_served}">
                      <label for="handles_${keg.id}">Handles Served:</label>
                      <input type="number" id="handles_${keg.id}" value="${keg.handles_served}">
                      <label for="jugs_${keg.id}">Jugs Served:</label>
                      <input type="number" id="jugs_${keg.id}" value="${keg.jugs_served}">
                      <button onclick="updateConsumption(${keg.id})">Update</button>
                  </div>`;
          });
      });
}

function updateConsumption(kegId) {
  const halvesServed = document.getElementById(`halves_${kegId}`).value;
  const handlesServed = document.getElementById(`handles_${kegId}`).value;
  const jugsServed = document.getElementById(`jugs_${kegId}`).value;

  fetch('backend.php?action=update_consumption', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ kegId, halvesServed, handlesServed, jugsServed })
  }).then(response => response.json())
  .then(data => {
      if (data.status === 'success') {
          alert('Consumption updated successfully.');
          fetchConsumption(); // Refresh the list
      } else {
          alert('Error updating consumption: ' + data.message);
      }
  });
}
