<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

class Class_Tax_Engine {
    public static function calculate_year( $year ) {
        $empty_u1 = [
            'kennzahl_000' => 0, 'kennzahl_020' => 0, 'kennzahl_022' => 0, 'kennzahl_029' => 0,
            'kennzahl_006' => 0, 'kennzahl_037' => 0, 'kennzahl_052' => 0, 'kennzahl_007' => 0,
            'kennzahl_070' => 0, 'kennzahl_071' => 0, 'kennzahl_072' => 0, 'kennzahl_073' => 0,
            'kennzahl_008' => 0, 'kennzahl_088' => 0, 'kennzahl_060' => 0, 'kennzahl_061' => 0,
            'kennzahl_062' => 0, 'ust_total' => 0
        ];

        $result = [
            'u1' => $empty_u1,
            'e1' => [],
            'periods' => [
                'months'   => array_fill( 1, 12, $empty_u1 ),
                'quarters' => array_fill( 1, 4, $empty_u1 )
            ],
            'debug_count' => 0, 'debug_total_docs' => 0, 'document_details' => [],
            'stammdaten' => [
                'informationen'  => '',
                'fehlerliste'    => [],
                'systemfehler'   => [],
                'ear'            => 'Nein',
                'netto_system'   => 'Nein',
                'gehaltsstellen' => '0',
                'beruf'          => 'Nicht definiert',
                'e1_320'         => '0,00',
                'u1_000'         => '0,00'
            ]
        ];

        // 1. STAMMDATEN VON DER ACF OPTIONS-SEITE LADEN
        if ( have_rows( 'view_jahre', 'option' ) ) {
            while ( have_rows( 'view_jahre', 'option' ) ) {
                the_row();
                $row_year = get_sub_field( 'jahres_daten' );

                if ( (string) $row_year === (string) $year ) {
                    $result['stammdaten']['informationen'] = get_sub_field( 'infos_box' ) ?: '';
                    $result['stammdaten']['ear']           = get_sub_field( 'einnahmen-ausgaben-rechnung' ) ? 'Ja' : 'Nein';
                    $result['stammdaten']['netto_system']  = get_sub_field( 'ust-bruttosystem' ) ? 'Ja' : 'Nein';

                    $gehaltsstellen = get_sub_field( 'gehaltsstellen' );
                    $result['stammdaten']['gehaltsstellen'] = ( $gehaltsstellen !== '' && $gehaltsstellen !== null ) ? $gehaltsstellen : '0';

                    $result['stammdaten']['beruf']   = get_sub_field( 'berufliche_tatigkeit' ) ?: 'Nicht definiert';
                    $result['stammdaten']['e1_320']  = get_sub_field( 'summe_e1' ) ?: '0,00';
                    $result['stammdaten']['u1_000']  = get_sub_field( 'lieferungen_sonstige_leistungen_und_eigenverbrauch' ) ?: '0,00';

                    if ( have_rows( 'fehlerliste' ) ) {
                        while ( have_rows( 'fehlerliste' ) ) {
                            the_row();
                            $f = get_sub_field( 'fehler' );
                            if ( ! empty( $f ) ) $result['stammdaten']['fehlerliste'][] = $f;
                        }
                    }

                    if ( have_rows( 'systemfehler_liste' ) ) {
                        while ( have_rows( 'systemfehler_liste' ) ) {
                            the_row();
                            $sf = get_sub_field( 'systemfehler' );
                            if ( ! empty( $sf ) ) $result['stammdaten']['systemfehler'][] = $sf;
                        }
                    }

                    break;
                }
            }
        }

        // 2. DOKUMENTE VERARBEITEN
        $documents = get_posts( [ 'post_type' => 'documents', 'posts_per_page' => -1, 'post_status' => 'any' ] );
        $result['debug_total_docs'] = count( $documents );

        foreach ( $documents as $doc ) {
            $doc_date = ''; $term_of_payment = ''; $doc_number = '';
            if ( have_rows( 'documents_document_data', $doc->ID ) ) {
                while ( have_rows( 'documents_document_data', $doc->ID ) ) {
                    the_row();
                    $doc_date        = get_sub_field( 'documents_document_data_document_date' );
                    $term_of_payment = get_sub_field( 'documents_document_data_term_of_payment' );
                    $doc_number      = get_sub_field( 'documents_document_data_document_number' );
                }
            }

            if ( strpos( (string) $doc_date, (string) $year ) !== false ) {
                $result['debug_count']++;

                $timestamp = strtotime( str_replace( '/', '.', $doc_date ) );
                $m = (int) date( 'n', $timestamp );
                $q = (int) ceil( $m / 3 );

                // TYP-ERKENNUNG: Jede Kategorie = Ausgabe; nur "einnahme"-Slug = Einnahme
                $terms       = wp_get_post_terms( $doc->ID, 'document_cat', [ 'fields' => 'slugs' ] );
                $is_einnahme = false;
                $doc_type    = 'Fehlt';

                if ( ! empty( $terms ) ) {
                    $doc_type = 'Ausgabe';
                    foreach ( $terms as $slug ) {
                        if ( strpos( strtolower( $slug ), 'einnahme' ) !== false ) {
                            $is_einnahme = true;
                            $doc_type    = 'Einnahme';
                            break;
                        }
                    }
                }

                // KUGELSICHERE GEWERBE-ZUORDNUNG via strpos
                $acf_gewerbe_raw = get_field( 'documents_gewerbeschein', $doc->ID );
                $acf_val = is_array( $acf_gewerbe_raw )
                    ? ( $acf_gewerbe_raw['value'] ?? $acf_gewerbe_raw['label'] ?? $acf_gewerbe_raw[0] ?? '' )
                    : (string) $acf_gewerbe_raw;
                $acf_val = strtolower( trim( $acf_val ) );

                $gewerbe = 'Nicht zugeordnet';
                if ( strpos( $acf_val, 'catering' ) !== false ) {
                    $gewerbe = 'Catering';
                } elseif ( strpos( $acf_val, 'technik' ) !== false ) {
                    $gewerbe = 'Veranstaltungstechnik';
                } elseif ( strpos( $acf_val, 'management' ) !== false ) {
                    $gewerbe = 'Event Management';
                } elseif ( strpos( $acf_val, 'werbe' ) !== false || strpos( $acf_val, 'agentur' ) !== false ) {
                    $gewerbe = 'Werbeagentur';
                }

                if ( ! isset( $result['e1'][ $gewerbe ] ) ) $result['e1'][ $gewerbe ] = [];

                $doc_net_total  = 0;
                $doc_tax_total  = 0;
                $tax_rates      = [];
                $items_list     = [];
                $has_non_20_tax = false;

                if ( have_rows( 'documents_article_items', $doc->ID ) ) {
                    while ( have_rows( 'documents_article_items', $doc->ID ) ) {
                        the_row();
                        $name       = get_sub_field( 'documents_article_items_article_name' ) ?: 'Artikel';
                        $desc       = get_sub_field( 'documents_article_items_article_description' ) ?: '';
                        $net_price  = (float) ( get_sub_field( 'documents_article_items_price_net_single' ) ?: 0 );
                        $qty        = (float) ( get_sub_field( 'documents_article_items_quantity' ) ?: 1 );
                        $tax_rate   = (int)   ( get_sub_field( 'documents_article_items_tax_rate' ) ?: 20 );
                        $privat_pct = (float) ( get_sub_field( 'documents_article_items_privatanteil' ) ?: 0 );

                        if ( ! in_array( $tax_rate, $tax_rates ) ) $tax_rates[] = $tax_rate;
                        if ( $tax_rate !== 20 ) $has_non_20_tax = true;

                        $net_total_cent    = (int) round( $net_price * $qty * 100 );
                        $privat_abzug_cent = (int) round( $net_total_cent * ( $privat_pct / 100 ) );
                        $net_business_cent = $net_total_cent - $privat_abzug_cent;
                        $tax_business_cent = (int) round( $net_business_cent * ( $tax_rate / 100 ) );

                        $doc_net_total += $net_business_cent;
                        $doc_tax_total += $tax_business_cent;

                        if ( $is_einnahme ) {
                            $result['u1']['kennzahl_000'] += $net_business_cent;
                            $result['u1']['ust_total']    += $tax_business_cent;

                            if ( $tax_rate === 0 )  $result['u1']['kennzahl_020'] += $net_business_cent;
                            if ( $tax_rate === 20 ) $result['u1']['kennzahl_022'] += $net_business_cent;

                            $result['periods']['months'][ $m ]['kennzahl_000'] += $net_business_cent;
                            if ( $tax_rate === 0 )  $result['periods']['months'][ $m ]['kennzahl_020'] += $net_business_cent;
                            if ( $tax_rate === 20 ) $result['periods']['months'][ $m ]['kennzahl_022'] += $net_business_cent;

                            $result['periods']['quarters'][ $q ]['kennzahl_000'] += $net_business_cent;
                            if ( $tax_rate === 0 )  $result['periods']['quarters'][ $q ]['kennzahl_020'] += $net_business_cent;
                            if ( $tax_rate === 20 ) $result['periods']['quarters'][ $q ]['kennzahl_022'] += $net_business_cent;

                            $result['e1'][ $gewerbe ]['9040'] = ( $result['e1'][ $gewerbe ]['9040'] ?? 0 ) + $net_business_cent;
                            $result['e1'][ $gewerbe ]['320']  = ( $result['e1'][ $gewerbe ]['320']  ?? 0 ) + $net_business_cent;
                        } else {
                            $result['u1']['kennzahl_060']                        += $tax_business_cent;
                            $result['periods']['months'][ $m ]['kennzahl_060']   += $tax_business_cent;
                            $result['periods']['quarters'][ $q ]['kennzahl_060'] += $tax_business_cent;

                            $kz_ausgabe = ( stripos( $name, 'waren' ) !== false ) ? '9100' : '9230';
                            $result['e1'][ $gewerbe ][ $kz_ausgabe ] = ( $result['e1'][ $gewerbe ][ $kz_ausgabe ] ?? 0 ) + $net_business_cent;
                            $result['e1'][ $gewerbe ]['320']         = ( $result['e1'][ $gewerbe ]['320']         ?? 0 ) - $net_business_cent;
                        }

                        $items_list[] = [
                            'name'          => $name,
                            'desc'          => $desc,
                            'qty'           => $qty,
                            'tax_rate'      => $tax_rate,
                            'privat_pct'    => $privat_pct,
                            'net_single'    => (int) round( $net_price * 100 ),
                            'net_total_raw' => $net_total_cent,
                            'privat_abzug'  => $privat_abzug_cent,
                            'net_business'  => $net_business_cent,
                            'tax_business'  => $tax_business_cent
                        ];
                    }
                }

                // PARTNER LOGIK
                $partner_name = 'Unbekannt';
                if ( $is_einnahme ) {
                    $client_rel = get_field( 'documents_client_relationship', $doc->ID );
                    if ( ! empty( $client_rel ) ) {
                        $client_id    = is_array( $client_rel )
                            ? ( is_object( $client_rel[0] ) ? $client_rel[0]->ID : $client_rel[0] )
                            : ( is_object( $client_rel )    ? $client_rel->ID    : $client_rel );
                        $partner_name = get_field( 'clients_company_name', $client_id ) ?: get_the_title( $client_id );
                    } else {
                        $partner_name = 'Kein Kunde verknüpft';
                    }
                } else {
                    $verkaufer_val = get_field( 'documents_verkaufer', $doc->ID );
                    if ( is_array( $verkaufer_val ) && isset( $verkaufer_val['label'] ) ) {
                        $partner_name = $verkaufer_val['label'];
                    } elseif ( is_array( $verkaufer_val ) && isset( $verkaufer_val[0] ) ) {
                        $partner_name = $verkaufer_val[0];
                    } else {
                        $partner_name = $verkaufer_val ?: 'Kein Verkäufer angegeben';
                    }
                }

                $doc_title = get_the_title( $doc->ID );

                // KUGELSICHERE ABSCHREIBUNGSART
                $abschreibung_raw = get_field( 'documents_abschreibungsart', $doc->ID );
                if ( is_array( $abschreibung_raw ) ) {
                    $abschreibung = $abschreibung_raw['label'] ?? $abschreibung_raw['value'] ?? $abschreibung_raw[0] ?? '';
                } else {
                    $abschreibung = (string) $abschreibung_raw;
                }
                if ( empty( trim( $abschreibung ) ) ) $abschreibung = 'Nicht definiert';

                $beschreibung     = get_field( 'documents_beschreibung', $doc->ID ) ?: '';
                $waehrung         = get_field( 'documents_waehrung', $doc->ID ) ?: 'EUR';
                $fremdwaehrung    = get_field( 'documents_fremdwaehrung', $doc->ID ) ? 'Ja' : 'Nein';
                $auslandsrechnung = get_field( 'documents_auslandsrechnung', $doc->ID ) ? 'Ja' : 'Nein';
                $is_uploaded      = ! empty( get_field( 'documents_file', $doc->ID ) ) ? '✅ Ja' : '❌ Nein';
                $has_linked       = ! empty( get_field( 'documents_verknuepfte_dokumente', $doc->ID ) ) ? '🔗 Ja' : 'Nein';
                $edit_link        = get_edit_post_link( $doc->ID, 'raw' );

                $result['document_details'][] = [
                    'id'              => $doc->ID,
                    'title'           => $doc_title,
                    'partner_name'    => $partner_name,
                    'date'            => $doc_date,
                    'payment_date'    => $term_of_payment,
                    'doc_number'      => $doc_number,
                    'abschreibung'    => $abschreibung,
                    'gewerbe'         => $gewerbe,
                    'type'            => $doc_type,
                    'tax_rates'       => implode( ', ', $tax_rates ) . '%',
                    'netto'           => $doc_net_total,
                    'tax'             => $doc_tax_total,
                    'items'           => $items_list,
                    'beschreibung'    => $beschreibung,
                    'waehrung'        => $waehrung,
                    'fremdwaehrung'   => $fremdwaehrung,
                    'auslandsrechnung'=> $auslandsrechnung,
                    'is_uploaded'     => $is_uploaded,
                    'has_linked'      => $has_linked,
                    'edit_link'       => $edit_link,
                    'has_non_20_tax'  => $has_non_20_tax
                ];
            }
        }

        usort( $result['document_details'], function( $a, $b ) {
            return strtotime( str_replace( '/', '.', $a['date'] ) ) <=> strtotime( str_replace( '/', '.', $b['date'] ) );
        } );

        return $result;
    }
}
