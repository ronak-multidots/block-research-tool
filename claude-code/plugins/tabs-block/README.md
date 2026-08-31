# Global Store Tabs

A dynamic, accessible Tabs block: `global-store/tabs` (the container) plus
`global-store/tab` (one per tab, restricted to only appear inside Tabs). Each
tab's nav title supports an icon (Dashicon or a custom uploaded image) plus
text, and any block can be nested inside a tab's content via InnerBlocks.

## How it's wired together

- **`global-store/tabs`** is the container. Its `render.php` builds the nav
  row by reading each child Tab block's *saved attributes* directly from the
  parsed block tree (`$block->parsed_block['innerBlocks']`) — that data
  (title/icon) lives on the child blocks, not the parent. The tab panels
  themselves are simply `$content`, i.e. whatever the child Tab blocks
  already rendered.
- **`global-store/tab`** is the child. Its `render.php` only renders the
  panel wrapper (`role="tabpanel"`) around its own arbitrary InnerBlocks
  content; it does not render its own title/icon (that's the parent's job).
- **Editor UX**: the Tabs block's `edit.js` synthesizes a live nav bar from
  its sibling Tab blocks' attributes (via `getBlocks()`), and tracks which
  tab is "open" for editing through the standard Block Context API
  (`providesContext`/`usesContext` on `activeTabId`) — not local component
  state — so each Tab's own `edit.js` can independently know whether to show
  or hide its InnerBlocks content.
- **Frontend interactivity**: `src/tabs/view.js` is a small vanilla-JS
  viewScript implementing the WAI-ARIA "Tabs (Automatic Activation)"
  pattern — click or arrow-key through tabs, `hidden`/`aria-selected` toggle
  accordingly. Before it hydrates, CSS alone (`:first-of-type`) shows the
  first tab so there's no flash of every tab's content stacked together.

Both blocks are dynamic (`render.php`, not static `save()` output) — see the
in-chat discussion in this project's history for why that matters here:
`save()` alone can't build a nav from sibling blocks' data, and dynamic
rendering means fixes apply to every already-published post automatically.

## Structure

```
tabs-block.php               Plugin bootstrap, registers both blocks from build/
src/
  tabs/                      The parent Tabs block
    block.json               providesContext, align/anchor/color/spacing supports
    index.js / edit.js / save.js / render.php
    view.js                  Frontend tab-switching (viewScript)
    style.scss / editor.scss
  tab/                       The child Tab block
    block.json               parent: ["global-store/tabs"], usesContext
    index.js / edit.js / save.js / render.php
    style.scss / editor.scss
tests/
  js/                        Jest + React Testing Library
  php/                       PHPUnit
  e2e/                       Playwright (@wordpress/e2e-test-utils-playwright)
    fixtures/design-reference.png   Original design brief, kept for manual comparison
```

## Setup

```bash
npm install
composer install
```

## Build

```bash
npm run build      # production build into build/tabs/ and build/tab/
npm start          # watch mode for development
```

`wp-scripts` auto-discovers both `src/tabs/block.json` and
`src/tab/block.json` and builds each as its own entry point; the plugin
bootstrap registers both from `build/`.

## Linting

```bash
npm run lint:js
npm run lint:css
composer run lint          # PHPCS (WordPress-Extra ruleset)
composer run lint:fix       # PHPCBF autofix
```

## Tests

### JavaScript (Jest / React Testing Library)

```bash
npm run test:unit
```

Covers both blocks' `edit.js`: icon type controls and tabId generation for
Tab, and the synthesized nav / active-tab switching / title editing for
Tabs.

### PHP (PHPUnit)

Requires a WordPress test install. Either:

```bash
npm run env:start      # starts a wp-env environment with this plugin mounted
npm run test:php
```

or, without wp-env:

```bash
bin/install-wp-tests.sh <db-name> <db-user> <db-pass> localhost latest
composer install
composer run test
```

Covers block registration (parent/child + context relationship) and
`render.php` output for both blocks — nav assembly from child attributes,
ARIA linking, sanitization/escaping, and Dashicons enqueue.

### End-to-end (Playwright)

```bash
npm run env:start
npm run test:e2e
```

Covers inserting Tabs (with its default template), the child-block
restriction (only Tab can be inserted directly inside Tabs), switching tabs
in the editor and on the published frontend, keyboard navigation, and a
`toHaveScreenshot()` visual-regression baseline (see
`tests/e2e/fixtures/design-reference.png` for the original design brief this
block was built from — kept for manual comparison, since it uses stock
photography this test suite doesn't have).
