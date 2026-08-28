const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	setupFiles: [
		...( defaultConfig.setupFiles || [] ),
		'<rootDir>/tests/js/jest.setup.js',
	],
};
