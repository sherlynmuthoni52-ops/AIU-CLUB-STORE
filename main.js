// main.js
// External JS for DOM manipulation, events, form validation, fetch, and array methods

/* ==========================================================================
   Toast Notification System
   ========================================================================== */

const ToastSystem = {
  container: null,

  init() {
    this.container = document.createElement('div');
    this.container.id = 'toast-container';
    this.container.className = 'toast-container';
    document.body.appendChild(this.container);
  },

  show(message, type = 'info', duration = 3000) {
    if (!this.container) this.init();

    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<i class="fas fa-' + this._iconFor(type) + '"></i><span>' + message + '</span>';

    toast.addEventListener('click', () => this._dismiss(toast));

    this.container.appendChild(toast);

    setTimeout(() => this._dismiss(toast), duration);
  },

  _iconFor(type) {
    switch (type) {
      case 'success': return 'check-circle';
      case 'error':   return 'exclamation-circle';
      case 'warning': return 'exclamation-triangle';
      default:        return 'info-circle';
    }
  },

  _dismiss(toast) {
    toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 300);
  }
};

// Legacy alias for existing code that calls flashMessage()
function flashMessage(msg, type = 'info') {
  ToastSystem.show(msg, type);
}

function showFieldError(form, fieldName, message) {
  const field = form.querySelector('[name="' + fieldName + '"]');
  if (!field) return;
  const wrap = document.createElement('div');
  wrap.className = 'field-error';
  wrap.textContent = message;
  field.insertAdjacentElement('afterend', wrap);
  field.classList.add('error');
  // Add 'error' state to parent .field for styling
  const fieldWrap = field.closest('.field');
  if (fieldWrap) fieldWrap.classList.add('error');
  // Scroll to error on mobile
  if (fieldWrap && window.innerWidth < 700) {
    fieldWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function clearFormErrors(form) {
  form.querySelectorAll('.field-error').forEach(n => n.remove());
  form.querySelectorAll('input, select, textarea').forEach(i => {
    i.style.borderColor = '';
    i.classList.remove('error');
  });
  form.querySelectorAll('.field').forEach(f => f.classList.remove('error'));
}

/* ==========================================================================
   Floating Label Enhancement (for selects and pre-filled inputs)
   ========================================================================== */

function initFloatingLabels() {
  // For selects (and any field), toggle 'filled' class on the .field wrapper
  document.querySelectorAll('.field select, .field input, .field textarea').forEach(fieldEl => {
    const wrapper = fieldEl.closest('.field');
    if (!wrapper) return;

    const toggleFilled = () => {
      const hasValue = fieldEl.value && fieldEl.value !== '';
      wrapper.classList.toggle('filled', hasValue);
    };

    fieldEl.addEventListener('focus', toggleFilled);
    fieldEl.addEventListener('blur', toggleFilled);
    toggleFilled();
  });
}

/* ==========================================================================
   Form Submission — Button Loading States
   ========================================================================== */

function initFormLoadingStates() {
  document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.tagName !== 'FORM') return;

    // Skip loading state for forms that should remain client-side only
    // (e.g., booking form handled by its own validation logic)
    if (form.id === 'booking-form') return;

    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submitBtn) return;

    const originalText = submitBtn.innerHTML;
    const loadingText = submitBtn.getAttribute('data-loading') || 'Saving...';
    const spinner = '<span class="spinner"></span> ';

    // If the form's own validation handler prevented default, skip loading state
    if (e.defaultPrevented) return;

    submitBtn.disabled = true;
    submitBtn.classList.add('loading');
    submitBtn.innerHTML = spinner + loadingText;

    // Restore on page unload as a safety net
    window.addEventListener('beforeunload', () => {
      submitBtn.disabled = false;
      submitBtn.classList.remove('loading');
      submitBtn.innerHTML = originalText;
    }, { once: true });
  });
}

/* ==========================================================================
   Admin Form Client-Side Validation
   ========================================================================== */

