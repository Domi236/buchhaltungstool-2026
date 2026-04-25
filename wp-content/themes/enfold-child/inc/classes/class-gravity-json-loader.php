<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
/**
 * Functionality for our Document_AI
 */
if ( ! class_exists( 'Gravity_Json_loader' ) ):
    class Gravity_Json_loader
    {
        public static $json;

        public static function get_json($json_file_name) {
            $file_path_archiv = wp_upload_dir()['basedir']. '/response-uploader/archive-uploader/';
            $json_file_name = $json_file_name ?? 'company-name-document-number.json';
            $json_content = file_get_contents($file_path_archiv . $json_file_name);
            $json_content = str_replace("\xEF\xBB\xBF",'',$json_content);
            $jsonDecode = json_decode(trim($json_content), TRUE);
            Gravity_Json_loader::$json = $jsonDecode;
            return $jsonDecode;
        }
    }

endif;