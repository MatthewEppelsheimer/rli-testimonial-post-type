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

	$testimonials = rli_testimonial_query_testimonial( $args );
	
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

/*
 *	rli_testimonial_query_testimonial( $args ) is a wrapper for WP_Query to get rli_testimonial
 *	posts for use in a custom loop.
 *
 *	@param $args an an array of arguments formatted to pass to WP_Query
 *
 *	@returns array of query results, regardless of query results
 */

function rli_testimonial_query_testimonial( $args ) {
	$defaults = array(
		'orderby' => 'title',
		'order' => 'ASC',
		'posts_per_page' => -1
	);
	$query_args = wp_parse_args( $defaults, $args );
	$query_args['post_type'] = 'rli_testimonial';

	$testimonials = new WP_Query( $query_args);
	
	return $testimonials;
}