function initAdminFormValidation() {
  // Generic validator for admin .form-card forms
  function validateAdminForm(form, rules) {
    if (!form) return;

    form.addEventListener('submit', function(e) {
      clearFormErrors(form);
      const formData = new FormData(form);
      const errors = [];

      rules.forEach(rule => {
        const value = (formData.get(rule.field) || '').trim();
        if (rule.required && !value) {
          errors.push({ field: rule.field, message: rule.message || 'This field is required.' });
        } else if (value && rule.pattern && !new RegExp(rule.pattern).test(value)) {
          errors.push({ field: rule.field, message: rule.message });
        } else if (value && rule.min !== undefined && Number(value) < rule.min) {
          errors.push({ field: rule.field, message: rule.message });
        }
      });

      if (errors.length) {
        e.preventDefault();
        errors.forEach(err => showFieldError(form, err.field, err.message));
        ToastSystem.show('Please fix the errors below.', 'error');
        return false;
      }
      return true;
    });
  }

  // Detect form types by their fields (more robust than action-based selectors)
  const eventForms = document.querySelectorAll('.form-card');

  eventForms.forEach(form => {
    const hasTitle = form.querySelector('[name="title"]') !== null;
    const hasVenue = form.querySelector('[name="venue"]') !== null;
    const hasPrice = form.querySelector('[name="price"]') !== null;
    const hasName = form.querySelector('[name="name"]') !== null;
    const hasStock = form.querySelector('[name="stock"]') !== null;
    const hasSize = form.querySelector('[name="size"]') !== null;
    const hasUserId = form.querySelector('[name="user_id"]') !== null;
    const hasClubId = form.querySelector('[name="club_id"]') !== null;
    const hasTicketCode = form.querySelector('[name="ticket_code"]') !== null;

    // Event forms (title, venue, date, capacity, price)
    if (hasTitle && hasVenue) {
      validateAdminForm(form, [
        { field: 'title', required: true, message: 'Event title is required.' },
        { field: 'venue', required: true, message: 'Venue is required.' },
        { field: 'date', required: true, message: 'Please select a date and time.' },
        { field: 'capacity', required: true, min: 1, message: 'Capacity must be at least 1.' },
        { field: 'ticket_price', required: true, min: 0, message: 'Ticket price must be 0 or more.' }
      ]);
    }

    // Product forms (name, price, stock, category, club_id)
    if (hasPrice && hasName && hasStock && hasClubId) {
      validateAdminForm(form, [
        { field: 'name', required: true, message: 'Product name is required.' },
        { field: 'price', required: true, min: 0, message: 'Price must be 0 or higher.' },
        { field: 'stock', required: true, min: 0, message: 'Stock must be 0 or higher.' },
        { field: 'category', required: true, message: 'Category is required.' }
      ]);
    }

    // Club forms (name only, no price/stock)
    if (hasName && !hasPrice && !hasStock && !hasTitle) {
      validateAdminForm(form, [
        { field: 'name', required: true, message: 'Club name is required.' }
      ]);
    }

    // Size forms (size, stock) — no title, no price, no name
    if (hasSize && !hasTitle && !hasPrice) {
      validateAdminForm(form, [
        { field: 'size', required: true, message: 'Size is required (e.g. M, L).' },
        { field: 'stock', required: true, min: 0, message: 'Stock must be 0 or higher.' }
      ]);
    }

    // Allocation form (user_id, club_id)
    if (hasUserId && hasClubId) {
      validateAdminForm(form, [
        { field: 'user_id', required: true, message: 'Please select a club admin.' },
        { field: 'club_id', required: true, message: 'Please select a club.' }
      ]);
    }

    // Product image form (product_id + file input)
    if (form.querySelector('[name="product_id"]') && form.querySelector('[name="image"]')) {
      validateAdminForm(form, [
        { field: 'product_id', required: true, message: 'Please select a product.' }
      ]);
    }

    // Ticket check-in form (ticket_code only)
    if (hasTicketCode) {
      validateAdminForm(form, [
        { field: 'ticket_code', required: true, message: 'Please enter a ticket code.' }
      ]);
    }
  });
}

/* ==========================================================================
   Input Enhancements
   ========================================================================== */

// Auto-uppercase ticket code input
function initTicketCodeUppercase() {
  const codeInput = document.querySelector('input[name="ticket_code"]');
  if (codeInput) {
    codeInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.toUpperCase();
    });
  }
}

/* ==========================================================================
   Delete Confirmation
   ========================================================================== */

