<?php
/**
 * Plugin Name: Blog Post Remover
 * Plugin URI:  https://example.com
 * Description: Securely removes only WordPress blog posts (post_type=post) in safe batches.
 * Version:     1.0.0
 * Author:      Blog Post Remover
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: blog-post-remover
 *
 * @package Blog_Post_Remover
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin_file = __DIR__ . '/blog-post-remover/blog-post-remover.php';

if ( file_exists( $plugin_file ) ) {
	require_once $plugin_file;
}
