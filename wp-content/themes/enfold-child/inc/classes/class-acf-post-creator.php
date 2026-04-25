<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
class ACF_Post_Creator {

    //two types, from jsonfile or gravity form
    public function create_new_client()
    {

        // Create new post.
        $post_data = array(
            'post_title'    => 'temporary-not-labeled',
            'post_name'    => 'temporary-not-labeled',
            'post_type'     => 'clients',
            'post_status'   => 'publish'
        );
        return wp_insert_post( $post_data );
    }

    // can only come from json
    public function create_new_document()
    {
        // Create new post.
        $post_data = array(
            'post_title'    => 'temporary-not-labeled',
            'post_name'    => 'temporary-not-labeled',
            'post_type'     => 'documents',
            'post_status'   => 'publish'
        );
        return wp_insert_post( $post_data );
    }
}