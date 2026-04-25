<?php

add_action('wp_enqueue_scripts', 'theme_enqueue_styles', 9999999);
function theme_enqueue_styles()
{
    // Get the theme data
    $the_theme = wp_get_theme();

    $build = get_stylesheet_directory() . '/build/';
    if (is_dir($build)) {
        $files = scandir($build);
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $string) {
                if ($string === '.' || $string === '..') continue;
                if (strpos($string, 'style') === 0) {
                    wp_enqueue_style('pc_custom_css', get_stylesheet_directory_uri() . '/build/' . $files[$key], array(), $the_theme->get('Version'));
                }  elseif (strpos($string, 'runtime') === 0) {
                    wp_enqueue_script('runtime_js', get_stylesheet_directory_uri() . '/build/' . $files[$key], array('jquery'), $the_theme->get('Version'), true);
                } elseif (strpos($string, 'main') === 0) {
                    wp_enqueue_script( 'custom_footer_js', get_stylesheet_directory_uri() . '/build/' . $files[$key], array('runtime_js'), $the_theme->get( 'Version' ), true );
                }
            }
        }
    }
	wp_enqueue_script( 'hoverIntent', get_stylesheet_directory_uri() . '/hover-intent.min.js', array('jquery'), $the_theme->get( 'Version' ), true );
}

/**
 * Registers an editor stylesheet for the theme.
 */
add_action('admin_enqueue_scripts', 'custom_theme_add_editor_styles');
function custom_theme_add_editor_styles()
{
    $build = get_stylesheet_directory() . '/build/';
    if (is_dir($build)) {
        $files = scandir($build);
        if (is_array($files) && !empty($files)) {
            foreach ($files as $key => $string) {
                if ($string === '.' || $string === '..') continue;
                if (strpos($string, 'custom-editor-style') === 0) {
                    wp_enqueue_style('admin-styles', get_stylesheet_directory_uri() . '/build/' . $files[$key]);
                }
            }
        }
    }
}

function pc_login_stylesheet() {
	wp_enqueue_style( 'custom-login', get_stylesheet_directory_uri() . '/style-login.css' );
}
add_action( 'login_enqueue_scripts', 'pc_login_stylesheet' );

