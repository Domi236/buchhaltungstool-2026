<?php

/**
 * @param $field
 * @param $accuracy
 * @param $coordinates
 * @param $content
 * @param $optional_display
 * @return array|string|string[]
 * generate html for the label fields of labeling view
 */
function add_additional_content_to_label_field($field, $content, $optional_display = false) {
    $addional_field = "";
    if($optional_display) {
        $addional_field = '<span class="gfield_required"><span class="gfield_required gfield_required_text">('. $optional_display .')</span></span>';
    }
    return str_replace  (
        $field->label, "<span class='default-label'>" .$field->label."</span>
        <label for='check_document_number' class='checkbox_accuracy'>
            <input class='check single-check' type='checkbox' id='check_document_number' name='check_document_number' value='check'> 
                <span class='accuracy-display'></span>
            </label>
            <a href='#input' class='focus-rectangle'>
                <label for='focus_check_document_number'>
                    <input class='focus_check' type='radio' id='focus_check_document_number' name='focus_check_document_number' value='' disabled>  <i class='fa-regular fa-camera-viewfinder'></i>
                </label>
            </a>" . $addional_field , $content );
}

/**
 * @param $accuracy
 * @return string
 * get_accuracy
 */

/**
 * @param $json_coordinates
 * @return string
 */



/**
 *
 *
 * label_standard_elements
 * like document number or date
 * REQUIRED
 */
//DOCUMENT DATA
//document_number
add_filter( 'gform_field_content_4_17', 'label_standard_elements', 10, 5 );
//document_type
add_filter( 'gform_field_content_4_91', 'label_standard_elements', 10, 5 );
//document_date
add_filter( 'gform_field_content_4_33', 'label_standard_elements', 10, 5 );
//term_of_payment
add_filter( 'gform_field_content_4_36', 'label_standard_elements', 10, 5 );

//CLIENT DATA
//client_company_name
add_filter( 'gform_field_content_4_15', 'label_standard_elements', 10, 5 );
//client_number
add_filter( 'gform_field_content_4_92', 'label_standard_elements', 10, 5 );
//customer_agent
add_filter( 'gform_field_content_4_23', 'label_standard_elements', 10, 5 );
//client_phone_number
add_filter( 'gform_field_content_4_25', 'label_standard_elements', 10, 5 );
//client_mail
add_filter( 'gform_field_content_4_26', 'label_standard_elements', 10, 5 );
//client_uid_number
add_filter( 'gform_field_content_4_28', 'label_standard_elements', 10, 5 );
//client_tax_number
add_filter( 'gform_field_content_4_38', 'label_standard_elements', 10, 5 );
//bankdetails
//bank_details_bank_name
add_filter( 'gform_field_content_4_42', 'label_standard_elements', 10, 5 );
//account_owner
add_filter( 'gform_field_content_4_45', 'label_standard_elements', 10, 5 );
//bank_details_iban
add_filter( 'gform_field_content_4_43', 'label_standard_elements', 10, 5 );
//bank_details_bic
add_filter( 'gform_field_content_4_44', 'label_standard_elements', 10, 5 );
//company_adress
//company_country
add_filter( 'gform_field_content_4_46', 'label_standard_elements', 10, 5 );
//company_postal_code
add_filter( 'gform_field_content_4_54', 'label_standard_elements', 10, 5 );
//company_town
add_filter( 'gform_field_content_4_48', 'label_standard_elements', 10, 5 );
//company_street
add_filter( 'gform_field_content_4_49', 'label_standard_elements', 10, 5 );
//company_number
add_filter( 'gform_field_content_4_50', 'label_standard_elements', 10, 5 );
//shipping adress
//shipping_country
add_filter( 'gform_field_content_4_55', 'label_standard_elements', 10, 5 );
//shipping_postal_code
add_filter( 'gform_field_content_4_47', 'label_standard_elements', 10, 5 );
//shipping_town
add_filter( 'gform_field_content_4_53', 'label_standard_elements', 10, 5 );
//shipping_street
add_filter( 'gform_field_content_4_52', 'label_standard_elements', 10, 5 );
//shipping_number
add_filter( 'gform_field_content_4_58', 'label_standard_elements', 10, 5 );

//recipient_data
//tax_number
add_filter( 'gform_field_content_4_72', 'label_standard_elements', 10, 5 );
//uid_number
add_filter( 'gform_field_content_4_71', 'label_standard_elements', 10, 5 );
//company_adress
//company_country
add_filter( 'gform_field_content_4_56', 'label_standard_elements', 10, 5 );
//company_postal_code
add_filter( 'gform_field_content_4_64', 'label_standard_elements', 10, 5 );
//company_town
add_filter( 'gform_field_content_4_63', 'label_standard_elements', 10, 5 );
//company_street
add_filter( 'gform_field_content_4_60', 'label_standard_elements', 10, 5 );
//company_number
add_filter( 'gform_field_content_4_51', 'label_standard_elements', 10, 5 );
//shipping adress
//shipping_country
add_filter( 'gform_field_content_4_57', 'label_standard_elements', 10, 5 );
//shipping_postal_code
add_filter( 'gform_field_content_4_65', 'label_standard_elements', 10, 5 );
//shipping_town
add_filter( 'gform_field_content_4_62', 'label_standard_elements', 10, 5 );
//shipping_street
add_filter( 'gform_field_content_4_61', 'label_standard_elements', 10, 5 );
//shipping_number
add_filter( 'gform_field_content_4_59', 'label_standard_elements', 10, 5 );

/**
 * label_standard_elements
 * like document uid or tax number
 * OPTIONAL
 */

function label_standard_elements( $content, $field, $value, $lead_id, $form_id ) {
    /**
     * aufpassen, dass die value nicht auch heißt wie das Label sonst passieren fehler
     * call json only one time and set it in the class
     */

    if(is_admin()) {
        //!IMPORTANT else admin view is broken
        return $content;
    }


    $default = false;
    $client_data = false;
    $optional = 'Optional';
    /**
     * required fields
     */
    //document data
    if($field->id === 17) {
      $field_name = 'document_number';
        $optional = false;
    } else if ($field->id === 91) {
      $field_name = 'document_type';
      $optional = 'Required';
      $default = true;
    } else if ($field->id === 33) {
      $field_name = 'document_date';
        $optional = false;
        //client data
    } else if ($field->id === 15) {
        $client_data = true;
        $field_name = 'company_name';
        $optional = false;
        //recipient data

    /**
     * optional fields
     */
    } else if ($field->id === 36) {
      $field_name = 'term_of_payment';
    //client data
    } else if ($field->id === 92) {
        $client_data = true;
        $field_name = 'client_number';
    } else if ($field->id === 23) {
        $client_data = true;
        $field_name = 'customer_agent';
    } else if ($field->id === 25) {
        $client_data = true;
        $field_name = 'phone_number';
    } else if ($field->id === 26) {
        $client_data = true;
        $field_name = 'mail';
    } else if ($field->id === 28) {
        $client_data = true;
        $field_name = 'uid_number';
    } else if ($field->id === 38) {
        $client_data = true;
        $field_name = 'tax_number';
    //bank data
    } else if ($field->id === 42) {
        $client_data = true;
        $field_name = 'bank_details_bank_name';
    } else if ($field->id === 45) {
        $client_data = true;
        $field_name = 'account_owner';
    } else if ($field->id === 43) {
        $client_data = true;
        $field_name = 'bank_details_iban';
    } else if ($field->id === 44) {
        $client_data = true;
        $field_name = 'bank_details_bic';
    //company adress data
    } else if ($field->id === 46) {
        $client_data = true;
        $field_name = 'company_country';
    } else if ($field->id === 54) {
        $client_data = true;
        $field_name = 'company_postal_code';
    } else if ($field->id === 48) {
        $client_data = true;
        $field_name = 'company_town';
    } else if ($field->id === 49) {
        $client_data = true;
        $field_name = 'company_street';
    } else if ($field->id === 50) {
        $client_data = true;
        $field_name = 'company_number';
    //shipping adress data
    } else if ($field->id === 55) {
        $client_data = true;
        $field_name = 'shipping_country';
    } else if ($field->id === 47) {
        $client_data = true;
        $field_name = 'shipping_postal_code';
    } else if ($field->id === 53) {
        $client_data = true;
        $field_name = 'shipping_town';
    } else if ($field->id === 52) {
        $client_data = true;
        $field_name = 'shipping_street';
    } else if ($field->id === 58) {
        $client_data = true;
        $field_name = 'shipping_number';
    }

    /**
     * optional fields
     */


    //set accuracy and coordinates to labeling view items
    return add_additional_content_to_label_field($field, $content, $optional);

}

