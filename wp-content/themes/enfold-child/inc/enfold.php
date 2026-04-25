<?php
/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 21.12.17
 * Time: 12:19
 */

/**
 * Remove Portfolio
 */
add_action('after_setup_theme', 'remove_portfolio');
function remove_portfolio()
{
    remove_action('init', 'portfolio_register');
}


/**
 * Deaktivate Layerslider
 */
add_theme_support( 'deactivate_layerslider' );

/**
 * Allow custom enfold elements
 */
add_filter('avia_load_shortcodes', 'avia_include_shortcode_template', 15, 1);
function avia_include_shortcode_template($paths)
{
    $template_url = get_stylesheet_directory();
    array_unshift($paths, $template_url . '/avia-shortcodes/');

    return $paths;
}

/*
 * Custom Color Section
 */
add_filter('avf_color_sets', function ($color_sets) {
    $color_sets['tertiary_color'] = 'Lightblue Content';
    $color_sets['beige_color'] = 'Beige Content';
    return $color_sets;
});


/** redirect not used archives */
add_action('template_redirect', 'meks_remove_wp_archives');
function meks_remove_wp_archives(){
	$custom_tax = array(
	);
	if( is_category() ||
	    is_tag() ||
	    is_date() ||
	    is_author() ||
	    is_tax( $custom_tax ) ) {
		global $wp_query;
		$wp_query->set_404(); //set to 404 not found page
	}
}
