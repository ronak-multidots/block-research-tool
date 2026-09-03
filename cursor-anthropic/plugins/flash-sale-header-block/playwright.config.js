/**
 * Playwright configuration for the end-to-end suite.
 *
 * Builds on the configuration shipped with @wordpress/scripts, which wires up the
 * admin storage state and the wp-env web server. Run the suite with:
 *
 *   npm run build
 *   npm run env:start
 *   npm run test:e2e
 */

const { defineConfig } = require( '@playwright/test' );
const baseConfig = require( '@wordpress/scripts/config/playwright.config' );

module.exports = defineConfig( {
	...baseConfig,
	testDir: './tests/e2e',
	webServer: {
		...baseConfig.webServer,
		command: 'npm run env:start',
	},
} );
