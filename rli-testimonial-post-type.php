<?php
/*
Plugin Name: Rocket Lift Testimonial Post Type
Version: 1.1
Plugin URI: http://rocketlift.com/software/rli-testimonials
Description: Adds a custom post type called 'Testimonial'.
Author: Matthew Eppelsheimer and Rocket Lift
Author URI: http://rocketlift.com/
License: GPL 2
*/

// Register CPT

function rli_testimonial_register() {
	register_post_type( 'rli_testimonial' , array( 
		'public' => true,
		'exclude_from_search' => true,
		'publicly_queryable' => false,
		'supports' =>  array(
			'title',
			'editor',
			'excerpt',
			'page-attributes'
		),
		'query_var' => 'rli_testimonial',
		'rewrite' =>  array(
			'slug' => 'testimonial'
		),
		'labels' => array(
			'name' => "Testimonials",
			'singular_name' => "Testimonial",
			'add_new' => "Add Testimonial",
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

add_action( 'init', 'rli_testimonial_register', 10 );

// Register testimonial category taxonomy

function rli_testimonial_category_register(){

	$labels = array( 
		'name' => 'Testimonial Categories',
		'singular_name' => 'Testimonial Category',
		'menu_name'	=>	'Testimonial Categories',
		'all_items'	=>	'All Categories',
		'edit_item'	=>	'Edit Category',
		'view_item'	=>	'View Category',
		'update_item'	=>	'Update Category',
		'add_new_item'	=>	'Add new Category',
		'new_item_name'	=>	'New Taxonomy Category name',
		'search_items'	=>	'Search Taxonomy Categories',
		'popular_items'	=>	'Popular Taxonomy Categories',
		'separate_items_with_commas' =>	'Separate categories with commas',
		'add_or_remove_items'	=>	'Add or remove Taxonomy Categories',
		'choose_from_most_used'	=>	'Choose from the most used categories',
		'not_found'	=>	'No categories found'
	);

	$args = array( 
		'labels' => $labels,
		'show_admin_column' => true,
		'query_var'	=>	'testimonial_category',
		'hierarchical'	=>	true,
		'update_count_callback' => '_update_post_term_count'
	);

	register_taxonomy(
		'rli_testimonial_category',
		'rli_testimonial',
		$args
	);
	
	register_taxonomy_for_object_type( 'rli_testimonial_category', 'rli_testimonial' );
}
add_action( 'init', 'rli_testimonial_category_register', 11 );

// include rli_testimonial posts in tag pages 
// CURRENTLY DISABLED
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
 *	rli_testimonial_testimonials() takes query arguments for rli_testimonial and 
 *	performs the query, manages a custom loop, and echoes html
 *
 *	@param $args an array of $args formatted for WP_Query to accept
 *  @param $template_callback a callback template to output html within the loop
 *  @param $loop_args an array with 'before' and 'after' keys for inside the "if" and outside "while"
 *	
 *
 *  @uses rli_testimonial_get_testimonials()
 *	@return output if we output html with testimonials; false if not
 */

function rli_testimonial_testimonials( $args = array(), $template_callback = 'rli_testimonial_widget_display_template_default', $loop_args = array() ) {
	echo rli_testimonial_get_testimonials( $args, $template_callback, $loop_args );
}

/*
 *	rli_testimonial_get_testimonials() takes query arguments for rli_testimonial and 
 *	performs the query, manages a custom loop, and returns html
 *
 *	@param $args an array of $args formatted for WP_Query to accept
 *  @param $template_callback a callback template to output html within the loop
 *  @param $loop_args an array with 'before' and 'after' keys for inside the "if" and outside "while"
 *	
 *	@return output if we output html with testimonials; false if not
 */

function rli_testimonial_get_testimonials( $args = array(), $template_callback = 'rli_testimonial_widget_display_template_default', $loop_args = array() ) {

	$testimonials = rli_testimonial_query_testimonials( $args );
	
	if ( $testimonials->have_posts() ) {
		$output = "";

		if ( ! empty( $loop_args['before'] ) )
			$output .= $loop_args['before'];

		while ( $testimonials->have_posts() ) {
			$testimonials->the_post();

			/*	BUILD HTML	*/
			$output .= $template_callback();

		}

		if ( ! empty( $loop_args['after'] ) )
			$output .= $loop_args['after'];

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
		$new_defaults = wp_parse_args( $defaults_override, $defaults );
		$query_args = wp_parse_args( $args, $new_defaults );
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

/**
 *	Testimonials Widgets
 */

class rli_testimonial_widget extends WP_Widget {

	// register the widget
	function rli_testimonial_widget() {
		$widget_options = array(
			'classname' => 'rli_testimonial_widget',
			'description' => 'Display a list of testimonials.'
		);
		$this->WP_Widget( 'rli_testimonial_widget', 'Testimonials Widget' );
	}

	// build the widget's admin form
	function form( $instance ) {
		$defaults = array(
			'title' => 'Testimonials',
			'number' => 3,
			'orderby' => 'menu_order',
			'category' => 'all'
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$number = $instance['number'];
		$orderby = $instance['orderby'];
		$category = $instance['category'];
		
		// Get categories
		$args = array(
			'orderby'	=>	'name',
			'order'	=>	'ASC',
			'hide_empty'	=>	true
		);
		$categories = get_terms(
			'rli_testimonial_category',
			$args
		);
		
		// print_r( $categories );

		// Widget title field
		$output = "<p>" . __( 'Widget Title', 'rli_testimonials' ) . ": <input class='widefat' name='" . $this->get_field_name( 'title' ) . "' type='text' value='" . esc_attr( $title ) . "' /></p>";

		// Number of testimonials to display field
		$output .= "<p>" . __( 'Number of testimonials to display', 'rli_testimonials' ) . ": <input class='widefat' name='" . $this->get_field_name( 'number' ) . "' type='text' value='" . esc_attr( $number ) . "' /></p>";

		// Order by selector
		$output .= "<p>" . __( 'Order by', 'rli_testimonials' ) . ": <select name='" . $this->get_field_name( 'orderby' ) . "'>";
			$output .= "<option value='menu_order' " . selected( $orderby, 'menu_order', false ) . ">" . __( 'Manual (drag and drop)', 'rli_testimonials' ) . "</option>";
			$output .= "<option value='date' " . selected( $orderby, 'date', false ) . ">" . __( 'Latest (publish date)', 'rli_testimonials' ) . "</option>";
			$output .= "<option value='rand' " . selected( $orderby, 'rand', false ) . ">" . __( 'Random', 'rli_testimonials' ) . "</option>";
		$output .= "</select></p>";

		// Category selector
		$output .= "<p>" . __( 'Category', 'rli_testimonials' ) . ": <select name='" . $this->get_field_name( 'category' ) . "'>";
			$output .= "<option value='' " . selected( $category, '', false ) . ">" . __( 'All categories', 'rli_testimonials' ) . "</option>";
			// run through array of categories
			foreach ( $categories as $cat ) {
				$output .= "<option value='" . $cat->slug . "' " . selected( $category, $cat->slug, false ) . ">" . $cat->name . "</option>";
			}
		$output .= "</select></p>";

		echo $output;
	}

	// save widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = strip_tags( $new_instance['number'] );
		$instance['orderby'] = strip_tags( $new_instance['orderby'] );
		$instance['category'] = strip_tags( $new_instance['category'] );

		return $instance;
	}

	// display the widget
	function widget( $args, $instance ) {
		// prepare settings
		global $post;
		$title = apply_filters( 'widget_title', $instance['title'] );
		$number = empty( $instance['number'] ) ? 3 : $instance['number'];
		$orderby = empty( $instance['orderby'] ) ? 'date' : $instance['orderby'];
		$category = empty( $instance['category'] ) ? '' : $instance['category'];
		
		// prepare query settings
		$query_args = array(
			'orderby' => $orderby,
			'posts_per_page' => $number
		);
		
		// sort by category if needed
		if ( ! empty( $category ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'	=>	'rli_testimonial_category',
					'field'		=>	'slug',
					'terms'		=>	$category
				)
			);
		}
		
		// Begin output
		$output = '';
		if ( ! empty( $args['before_widget'] ) )
			$output = $args['before_widget'];

		// Prepare output for before and after content inside the loop
		$loop_args = array(
			'before' => '',
			'after' => ''
		);
		if ( ! empty( $title ) )
			$loop_args['before'] = $args['before_title'] . $title . $args['after_title'] . "\n";
		$loop_args['before'] .= "<ul>";
		$loop_args['after'] .= "</ul>";

		// query and echo testimonials based on args, with filterable display template callback
		$output .= rli_testimonial_get_testimonials( 
			$query_args 
			, apply_filters( 'rli_testimonial_widget_template', 'rli_testimonial_widget_display_template_default' )
			, apply_filters( 'rli_testimonial_widget_loop_args', $loop_args )
		 );

		// Complete output
		if ( ! empty( $args['after_widget'] ) )
			$output .= $args['after_widget'];
		
		echo $output;
	}
}

// Register widget
function rli_testimonial_register_widget() {
	register_widget( 'rli_testimonial_widget' );
}

add_action( 'widgets_init', 'rli_testimonial_register_widget' );

// Default widget display template
function rli_testimonial_widget_display_template_default() {
	$output = "<li class='rli-testimonial'><span class='rli-testimonial-content'>" . get_the_content() . "</span> &mdash; <span class='rli-testimonial-author'>" . get_the_title() . "</span></li>";
	return $output;
}

// Shortcode

function rli_testimonial_register_shortcodes() {
	add_shortcode( 'testimonial', 'rli_testimonial_shortcode' );
}
add_action( 'init', 'rli_testimonial_register_shortcodes' );

/*
 *	Creates a shortcode to display a list of people on demand
 *
 *	Supports the 'category' keyword.
 */

function rli_testimonial_shortcode( $atts ) {

	// Treat 'cat' and 'category' inputs interchangably
	if ( ! empty( $atts['cat'] ) )
		$atts['category'] = $atts['cat'];

	// Set up default shortcode attributes
	$atts = shortcode_atts( 
		array( 
			'category'	=>	'',
			'num'		=>	null,
			'order'		=>	apply_filters( 'rli_testimonial_shortcode_default_orderby', 'date' ),
			'header'		=>	null,
			'headerwrap'	=>	apply_filters( 'rli_testimonial_shortcode_default_header_wrapper', 'h3' )
		), 
		$atts
	);
	
	// Prepare query args based on shortcode attributes
	$query_args = array();
	
	// Handle category shortcode attribute
	if ( '' != $atts['category'] )
		$query_args['tax_query'] = array(
			array( 
				'taxonomy'	=>	'rli_testimonial_category',
				'field'		=>	'slug',
				'terms'		=>	$atts['category']
			)
		);

	// Handle number of posts to show
	if ( ! empty( $atts['num'] ) ) 
		$query_args['posts_per_page'] = $atts['num'];
	
	// Handle post order
	$query_args['orderby'] = $atts['order'];

	// Get testimonials
	$testimonials = rli_testimonial_get_testimonials( 
		$query_args
		, apply_filters( 'rli_testimonial_shortcode_template', 'rli_testimonial_shortcode_display_template_default' )
		, apply_filters( 'rli_testimonial_shortcode_loop_args', array() )
	 );
		
	// Bail if there are none
	if ( false == $testimonials )
		return false;

	// Build and display header	
	$header_text = '';
	if ( null != $atts['header'] ) { // if header is not null, show the header
		$header_text = $atts['header'];
	} else if ( ! empty( $atts['category'] ) ) { // if cat is set, display the title based on category
		// translate cat slug into name
		$category_name = get_term_by( 'slug', $atts['category'], 'rli_testimonial_category' )->name;
		$header_text = "Testimonials from " . $category_name;
	} else { // otherwise, display "Testionials" title
		$header_text = "Testimonials";
	}
	$out = '';
	if ( '' != $header_text )
		$out .= "<" . $atts['headerwrap'] . ">$header_text</" . $atts['headerwrap'] . ">";

	// Display testimonials
	$out .= $testimonials;
	return $out;
}

// Default shortcode display template
function rli_testimonial_shortcode_display_template_default() {
	global $post;
	$output = "<p class='rli-testimonial'><span class='rli-testimonial-content'>" . get_the_content() . "</span> &mdash; <span class='rli-testimonial-author'>" . get_the_title() . "</span></p>";
	return $output;
}
