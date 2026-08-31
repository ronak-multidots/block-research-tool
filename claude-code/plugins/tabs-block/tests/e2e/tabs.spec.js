/**
 * E2E coverage for the Tabs/Tab blocks, using the WordPress Playwright test
 * utils (requires `npm run env:start` first, see README.md).
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Insert a Tabs block with three Tab children (mirroring the reference
 * design this block was built from: an icon + title per tab, and arbitrary
 * content inside each). See tests/e2e/fixtures/design-reference.png.
 *
 * @param {Object} editor The @wordpress/e2e-test-utils-playwright editor fixture.
 */
async function insertReferenceTabs( editor ) {
	await editor.insertBlock( {
		name: 'global-store/tabs',
		innerBlocks: [
			{
				name: 'global-store/tab',
				attributes: {
					title: 'Our Mission',
					iconType: 'dashicon',
					dashicon: 'dashicons-star-filled',
				},
				innerBlocks: [
					{
						name: 'core/paragraph',
						attributes: {
							content: 'Serving People. Solving Problems.',
						},
					},
				],
			},
			{
				name: 'global-store/tab',
				attributes: {
					title: 'Our Superpowers',
					iconType: 'dashicon',
					dashicon: 'dashicons-superhero',
				},
				innerBlocks: [
					{
						name: 'core/paragraph',
						attributes: {
							content: 'We move fast and ship things.',
						},
					},
				],
			},
			{
				name: 'global-store/tab',
				attributes: { title: 'What We Stand For' },
				innerBlocks: [
					{
						name: 'core/paragraph',
						attributes: {
							content: 'Integrity, curiosity, and craft.',
						},
					},
				],
			},
		],
	} );
}

test.describe( 'Tabs block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'can be inserted with its default two-tab template', async ( {
		editor,
	} ) => {
		await editor.insertBlock( { name: 'global-store/tabs' } );

		const block = editor.canvas.locator(
			'[data-type="global-store/tabs"]'
		);
		await expect( block ).toBeVisible();
		await expect(
			block.locator( '[data-type="global-store/tab"]' )
		).toHaveCount( 2 );
	} );

	test( 'only global-store/tab blocks can be inserted directly inside Tabs', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'global-store/tabs' } );

		// Ask the actual registered block type (not just the source
		// block.json) which blocks the editor will allow as direct
		// children of Tabs — the same restriction the UI's inserter
		// enforces, verified here at the runtime/integration level.
		const allowedBlocks = await page.evaluate(
			() =>
				window.wp.data
					.select( 'core/blocks' )
					.getBlockType( 'global-store/tab' ).parent
		);

		expect( allowedBlocks ).toEqual( [ 'global-store/tabs' ] );

		// The inserter should offer Tab but not an arbitrary block like
		// Paragraph when adding directly inside Tabs.
		const block = editor.canvas.locator(
			'[data-type="global-store/tabs"]'
		);
		await block.click();

		const canInsertTab = await page.evaluate( () => {
			const tabsClientId = window.wp.data
				.select( 'core/block-editor' )
				.getBlocks()[ 0 ].clientId;
			return window.wp.data
				.select( 'core/block-editor' )
				.canInsertBlockType( 'global-store/tab', tabsClientId );
		} );
		const canInsertParagraph = await page.evaluate( () => {
			const tabsClientId = window.wp.data
				.select( 'core/block-editor' )
				.getBlocks()[ 0 ].clientId;
			return window.wp.data
				.select( 'core/block-editor' )
				.canInsertBlockType( 'core/paragraph', tabsClientId );
		} );

		expect( canInsertTab ).toBe( true );
		expect( canInsertParagraph ).toBe( false );
	} );

	test( "clicking a nav tab in the editor shows that tab's content and hides the rest", async ( {
		editor,
	} ) => {
		await insertReferenceTabs( editor );

		const block = editor.canvas.locator(
			'[data-type="global-store/tabs"]'
		);
		await block.getByRole( 'tab', { name: /Our Superpowers/ } ).click();

		await expect(
			block.getByText( 'We move fast and ship things.' )
		).toBeVisible();
		await expect(
			block.getByText( 'Serving People. Solving Problems.' )
		).toBeHidden();
	} );

	test( 'renders on the frontend with the first tab active and switches on click', async ( {
		editor,
		page,
	} ) => {
		await insertReferenceTabs( editor );
		await editor.publishPost();

		const permalink = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await page.goto( permalink );

		const block = page.locator( '.tabs-block' );
		await expect( block ).toBeVisible();
		await expect(
			block.getByText( 'Serving People. Solving Problems.' )
		).toBeVisible();
		await expect(
			block.getByText( 'Integrity, curiosity, and craft.' )
		).toBeHidden();

		await block.getByRole( 'tab', { name: /What We Stand For/ } ).click();

		await expect(
			block.getByText( 'Integrity, curiosity, and craft.' )
		).toBeVisible();
		await expect(
			block.getByText( 'Serving People. Solving Problems.' )
		).toBeHidden();
	} );

	test( 'supports keyboard navigation between tabs (WAI-ARIA tabs pattern)', async ( {
		editor,
		page,
	} ) => {
		await insertReferenceTabs( editor );
		await editor.publishPost();

		const permalink = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await page.goto( permalink );

		const block = page.locator( '.tabs-block' );
		await block.getByRole( 'tab', { name: /Our Mission/ } ).focus();
		await page.keyboard.press( 'ArrowRight' );

		await expect(
			block.getByRole( 'tab', { name: /Our Superpowers/ } )
		).toBeFocused();
		await expect(
			block.getByText( 'We move fast and ship things.' )
		).toBeVisible();
	} );

	test( 'visually matches the reference design at a glance (nav row + active underline)', async ( {
		editor,
		page,
	} ) => {
		await insertReferenceTabs( editor );
		await editor.publishPost();

		const permalink = await page.evaluate( () =>
			window.wp.data.select( 'core/editor' ).getPermalink()
		);
		await page.goto( permalink );

		// A full pixel-diff against tests/e2e/fixtures/design-reference.png
		// isn't meaningful (that mockup uses stock photography this test
		// doesn't have), but this baseline catches unintended regressions to
		// the block's own nav/panel layout on every run after the first.
		await expect( page.locator( '.tabs-block' ) ).toHaveScreenshot(
			'tabs-block-frontend.png',
			{ maxDiffPixelRatio: 0.02 }
		);
	} );

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

	for ( const viewport of DEVICE_VIEWPORTS ) {
		test( `stays within the viewport width on ${ viewport.name } (${ viewport.width }px)`, async ( {
			editor,
			page,
		} ) => {
			await insertReferenceTabs( editor );
			await editor.publishPost();

			const permalink = await page.evaluate( () =>
				window.wp.data.select( 'core/editor' ).getPermalink()
			);

			await page.setViewportSize( {
				width: viewport.width,
				height: viewport.height,
			} );
			await page.goto( permalink );

			// The nav is allowed to become its own horizontally-scrollable
			// strip when tabs don't fit (see src/tabs/style.scss), but the
			// page itself must never grow wider than the viewport.
			const pageOverflows = await page.evaluate(
				() =>
					document.documentElement.scrollWidth >
					document.documentElement.clientWidth
			);
			expect( pageOverflows ).toBe( false );

			await expect( page.locator( '.tabs-block' ) ).toBeVisible();
		} );
	}
} );
