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
  const cartLink = document.querySelector('.nav-links a[href="cart.php"]');
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
      const phone = (formData.get('phone') || '').trim();
      const qty = Number(formData.get('qty') || 0);
      const eventId = formData.get('event') || '';

      const errors = [];
      if (name.length < 2) errors.push({ field: 'name', message: 'Enter your full name (2+ characters).' });
      if (!/^\S+@\S+\.\S+$/.test(email)) errors.push({ field: 'email', message: 'Enter a valid email address.' });
      if (!/^\+?\d{9,15}$/.test(phone)) errors.push({ field: 'phone', message: 'Enter a valid phone number (digits only, include country code).' });
      if (!Number.isInteger(qty) || qty < 1) errors.push({ field: 'qty', message: 'Quantity must be 1 or more.' });
      if (!eventId) errors.push({ field: 'event', message: 'Please select an event.' });

      if (errors.length) {
        // display errors
        errors.forEach(err => showFieldError(bookingForm, err.field, err.message));
        flashMessage('Please fix errors and try again.', 'info');
        return;
      }

      // Check available spots before confirming
      const spotsEl = document.querySelector(`.event-card[data-id="${eventId}"] .spots`);
      let available = Infinity;
      if (spotsEl) available = Number(spotsEl.dataset.spots || spotsEl.textContent.replace(/\D/g, '') || 0);
      if (qty > available) {
        showFieldError(bookingForm, 'qty', `Only ${available} spot(s) available for the selected event.`);
        flashMessage('Not enough spots available.', 'info');
        return;
      }

      // Reduce spots and update UI
      if (spotsEl) {
        let spots = available - qty;
        spotsEl.dataset.spots = spots;
        spotsEl.textContent = `${spots} left`;
        spotsEl.classList.add('pulse');
        setTimeout(() => spotsEl.classList.remove('pulse'), 900);
      }

      flashMessage('Booking successful — confirmation sent to your email.', 'success');
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

    // Populate event select when events are loaded (or later) — keep a reference
    const eventSelect = bookingForm.querySelector('#booking-event-select');
    if (eventSelect) {
      // Wait for events to be loaded and then populate (a helper below sets window.__loadedEvents)
      if (window.__loadedEvents) populateEventSelect(window.__loadedEvents, eventSelect);
      else window.__populateSelect = (evs) => populateEventSelect(evs, eventSelect);
    }

    // When user clicks a book button on a card, preselect that event in the form
    const eventsContainer = document.querySelector('#events-list');
    if (eventsContainer) {
      eventsContainer.addEventListener('click', (ev) => {
        const bookBtn = ev.target.closest('.button');
        if (!bookBtn) return;
        const card = ev.target.closest('.event-card');
        if (!card) return;
        const id = card.dataset.id;
        const sel = bookingForm.querySelector('[name="event"]');
        if (sel) {
          sel.value = id;
          sel.dispatchEvent(new Event('change'));
        }
        // scroll to form for mobile/desktop
        bookingForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        // Build HTML for all events using map(), include new fields and badges
        const cardsHtml = events.map(ev => {
          const tagsHtml = (ev.tags || []).map(t => `<span class="badge tag">${escapeHtml(t)}</span>`).join(' ');
          const priceHtml = ev.price.toLowerCase() === 'free' ? `<span class="price">${escapeHtml(ev.price)} <span class="badge free">FREE</span></span>` : `<span class="price">${escapeHtml(ev.price)}</span>`;
          return `\n            <article class="card event-card fade-in" data-host="${escapeHtml(ev.host)}" data-id="${ev.id}">\n              <div class="card-image">${ev.icon || '📅'}</div>\n              <p class="event-date">${escapeHtml(ev.date)}</p>\n              <h3>${escapeHtml(ev.title)} ${tagsHtml}</h3>\n              <p>${escapeHtml(ev.location)} · Hosted by ${escapeHtml(ev.host)}</p>\n              <p class="spots" data-spots="${Number(ev.spots_left || 0)}">${Number(ev.spots_left || 0)} left</p>\n              ${priceHtml}\n              <p style="margin-top:10px;"><a class="button" href="#">Book Ticket</a></p>\n            </article>\n          `;
        }).join('');

        eventsContainer.innerHTML = cardsHtml;

        // Demonstrate forEach() - style badges and small UI touches
        eventsContainer.querySelectorAll('.event-card').forEach(card => {
          const priceEl = card.querySelector('.price');
          if (priceEl && priceEl.textContent.trim().toLowerCase().includes('free')) {
            // already has FREE badge in priceHtml; add pulse to draw attention
            const freeBadge = priceEl.querySelector('.badge.free');
            if (freeBadge) freeBadge.classList.add('pulse');
            setTimeout(() => freeBadge && freeBadge.classList.remove('pulse'), 900);
          }

          // Add host badge after title
          const host = card.dataset.host;
          if (host) {
            const h = card.querySelector('h3');
            const hostBadge = document.createElement('span');
            hostBadge.className = 'badge';
            hostBadge.textContent = host;
            h.appendChild(hostBadge);
          }
        });

        // Make loaded events available to other handlers (e.g., populate booking select)
        window.__loadedEvents = events;
        if (typeof window.__populateSelect === 'function') window.__populateSelect(events);

        // Example usage of the freeEvents array (logged)
        console.info('Free events:', freeEvents.map(e => e.title));

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

  // Toggle between sign in and sign up forms
  const signUpButton = document.getElementById('signUpButton');
  const signInButton = document.getElementById('signInButton');
  const signInForm = document.getElementById('signIn');
  const signUpForm = document.getElementById('signup');

  if (signUpButton && signInButton && signInForm && signUpForm) {
    signUpButton.addEventListener('click', function() {
      signInForm.style.display = 'none';
      signUpForm.style.display = 'block';
    });
    signInButton.addEventListener('click', function() {
      signInForm.style.display = 'block';
      signUpForm.style.display = 'none';
    });
  }

});
