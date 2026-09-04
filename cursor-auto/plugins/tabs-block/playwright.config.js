/**
 * Playwright configuration for the end-to-end suite.
 *
 * The WordPress preset points the tests at the `wp-env` instance started by
 * `npm run env:start`.
 */

const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = {
	...baseConfig,
	testDir: './tests/e2e',
};
