<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
/**
 * Functionality for our Document_AI
 */
if ( ! class_exists( 'Document_AI_Connector' ) ):
    class Document_AI_Connector {

        private static $authorizationGET = 'Authorization: Basic b2ZmaWNlQHBhcnR5Y3Jvd2QuYXQ6amVRUXBEdktCM3IoaG1GZjFGMmpLM1BX';

        private static $authorizationPOST = 'Authorization: Basic b2ZmaWNlQHBhcnR5Y3Jvd2QuYXQ6amVRUXBEdktCM3IoaG1GZjFGMmpLM1BX';

        private static $urlPOST = '';
        private static $urlGET = '';


        /**
         * POST Request
         */
        public static function post_document_to_api() {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::$urlPOST,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => 'content=gib%20dir%20meinen%20neuen%20Post%20gib%20dir%20meinen%20neuen%20Post%20gib%20dir%20meinen%20neuen%20Postgib%20dir%20meinen%20neuen%20Post&title=Mein%20Test%20Post%20beitrag2',
                CURLOPT_HTTPHEADER => array(
                    self::$authorizationPOST,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec( $curl );

            curl_close( $curl );

            return json_decode($response, true);
        }

        /**
         * GET Request
         */
        public static function get_document_from_api() {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::$urlPOST,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POSTFIELDS => 'content=gib%20dir%20meinen%20neuen%20Post%20gib%20dir%20meinen%20neuen%20Post%20gib%20dir%20meinen%20neuen%20Postgib%20dir%20meinen%20neuen%20Post&title=Mein%20Test%20Post%20beitrag2',
                CURLOPT_HTTPHEADER => array(
                    self::$authorizationGET,
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            ));

            $response = curl_exec( $curl );

            curl_close( $curl );

            return json_decode($response, true);
        }

        public static function document_ai_test_response_1() {
            $path = wp_upload_dir()['basedir']. '/document-uploader/response.json';

            // Read the JSON file
            $json = file_get_contents($path);

            // Decode the JSON file
            return json_decode($json,true);
        }

        public static function document_ai_test_response_2() {
            $path = wp_upload_dir()['basedir']. '/document-uploader/response-2.json';
            // Read the JSON file
            $json = file_get_contents($path);

            // Decode the JSON file
            return json_decode($json,true);
        }
    }

endif;
