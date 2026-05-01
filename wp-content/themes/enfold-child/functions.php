<?php
/**
 * ACF Data Viewer
 */
require_once get_stylesheet_directory() . '/inc/classes/class_data_viewer.php';

/**
 * ResDebug functions
 */
require_once get_stylesheet_directory() . '/inc/partycrowd-debug-functions.php';

/**
 * PartyCrowd Functions
 */
require_once get_stylesheet_directory() . '/inc/partycrowd-functions.php';

/**
 * Acf Functions
 */
require_once get_stylesheet_directory() . '/inc/acf.php';

/**
 * Image Sizes
 */
require_once get_stylesheet_directory() . '/inc/image-sizes.php';

/**
 * Enqueu styles and js
 */
require_once get_stylesheet_directory() . '/inc/enqueue.php';

/**
 * Add Fonts
 */
require_once get_stylesheet_directory() . '/inc/fonts.php';

/**
 * Enfold
 */
require_once get_stylesheet_directory() . '/inc/enfold.php';

/**
 * Shortcodes
 */
require_once get_stylesheet_directory() . '/inc/shortcodes.php';

/**
 * Widgets
 */
require_once get_stylesheet_directory() . '/inc/widgets.php';
/**
 * GravityForm functions
 */
require_once get_stylesheet_directory() . '/inc/gravity.php';

/**
 * facetwp functions
 */
require_once get_stylesheet_directory() . '/inc/facetwp.php';

/**
 * custom footer functions
 */
require_once get_stylesheet_directory() . '/inc/footer.php';

/**
 * Enfold element output functions
 */
require_once get_stylesheet_directory() . '/inc/enfold-element-output-functions.php';

/**
 * Steuerausgleich caluclator
 */
require_once get_stylesheet_directory() . '/inc/classes/steuerausgleich/class-calucalation.php';

/**
 * Tax Snapshot Engine
 */
require_once get_stylesheet_directory() . '/inc/classes/steuerausgleich/class-tax-snapshot.php';

/**
 * Tax Engine (Kennzahlen-Berechnung)
 */
require_once get_stylesheet_directory() . '/inc/classes/steuerausgleich/class-tax-engine.php';

/**
 * Sliced Invoices Helper
 */
require_once get_stylesheet_directory() . '/inc/sliced-invoices-helper.php';

/**
 * Sliced template modules
 */
require_once get_stylesheet_directory() . '/sliced/template-tags/sliced-tags-display-modules.php';

/**
 * für mitarbeiter pwa
 */
require_once get_stylesheet_directory() . '/inc/api-endpoints-remote.php';


// --- START: EINZIGER TEST TRIGGER ---
add_action( 'wp_loaded', function() {
    if ( isset( $_GET['run_tax_test'] ) && $_GET['run_tax_test'] === '1' ) {
        $file = get_stylesheet_directory() . '/inc/classes/steuerausgleich/class-tax-snapshot.php';

        if ( ! file_exists( $file ) ) {
            wp_die( "<h1>Fehler 1</h1><p>Datei fehlt: {$file}</p>" );
        }

        require_once $file;

        if ( ! class_exists( 'Class_Tax_Snapshot' ) ) {
            wp_die( "<h1>Fehler 2</h1><p>Datei wurde geladen, aber die Klasse existiert nicht. Hast du 'class-tax-snapshot.php' wirklich abgespeichert?</p>" );
        }

        try {
            Class_Tax_Snapshot::init_storage();
            Class_Tax_Snapshot::run_historical_tests();
            wp_die( "<h1>TEST ERFOLGREICH GESTARTET!</h1><p>Bitte prüfe jetzt deine Log-Datei im /logs/ Ordner.</p>" );
        } catch( Throwable $e ) {
            wp_die( "<h1>Laufzeit-Fehler</h1><p>" . $e->getMessage() . "</p>" );
        }
    }
});
// --- ENDE: EINZIGER TEST TRIGGER ---


Class_Tax_Snapshot::init();
