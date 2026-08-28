/**
 * E2E coverage for the Flash Sale Header block, using the WordPress
 * Playwright test utils (requires `npm run env:start` first, see README.md).
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Flash Sale Header block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'can be inserted and defaults to the wide layout', async ( {
		editor,
	} ) => {
		await editor.insertBlock( { name: 'global-store/flash-sale-header' } );

		const block = editor.canvas.locator(
			'[data-type="global-store/flash-sale-header"]'
		);

		await expect( block ).toBeVisible();
		await expect( block ).toHaveClass( /is-size-wide/ );
	} );

	test( 'can switch between the three layout sizes from the sidebar', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'global-store/flash-sale-header' } );
		await editor.openDocumentSettingsSidebar();

		const block = editor.canvas.locator(
			'[data-type="global-store/flash-sale-header"]'
		);

		const sizeControl = page.getByLabel( 'Size' );

		await sizeControl.selectOption( 'medium' );
		await expect( block ).toHaveClass( /is-size-medium/ );

		await sizeControl.selectOption( 'tall' );
		await expect( block ).toHaveClass( /is-size-tall/ );

		await sizeControl.selectOption( 'wide' );
		await expect( block ).toHaveClass( /is-size-wide/ );
	} );

	test( 'entering an expiry date renders a live countdown on the frontend', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'global-store/flash-sale-header',
			attributes: {
				title: 'E2E Flash Sale',
				expiryDateTime: '2099-12-31T23:59:00',
			},
		} );

		await editor.publishPost();

		const permalink = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await page.goto( permalink );

		await expect( page.locator( '.flash-sale-header' ) ).toBeVisible();
		await expect(
			page.locator( '[data-flash-sale-countdown]' )
		).toBeVisible();
		await expect(
			page.locator(
				'[data-unit="days"] .flash-sale-header__countdown-value'
			)
		).not.toHaveText( '00', { timeout: 5000 } );
	} );
} );
