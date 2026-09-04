/**
 * Capture CTO demo screenshots for every Flash Sale and Tabs implementation.
 *
 * Writes desktop / tablet / mobile shots of the responsive page plus a
 * layouts composite into each plugin's docs/ folder.
 */

import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';
import { chromium } from '../cursor-auto/plugins/flash-sale-header-block/node_modules/playwright/index.mjs';

const root = dirname( dirname( fileURLToPath( import.meta.url ) ) );

const plugins = [
	{ dir: 'claude-code/plugins/flash-sale-header-block', kind: 'flash-sale', label: 'Claude Code' },
	{ dir: 'claude-code/plugins/tabs-block', kind: 'tabs', label: 'Claude Code' },
	{ dir: 'cursor-anthropic/plugins/flash-sale-header-block', kind: 'flash-sale', label: 'Cursor + Anthropic' },
	{ dir: 'cursor-anthropic/plugins/tabs-block', kind: 'tabs', label: 'Cursor + Anthropic' },
	{ dir: 'cursor-auto/plugins/flash-sale-header-block', kind: 'flash-sale', label: 'Cursor Auto' },
	{ dir: 'cursor-auto/plugins/tabs-block', kind: 'tabs', label: 'Cursor Auto' },
];

const viewports = {
	desktop: { width: 1440, height: 900 },
	tablet: { width: 768, height: 1024 },
	mobile: { width: 390, height: 844 },
};

const mime = {
	'.html': 'text/html; charset=utf-8',
	'.css': 'text/css; charset=utf-8',
	'.svg': 'image/svg+xml',
	'.js': 'text/javascript; charset=utf-8',
	'.png': 'image/png',
};

function startServer( baseDir ) {
	return new Promise( ( resolve ) => {
		const server = createServer( async ( req, res ) => {
			const url = new URL( req.url, 'http://127.0.0.1' );
			const file = join( baseDir, decodeURIComponent( url.pathname ) );
			try {
				const data = await readFile( file );
				res.writeHead( 200, { 'Content-Type': mime[ extname( file ) ] || 'application/octet-stream' } );
				res.end( data );
			} catch {
				res.writeHead( 404 );
				res.end( 'not found' );
			}
		} );
		server.listen( 0, '127.0.0.1', () => {
			resolve( { server, port: server.address().port } );
		} );
	} );
}

async function main() {
	for ( const plugin of plugins ) {
		execFileSync(
			'php',
			[ join( root, 'tools/generate-plugin-demo.php' ), join( root, plugin.dir ), plugin.kind, plugin.label ],
			{ stdio: 'inherit' }
		);
	}

	const browser = await chromium.launch( {
		channel: 'chrome',
	} ).catch( () => chromium.launch() );
	const context = await browser.newContext( { deviceScaleFactor: 2 } );
	const page = await context.newPage();

	for ( const plugin of plugins ) {
		const docs = join( root, plugin.dir, 'docs' );
		const { server, port } = await startServer( docs );
		const origin = `http://127.0.0.1:${ port }`;

		await page.setViewportSize( { width: plugin.kind === 'flash-sale' ? 1600 : 1200, height: 1100 } );
		await page.goto( `${ origin }/layouts.html`, { waitUntil: 'networkidle' } );
		await page.screenshot( { path: join( docs, 'layouts.png' ), fullPage: true } );

		for ( const [ name, size ] of Object.entries( viewports ) ) {
			await page.setViewportSize( size );
			await page.goto( `${ origin }/responsive.html`, { waitUntil: 'networkidle' } );
			await page.screenshot( { path: join( docs, `${ name }.png` ), fullPage: true } );
		}

		server.close();
		console.log( `Captured ${ plugin.label } ${ plugin.kind }` );
	}

	await browser.close();
}

main().catch( ( error ) => {
	console.error( error );
	process.exit( 1 );
} );
