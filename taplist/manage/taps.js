document.addEventListener("DOMContentLoaded", () => {
  fetchTaps();
});

function fetchTaps() {
  fetch('backend.php?action=get_taps')
      .then(response => response.json())
      .then(data => {
          const tapList = document.getElementById('tapList');
          tapList.innerHTML = '';
          data.taps.forEach(tap => {
              tapList.innerHTML += `
                  <div>
                      <h2>${tap.name}</h2>
                      <p>In Service: ${tap.in_service ? 'Yes' : 'No'}</p>
                      <p>Keg ID: ${tap.keg_id}</p>
                      <label for="dateServed_${tap.id}">Date Served:</label>
                      <input type="date" id="dateServed_${tap.id}" value="${new Date().toISOString().split('T')[0]}">
                      <button onclick="recordDateServed(${tap.id})">Record Date Served</button>
                      <button onclick="markKegEmpty(${tap.id})">Mark Keg Empty</button>
                  </div>`;
          });
      });
}

function recordDateServed(tapId) {
  const dateServed = document.getElementById(`dateServed_${tapId}`).value;
  fetch('backend.php?action=record_date_served', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ tapId, dateServed })
  }).then(response => response.json())
  .then(data => {
      if (data.status === 'success') {
          alert('Date served recorded successfully.');
      } else {
          alert('Error recording date served: ' + data.message);
      }
  });
}

function markKegEmpty(tapId) {
  fetch('backend.php?action=mark_keg_empty', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ tapId })
  }).then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      alert('Keg marked as empty successfully.');
      fetchTaps(); // Refresh the tap list
    } else {
      alert('Error marking keg as empty: ' + data.message);
    }
  });
}