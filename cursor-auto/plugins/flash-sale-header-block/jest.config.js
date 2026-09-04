/**
 * Jest configuration for the block's JavaScript unit tests.
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
	transformIgnorePatterns: [ 'node_modules/(?!(?:.*node_modules/)?uuid/)' ],
	collectCoverageFrom: [
		'src/**/*.js',
		'!src/**/index.js',
		'!src/**/icon.js',
	],
};
