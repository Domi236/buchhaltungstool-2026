<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

class Class_Tax_Snapshot {
    public static function init_storage() {}

    public static function run_historical_tests() {
        $json_path = plugin_dir_path( __FILE__ ) . 'tax-snapshot-data.json';
        if ( ! file_exists( $json_path ) ) {
            echo "<div style='padding:20px; background:#fdedec; color:#c0392b;'>Fehler: Die Datei tax-snapshot-data.json wurde nicht gefunden.</div>";
            return;
        }

        $data = json_decode( file_get_contents( $json_path ), true );

        $ground_truth = $data['ground_truth'];
        if ( ! isset( $ground_truth['2025'] ) ) $ground_truth['2025'] = $data['blank_year'];
        if ( ! isset( $ground_truth['2026'] ) ) $ground_truth['2026'] = $data['blank_year'];

        $e1_struktur = $data['e1_struktur'];
        $u1_struktur = $data['u1_struktur'];

        echo '<div style="background:#f3f4f6; padding:20px; font-family: Arial, sans-serif;">';

        foreach ( $ground_truth as $year => $expected ) {
            $calculated = Class_Tax_Engine::calculate_year( $year );
            $sd = $calculated['stammdaten'];

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

            // =========================================================
            // VORAB-BERECHNUNG DER SYSTEMFEHLER
            // =========================================================
            $e1_mismatch_count          = 0;
            $u1_mismatch_count          = 0;
            $unassigned_count           = 0;
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
                    if ( $doc['gewerbe'] === 'Nicht zugeordnet' )                                                          $unassigned_count++;
                    if ( $doc['type'] === 'Fehlt' )                                                                        $missing_type_count++;
                    if ( $doc['is_uploaded'] === '❌ Nein' )                                                               $not_uploaded_count++;
                    if ( $doc['auslandsrechnung'] === 'Ja' )                                                               $foreign_count++;
                    if ( $doc['fremdwaehrung'] === 'Ja' )                                                                  $foreign_currency_count++;
                    if ( empty( $doc['payment_date'] ) )                                                                   $missing_payment_date_count++;
                    if ( $doc['has_linked'] === '🔗 Ja' )                                                                  $linked_docs_count++;
                    if ( $doc['type'] === 'Ausgabe' && $doc['abschreibung'] === 'Nicht definiert' )                        $missing_abschreibung_count++;
                    if ( $doc['has_non_20_tax'] )                                                                          $tax_not_20_count++;
                    if ( $doc['type'] === 'Einnahme' && $doc['partner_name'] === 'Kein Kunde verknüpft' )                  $missing_client_count++;
                    if ( ( $doc['type'] === 'Ausgabe' || $doc['type'] === 'Fehlt' ) && $doc['partner_name'] === 'Kein Verkäufer angegeben' ) $missing_vendor_count++;
                }
            }

            if ( isset( $expected['e1'] ) ) {
                foreach ( $e1_struktur as $gewerbe_name => $struktur ) {
                    foreach ( [ 'einnahmen', 'ausgaben' ] as $art ) {
                        foreach ( $struktur[ $art ] as $kz => $text ) {
                            $soll = $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0;
                            $ist  = $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0;
                            if ( $soll !== $ist ) $e1_mismatch_count++;
                        }
                    }
                }
                foreach ( $ausserbetrieblich['items'] as $kz => $text ) {
                    if ( strpos( $kz, 'title_' ) !== 0 ) {
                        $soll = $expected['e1']['Ausserbetrieblich'][ $kz ] ?? 0;
                        if ( $soll !== 0 ) $e1_mismatch_count++;
                    }
                }
            }

            if ( isset( $expected['u1'] ) ) {
                foreach ( $u1_struktur as $section_title => $fields ) {
                    foreach ( $fields as $kz => $text ) {
                        $soll = $expected['u1'][ $kz ] ?? 0;
                        $ist  = $calculated['u1'][ $kz ] ?? 0;
                        if ( $soll !== $ist ) $u1_mismatch_count++;
                    }
                }
            }

