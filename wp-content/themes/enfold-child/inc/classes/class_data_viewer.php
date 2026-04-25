<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
/**
 * Get Data from Document, Client, Recipient Data from Database
 */
if ( ! class_exists( 'AI_Data_Viewer' ) ) :
    class AI_Data_Viewer {


        /**
         * get document data from database by ID
         ** trim all data before adding to database
         */
        public function get_document_data_from_document($document_ID, $only_minimal_data = false): array
        {

            if( have_rows('documents_document_data',$document_ID)):
                while( have_rows('documents_document_data',$document_ID) ): the_row();
                    $document_number = get_sub_field('documents_document_data_document_number') ?? 'Missing';
                    $client_number = get_sub_field('documents_document_data_client_number');
                    $document_date = get_sub_field('documents_document_data_document_date');
                    $term_of_payment = get_sub_field('documents_document_data_term_of_payment');
                endwhile;
            endif;

            $document_data = array(
                'document_state' => get_field('documents_status', $document_ID),
                'company_name' => get_field('documents_company_name', $document_ID) ?? 'Missing',
                'document_path' => get_field('documents_path', $document_ID),
                'json_path' => get_field('documents_json_path', $document_ID),
                'document_type' => get_field('documents_document_type', $document_ID),
                'document_link' => get_permalink($document_ID),
                'document_number' => $document_number ?? '',
            );

             if(!$only_minimal_data)  {
                 $document_data_specific = array(
                     'client_number' => $client_number ?? '',
                     'document_date' => $document_date ?? '',
                     'term_of_payment' => $term_of_payment ?? '',
                 );
                 $document_data = array_merge($document_data,$document_data_specific);
             }

            return $document_data;
        }


        /**
         * get articles from database by ID
         * * trim all data before adding to database

         */
        public function get_document_products_from_document($document_ID): array
        {

            $products_list = array();
            if( have_rows('documents_article_items', $document_ID) ):
                while( have_rows('documents_article_items',$document_ID) ): the_row();
                    $product = [];
                    $product['name'] = get_sub_field('documents_article_items_article_name');
                    $product['article_number'] = get_sub_field('documents_article_items_article_number');
                    $product['quantity'] = get_sub_field('documents_article_items_quantity');
                    $product['unit_of_measure'] = get_sub_field('documents_article_items_unit_of_measure');
                    $product['price_gross_single'] = get_sub_field('documents_article_items_price_gross');
                    $product['price_net_single'] = get_sub_field('documents_article_items_price_net_single');
                    $product['currency'] = get_sub_field('documents_article_items_currency');
                    $product['tax_rate'] = get_sub_field('documents_article_items_tax_rate');
                    $product['description'] = get_sub_field('documents_article_items_article_description');
                    $products_list[] = $product;
                endwhile;
            endif;

            return $products_list;
        }


        /**
         * get client data from database by ID
         ** trim all data before adding to database
         */
        public function get_client_from_document($document_ID): array
        {

            $client_data = get_field('documents_client_relationship', $document_ID);
            if( $client_data ):
                foreach( $client_data as $client_data_ID ):
                    $client_ID = $client_data_ID;
                    $company_name = get_field( 'clients_company_name', $client_data_ID);
                    $customer_agent = get_field( 'clients_customer_agent', $client_data_ID);
                    $clients_tax_number = get_field('clients_tax_number',$client_data_ID);
                    $clients_uid_number = get_field('clients_uid_number',$client_data_ID);
                    $clients_phone_number = get_field('clients_phone_number',$client_data_ID);
                    $clients_clients_mail = get_field('clients_mail',$client_data_ID);

                    if( have_rows('clients_bank_details', $client_data_ID) ):
                        while( have_rows('clients_bank_details', $client_data_ID) ): the_row();
                            $clients_bank_details_bank_name = get_sub_field('clients_bank_details_bank_name');
                            $clients_account_owner = get_sub_field('clients_account_owner');
                            $clients_bank_details_iban = get_sub_field('clients_bank_details_iban');
                            $clients_bank_details_bic = get_sub_field('clients_bank_details_bic');
                        endwhile;
                    endif;

                    if( have_rows('clients_company_adress', $client_data_ID) ):
                        while( have_rows('clients_company_adress', $client_data_ID) ): the_row();
                            $clients_company_country = get_sub_field('clients_company_country');
                            $clients_company_postal_code = get_sub_field('clients_company_postal_code');
                            $clients_company_town = get_sub_field('clients_company_town');
                            $clients_company_street = get_sub_field('clients_company_street');
                            $clients_company_number = get_sub_field('clients_company_number');
                        endwhile;
                    endif;

                    /**
                     * add checkbox backend for setting clients_shipping_adress to the same adress as company adress
                     */

                    if( have_rows('clients_shipping_adress', $client_data_ID) ):
                        while( have_rows('clients_shipping_adress', $client_data_ID) ): the_row();
                            $clients_shipping_country = get_sub_field('clients_shipping_country');
                            $clients_shipping_postal_code = get_sub_field('clients_shipping_postal_code');
                            $clients_shipping_town = get_sub_field('clients_shipping_town');
                            $clients_shipping_street = get_sub_field('clients_shipping_street');
                            $clients_shipping_house_number = get_sub_field('clients_shipping_house_number');
                        endwhile;
                    endif;
                endforeach;
            endif;

            return array(
                'ID' => $client_ID ?? '',
                'company_name' => $company_name ?? '',
                'customer_agent' => $customer_agent ?? '',
                'client_number' => $client_number ?? '',
                'clients_tax_number' => $clients_tax_number ?? '',
                'clients_phone_number' => $clients_phone_number ?? '',
                'clients_uid_number' => $clients_uid_number ?? '',
                'clients_mail' => $clients_clients_mail ?? '',
                //bank details
                'clients_bank_details_bank_name' => $clients_bank_details_bank_name ?? '',
                'clients_account_owner' => $clients_account_owner ?? '',
                'clients_bank_details_iban' => $clients_bank_details_iban ?? '',
                'clients_bank_details_bic' => $clients_bank_details_bic ?? '',
                //company Adress
                'clients_company_country' => $clients_company_country ?? '',
                'clients_company_postal_code' => $clients_company_postal_code ?? '',
                'clients_company_town' => $clients_company_town ?? '',
                'clients_company_street' => $clients_company_street ?? '',
                'clients_company_number' => $clients_company_number ?? '',
                //shipping Adress
                'clients_shipping_country' => $clients_shipping_country ?? '',
                'clients_shipping_postal_code' => $clients_shipping_postal_code ?? '',
                'clients_shipping_town' => $clients_shipping_town ?? '',
                'clients_shipping_street' => $clients_shipping_street ?? '',
                'clients_shipping_house_number' => $clients_shipping_house_number ?? ''
            );
        }
    }
endif;
