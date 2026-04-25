<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
class ACF_Post_Updater{

    // can only come from both
    public function update_client( $data, $recipient_id, $client_id = false)
    {

        if(!$client_id) {
            $post_id = $data[79];
        } else {
            $post_id = $client_id;
        }

        //company name
        $field_key = "field_6448d03239ad6";
        $value = $data[15];
        update_field( $field_key, $value, $post_id );

        //customer agent
        $field_key = "field_6446a592e5ea4";
        $value = $data[23];
        update_field( $field_key, $value, $post_id );

        //client number
        $field_key = "field_64516b9d5f99c";
        $value = $data[84];
        update_field( $field_key, $value, $post_id );

        //phone number
        $field_key = "field_6446a5d2e5ea5";
        $value = $data[25];
        update_field( $field_key, $value, $post_id );

        //mail
        $field_key = "field_6446a5eae5ea6";
        $value = $data[26];
        update_field( $field_key, $value, $post_id );

        //uid number
        $field_key = "field_643fef8d43b7c";
        $value = $data[28];
        update_field( $field_key, $value, $post_id );

        //tax number
        $field_key = "field_643fefa843b7d";
        $value = $data[38];
        update_field( $field_key, $value, $post_id );

        //bank details
        $values = array(
            'clients_bank_details_bank_name'	=>	$data[42],
            'clients_account_owner'	=>	$data[45],
            'clients_bank_details_iban'	=>	$data[43],
            'clients_bank_details_bic'		=>	$data[44]

        );
        //Update the field using this array as value:
        update_field( 'field_643ff0a2b94f2', $values, $post_id );

        // company adress
        $values = array(
            'clients_company_country'	=>	$data[46],
            'clients_company_postal_code'	=>	$data[54],
            'clients_company_town'	=>	$data[48],
            'clients_company_street'		=>	$data[49],
            'clients_company_number'		=>	$data[50]

        );
        //Update the field using this array as value:
        update_field( 'field_643fede4adf43', $values, $post_id );

        //shipping adress
        $values = array(
            'clients_shipping_country'	=>	$data[55],
            'clients_shipping_postal_code'	=>	$data[47],
            'clients_shipping_town'	=>	$data[53],
            'clients_shipping_street'		=>	$data[52],
            'clients_shipping_house_number'		=>	$data[58]

        );
        //Update the field using this array as value:
        update_field( 'field_643feeffadf49', $values, $post_id );

        // add new id to the array
        $value[] = $recipient_id;
        // clients_recipients_relationship
        update_field('field_644008bc3d6d7', $value, $post_id);

        wp_update_post( array(
            'ID' => $post_id,
            'post_title' => $data[15],
            'post_name' => strtolower(str_replace(" ", "-", $data[15])),
        ));
    }
    // can only come from both
    public function update_document( $data, $recipient_id, $client_id, $document_id = false, $type = "Gravity")
    {
        if(!$document_id) {
            $post_id = $data[78];
        } else {
            $post_id = $document_id;
        }

        /*$value = get_field('documents_client_relationship', $post_id, false);
        // add new id to the array
        $value[] = $client_id;
        // update the field
        update_field('field_644006ce339df', $value, $post_id);*/

        // documents_client_relationship
        update_field('field_644006ce339df', $client_id, $post_id);

        // documents_document_type
        update_field('field_643dc1bb5df99', 4, $post_id  );

        //document path
        $field_key = "field_6442593a41953";
        $value = $data[76];
        update_field( $field_key, $value, $post_id );

        //json path
        $field_key = "field_644b80da63556";
        $value = $data[77];
        update_field( $field_key, $value, $post_id );

        if($type === 'Gravity') {
            // state
            update_field('field_64404047c5feb', 'Custom Edited', $post_id  );

            //Accuracy Status Percent
            $field_key = "field_6440427e1a986";
            $value = 0;

            //update_field( $field_key, $value, $post_id );
        }

        // document data
        $values = array(
            'documents_document_data_document_number'	=>	$data[17],
            'documents_document_data_document_date'	=>	$data[33],
            'documents_document_data_term_of_payment'   =>	$data[36],
        );
        //Update the field using this array as value:
        update_field( 'field_643ff85d9fa4e', $values, $post_id );


        $list_values = unserialize( rgar( $data, '74' ) );

        $product_lists = array();
        foreach ($list_values as $list) {
            $product = array();
            foreach ($list as $key => $value) {
                if($key === 'Article Name') {
                    $product['documents_article_items_article_name'] = $value;
                } else if($key === 'Article Number') {
                    $product['documents_article_items_article_number'] = $value;
                } else if($key === 'Quantity') {
                    $product['documents_article_items_quantity'] = $value;
                } else if($key === 'Unit of Measure') {
                    $product['documents_article_items_unit_of_measure'] = $value;
                } else if($key === 'Item Price Gross') {
                    $product['documents_article_items_price_gross'] = $value;
                } else if($key === 'Item Price Net') {
                    $product['documents_article_items_price_net_single'] = $value;
                } else if($key === 'Currency') {
                    $product['documents_article_items_currency'] = $value;
                } else if($key === 'Tax Rate') {
                    $product['documents_article_items_tax_rate'] = $value;
                } else if($key === 'Description') {
                    $product['documents_article_items_article_description'] = $value;
                }
            }
            $product_lists[] = $product;
        }

        // construct an array for the repeater value
        /*$value = array(
            // each row is a nested array
            array(
                // row 1
                // each row contains field key => value pairs for the fields
                //products name
                'documents_article_items_article_name' => 'value',
                //products number
                'documents_article_items_article_number' => 'value',
                //quantity
                'documents_article_items_quantity' => 21,
                //unit of measure
                'documents_article_items_unit_of_measure' => 'value',
                //item price gross
                'documents_article_items_price_gross' => 'value',
                //item price net
                'documents_article_items_price_net_single' => 'value',
                //currency
                'documents_article_items_currency' => 'value',
                //tax-rate
                'documents_article_items_tax_rate' => 'value',
                //description
                'documents_article_items_article_description' => 'value',
            ),
            array(
                // row 2
                //products name
                'documents_article_items_article_name' => 'value2',
                //products number
                'documents_article_items_article_number' => 'value2',
                //quantity
                'documents_article_items_quantity' => 221,
                //unit of measure
                'documents_article_items_unit_of_measure' => 'value2',
                //item price gross
                'documents_article_items_price_gross' => 'val2ue',
                //item price net
                'documents_article_items_price_net_single' => 'val2ue',
                //currency
                'documents_article_items_currency' => 'valu2e',
                //tax-rate
                'documents_article_items_tax_rate' => 'val2ue',
                //description
                'documents_article_items_article_description' => 'valu2e',
            ),
            // etc for each row
        );*/
        update_field( 'field_6447c34b10c07', $product_lists, $post_id );
    }
}