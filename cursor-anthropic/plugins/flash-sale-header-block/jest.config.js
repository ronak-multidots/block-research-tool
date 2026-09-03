/**
 * Jest configuration for the block's JavaScript unit tests.
 *
 * Builds on the WordPress preset so `@wordpress/*` packages, Babel and the jsdom
 * environment are already wired up.
 */

const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	rootDir: __dirname,
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	setupFilesAfterEnv: [
		...( defaultConfig.setupFilesAfterEnv || [] ),
		'<rootDir>/tests/js/setup.js',
	],
	moduleNameMapper: {
		...( defaultConfig.moduleNameMapper || {} ),
		'\\.(scss|css)$': '<rootDir>/tests/js/style-mock.js',
	},
	/*
	 * @wordpress/components depends on an ESM-only build of `uuid`, which Jest cannot
	 * load untransformed. Everything else in node_modules is still skipped.
	 */
	transformIgnorePatterns: [ 'node_modules/(?!(?:.*node_modules/)?uuid/)' ],
	collectCoverageFrom: [
		'src/**/*.js',
		'!src/**/index.js',
		'!src/**/icon.js',
	],
};
