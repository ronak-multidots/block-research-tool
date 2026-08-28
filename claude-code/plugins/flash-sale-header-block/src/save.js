/**
 * This is a dynamic block — all markup is produced server-side by
 * `render.php` so the countdown expiry can be validated and escaped in PHP.
 * Saving `null` tells Gutenberg not to persist any markup for the block
 * itself; only the JSON attributes are stored in the block comment.
 */
export default function save() {
	return null;
}
