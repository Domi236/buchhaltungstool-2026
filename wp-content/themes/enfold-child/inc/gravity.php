<?php

/**
 * Gravity stop saving ip's
 */
add_filter('gform_ip_address', '__return_empty_string');

/**
 * Gravity Forms change upload Path (Document Uploader)
 */
add_filter( 'gform_upload_path_2', 'change_upload_path', 10, 2 );
function change_upload_path( $path_info, $form_id ) {

    AI_Logger::debug($path_info['path'], 'test');
    AI_Logger::debug($path_info['url'], 'test');

    /***TODO: change path**/
    //D:\MAMP\htdocs\steuerausgleich-uploader\wp-content\plugins\gravityforms\includes\fields\class-gf-field-fileupload.php
    $path_info['path'] = 'D:/MAMP/htdocs/steuerausgleich-uploader/wp-content/uploads/document-uploader/temporary-uploader/';
    $path_info['url'] = 'http://localhost/steuerausgleich-uploader/wp-content/uploads/document-uploader/temporary-uploader/';

    return $path_info;
}

/**
 * upload Documents in the standard directory
 */
add_action( 'gform_after_submission_2', 'upload_documents', 10, 2 );
function upload_documents( $entry, $form ) {
    //absolue path
    $path = wp_upload_dir()['basedir']. '/document-uploader/temporary-uploader/';
    // url path
    $path_url = wp_upload_dir()['basedir']. '/document-uploader/temporary-uploader/';

    $scan = scandir($path);
    $uploades_files = array();
    foreach($scan as $file) {
        if (!is_dir("$path/$file")) {
            $uploades_files[] = $file;
        }
    }

    //var_dump($uploades_files);
    //exit

    foreach($uploades_files as $file) {
        //$document_uploader = new AI_Document_Uploader(false);
    }
}

/**
 * upload Documents in the standard directory
 */
//add_action( 'gform_after_submission_4', 'save_data', 10, 2 );
function save_data( $entry, $form ) {

    /* foreach ( $form['fields'] as $field ) {
         echo '<pre>';
         $field_value = $field->get_value_export( $entry, $field->id, true );
         echo $field_value;
         // do something with the field value.
     }*/


     $creator = new ACF_Post_Creator;
    if($entry[88] === 'create-new') {
        $client_id = $creator->create_new_client();
     } else {
         $client_id = $entry[79];
     }


     /*if($created) {
        $document_id = $creator->create_new_document();
     } else {
         $document_id = $entry[78];
     }*/

     $updater = new ACF_Post_Updater;
     $updater->update_document($entry, $client_id, $entry[78], 'Gravity' );
     $updater->update_client($entry, $client_id );

     $title = generate_document_title($entry[78]);

     if($title !== get_the_title($entry[78])) {
         wp_update_post( array(
             'ID' => $entry[78],
             'post_title' => $title,
             'post_name' => strtolower($title),
         ));
     }
 }


 /**
  * set Fields Dynamically for Labeling View
  */

add_filter('gform_pre_render_4', 'set_fields_dynamically_for_labeling_view');
add_filter('gform_pre_validation_4', 'set_fields_dynamically_for_labeling_view');
add_filter('gform_pre_submission_filter_4', 'set_fields_dynamically_for_labeling_view');
add_filter('gform_admin_pre_render_4', 'set_fields_dynamically_for_labeling_view');
function set_fields_dynamically_for_labeling_view($form) {
    foreach ($form['fields'] as &$field) {

            /*
             * only for radio btns
            // Generate your data here. Below is just an example
            $cat = get_categories('taxonomy=document_cat&type=documents&hide_empty=0&hierarchical=0' );

            // Generate nice arrays that Gravity Forms can understand
            $choices = [];
            $inputs = [];
            $input_id = 1;
            foreach ($cat as $single) {
                $choices[] = ['text' => $single->name, 'value' => $single->term_id];
                $inputs[] = ['label' => $single->name, 'id' => $single->term_id . '.' . $input_id];
                $input_id++;
            }

            // Set choices to field
            $field->choices = $choices;
            $field->inputs = $inputs;

            foreach( $field->choices as &$choice ) {
                if( 3 === $choice['value']) {
                    $choice['isSelected'] = true;
                }
            }*/

        if($field->inputName == 'gr_choose_client' || $field->inputName == 'gr_choose_recipient' || $field->inputName == 'gr_document_type') {
            // Generate your data here. Below is just an example
            if($field->inputName == 'gr_choose_client') {
                $cpt = get_posts('numberposts=-1&post_type=clients');
                $name = 'Client';
            } else {
                $cpt = get_categories('taxonomy=document_cat&type=documents&hide_empty=0&hierarchical=0' );
                $name = 'Document Type';
            }

            // Generate nice arrays that Gravity Forms can understand
            $choices = [];
            $inputs = [];
            $input_id = 1;
            foreach ($cpt as $page) {
                if($input_id === count($cpt) && $name !== 'Document Type') {
                    $choices[] = ['text' => $page->post_title, 'value' => $page->ID];
                    $inputs[] = ['label' => $page->post_title, 'id' => $field->id . '.' . $input_id];
                } else {
                    $choices[] = ['text' => $page->name, 'value' => $page->term_id];
                    $inputs[] = ['label' => $page->name, 'id' => $field->term_id . '.' . $input_id];
                }

                if($input_id === count($cpt) && $name !== 'Document Type') {
                    $choices[] = ['text' => 'Create New ' . $name, 'value' => 'create-new'];
                    $inputs[] = ['label' => 'Create New ' . $name, 'id' => 'create-new'];
                }
                $input_id++;
            }

            // Set choices to field
            if($name !== 'Document Type') {
                $field->placeholder = 'Select a ' .  $name;
            } else {
                $field->placeholder = 'Select ' .  $name;
            }
            $field->choices = $choices;
            $field->inputs = $inputs;

            if($field->inputName == 'gr_choose_client') {
                $client_ID = get_field('documents_client_relationship', get_the_ID());

                foreach( $field->choices as &$choice ) {
                    if( isset($client_ID) && $client_ID[0] === $choice['value']) {
                        $choice['isSelected'] = true;
                    }
                }
            } else {
                foreach( $field->choices as &$choice ) {
                    $type = get_field('documents_document_type');
                    if( $type == $choice['value']) {
                        $choice['isSelected'] = true;
                    }
                }
            }

        } else if($field->inputName == 'gr_document_articles') {
            /*var_dump($field);
            $list_array = array(
                array(
                    "text" => "Option 1",
                    "value" => ["Good","Good", "Good"  ],
                    'isSelected' =>  false,
                    'price' =>  '',
                    'inputName' => 'myname'
                ),
                array(
                    "text" => "Option 2",
                    "value" => "Good",
                    'isSelected' =>  false,
                    'price' =>  '',
                    'inputName' => 'myname2'
                )
            );
           $field->choices = $list_array;
            var_dump($field->choices);*/
        }
    }
    return $form;
}


