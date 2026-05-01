<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

class Class_Tax_Engine {
    public static function calculate_year( $year ) {
        $empty_u1 = [
            'kennzahl_000' => 0, 'kennzahl_020' => 0, 'kennzahl_022' => 0, 'kennzahl_029' => 0,
            'kennzahl_006' => 0, 'kennzahl_037' => 0, 'kennzahl_052' => 0, 'kennzahl_007' => 0,
            'kennzahl_070' => 0, 'kennzahl_071' => 0, 'kennzahl_072' => 0, 'kennzahl_073' => 0,
            'kennzahl_008' => 0, 'kennzahl_088' => 0, 'kennzahl_060' => 0, 'kennzahl_061' => 0,
            'kennzahl_062' => 0, 'ust_total' => 0, 'kammerumlage' => 0
        ];

        $result = [
            'u1'                 => $empty_u1,
            'e1'                 => [ 'Ausserbetrieblich' => [] ],
            'periods'            => [
                'months'   => array_fill( 1, 12, $empty_u1 ),
                'quarters' => array_fill( 1, 4, $empty_u1 )
            ],
            'debug_count'        => 0,
            'debug_total_docs'   => 0,
            'document_details'   => [],
            'e1_320_calculated'  => 0,
            'missing_sliced_gewerbe_count' => 0,
            'error_counts'       => [
                'no_upload'      => 0,
                'no_payment'     => 0,
                'no_client'      => 0,
                'tax_check'      => 0,
                'missing_gewerbe' => 0,
                'unassigned'     => 0,
            ],
            'stammdaten'         => [
                'informationen'  => '',
                'fehlerliste'    => [],
                'systemfehler'   => [],
                'ear'            => 'Nein',
                'netto_system'   => 'Nein',
                'gehaltsstellen' => '0',
                'beruf'          => 'Nicht definiert',
                'e1_320'         => '0,00',
                'u1_000'         => '0,00',
                'ausser_718'     => 0,
                'ausser_916'     => 0,
                'ausser_169'     => 0,
                'ausser_719'     => 0,
                'ausser_720'     => 0,
                'ausser_721'     => 0,
                'ausser_722'     => 0,
                'ausser_724'     => 0,
                'ausser_159'     => 0,
                'pauschale_9275' => 0,
                'pauschale_9215' => 0,
                'pauschale_9217' => 0,
                'pauschale_gewerbe' => 'werbe_agentur',
            ]
        ];

        if ( have_rows( 'view_jahre', 'option' ) ) {
            while ( have_rows( 'view_jahre', 'option' ) ) {
                the_row();
                if ( (string) get_sub_field( 'jahres_daten' ) === (string) $year ) {
                    $result['stammdaten']['informationen'] = get_sub_field( 'infos_box' ) ?: '';
                    $result['stammdaten']['ear']           = get_sub_field( 'einnahmen-ausgaben-rechnung' ) ? 'Ja' : 'Nein';
                    $result['stammdaten']['netto_system']  = get_sub_field( 'ust-nettosystem' ) ? 'Ja' : 'Nein';
                    $gs = get_sub_field( 'gehaltsstellen' );
                    $result['stammdaten']['gehaltsstellen'] = ( $gs !== '' && $gs !== null ) ? $gs : '0';
                    $result['stammdaten']['beruf']   = get_sub_field( 'berufliche_tatigkeit' ) ?: 'Nicht definiert';
                    $result['stammdaten']['e1_320']  = get_sub_field( 'summe_e1' ) ?: '0,00';
                    $result['stammdaten']['u1_000']  = get_sub_field( 'lieferungen_sonstige_leistungen_und_eigenverbrauch' ) ?: '0,00';

                    $acf_euro_to_cent = static function ( $acf_value ) {
                        if ( $acf_value === '' || $acf_value === null ) {
                            return 0;
                        }
                        return (int) round( (float) $acf_value * 100 );
                    };
                    $result['stammdaten']['ausser_718']     = $acf_euro_to_cent( get_sub_field( 'ausser_718' ) );
                    $result['stammdaten']['ausser_916']     = $acf_euro_to_cent( get_sub_field( 'ausser_916' ) );
                    $result['stammdaten']['ausser_169']    = $acf_euro_to_cent( get_sub_field( 'ausser_169' ) );
                    $result['stammdaten']['ausser_719']    = $acf_euro_to_cent( get_sub_field( 'ausser_719' ) );
                    $result['stammdaten']['ausser_720']    = $acf_euro_to_cent( get_sub_field( 'ausser_720' ) );
                    $result['stammdaten']['ausser_721']    = $acf_euro_to_cent( get_sub_field( 'ausser_721' ) );
                    $result['stammdaten']['ausser_722']    = $acf_euro_to_cent( get_sub_field( 'ausser_722' ) );
                    $result['stammdaten']['ausser_724']    = $acf_euro_to_cent( get_sub_field( 'ausser_724' ) );
                    $result['stammdaten']['ausser_159']    = $acf_euro_to_cent( get_sub_field( 'ausser_159' ) );
                    $result['stammdaten']['pauschale_9275'] = $acf_euro_to_cent( get_sub_field( 'pauschale_9275' ) );
                    $result['stammdaten']['pauschale_9215'] = $acf_euro_to_cent( get_sub_field( 'pauschale_9215' ) );
                    $result['stammdaten']['pauschale_9217'] = $acf_euro_to_cent( get_sub_field( 'pauschale_9217' ) );
                    $pg_raw = get_sub_field( 'pauschale_gewerbe' );
                    $result['stammdaten']['pauschale_gewerbe'] = is_array( $pg_raw )
                        ? ( $pg_raw['value'] ?? 'werbe_agentur' )
                        : ( $pg_raw ?: 'werbe_agentur' );

                    if ( have_rows( 'fehlerliste' ) ) {
                        while ( have_rows( 'fehlerliste' ) ) { the_row(); if ( $f = get_sub_field( 'fehler' ) ) $result['stammdaten']['fehlerliste'][] = $f; }
                    }
                    if ( have_rows( 'systemfehler_liste' ) ) {
                        while ( have_rows( 'systemfehler_liste' ) ) { the_row(); if ( $sf = get_sub_field( 'systemfehler' ) ) $result['stammdaten']['systemfehler'][] = $sf; }
                    }
                    break;
                }
            }
        }

        // Außerbetriebliche Pauschalen (ACF Jahresdaten, Cent) und betriebliche Pauschalen je Gewerk.
        $sd = $result['stammdaten'];
        $result['e1']['Ausserbetrieblich']['718'] = $sd['ausser_718'];
        $result['e1']['Ausserbetrieblich']['916'] = $sd['ausser_916'];
        $result['e1']['Ausserbetrieblich']['169'] = $sd['ausser_169'];
        $result['e1']['Ausserbetrieblich']['719'] = $sd['ausser_719'];
        $result['e1']['Ausserbetrieblich']['720'] = $sd['ausser_720'];
        $result['e1']['Ausserbetrieblich']['721'] = $sd['ausser_721'];
        $result['e1']['Ausserbetrieblich']['722'] = $sd['ausser_722'];
        $result['e1']['Ausserbetrieblich']['724'] = $sd['ausser_724'];
        $result['e1']['Ausserbetrieblich']['159'] = $sd['ausser_159'];

        $pg_slug = strtolower( (string) $sd['pauschale_gewerbe'] );
        $pauschale_gewerbe_label = 'Werbeagentur';
        if ( strpos( $pg_slug, 'catering' ) !== false ) {
            $pauschale_gewerbe_label = 'Catering';
        } elseif ( strpos( $pg_slug, 'technik' ) !== false ) {
            $pauschale_gewerbe_label = 'Veranstaltungstechnik';
        } elseif ( strpos( $pg_slug, 'management' ) !== false ) {
            $pauschale_gewerbe_label = 'Event Management';
        }

        if ( ! isset( $result['e1'][ $pauschale_gewerbe_label ] ) ) {
            $result['e1'][ $pauschale_gewerbe_label ] = [];
        }
        $result['e1'][ $pauschale_gewerbe_label ]['9275'] = ( $result['e1'][ $pauschale_gewerbe_label ]['9275'] ?? 0 ) + $sd['pauschale_9275'];
        $result['e1'][ $pauschale_gewerbe_label ]['9215'] = ( $result['e1'][ $pauschale_gewerbe_label ]['9215'] ?? 0 ) + $sd['pauschale_9215'];
        $result['e1'][ $pauschale_gewerbe_label ]['9217'] = ( $result['e1'][ $pauschale_gewerbe_label ]['9217'] ?? 0 ) + $sd['pauschale_9217'];

        $pausch_summe = $sd['pauschale_9275'] + $sd['pauschale_9215'] + $sd['pauschale_9217'];
        $result['e1_320_calculated'] -= $pausch_summe;
        $result['e1'][ $pauschale_gewerbe_label ]['320'] = ( $result['e1'][ $pauschale_gewerbe_label ]['320'] ?? 0 ) - $pausch_summe;

        $post_types_query = [ 'documents' ];
        if ( post_type_exists( 'sliced_invoice' ) ) {
            $post_types_query[] = 'sliced_invoice';
        }

        $documents = get_posts(
            [
                'post_type'      => $post_types_query,
                'posts_per_page' => -1,
                'post_status'    => 'any',
            ]
        );
        $result['debug_total_docs'] = count( $documents );

        foreach ( $documents as $doc ) {
            $is_sliced = ( $doc->post_type === 'sliced_invoice' );

            /**
             * Steuer-Analyse bezieht sich auf gebuchte Rechnungen, nicht auf Angebote (PDF).
             */
            if ( $is_sliced && function_exists( 'get_field' ) ) {
                $is_quote_cf = get_field( 'is_quote', $doc->ID );
                if ( ! empty( $is_quote_cf ) ) {
                    continue;
                }
            }

            $doc_date        = '';
            $term_of_payment = '';
            $doc_number      = '';

            if ( $is_sliced ) {
                $created = get_post_meta( $doc->ID, '_sliced_invoice_created', true );
                if ( ! empty( $created ) && is_numeric( $created ) ) {
                    $doc_date = gmdate( 'd.m.Y', (int) $created );
                } else {
                    $doc_date = get_the_date( 'd.m.Y', $doc );
                }
                $prefix = get_post_meta( $doc->ID, '_sliced_invoice_prefix', true );
                $num    = get_post_meta( $doc->ID, '_sliced_invoice_number', true );
                $prefix = $prefix ?: '';
                $num    = is_string( $num ) || is_numeric( $num ) ? (string) $num : '';
                $doc_number = trim( (string) $prefix . $num );
                if ( $doc_number === '' ) {
                    $doc_number = 'SI-' . (int) $doc->ID;
                }
                $paid = get_post_meta( $doc->ID, '_sliced_invoice_paid', true );
                if ( ! empty( $paid ) ) {
                    $term_of_payment = is_numeric( $paid ) ? gmdate( 'd.m.Y', (int) $paid ) : '';
                }
            }

            if ( have_rows( 'documents_document_data', $doc->ID ) ) {
                while ( have_rows( 'documents_document_data', $doc->ID ) ) {
                    the_row();
                    if ( empty( trim( (string) $doc_date ) ) || ! $is_sliced ) {
                        $acd = get_sub_field( 'documents_document_data_document_date' );
                        if ( ! empty( $acd ) ) {
                            $doc_date = $acd;
                        }
                    }
                    $top = get_sub_field( 'documents_document_data_term_of_payment' );
                    if ( ! empty( trim( (string) $top ) ) ) {
                        $term_of_payment = $top;
                    }
                    if ( empty( trim( (string) $doc_number ) ) || ! $is_sliced ) {
                        $acdnum = get_sub_field( 'documents_document_data_document_number' );
                        if ( ! empty( $acdnum ) ) {
                            $doc_number = $acdnum;
                        }
                    }
                }
            }

            if ( strpos( (string) $doc_date, (string) $year ) === false ) {
                continue;
            }

            $result['debug_count']++;

            $timestamp = strtotime( str_replace( '/', '.', (string) $doc_date ) );
            if ( $timestamp <= 0 ) {
                continue;
            }
            $m = (int) date( 'n', $timestamp );
            $q = (int) ceil( $m / 3 );

            $is_einnahme = false;
            $doc_type    = 'Fehlt';

            if ( $is_sliced ) {
                $is_einnahme = true;
                $doc_type    = 'Einnahme';
            } else {
                $terms = wp_get_post_terms( $doc->ID, 'document_cat', [ 'fields' => 'slugs' ] );
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
            }

            $acf_gewerbe_raw = self::resolve_gewerbeschein_raw( $doc->ID, $is_sliced );
            $acf_val         = is_array( $acf_gewerbe_raw )
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

            if ( $is_sliced && $gewerbe === 'Nicht zugeordnet' ) {
                $result['missing_sliced_gewerbe_count']++;
            }

            if ( ! isset( $result['e1'][ $gewerbe ] ) ) {
                $result['e1'][ $gewerbe ] = [];
            }

            $abschreibung_raw = get_field( 'documents_abschreibungsart', $doc->ID );
            $a_key = '';
            $a_label = '';
            if ( is_array( $abschreibung_raw ) ) {
                $a_key   = $abschreibung_raw['value'] ?? $abschreibung_raw[0] ?? '';
                $a_label = $abschreibung_raw['label'] ?? ( is_string( $a_key ) ? $a_key : '' );
            } else {
                $a_key   = (string) $abschreibung_raw;
                $a_label = $a_key;
            }

            $k = strtolower( trim( is_string( $a_key ) ? $a_key : (string) $a_key ) );
            $l = strtolower( trim( (string) $a_label ) );
            if ( empty( trim( (string) $a_label ) ) ) {
                $a_label = $is_sliced ? 'Rechnung (Sliced Invoices)' : 'Nicht definiert';
            }

            $kz_ausgabe           = '';
            $is_ausserbetrieblich = false;
            $is_kammerumlage      = false;
            $e1_reduction_factor  = 1.0;

            if ( $k === 'werbekosten' || $l === 'werbekosten' ) {
                $kz_ausgabe           = 'info_werbekosten';
                $is_ausserbetrieblich = true;
            } elseif ( $k === 'betriebsausgaben' || $l === 'betriebsausgaben' ) {
                $kz_ausgabe = '9230';
            } elseif ( $k === 'reisepauschale' || $l === 'verpflegungspauschale/reisepauschale' ) {
                $kz_ausgabe = '9160';
            } elseif ( $k === 'firmenessen' || $l === 'geschäftsessen' ) {
                $kz_ausgabe = '9200';
                $e1_reduction_factor = 0.5;
            } elseif ( $k === 'arztkosten' || $l === 'arztkosten/medikamente' ) {
                $kz_ausgabe = '730';
                $is_ausserbetrieblich = true;
            } elseif ( $k === 'geschenke' || $l === 'geschenke mit werbezweck' ) {
                $kz_ausgabe = '9200';
            } elseif ( $k === 'kammerumlage' || $l === 'kammerumlage' ) {
                $kz_ausgabe = '9230';
                $is_kammerumlage = true;
            } elseif ( $k === 'svs' || $l === 'sozialversicherung' ) {
                $kz_ausgabe = '9225';
            } elseif ( $k === 'vorsorgeleistung' || $l === 'vorsorgeleistung' ) {
                $kz_ausgabe = '9225';
            } elseif ( $k === 'fremdpersonal' || $l === 'fremdpersonal' ) {
                $kz_ausgabe = '9110';
            } elseif ( $k === 'eigenpersonal' || $l === 'eigenpersonal' ) {
                $kz_ausgabe = '9120';
            } elseif ( $k === 'miete' || $l === 'miete/pachten/leasing' ) {
                $kz_ausgabe = '9180';
            } elseif ( $k === 'lizensgebuhren' || $l === 'lizenzgebühren/provision' ) {
                $kz_ausgabe = '9190';
            } elseif ( $k === 'waren' || $l === 'waren' ) {
                $kz_ausgabe = '9100';
            } elseif ( $k === 'presentaion' || $l === 'präsentation/werbung' ) {
                $kz_ausgabe = '9200';
            } elseif ( $k === 'weiterbildung' || $l === 'weiterbildung' ) {
                $kz_ausgabe = '9230';
            } elseif ( $k === 'ticket' || $l === 'öffi ticket' ) {
                $kz_ausgabe = '9165';
                $is_ticket_100         = (bool) get_field( 'documents_ticket_100', $doc->ID );
                $e1_reduction_factor   = $is_ticket_100 ? 1.0 : 0.5;
            } elseif ( $k === 'steuerberater' || $l === 'steuerberater' ) {
                $kz_ausgabe = '460';
                $is_ausserbetrieblich = true;
            } elseif ( $k === 'sprit' || $l === 'sprit' ) {
                $kz_ausgabe = '9170';
            } elseif ( $k === 'kfz-kosten' || $l === 'kfz-kosten' ) {
                $kz_ausgabe = '9170';
            } elseif ( $k === 'firmenfeier-werbeveranstaltung' || $l === 'firmenfeier-werbeveranstaltung' ) {
                $kz_ausgabe = '9200';
            } elseif ( $k === 'anlagegueter' || $l === 'anlagegüter' ) {
                $kz_ausgabe = '9130';
            } elseif ( $k === 'degressive-abschreibung' || $l === 'degressive abschreibung anlagegüter' ) {
                $kz_ausgabe = '9134';
            } elseif ( $k === 'keine-ausgabe' || $l === 'keine ausgabe' ) {
                $kz_ausgabe = '';
            } else {
                $kz_ausgabe = '';
            }

            $is_keine_ausgabe = ( $k === 'keine-ausgabe' || $l === 'keine ausgabe' );

            $doc_net_total  = 0;
            $doc_tax_total  = 0;
            $tax_rates      = [];
            $items_list     = [];
            $has_non_20_tax = false;
            $err_tax_check  = false;

            $is_109a         = (bool) get_field( 'documents_109a_erfasst', $doc->ID );
            $is_netto_system = ( $result['stammdaten']['netto_system'] === 'Ja' );

            $doc_contributions = [];

            $extracted_items = [];
            if ( function_exists( 'have_rows' ) && have_rows( 'documents_article_items', $doc->ID ) ) {
                while ( have_rows( 'documents_article_items', $doc->ID ) ) {
                    the_row();
                    $extracted_items[] = [
                        'name'       => get_sub_field( 'documents_article_items_article_name' ) ?: 'Artikel',
                        'desc'       => get_sub_field( 'documents_article_items_article_description' ) ?: '',
                        'qty'        => (float) ( get_sub_field( 'documents_article_items_quantity' ) ?: 1 ),
                        'net_single' => (float) ( get_sub_field( 'documents_article_items_price_net_single' ) ?: 0 ),
                        'tax_rate'   => (int) ( get_sub_field( 'documents_article_items_tax_rate' ) ?: 20 ),
                        'privat_pct' => (float) ( get_sub_field( 'documents_article_items_privatnutzung' ) ?: 0 ),
                    ];
                }
            } elseif ( $is_sliced ) {
                $sliced_items = get_post_meta( $doc->ID, '_sliced_items', true );
                if ( is_array( $sliced_items ) ) {
                    foreach ( $sliced_items as $sitem ) {
                        if ( ! is_array( $sitem ) ) {
                            continue;
                        }
                        $tax_rate = 20;
                        if ( isset( $sitem['tax'] ) && is_numeric( $sitem['tax'] ) ) {
                            $tax_rate = (int) $sitem['tax'];
                        } elseif ( isset( $sitem['tax_percentage'] ) && is_numeric( $sitem['tax_percentage'] ) ) {
                            $tax_rate = (int) $sitem['tax_percentage'];
                        }
                        $qty = isset( $sitem['qty'] ) ? (float) $sitem['qty'] : 1.0;
                        if ( $qty <= 0 ) {
                            $qty = 1.0;
                        }
                        $amount = isset( $sitem['amount'] ) ? (float) $sitem['amount'] : 0.0;
                        if ( $amount === 0.0 && isset( $sitem['line_total'] ) ) {
                            $amount = (float) $sitem['line_total'];
                        }
                        $extracted_items[] = [
                            'name'       => isset( $sitem['title'] ) ? (string) $sitem['title'] : 'Position',
                            'desc'       => isset( $sitem['description'] ) ? (string) $sitem['description'] : '',
                            'qty'        => $qty,
                            'net_single' => $amount,
                            'tax_rate'   => $tax_rate,
                            'privat_pct' => 0.0,
                        ];
                    }
                }
            }

            foreach ( $extracted_items as $row_item ) {
                    $name       = $row_item['name'];
                    $desc       = $row_item['desc'];
                    $net_price  = (float) $row_item['net_single'];
                    $qty        = (float) $row_item['qty'];
                    $tax_rate   = (int) $row_item['tax_rate'];
                    $privat_pct = (float) $row_item['privat_pct'];

                    if ( ! in_array( $tax_rate, $tax_rates, true ) ) {
                        $tax_rates[] = $tax_rate;
                    }
                    if ( $tax_rate !== 20 ) {
                        $has_non_20_tax = true;
                    }
                    if ( $tax_rate !== 20 && $tax_rate !== 0 ) {
                        $err_tax_check = true;
                    }

                    $net_total_cent    = (int) round( $net_price * $qty * 100 );
                    $privat_abzug_cent = (int) round( $net_total_cent * ( $privat_pct / 100 ) );
                    $net_business_cent = $net_total_cent - $privat_abzug_cent;
                    $tax_business_cent = (int) round( $net_business_cent * ( $tax_rate / 100 ) );

                    $doc_net_total += $net_business_cent;
                    $doc_tax_total += $tax_business_cent;

                    if ( $is_einnahme ) {
                        $result['u1']['kennzahl_000'] += $net_business_cent;
                        $result['u1']['ust_total']    += $tax_business_cent;
                        $doc_contributions[] = 'u1-kennzahl_000';
                        $doc_contributions[] = "uva-m{$m}-kennzahl_000";
                        $doc_contributions[] = "uva-q{$q}-kennzahl_000";
                        if ( $tax_rate === 0 ) {
                            $result['u1']['kennzahl_020'] += $net_business_cent;
                            $doc_contributions[] = 'u1-kennzahl_020';
                            $doc_contributions[] = "uva-m{$m}-kennzahl_020";
                            $doc_contributions[] = "uva-q{$q}-kennzahl_020";
                        }
                        if ( $tax_rate === 10 ) {
                            $result['u1']['kennzahl_029'] += $net_business_cent;
                            $doc_contributions[] = 'u1-kennzahl_029';
                            $doc_contributions[] = "uva-m{$m}-kennzahl_029";
                            $doc_contributions[] = "uva-q{$q}-kennzahl_029";
                        }
                        if ( $tax_rate === 13 ) {
                            $result['u1']['kennzahl_006'] += $net_business_cent;
                            $doc_contributions[] = 'u1-kennzahl_006';
                            $doc_contributions[] = "uva-m{$m}-kennzahl_006";
                            $doc_contributions[] = "uva-q{$q}-kennzahl_006";
                        }
                        if ( $tax_rate === 20 ) {
                            $result['u1']['kennzahl_022'] += $net_business_cent;
                            $doc_contributions[] = 'u1-kennzahl_022';
                            $doc_contributions[] = "uva-m{$m}-kennzahl_022";
                            $doc_contributions[] = "uva-q{$q}-kennzahl_022";
                        }
                        $result['periods']['months'][ $m ]['kennzahl_000'] += $net_business_cent;
                        if ( $tax_rate === 0 ) {
                            $result['periods']['months'][ $m ]['kennzahl_020'] += $net_business_cent;
                        }
                        if ( $tax_rate === 10 ) {
                            $result['periods']['months'][ $m ]['kennzahl_029'] += $net_business_cent;
                        }
                        if ( $tax_rate === 13 ) {
                            $result['periods']['months'][ $m ]['kennzahl_006'] += $net_business_cent;
                        }
                        if ( $tax_rate === 20 ) {
                            $result['periods']['months'][ $m ]['kennzahl_022'] += $net_business_cent;
                        }
                        $result['periods']['quarters'][ $q ]['kennzahl_000'] += $net_business_cent;
                        if ( $tax_rate === 0 ) {
                            $result['periods']['quarters'][ $q ]['kennzahl_020'] += $net_business_cent;
                        }
                        if ( $tax_rate === 10 ) {
                            $result['periods']['quarters'][ $q ]['kennzahl_029'] += $net_business_cent;
                        }
                        if ( $tax_rate === 13 ) {
                            $result['periods']['quarters'][ $q ]['kennzahl_006'] += $net_business_cent;
                        }
                        if ( $tax_rate === 20 ) {
                            $result['periods']['quarters'][ $q ]['kennzahl_022'] += $net_business_cent;
                        }
                        $e1_wert_cent = $is_netto_system ? $net_business_cent : ( $net_business_cent + $tax_business_cent );
                        $kz_e         = $is_109a ? '9050' : '9040';
                        $result['e1'][ $gewerbe ][ $kz_e ] = ( $result['e1'][ $gewerbe ][ $kz_e ] ?? 0 ) + $e1_wert_cent;
                        $result['e1_320_calculated']      += $e1_wert_cent;
                        $doc_contributions[] = "e1-{$gewerbe}-{$kz_e}";
                        $doc_contributions[] = 'e1-global-320';
                    } elseif ( ! $is_keine_ausgabe ) {
                        $result['u1']['kennzahl_060']                        += $tax_business_cent;
                        $result['periods']['months'][ $m ]['kennzahl_060']   += $tax_business_cent;
                        $result['periods']['quarters'][ $q ]['kennzahl_060'] += $tax_business_cent;
                        $doc_contributions[] = 'u1-kennzahl_060';
                        $doc_contributions[] = "uva-m{$m}-kennzahl_060";
                        $doc_contributions[] = "uva-q{$q}-kennzahl_060";
                        if ( $is_kammerumlage ) {
                            $result['u1']['kammerumlage'] = ( $result['u1']['kammerumlage'] ?? 0 ) + $net_business_cent;
                            $doc_contributions[] = 'u1-kammerumlage';
                        }
                        if ( $kz_ausgabe !== '' ) {
                            if ( $is_ausserbetrieblich ) {
                                $base_expense = $net_business_cent + $tax_business_cent;
                            } else {
                                $base_expense = $is_netto_system ? $net_business_cent : ( $net_business_cent + $tax_business_cent );
                            }
                            $final_expense_val = (int) round( $base_expense * $e1_reduction_factor );
                            if ( $is_ausserbetrieblich ) {
                                $result['e1']['Ausserbetrieblich'][ $kz_ausgabe ] = ( $result['e1']['Ausserbetrieblich'][ $kz_ausgabe ] ?? 0 ) + $final_expense_val;
                                $doc_contributions[] = "e1-Ausserbetrieblich-{$kz_ausgabe}";
                            } else {
                                $result['e1'][ $gewerbe ][ $kz_ausgabe ] = ( $result['e1'][ $gewerbe ][ $kz_ausgabe ] ?? 0 ) + $final_expense_val;
                                $result['e1_320_calculated']            -= $final_expense_val;
                                $doc_contributions[] = "e1-{$gewerbe}-{$kz_ausgabe}";
                                $doc_contributions[] = 'e1-global-320';
                            }
                        }
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
                        'tax_business'  => $tax_business_cent,
                    ];
            }

            $partner_name = 'Unbekannt';
            if ( $is_einnahme ) {
                if ( $is_sliced ) {
                    $scid = (int) get_post_meta( $doc->ID, '_sliced_client', true );
                    if ( $scid > 0 ) {
                        $partner_name = get_field( 'clients_company_name', $scid ) ?: get_the_title( $scid );
                        if ( '' === trim( (string) $partner_name ) ) {
                            $partner_name = 'Kunde #' . $scid;
                        }
                    } else {
                        $partner_name = 'Kein Kunde verknüpft';
                    }
                } else {
                    $client_rel = get_field( 'documents_client_relationship', $doc->ID );
                    if ( ! empty( $client_rel ) ) {
                        $client_id    = is_array( $client_rel )
                            ? ( is_object( $client_rel[0] ) ? $client_rel[0]->ID : $client_rel[0] )
                            : ( is_object( $client_rel ) ? $client_rel->ID : $client_rel );
                        $partner_name = get_field( 'clients_company_name', $client_id ) ?: get_the_title( $client_id );
                    } else {
                        $partner_name = 'Kein Kunde verknüpft';
                    }
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

            $display_abschreibung = is_array( $abschreibung_raw )
                ? ( $abschreibung_raw['label'] ?? (string) $a_label )
                : (string) $a_label;
            if ( empty( trim( (string) $display_abschreibung ) ) ) {
                $display_abschreibung = $is_sliced ? 'Rechnung (Sliced Invoices)' : 'Nicht definiert';
            }

            $beschreibung     = get_field( 'documents_beschreibung', $doc->ID ) ?: '';
            $si_currency      = strtoupper( (string) get_post_meta( $doc->ID, '_sliced_invoice_currency', true ) );
            $waehrung_meta    = get_field( 'documents_waehrung', $doc->ID );
            $waehrung         = ! empty( $waehrung_meta ) ? (string) $waehrung_meta : ( $si_currency ?: 'EUR' );
            $fremdwaehrung    = get_field( 'documents_fremdwaehrung', $doc->ID ) ? 'Ja' : 'Nein';
            $auslandsrechnung = get_field( 'documents_auslandsrechnung', $doc->ID ) ? 'Ja' : 'Nein';
            $is_uploaded      = ! empty( get_field( 'documents_file', $doc->ID ) ) ? '✅ Ja' : '❌ Nein';
            if ( $is_sliced ) {
                $is_uploaded = '✅ Erstellt (Sliced Invoices)';
            }
            $has_linked       = ! empty( get_field( 'documents_verknuepfte_dokumente', $doc->ID ) ) ? '🔗 Ja' : 'Nein';
            $edit_link = get_edit_post_link( $doc->ID, 'raw' );
            $doc_title = get_the_title( $doc->ID );

            $sliced_missing_gew = ( $is_sliced && $gewerbe === 'Nicht zugeordnet' );

            if ( $sliced_missing_gew ) {
                $doc_contributions[] = 'filter-sliced-missing-gewerbe';
            }

            $has_doc_file = ! empty( get_field( 'documents_file', $doc->ID ) );
            $is_upload_ok = $has_doc_file || $is_sliced;

            if ( ! $is_upload_ok ) {
                $doc_contributions[] = 'err-no-upload';
                $result['error_counts']['no_upload']++;
            }
            if ( empty( trim( (string) $term_of_payment ) ) ) {
                $doc_contributions[] = 'err-no-payment';
                $result['error_counts']['no_payment']++;
            }
            if ( $is_einnahme && ( $partner_name === 'Kein Kunde verknüpft' || $partner_name === 'Unbekannt' ) ) {
                $doc_contributions[] = 'err-no-client';
                $result['error_counts']['no_client']++;
            }
            if ( $err_tax_check ) {
                $doc_contributions[] = 'err-tax-check';
                $result['error_counts']['tax_check']++;
            }
            if ( $is_sliced && $gewerbe === 'Nicht zugeordnet' ) {
                $doc_contributions[] = 'err-missing-gewerbe';
                $result['error_counts']['missing_gewerbe']++;
            }
            if ( $gewerbe === 'Nicht zugeordnet' && ! $is_sliced ) {
                $doc_contributions[] = 'err-unassigned';
                $result['error_counts']['unassigned']++;
            }

            $result['document_details'][] = [
                'id'                    => $doc->ID,
                'title'                 => $doc_title,
                'partner_name'          => $partner_name,
                'date'                  => $doc_date,
                'payment_date'          => $term_of_payment,
                'doc_number'            => $doc_number,
                'abschreibung'          => $display_abschreibung,
                'gewerbe'               => $gewerbe,
                'type'                  => $doc_type,
                'is_sliced'             => $is_sliced,
                'sliced_missing_gewerbe'=> $sliced_missing_gew,
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
                'has_non_20_tax'  => $has_non_20_tax,
                'contributions'   => array_values( array_unique( $doc_contributions ) ),
            ];
        }

        usort( $result['document_details'], function( $a, $b ) {
            return strtotime( str_replace( '/', '.', $a['date'] ) ) <=> strtotime( str_replace( '/', '.', $b['date'] ) );
        } );

        return $result;
    }

    /**
     * Erst belegtes Gewerbeschein-Feld (Sliced: mehrere ACF-/Meta-Keys wie im Backend).
     *
     * @param int  $doc_id Post-ID.
     * @param bool $is_sliced sliced_invoice oder documents.
     * @return mixed Rohwert für documents_gewerbeschein-/Select-Logik (Array oder Skalar), oder false/'' wenn leer.
     */
    private static function resolve_gewerbeschein_raw( $doc_id, $is_sliced ) {
        $candidates = [];

        if ( $is_sliced && function_exists( 'get_field' ) ) {
            $candidates[] = get_field( 'sliced_invoice_gewerbeschein', $doc_id );
            $candidates[] = get_field( 'gewerbe_wahl_sliced_invoices', $doc_id );
        }

        if ( $is_sliced ) {
            $candidates[] = get_post_meta( $doc_id, 'sliced_invoice_gewerbeschein', true );
            $candidates[] = get_post_meta( $doc_id, 'gewerbe_wahl_sliced_invoices', true );
        }

        if ( function_exists( 'get_field' ) ) {
            $candidates[] = get_field( 'documents_gewerbeschein', $doc_id );
        }

        foreach ( $candidates as $raw ) {
            if ( self::gewerbeschein_candidate_nonempty( $raw ) ) {
                return $raw;
            }
        }

        return false;
    }

    /**
     * @param mixed $raw ACF-/Meta-Wert (Select kann Array sein).
     */
    private static function gewerbeschein_candidate_nonempty( $raw ) {
        if ( null === $raw || false === $raw ) {
            return false;
        }
        if ( is_array( $raw ) ) {
            foreach ( [ 'value', 'label' ] as $k ) {
                if ( isset( $raw[ $k ] ) && trim( (string) $raw[ $k ] ) !== '' ) {
                    return true;
                }
            }
            if ( isset( $raw[0] ) && trim( (string) $raw[0] ) !== '' ) {
                return true;
            }

            return false;
        }

        return trim( (string) $raw ) !== '';
    }
}
