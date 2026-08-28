const { execSync } = require( 'child_process' );
const baseGlobalSetup = require( '@wordpress/scripts/config/playwright/global-setup.js' );

/**
 * The `tests-wordpress` wp-env service (port 8889) shares its database with
 * the PHPUnit `tests-cli` bootstrap (see tests/php/bootstrap.php). Every
 * PHPUnit run reinstalls that database from scratch, which deactivates this
 * plugin and resets the active theme to a placeholder "default" theme that
 * doesn't exist on disk (breaking asset URL generation). Re-assert both
 * before every e2e run so `npm run test:e2e` doesn't depend on run order
 * relative to `npm run test:php` — on top of the base @wordpress/scripts
 * Playwright global setup, which logs in and saves the admin storage state
 * that the e2e-test-utils-playwright fixtures expect.
 *
 * @param {import('@playwright/test').FullConfig} config Playwright's resolved config.
 */
module.exports = async function globalSetup( config ) {
	execSync(
		'npx wp-env run tests-cli wp plugin activate flash-sale-header-block',
		{ stdio: 'inherit' }
	);
	execSync( 'npx wp-env run tests-cli wp theme activate twentytwentyfive', {
		stdio: 'inherit',
	} );

	await baseGlobalSetup( config );
};