function initDeleteConfirmations() {
  document.addEventListener('submit', function(e) {
    const form = e.target.closest('form');
    if (!form) return;

    // Check for inline onsubmit confirm handlers on the form itself
    const onsubmit = form.getAttribute('onsubmit');
    if (onsubmit && onsubmit.includes('confirm')) {
      const match = onsubmit.match(/confirm\(['"](.*)['"]\)/);
      if (match && !confirm(match[1])) {
        e.preventDefault();
        return false;
      }
    }
  });
}

/* ==========================================================================
   Main DOM Ready Handler
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  // 1) Select DOM elements and change their content or styles
  const brand = document.querySelector('.brand');
  if (brand) {
    brand.textContent = 'AIU CLUB STORE';
    brand.style.color = '#2b6cb0';
  }

  // Update cart count if present
  const cartLink = document.querySelector('.nav-links a[href="cart.php"]');
  if (cartLink) {
    const match = cartLink.textContent.match(/Cart\s*\((\d+)\)/);
    if (match) {
      let count = parseInt(match[1], 10);
      cartLink.dataset.count = count;
    }
  }

  // 2) Global click handler for .button elements
  document.body.addEventListener('click', (e) => {
    const btn = e.target.closest('.button');
    if (!btn) return;

    const isFormButton = btn.tagName === 'BUTTON' && btn.closest('form') !== null;
    const isRealLink = btn.tagName === 'A' && btn.getAttribute('href') && btn.getAttribute('href') !== '#';
    if (isFormButton || isRealLink) return;

    e.preventDefault();

    const text = btn.textContent.trim().toLowerCase();
    if (text.includes('add to cart') || text.includes('book') || text.includes('reserve')) {
      if (cartLink) {
        cartLink.dataset.count = Number(cartLink.dataset.count || 0) + 1;
        cartLink.textContent = 'Cart (' + cartLink.dataset.count + ')';
        ToastSystem.show('Added to cart', 'success');
      } else {
        ToastSystem.show('Action received', 'info');
      }
    }
  });

  // 3) Toast replacement for alert()-based success messages (admin_users.php, admin_orders.php)
  // These pages redirect with ?saved=1 after a successful save
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('saved') || urlParams.has('updated') || urlParams.has('deleted')) {
    const savedType = urlParams.get('saved') || urlParams.get('updated') || urlParams.get('deleted');
    if (savedType !== '0' && savedType !== null) {
      ToastSystem.show('Changes saved successfully!', 'success');
    }
    // Clean up the URL parameter without reloading
    const url = new URL(window.location);
    url.searchParams.delete('saved');
    url.searchParams.delete('updated');
    url.searchParams.delete('deleted');
    window.history.replaceState({}, '', url);
  }

  // 4) Booking form validation (preserved from original)
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
        errors.forEach(err => showFieldError(bookingForm, err.field, err.message));
        ToastSystem.show('Please fix errors and try again.', 'info');
        return;
      }

      const spotsEl = document.querySelector('.event-card[data-id="' + eventId + '"] .spots');
      let available = Infinity;
      if (spotsEl) available = Number(spotsEl.dataset.spots || spotsEl.textContent.replace(/\D/g, '') || 0);
      if (qty > available) {
        showFieldError(bookingForm, 'qty', 'Only ' + available + ' spot(s) available for the selected event.');
        ToastSystem.show('Not enough spots available.', 'info');
        return;
      }

      if (spotsEl) {
        let spots = available - qty;
        spotsEl.dataset.spots = spots;
        spotsEl.textContent = spots + ' left';
        spotsEl.classList.add('pulse');
        setTimeout(() => spotsEl.classList.remove('pulse'), 900);
      }

      ToastSystem.show('Booking successful — confirmation sent to your email.', 'success');
      bookingForm.reset();
    });

    const nameInput = bookingForm.querySelector('[name="name"]');
    if (nameInput) {
      nameInput.addEventListener('keyup', (e) => {
        const len = e.target.value.length;
        const hint = bookingForm.querySelector('.name-hint');
        if (hint) hint.textContent = len + ' characters';
      });
    }

    const eventSelect = bookingForm.querySelector('#booking-event-select');
    if (eventSelect) {
      if (window.__loadedEvents) populateEventSelect(window.__loadedEvents, eventSelect);
      else window.__populateSelect = (evs) => populateEventSelect(evs, eventSelect);
    }
  }

  // 5) Toggle between sign in and sign up forms
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

  // --- Initialize all enhancement modules ---
  initFloatingLabels();
  initFormLoadingStates();
  initAdminFormValidation();
  initTicketCodeUppercase();
  initDeleteConfirmations();
});

// Utility: simple HTML escape
function escapeHtml(str) {
  return String(str || '').replace(/[&<>"']/g, (s) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s]);
}