            $dynamic_system_errors = [];
            if ( $e1_mismatch_count > 0 )          $dynamic_system_errors[] = "{$e1_mismatch_count} Unstimmigkeiten im E1 gefunden";
            if ( $u1_mismatch_count > 0 )          $dynamic_system_errors[] = "{$u1_mismatch_count} Unstimmigkeiten im U1 gefunden";
            if ( $unassigned_count > 0 )            $dynamic_system_errors[] = "{$unassigned_count} nicht zugeordnete Einträge gefunden";
            if ( $missing_type_count > 0 )          $dynamic_system_errors[] = "{$missing_type_count} Einträge fehlt der Typ";
            if ( $not_uploaded_count > 0 )          $dynamic_system_errors[] = "{$not_uploaded_count} Rechnungen wurden nicht hochgeladen";
            if ( $foreign_count > 0 )               $dynamic_system_errors[] = "{$foreign_count} Rechnungen sind aus dem Ausland (muss überprüft werden)";
            if ( $foreign_currency_count > 0 )      $dynamic_system_errors[] = "{$foreign_currency_count} Rechnungen haben andere Währung";
            if ( $missing_payment_date_count > 0 )  $dynamic_system_errors[] = "{$missing_payment_date_count} Rechnungen haben keinen Zahlungseingang";
            if ( $linked_docs_count > 0 )           $dynamic_system_errors[] = "{$linked_docs_count} Rechnungen haben verknüpfte Dokumente (muss überprüft werden)";
            if ( $missing_client_count > 0 )        $dynamic_system_errors[] = "{$missing_client_count} Einnahmen haben keinen Kunden gewählt";
            if ( $missing_vendor_count > 0 )        $dynamic_system_errors[] = "{$missing_vendor_count} Ausgaben haben keinen Verkäufer gewählt";
            if ( $missing_abschreibung_count > 0 )  $dynamic_system_errors[] = "{$missing_abschreibung_count} Ausgaben fehlt die Abschreibungsart";
            if ( $tax_not_20_count > 0 )            $dynamic_system_errors[] = "{$tax_not_20_count} Rechnungen haben einen Steuersatz ungleich 20% (muss überprüft werden)";

            $all_system_errors = array_merge( $dynamic_system_errors, $sd['systemfehler'] );
            // =========================================================

            $open_tag = ( $year === '2024' ) ? 'open' : '';
            echo "<details {$open_tag} style='margin-bottom: 20px; border: 1px solid #ccc; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>";
            echo "<summary style='background: #fff; padding: 15px; font-size: 18px; font-weight: bold; cursor: pointer; border-bottom: 1px solid #ccc; outline: none;'>";
            echo "📆 Veranlagungsjahr {$year}";
            echo "</summary>";
            echo "<div style='padding: 20px;'>";

            // 1. DASHBOARD
            echo "<details open style='margin-bottom: 20px; border: 1px solid #8e44ad;'>";
            echo "<summary style='background: #9b59b6; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>💡 Infos, Fehler & Daten</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";

            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>Gesamtwerte E1 und U1</h3>";
            echo "<p style='font-size:13px; line-height:1.5;'><strong>Gesamtsaldo der Einkünfte aus selbständiger Arbeit sowie Einkünfteverteilungen</strong><br>Summe Kennzahl 320<br>Summe aus allen Beilagen E1a, E1a-K und E11 sowie den Kennzahlen 321 bis 501 - (Kennzahl 320) <strong>{$sd['e1_320']}</strong></p>";
            echo "<p style='font-size:13px; line-height:1.5; margin-bottom:20px;'><strong>Lieferungen, sonstige Leistungen und Eigenverbrauch</strong><br>Gesamtbetrag der Bemessungsgrundlagen des Veranlagungszeitraumes {$year} für Lieferungen und sonstige Leistungen (ohne den nachstehend angeführten Eigenverbrauch) einschließlich Anzahlungen (jeweils ohne Umsatzsteuer) - (Kennzahl 000) <strong>{$sd['u1_000']}</strong></p>";

