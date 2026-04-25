<?php

/**
 * Parse shortcodes in textarea field
 */
add_filter('acf/format_value/type=textarea', 'do_shortcode');


if (function_exists('acf_add_options_page')) {

    /**
     * Documents List View Settings
     */
    acf_add_options_sub_page(array(
        'page_title'     => 'Documents List View Settings',
        'menu_title'    => 'Documents List View',
        'parent_slug'    => 'edit.php?post_type=documents',
    ));
}

/**
 * load custom field choices for detail blog sites
 */
function acf_load_custom_field_choices( $field ) {
    // reset choices
    $field['choices'] = array();

    // if has rows
    if( have_rows('autoren_autoren', 'option') ) {

        // while has rows
        while( have_rows('autoren_autoren', 'option') ) {

            // instantiate row
            the_row();

            // vars
            $name = get_sub_field('autoren_name');
            $value = $name;
            $label = $name;


            // append to choices
            $field['choices'][ $value ] = $label;
        }
    }
    // return the field
    return $field;
}

add_filter('acf/load_field/name=blog_choose_autor', 'acf_load_custom_field_choices');


/**
 * change document title on save
 * check acf fields (company name + dokument number)
 */
//add_filter( 'wp_insert_post_data' , 'modify_post_title' , '99', 1 ); // Grabs the inserted post data so you can modify it.

function modify_post_title( $data )
{
    if($data['post_type'] == 'documents') { // If the actual field name of the rating date is different, you'll have to update this.
        $title = generate_document_title();
        $data['post_title'] =  $title ; //Updates the post title to your new title.
        $data['post_name'] =  strtolower($title) ; //Updates the post title to your new title.

    } /**
     * make errors on gravity save
     */
    else if($data['post_type'] == 'clients') {
        $title = get_field( 'clients_company_name');
        $data['post_title'] =  $title ; //Updates the post title to your new title.
        $data['post_name'] =  strtolower($title) ; //Updates the post title to your new title.
    }
    return $data; // Returns the modified data.
}

/**
 * change document title on save
 * check acf fields (company name + dokument number)
 */
/*function wpse105926_save_post_callback( $post_id ) {

    // verify post is not a revision
    if ( ! wp_is_post_revision( $post_id ) ) {

        // unhook this function to prevent infinite looping
        remove_action( 'save_post', 'wpse105926_save_post_callback' );

        if(get_post_type($post_id) === 'documents') {
            $title = generate_document_title();
        } else if (get_post_type($post_id) === 'clients') {
            $title = get_field('clients_company_name');
        }
        // update the post slug
        wp_update_post( array(
            'ID' => $post_id,
            'post_title' => $title,
            'post_name' => strtolower($title),
        ));

        // re-hook this function
        add_action( 'save_post', 'wpse105926_save_post_callback' );

    }
}*/

// verursacht fehler beim bearbeiten im acf backend (Dokumente)
//add_action( 'save_post', 'wpse105926_save_post_callback' );

/**
 * @return string
 * generate title
 */
function generate_document_title($ID = false) {
    $number = '';
    if(!$ID){
        $ID = get_the_ID();
    }
    if( have_rows('documents_document_data', $ID) ): ?>
        <?php while( have_rows('documents_document_data', $ID) ): the_row();
            // Get sub field values.
            $number = get_sub_field('documents_document_data_document_number');
            $number = !empty($number) ? $number : 'M-DN';
            ?>
        <?php endwhile; ?>
    <?php endif;

    $company_name = "";
    $client_data = get_field('documents_client_relationship', $ID);
    if( $client_data ):
        foreach( $client_data as $client_data_ID ):
            $company_name = get_field('clients_company_name', $client_data_ID) ?? 'M-CN';
            $company_name = !empty($company_name) ? $company_name : 'M-CN';
        endforeach;
    endif;

    return str_replace(" ", "-", $company_name) . '-' . str_replace(" ", "-", $number);
}


/**
 * disable specific fields in acf document backend
 * for non admin users
 */
function set_fields_read_only_for_not_admin( $field ) {
    if ( !current_user_can( 'manage_options' ) ) {
        $field['readonly'] = true;
    }
    return $field;
}

function set_fields_read_only( $field ) {
        $field['readonly'] = true;
    return $field;
}


add_filter('acf/prepare_field/name=documents_accuracy_status_percent', 'set_fields_read_only_for_not_admin');
add_filter('acf/prepare_field/name=documents_path', 'set_fields_read_only');



/************************************** AI Labeling Addon PLUGIN AND GENERAL AI SETTINGS ****************************/

add_action('acf/init', 'ai_settings_option_page_init');
function ai_settings_option_page_init()
{
    if (function_exists('acf_add_options_sub_page')) {


        /**
         * AI Settings
         */
        $ai_settings = acf_add_options_page(array(
            'page_title' => __('AI Settings'),
            'menu_title' => __('AI Settings'),
            'redirect' => false,
        ));

        if(class_exists('PC_Addons')) {
            $labeling_view = acf_add_options_sub_page(array(
                'page_title' => __('Labeling View'),
                'menu_title' => __('Labeling View'),
                'parent_slug' => $ai_settings['menu_slug'],
            ));
        }

        $performance = acf_add_options_sub_page(array(
            'page_title' => __('Performance'),
            'menu_title' => __('Performance'),
            'parent_slug' => $ai_settings['menu_slug'],
        ));

        $logs = acf_add_options_sub_page(array(
            'page_title' => __('Logs'),
            'menu_title' => __('Logs'),
            'parent_slug' => $ai_settings['menu_slug'],
        ));

        /**
         * Add sub page (should be additional plugin)
         */
        $export_transfer = acf_add_options_sub_page(array(
            'page_title' => __('Export/Transfer'),
            'menu_title' => __('Export/Transfer'),
            'parent_slug' => $ai_settings['menu_slug'],
        ));

    }
}


/*
function my_post_updated_func( $post_id ) {
    // If this is just a revision, don't do anything
    if ( wp_is_post_revision( $post_id ) || get_post_type($post_id) !== 'clients')return;
    remove_action( 'post_updated', 'my_post_updated_func' );
    $v = get_post_meta( $post_id, 'field_6448d03239ad6', true );
    $my_args = array(
        'ID' => $post_id,
        'post_title' => $v,
        'post_name' => strtolower(str_replace(" ", "-", $v)),
    );
    // update the post, which calls save_post again
    wp_update_post( $my_args );
    add_action( 'post_updated', 'my_post_updated_func' );
}
add_action( 'post_updated', 'my_post_updated_func' );*/


if( function_exists('acf_add_options_page') ) {
    acf_add_options_page(array(
        'page_title'    => 'Rechnungs-Absender',
        'menu_title'    => 'Absender Profile',
        'menu_slug'     => 'sender-profiles',
        'capability'    => 'edit_posts',
        'redirect'      => false,
        'icon_url'      => 'dashicons-building',
    ));
}

add_filter('acf/load_field/name=invoice_sender_profile', 'partycrowd_load_sender_choices');
function partycrowd_load_sender_choices( $field ) {
    $field['choices'] = array();

    // Prüfen, ob der Repeater auf der Optionen-Seite Daten hat
    if( have_rows('sender_profiles', 'option') ) {
        while( have_rows('sender_profiles', 'option') ) {
            the_row();
            // Firmenname als Auswahlmöglichkeit setzen
            $name = get_sub_field('company_name');
            if ( $name ) {
                $field['choices'][ $name ] = $name;
            }
        }
    }
    return $field;
}