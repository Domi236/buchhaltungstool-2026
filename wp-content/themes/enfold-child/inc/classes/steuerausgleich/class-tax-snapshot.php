<?php
if ( ! defined( 'ABSPATH' ) ) { die; }

class Class_Tax_Snapshot {

    public static function init_storage() {
        // Wird vorerst übersprungen, da wir direkt im Browser testen
    }

    public static function run_historical_tests() {
        $ground_truth = [
            '2021' => [ 'kennzahl_000' => 297419, 'kennzahl_020' => 10700, 'kennzahl_022' => 286719 ],
            '2022' => [ 'kennzahl_000' => 332783, 'kennzahl_022' => 332783, 'kennzahl_320' => -973434 ],
            '2023' => [ 'kennzahl_000' => 1671098, 'kennzahl_020' => 119303, 'kennzahl_022' => 1551795, 'kennzahl_060' => 190420, 'kennzahl_320' => -39580 ]
        ];

        echo '<div style="background:#fff; color:#333; padding:30px; font-family:sans-serif; border: 3px solid #222; margin: 20px;">';
        echo '<h1 style="color:#27ae60;">🚀 TEST ENGINE GESTARTET!</h1>';
        echo '<p>Abhängigkeiten entfernt. Direkte Bildschirmausgabe aktiv.</p>';
        echo '<ul>';

        // 1. Berechnungs-Klasse sicher laden
        $calc_file = get_stylesheet_directory() . '/inc/classes/steuerausgleich/class-calucalation.php';
        if ( file_exists( $calc_file ) ) {
            require_once $calc_file;
        }

        // 2. Klasse instanziieren (mit oder ohne Tippfehler)
        $calc = null;
        if ( class_exists( 'Calculation' ) ) {
            $calc = new Calculation();
        } elseif ( class_exists( 'Calucalation' ) ) {
            $calc = new Calucalation();
        }

        // 3. Status ausgeben
        if ( ! $calc ) {
            echo '<li style="color:#c0392b;"><strong>FEHLER:</strong> Berechnungs-Klasse (class-calucalation.php) konnte nicht geladen werden!</li>';
        } else {
            echo '<li style="color:#27ae60;"><strong>ERFOLG:</strong> Alte Berechnungs-Klasse erfolgreich verknüpft.</li><br>';

            foreach ( $ground_truth as $year => $data ) {
                echo "<li>Initialisiere Datenabgleich für das Jahr <strong>{$year}</strong>...</li>";
            }
        }

        echo '</ul></div>';

        // Harter Stop: Überschreibe das standard wp_die() aus der functions.php
        die();
    }
}