            $soll_netto  = $expected['u1']['kennzahl_000'] ?? 0;
            $ist_netto   = $calculated['u1']['kennzahl_000'] ?? 0;
            $soll_tax    = ( ( $expected['u1']['kennzahl_022'] ?? 0 ) * 0.2 )
                         + ( ( $expected['u1']['kennzahl_029'] ?? 0 ) * 0.1 )
                         + ( ( $expected['u1']['kennzahl_006'] ?? 0 ) * 0.13 )
                         + ( ( $expected['u1']['kennzahl_037'] ?? 0 ) * 0.19 );
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
                foreach ( $sd['fehlerliste'] as $f ) { echo "<li><span style='color:#f39c12;'>⚠️</span> {$f}</li>"; }
                echo "</ul>";
            } else {
                echo "<p style='font-size:13px;'><em>Keine manuellen Fehler hinterlegt.</em></p>";
            }

            echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-top: 20px; margin-bottom: 10px;'>Systemfehler</h3>";
            if ( ! empty( $all_system_errors ) ) {
                echo "<ul style='font-size:13px; list-style-type:none; padding-left:0;'>";
                foreach ( $all_system_errors as $sf ) { echo "<li style='margin-bottom:5px;'><span style='color:#c0392b;'>⛔</span> <strong>System-Meldung:</strong> {$sf}</li>"; }
                echo "</ul>";
            } else {
                echo "<p style='font-size:13px;'><em>Keine Systemfehler für dieses Jahr erkannt. Alles im grünen Bereich!</em></p>";
            }
            echo "</div></details>";

            // HILFSFUNKTION RENDERN
            $render_row = function( $kz, $text, $soll_cent, $ist_cent ) {
                $delta = $ist_cent - $soll_cent;
                $bg    = ( $delta === 0 ) ? '#e8f8f5' : '#fdedec';
                $color = ( $delta === 0 ) ? '#27ae60' : '#c0392b';
                echo "<div style='display:flex; justify-content: space-between; border-bottom:1px solid #ddd; padding: 10px; background: {$bg}; font-size: 13px;'>";
                echo "<div style='width: 60%; padding-right:15px; line-height: 1.4;'>" . nl2br( $text ) . "</div>";
                echo "<div style='width: 40%; display:flex; justify-content: space-between; text-align:right;'>";
                echo "<div style='width:33%;'>Soll:<br><strong>" . number_format( $soll_cent / 100, 2, ',', '.' ) . " €</strong></div>";
                echo "<div style='width:33%;'>Ist:<br><strong>" . number_format( $ist_cent / 100, 2, ',', '.' ) . " €</strong></div>";
                echo "<div style='width:33%; color:{$color};'>Diff:<br><strong>" . number_format( $delta / 100, 2, ',', '.' ) . " €</strong></div>";
                echo "</div></div>";
            };

            // 2. EINKOMMENSTEUER (E1) ACCORDION
            echo "<details {$open_tag} style='margin-bottom: 20px; border: 1px solid #16a085;'>";
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

            echo "<h3 style='font-size: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;'>Angaben zum Betrieb</h3>";
            echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>";
            echo "<tr><td style='width: 200px; padding: 4px 0;'>Anschrift</td><td>Brauhausstraße 8/Stg. 4/8</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Postleitzahl</td><td>2351</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Ort</td><td>Wiener Neudorf</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Land</td><td>Österreich</td></tr>";
            echo "<tr><td colspan='2' style='padding: 10px 0 4px 0; font-weight: bold;'>Gewinnermittlungsarten</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Vollständige Einnahmen-Ausgaben-Rechnung gemäß § 4 Abs. 3</td><td>{$sd['ear']}</td></tr>";
            $system_label = ( $sd['netto_system'] === 'Ja' ) ? 'USt-Nettosystem' : 'USt-Bruttosystem';
            echo "<tr><td style='padding: 4px 0;'>{$system_label}</td><td>Ja</td></tr>";
            echo "<tr><td colspan='2' style='padding: 10px 0 4px 0; font-weight: bold;'>Allgemeine Angaben zur Ermittlung der Einkünfte</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Beginn des Wirtschaftsjahres (TTMMJJJJ)</td><td>01.01.{$year}</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Ende des Wirtschaftsjahres (TTMMJJJJ)</td><td>31.12.{$year}</td></tr>";
            echo "</table>";
            echo "</div>";

            if ( isset( $expected['e1'] ) ) {
                foreach ( $e1_struktur as $gewerbe_name => $struktur ) {
                    echo "<div style='margin-bottom: 30px;'>";
                    echo "<h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>" . $struktur['title'] . "</h3>";
                    echo "<p style='font-weight: bold; margin: 15px 0 5px 0;'>Gewinnermittlung<br>Erträge/Betriebseinnahmen</p>";
                    foreach ( $struktur['einnahmen'] as $kz => $text ) {
                        $render_row( $kz, $text, $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0, $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0 );
                    }
                    echo "<p style='font-weight: bold; margin: 15px 0 5px 0;'>Aufwendungen/Betriebsausgaben</p>";
                    foreach ( $struktur['ausgaben'] as $kz => $text ) {
                        $render_row( $kz, $text, $expected['e1'][ $gewerbe_name ][ $kz ] ?? 0, $calculated['e1'][ $gewerbe_name ][ $kz ] ?? 0 );
                    }
                    echo "</div>";
                }
            }

            echo "<div style='margin-top: 40px; border-top: 2px solid #ccc; padding-top: 20px;'>";
            echo "<h2 style='font-size: 18px; margin-bottom: 15px;'>" . $ausserbetrieblich['title'] . "</h2>";
            foreach ( $ausserbetrieblich['items'] as $kz => $text ) {
                if ( strpos( $kz, 'title_' ) === 0 ) {
                    echo "<p style='font-weight: bold; font-size: 13px; margin: 15px 0 5px 0; white-space: pre-line;'>" . $text . "</p>";
                    continue;
                }
                $render_row( $kz, $text, $expected['e1']['Ausserbetrieblich'][ $kz ] ?? 0, 0 );
            }
            echo "</div></div></details>";

            // 3. UMSATZSTEUER (U1) ACCORDION
            echo "<details {$open_tag} style='margin-bottom: 20px; border: 1px solid #2980b9;'>";
            echo "<summary style='background: #2980b9; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📊 Umsatzsteuererklärung (U1)</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";
            echo "<div style='margin-bottom: 30px; font-size: 13px;'>";
            echo "<h2 style='font-size: 16px; border-bottom: 1px solid #ccc; padding-bottom: 5px;'>Unternehmensdaten</h2>";
            echo "<table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><td style='width: 200px; padding: 4px 0;'>Firmenname</td><td>Fila Dominik</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Anschrift</td><td>Brauhausstraße 8/Stg. 4/8</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>PLZ</td><td>2351</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Ort</td><td>Wiener Neudorf</td></tr>";
            echo "<tr><td style='padding: 4px 0;'>Steuernummer</td><td>16 346/1429</td></tr>";
            echo "</table></div>";

            if ( isset( $expected['u1'] ) ) {
                foreach ( $u1_struktur as $section_title => $fields ) {
                    echo "<div style='margin-bottom: 30px;'><h3 style='font-size: 15px; background: #eee; padding: 8px; margin-bottom: 10px;'>{$section_title}</h3>";
                    foreach ( $fields as $kz => $text ) {
                        $render_row( $kz, $text, $expected['u1'][ $kz ] ?? 0, $calculated['u1'][ $kz ] ?? 0 );
                    }
                    echo "</div>";
                }
                echo "<div style='margin-top: 30px; font-size: 13px; border-top: 1px solid #eee; padding-top: 15px;'>";
                echo "<p>Kammerumlagepflicht(§ 122 Wirtschaftskammergesetz) liegt vor: <strong>Ja</strong></p>";
                echo "<p>An Kammerumlage wurde entrichtet: <strong>" . number_format( ( $expected['u1']['kammerumlage'] ?? 0 ) / 100, 2, ',', '.' ) . " €</strong></p>";
                echo "</div>";
            }
            echo "</div></details>";

            // 3.5 UVA
            echo "<details style='margin-bottom: 20px; border: 1px solid #e67e22;'>";
            echo "<summary style='background: #e67e22; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📅 Umsatzsteuervoranmeldung (UVA)</summary>";
            echo "<div style='padding: 15px; background: #fff;'>";
            echo "<h3 style='font-size:16px; color:#e67e22; border-bottom:1px solid #eee; margin-bottom:15px; padding-bottom:5px;'>Quartalsweise Auswertung</h3>";
            $q_names = [ 1 => '1. Quartal (Jänner - März)', 2 => '2. Quartal (April - Juni)', 3 => '3. Quartal (Juli - September)', 4 => '4. Quartal (Oktober - Dezember)' ];
            foreach ( $q_names as $q_idx => $q_name ) {
                echo "<details style='margin-bottom: 8px; border: 1px solid #ddd; background: #fff;'><summary style='padding: 10px; background: #fdf2e9; font-weight: bold; cursor: pointer; outline: none;'>{$q_name}</summary><div style='padding: 15px;'>";
                foreach ( $u1_struktur as $sect => $fields ) {
                    echo "<h4 style='font-size:14px; margin:15px 0 10px 0; border-bottom: 1px dashed #eee; padding-bottom: 5px;'>{$sect}</h4>";
                    foreach ( $fields as $kz => $txt ) { $render_row( $kz, $txt, 0, $calculated['periods']['quarters'][ $q_idx ][ $kz ] ?? 0 ); }
                }
                echo "</div></details>";
            }
            echo "<h3 style='font-size:16px; color:#e67e22; border-bottom:1px solid #eee; margin-top:30px; margin-bottom:15px; padding-bottom:5px;'>Monatliche Auswertung</h3>";
            $m_names = [ 1 => 'Jänner', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember' ];
            foreach ( $m_names as $m_idx => $m_name ) {
                echo "<details style='margin-bottom: 8px; border: 1px solid #ddd; background: #fff;'><summary style='padding: 10px; background: #fdf2e9; font-weight: bold; cursor: pointer; outline: none;'>{$m_name}</summary><div style='padding: 15px;'>";
                foreach ( $u1_struktur as $sect => $fields ) {
                    echo "<h4 style='font-size:14px; margin:15px 0 10px 0; border-bottom: 1px dashed #eee; padding-bottom: 5px;'>{$sect}</h4>";
                    foreach ( $fields as $kz => $txt ) { $render_row( $kz, $txt, 0, $calculated['periods']['months'][ $m_idx ][ $kz ] ?? 0 ); }
                }
                echo "</div></details>";
            }
            echo "</div></details>";

            // 4. BELEGE
            echo "<details {$open_tag} style='margin-bottom: 20px; border: 1px solid #34495e;'>";
            echo "<summary style='background: #34495e; color: #fff; padding: 12px; font-size: 16px; font-weight: bold; cursor: pointer; outline: none;'>📄 Verarbeitete Belege & Artikel (Detailliert)</summary>";
            echo "<div style='padding: 15px; background: #ecf0f1;'>";

            if ( ! empty( $calculated['document_details'] ) ) {
                foreach ( $calculated['document_details'] as $doc ) {
                    $is_in = ( $doc['type'] === 'Einnahme' );

                    if ( $doc['type'] === 'Einnahme' ) {
                        $color = '#27ae60'; $bg = '#e8f8f5';
                    } elseif ( $doc['type'] === 'Ausgabe' ) {
                        $color = '#c0392b'; $bg = '#fdedec';
                    } else {
                        $color = '#f39c12'; $bg = '#fcf3cf';
                    }

                    $netto_eur  = number_format( $doc['netto'] / 100, 2, ',', '.' ) . ' €';
                    $tax_eur    = number_format( $doc['tax'] / 100, 2, ',', '.' ) . ' €';
                    $brutto_eur = number_format( ( $doc['netto'] + $doc['tax'] ) / 100, 2, ',', '.' ) . ' €';

                    echo "<details style='background: #fff; border: 1px solid #bdc3c7; margin-bottom: 8px; border-radius: 4px;'>";
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
            echo "</div></details></div></details>";
        }
        echo "</div>";
        die();
    }
}
