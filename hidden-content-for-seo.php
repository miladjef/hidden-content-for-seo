<?php
/**
 ** Plugin Name: Hidden Content for SEO
 ** Description: A plugin that hides content on pages from users but makes it readable by search engines. Compatible
 * with Rank Math.
 ** Version: 1.0
 * * Author: milad jafari gavzan
 * * Author URI: https://miladjafarigavzan.ir
 * * License: GPL-2.0+
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
	exit;
}

// Add a meta box for entering hidden content using the WordPress text editor
function hcfs_add_meta_box() {
	add_meta_box(
		'hcfs_meta_box',
		'Hidden Content for SEO',
		'hcfs_meta_box_callback',
		'page', // This plugin only works on Pages
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'hcfs_add_meta_box');

// Callback function to render the meta box with the WordPress text editor
function hcfs_meta_box_callback($post) {
	wp_nonce_field('hcfs_save_meta_box_data', 'hcfs_meta_box_nonce');

	$value = get_post_meta($post->ID, '_hcfs_hidden_content', true);

	// WordPress editor for hidden content
	wp_editor($value, 'hcfs_hidden_content', array(
		'textarea_name' => 'hcfs_hidden_content',
		'media_buttons' => true, // Allows adding media like images
		'textarea_rows' => 10,
		'teeny' => false, // Full-featured editor (not a simple version)
	));
	echo '<p>This content will be hidden from users and only readable by search engines.</p>';
}

// Save the meta box data
function hcfs_save_meta_box_data($post_id) {
	// Check if nonce is set
	if (!isset($_POST['hcfs_meta_box_nonce'])) {
		return;
	}

	// Verify the nonce
	if (!wp_verify_nonce($_POST['hcfs_meta_box_nonce'], 'hcfs_save_meta_box_data')) {
		return;
	}

	// Prevent autosave from overwriting data
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check user permissions before saving
	if (isset($_POST['post_type']) && 'page' === $_POST['post_type']) {
		if (!current_user_can('edit_page', $post_id)) {
			return;
		}
	}

	// Check if the hidden content is set
	if (!isset($_POST['hcfs_hidden_content'])) {
		return;
	}

	// Sanitize and save the hidden content
	$my_data = wp_kses_post($_POST['hcfs_hidden_content']); // Allows safe HTML
	update_post_meta($post_id, '_hcfs_hidden_content', $my_data);
}
add_action('save_post', 'hcfs_save_meta_box_data');

// Display the hidden content only for search engines
function hcfs_display_hidden_content_for_seo() {
	if (is_page()) {
		global $post;
		$hidden_content = get_post_meta($post->ID, '_hcfs_hidden_content', true);

		if ($hidden_content) {
			// Hidden content is wrapped in a div with 'display:none'
			echo '<!-- wp:hidden-content-start -->';
			echo '<div style="display:none;">' . apply_filters('the_content', $hidden_content) . '</div>';
			echo '<!-- wp:hidden-content-end -->';
		}
	}
}
add_action('wp_footer', 'hcfs_display_hidden_content_for_seo');

// Function to inject hidden content into Rank Math's analysis
function hcfs_add_hidden_content_to_rank_math( $content ) {
	if ( is_page() ) {
		global $post;
		$hidden_content = get_post_meta( $post->ID, '_hcfs_hidden_content', true );

		if ( $hidden_content ) {
			// Append the hidden content to the main content so Rank Math can analyze it
			$content .= ' ' . apply_filters('the_content', $hidden_content);
		}
	}
	return $content;
}

// Add the hidden content into Rank Math's content analysis filter
add_filter( 'rank_math/frontend/content', 'hcfs_add_hidden_content_to_rank_math', 10, 1 );
add_filter( 'rank_math/analyze/content', 'hcfs_add_hidden_content_to_rank_math', 10, 1 );

// Add hidden content to the SEO content in the admin area for Rank Math to recognize
function hcfs_add_hidden_content_to_seo_analysis( $post_content, $post ) {
	if ( $post->post_type === 'page' ) {
		$hidden_content = get_post_meta( $post->ID, '_hcfs_hidden_content', true );

		if ( $hidden_content ) {
			// Append the hidden content so Rank Math can analyze it
			$post_content .= ' ' . apply_filters('the_content', $hidden_content);
		}
	}
	return $post_content;
}
add_filter( 'rank_math/content_analysis/prepare_content', 'hcfs_add_hidden_content_to_seo_analysis', 10, 2 );
