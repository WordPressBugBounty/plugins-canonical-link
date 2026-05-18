<?php
/*
Plugin Name: Canonical Link
Plugin URI: https://webguy.io/blog/super-simple-dynamic-canonical-link-code/
Description: Adds the canonical link to your site (https://wikipedia.org/wiki/Canonical_link_element). Activate and then set your permalinks to "Post name" under Settings > Permalinks. That's it. Verify that it's working correctly with the Firefox add-on: https://addons.mozilla.org/addon/canonical-link/.
Version: 1.7
Requires at least: 5.0
Author: Web Guy
Author URI: https://webguy.io/
License: CC0
License URI: https://creativecommons.org/public-domain/cc0/
Text Domain: canonical-link
*/

if ( !defined( 'ABSPATH' ) ) {
	http_response_code( 404 );
	die();
}

if ( is_admin() ) {
	add_action( 'add_meta_boxes', 'canonicalink_meta_box_add' );
	function canonicalink_meta_box_add() {
		$post_types = get_post_types( array( 'public' => true ) );
		add_meta_box( 'canonicalink_post', 'Canonical Link', 'canonicalink_post_meta_box', $post_types, 'normal', 'high' );
	}
	function canonicalink_post_meta_box( $post ) {
		$values = get_post_custom( $post->ID );
		$canonicalink_custom_url = '';
		if ( isset( $values['canonicalink_custom'] ) ) {
			$canonicalink_custom_url = esc_url( $values['canonicalink_custom'][0] );
		}
		wp_nonce_field( 'canonicalink_meta_box_nonce', 'meta_box_nonce' );
		?>
		<p><input type="text" name="canonicalink_custom" class="large-text" placeholder="https://example.com/" value="<?php if ( $canonicalink_custom = get_post_meta( $post->ID, 'canonicalink_custom', true ) ) { echo esc_url( $canonicalink_custom_url ); } ?>"></p>
		<?php
	}
	add_action( 'save_post', 'canonicalink_meta_box_save' );
	function canonicalink_meta_box_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['meta_box_nonce'] ) ), 'canonicalink_meta_box_nonce' ) ) return;
		if ( !current_user_can( 'edit_post', $post_id ) ) return;
		if ( isset( $_POST['canonicalink_custom'] ) ) {
			update_post_meta( $post_id, 'canonicalink_custom', esc_url_raw( wp_unslash( $_POST['canonicalink_custom'] ) ) );
		}
	}
}

add_action( 'wp_head', 'canonicalink', 1 );
function canonicalink() {
	global $post;
	if ( !$post ) return;
	$canonicalink_custom_url = '';
	$values = get_post_custom( $post->ID );
	if ( isset( $values['canonicalink_custom'] ) ) {
		$canonicalink_custom_url = esc_url( $values['canonicalink_custom'][0] );
	}
	if ( ( is_singular() ) && ( $canonicalink_custom = get_post_meta( $post->ID, 'canonicalink_custom', true ) ) ) {
		echo '<link rel="canonical" href="' . esc_url( $canonicalink_custom_url ) . '">';
	} else {
		echo '<link rel="canonical" href="' . esc_url( ( isset( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . ( isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '' ) . wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH ) ) . '">';
	}
}