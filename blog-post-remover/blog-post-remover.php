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

/**
 * Main plugin class.
 */
class Blog_Post_Remover {

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 100;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_ajax_bpr_start_deletion', array( $this, 'ajax_start_deletion' ) );
		add_action( 'wp_ajax_bpr_delete_batch', array( $this, 'ajax_delete_batch' ) );
	}

	/**
	 * Registers tools page.
	 *
	 * @return void
	 */
	public function register_admin_page() {
		add_management_page(
			__( 'Blog Post Remover', 'blog-post-remover' ),
			__( 'Blog Post Remover', 'blog-post-remover' ),
			'manage_options',
			'blog-post-remover',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue assets for plugin page.
	 *
	 * @param string $hook_suffix Current admin page suffix.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'tools_page_blog-post-remover' !== $hook_suffix ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'blog-post-remover-admin',
			plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'blog-post-remover-admin',
			plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'blog-post-remover-admin',
			'bprAdmin',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'bpr_nonce' ),
				'confirmationPhrase' => 'blog remove',
				'batchSize'          => self::BATCH_SIZE,
				'i18n'               => array(
					'processing' => __( 'Starting deletion...', 'blog-post-remover' ),
					'complete'   => __( 'All blog posts have been removed successfully.', 'blog-post-remover' ),
					'empty'      => __( 'There are no blog posts to remove.', 'blog-post-remover' ),
					'error'      => __( 'An unexpected error occurred. Please try again.', 'blog-post-remover' ),
				),
			)
		);
	}

	/**
	 * Render plugin admin page.
	 *
	 * @return void
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'blog-post-remover' ) );
		}
		?>
		<div class="wrap bpr-wrap">
			<h1><?php esc_html_e( 'Blog Post Remover', 'blog-post-remover' ); ?></h1>

			<div class="notice notice-error inline bpr-warning">
				<p>
					<strong><?php esc_html_e( 'This action will permanently delete all blog posts from this website. Pages, media, products, users, and settings will not be deleted.', 'blog-post-remover' ); ?></strong>
				</p>
			</div>

			<p>
				<label for="bpr-confirmation-input"><strong><?php esc_html_e( 'Type the exact phrase to confirm:', 'blog-post-remover' ); ?></strong></label>
				<code>blog remove</code>
			</p>

			<input
				type="text"
				id="bpr-confirmation-input"
				class="regular-text"
				autocomplete="off"
				placeholder="blog remove"
			/>

			<p>
				<button type="button" id="bpr-remove-button" class="button button-danger" disabled>
					<?php esc_html_e( 'Remove All Blog Posts', 'blog-post-remover' ); ?>
				</button>
			</p>

			<div id="bpr-progress" class="bpr-progress" aria-live="polite"></div>
		</div>
		<?php
	}

	/**
	 * Start deletion and return total posts.
	 *
	 * @return void
	 */
	public function ajax_start_deletion() {
		$this->validate_ajax_request();

		$total_posts = $this->count_blog_posts();

		wp_send_json_success(
			array(
				'total'      => $total_posts,
				'batch_size' => self::BATCH_SIZE,
			)
		);
	}

	/**
	 * Delete a batch of blog posts.
	 *
	 * @return void
	 */
	public function ajax_delete_batch() {
		$this->validate_ajax_request();

		$input_total   = isset( $_POST['total'] ) ? (int) $_POST['total'] : 0;
		$input_deleted = isset( $_POST['deleted'] ) ? (int) $_POST['deleted'] : 0;

		if ( $input_total < 0 || $input_deleted < 0 || $input_deleted > $input_total ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid progress data received.', 'blog-post-remover' ),
				),
				400
			);
		}

		$post_ids = get_posts(
			array(
				'post_type'              => 'post',
				'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
				'posts_per_page'         => self::BATCH_SIZE,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => true,
			)
		);

		if ( empty( $post_ids ) ) {
			wp_send_json_success(
				array(
					'deleted'   => $input_total,
					'total'     => $input_total,
					'remaining' => 0,
					'complete'  => true,
				)
			);
		}

		$deleted_this_batch = 0;

		foreach ( $post_ids as $post_id ) {
			$deleted = wp_delete_post( (int) $post_id, true );

			if ( false !== $deleted ) {
				++$deleted_this_batch;
			}
		}

		$remaining       = $this->count_blog_posts();
		$deleted_so_far  = $input_total - $remaining;
		$is_complete     = ( 0 === $remaining );
		$final_deleted   = max( $input_deleted + $deleted_this_batch, $deleted_so_far );
		$bounded_deleted = min( $final_deleted, $input_total );

		wp_send_json_success(
			array(
				'deleted'   => $bounded_deleted,
				'total'     => $input_total,
				'remaining' => $remaining,
				'complete'  => $is_complete,
			)
		);
	}

	/**
	 * Validate nonce and capability.
	 *
	 * @return void
	 */
	private function validate_ajax_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You are not allowed to perform this action.', 'blog-post-remover' ),
				),
				403
			);
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'bpr_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed. Please refresh and try again.', 'blog-post-remover' ),
				),
				403
			);
		}
	}

	/**
	 * Count blog posts only.
	 *
	 * @return int
	 */
	private function count_blog_posts() {
		$count = wp_count_posts( 'post' );

		if ( ! $count instanceof stdClass ) {
			return 0;
		}

		$total = 0;

		foreach ( $count as $value ) {
			$total += (int) $value;
		}

		return $total;
	}
}

new Blog_Post_Remover();
