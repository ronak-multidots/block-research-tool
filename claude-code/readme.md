## Prompt for Claude
Act as a Principal WordPress Engineer. Develop a custom, secure, and fully responsive WordPress Gutenberg Block named "Global Store Flash Sale Header" (`global-store/flash-sale-header`) based on the following specifications:

### 1. Requirements & Core Features
- **3 Size/Layout Options:**
  1. `wide` (Horizontal full-width header: text left, dynamic countdown below text, cutout imagery right).
  2. `medium` (Medium box/card layout: text top-left, countdown below, CTA button, cutout imagery right).
  3. `tall` (Vertical sidebar/mobile layout: imagery top, text & countdown middle, CTA button & legal text bottom).
  - Size selection must be available via an Inspector Control (`SelectControl` or toggle buttons) in the Block Editor sidebar, and also default to automatic CSS Container Queries (`container-type: inline-size`) so it scales seamlessly based on parent container width.
- **Editable Content Fields:**
  - Header Title (e.g., "The Flash Sale") - RichText / TextControl
  - Subtitle / Offer Details (e.g., "£1 a month for 12 months")
  - Expiry Date & Time (using WordPress `@wordpress/components` DateTimePicker for the live countdown timer)
  - CTA Button Text & Target URL
  - Fine Print / Legal Disclaimer Text
  - Cutout Image Upload / Selection using WordPress `@wordpress/block-editor` MediaUpload component.

### 2. Technical Stack & Structure
- Standard `@wordpress/create-block` structure using modern ESNext and `block.json` (Block API v3).
- **Frontend Live Countdown:** Lightweight vanillajs dynamic frontend script (`viewScript` in `block.json`) that hydrates the target timestamp and updates Days, Hours, Mins, Secs continuously.
- **Server-Side vs Client-Side Rendering:** Use dynamic server-side rendering (`render.php`) for strict SSR markup rendering, with JS frontend hydration for the live countdown timer.

### 3. Security & WordPress Coding Standards
- **Sanitization & Escaping:** Ensure all attributes rendered in `render.php` are properly sanitized and escaped using native WP functions (`esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses_post()`).
- **Data Validation:** Define exact attribute types and defaults in `block.json` to prevent invalid data injection.
- **Nonce & REST Safety:** If fetching block data via REST API, validate nonces and verify user capabilities (`current_user_can('edit_posts')`).
- Strictly adhere to WordPress Coding Standards (WPCS for PHP and `@wordpress/eslint-plugin` for JavaScript).

### 4. Testing & Quality Assurance
- **JS Testing (Jest / React Testing Library):** Write test cases verifying that `edit.js` renders controls, updates layout size attributes correctly, and updates block state.
- **PHP Unit Testing (PHPUnit):** Write test cases verifying block registration, proper output sanitization, and server-side rendering behavior in `render.php`.
- **E2E Testing (Playwright):** Provide an end-to-end test verifying block insertion in the Gutenberg editor, switching between the 3 sizes, entering countdown dates, and checking frontend DOM output.

### Steps to Implement:
1. Scaffold plugin file structure in `plugins/flash-sale-header-block/`.
2. Generate `block.json`, `index.js`, `edit.js`, `render.php`, `style.scss`, `editor.scss`, and `view.js`.
3. Build style classes utilizing modern CSS Grid/Flexbox and CSS Container Queries for layout switching (`.is-size-wide`, `.is-size-medium`, `.is-size-tall`).
4. Add unit and integration tests inside `tests/`.