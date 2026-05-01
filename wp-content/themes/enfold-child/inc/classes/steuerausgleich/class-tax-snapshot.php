<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

class Class_Tax_Snapshot {
    public static function init_storage() {}

    // NEU: Shortcode Registrierung
    public static function init() {
        add_shortcode( 'tax_dashboard', [ __CLASS__, 'render_shortcode' ] );
    }

    // NEU: Shortcode Handler mit Output Buffering
    public static function render_shortcode() {
        ob_start();
        self::run_historical_tests( false ); // false = Script nicht mit die() abbrechen
        return ob_get_clean();
    }

    public static function run_historical_tests( $kill_script = true ) {

        $corrections_path = plugin_dir_path( __FILE__ ) . 'tax-corrections.json';

        // Speichern (POST JSON → tax-corrections.json), vor jeglicher HTML-Ausgabe.
        if ( isset( $_GET['save_tax_correction'] ) ) {
            if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( [ 'message' => 'Forbidden' ], 403 );
            }
            $nonce_header = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce_header, 'tax_corr_save' ) ) {
                wp_send_json_error( [ 'message' => 'Bad nonce' ], 403 );
            }
            $input = json_decode( (string) file_get_contents( 'php://input' ), true );
            if ( ! is_array( $input ) || empty( $input['id'] ) || ! is_string( $input['id'] ) ) {
                wp_send_json_error( [ 'message' => 'Invalid payload' ], 400 );
            }
            $row_id = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $input['id'] );
            if ( $row_id === '' ) {
                wp_send_json_error( [ 'message' => 'Invalid id' ], 400 );
            }

            $existing = [];
            if ( file_exists( $corrections_path ) ) {
                $existing = json_decode( (string) file_get_contents( $corrections_path ), true );
                $existing = is_array( $existing ) ? $existing : [];
            }

            $existing[ $row_id ] = [
                'korrektur' => isset( $input['korrektur'] ) ? sanitize_text_field( (string) $input['korrektur'] ) : '',
                'notiz'     => isset( $input['notiz'] ) ? sanitize_textarea_field( (string) $input['notiz'] ) : '',
            ];

            file_put_contents( $corrections_path, wp_json_encode( $existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ), LOCK_EX );
            wp_send_json_success();
        }

        $corrections_data = [];
        if ( file_exists( $corrections_path ) ) {
            $decoded = json_decode( (string) file_get_contents( $corrections_path ), true );
            $corrections_data = is_array( $decoded ) ? $decoded : [];
        }

        $json_path = plugin_dir_path( __FILE__ ) . 'tax-snapshot-data.json';
        if ( ! file_exists( $json_path ) ) {
            echo "<div style='padding:20px; background:#fdedec; color:#c0392b;'>Fehler: Die Datei tax-snapshot-data.json wurde nicht gefunden.</div>";
            return;
        }

        $data = json_decode( file_get_contents( $json_path ), true );

        $ground_truth = $data['ground_truth'];
        if ( ! isset( $ground_truth['2025'] ) ) {
            $ground_truth['2025'] = $data['blank_year'];
        }
        if ( ! isset( $ground_truth['2026'] ) ) {
            $ground_truth['2026'] = $data['blank_year'];
        }

        $e1_struktur = $data['e1_struktur'];
        $u1_struktur = $data['u1_struktur'];

        echo '<div style="background:#f3f4f6; padding:20px; font-family: Arial, sans-serif;">';
        echo '<input type="hidden" id="tax-corr-nonce" value="' . esc_attr( wp_create_nonce( 'tax_corr_save' ) ) . '" />';

        foreach ( $ground_truth as $year => $expected ) {
            $calculated = Class_Tax_Engine::calculate_year( $year );
            $sd         = $calculated['stammdaten'];

            $ec_defaults = [
                'no_upload'       => 0,
                'no_payment'      => 0,
                'no_client'       => 0,
                'tax_check'       => 0,
                'missing_gewerbe' => 0,
                'unassigned'      => 0,
            ];
            $ec = isset( $calculated['error_counts'] ) && is_array( $calculated['error_counts'] )
                ? array_merge( $ec_defaults, $calculated['error_counts'] )
                : $ec_defaults;

            $is_netto_system = isset( $sd['netto_system'] ) && $sd['netto_system'] === 'Ja';
            $e1_form_label   = $is_netto_system ? 'Netto' : 'Brutto';

            $sd_e1_320_clean = str_replace( [ '.', ' ' ], '', $sd['e1_320'] );
            $sd_e1_320_clean = str_replace( ',', '.', $sd_e1_320_clean );
            $e1_320_acf_cent = (int) round( (float) $sd_e1_320_clean * 100 );

            $ausserbetrieblich = [
                'title' => 'Außerbetriebliche Einkunftsarten',
                'items' => [
                    'title_1'   => "Einkünfte aus nichtselbständiger Arbeit (Werbungskosten)\nEinkünfte aus nichtselbständiger Arbeit\nAnzahl der inländischen gehalts- oder pensionsauszahlenden Stellen im Jahr {$year}: {$sd['gehaltsstellen']}",
                    '725'       => "Steuerfreie Einkünfte auf Grund völkerrechtlicher Vereinbarungen (z.B. UNO, UNIDO) - (Kennzahl 725)",
                    'title_1_5' => "Pendlerpauschale/Pendlereuro",
                    '718'       => "Pendlerpauschale - tatsächlich zustehender Jahresbetrag - (Kennzahl 718)",
                    '916'       => "Pendlereuro (Absetzbetrag) - tatsächlich zustehender Jahresbetrag - (Kennzahl 916)",
                    'title_2'   => "Werbungskosten\nGenaue Bezeichnung Ihrer beruflichen Tätigkeit (z.B. Koch, Verkäufer*in; nicht ausreichend ist Angestellte*r, Arbeiter*in): {$sd['beruf']}",
                    '169'       => "Digitale Arbeitsmittel (z.B. Computer, Internet) ohne Kürzung um ein allfälliges Homeoffice-Pauschale - (Kennzahl 169)",
                    '719'       => "Andere Arbeitsmittel, die nicht in Kennzahl 169 zu erfassen sind (bei Anschaffungen über 1.000 Euro inkl. Umsatzsteuer tragen Sie hier nur die jährliche Abschreibung ein) - (Kennzahl 719)",
                    '720'       => "Fachliteratur (keine allgemein bildenden Werke wie Lexika, Nachschlagewerke, Zeitungen etc.) - (Kennzahl 720)",
                    '721'       => "Beruflich veranlasste Reisekosten\n(ohne Fahrtkosten Wohnung/Arbeitsstätte und Familienheimfahrten) - (Kennzahl 721)",
                    '722'       => "Fortbildungs-, Ausbildungs- und Umschulungskosten - (Kennzahl 722)",
                    '724'       => "Sonstige Werbungskosten, die nicht unter die Kennzahlen 169, 719, 720, 721, 722, 300, 723 und 159 fallen (z.B. Betriebsratsumlage) - (Kennzahl 724)",
                    '159'       => "Arbeitszimmer\nAchtung: Es darf keine Eintragung in Kennzahl 158 erfolgen. Nur abzugsfähig, wenn das Arbeitszimmer Mittelpunkt der gesamten beruflichen Tätigkeit ist.\n - (Kennzahl 159)",
                    'title_3'   => "Sonderausgaben inkl. Verlustabzug",
                    '460'       => "Steuerberatungskosten - (Kennzahl 460)",
                    'title_4'   => "Außergewöhnliche Belastungen\nAußergewöhnliche Belastungen mit Selbstbehalt (abzüglich erhaltener Ersätze oder Vergütungen)",
                    '730'       => "Krankheitskosten (inkl. Zahnersatz) - (Kennzahl 730)",
                    '731'       => "Außergewöhnliche Belastungen mit Selbstbehalt (abzüglich erhaltener Ersätze oder Vergütungen)\nBegräbniskosten (soweit nicht gedeckt durch: Nachlassaktiva, Versicherungsleistungen, steuerfreie Ersätze durch Arbeitgeber*in, Vermögensübertragung innerhalb der letzten 7 Jahre vor Ableben) - (Kennzahl 731)",
                    '734'       => "Kurkosten nach Abzug einer anteiligen Haushaltsersparnis für Verpflegung (Vollpension) in Höhe von 5,23 Euro täglich - (Kennzahl 734)",
                    '735'       => "Sonstige außergewöhnliche Belastungen, die nicht unter die Kennzahlen 730, 731, 734, fallen - (Kennzahl 735)",
                    'title_5'   => "Außergewöhnliche Belastungen ohne Selbstbehalt",
                    '475'       => "Katastrophenschäden (abzüglich erhaltener Ersätze oder Vergütungen) - (Kennzahl 475)"
                ]
            ];

            $e1_mismatch_count          = 0;
            $u1_mismatch_count          = 0;
            $missing_type_count         = 0;
            $not_uploaded_count         = 0;
            $foreign_count              = 0;
            $foreign_currency_count     = 0;
            $missing_payment_date_count = 0;
            $linked_docs_count          = 0;
            $missing_client_count       = 0;
            $missing_vendor_count       = 0;
            $missing_abschreibung_count = 0;
            $tax_not_20_count           = 0;

            if ( ! empty( $calculated['document_details'] ) ) {
                foreach ( $calculated['document_details'] as $doc ) {
                    if ( $doc['type'] === 'Fehlt' ) {
                        $missing_type_count++;
                    }
                    if ( $doc['is_uploaded'] === '❌ Nein' ) {
                        $not_uploaded_count++;
                    }
                    if ( $doc['auslandsrechnung'] === 'Ja' ) {
                        $foreign_count++;
                    }
                    if ( $doc['fremdwaehrung'] === 'Ja' ) {
                        $foreign_currency_count++;
                    }
                    if ( empty( $doc['payment_date'] ) ) {
                        $missing_payment_date_count++;
                    }
                    if ( $doc['has_linked'] === '🔗 Ja' ) {
                        $linked_docs_count++;
                    }
                    if ( $doc['type'] === 'Ausgabe' && $doc['abschreibung'] === 'Nicht definiert' ) {
                        $missing_abschreibung_count++;
                    }
                    if ( $doc['has_non_20_tax'] ) {
                        $tax_not_20_count++;
                    }
                    if ( $doc['type'] === 'Einnahme' && $doc['partner_name'] === 'Kein Kunde verknüpft' ) {
                        $missing_client_count++;
                    }
                    if ( ( $doc['type'] === 'Ausgabe' || $doc['type'] === 'Fehlt' ) && $doc['partner_name'] === 'Kein Verkäufer angegeben' ) {
                        $missing_vendor_count++;
                    }
                }
            }

            if ( isset( $expected['e1'] ) ) {
                foreach ( $e1_struktur as $gewerbe_name => $struktur ) {
                    foreach ( [ 'einnahmen', 'ausgaben' ] as $art ) {
                        foreach ( $struktur[ $art ] as $kz => $text ) {
                            $soll = $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0;
                            $ist  = $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0;
                            if ( abs( $soll - $ist ) > 1 ) {
                                $e1_mismatch_count++;
                            }
                        }
                    }
                }
                foreach ( $ausserbetrieblich['items'] as $kz => $text ) {
                    if ( strpos( $kz, 'title_' ) !== 0 ) {
                        $soll = $expected['e1']['Ausserbetrieblich'][ $kz ] ?? 0;
                        $ist  = $calculated['e1']['Ausserbetrieblich'][ $kz ] ?? 0;
                        if ( abs( $soll - $ist ) > 1 ) {
                            $e1_mismatch_count++;
                        }
                    }
                }
                if ( abs( $e1_320_acf_cent - ( $calculated['e1_320_calculated'] ?? 0 ) ) > 1 ) {
                    $e1_mismatch_count++;
                }
            }

            if ( isset( $expected['u1'] ) ) {
                foreach ( $u1_struktur as $section_title => $fields ) {
                    foreach ( $fields as $kz => $text ) {
                        $soll = $expected['u1'][ $kz ] ?? 0;
                        $ist  = $calculated['u1'][ $kz ] ?? 0;
                        if ( abs( $soll - $ist ) > 1 ) {
                            $u1_mismatch_count++;
                        }
                    }
                }
            }

            $dynamic_system_errors = [];
            if ( $e1_mismatch_count > 0 ) {
                $dynamic_system_errors[] = "{$e1_mismatch_count} Unstimmigkeiten im E1 gefunden";
            }
            if ( $u1_mismatch_count > 0 ) {
                $dynamic_system_errors[] = "{$u1_mismatch_count} Unstimmigkeiten im U1 gefunden";
            }
            if ( $missing_type_count > 0 ) {
                $dynamic_system_errors[] = "{$missing_type_count} Einträge fehlt der Typ";
            }
            if ( $foreign_count > 0 ) {
                $dynamic_system_errors[] = "{$foreign_count} Rechnungen sind aus dem Ausland (muss überprüft werden)";
            }
            if ( $foreign_currency_count > 0 ) {
                $dynamic_system_errors[] = "{$foreign_currency_count} Rechnungen haben andere Währung";
            }
            if ( $linked_docs_count > 0 ) {
                $dynamic_system_errors[] = "{$linked_docs_count} Rechnungen haben verknüpfte Dokumente (muss überprüft werden)";
            }
            if ( $missing_vendor_count > 0 ) {
                $dynamic_system_errors[] = "{$missing_vendor_count} Ausgaben haben keinen Verkäufer gewählt";
            }
            if ( $missing_abschreibung_count > 0 ) {
                $dynamic_system_errors[] = "{$missing_abschreibung_count} Ausgaben fehlt die Abschreibungsart";
            }
            // Belege: Upload/Zahlung/Kunde/USt-/Gewebe — als klickbare err-*-Filter (error_counts), nicht als Textzeilen

            $all_system_errors = array_merge( $dynamic_system_errors, $sd['systemfehler'] );
            $open_tag          = ( $year === '2024' ) ? 'open' : '';

            // =========================================================
            // UI RENDERING
            // =========================================================
            echo "<div class='year-container' style='padding: 20px;'>";
            echo "<details {$open_tag} style='margin-bottom: 20px; border: 1px solid #ccc; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>";
            echo "<summary style='background: #fff; padding: 15px; font-size: 18px; font-weight: bold; cursor: pointer; border-bottom: 1px solid #ccc; outline: none;'>";
            echo "📆 Veranlagungsjahr {$year}";
            echo "</summary>";
            echo "<div style='padding: 20px;'>";

            // 1. DASHBOARD
            echo "<details open class='main-accordion' style='margin-bottom: 20px; border: 1px solid #8e44ad;'>";
            echo "<summary style='background: #9b59b6; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>💡 Infos, Fehler & Daten</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";

            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>Gesamtwerte E1 und U1</h3>";
            echo "<p style='font-size:13px; line-height:1.5;'><strong>Gesamtsaldo der Einkünfte aus selbständiger Arbeit sowie Einkünfteverteilungen</strong><br>Summe Kennzahl 320<br>Summe aus allen Beilagen E1a, E1a-K und E11 sowie den Kennzahlen 321 bis 501 - (Kennzahl 320) <strong>{$sd['e1_320']}</strong> <span style='color:#555;'>(E1-Zeilen: {$e1_form_label})</span></p>";
            echo "<p style='font-size:13px; line-height:1.5; margin-bottom:20px;'><strong>Lieferungen, sonstige Leistungen und Eigenverbrauch</strong><br>Gesamtbetrag der Bemessungsgrundlagen des Veranlagungszeitraumes {$year} für Lieferungen und sonstige Leistungen (ohne den nachstehend angeführten Eigenverbrauch) einschließlich Anzahlungen (jeweils ohne Umsatzsteuer) - (Kennzahl 000) <strong>{$sd['u1_000']}</strong> <span style='color:#555;'>(Netto)</span></p>";

            $soll_netto  = $expected['u1']['kennzahl_000'] ?? 0;
            $ist_netto   = $calculated['u1']['kennzahl_000'] ?? 0;
            $soll_tax    = ( ( $expected['u1']['kennzahl_022'] ?? 0 ) * 0.2 ) + ( ( $expected['u1']['kennzahl_029'] ?? 0 ) * 0.1 ) + ( ( $expected['u1']['kennzahl_006'] ?? 0 ) * 0.13 ) + ( ( $expected['u1']['kennzahl_037'] ?? 0 ) * 0.19 );
            $soll_brutto = $soll_netto + $soll_tax;
            $ist_brutto  = $ist_netto + ( $calculated['u1']['ust_total'] ?? 0 );

            echo "<div style='background:#fdf2e9; padding:10px; border-left:3px solid #e67e22; font-size:13px;'>";
            echo "<p style='margin:0 0 5px 0;'>Umsatz Netto: SOLL: <strong>" . number_format( $soll_netto / 100, 2, ',', '.' ) . " €</strong> IST: <strong>" . number_format( $ist_netto / 100, 2, ',', '.' ) . " €</strong></p>";
            echo "<p style='margin:0;'>Umsatz Brutto: SOLL: <strong>" . number_format( $soll_brutto / 100, 2, ',', '.' ) . " €</strong> IST: <strong>" . number_format( $ist_brutto / 100, 2, ',', '.' ) . " €</strong></p>";
            echo "</div>";

            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 20px; margin-bottom: 10px;'>Informationen</h3>";
            echo "<div style='font-size:13px;'>" . ( ! empty( $sd['informationen'] ) ? $sd['informationen'] : '<em>Keine Informationen hinterlegt.</em>' ) . "</div>";
            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 20px; margin-bottom: 10px;'>Fehlerliste (Manuell)</h3>";
            if ( ! empty( $sd['fehlerliste'] ) ) {
                echo "<ul style='font-size:13px; list-style-type:none; padding-left:0;'>";
                foreach ( $sd['fehlerliste'] as $f ) {
                    echo "<li><span style='color:#f39c12;'>⚠️</span> {$f}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='font-size:13px;'><em>Keine manuellen Fehler hinterlegt.</em></p>";
            }

            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 20px; margin-bottom: 10px;'>Systemfehler</h3>";

            $ec_btn_defs = [
                'no_upload'       => [
                    'filter' => 'err-no-upload',
                    'title'  => 'Fehlender Upload',
                    'text'   => 'Rechnungen wurden nicht hochgeladen',
                    'icon'   => '⛔',
                    'style'  => 'cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #fadbd8;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
                'no_payment'      => [
                    'filter' => 'err-no-payment',
                    'title'  => 'Fehlender Zahlungseingang',
                    'text'   => 'Rechnungen haben keinen Zahlungseingang',
                    'icon'   => '⛔',
                    'style'  => 'cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #fadbd8;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
                'no_client'       => [
                    'filter' => 'err-no-client',
                    'title'  => 'Fehlender Kunde',
                    'text'   => 'Einnahmen ohne Kundenverknüpfung',
                    'icon'   => '⛔',
                    'style'  => 'cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #fadbd8;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
                'tax_check'       => [
                    'filter' => 'err-tax-check',
                    'title'  => 'Steuersatz-Prüfung',
                    'text'   => 'Rechnungen mit zu prüfendem Steuersatz (≠ 20 % und ≠ 0 %)',
                    'icon'   => '⛔',
                    'style'  => 'cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #fadbd8;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
                'missing_gewerbe' => [
                    'filter' => 'err-missing-gewerbe',
                    'title'  => 'Fehlendes Gewerbe (Sliced Invoices)',
                    'text'   => 'Sliced Invoices ohne Gewerbe-Zuordnung',
                    'icon'   => '⚠️',
                    'style'  => 'cursor:pointer;color:#af601a;text-align:left;width:100%;padding:8px 10px;background:#fcf3cf;border:1px solid #f9e79f;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
                'unassigned'      => [
                    'filter' => 'err-unassigned',
                    'title'  => 'Ausgaben ohne Gewerbe (normale Dokumente)',
                    'text'   => 'Ausgaben / Belege ohne Gewerbeschein-Zuordnung (kein Sliced)',
                    'icon'   => '🔍',
                    'style'  => 'cursor:pointer;color:#7f8c8d;text-align:left;width:100%;padding:8px 10px;background:#f2f3f4;border:1px solid #d5d8dc;border-radius:4px;font-size:13px;margin-bottom:8px;',
                ],
            ];

            $has_ec_buttons = false;
            foreach ( $ec_btn_defs as $k => $_def ) {
                if ( (int) $ec[ $k ] > 0 ) {
                    $has_ec_buttons = true;
                    break;
                }
            }
            if ( $has_ec_buttons ) {
                echo "<p style='font-size:12px; color:#7f8c8d; margin:0 0 8px 0;'><em>Klickbare Engine-Meldungen (Filter in Belegen über contributions / err-*):</em></p>";
                if ( (int) $ec['missing_gewerbe'] > 0 || (int) $ec['unassigned'] > 0 ) {
                    echo '<p style="font-size:12px; color:#515a5a; margin:0 0 10px 0;"><strong>Sliced vs. Dokumente:</strong> <code>err-missing-gewerbe</code> betrifft nur Sliced Invoices, <code>err-unassigned</code> nur Belege vom Typ <em>documents</em> ohne Gewerbeschein.</p>';
                }
                echo "<ul style='font-size:13px; list-style-type:none; padding-left:0; margin:0 0 12px 0;'>";
                foreach ( $ec_btn_defs as $k => $def ) {
                    $n = (int) $ec[ $k ];
                    if ( $n <= 0 ) {
                        continue;
                    }
                    $fk   = esc_attr( $def['filter'] );
                    $ttl  = esc_attr( $def['title'] );
                    $stl  = esc_attr( $def['style'] );
                    $ic   = isset( $def['icon'] ) ? $def['icon'] : '⛔';
                    echo '<li style="margin-bottom:2px;"><button type="button" class="sys-error-btn" data-target="belege-accordion" data-filter="' . $fk . '" data-title="' . $ttl . '" style="' . $stl . '"><span class="sys-error-icon" aria-hidden="true">' . esc_html( $ic ) . '</span> <strong>' . (int) $n . '×</strong> ' . esc_html( $def['text'] ) . ' <span style="opacity:0.85;">(Klick → Belege filtern)</span></button></li>';
                }
                echo '</ul>';
            }

            if ( ! empty( $all_system_errors ) ) {
                echo "<ul style='font-size:13px; list-style-type:none; padding-left:0;'>";
                foreach ( $all_system_errors as $sf ) {
                    echo "<li style='margin-bottom:5px;'><span style='color:#c0392b;'>⛔</span> <strong>System-Meldung:</strong> {$sf}</li>";
                }
                echo "</ul>";
            }
            if ( $has_ec_buttons || ! empty( $all_system_errors ) ) {
                echo "<p style='font-size:11px; color:#7f8c8d; margin-top:10px;'><em>Hinweis: Diese Meldungen beziehen sich auf Rohdaten (IST vs. Referenz aus tax-snapshot-data.json), ohne berücksichtigte Frontend-Korrekturen in den Zeilen unten.</em></p>";
            }
            if ( empty( $all_system_errors ) && ! $has_ec_buttons ) {
                echo "<p style='font-size:13px;'><em>Keine Systemfehler für dieses Jahr erkannt. Alles im grünen Bereich!</em></p>";
            }

            $has_drill = ( isset( $expected['e1'] ) && $e1_mismatch_count > 0 )
                || ( isset( $expected['u1'] ) && $u1_mismatch_count > 0 );

            if ( $has_drill ) {
                echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 20px; margin-bottom: 10px;'>Schnellanzeige (Drill-Down)</h3>";
                echo "<ul class='snapshot-drill-list' style='list-style:none;padding-left:0;margin:0 0 10px;font-size:13px;line-height:1.5;'>";
                if ( isset( $expected['e1'] ) && $e1_mismatch_count > 0 ) {
                    echo '<li style="margin-bottom:8px;"><button type="button" class="sys-error-btn" data-target="e1-accordion" data-type="mismatch" style="cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #f5b7b1;border-radius:4px;font-size:13px;"><strong>E1:</strong> ' . (int) $e1_mismatch_count . " Unstimmigkeit(en) zur Referenz nur anzeigen</button></li>";
                }
                if ( isset( $expected['u1'] ) && $u1_mismatch_count > 0 ) {
                    echo '<li style="margin-bottom:8px;"><button type="button" class="sys-error-btn" data-target="u1-accordion" data-type="mismatch" style="cursor:pointer;color:#c0392b;text-align:left;width:100%;padding:8px 10px;background:#fdedec;border:1px solid #f5b7b1;border-radius:4px;font-size:13px;"><strong>U1:</strong> ' . (int) $u1_mismatch_count . " Unstimmigkeit(en) zur Referenz nur anzeigen</button></li>";
                }
                echo '</ul>';
            }

            echo "</div></details>";

            // HILFSFUNKTION: Zeile mit Korrektur-Soll, Notiz, Live-Diff (JS färbt)
            // =========================================================
            $render_row = function( $kz, $text, $soll_cent, $ist_cent, $filter_key = '', $type_label = 'Netto' ) use ( $year, $corrections_data ) {
                $row_hash = md5( (string) $year . '|' . (string) $kz . '|' . (string) $text . '|' . (string) $filter_key );
                $kz_safe  = preg_replace( '/[^a-zA-Z0-9_\-]/', '_', (string) $kz );
                $row_id   = 'row_' . (string) $year . '_' . $kz_safe . '_' . $row_hash;

                $tl = 'Netto' === $type_label || 'Brutto' === $type_label ? $type_label : 'Netto';

                $saved = ( isset( $corrections_data[ $row_id ] ) && is_array( $corrections_data[ $row_id ] ) )
                    ? $corrections_data[ $row_id ]
                    : [];
                $saved_corr  = isset( $saved['korrektur'] ) ? (string) $saved['korrektur'] : '';
                $saved_notiz = isset( $saved['notiz'] ) ? (string) $saved['notiz'] : '';

                $btn_html = '';
                if ( $ist_cent != 0 && $filter_key !== '' ) {
                    $first_line  = explode( "\n", $text );
                    $short_title = esc_attr( $first_line[0] );
                    $fk          = esc_attr( $filter_key );
                    $btn_html    = "<br><button type=\"button\" class=\"beleg-filter-btn\" data-filter=\"{$fk}\" data-title=\"{$short_title}\" style='margin-top: 5px; background: #3498db; color: #fff; border: none; border-radius: 3px; padding: 3px 8px; font-size: 11px; cursor: pointer;'>🔍 Belege ansehen</button>";
                }

                $display_notiz = ( $saved_corr !== '' && trim( $saved_corr ) !== '' ) ? 'block' : 'none';

                $has_ref_mismatch = abs( (int) $soll_cent - (int) $ist_cent ) > 1;
                $wrapper_classes = [ 'tax-row-wrapper' ];
                if ( $has_ref_mismatch ) {
                    $wrapper_classes[] = 'mismatch-row';
                }

                echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '" id="' . esc_attr( $row_id ) . '" data-soll="' . (int) $soll_cent . '" data-ist="' . (int) $ist_cent . '" data-type-label="' . esc_attr( $tl ) . '" data-ref-mismatch="' . ( $has_ref_mismatch ? '1' : '0' ) . '" style="border-bottom:1px solid #ddd; background: #fdedec; transition: background 0.3s ease;">';
                echo "<div class='tax-row-main' style='display:flex; justify-content: space-between; padding: 10px; font-size: 13px;'>";
                echo "<div style='width: 40%; padding-right:15px; line-height: 1.4;'>" . nl2br( $text ) . $btn_html . '</div>';
                echo "<div style='width: 60%; display:flex; justify-content: space-between; text-align:right; align-items:flex-end; flex-wrap: wrap; gap: 6px;'>";
                echo "<div style='width:22%; min-width:90px;'>Original-Soll (<span class='type-label'>" . esc_html( $tl ) . "</span>):<br><strong class='original-soll' style='color:#7f8c8d;'>" . number_format( $soll_cent / 100, 2, ',', '.' ) . " €</strong></div>";
                echo "<div style='width:28%; min-width:100px; text-align:left; padding-left:10px;'>Korrektur-Soll (<span class='type-label'>" . esc_html( $tl ) . "</span>):<br><input type='text' class='korrektur-input' value='" . esc_attr( $saved_corr ) . "' placeholder='0,00' autocomplete='off' style='width: 90px; max-width:100%; padding: 3px 5px; font-size: 12px; border: 1px solid #ccc; border-radius: 3px; background:#fff;'></div>";
                echo "<div style='width:22%; min-width:90px;'>Berechnet-Ist (<span class='type-label'>" . esc_html( $tl ) . "</span>):<br><strong class='ist-label'>" . number_format( $ist_cent / 100, 2, ',', '.' ) . " €</strong></div>";
                echo "<div style='width:22%; min-width:90px;' class='diff-container'>Diff (<span class='type-label'>" . esc_html( $tl ) . "</span>):<br><strong class='diff-label'>0,00 €</strong></div>";
                echo '</div></div>';
                echo "<div class='notiz-container' style='display: {$display_notiz}; padding: 0 10px 10px 10px;'>";
                echo "<input type='text' class='notiz-input' value='" . esc_attr( $saved_notiz ) . "' placeholder='Grund für die Korrektur notieren…' autocomplete='off' style='width: 100%; max-width:100%; padding: 6px; font-size: 12px; border: 1px dashed #3498db; border-radius: 3px; background: rgba(255,255,255,0.85);'>";
                echo '</div>';
                echo '</div>';
            };

            // 2. EINKOMMENSTEUER (E1) ACCORDION
            echo "<details class='main-accordion e1-accordion' {$open_tag} style='margin-bottom: 20px; border: 1px solid #16a085;'>";
            echo "<summary style='background: #1abc9c; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📝 Einkommensteuererklärung (E1)</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";

            echo "<div style='margin-bottom: 30px; font-size: 13px;'>";
            echo "<h2 style='font-size: 16px; border-bottom: 1px solid #ccc; padding-bottom: 5px;'>Unternehmensdaten</h2>";
            echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><td style='width: 200px; padding: 4px 0;'>Firmenname</td><td>Fila Dominik</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Anschrift</td><td>Brauhausstraße 8/Stg. 4/8</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>PLZ</td><td>2351</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Ort</td><td>Wiener Neudorf</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Steuernummer</td><td>16 346/1429</td></tr>";
            echo "</table>";

            echo "<h2 style='font-size: 16px; border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-top: 20px;'>Einkünfte aus selbständiger Arbeit</h2>";
            echo "<p style='margin-bottom: 20px;'>Einkünfte aus selbständiger Arbeit - Einzelunternehmer*in</p>";

            if ( isset( $expected['e1'] ) ) {
                foreach ( $e1_struktur as $gewerbe_name => $struktur ) {
                    echo "<div class='e1-section-block' style='margin-bottom: 30px;'>";
                    echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>" . $struktur['title'] . "</h3>";
                    echo "<div class='e1-sub-section'><p style='font-weight: bold; margin: 15px 0 5px 0;'>Gewinnermittlung<br>Erträge/Betriebseinnahmen</p>";
                    foreach ( $struktur['einnahmen'] as $kz => $text ) {
                        $render_row( $kz, $text, $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0, $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0, "e1-{$gewerbe_name}-{$kz}", $e1_form_label );
                    }
                    echo '</div>';
                    echo "<div class='e1-sub-section'><p style='font-weight: bold; margin: 15px 0 5px 0;'>Aufwendungen/Betriebsausgaben</p>";
                    foreach ( $struktur['ausgaben'] as $kz => $text ) {
                        $render_row( $kz, $text, $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0, $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0, "e1-{$gewerbe_name}-{$kz}", $e1_form_label );
                    }
                    echo '</div>';
                    echo "</div>";
                }
            }

            echo "<div class='e1-section-block' style='margin-top: 10px;'>";
            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 30px; margin-bottom: 10px;'>Gesamtsaldo der Einkünfte aus selbständiger Arbeit sowie Einkünfteverteilungen</h3>";
            $kz_320_text = "Summe Kennzahl 320\nSumme aus allen Beilagen E1a, E1a-K und E11 sowie den Kennzahlen 321 bis 501 - (Kennzahl 320)";
            $render_row( '320', $kz_320_text, $e1_320_acf_cent, $calculated['e1_320_calculated'], 'e1-global-320', $e1_form_label );
            echo '</div>';

            echo "<div class='e1-section-block' style='margin-top: 40px; border-top: 2px solid #ccc; padding-top: 20px;'>";
            echo "<h2 style='font-size: 18px; margin-bottom: 15px;'>" . $ausserbetrieblich['title'] . "</h2>";

            $werbekosten_info = (int) ( $calculated['e1']['Ausserbetrieblich']['info_werbekosten'] ?? 0 );
            $wk_btn_html        = '';
            if ( $werbekosten_info > 0 ) {
                $wk_f  = esc_attr( 'e1-Ausserbetrieblich-info_werbekosten' );
                $wk_tt = esc_attr( 'Hochgeladene Werbungskosten' );
                $wk_btn_html = "<button type=\"button\" class=\"beleg-filter-btn\" data-filter=\"{$wk_f}\" data-title=\"{$wk_tt}\" style=\"margin-left: 10px; background: #3498db; color: #fff; border: none; border-radius: 3px; padding: 3px 8px; font-size: 11px; cursor: pointer;\">🔍 Belege ansehen</button>";
            }
            echo "<div style='background: #e8f4fd; border: 1px solid #3498db; border-left: 4px solid #2980b9; padding: 12px; margin-bottom: 20px; font-size: 13px; border-radius: 3px;'>";
            echo "<strong style='color: #2980b9;'>ℹ️ Info zu hochgeladenen Werbungskosten</strong><br>";
            echo "<div style='margin-top: 5px;'>Die Summe der Belege mit Abschreibungsart <em>Werbekosten</em> (rein informativ): ";
            echo '<strong style="font-size: 14px;">' . number_format( $werbekosten_info / 100, 2, ',', '.' ) . " €</strong> {$wk_btn_html}</div>";
            echo '<div style="margin-top: 8px; color: #555; font-size: 11px;">Dieser Wert wird keiner E1-Kennzahl automatisch zugeordnet. Kennzahl 169 u. Ä. bitte über die Jahres-Einstellungen bzw. Formular ausweisen.</div>';
            echo '</div>';

            foreach ( $ausserbetrieblich['items'] as $kz => $text ) {
                if ( strpos( $kz, 'title_' ) === 0 ) {
                    echo "<p style='font-weight: bold; font-size: 13px; margin: 15px 0 5px 0; white-space: pre-line;'>" . $text . "</p>";
                    continue;
                }
                $render_row( $kz, $text, $expected['e1']['Ausserbetrieblich'][ $kz ] ?? 0, $calculated['e1']['Ausserbetrieblich'][ $kz ] ?? 0, "e1-Ausserbetrieblich-{$kz}", 'Brutto' );
            }
            echo "</div></div></div></details>";

            // 3. UMSATZSTEUER (U1) ACCORDION
            echo "<details class='main-accordion u1-accordion' {$open_tag} style='margin-bottom: 20px; border: 1px solid #2980b9;'>";
            echo "<summary style='background: #2980b9; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📊 Umsatzsteuererklärung (U1)</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";

            if ( isset( $expected['u1'] ) ) {
                foreach ( $u1_struktur as $section_title => $fields ) {
                    echo "<div class='u1-section-block' style='margin-bottom: 30px;'><h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>{$section_title}</h3>";
                    foreach ( $fields as $kz => $text ) {
                        $render_row( $kz, $text, $expected['u1'][ $kz ] ?? 0, $calculated['u1'][ $kz ] ?? 0, "u1-{$kz}", 'Netto' );
                    }
                    echo "</div>";
                }
                echo "<div class='u1-section-block' style='margin-top: 30px; font-size: 13px; border-top: 1px solid #eee; padding-top: 15px;'>";
                echo "<p style='margin-bottom:5px;'>Kammerumlagepflicht(§ 122 Wirtschaftskammergesetz) liegt vor: <strong>Ja</strong></p>";
                $render_row( 'kammerumlage', "An Kammerumlage wurde entrichtet", $expected['u1']['kammerumlage'] ?? 0, $calculated['u1']['kammerumlage'] ?? 0, 'u1-kammerumlage', 'Netto' );
                echo "</div>";
            }
            echo "</div></details>";

            // 3.5 UVA
            echo "<details class='main-accordion' style='margin-bottom: 20px; border: 1px solid #e67e22;'>";
            echo "<summary style='background: #e67e22; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📅 Umsatzsteuervoranmeldung (UVA)</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";
            echo "<h3 style='font-size:16px; color:#e67e22; border-bottom:1px solid #eee; margin-bottom:15px; padding-bottom:5px;'>Quartalsweise Auswertung</h3>";
            $q_names = [ 1 => '1. Quartal (Jänner - März)', 2 => '2. Quartal (April - Juni)', 3 => '3. Quartal (Juli - September)', 4 => '4. Quartal (Oktober - Dezember)' ];
            foreach ( $q_names as $q_idx => $q_name ) {
                echo "<details style='margin-bottom: 8px; border: 1px solid #ddd; background: #fff;'><summary style='padding: 10px; background: #fdf2e9; font-weight: bold; cursor: pointer; outline: none;'>{$q_name}</summary><div style='padding: 15px;'>";
                foreach ( $u1_struktur as $sect => $fields ) {
                    echo "<h4 style='font-size:14px; margin:15px 0 10px 0; border-bottom: 1px dashed #eee; padding-bottom: 5px;'>{$sect}</h4>";
                    foreach ( $fields as $kz => $txt ) {
                        $render_row( $kz, $txt, 0, $calculated['periods']['quarters'][ $q_idx ][ $kz ] ?? 0, "uva-q{$q_idx}-{$kz}", 'Netto' );
                    }
                }
                echo "</div></details>";
            }
            echo "<h3 style='font-size:16px; color:#e67e22; border-bottom:1px solid #eee; margin-top:30px; margin-bottom:15px; padding-bottom:5px;'>Monatliche Auswertung</h3>";
            $m_names = [ 1 => 'Jänner', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember' ];
            foreach ( $m_names as $m_idx => $m_name ) {
                echo "<details style='margin-bottom: 8px; border: 1px solid #ddd; background: #fff;'><summary style='padding: 10px; background: #fdf2e9; font-weight: bold; cursor: pointer; outline: none;'>{$m_name}</summary><div style='padding: 15px;'>";
                foreach ( $u1_struktur as $sect => $fields ) {
                    echo "<h4 style='font-size:14px; margin:15px 0 10px 0; border-bottom: 1px dashed #eee; padding-bottom: 5px;'>{$sect}</h4>";
                    foreach ( $fields as $kz => $txt ) {
                        $render_row( $kz, $txt, 0, $calculated['periods']['months'][ $m_idx ][ $kz ] ?? 0, "uva-m{$m_idx}-{$kz}", 'Netto' );
                    }
                }
                echo "</div></details>";
            }
            echo "</div></details>";

            // 4. BELEGE
            echo "<details class='main-accordion belege-accordion' {$open_tag} style='margin-bottom: 20px; border: 1px solid #34495e;'>";
            echo "<summary style='background: #34495e; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📄 Verarbeitete Belege & Artikel (Detailliert)</summary>";
            echo "<div style='padding: 15px; background: #ecf0f1;'>";

            // FILTER UI BANNER (JS setzt display:flex; Metriken aus aktiver Zeile)
            echo "<div class='active-filter-container' style='display:none; background: #3498db; color: white; padding: 12px 15px; margin-bottom: 15px; border-radius: 4px; flex-direction: row; align-items: flex-start; justify-content: space-between; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex-wrap: nowrap;'>";
            echo "<div class='active-filter-text' style='font-size:14px; flex:1; min-width:0;'>Gefiltert nach: …</div>";
            echo "<button type='button' class='reset-filter-btn' style='flex-shrink:0; background: rgba(255,255,255,0.2); border: none; color: white; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-weight: bold; white-space: nowrap; height: fit-content;'>✖ Filter aufheben</button>";
            echo "</div>";

            if ( ! empty( $calculated['document_details'] ) ) {
                foreach ( $calculated['document_details'] as $doc ) {
                    $is_in = ( $doc['type'] === 'Einnahme' );

                    if ( $doc['type'] === 'Einnahme' ) {
                        $color = '#27ae60';
                        $bg    = '#e8f8f5';
                    } elseif ( $doc['type'] === 'Ausgabe' ) {
                        $color = '#c0392b';
                        $bg    = '#fdedec';
                    } else {
                        $color = '#f39c12';
                        $bg    = '#fcf3cf';
                    }

                    $netto_eur  = number_format( $doc['netto'] / 100, 2, ',', '.' ) . ' €';
                    $tax_eur    = number_format( $doc['tax'] / 100, 2, ',', '.' ) . ' €';
                    $brutto_eur = number_format( ( $doc['netto'] + $doc['tax'] ) / 100, 2, ',', '.' ) . ' €';

                    $contribs     = $doc['contributions'] ?? [];
                    $contribs_json = esc_attr( wp_json_encode( $contribs, JSON_UNESCAPED_UNICODE ) );

                    $is_unassigned   = isset( $doc['gewerbe'] ) && $doc['gewerbe'] === 'Nicht zugeordnet';
                    $sl_missing_attr = ! empty( $doc['sliced_missing_gewerbe'] ) ? '1' : '0';
                    echo "<details class='beleg-item-details' data-contributions=\"{$contribs_json}\" data-unassigned=\"" . ( $is_unassigned ? '1' : '0' ) . "\" data-sliced-missing=\"{$sl_missing_attr}\" style='background: #fff; border: 1px solid #bdc3c7; margin-bottom: 8px; border-radius: 4px;'>";
                    echo "<summary style='background: {$bg}; padding: 10px; cursor: pointer; font-weight: bold; display: flex; justify-content: space-between; align-items: center; outline: none; border-bottom: 1px solid #bdc3c7;'>";
                    echo "<span style='width: 10%; color: #7f8c8d;'>{$doc['date']}</span>";
                    echo "<span style='width: 10%; color: #555;'>#{$doc['id']} <a href='{$doc['edit_link']}' target='_blank' style='text-decoration:none; margin-left:5px;' title='Im Backend bearbeiten'>✏️</a></span>";
                    echo "<span style='width: 25%;'>{$doc['title']} <em style='font-size:11px; color:#7f8c8d;'>({$doc['gewerbe']})</em></span>";
                    echo "<span style='width: 20%; color: {$color}; text-align: right;'>Geschäftl. Netto: {$netto_eur}</span>";
                    echo "<span style='width: 15%; text-align: right;'>Steuer: {$tax_eur}</span>";
                    echo "<span style='width: 15%; text-align: right; font-weight:bold;'>Brutto: {$brutto_eur}</span>";
                    echo "</summary>";

                    echo "<div style='padding: 15px;'>";
                    echo "<div style='display:flex; flex-wrap: wrap; gap: 10px 2%; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; font-size: 13px;'>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Typ:</strong> <span style='color:{$color}'>{$doc['type']}</span></div>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Zahlungseingang:</strong> {$doc['payment_date']}</div>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Abschreibung:</strong> {$doc['abschreibung']}</div>";

                    if ( $is_in ) {
                        echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Kunde:</strong> {$doc['partner_name']}</div>";
                        echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Rechnungsnummer:</strong> {$doc['doc_number']}</div>";
                        echo "<div style='width: 32%;'></div>";
                    } else {
                        echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Verkäufer:</strong> {$doc['partner_name']}</div>";
                        echo "<div style='width: 32%;'></div>";
                        echo "<div style='width: 32%;'></div>";
                    }

                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Fremdwährung?:</strong> {$doc['fremdwaehrung']}</div>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Auslandsrechnung?:</strong> {$doc['auslandsrechnung']}</div>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Rechnung hochgeladen:</strong> {$doc['is_uploaded']}</div>";
                    echo "<div style='width: 32%;'><strong style='color:#7f8c8d;'>Verknüpfte Dok.:</strong> {$doc['has_linked']}</div>";
                    echo "</div>";

                    if ( ! empty( $doc['items'] ) ) {
                        echo "<table style='width:100%; border-collapse: collapse; font-size: 12px;'>";
                        echo "<tr style='background: #f8f9fa; color: #333; text-align:left;'>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:5%;'>Menge</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:15%;'>Artikel</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:15%;'>Beschreibung</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:10%; text-align:right;'>Einzel</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:5%; text-align:center;'>USt %</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:5%; text-align:center;'>Währung</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:5%; text-align:center;'>Privat %</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:15%; text-align:right; color:{$color};'>Geschäftlich Netto</th>";
                        echo "<th style='padding:6px; border:1px solid #ddd; width:10%; text-align:right;'>Brutto</th>";
                        echo "</tr>";
                        foreach ( $doc['items'] as $item ) {
                            $privat_bg  = $item['privat_pct'] > 0 ? '#fff3e0' : 'transparent';
                            $item_brutto = ( $item['net_business'] + $item['tax_business'] ) / 100;
                            echo "<tr style='background: {$privat_bg};'>";
                            echo "<td style='padding:6px; border:1px solid #ddd;'>{$item['qty']}x</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd;'><strong>{$item['name']}</strong></td>";
                            echo "<td style='padding:6px; border:1px solid #ddd;'>{$item['desc']}</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:right;'>" . number_format( $item['net_single'] / 100, 2, ',', '.' ) . "</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>{$item['tax_rate']}%</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:center;'>{$doc['waehrung']}</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:center; color:#e67e22;'>{$item['privat_pct']}%</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:right; font-weight:bold; color:{$color};'>" . number_format( $item['net_business'] / 100, 2, ',', '.' ) . "</td>";
                            echo "<td style='padding:6px; border:1px solid #ddd; text-align:right; font-weight:bold;'>" . number_format( $item_brutto, 2, ',', '.' ) . "</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                    echo "</div></details>";
                }
            } else {
                echo "<p style='color:#7f8c8d;'>Keine Belege für dieses Jahr in der Datenbank gefunden.</p>";
            }
            echo "</div></details></div></details></div>";
        }

        // =========================================================
        // JS: Belegfilter, Korrektur-Soll, Diff, AJAX-Speicherung
        // =========================================================
        echo '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var nonceEl = document.getElementById("tax-corr-nonce");

            function clearYearAccordionDrill(yearContainer) {
                if (!yearContainer) return;
                yearContainer.querySelectorAll(".tax-row-wrapper").forEach(function(row) {
                    row.style.display = "";
                });
                yearContainer.querySelectorAll(".e1-section-block, .e1-sub-section, .u1-section-block").forEach(function(el) {
                    el.style.display = "";
                });
            }

            document.querySelectorAll(".sys-error-btn").forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    var filterKey = this.getAttribute("data-filter");
                    var targetClass = this.getAttribute("data-target") || "";
                    var filterType = this.getAttribute("data-type") || "";

                    var yearContainer = this.closest(".year-container");
                    if (!yearContainer) return;

                    if (filterKey) {
                        clearYearAccordionDrill(yearContainer);

                        yearContainer.querySelectorAll("details.main-accordion").forEach(function(d) {
                            d.removeAttribute("open");
                        });

                        var targetAccErr = targetClass
                            ? yearContainer.querySelector("." + targetClass)
                            : yearContainer.querySelector(".belege-accordion");
                        if (targetAccErr) {
                            targetAccErr.setAttribute("open", "");
                        }

                        var titleErr = this.getAttribute("data-title") || filterKey;
                        var foundErr = 0;
                        yearContainer.querySelectorAll(".beleg-item-details").forEach(function(doc) {
                            var contribs = [];
                            try {
                                contribs = JSON.parse(doc.getAttribute("data-contributions") || "[]");
                            } catch (err2) {
                                contribs = [];
                            }
                            if (contribs.indexOf(filterKey) !== -1) {
                                doc.style.display = "";
                                foundErr++;
                            } else {
                                doc.style.display = "none";
                            }
                        });

                        var filterContainerErr = yearContainer.querySelector(".active-filter-container");
                        var filterTextErr = yearContainer.querySelector(".active-filter-text");
                        if (filterContainerErr && filterTextErr) {
                            filterContainerErr.style.display = "flex";
                            filterTextErr.innerHTML =
                                "<div style=\'width:100%;\'>" +
                                "<div style=\'font-size:15px;margin-bottom:4px;\'>Gefiltert nach Tag: <strong>" +
                                titleErr.replace(/&/g, "&amp;").replace(/</g, "&lt;") +
                                "</strong> <span style=\'opacity:0.9\'>(" + foundErr + " Belege)</span></div>" +
                                "</div>";
                        }

                        var belegeAccErr = yearContainer.querySelector(".belege-accordion");
                        if (belegeAccErr && belegeAccErr.scrollIntoView) {
                            belegeAccErr.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                        return;
                    }

                    if (!targetClass || !filterType) return;

                    clearYearAccordionDrill(yearContainer);

                    var targetAcc = yearContainer.querySelector("." + targetClass);
                    yearContainer.querySelectorAll("details.main-accordion").forEach(function(d) {
                        d.removeAttribute("open");
                    });
                    if (targetAcc) {
                        targetAcc.setAttribute("open", "");
                    }

                    if (filterType === "mismatch" && targetAcc) {
                        targetAcc.querySelectorAll(".tax-row-wrapper").forEach(function(row) {
                            row.style.display = row.classList.contains("mismatch-row") ? "" : "none";
                        });
                        targetAcc.querySelectorAll(".e1-sub-section").forEach(function(sub) {
                            sub.style.display = sub.querySelector(".tax-row-wrapper.mismatch-row") ? "" : "none";
                        });
                        targetAcc.querySelectorAll(".e1-section-block").forEach(function(block) {
                            block.style.display = block.querySelector(".tax-row-wrapper.mismatch-row") ? "" : "none";
                        });
                        targetAcc.querySelectorAll(".u1-section-block").forEach(function(block) {
                            block.style.display = block.querySelector(".tax-row-wrapper.mismatch-row") ? "" : "none";
                        });
                        if (targetAcc.scrollIntoView) {
                            targetAcc.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                        return;
                    }

                    if (filterType === "sliced-missing" || filterType === "unassigned") {
                        var belegeAcc = yearContainer.querySelector(".belege-accordion");
                        if (!belegeAcc) return;

                        var foundCount = 0;
                        var titleText = "";

                        yearContainer.querySelectorAll(".beleg-item-details").forEach(function(doc) {
                            var ok = filterType === "sliced-missing"
                                ? (doc.getAttribute("data-sliced-missing") === "1")
                                : (doc.getAttribute("data-unassigned") === "1");
                            doc.style.display = ok ? "" : "none";
                            if (ok) {
                                foundCount++;
                            }
                        });

                        titleText = filterType === "sliced-missing"
                            ? "Sliced-Rechnungen ohne Gewerbeschein-Zuordnung"
                            : "Belege ohne Gewerbe („Nicht zugeordnet“)";

                        var filterContainer = yearContainer.querySelector(".active-filter-container");
                        var filterText = yearContainer.querySelector(".active-filter-text");
                        if (filterContainer && filterText) {
                            filterContainer.style.display = "flex";
                            filterText.innerHTML =
                                "<div style=\'width:100%;\'>" +
                                "<div style=\'font-size:15px;margin-bottom:4px;\'>Drill-Down Belege: <strong>" +
                                titleText.replace(/&/g, "&amp;").replace(/</g, "&lt;") +
                                "</strong> <span style=\'opacity:0.9\'>(" + foundCount + " gefunden)</span></div>" +
                                "</div>";
                        }

                        if (belegeAcc.scrollIntoView) {
                            belegeAcc.scrollIntoView({ behavior: "smooth", block: "start" });
                        }
                    }
                });
            });

            document.querySelectorAll(".beleg-filter-btn").forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var filterKey = this.getAttribute("data-filter");
                    var title = this.getAttribute("data-title") || "";

                    var yearContainer = this.closest(".year-container");
                    if (!yearContainer) return;

                    clearYearAccordionDrill(yearContainer);

                    var rowWrapper = this.closest(".tax-row-wrapper");
                    var statsHtml = "";
                    if (rowWrapper) {
                        var wertArt = "";
                        var dtLab = rowWrapper.getAttribute("data-type-label");
                        if (dtLab && dtLab.trim()) {
                            wertArt = dtLab.trim();
                        } else {
                            var labEl = rowWrapper.querySelector(".type-label");
                            if (labEl) wertArt = labEl.innerText.trim();
                        }
                        var basisHtml = "";
                        if (wertArt !== "") {
                            basisHtml =
                                \'<div style="width:100%;margin-bottom:6px;font-size:12px;opacity:0.95;"><strong>Wertart:</strong> \' +
                                wertArt.replace(/&/g,"&amp;").replace(/</g,"&lt;") +
                                \'</div>\';
                        }
                        var origEl = rowWrapper.querySelector(".original-soll");
                        var origSollText = origEl ? origEl.innerText : "";
                        var korrInput = rowWrapper.querySelector(".korrektur-input");
                        var korrRaw = korrInput ? korrInput.value.trim() : "";
                        var korrText = korrRaw !== "" ? korrRaw + " €" : "–";
                        var istEl = rowWrapper.querySelector(".ist-label");
                        var istTextClean = istEl ? istEl.innerText : "";
                        if (!istTextClean) {
                            var istCent = parseInt(rowWrapper.getAttribute("data-ist"), 10) || 0;
                            istTextClean = (istCent / 100).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " €";
                        }
                        var diffLabel = rowWrapper.querySelector(".diff-label");
                        var diffText = diffLabel ? diffLabel.innerText : "";
                        var diffContainer = rowWrapper.querySelector(".diff-container");
                        var diffColor = "#fff";
                        if (diffContainer) {
                            diffColor = window.getComputedStyle(diffContainer).color || diffContainer.style.color || "#fff";
                        }
                        statsHtml =
                            basisHtml +
                            \'<div style="display:flex; flex-wrap:wrap; gap:12px 20px; margin-top:8px; font-size:13px; background:rgba(0,0,0,0.15); padding:8px 12px; border-radius:4px; border: 1px solid rgba(255,255,255,0.2);">\' +
                            \'<div><span style="opacity:0.85">Original-Soll:</span> <strong>\' + origSollText.replace(/&/g,"&amp;").replace(/</g,"&lt;") + \'</strong></div>\' +
                            \'<div><span style="opacity:0.85">Korrektur-Soll:</span> <strong>\' + korrText.replace(/&/g,"&amp;").replace(/</g,"&lt;") + \'</strong></div>\' +
                            \'<div><span style="opacity:0.85">Berechnet-Ist:</span> <strong>\' + istTextClean.replace(/&/g,"&amp;").replace(/</g,"&lt;") + \'</strong></div>\' +
                            \'<div><span style="opacity:0.85">Diff:</span> <strong style="background: rgba(255,255,255,0.92); color:\' + diffColor + \'; padding: 2px 6px; border-radius: 3px;">\' + diffText.replace(/&/g,"&amp;").replace(/</g,"&lt;") + \'</strong></div>\' +
                            \'</div>\';
                    }

                    yearContainer.querySelectorAll("details.main-accordion").forEach(function(d) {
                        d.removeAttribute("open");
                    });

                    var belegeAcc = yearContainer.querySelector(".belege-accordion");
                    if (belegeAcc) belegeAcc.setAttribute("open", "");

                    var foundCount = 0;
                    yearContainer.querySelectorAll(".beleg-item-details").forEach(function(doc) {
                        var contribs = [];
                        try {
                            contribs = JSON.parse(doc.getAttribute("data-contributions") || "[]");
                        } catch (err) { contribs = []; }
                        if (contribs.indexOf(filterKey) !== -1) {
                            doc.style.display = "block";
                            foundCount++;
                        } else {
                            doc.style.display = "none";
                        }
                    });

                    var filterContainer = yearContainer.querySelector(".active-filter-container");
                    var filterText = yearContainer.querySelector(".active-filter-text");
                    if (filterContainer && filterText) {
                        filterContainer.style.display = "flex";
                        filterText.innerHTML =
                            \'<div style="width:100%;">\' +
                            \'<div style="font-size:15px; margin-bottom:4px;">Gefiltert nach: <strong>\' +
                            title.replace(/&/g,"&amp;").replace(/</g,"&lt;") +
                            \'</strong> <span style="opacity:0.9">(\' + foundCount + \' Belege gefunden)</span></div>\' +
                            statsHtml +
                            \'</div>\';
                    }

                    if (belegeAcc) belegeAcc.scrollIntoView({behavior: "smooth", block: "start"});
                });
            });

            document.querySelectorAll(".reset-filter-btn").forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    var yearContainer = this.closest(".year-container");
                    if (!yearContainer) return;

                    clearYearAccordionDrill(yearContainer);

                    yearContainer.querySelectorAll(".beleg-item-details").forEach(function(doc) {
                        doc.style.display = "block";
                    });

                    var filterContainer = yearContainer.querySelector(".active-filter-container");
                    if (filterContainer) filterContainer.style.display = "none";
                });
            });

            function parseCurrencyToCents(val) {
                if (!val || String(val).trim() === "") return null;
                var cleanVal = String(val).replace(/\\./g, "").replace(",", ".");
                var parsed = parseFloat(cleanVal);
                return isNaN(parsed) ? null : Math.round(parsed * 100);
            }

            function formatCentsToCurrency(cents) {
                return (cents / 100).toLocaleString("de-DE", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " €";
            }

            function debounce(func, wait) {
                var timeout;
                return function() {
                    var args = arguments, ctx = this;
                    clearTimeout(timeout);
                    timeout = setTimeout(function() { func.apply(ctx, args); }, wait);
                };
            }

            var saveToServer = debounce(function(rowId, korrVal, notizVal) {
                if (!nonceEl || !nonceEl.value) {
                    console.error("Tax correction save: nonce missing");
                    return;
                }
                var url = new URL(window.location.href);
                url.searchParams.set("save_tax_correction", "1");
                fetch(url.toString(), {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type": "application/json",
                        "X-WP-Nonce": nonceEl.value
                    },
                    body: JSON.stringify({ id: rowId, korrektur: korrVal, notiz: notizVal })
                }).then(function(res) { return res.json(); }).then(function(data) {
                    console.log("Korrektur gespeichert:", rowId, data);
                }).catch(function(err) { console.error("Speichern fehlgeschlagen:", err); });
            }, 600);

            document.querySelectorAll(".tax-row-wrapper").forEach(function(wrapper) {
                var inputKorr = wrapper.querySelector(".korrektur-input");
                var inputNotiz = wrapper.querySelector(".notiz-input");
                if (!inputKorr || !inputNotiz) return;

                var rowId = wrapper.id;
                var sollCent = parseInt(wrapper.getAttribute("data-soll"), 10) || 0;
                var istCent = parseInt(wrapper.getAttribute("data-ist"), 10) || 0;

                var diffLabel = wrapper.querySelector(".diff-label");
                var diffContainer = wrapper.querySelector(".diff-container");
                var notizContainer = wrapper.querySelector(".notiz-container");
                if (!diffLabel || !diffContainer || !notizContainer) return;

                var updateRow = function(triggerSave) {
                    var rawKorr = inputKorr.value;
                    var korrCent = parseCurrencyToCents(rawKorr);

                    var targetSoll = sollCent;
                    var hasKorrektur = false;

                    if (korrCent !== null) {
                        targetSoll = korrCent;
                        hasKorrektur = true;
                        notizContainer.style.display = "block";
                    } else {
                        notizContainer.style.display = "none";
                    }

                    var delta = istCent - targetSoll;
                    var isOk = Math.abs(delta) <= 1;

                    diffLabel.innerText = formatCentsToCurrency(delta);

                    if (hasKorrektur) {
                        wrapper.style.background = isOk ? "#d6eaf8" : "#fadbd8";
                        diffContainer.style.color = isOk ? "#2980b9" : "#c0392b";
                    } else {
                        wrapper.style.background = isOk ? "#e8f8f5" : "#fdedec";
                        diffContainer.style.color = isOk ? "#27ae60" : "#c0392b";
                    }

                    if (triggerSave) {
                        saveToServer(rowId, rawKorr, inputNotiz.value);
                    }
                };

                inputKorr.addEventListener("input", function() { updateRow(true); });
                inputNotiz.addEventListener("input", function() { updateRow(true); });

                updateRow(false);
            });
        });
        </script>
        ';

        echo "</div>";
        if ( $kill_script ) { die(); }
    }
}
