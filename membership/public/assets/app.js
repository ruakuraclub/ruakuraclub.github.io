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
          document.dispatchEvent(new CustomEvent('live:updated'));
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
      if (event.target.matches('select, input[type="date"]')) {
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

// Email members: preview the chosen template for each ticked member (one per email address),
// with |< < > >| to flick through them. Refreshes when the template, mode or ticks change.
document.addEventListener('DOMContentLoaded', function () {
  var panel = document.getElementById('email-preview');
  if (!panel) {
    return;
  }
  var form = panel.closest('form');
  var frame = panel.querySelector('.preview-frame');
  var message = panel.querySelector('.preview-message');
  var position = panel.querySelector('.preview-position');
  var index = 0;
  var pending = null;

  // Ticked members grouped by email address, in the order shown.
  function recipients() {
    var groups = new Map();
    form.querySelectorAll('input[name="members[]"]:checked').forEach(function (box) {
      var key = box.getAttribute('data-email');
      if (!groups.has(key)) {
        groups.set(key, []);
      }
      groups.get(key).push(box.value);
    });
    return Array.from(groups.values());
  }

  function setText(selector, text) {
    panel.querySelector(selector).textContent = text;
  }

  function show() {
    var template = form.querySelector('select[name="template_id"]').value;
    var mode = form.querySelector('select[name="mode"]').value;
    var list = mode === 'bcc' ? [[]] : recipients();   // BCC: one email for everyone
    var empty = !template ? 'Choose a template to see a preview.' : (!list.length ? 'Tick at least one member to see a preview.' : '');

    index = Math.max(0, Math.min(index, list.length - 1));
    position.textContent = empty ? '' : (mode === 'bcc' ? 'One email for everyone' : (index + 1) + ' of ' + list.length);
    panel.querySelectorAll('[data-nav]').forEach(function (button) {
      var back = button.dataset.nav === 'first' || button.dataset.nav === 'prev';
      button.disabled = !!empty || (back ? index === 0 : index >= list.length - 1);
    });
    message.textContent = empty;
    frame.hidden = !!empty;
    if (pending) {
      pending.abort();   // a slower earlier request mustn't overwrite this one
      pending = null;
    }
    if (empty) {
      setText('.preview-to', '');
      setText('.preview-subject', '');
      return;
    }

    var query = new URLSearchParams({ template: template, mode: mode, members: list[index].join(',') }).toString();
    // A fresh frame each time: some browsers don't repaint a frame that's re-pointed at a sandboxed page.
    var fresh = frame.cloneNode(false);
    fresh.src = 'email-preview.php?' + query;
    frame.replaceWith(fresh);
    frame = fresh;
    pending = new AbortController();
    fetch('email-preview.php?' + query + '&format=json', { credentials: 'same-origin', signal: pending.signal })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.error) {
          message.textContent = data.error;
          frame.hidden = true;
          return;
        }
        setText('.preview-to', data.to);
        setText('.preview-subject', data.subject);
      })
      .catch(function () {});
  }

  panel.addEventListener('click', function (event) {
    var button = event.target.closest('[data-nav]');
    if (!button) {
      return;
    }
    var count = recipients().length;
    index = { first: 0, prev: index - 1, next: index + 1, last: count - 1 }[button.dataset.nav];
    show();
  });
  form.addEventListener('change', function (event) {
    if (event.target.matches('select[name="template_id"], select[name="mode"], input[name="members[]"]')) {
      show();
    }
  });
  document.addEventListener('live:updated', show);
  show();
});

// Fields marked data-select-on-focus select their contents when clicked into, so a suggested
// value (e.g. the next membership number) can simply be typed over.
document.addEventListener('focusin', function (event) {
  if (event.target.matches('input[data-select-on-focus]')) {
    var input = event.target;
    setTimeout(function () { input.select(); }, 0);   // after the click places the cursor
  }
});

// Nominators: picking an active member's name fills in their card number, and typing a
// card number fills in the name. Only exact matches fill; anything else is left as typed.
document.addEventListener('DOMContentLoaded', function () {
  var names = document.getElementById('nominator-names');
  if (!names) {
    return;
  }
  var byName = new Map();
  var byNumber = new Map();
  names.querySelectorAll('option').forEach(function (option) {
    var key = option.value.trim().toLowerCase();
    var number = option.getAttribute('data-number');
    byName.set(key, byName.has(key) ? null : number);   // null: two members share the name
    byNumber.set(number, option.value);
  });

  document.addEventListener('input', function (event) {
    var input = event.target;
    if (!input.matches('input[data-nominator]')) {
      return;
    }
    var other = document.querySelector('input[data-nominator="' + input.dataset.nominator + '"][data-part="'
      + (input.dataset.part === 'name' ? 'number' : 'name') + '"]');
    var match = input.dataset.part === 'name'
      ? byName.get(input.value.trim().toLowerCase())
      : byNumber.get(input.value.trim());
    if (match) {
      other.value = match;
    }
  });
});

