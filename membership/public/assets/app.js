// Subscription form: changing the start date or term recalculates the expiry date
// (start + term years, less a day). The expiry can still be changed by hand afterwards.
document.addEventListener('DOMContentLoaded', function () {
  var start = document.querySelector('input[name="start_date"]');
  var term = document.querySelector('input[name="term_years"]');
  var expiry = document.querySelector('input[name="expiry_date"]');
  if (!start || !term || !expiry) {
    return;
  }
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  function recalculate() {
    var years = parseInt(term.value, 10);
    if (!start.value || !(years >= 1 && years <= 10)) {
      return;
    }
    var d = new Date(start.value + 'T00:00:00');
    d.setFullYear(d.getFullYear() + years);
    d.setDate(d.getDate() - 1);
    expiry.value = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }
  start.addEventListener('change', recalculate);
  term.addEventListener('input', recalculate);
});

// Live search and filters: a GET form with data-live updates the results as you type or
// change a filter, without reloading the page. data-live lists the parts of the page to
// refresh (CSS selectors, comma-separated). The form's button still works without JavaScript.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-live]').forEach(function (form) {
    var selectors = form.getAttribute('data-live').split(',').map(function (s) { return s.trim(); });
    var timer = null;
    var pending = null;

    function refresh() {
      var url = form.getAttribute('action') + '?' + new URLSearchParams(new FormData(form)).toString();
      if (pending) {
        pending.abort();
      }
      pending = new AbortController();
      fetch(url, { credentials: 'same-origin', signal: pending.signal })
        .then(function (response) {
          if (!response.ok || response.redirected) {
            window.location.href = url;   // e.g. logged out: let the page handle it
            return null;
          }
          return response.text();
        })
        .then(function (html) {
          if (html === null) {
            return;
          }
          var fresh = new DOMParser().parseFromString(html, 'text/html');
          selectors.forEach(function (selector) {
            var current = document.querySelector(selector);
            var updated = fresh.querySelector(selector);
            if (current && updated) {
              current.replaceWith(updated);
            }
          });
          history.replaceState(null, '', url);
        })
        .catch(function (error) {
          if (error.name !== 'AbortError') {
            form.submit();
          }
        });
    }

    form.addEventListener('input', function (event) {
      if (event.target.matches('input[type="search"], input[type="text"]')) {
        clearTimeout(timer);
        timer = setTimeout(refresh, 300);   // wait for a pause in typing
      }
    });
    form.addEventListener('change', function (event) {
      if (event.target.matches('select')) {
        clearTimeout(timer);
        refresh();
      }
    });
  });
});

// "Print / save as PDF" buttons.
document.addEventListener('click', function (event) {
  if (event.target.closest('[data-print]')) {
    window.print();
  }
});

// Close an open menu when clicking anywhere else, and keep only one menu open.
document.addEventListener('click', function (event) {
  document.querySelectorAll('details.dropdown[open]').forEach(function (menu) {
    if (!menu.contains(event.target)) {
      menu.removeAttribute('open');
    }
  });
});
