// main.js
// External JS for DOM manipulation, events, form validation, fetch, and array methods

document.addEventListener('DOMContentLoaded', () => {
  // 1) Select DOM elements and change their content or styles
  const brand = document.querySelector('.brand');
  if (brand) {
    brand.textContent = 'AIU CLUB STORE';
    brand.style.color = '#2b6cb0'; // change color to show style change
  }

  // Update cart count if present
  const cartLink = document.querySelector('.nav-links a[href="cart.html"]');
  if (cartLink) {
    // extract number from text like "Cart (0)"
    const match = cartLink.textContent.match(/Cart\s*\((\d+)\)/);
    if (match) {
      let count = parseInt(match[1], 10);
      // store in dataset for later updates
      cartLink.dataset.count = count;
    }
  }

  // 2) Event listeners for clicks, form submits, and key presses
  // Global click handler for buttons with class .button
  document.body.addEventListener('click', (e) => {
    const btn = e.target.closest('.button');
    if (!btn) return;

    e.preventDefault(); // buttons in this demo are links — prevent navigation

    // If the button is an add-to-cart / book button, increment cart count
    const text = btn.textContent.trim().toLowerCase();
    if (text.includes('add to cart') || text.includes('book') || text.includes('reserve')) {
      if (cartLink) {
        cartLink.dataset.count = Number(cartLink.dataset.count || 0) + 1;
        cartLink.textContent = `Cart (${cartLink.dataset.count})`;
        flashMessage('Added to cart', 'success');
      } else {
        flashMessage('Action received', 'info');
      }
    }
  });

  // Simple flash message UI
  function flashMessage(msg, type = 'info') {
    const existing = document.querySelector('.flash-message');
    if (existing) existing.remove();
    const div = document.createElement('div');
    div.className = 'flash-message ' + type;
    div.textContent = msg;
    Object.assign(div.style, {
      position: 'fixed',
      right: '20px',
      top: '20px',
      padding: '10px 14px',
      background: type === 'success' ? '#48bb78' : '#3182ce',
      color: '#fff',
      borderRadius: '6px',
      zIndex: 9999,
    });
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 2500);
  }

  // 3) Form validation and submit handling for booking form (if present)
  const bookingForm = document.querySelector('#booking-form');
  if (bookingForm) {
    bookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      clearFormErrors(bookingForm);

      const formData = new FormData(bookingForm);
      const name = (formData.get('name') || '').trim();
      const email = (formData.get('email') || '').trim();
      const qty = Number(formData.get('qty') || 0);

      const errors = [];
      if (name.length < 2) errors.push({ field: 'name', message: 'Enter your full name (2+ characters).' });
      if (!/^\S+@\S+\.\S+$/.test(email)) errors.push({ field: 'email', message: 'Enter a valid email address.' });
      if (!Number.isInteger(qty) || qty < 1) errors.push({ field: 'qty', message: 'Quantity must be 1 or more.' });

      if (errors.length) {
        // display errors
        errors.forEach(err => showFieldError(bookingForm, err.field, err.message));
        flashMessage('Please fix errors and try again.', 'info');
        return;
      }

      // Simulate successful booking
      flashMessage('Booking successful — check your email for confirmation.', 'success');
      bookingForm.reset();
    });

    // Key press listener on the name field to demonstrate key events
    const nameInput = bookingForm.querySelector('[name="name"]');
    if (nameInput) {
      nameInput.addEventListener('keyup', (e) => {
        const len = e.target.value.length;
        const hint = bookingForm.querySelector('.name-hint');
        if (hint) hint.textContent = `${len} characters`;
      });
    }
  }

  function showFieldError(form, fieldName, message) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (!field) return;
    const wrap = document.createElement('div');
    wrap.className = 'field-error';
    wrap.textContent = message;
    wrap.style.color = '#9b2c2c';
    wrap.style.fontSize = '0.9rem';
    field.insertAdjacentElement('afterend', wrap);
    field.style.borderColor = '#f56565';
  }

  function clearFormErrors(form) {
    form.querySelectorAll('.field-error').forEach(n => n.remove());
    form.querySelectorAll('input, select, textarea').forEach(i => i.style.borderColor = '');
  }

  // 4) Use fetch() to load data and render it on the page (events data)
  const eventsContainer = document.querySelector('#events-list');
  const searchInput = document.querySelector('#events-search');
  if (eventsContainer) {
    fetch('events.json')
      .then(res => {
        if (!res.ok) throw new Error('Network response was not ok');
        return res.json();
      })
      .then(data => {
        // data.events expected
        const events = data.events || [];

        // 5) Use map(), filter(), forEach() with arrays
        // Example: create a list of free events using filter()
        const freeEvents = events.filter(ev => ev.price === 'Free');

        // Build HTML for all events using map()
        const cardsHtml = events.map(ev => {
          return `\n            <article class="card event-card" data-host="${escapeHtml(ev.host)}">\n              <div class="card-image">${ev.icon || '📅'}</div>\n              <p class="event-date">${escapeHtml(ev.date)}</p>\n              <h3>${escapeHtml(ev.title)}</h3>\n              <p>${escapeHtml(ev.location)} · Hosted by ${escapeHtml(ev.host)}</p>\n              <p class="price">${escapeHtml(ev.price)}</p>\n              <a class="button" href="#">Book Ticket</a>\n            </article>\n          `;
        }).join('');

        eventsContainer.innerHTML = cardsHtml;

        // Demonstrate forEach() - add a small badge to free events
        eventsContainer.querySelectorAll('.event-card').forEach(card => {
          const priceEl = card.querySelector('.price');
          if (priceEl && priceEl.textContent.trim().toLowerCase().includes('free')) {
            const badge = document.createElement('div');
            badge.textContent = 'FREE';
            badge.style.background = '#2f855a';
            badge.style.color = '#fff';
            badge.style.display = 'inline-block';
            badge.style.padding = '2px 6px';
            badge.style.marginLeft = '8px';
            badge.style.fontSize = '0.8rem';
            badge.style.borderRadius = '4px';
            priceEl.appendChild(badge);
          }
        });

        // Example usage of the freeEvents array (logged)
        console.info('Free events:', freeEvents.map(e => e.title));
      })
      .catch(err => {
        eventsContainer.innerHTML = '<p style="color:#9b2c2c">Could not load events data.</p>';
        console.error(err);
      });
  }

  // Search/filter UI - filter events on key press using array methods
  if (searchInput && eventsContainer) {
    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      const cards = Array.from(eventsContainer.querySelectorAll('.event-card'));
      // Use filter to decide which cards to show
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(q) ? '' : 'none';
      });
    });
  }

  // Utility: simple HTML escape (very small helper)
  function escapeHtml(str) {
    return String(str || '').replace(/[&<>\"']/g, (s) => ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":"&#39;"})[s]);
  }

});
