<?php
/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 10.01.18
 * Time: 11:09
 */

/**
 * Allow SVG upload
 */
add_filter('upload_mimes', 'cc_mime_types');
function cc_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}

/**
 * generate img by id
 */
function generate_img_by_id($id, $class) {
    // ACF IMAGE (ID) - MAKE SURE FIELD IS SET TO 'Image ID'
    $img_acf_size = 'featured_large';
    $img_acf_src = wp_get_attachment_image_src( $id, $img_acf_size );
    $img_acf_srcset = wp_get_attachment_image_srcset( $id, $img_acf_size );
    $img_acf_srcset_sizes = wp_get_attachment_image_sizes( $id, $img_acf_size );
    $img_acf_alt_text = get_post_meta( $id, '_wp_attachment_image_alt', true);
    $img_acf_title = get_the_title( $id );

    ob_start()
    ?>
    <img class="<?= $class ?>"
         src="<?= esc_url( $img_acf_src[0] ); ?>"
         title="<?= esc_attr( $img_acf_title ); ?>"
         srcset="<?= esc_attr( $img_acf_srcset ); ?>"
         sizes="<?= esc_attr( $img_acf_srcset_sizes ); ?>"
         alt="<?= $img_acf_alt_text ?>"
    />
    <?php return ob_get_clean();
}



add_action('admin_bar_menu', 'remove_from_admin_bar', 999);
function remove_from_admin_bar($wp_admin_bar)
{
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->remove_node('new_draft');
    $wp_admin_bar->remove_node('avia');
    $wp_admin_bar->remove_node('avia_ext');
    $wp_admin_bar->remove_node('wpseo-menu');
    $wp_admin_bar->remove_node('customize');
    $wp_admin_bar->remove_node('duplicate-post');
    $wp_admin_bar->remove_node('wp-logo');
}

/**
 * show login widget area
 */
function show_login_widget_area()
{
    ob_start(); ?>

    <?php if (is_active_sidebar('login-area')) : ?>
    <div class="row">
        <div class="col-xs-12">
            <div class="login-headline">
                <h1><?php _e('Händler Login', 'partycrowd'); ?></h1>
            </div>
        </div>
    </div>
    <div class="row" id="login-registration">
        <div class="col-md-6">
            <div id="login-area" class="login-area-area widget-area" role="complementary">
                <?php dynamic_sidebar('login-area'); ?>
            </div>
        </div>
        <div class="col-md-6">
            <h2><?php _e('Benutzer registrieren', 'partycrowd'); ?></h2>
            <p><?php _e('Wenn Sie nicht registriert sind, können Sie nicht auf die geschützten Inhalte zugreifen.', 'partycrowd'); ?></p>
            <p><?php _e('Sie können sich über den folgenden Link registrieren und erhalten die Bestätigung sofern Sie die Voraussetzungen erfüllen.', 'partycrowd'); ?></p>
            <p><?php _e('Danke!', 'partycrowd'); ?></p>
            <a class="button" href="/haendler-login/registrierung/"><?php _e('Registrieren', 'partycrowd'); ?></a>
        </div>
    </div>
<?php endif; ?>

    <?php echo ob_get_clean();
}

/**
 * get image data by id
 */
if (!function_exists('wp_get_attachment')) {
    function wp_get_attachment($attachment_id, $attachment_size = 'full')
    {
        $attachment = get_post($attachment_id);
        return array(
            'title' => $attachment->post_title,
            'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
            'caption' => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'copyright' => get_post_meta($attachment->ID, '_avia_attachment_copyright', true),
            'src' => wp_get_attachment_image_url($attachment_id, $attachment_size),
        );
    }
}

/**
 * Remove dashboard access for subscriber user
 */
add_action('init', function () {
    if (is_admin() && !defined('DOING_AJAX') && (current_user_can('subscriber'))) {
        wp_redirect(get_the_permalink(537));
        exit;
    }
});

add_action('after_setup_theme', function () {
    if (current_user_can('subscriber')) {
        show_admin_bar(false);
    }
});

/**
 * remove comments from backend menu
 */
add_action( 'admin_menu', 'dashboard_remove_menu_pages' );
function dashboard_remove_menu_pages() {
	remove_menu_page('edit-comments.php'); // Entfernt den Menüpunkt Kommentare
}


/**
 * set custom backend menu order
 */
function custom_menu_order( $menu_ord ) {
	if ( ! $menu_ord ) {
		return true;
	}

	return array(
		'index.php', // Dashboard
		'edit.php', // Beiträge
		'upload.php', // Mediathek
		'edit.php?post_type=page', // Seiten
        'admin.php?page=gf_edit_forms', //Forms
		//'separator1', // Erster visueller Trenner
        /*'edit.php?post_type=obst-und-gemuese',
        'edit.php?post_type=fruchtsnack',
        'edit.php?post_type=blumen-und-pflanzen',
        'edit.php?post_type=oel',
        'edit.php?post_type=mixology',
		'edit.php?post_type=pressemeldung',
		'edit.php?post_type=download',
		'edit.php?post_type=point_of_sale',*/
	);
}

add_filter( 'custom_menu_order', 'custom_menu_order' ); // Activate custom_menu_order
add_filter( 'menu_order', 'custom_menu_order' );


// set gravity forms menu positioning
add_filter( 'gform_menu_position', 'my_gform_menu_position', 10, 1 );
function my_gform_menu_position( $position ) {
	return 5;
}