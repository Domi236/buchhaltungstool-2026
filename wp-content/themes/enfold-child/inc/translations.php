<?php
/*
add_filter('gettext', 'pc_theme_change_strings', 10, 3);
function pc_theme_change_strings($translated, $text, $domain)
{
    /**
     * Gravity forms strings
     */
    /*if ($domain === 'gravityforms') {

        if ('Accepted file types: %s' === $text) {
            $translated = 'Unterstützte Dateiformate: %s (Maximal';
        }

        if ('Max. file size: %s' === $text) {
            $translated = __('Max. Datei Größe: %s', 'party_crowd');
        }

        if ('Max. files: %s' === $text) {
            $translated = __('Max. Dateien Anzahl: %s', 'party_crowd');
        }

        if ('max characters' === $text) {
            $translated = __('max. Zeichen', 'party_crowd');
        }

        if ('of' === $text) {
            $translated = __('von', 'party_crowd');
        }

        if ('Select files' === $text) {
            $translated = __('Datei auswählen', 'party_crowd');
        }

        if ('There was a problem with your submission.' === $text) {
            $translated = __('Es gab ein Problem mit deiner Eingabe.', 'party_crowd');
        }

        if ('Please review the fields below.' === $text) {
            $translated = __('Bitte prüfe die Felder unten.', 'party_crowd');
        }

        if ( 'This field is required.' === $text ) {
            $translated = __( 'Pflichtfeld', 'party_crowd' );
        }

        if ( 'Drop files here or' === $text ) {
            $translated = __( 'Datei auswählen oder hierher ziehen', 'party_crowd' );
        }
        
        if ('There was a problem with your submission.' === $text ) {
            $translated = __( 'Es gab ein Problem mit deiner Eingabe.', 'party_crowd' );
        }

        if ('Please review the fields below.' === $text ) {
            $translated = __( 'Bitte prüfe die Felder unten.', 'party_crowd' );
        }
    }

    /**
     * Facet
     */
    /*if ( $domain === 'fwp-front' ) {
        if ( 'No results found' === $text ) {
            $translated = __( 'Keine Ergebnisse gefunden', 'party_crowd' );
        }
    }

    return $translated;
}*/

/**
 * Gravity Perks // Blocklist // Change the Validation Message
 * https://gravitywiz.com/documentation/gravity-forms-blocklist/
 */
/*add_filter( 'gpb_validation_message', function( $validation_message ) {
    return 'Dieser Text wird vom Spamfilter des Formulars nicht erlaubt.';
});
*/