// Membership number fields (member form, New member dialog): say straight away whether the
// number is free or already taken. data-number-check is the member's own id (0 for a new one).
// Saving checks again, so this is just early warning.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('input[data-number-check]').forEach(watchNumber);
});

function watchNumber(input) {
  var status = input.parentElement.querySelector('.number-check');
  var original = input.value.trim();   // an existing member's own number needs no message
  var timer = null;
  var pending = null;

  function show(text, state, link) {
    status.textContent = text;
    status.className = 'number-check' + (state ? ' ' + state : '');
    if (link) {
      var a = document.createElement('a');
      a.href = link;
      a.target = '_blank';
      a.rel = 'noopener';
      a.textContent = 'view';
      status.append(' (', a, ')');
    }
  }

  function check() {
    var number = input.value.trim();
    if (pending) {
      pending.abort();
      pending = null;
    }
    if (number === '' || (number === original && input.dataset.numberCheck !== '0')) {
      show('', '');
      return;
    }
    if (!/^\d{1,9}$/.test(number)) {
      show('✗ Numbers only', 'taken');
      return;
    }
    pending = new AbortController();
    var query = new URLSearchParams({ number: number, except: input.dataset.numberCheck }).toString();
    fetch('member-number-check.php?' + query, { credentials: 'same-origin', signal: pending.signal })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.status === 'taken') {
          show('✗ Already belongs to ' + data.name, 'taken', 'member-edit.php?id=' + data.id);
        } else if (data.status === 'available') {
          show('✓ Available', 'ok');
        } else {
          show('✗ Numbers only', 'taken');
        }
      })
      .catch(function () {});
  }

  input.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(check, 300);   // wait for a pause in typing
  });
  check();   // a new member's suggested number gets a tick straight away
}

// Subscription form: "+ Add junior" adds another row (copied from the junior-row template).
var nextJuniorRow = 1000;   // row numbers above any the page starts with
document.addEventListener('click', function (event) {
  if (!event.target.closest('[data-add-junior]')) {
    return;
  }
  var template = document.getElementById('junior-row');
  var tbody = template.closest('fieldset').querySelector('table.people tbody');
  tbody.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__i__/g, nextJuniorRow++));
  tbody.lastElementChild.querySelector('select').focus();
});

// Subscription form: "+ New member…" in a member dropdown opens a dialog to add them,
// then puts the new member in that row (and in every other dropdown's list).
document.addEventListener('DOMContentLoaded', function () {
  var dialog = document.getElementById('new-member-dialog');
  if (!dialog) {
    return;
  }
  var form = dialog.querySelector('form');
  var errors = dialog.querySelector('.error');
  var number = form.querySelector('input[name="membership_number"]');
  var target = null;

  // Remember each dropdown's choice so picking "+ New member…" can put it back.
  document.addEventListener('focusin', function (event) {
    if (event.target.matches('select[data-member-select]')) {
      event.target.dataset.previous = event.target.value;
    }
  });

  document.addEventListener('change', function (event) {
    var select = event.target;
    if (!select.matches('select[data-member-select]') || select.value !== 'new') {
      return;
    }
    select.value = select.dataset.previous || '';
    target = select;
    form.reset();
    errors.hidden = true;
    errors.textContent = '';
    dialog.showModal();
    fetch('member-quick-add.php', { credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        number.value = data.next;
        number.dispatchEvent(new Event('input', { bubbles: true }));
      })
      .catch(function () {});
  });

  dialog.addEventListener('click', function (event) {
    if (event.target.closest('[data-close-dialog]')) {
      dialog.close();
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data.errors) {
          errors.textContent = '';
          data.errors.forEach(function (message) {
            var li = document.createElement('li');
            li.textContent = message;
            errors.append(li);
          });
          errors.hidden = false;
          return;
        }
        addOption(document, data);
        addOption(document.getElementById('junior-row').content, data);
        if (target) {
          target.value = String(data.id);
        }
        dialog.close();
      })
      .catch(function () {
        errors.textContent = "Couldn't add the member. Check you're still logged in and try again.";
        errors.hidden = false;
      });
  });

  // Adds the new member to every member dropdown, in surname order.
  function addOption(root, member) {
    root.querySelectorAll('select[data-member-select]').forEach(function (select) {
      var option = new Option(member.label, member.id);
      var before = Array.from(select.options).slice(2).find(function (o) {
        return o.text.trim().localeCompare(member.label, undefined, { sensitivity: 'base' }) > 0;
      });
      select.add(option, before || null);
    });
  }
});

// Issue an item: only show the fields that apply to the chosen item (code for cards and keys,
// colour and count for wristbands). Fields stay visible if scripts don't run.
document.addEventListener('DOMContentLoaded', function () {
  var type = document.querySelector('select[name="item_type"]');
  if (!type) {
    return;
  }
  var form = type.closest('form');
  function update() {
    form.querySelectorAll('[data-show-for]').forEach(function (field) {
      field.hidden = field.getAttribute('data-show-for').split(' ').indexOf(type.value) === -1;
    });
  }
  type.addEventListener('change', update);
  update();
});
