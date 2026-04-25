<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
class AI_Logger
{

    public static function debug($message, $logger = 'test')
    {

        if ($logger === 'test') {
            $post_log = get_stylesheet_directory() . '/logs/debugger-logs/bugs-report.txt';

        } else if ($logger === 'bug') {
            $post_log = get_stylesheet_directory() . '/logs/debugger-logs/testing-debugger.txt';

        } else if ($logger === 'ocr') {
            $post_log = get_stylesheet_directory() . '/logs/api-logs/ocr.txt';

        } else if ($logger === 'ai') {
            $post_log = get_stylesheet_directory() . '/logs/api-logs/document-ai.txt';
        }
//     	AI_Logger::debug('INITIALIZE -      4');
//		if(is_array($message)) {
//			$message = implode( ',', $message);
//			$message .= 11;
//		}

        if (file_exists($post_log)) {

            $file = fopen($post_log, 'a');
            fwrite($file, $message . "\n");

        } else {

            $file = fopen($post_log, 'w');
            fwrite($file, $message . "\n");

        }

        fclose($file);
    }

    public static function frontendDebug($message, $logger = 'test', $array = false)
    {
        /**
         * list styling
         * message
         * link
         * link text/url
         * icon
         */

        if ($logger === 'test') {

        } else if ($logger === 'error') {

        } else if ($logger === 'l-view') {

        } else if ($logger === 'd-view') {

        } else if ($logger === 'cron') {

        }

        if(!$array) {
            return $message;
        } else {
            ob_start();
                var_dump($message);
            return ob_get_clean();
        }
    }
}
