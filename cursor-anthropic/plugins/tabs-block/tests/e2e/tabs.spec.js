/**
 * End-to-end coverage for authoring and viewing a tabs block.
 */

const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

test.describe( 'Global Store Tabs', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost();
	} );

	test( 'ships three tabs and lets the author add another', async ( { editor, page } ) => {
		await editor.insertBlock( { name: 'global-store/tabs' } );

		const tabList = page.locator( '.wp-block-global-store-tabs__list' );

		await expect( tabList.locator( '.wp-block-global-store-tabs__tab' ) ).toHaveCount( 3 );

		await tabList.getByRole( 'button', { name: '+ Add tab' } ).click();

		await expect( tabList.locator( '.wp-block-global-store-tabs__tab' ) ).toHaveCount( 4 );
	} );

	test( 'shows the panel belonging to the selected tab', async ( { editor, page } ) => {
		await editor.insertBlock( { name: 'global-store/tabs' } );

		const panels = page.locator( '.wp-block-global-store-tabs__panel' );

		await expect( panels.nth( 0 ) ).toBeVisible();
		await expect( panels.nth( 1 ) ).toBeHidden();

		await page
			.locator( '.wp-block-global-store-tabs__tab-switch' )
			.first()
			.click();

		await expect( panels.nth( 0 ) ).toBeHidden();
		await expect( panels.nth( 1 ) ).toBeVisible();
	} );

	test( 'switches tabs on the front end with a pointer and a keyboard', async ( {
		admin,
		editor,
		page,
	} ) => {
		await editor.insertBlock( {
			name: 'global-store/tabs',
			innerBlocks: [
				{
					name: 'global-store/tab',
					attributes: { label: 'First', slug: 'first' },
					innerBlocks: [
						{ name: 'core/paragraph', attributes: { content: 'Panel one' } },
					],
				},
				{
					name: 'global-store/tab',
					attributes: { label: 'Second', slug: 'second' },
					innerBlocks: [
						{ name: 'core/paragraph', attributes: { content: 'Panel two' } },
					],
				},
			],
		} );

		const url = await editor.publishPost();

		await page.goto( url );

		await expect( page.getByText( 'Panel one' ) ).toBeVisible();
		await expect( page.getByText( 'Panel two' ) ).toBeHidden();

		await page.getByRole( 'tab', { name: 'Second' } ).click();

		await expect( page.getByText( 'Panel two' ) ).toBeVisible();
		await expect( page ).toHaveURL( /#second$/ );

		await page.keyboard.press( 'ArrowLeft' );

		await expect( page.getByText( 'Panel one' ) ).toBeVisible();

		// Guard against a regression that leaves the admin bar covering the block.
		await admin.visitAdminPage( 'index.php' );
	} );

	test( 'opens the tab a deep link points at', async ( { editor, page } ) => {
		await editor.insertBlock( {
			name: 'global-store/tabs',
			innerBlocks: [
				{
					name: 'global-store/tab',
					attributes: { label: 'First', slug: 'first' },
					innerBlocks: [
						{ name: 'core/paragraph', attributes: { content: 'Panel one' } },
					],
				},
				{
					name: 'global-store/tab',
					attributes: { label: 'Second', slug: 'second' },
					innerBlocks: [
						{ name: 'core/paragraph', attributes: { content: 'Panel two' } },
					],
				},
			],
		} );

		const url = await editor.publishPost();

		await page.goto( `${ url }#second` );

		await expect( page.getByText( 'Panel two' ) ).toBeVisible();
		await expect( page.getByText( 'Panel one' ) ).toBeHidden();
	} );
} );
