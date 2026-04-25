import './assets/fontawesome';
import Swiper, {Navigation, Pagination, Autoplay, EffectFade} from 'swiper';

jQuery(document).ready(function ($) {
    // now you can use jQuery code here with $ shortcut formatting
    // this will execute after the document is fully loaded
    // anything that interacts with your html should go here


    /**
     * Gravity Wiz // Gravity Forms // Disable Submission when Pressing Enter
     * https://gravitywiz.com/disable-submission-when-pressing-enter-for-gravity-forms/
     *
     * Instructions:
     * 1. Install our free Custom Javascript for Gravity Forms plugin.
     * Download the plugin here: https://gravitywiz.com/gravity-forms-custom-javascript/
     * 2. Copy and paste the snippet into the editor of the Custom Javascript for Gravity Forms plugin.
     *
     * IMPORTANT TO STOP RELOAD ON TYPING ENTER
     */
    jQuery(document).on( 'keypress', '.gform_wrapper', function (e) {
        var code = e.keyCode || e.which;
        if ( code == 13 && ! jQuery( e.target ).is( 'textarea,input[type="submit"],input[type="button"]' ) ) {
            e.preventDefault();
            return false;
        }
    } );


    /**
     * Wrap our JS for capturing values
     * for Choose Client Dropdown
     */
    var selectClient = {

        // Setup & Event listener
        // ----------------------
        init: function() {

            var select = jQuery('#input_4_41')[0]; //change id to match your form
            if(typeof select === 'undefined' || !select) {
                return;
            }

            //Set Initial Value (before change)
            selectClient.setEmail.apply( {value: select.value} );

            jQuery(select).on('change', this.setEmail);
        },

        // Set text (or hidden) field to option value
        // (change selector to match your form)
        // ------------------------------------
        setEmail: function(ev) {
            jQuery('#input_4_88')[0].value = this.value;
        }
    };
    jQuery().ready( selectClient.init() );


    /**
     * Wrap our JS for capturing values
     * for Choose Recipient Dropdown
     */
    var selectRecipient = {

        // Setup & Event listener
        // ----------------------
        init: function() {

            var select = jQuery('#input_4_67')[0]; //change id to match your form
            if(typeof select === 'undefined' || !select) {
                return;
            }

            //Set Initial Value (before change)
            selectRecipient.setEmail.apply( {value: select.value} );

            jQuery(select).on('change', this.setEmail);
        },

        // Set text (or hidden) field to option value
        // (change selector to match your form)
        // ------------------------------------
        setEmail: function(ev) {
            jQuery('#input_4_89')[0].value = this.value;
        }
    };
    jQuery().ready( selectRecipient.init() );
    

    /**
     * FacetWP Infinite Scroll in a very simple way
     */

    $(document).one('facetwp-loaded', function () {
        fwpInfiniteScroll();
    });

    function fwpInfiniteScroll() {
        var threshold = 3100; // loading threshold in pixels
        var intervall = 1000; // how often the script checks distances

        var scrollInterval = setInterval(() => {
            var $loadmore = $('.facetwp-facet-load_more_infinite button.facetwp-load-more');
            var distance = ($loadmore.offset().top - $(window).scrollTop());

            if (distance <= threshold
                && (!$loadmore.hasClass("loading"))
                && (!$loadmore.hasClass("facetwp-hidden"))
            ) {
                $loadmore.addClass('loading').click();
            }

            /*if (distance < 0) {
              clearInterval(scrollInterval); // stop running when there is no button anymore (not working well with filters)
            }*/
        }, intervall);
    }

    /*$("#labeling-view-form .acf-field .acf-label").each(function () {
        $(this).append(
            "<div class='checkbox_acf_frontend_item'>" +
            "<span class='check single-check'><i class='fa-solid fa-square-check'></i></span>" +
            "<span class='uncheck single-uncheck'></span>" +
            " Accuracy</div>"
        );
    })*/

    /**
     * get acf field data
     * @param type string
     * @returns array
     */
    function get_field_data(type = 'document_data') {
        if(type === 'document_data') {
            return [
                'documents_document_type',
                'documents_company_name',
                'documents_document_data_document_number',
                'documents_document_data_client_number',
                'documents_document_data_document_date',
                'documents_document_data_term_of_payment',
                'documents_document_data_term_of_delivery',
            ]
        } else if(type === 'products_data') {
            return [ 'documents_article_items']

        }  else if(type === 'client_data') {
            return [
                'clients_company_name',
                'clients_customer_agent',
                'clients_phone_number',
                'clients_mail',
                'clients_uid_number',
                'clients_tax_number',

                /**bank**/
                'bank_details',
                'clients_bank_details_bank_name',
                'clients_account_owner',
                'clients_bank_details_iban',
                'clients_bank_details_bic',

                /**company adress*/
                'bank_details_copy2',
                'clients_company_country',
                'clients_company_postal_code',
                'clients_company_town',
                'clients_company_street',
                'clients_company_number',

                /**shipping adress**/
                'bank_details_copy',
                'clients_shipping_country',
                'clients_shipping_postal_code',
                'clients_shipping_town',
                'clients_shipping_street',
                'clients_shipping_house_number',
            ]

        } else if(type === 'recipient_data') {
            return [
                'recipients_company_name',
                'recipients_uid_number',
                'recipients_tax_number',

                /**company adress*/
                'company_adress_recipient',
                'recipients_company_country',
                'recipients_company_postal_code',
                'recipients_company_town',
                'recipients_company_street',
                'recipients_company_house_number',

                /**shipping adress**/
                'shipping_adress_recipient',
                'recipients_recipient_country',
                'recipients_recipient_postal_code',
                'recipients_recipient_town',
                'recipients_recipient_street',
                'recipients_recipient_house_number',
            ]
        }
    }

    /**
     * labeling view
     * */
    window.addEventListener('load', () => {

        let active_labeling_view_mode = 'edit';

        const sidebar_header_btns = document.getElementsByClassName('labeling_view__sidebar_header_btn');
        if(sidebar_header_btns !== null) {
            const acf_fields = document.getElementsByClassName('acf-field');
            const document_data_btn = document.getElementById('document_values');
            display_fields_per_view(get_field_data("document_data"), document_data_btn, acf_fields);


            [...sidebar_header_btns].map((item, index, array) => {
                item.addEventListener('click', (ev) => {

                    /**
                     * loop throw btns to remove the btn active classes
                     * before the one will set as btn active
                     */
                    array.map((item_2) => {
                        item_2.classList.remove('btn_active');
                    });

                    /**
                     * loop throw acf fields to remove the active display classes
                     * before they will set as display active
                     */
                    [...acf_fields].map((acf_field_item) => {
                        acf_field_item.classList.remove('acf-active-display-view');
                    })

                    /**
                     * add btn active
                     */
                    let btn = ev.target;
                    btn.classList.add('btn_active');

                    /**
                     * check which btn is similiar with the fields
                     * and sets the active classes to that fields
                     */
                    if(btn.classList.contains('document_values')) {
                        display_fields_per_view(get_field_data("document_data"), btn, acf_fields);
                    } else if(btn.classList.contains('product_values')) {
                        display_fields_per_view(get_field_data("products_data"), btn, acf_fields);
                    } else if(btn.classList.contains('client_values')) {
                        display_fields_per_view(get_field_data("client_data"), btn, acf_fields);
                    } else if(btn.classList.contains('recipient_values')) {
                        display_fields_per_view(get_field_data("recipient_data"), btn, acf_fields);
                    }
                })
            })

            /**
             * Labeling View (Checkboxes Handling)
             */
            const checkbox_all = document.getElementById('choose-all-checkboxes');

            const checkbox_singles = [...document.getElementsByClassName('single-check')];
            [...acf_fields].map((single_field, index) => {
                //console.log(single_field)
                let current_index = index;
                single_field.addEventListener('click', (ev) => check_boxes('single', checkbox_singles, current_index, false, ev))
            })

         //   checkbox_all.addEventListener('click', () => check_boxes('all', checkbox_singles, 0, checkbox_all))

            /**
             * modi switch
             */
            const mode_btns = document.getElementsByClassName('labeling_view__sidebar__mode_rectangle_btn');
            const modi_displays = document.getElementsByClassName('modi_display');

            const labeling_view_btn = document.getElementById('labeling_view__sidebar__labeling_mode_rectangle_btn');
            const labeling_view_btn_display = document.getElementById('labeling-data-mode-display');
            mode_btns_functionality(labeling_view_btn, labeling_view_btn_display, mode_btns, modi_displays, active_labeling_view_mode, 'labeling')

            const edit_view_btn = document.getElementById('labeling_view__sidebar__edit_mode_rectangle_btn');
            const edit_view_btn_display = document.getElementById('edit-data-mode-display');
            mode_btns_functionality(edit_view_btn, edit_view_btn_display, mode_btns, modi_displays, active_labeling_view_mode, 'edit')

            const trash_view_btn = document.getElementById('labeling_view__sidebar__trash_mode_rectangle_btn');
            const trash_view_btn_display = document.getElementById('trash-data-mode-display');
            mode_btns_functionality(trash_view_btn, trash_view_btn_display, mode_btns, modi_displays, active_labeling_view_mode, 'delete', acf_fields, checkbox_singles, checkbox_all)
        }
    })

    /**
     * view btn and display handler (modi switcher)
     * @param view_btn
     * @param view_btn_display
     * @param mode_btns
     * @param modi_displays
     * @param active_labeling_view_mode
     * @param state
     * @param acf_fields
     * @param checkbox_singles
     * @param check_all_btn
     */
    function mode_btns_functionality(view_btn, view_btn_display, mode_btns, modi_displays, active_labeling_view_mode, state, acf_fields = false, checkbox_singles, check_all_btn) {
        view_btn.addEventListener('click', () => {
            active_labeling_view_mode = state; //switch modi state

            [...mode_btns].map((mode_btn) => {
                mode_btn.classList.remove('active');
            });

            [...modi_displays].map((modi_display) => {
                modi_display.classList.remove('active');
            });

            view_btn.classList.add('active');
            view_btn_display.classList.add('active');

            if(active_labeling_view_mode === 'delete') {
                //delete_all_inputs_and_polygons_which_checked(acf_fields, checkbox_singles, check_all_btn)
            }
        });
    }


    /**
     * labeling view fields set
     * array, dom element
     * */
    function display_fields_per_view(data_fields, btn, acf_fields) {
        [...acf_fields].map((acf_field) => {
            if(data_fields.indexOf(acf_field.getAttribute('data-name')) > -1) {
                acf_field.classList.add('acf-active-display-view');
            }
        })
    }

    /**
     * only for all and single check/uncheck
     */
    function check_boxes(type, checkbox_singles, current_index = 0, checkbox_btn = false, ev = false) {
        let checker_all = false;
        checkbox_singles.map((item, index2) => {

            if(type === 'all') {
                if(index2 === 0) {
                    for (let i = 0; i < checkbox_singles.length; i++) {
                        if (checkbox_singles[i].classList.contains('active')) {
                            checker_all = true;
                            break;
                        }
                    }
                }
                if(!checker_all) {
                    if(type !== 'category') {
                        checkbox_btn.children[0].classList.add('active');
                        item.classList.add('active');
                    }
                } else {
                    item.classList.remove('active');
                    checkbox_btn.children[0].classList.remove('active');
                }
            } else if(type === 'single') {
                if(current_index === index2) {
                    if(ev.target.getAttribute('type') !== 'text'
                        && ev.target.getAttribute('type') !== 'number'
                        && ev.target.getAttribute('rows') !== '8'
                        && ev.target.getAttribute('type') !== 'radio'
                        && ev.target.tagName.toLowerCase() !== 'span'
                    ) {
                        item.classList.toggle('active');
                    }
                }
            }
        })
    }

    function delete_all_inputs_and_polygons_which_checked(acf_fields, checkbox_singles, check_all_btn) {
        [...acf_fields].map((field, index, array) => {
            /**
             * choose all fields which are checked
             * also ai labeled things
             * delete all polygons from fields
             * delete all inputs from acf fields
             */
            /**
             * here only deleting the inputs (TODO: polygons delete upcoming)
             * here only deleting the inputs (TODO: datepicker Inputs deleting not work)
             * here only deleting the inputs (TODO: deleting only works 1 time | fix by clicking on delete, all entries should be placed as value-before delete value)
             */
            if(field.classList.contains('acf-field-text') ||
                field.classList.contains('acf-field-number') ||
                field.classList.contains('acf-field-date-picker')
            ) {
                // acf-field acf-label checkbox_acf_frontend_item single-check
                let label = field.children[0].children[1].children[0];
                // acf-field acf-input acf-input-wrap input
                let input = field.children[1].children[0].children[0];
                if(input !== null && label.classList.contains('active')) {
                    input.setAttribute('value', '');
                    label.classList.remove('active')
                }

            } else if (field.classList.contains('acf-field-taxonomy')) {

                // acf-field acf-label checkbox_acf_frontend_item single-check
                let label = field.children[0].children[1].children[0];
                // acf-field searching for label fields
                if(label.classList.contains('active')) {
                    label.classList.remove('active');

                    let labels = field.querySelectorAll('label');

                    for (let i = 0; i < labels.length; i++) {
                        if (labels[i].classList.contains('selected')) {
                            labels[i].classList.remove('selected');
                            labels[i].children[0].removeAttribute('checked');
                            break;
                        }
                    }
                }
            }
            if(index === (array.length - 1) ) {
                check_all_btn.children[0].classList.remove('active');
            }
        })
    }
});
