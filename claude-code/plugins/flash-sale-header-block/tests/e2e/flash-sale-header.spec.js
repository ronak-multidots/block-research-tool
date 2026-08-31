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

	// A pixel-diff against tests/e2e/fixtures/design-reference.png isn't
	// meaningful (that mockup uses stock photography this test doesn't
	// have), but this baseline catches unintended regressions to the
	// block's own layout/typography on every run after the first.
	for ( const size of [ 'wide', 'medium', 'tall' ] ) {
		test( `visually matches its own baseline in the ${ size } layout`, async ( {
			editor,
			page,
		} ) => {
			await editor.insertBlock( {
				name: 'global-store/flash-sale-header',
				attributes: { size, expiryDateTime: '2099-12-31T23:59:00' },
			} );
			await editor.publishPost();

			const permalink = await page.evaluate( () =>
				window.wp.data.select( 'core/editor' ).getPermalink()
			);
			await page.goto( permalink );

			await expect(
				page.locator( '.flash-sale-header' )
			).toHaveScreenshot( `flash-sale-header-${ size }.png`, {
				maxDiffPixelRatio: 0.02,
				// The live countdown's digits change every second.
				mask: [ page.locator( '.flash-sale-header__countdown' ) ],
			} );
		} );
	}

	// Major device breakpoints: small phone, large phone, tablet portrait,
	// tablet landscape/small laptop, and desktop.
	const DEVICE_VIEWPORTS = [
		{ name: 'iPhone SE', width: 375, height: 667 },
		{ name: 'iPhone 14', width: 390, height: 844 },
		{ name: 'iPad Mini portrait', width: 768, height: 1024 },
		{ name: 'iPad landscape', width: 1024, height: 768 },
		{ name: 'Laptop', width: 1366, height: 800 },
		{ name: 'Desktop', width: 1920, height: 1080 },
	];

	for ( const size of [ 'wide', 'medium', 'tall' ] ) {
		for ( const viewport of DEVICE_VIEWPORTS ) {
			test( `${ size } layout stays within the viewport width on ${ viewport.name } (${ viewport.width }px)`, async ( {
				editor,
				page,
			} ) => {
				await editor.insertBlock( {
					name: 'global-store/flash-sale-header',
					attributes: {
						size,
						expiryDateTime: '2099-12-31T23:59:00',
					},
				} );
				await editor.publishPost();

				const permalink = await page.evaluate( () =>
					window.wp.data.select( 'core/editor' ).getPermalink()
				);

				await page.setViewportSize( {
					width: viewport.width,
					height: viewport.height,
				} );
				await page.goto( permalink );

				const pageOverflows = await page.evaluate(
					() =>
						document.documentElement.scrollWidth >
						document.documentElement.clientWidth
				);
				expect( pageOverflows ).toBe( false );

				await expect(
					page.locator( '.flash-sale-header' )
				).toBeVisible();
			} );
		}
	}
} );
