/**
 * jsdom doesn't implement ResizeObserver, which real `@wordpress/components`
 * rely on internally. Stub it so mounting the real component tree in
 * edit.test.js doesn't throw.
 */
global.ResizeObserver =
	global.ResizeObserver ||
	class ResizeObserver {
		observe() {}
		unobserve() {}
		disconnect() {}
	};