/**
 * fill products programmatically
 */
add_filter( 'gform_field_value_gr_document_articles', 'populate_snapshotList' );
function populate_snapshotList( $value ) {
    $ai_data_viewer = new AI_Data_Viewer();
    $products = $ai_data_viewer->get_document_products_from_document(get_the_ID());

    $product_lists = array();
    foreach ($products as $list) {
        $product = array();
        foreach ($list as $key => $value) {
            if($key === 'name') {
                $product['Article Name'] = $value;
            } else if($key === 'article_number') {
                $product['Article Number'] = $value;
            } else if($key === 'quantity') {
                $product['Quantity'] = $value;
            } else if($key === 'unit_of_measure') {
                $product['Unit of Measure'] = $value;
            } else if($key === 'price_gross_single') {
                $product['Item Price Gross'] = $value;
            } else if($key === 'price_net_single') {
                $product['Item Price Net'] = $value;
            } else if($key === 'currency') {
                $product['Currency'] = $value;
            } else if($key === 'tax_rate') {
                $product['Tax Rate'] = $value;
            } else if($key === 'description') {
                $product['Description'] = $value;
            }
        }
        $product_lists[] = $product;
    }
    return $product_lists;
}





// Require all inputs for a list field.
/*add_filter( 'gform_field_validation_8_99', 'validate_list_field', 10, 4 );
function validate_list_field( $result, $value, $form, $field )
{
    if ($field->label =='gr_document_articless_yes') {

        GFCommon::log_debug(__METHOD__ . '(): List Field: ' . print_r($value, true));

        foreach ($value as $row_values) {
            GFCommon::log_debug(__METHOD__ . '(): Row Value: ' . print_r($row_values, true));

            $column_1 = rgar($row_values, 'Type');
            GFCommon::log_debug(__METHOD__ . '(): Column 1: ' . print_r($column_1, true));

            $column_2 = rgar($row_values, 'Cost');
            GFCommon::log_debug(__METHOD__ . '(): Column 2: ' . print_r($column_2, true));

            $column_3 = rgar($row_values, 'Frequency');
            GFCommon::log_debug(__METHOD__ . '(): Column 3: ' . print_r($column_3, true));

            if (empty($column_1) || empty($column_2) || empty($column_3)) {
                $has_empty_input = true;
            }
        }

        if ($has_empty_input) {
            $result['is_valid'] = false;
            $result['message'] = 'All inputs are required!';
        }
    }

    return $result;
}*/
/*

add_filter( 'gform_pre_render_3', 'populate_list' );
add_filter( 'gform_pre_validation_3', 'populate_list' );
add_filter( 'gform_pre_submission_3', 'populate_list' );
add_filter( 'gform_admin_pre_render_3', 'populate_list' );
function populate_list( $form ) {
    foreach ( $form['fields'] as &$field ) {
        if ( $field->label =='gr_document_articless_yes'){
            $list_insertions[] = array('Value'=>'Value','Rating'=>'Rating');
        }
        $field->defaultValue = $list_insertions;
    }
    return $form;
}*/

/**
 * change column to custom element (exmaple textarea to list field)
 */
/*add_filter( 'gform_column_input_content_4_74_9', 'change_column3_content', 10, 6 );
function change_column3_content( $input, $input_info, $field, $text, $value, $form_id ) {
    // build field name, must match List field syntax to be processed correctly
    $input_field_name = 'input_' . $field->id . '[]';
    $new_input = '<textarea name="' . $input_field_name . '" class="textarea small" cols="2" rows="2">' . $value . '</textarea>';
    return $new_input;
}*/


//add_filter( 'gform_validation_4', 'client_validation' );
function client_validation( $validation_result ) {
    $form = $validation_result['form'];

    //supposing we don't want input 1 to be a value of 86
    if ( empty(rgpost( 'input_88' ))) {

        // set the form validation to false
        $validation_result['is_valid'] = false;

        //finding Field with ID of 1 and marking it as failed validation
        foreach( $form['fields'] as &$field ) {

            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == '41' ) {
                $field->failed_validation = true;
                $field->validation_message = 'This field is invalid!';
                break;
            }
        }

    }

    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;

}