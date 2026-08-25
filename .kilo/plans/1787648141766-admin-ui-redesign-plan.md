# Admin UI Redesign Plan: Visual Appeal & Form UX Enhancement

## Goal

Redesign the admin pages of the AIU Club Store to be visually appealing and modern, focusing on form input styling, form submission UX, font choices, and overall visual hierarchy — using only CSS, JavaScript, HTML, and PHP enhancements.

## Scope

**In scope:**
- `style.css` — design token overhaul, form/card/table styling, admin-specific styles
- `main.js` — form submission enhancements, validation, toast notifications, button loading states
- All admin PHP pages (`admin.php`, `admin_events.php`, `admin_events_edit.php`, `admin_products.php`, `admin_sizes.php`, `admin_product_image.php`, `admin_users.php`, `admin_clubs.php`, `admin_club_allocations.php`, `admin_orders.php`, `admin_reports.php`, `ticket_checkin.php`) — HTML structure updates for new CSS classes
- `login.php` — form styling consistency (uses `.auth-container`)

**Out of scope:**
- Database schema changes
- New PHP backend logic (validation, queries)
- Non-admin pages (shop.php, cart.php, checkout.php, events.php, etc.) — unless trivially affected by shared CSS changes

## Current State Analysis

### Color Tokens (style.css:4-10)
| Variable | Current Value | Issue |
|---|---|---|
| `--navy` | `#000000` | Actually black, not navy |
| `--blue` | `#e3111f` | Actually AIU red, mislabeled |
| `--gold` | `#ffffff` | Actually white, mislabeled |
| `--ink` | `#000000` | Same as `--navy` |
| `--paper` | `#ffffff` | Same as `--gold` |

### `.form-card` — Critical Gap
Used by 8 admin pages but has **no CSS rules whatsoever**. Forms render as bare HTML with `<label>Label <input></label>` — labels and inputs are inline-stacked inside `<label>` elements with no visual structure.

### Fonts
Only `Arial, sans-serif` (style.css:21). No Google Fonts, no font variable system, no visual hierarchy.

### Form Inputs (style.css:421-425)
Minimal styling: 8px padding, 6px border-radius, 1px border. No focus ring, no transition, no error state.

### Form Submission UX
- `admin_users.php:118-127` and `admin_orders.php:280-288` use `alert()` for success confirmation
- No button loading states on any form
- No client-side validation feedback on admin forms

### Tables (style.css:288-299)
Basic styling only. No row striping, no hover on data rows, no header stickiness.

---

## Design Decisions

