<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die;
}
/**
 * Get Data from Document, Client, Recipient Data from Database
 */
if (!class_exists('AI_Document_Uploader')) :
    class AI_Document_Uploader
    {
        private $ai_data;
        public static $file_path_temporary;
        public static $json_path_temporary;

        public static $file_path_archiv;
        public static $json_path_archiv;

        public function __construct($ai_data) {
            AI_Document_Uploader::$json_path_temporary = wp_upload_dir()['basedir']. '/response-uploader/temporary-uploader/';
            AI_Document_Uploader::$json_path_archiv = wp_upload_dir()['basedir']. '/response-uploader/archive-uploader/';
            AI_Document_Uploader::$file_path_temporary = wp_upload_dir()['basedir']. '/document-uploader/temporary-uploader/';
            AI_Document_Uploader::$file_path_archiv = wp_upload_dir()['basedir']. '/document-uploader/archive-uploader/';
            $this->ai_data = $ai_data;

            if(!$this->ai_data) {

            }
        }
        private function generate_json_from_response($data, $response) : string
        {
            /**
             * we need only the company number and the document number
             * from the json, then we save the response in a json file
             */
            //return file_put_contents($data, json_encode($response));
            return 12;
        }


        /**
         * @param $file_path
         * @param $ai_data
         * @return string
         * happens after cronjob send the data back (step 1)
         * collect_the_needed_data and send them to the right locations in 2 simultan Steps (client; Recipient)
         * then document for the right routing and then, removing, saving the document in the right directory
         */
        public function collect_the_needed_data()
        {
            $ai_data_viewer = new Document_AI_Connector();
            return $ai_data_viewer->document_ai_test_response_1();
        }

        /**
         * @param $file_path
         * @param $ai_data
         * @return string
         * check is client already set up in the database, then link them, else create new (step 2.1)
         */
        private function check_client_data($file_path, $ai_data) : string
        {
            return $this->file_path_archiv = '';
        }

        /**
         * @param $file_path
         * @param $ai_data
         * @return string
         * check is recipient already set up in the database, then link them, else create new (step 2.2)
         */
        private function check_recipient_data($file_path, $ai_data) : string
        {
            return $this->file_path_archiv = '';
        }

        /**
         * @param $file_path
         * @param $ai_data
         * @return string
         * check is recipient already set up in the database, then send the user the log message, document already exists with link, else create new (step 2.2)
         */
        private function check_document_data($file_path, $ai_data) : string
        {
           return $this->file_path_archiv = '';
        }


        /**
         * @param $file_path
         * @return string
         * happens after cronjob send the data back (step 2)
         */
        private function upload_files_in_the_right_directory($file_name, $json_name) : string
        {
            copy(AI_Document_Uploader::$json_path_temporary . $json_name, AI_Document_Uploader::$json_path_archiv . $json_name);
            copy(AI_Document_Uploader::$file_path_temporary .$file_name, AI_Document_Uploader::$file_path_archiv .$file_name);
            return 12;
        }
    }
endif;