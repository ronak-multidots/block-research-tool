/**
 * End-to-end coverage for the Global Store Flash Sale Header block.
 *
 * Requires a running wp-env instance and compiled assets:
 *
 *   npm run build && npm run env:start && npm run test:e2e
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

const BLOCK_NAME = 'global-store/flash-sale-header';
const BLOCK_SELECTOR = '.wp-block-global-store-flash-sale-header';

/**
 * A fixed expiry far enough in the future that the countdown never reaches zero.
 *
 * @return {string} Datetime in the `Y-m-d\TH:i:s` format the block stores.
 */
function futureExpiry() {
	const date = new Date( Date.now() + 3 * 24 * 60 * 60 * 1000 );

	return date.toISOString().slice( 0, 19 );
}

test.describe( 'Flash Sale Header block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllPosts();
	} );

	test( 'can be inserted from the block inserter', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: BLOCK_NAME } );

		const block = editor.canvas.locator( BLOCK_SELECTOR );

		await expect( block ).toBeVisible();
		await expect( block ).toContainText( 'The Flash Sale' );
		await expect( block ).toHaveClass( /is-size-auto/ );

		// The block is dynamic, so the post content holds attributes only.
		const content = await editor.getEditedPostContent();
		expect( content ).toContain( `<!-- wp:${ BLOCK_NAME }` );
		expect( content ).not.toContain( '</div>' );

		await expect( page.locator( 'body' ) ).toBeVisible();
	} );

	test( 'switches between the three layout sizes', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.openDocumentSettingsSidebar();

		const sizeSelect = page
			.getByRole( 'region', { name: 'Editor settings' } )
			.getByLabel( 'Size' );

		for ( const size of [ 'wide', 'medium', 'tall', 'auto' ] ) {
			await sizeSelect.selectOption( size );

			await expect( editor.canvas.locator( BLOCK_SELECTOR ) ).toHaveClass(
				new RegExp( `is-size-${ size }` )
			);
		}

		const [ block ] = await editor.getBlocks();
		expect( block.attributes.size ).toBe( 'auto' );
	} );

	test( 'accepts an expiry date and previews the countdown', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: BLOCK_NAME } );
		await editor.openDocumentSettingsSidebar();

		const settings = page.getByRole( 'region', {
			name: 'Editor settings',
		} );

		// The DateTimePicker splits the value across a set of number inputs.
		const year = new Date().getFullYear() + 1;
		await settings.getByLabel( 'Year' ).fill( String( year ) );
		await settings.getByLabel( 'Month' ).selectOption( '12' );
		await settings.getByLabel( 'Day' ).fill( '24' );
		await settings.getByLabel( 'Day' ).blur();

		const countdown = editor.canvas.locator(
			`${ BLOCK_SELECTOR } [data-gsfsh-unit="days"]`
		);

		await expect( countdown ).toBeVisible();
		await expect( countdown ).not.toHaveText( '00' );

		const [ block ] = await editor.getBlocks();
		expect( block.attributes.expiryDate ).toContain( String( year ) );
	} );

	test( 'renders the configured content on the front end', async ( {
		editor,
		page,
	} ) => {
		const expiry = futureExpiry();

		await editor.insertBlock( {
			name: BLOCK_NAME,
			attributes: {
				size: 'wide',
				title: 'The Flash Sale',
				subtitle: '£1 a month for 12 months',
				countdownLabel: 'Offer ends in',
				expiryDate: expiry,
				ctaText: 'Subscribe now',
				ctaUrl: 'https://example.com/subscribe',
				finePrint: 'New subscribers only.',
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const block = page.locator( BLOCK_SELECTOR );

		await expect( block ).toHaveClass( /is-size-wide/ );
		await expect(
			block.locator( '.wp-block-global-store-flash-sale-header__title' )
		).toHaveText( 'The Flash Sale' );
		await expect(
			block.getByRole( 'link', { name: 'Subscribe now' } )
		).toHaveAttribute( 'href', 'https://example.com/subscribe' );
		await expect( block ).toContainText( 'New subscribers only.' );

		// The countdown is server rendered, so it is populated before hydration.
		const days = block.locator( '[data-gsfsh-unit="days"]' );
		await expect( days ).toHaveText( /^0[23]$/ );

		const seconds = block.locator( '[data-gsfsh-unit="seconds"]' );
		const initialSeconds = await seconds.textContent();

		await expect
			.poll( async () => seconds.textContent(), { timeout: 5000 } )
			.not.toBe( initialSeconds );
	} );

	test( 'shows the expired state once the offer ends', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: BLOCK_NAME,
			attributes: {
				title: 'The Flash Sale',
				// Two seconds in the future: the front end has to flip live.
				expiryDate: new Date( Date.now() + 2000 )
					.toISOString()
					.slice( 0, 19 ),
				expiredMessage: 'This offer has ended.',
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		const block = page.locator( BLOCK_SELECTOR );

		await expect( block.locator( '[data-gsfsh-expired]' ) ).toBeVisible( {
			timeout: 10_000,
		} );
		await expect( block.locator( '[data-gsfsh-units]' ) ).toBeHidden();
	} );

	test( 'does not emit unescaped markup from hostile attributes', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: BLOCK_NAME,
			attributes: {
				title: '<img src=x onerror="window.__xss=true">Flash Sale',
				ctaText: 'Subscribe now',
				ctaUrl: 'javascript:window.__xss=true',
			},
		} );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		await expect( page.locator( BLOCK_SELECTOR ) ).toBeVisible();
		expect( await page.evaluate( () => window.__xss ) ).toBeUndefined();
		expect(
			await page.locator( BLOCK_SELECTOR ).innerHTML()
		).not.toContain( 'javascript:' );
	} );
} );
