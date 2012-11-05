<?php
/*
Plugin Name: Rocket Lift Testimonial Post Type
Version: 1.0
Plugin URI: http://rocketlift.com/software/testimonials-post-type
Description: Adds a custom post type called 'Testimonial'.
Author: Matthew Eppelsheimer
Author URI: http://rocketlift.com/
License: GPL 2
*/


function rli_testimonial_register() {
	register_post_type( 'rli_testimonial' , array( 
		'public' => true,
		'supports' =>  array(
			'title',
			'editor',
			'excerpt'
		),
		'query_var' => 'rli_testimonial',
		'rewrite' =>  array(
			'slug' => 'testimonial'
		),
		'labels' => array(
			'name' => "Testimonials",
			'singular_name' => "Testimonial",
			'add_new' => "Add New Testimonial",
			'add_new_item' => "Add New Testimonial",
			'edit_item' => "Edit Testimonial",
			'new_item' => "Add New Testimonial",
			'view_item' => "View Testimonial",
			'search_items' => "Search Testimonials",
			'not_found' => "No Testimonials Found",
			'not_found_in_trash' => "No Testimonials Found in Trash"
		)
	) );
}

add_action( 'init', 'rli_testimonial_register' );

// include rli_testimonial posts in tag pages 
// see www.slideshare.net/andrewnacin/you-dont0know-query-wordcamp-portland-2011 circa slide #45
function rli_testimonial_include_testimonials_in_query( $query ) {
	if ( $query->is_tag() )
		$query->set( 'post_type', 'rli_testimonial' );
		$query->set( 'posts_per_page', '-1' );
}

// add_action( 'pre_get_posts', 'rli_testimonial_include_testimonials_in_query' );

// add rules
function rli_testimonial_post_type_add_rules() {
	add_rewrite_rule( 'testimonial/?([^/]*)', 'index.php?rli_testimonial=$matches[1]', 'top' );
}

// activation
function rli_testimonial_post_type_activation() {
	rli_testimonial_post_type_add_rules();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'rli_testimonial_post_type_activation' );

// deactivation
function rli_testimonial_post_type_deactivation() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'rli_testimonial_post_type_deactivation' );

/*
 *	rli_testimonial_show_testimonials() takes query arguments for rli_testimonial and 
 *	performs the query, manages a custom loop, and echoes html
 *
 *	@param $args an array of $args formatted for WP_Query to accept
 *	
 *	@return true if we output html with testimonials; false if not
 */

function rli_testimonial_show_testimonials( $args, $template_callback ) {

	global $post;

	$testimonials = rli_testimonial_query_testimonials( $args );
	
	if ( $testimonials->have_posts() ) {
		$output = "";
		while ( $testimonials->have_posts() ) {
			$testimonials->the_post();
			
			/*	BUILD HTML	*/
			$output .= $template_callback( $post );
			
		}
		wp_reset_query();
		return $output;
	}
	
	wp_reset_query();
	return false;
}

/**
 * RLI Utility to get get custom posts of a given type
 *
 *	@param		str		$post_type	The post type to query for
 *	@param		array	$args	Array of arguments formulated to pass to the WP_Query class constructor
 *	@param		array	$defaults_override	Array of default arguments formulated to pass to the 
 *						WP_Query class constructor and override the utility's own defaults
 *	@returns			an array of WP_Query results of the custom post type passed
 *
 *	@since		2012/11/01
 */

if ( ! function_exists( 'rli_library_get_custom_posts' ) ) {
	function rli_library_get_custom_posts( $post_type, $args, $defaults_override = array() ) {
		$defaults = array(
			'posts_per_page' => -1,
			'order' => 'ASC',
			'orderby' => 'menu_order'
		);
		$new_defaults = wp_parse_args( $defaults, $defaults_override );
		$query_args = wp_parse_args( $new_defaults, $args );
		$query_args['post_type'] = $post_type;
	
		$results = new WP_Query( $query_args );
	
		return $results;
	}
}

/**
 * Utility to query testimonials
 *
 *	@param		array	$args	Array of arguments formulated to pass to the WP_Query class constructor
 *	@returns	an array of WP_Query results with rli_testimonial posts
 *
 *	@uses		rli_library_get_custom_posts()
 *	@since		version 0.4
 */

function rli_testimonial_query_testimonials( $args = array() ) {
	return rli_library_get_custom_posts( 'rli_testimonial', $args );
}