### 1. Color Palette Redesign
Keep AIU brand red (#e3111f) as primary accent. Fix naming and add supporting colors.

| Variable | New Value | Usage |
|---|---|---|
| `--red` | `#e3111f` | AIU brand primary color (buttons, accents) |
| `--red-hover` | `#b91c1c` | Button hover state |
| `--navy` | `#0a1128` | Navbar/footer background, dark headings |
| `--gold` | `#c9a227` | Secondary accent (allocation buttons) |
| `--gold-hover` | `#a3851e` | Secondary button hover |
| `--paper` | `#f8f9fa` | Page background |
| `--ink` | `#1a1a2e` | Primary text |
| `--ink-light` | `#6b7280` | Secondary/muted text |
| `--border` | `#e5e8ef` | Form/table borders |
| `--border-focus` | `#3182ce` | Input focus ring |
| `--success` | `#2f855a` | Success states |
| `--danger` | `#c53030` | Error/danger states |
| `--warning` | `#dd6b20` | Warning states |
| `--card-bg` | `#ffffff` | Card backgrounds |

**Rationale:** Rename `--blue` → `--red` for clarity. Repurpose `--gold` to its current actual usage color (`#c9a227`, already used in `.button.allocations-btn`). Add complementary colors for status badges and form states.

### 2. Font System
Add Google Fonts: **Inter** (primary) with system-font fallbacks.

- **Body font:** `Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`
- **Heading font:** Same (Inter handles weights well)
- Font sizes: Use `clamp()` for responsive scaling where appropriate
- Update `header.php` to include the Google Fonts `<link>` tag

### 3. Form Styling Approach
Use a **floating label** pattern for text inputs (modern, clean, saves vertical space):

```html
<div class="field">
  <input type="text" name="title" id="title" required>
  <label for="title">Event title</label>
</div>
```

**Rationale:** Floating labels provide a polished, modern look and are widely used in contemporary admin UIs. The existing login page already uses a label-floating pattern (though with a different technique), so this creates consistency. However, for `select` and `textarea`, use stacked labels (floating labels on selects are problematic).

### 4. Toast Notifications
Replace `alert()` calls with CSS/JavaScript toast notifications that auto-dismiss after 3 seconds. Keep the same PHP flash message system (`pull_message()`) — just display it as a toast instead of a plain `.flash` paragraph.

---

## Implementation Tasks

### Phase 1: CSS Overhaul (style.css)

#### Task 1.1: Update Design Tokens
- Rename `--blue` → `--red`, update value to `#e3111f`
- Rename `--gold` to `#c9a227` (was `#ffffff`)
- Add `--red-hover`, `--gold-hover`, `--ink-light`, `--border`, `--border-focus`, `--success`, `--danger`, `--warning`, `--card-bg`
- Set `--paper` to `#f8f9fa`, `--ink` to `#1a1a2e`, `--navy` to `#0a1128`

#### Task 1.2: Update Global Styles
- Add Google Fonts Inter import at top of style.css (or in header.php — see Task 4)
- Update `body` font-family to use Inter stack
- Update `body` background to `var(--paper)`
- Add smooth scroll behavior

#### Task 1.3: Create `.form-card` Styles (Critical Missing Piece)
Add comprehensive styling for the `.form-card` class:
```css
.form-card {
  background: var(--card-bg);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  padding: 28px;
  margin-bottom: 28px;
}
```
Add responsive: padding `20px` on mobile

#### Task 1.4: Create `.field` / Floating Label System
- Add `.field` wrapper class with relative positioning
- Style `input`, `textarea`, `select` with consistent dimensions:
  - Height: 48px for inputs, 120px min for textarea
  - Padding: 14px 16px
  - Border: 1px solid `var(--border)`
  - Border-radius: 8px
  - Transition: border-color 200ms, box-shadow 200ms
  - Focus: outline none, border-color `var(--border-focus)`, box-shadow `0 0 0 3px rgba(49,130,206,0.15)`
- Floating label: position absolute, transitions for top/opacity/color on focus/filled state
- Error state: `.field-error` border color `var(--danger)`, label color `var(--danger)`

#### Task 1.5: Form Layout Structure
- `.form-card` fields: `display: flex; flex-direction: column; gap: 20px`
- Full-width submit buttons within forms
- Button: consistent `var(--red)` background, white text, hover transition

#### Task 1.6: Table Enhancement
- Add row striping: `tbody tr:nth-child(even) { background: var(--paper); }`
- Hover on data rows: `tbody tr:hover { background: #f0f7ff; }`
- Header: darker background, bolder text
- Select elements in tables: styled like form selects (compact height ~36px)

#### Task 1.7: Card & Dashboard Enhancement
- Refine `.card` with new palette
- Summary cards: gradient background or colored border accent
- Add `.card-header` pattern if needed for section grouping

#### Task 1.8: Flash/Badge Refinement
- Update `.flash` to use new colors with proper success/error variants
- Add `.flash.error` variant (red bg) vs default (blue bg)
- Update `.badge` variants to use new semantic colors

### Phase 2: JavaScript Enhancement (main.js)

#### Task 2.1: Toast Notification System
Replace `alert()` with a `showToast()` function:
```javascript
function showToast(message, type = 'info') {
  // Creates a fixed-position toast at top-right
  // Auto-dismisses after 3 seconds
  // Types: success, error, info — each with distinct background color
}
```

#### Task 2.2: Admin Page Toast Integration
- In `admin_users.php` inline script: replace `alert('Changes saved successfully!')` with `showToast('Changes saved successfully!', 'success')`
- In `admin_orders.php` inline script: same replacement

#### Task 2.3: Form Submit Button Loading States
Add JavaScript that targets all admin `.form-card` forms:
- On submit: set button to "Saving..." with spinner icon, disable button, show loading class
- Works generically via event delegation

#### Task 2.4: Client-Side Form Validation (Admin Forms)
Add lightweight validation for key admin forms:
- `admin_events.php` / `admin_events_edit.php`: validate title, venue, capacity (>=1), date, price (>=0)
- `admin_products.php`: validate name, price (>=0), stock (>=0), category, club
- `admin_clubs.php`: validate name
- `admin_club_allocations.php`: validate both selects have non-empty values
- `ticket_checkin.php`: validate ticket_code is non-empty and uppercase
- Show inline error messages below fields using existing `.field-error` class
- Error fields get `var(--danger)` border

#### Task 2.5: Input Enhancement
- Auto-uppercase ticket code input on `ticket_checkin.php`
- Add character counter for description textareas (optional)
- Focus management: auto-focus first input on admin form pages

### Phase 3: PHP/HTML Updates (Admin Pages)

#### Task 3.1: Wrap Form Fields in `.field` Divs
Update each admin page that outputs a `<form class="form-card">`:

**admin_events.php** (form fields lines 147-187):
- Wrap each `<label>` in a `<div class="field">`
- Change `<label>Club <select>...</select></label>` to `<div class="field"><select id="club_id" name="..."><label for="club_id">Club</label></div>`
- Apply same pattern for all fields: title, description, venue, date, capacity, ticket_price, poster

**admin_events_edit.php** (form fields lines 145-194): Apply same `.field` wrapper pattern

**admin_products.php** (form fields lines 119-153): Apply same pattern

**admin_clubs.php** (form fields lines 111-122): Apply same pattern

**admin_sizes.php** (form fields lines 93-102): Apply same pattern

**admin_club_allocations.php** (form fields lines 153-177): Apply same pattern

**admin_product_image.php** (form fields lines 101-117): Apply same pattern with `.field` wrappers

**ticket_checkin.php** (line 40): Apply `.field` wrapper to the ticket_code input

#### Task 3.2: Add `id` Attributes to Form Inputs
Each input/select/textarea that has an associated label needs an `id` for the `for` attribute to work with floating labels. Add unique ids:
- `club_id`, `title`, `description`, `venue`, `date`, `capacity`, `ticket_price`, `poster`
- `name`, `image`
- `size`, `stock`
- `user_id` (allocations), `club_id` (allocations)
- `product_id` (image upload)

#### Task 3.3: Add Validation Attributes
Where not already present, add `min`, `max`, `step`, `pattern` as appropriate:
- Price inputs: `step="0.01" min="0"`
- Capacity: `min="1"`
- Stock: `min="0"`
- All required fields: `required`

#### Task 3.4: Add Loading State Attributes to Submit Buttons
Add a `data-loading` attribute to submit buttons for JS to use:
```html
<button class="button" data-loading="Saving...">Save Club</button>
```

#### Task 3.5: Update PHP Flash Message Display
Modify `includes/header.php` line 33 to render flash messages as toasts instead of plain paragraphs, or add toast container. Alternatively, keep the `.flash` element but add JS to auto-dismiss it.

### Phase 4: header.php & Shared Updates

#### Task 4.1: Add Google Fonts to header.php
Add the Inter font import:
```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```

#### Task 4.2: Toast Container
Add a `<div id="toast-container">` to the footer or header for JavaScript to populate:
```html
<div id="toast-container" class="toast-container"></div>
```

---

## File-by-File Change Summary

| File | Changes |
|---|---|
| `style.css` | New token palette, `.form-card`, `.field` floating labels, enhanced tables, badges, flash, cards |
| `main.js` | Toast system, button loading states, form validation, input enhancement |
| `includes/header.php` | Google Fonts link, toast container |
| `admin_events.php` | `.field` wrappers, input ids, loading attrs |
| `admin_events_edit.php` | `.field` wrappers, input ids, loading attrs |
| `admin_products.php` | `.field` wrappers, input ids, loading attrs |
| `admin_clubs.php` | `.field` wrappers, input ids, loading attrs |
| `admin_sizes.php` | `.field` wrappers, input ids, loading attrs |
| `admin_product_image.php` | `.field` wrappers, input ids |
| `admin_club_allocations.php` | `.field` wrappers, input ids, loading attrs |
| `ticket_checkin.php` | `.field` wrappers, input id, auto-uppercase JS |
| `admin_users.php` | Replace `alert()` with `showToast()`, styled selects in tables |
| `admin_orders.php` | Replace `alert()` with `showToast()`, styled selects in tables |
| `admin.php` | Dashboard card styling updates (summary-grid colored accents) |
| `admin_reports.php` | Card/table styling updates from shared CSS |

---

## Rollout & Migration Strategy

1. **CSS-first approach:** Update `style.css` design tokens and form styles first. Verify no visual regressions on non-admin pages (login.php, shop, events, etc.) — all use the same CSS file.
2. **HTML structure updates:** Add `.field` wrappers and input `id` attributes to each admin PHP page. Floating labels work via CSS `:focus` and `:placeholder-shown` — no JS required for basic functionality.
3. **JavaScript enhancements:** Add toast/ validation JS to `main.js`. Inline scripts in `admin_users.php` and `admin_orders.php` that call `alert()` are updated to call `showToast()`.
4. **Testing checkpoint:** After each admin page update, load the page and verify the form renders correctly with floating labels, validation works, and submissions still function.

**Risk:** Since `style.css` is shared across all pages, changes to global tokens (`--blue` → `--red`) may affect non-admin pages. Mitigate by replacing all `var(--blue)` references in CSS with `var(--red)` and updating the PHP/HTML where `--blue` is referenced in inline styles. Search for `var(--blue)` and `--blue` usage across all files.

---

## Validation Plan

1. **Visual verification:** Load each admin page in a browser:
   - `admin.php` — dashboard cards and management links render with new palette
   - `admin_events.php`, `admin_events_edit.php` — event forms show floating labels, styled selects, file input
   - `admin_products.php` — product forms with floating labels
   - `admin_clubs.php` — club forms with floating labels
   - `admin_sizes.php` — size forms with floating labels
   - `admin_product_image.php` — upload form with preview
   - `admin_club_allocations.php` — allocation form with floating-label selects
   - `ticket_checkin.php` — check-in form with auto-uppercase
   - `admin_users.php`, `admin_orders.php` — tables with styled dropdowns, no alert() popups after save

2. **Functional verification:**
   - All form submissions still work (create/edit/delete operations)
   - Client-side validation prevents submission with empty/invalid fields
   - Loading states appear on button click during form submission
   - Toast notifications replace alert() dialogs and auto-dismiss
   - Flash messages from PHP server-side validation display correctly

3. **Cross-role verification:**
   - Test as `super_admin` — all management links visible
   - Test as `club_admin` — restricted club selection, disabled dropdowns
   - Test as `student` — admin pages should 403

4. **Responsive verification:**
   - Forms stack vertically on mobile (they already do via flex-column)
   - Tables scroll horizontally if needed on small screens
   - Font sizes scale appropriately

5. **Shared CSS regression check:**
   - `login.php` — auth forms still render correctly with new tokens
   - `shop.php`, `events.php`, `cart.php` — cards, buttons, tables unchanged visually (same CSS classes)
   - `index.php` — hero section, featured clubs cards look consistent
