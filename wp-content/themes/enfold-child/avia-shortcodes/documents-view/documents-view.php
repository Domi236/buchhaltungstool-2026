<?php

// Don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

if (!class_exists('documents_view')) {
    class documents_view extends aviaShortcodeTemplate
    {

        /**
         * Create the config array for the shortcode button
         */
        function shortcode_insert_button()
        {
            $this->config['version'] = '1.0';
            $this->config['self_closing'] = 'yes';
            $this->config['self_closing'] = 'yes';

            $this->config['name'] = __('Documents View', 'avia_framework');
            $this->config['tab'] = __('Autory', 'avia_framework');
            $this->config['icon'] = AviaBuilder::$path['imagesURL'] . 'sc-team.png';
            $this->config['order'] = 93;
            $this->config['target'] = 'avia-target-insert';
            $this->config['shortcode'] = 'documents_view';
            $this->config['modal_data'] = array('modal_class' => 'mediumscreen');
            $this->config['tooltip'] = __('', 'avia_framework');
            $this->config['preview'] = false;
            $this->config['disabling_allowed'] = true;
            $this->config['id_name'] = 'id';
            $this->config['id_show'] = 'yes';
        }

        function extra_assets()
        {
            wp_enqueue_style( 'documents-view-css', get_stylesheet_directory_uri() . '/avia-shortcodes/documents-view/documents-view.css');
            wp_enqueue_script( 'documents-view-js', get_stylesheet_directory_uri() . '/avia-shortcodes/documents-view/documents-view.js', array( 'jquery' ), false, true );
        }

        /**
         * Popup Elements
         *
         * If this function is defined in a child class the element automatically gets an edit button, that, when pressed
         * opens a modal window that allows to edit the element properties
         *
         * @return void
         */
        function popup_elements()
        {

            $this->elements = array(

                array(
                    'type' => 'tab_container',
                    'nodescription' => true
                ),

                /*array(
                    'type' => 'tab',
                    'name' => __('Content', 'avia_framework'),
                    'nodescription' => true
                ),*/

                array(
                    'type' => 'template',
                    'template_id' => $this->popup_key('content_jobs')
                ),

                array(
                    'type' => 'tab_close',
                    'nodescription' => true
                ),

                array(
                    'type' => 'tab',
                    'name' => __('Advanced', 'avia_framework'),
                    'nodescription' => true
                ),

                array(
                    'type' => 'toggle_container',
                    'nodescription' => true
                ),

                array(
                    'type' => 'template',
                    'template_id' => $this->popup_key('layout_spacing')
                ),

                array(
                    'type' => 'template',
                    'template_id' => 'screen_options_toggle',
                ),

                array(
                    'type' => 'template',
                    'template_id' => 'developer_options_toggle',
                    'args' => array('sc' => $this)
                ),

                array(
                    'type' => 'toggle_container_close',
                    'nodescription' => true
                ),

                array(
                    'type' => 'tab_close',
                    'nodescription' => true
                ),

                array(
                    'type' => 'tab_container_close',
                    'nodescription' => true
                )

            );

        }

        /**
         * Create and register templates for easier maintainance
         *
         * @since 4.6.4
         */
        protected function register_dynamic_templates()
        {

            /**
             * Content Tab
             * ===========
             */
            /*$c = array(
                // Custom Fields here
            );

            AviaPopupTemplates()->register_dynamic_template($this->popup_key('content_jobs'), $c);
            */

            $c = array(
                array(
                    'name' => __('Custom top and bottom margin', 'avia_framework'),
                    'desc' => __('If checked allows you to set a custom top and bottom margin. Otherwise the margin is calculated by the theme based on surrounding elements', 'avia_framework'),
                    'id' => 'margin',
                    'type' => 'checkbox',
                    'std' => '',
                    'lockable' => true,
                ),

                array(
                    'name' => __('Custom top and bottom margin', 'avia_framework'),
                    'desc' => __('Set a custom top or bottom margin. Both pixel and &percnt; based values are accepted. eg: 30px, 5&percnt;', 'avia_framework'),
                    'id' => 'custom_margin',
                    'type' => 'multi_input',
                    'sync' => true,
                    'std' => '0px',
                    'lockable' => true,
                    'required' => array('margin', 'not', ''),
                    'multi' => array(
                        'top' => __('Margin-Top', 'avia_framework'),
                        'bottom' => __('Margin-Bottom', 'avia_framework'),
                    )
                ),
            );

            $template = array(
                array(
                    'type' => 'template',
                    'template_id' => 'toggle',
                    'title' => __('Spacing', 'avia_framework'),
                    'content' => $c
                ),
            );

            AviaPopupTemplates()->register_dynamic_template($this->popup_key('layout_spacing'), $template);

        }


        /**
         * Editor Element - this function defines the visual appearance of an element on the AviaBuilder Canvas
         * Most common usage is to define some markup in the $params['innerHtml'] which is then inserted into the drag and drop container
         * Less often used: $params['data'] to add data attributes, $params['class'] to modify the className
         *
         *
         * @param array $params this array holds the default values for $content and $args.
         * @return $params the return array usually holds an innerHtml key that holds item specific markup.
         */
        function editor_element($params)
        {
            $params = parent::editor_element($params);
            $params['content'] = null; //remove to allow content elements
            return $params;
        }

        protected function get_custom_margin_style($atts)
        {

            $margin = array();

            if (!empty($atts['margin'])) {
                $explode_custom_margin = explode(',', $atts['custom_margin']);
                if (count($explode_custom_margin) > 1) {
                    $margin['margin-top'] = $explode_custom_margin['0'];
                    $margin['margin-bottom'] = $explode_custom_margin['1'];
                } else {
                    $margin['margin-top'] = $atts['custom_margin'];
                    $margin['margin-bottom'] = $atts['custom_margin'];
                }
            }

            $custom_margin_style = '';
            $custom_margin_style .= AviaHelper::style_string($margin, 'margin-top');
            $custom_margin_style .= AviaHelper::style_string($margin, 'margin-bottom');

            return $custom_margin_style;
        }

        /**
         * Frontend Shortcode Handler
         *
         * @param array $atts array of attributes
         * @param string $content text within enclosing form of shortcode element
         * @param string $shortcodename the shortcode found, when == callback name
         * @return string $output returns the modified html string
         */
        function shortcode_handler($atts, $content = '', $shortcodename = '', $meta = '')
        {
            $custom_margin_style = $this->get_custom_margin_style($atts);
            $screen_options = AviaHelper::av_mobile_sizes($atts);
            extract($screen_options);

            /*$atts = shortcode_atts(array(
            ), $atts, $this->config['shortcode']);*/
            extract($atts);

	        $output = "";
            $documents = new WP_Query(array(
		        'post_type' => 'documents',
		        //'posts_per_page' => get_field('dlvs_amount_per_page', 'option'),
		        'posts_per_page' => -1,
		        'order' => 'post_title',
		        'orderby' => 'ASC, ASC',
		        'facetwp' => true,
	        ));

            /**
             * color sets im Backend definieren und diese sind dann global zur Verfügung
             */

            $limiter_settings_colors = array(
                'limiter_color' => '',
                'limiter_background_color' => '',
            );

            $document_view_settings_colors = array(
                'state_colors' => array(
                    'labeled' => '',
                    'not_labeled' => '',
                    'not_complete' => '',
                ),
                'backend_edit_colors' => array(
                    'backend_edit_color_ready' => '',
                    'backend_edit_color_not_ready' => '',
                    'backend_edit_color_not_ready_background' => ''
                ),
            );


	        ob_start();


            $url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];



            ?>
	        <div id='<?php echo $meta['custom_el_id']; ?>'
             class='documents_view <?php echo $meta['el_class']; ?> <?php echo $av_display_classes; ?>'
             style='<?php echo $custom_margin_style; ?>'>
                <div class="row">
                    <?php

                    if (strpos($url,'eintragungen') !== false) { ?>

                        <?php $calculator = new Calucalation;
                        if (strpos($url,'eintragungen-uebersicht') !== false) { ?>
                            <div class="col-md-4 state-filter">
                        <h1 style="color:darkred">Eintragungen - E1</h1>
                        <div>
                            <h3>Angaben zum Betrieb</h3>
                            <p>Branche<strong>56.2 Caterer und Erbringung sonstiger Verpflegungsdienstleistungen</strong> / <strong>82.3 Messe-, Ausstellungs- und Kongressveranstalter</strong> / <strong>?????????</strong></p>
                            <h3>Gewinnermittlungsarten</h3>
                            <p>Vollständige Einnahmen-Ausgaben-Rechnung gemäß § 4 Abs. 3 <strong>JA</strong></p>
                            <p>USt-Nettosystem <strong>JA</strong></p>
                        </div>
                        <div>
                            <h3>Gewinnermittlung</h3>
                            <h4>Erträge/Betriebseinnahmen</h4>
                            <p>Erträge/Betriebseinnahmen (Waren-/Leistungserlöse) ohne solche, die in einer Mitteilung gemäß § 109a erfasst sind - EKR 40-44 - einschließlich Eigenverbrauch (Entnahmewerte von Umlaufvermögen) - (Kennzahl 9040)<br/>
                            Grundsätzlich alle Einnahmen aus selbstständigen Tätigkeiten</p> <strong style="text-transform: uppercase;">noch anführen was hier zusammen gerechnet wird </strong><br/>
                            <p>Erträge/Betriebseinnahmen, die in einer Mitteilung gemäß § 109a erfasst sind - EKR 40-44 - (Kennzahl 9050)<br/>
                            (War bis jetzt immer: <strong>0</strong>)</p>>
                            <h4>Aufwendungen/Betriebsausgaben</h4>
                            <p>Waren, Rohstoffe, Hilfsstoffe EKR 500-539, 580 - (Kennzahl 9100) <strong style="text-transform: uppercase;">WAREN</strong></p>
                            <p>Beigestelltes Personal (Fremdpersonal) und Fremdleistungen EKR 570-579, 581, 750-753 - (Kennzahl 9110) <strong style="text-transform: uppercase;">Fremdpersonal</strong></p>
                            <p>Personalaufwand ("eigenes Personal") EKR 60-68 - (Kennzahl 9120) <strong style="text-transform: uppercase;">Eigenpersonal</strong></p>
                            <p>Abschreibungen auf das Anlagevermögen (z.B. AfA, geringwertige Wirtschaftsgüter,
                                EKR 700-708), soweit sie nicht in Kennzahl 9134 und/oder 9135 zu erfassen sind. - (Kennzahl 9130) <strong style="text-transform: uppercase;">Anlagegüter</strong></p>
                            <p>Degressive Absetzung für Abnutzung (§ 7 Abs. 1a) - (Kennzahl 9134) <strong style="text-transform: uppercase;">Anlagegüter Degressive Abschreibung</strong></p>
                            <p>Reise- und Fahrtspesen inkl. Kilometergeld und Diäten (ohne tatsächliche Kfz-Kosten) EKR 734-737 - (Kennzahl 9160)<strong style="text-transform: uppercase;">Verpflegungspauschale/Reisepauschale</strong></p>
                            <p>Pauschale von 50% der Kosten einer Wochen-, Monats- oder Jahreskarte für Massenbeförderungsmittel - (Kennzahl 9165)<strong style="text-transform: uppercase;">öffi ticket</strong></p>
                            <p>Tatsächliche Kfz-Kosten (ohne AfA, Leasing und Kilometergeld) EKR 732-733 - (Kennzahl 9170)<strong style="text-transform: uppercase;">kfz-kosten + Sprit</strong></p>
                            <p>Miet- und Pachtaufwand, Leasing EKR 740-743, 744-747 - (Kennzahl 9180) <strong style="text-transform: uppercase;">Miete/Pachten/Leasing</strong></p>
                            <p>Großes Arbeitsplatzpauschale (1.200 Euro für ein volles Wirtschaftsjahr) - (Kennzahl 9217) <strong style="text-transform: uppercase;">?????? - Miete abrechnen</strong></p>
                            <p>Provisionen an Dritte, Lizenzgebühren EKR 754-757, 748-749 - (Kennzahl 9190) <strong style="text-transform: uppercase;">Lizenzgebühren/Provision</strong></p>
                            <p>Werbe- und Repräsentationsaufwendungen, nicht in den Kennzahlen 9243 bis 9209 zu erfassenden Spenden, Trinkgelder
                                EKR 765-769 - (Kennzahl 9200) <strong style="text-transform: uppercase;">Firmenfeier-Werbeveranstaltung + Geschenke mit werbezweck + Geschäftsessen + Präsentation/Werbung</strong></p>
                            <p>Eigene Pflichtversicherungsbeiträge, Beiträge zu Versorgungs- und Unterstützungseinrichtungen und Beiträge zur Selbständigenvorsorge - (Kennzahl 9225) <strong style="text-transform: uppercase;">Sozialversicherung + Vorsorgeleistung</strong></p>
                            <p>In den obigen Kennzahlen nicht erfasste übrige Aufwendungen/Betriebsausgaben (ohne pauschalierte Betriebsausgaben), Kapitalveränderungen - Saldo - (Kennzahl 9230) <strong style="text-transform: uppercase;">Betriebsausgaben + Weiterbildung</strong></p>
                        </div>
                        <div>
                            <h3>Korrekturen und Ergänzungen zur Gewinnermittlung (Steuerliche Mehr-/Weniger-Rechnung)</h3>
                            <h4>Investitionsfreibetrag und Öko-Zuschlag</h4>
                            <p>Investitionsfreibetrag (10%)
                                Achtung: Steht bei einer Pauschalierung auf Grundlage von § 17 nicht zu - (Kennzahl 9276) <strong style="text-transform: uppercase;">?????</strong></p>
                        </div>
                        <div>
                            <h3>Gesamtsaldo der Einkünfte aus selbständiger Arbeit sowie Einkünfteverteilungen</h3>
                            <h4>Summe Kennzahl 320</h4>
                            <p>Summe aus allen Beilagen E1a, E1a-K und E11 sowie den Kennzahlen 321 bis 501 - (Kennzahl 320) <strong style="text-transform: uppercase;">??????</strong></p>
                        </div>
                        <div>
                            <h3>Außerbetriebliche Einkunftsarten</h3>
                            <h4>Einkünfte aus nicht selbstständiger Arbeit (Werbungskosten)</h4>
                            <p>Anzahl derinländischen gehalts- oder pensionsauszahlenden Stellen im Jahr 2023 <strong style="text-transform: uppercase;">Alle Firmen wo ich angestellt war eintragen Zahl</strong></p>
                            <p>Steuerfreie Einkünfte auf Grund völkerrechtlicher Vereinbarungen (z.B. UNO, UNIDO) - (Kennzahl 725) <strong style="text-transform: uppercase;">Außerbetriebliche Gesamteinnahmen als Angestellter</strong></p>
                            <h4>Pendlerpauschale/Pendlereuro</h4>
                            <p>Pendlerpauschale -tatsächlich zustehender Jahresbetrag- (Kennzahl 718) <strong style="text-transform: uppercase;">Pendlerpauschale online berechnen</strong></p>
                            <p>Pendlereuro (Absetzbetrag) -tatsächlich zustehender Jahresbetrag - (Kennzahl 916)  <strong style="text-transform: uppercase;">Pendlereuro online berechnen</strong></p>
                            <h4>Werbungskosten</h4>
                            <p>Genaue Bezeichnung Ihrer beruflichen Tätigkeit(z.B. Koch, Verkäufer*in; nicht ausreichend ist Angestellte*r, Arbeiter*in) <strong style="text-transform: uppercase;">Servicekraft</strong></p>
                            <p>Digitale Arbeitsmittel (z.B. Computer, Internet) ohne Kürzung um ein allfälligesHomeoffice-Pauschale - (Kennzahl 169) 474,35 - (Kennzahl 169)  <strong style="text-transform: uppercase;">Werbungskosten</strong></p>
                            <p>Andere Arbeitsmittel, die nichtin Kennzahl 169 zu erfassen sind - (Kennzahl 719) <strong style="text-transform: uppercase;">Werbungskosten</strong></p>
                            <p>Fortbildungs-, Ausbildungs- undUmschulungskosten - (Kennzahl 722)<strong style="text-transform: uppercase;"> Werbungskosten</strong></p>
                            <p>Fachliteratur(keine allgemein bildenden Werke wie Lexika,Nachschlagewerke, Zeitungen etc.) - (Kennzahl 720)<strong style="text-transform: uppercase;"> Werbungskosten</strong></p>
                            <p>Arbeitszimmer- (Kennzahl 159)<strong style="text-transform: uppercase;"> 300 oder 0 ????</strong></p>
                        </div>
                        <div>
                            <h4>Sonderasusgaben inkl. Verlustabzug</h4>
                            <p>Steuerberatungskosten - (Kennzahl 460) <strong style="text-transform: uppercase;"> Steuerberater</strong></p>
                        </div>
                        <div>
                            <h4>Außergewöhnliche Belastungen</h4>
                            <p>Krankheitskosten (inkl. Zahnersatz) - (Kennzahl 730) <strong style="text-transform: uppercase;"> Arztkosten/Medikamente</strong></p>
                        </div>


                        <h3 style="color:darkred">Eintragungen - U1 </h3>
                        <div>
                            <h4></h4>

                        </div>
                        <?php } else {?>



                           <?php
                           $pos = strrpos($url, '20');
                           $current_year = $pos === false ? $url : substr($url, $pos);
                           $current_year = rtrim($current_year, "/");
                           echo '...... ' . $current_year. ' ...... ' ; ?>
                            <div class="col-md-4">
                            <?php echo $calculator->generate_calculation_template(' U1 ', $current_year); ?>
                           </div>
                            <div class="col-md-4">
                                <?php echo $calculator->generate_calculation_template(' U1 - Umsatzsteuer Voranmeldung | Jänner-März', $current_year, 1, true); ?>
                                <?php echo $calculator->generate_calculation_template(' U1 - Umsatzsteuer Voranmeldung | April-Juni', $current_year, 2, true); ?>
                                <?php echo $calculator->generate_calculation_template(' U1 - Umsatzsteuer Voranmeldung | Juli-September', $current_year, 3, true); ?>
                                <?php echo $calculator->generate_calculation_template(' U1 - Umsatzsteuer Voranmeldung | Oktober-Dezember', $current_year, 4, true); ?>
                            </div>


                            <?php }
                     } else if (strpos($url,'documents-calculation') !== false) { ?>
                        <div class="col-md-4 state-filter">
                            <?php
                            if (strpos($url,'documents-calculation-uebersicht') !== false) {

                            } else {
                                $pos = strrpos($url, '20');
                                $current_year = $pos === false ? $url : substr($url, $pos);
                                $current_year = rtrim($current_year, "/");
                            $calculator = new Calucalation;
                            echo '...... ' . $current_year. ' ...... ' ;

                            echo $calculator->generate_calculation_template('Gesamt', $current_year,); ?>
                            <div>
            <!--<span>Pendlerpauschale</span>
<span>Pendlerpauschale für selbstständige</span>
<span>Lohnzettel Summe (geringfügig)</span><span>Geschenke mit werbezweck</span>
            <span>Homeoffice Pauschale</span>
            <span>Werbekosten</span>-->
        <div class="col-md-4 state-filter">
            <h3 style="color:darkblue">Umsatzsteuererklärung</h3>
            <h4>EXTRA  (Betriebskosten)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('betriebsausgaben',$current_year, 3); ?>
            <h4>EXTRA  (Geschäftsessen)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('firmenessen',$current_year, 3); ?>
            <h4>EXTRA  (sprit)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('sprit',$current_year, 3); ?>
            <h4>EXTRA  (ticket)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('ticket',$current_year, 3); ?>
            <h4>EXTRA  (weiterbildung)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('weiterbildung',$current_year, 3); ?>
            <h4>EXTRA  (presentaion)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('presentaion ',$current_year, 3); ?>
            <h4>EXTRA  (lizensgebuhren)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('lizensgebuhren ',$current_year, 3); ?>
            <h4>EXTRA  (waren)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('waren ',$current_year, 3); ?>
            <h4>EXTRA  (miete)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('miete ',$current_year, 3); ?>
            <h4>EXTRA  (Fremdpersonal)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('Fremdpersonal ',$current_year, 3); ?>
            <h4>EXTRA  (vorsorgeleistung)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('vorsorgeleistung',$current_year, 3); ?>
            <h4>EXTRA  (Verpflegungspauschale/Reisepauschale)</h4>
            <?php // speziell berechnen
            echo $calculator->generate_single_ust_template_calculation('reisepauschale',$current_year, 3); ?>
            <h4>EXTRA  (Kilometergeld -> ab 30.000km wirds versteuert)</h4>
            <?php // speziell berechnen
            echo $calculator->generate_single_ust_template_calculation('reisepauschale',$current_year, 3); ?>
            <h4>EXTRA  (Firmenfeier-Werbeveranstaltung)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('Firmenfeier-Werbeveranstaltung',$current_year, 3); ?>
            <h4>EXTRA  (Anlagegüter)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('Anlagegueter',$current_year, 3); ?>
            <h4>EXTRA  (Degressive Abschreibung Anlagegüter)</h4>
            <?php echo $calculator->generate_single_ust_template_calculation('degressive-abschreibung',$current_year, 3); ?>

          <h4>EXTRA  (Eihnnahmen)</h4>
          <?php echo $calculator->generate_single_ust_template_calculation(false, $current_year, 6); ?>
        </div>
                        </div>
                        <div class="col-md-4 state-filter">
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Jänner', $current_year, '01'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Februar', $current_year, '02'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('März', $current_year, '03'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('April', $current_year, '04'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Mai', $current_year, '05'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Juni', $current_year, '06'); ?>
                            </div>
                        </div>
                        <div class="col-md-4 state-filter">
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Juli', $current_year, '07'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('August', $current_year, '08'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('September', $current_year, '09'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Oktober', $current_year, '10'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('November', $current_year, '11'); ?>
                            </div>
                            <div class="">
                                <?php echo $calculator->generate_calculation_template('Dezemeber', $current_year, '12'); ?>
                            </div>
                        </div>
                    <?php }} else { ?>
                        <div class="col-md-4 type-filter">
                            <div class="facet-search">
                                <div class="documents_view__filter">
                                    <span><i class="fa-sharp fa-solid fa-file-invoice"></i> Document Type:</span>
                                    <div class="documents_view__filter__container">
                                        <?php echo do_shortcode('[facetwp facet="document_type_filter"]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 state-filter">
                            <div class="facet-search">
                                <div class="documents_view__filter">
                                    <span><i class="fa-solid fa-battery-bolt"></i> Gewerbeschein:</span>
                                    <div class="documents_view__filter__container">
                                        <?php echo do_shortcode('[facetwp facet="documents_gewerbeschein_filter"]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 state-filter">
                            <div class="facet-search">
                                <div class="documents_view__filter">
                                    <span><i class="fa-solid fa-battery-bolt"></i> Datum:</span>
                                    <div class="documents_view__filter__container">
                                        <?php echo do_shortcode('[facetwp facet="documents_date_range"]'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <!--<div class="col-md-4">
                        <div class="facet-search">
                            <div class="">
                                <span>Unlabeled Documents: <span><?php //echo $documents_count->post_count; ?></span></span>
                            </div>
                        </div>
                    </div>-->
                    <div class="col-md-12">
                        <div class="facetwp-template">
                            <h2>WICHTIG FIX ROUND " DEZIMALStellen bei ust rechnungen</h2>
                            <table id="customers" class="documents_view__item">
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Gewerbe</th>
                                    <th>Abschreibung</th>
                                    <th>Datum</th>
                                    <th style="width: 68px;">Sum-N</th>
                                    <th style="width: 68px;">Sum-UST</th>
                                    <th style="width: 543px;">Artikel: Name / Anz. / Steuer % / Privat % / Netto / UST</th>
                                </tr>
                            <?php

                            if ($documents->have_posts()) :
                                ?>
                                <?php while ($documents->have_posts()) : $documents->the_post();
                                    //$document = get_post();
                                    $this->document_encode(get_field('dlvs_document_display_view', 'option'));
                                endwhile;

                                ?>
                            <?php else:
                                _e( 'Sorry, no documents-view matched your criteria.' );
                             endif;
                            ?>
                            </table>
                        </div>
                        <?php
                        wp_reset_postdata();
                        if(get_field('dlvs_documents_limiter', 'option') == 'pagination') {
                            echo '<div class="pagination_container limiter_container">' . do_shortcode('[facetwp facet="pagination"]') . '</div>';
                        } else if(get_field('dlvs_documents_limiter', 'option') == 'infinite_loading') {
                            echo '<div class="load_more_container limiter_container infinite">' . do_shortcode('[facetwp facet="load_more_infinite"]') . '</div>';
                        } else {
                            echo '<div class="load_more_container limiter_container default">' . do_shortcode('[facetwp facet="load_more"]') . '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php } ?>
	        </div>
	        <?php return ob_get_clean();
        }

	    private function document_encode($type = 'table_view')
        {
            $type == 'table_view' ? $class = '' : $class = 'col-md-4';
            $document_ID = get_the_ID();
            $data_viewer = new AI_Data_Viewer;
            $document_data = $data_viewer->get_document_data_from_document($document_ID, true);

            // var_dump($document_data);
            $net_price_sum = [];
            $ust_sum = [];
            ?>
                <tr>
                    <td>
                        <!--http://localhost/steuerausgleich-uploader-->
                        <a class="documents_view__item__backend__edit" href="https://buchhaltung.partycrowd.at/wp-admin/post.php?post=<?php echo $document_ID ?>&action=edit"><i class="fa-solid fa-pencil"></i></a>
                    </td>
                    <td>
                        <a class="documents_view__item__backend__show" href="<?php echo get_field('documents_file', get_the_ID()); ?>"><i class="fa-sharp fa-solid fa-magnifying-glass"></i></a>
                    </td>
                    <td><?php echo get_the_title()//generate_document_title(); ?></td>
                    <td><?php $term = get_term($document_data['document_type']);
                        echo $term->name; //gets term name ?></td>
                    <td><?php echo get_field('documents_gewerbeschein', get_the_ID())?></td>
                    <td><?php echo get_field('documents_abschreibungsart', get_the_ID())?></td>
                    <td>            <?php if( have_rows('documents_document_data') ): ?>
                            <?php while( have_rows('documents_document_data') ): the_row();
                                echo get_sub_field('documents_document_data_document_date')?>
                            <?php endwhile; ?>
                        <?php endif; ?></td>
                    <td>
                        <?php $calculator = new Calucalation;
                        echo $calculator->calculate_single_sum_netto_ust(get_the_ID())[0] . '€'; ?></td>
                     <!--echo array_sum($ust_sum). '€';-->
                    <td><?php echo $calculator->calculate_single_sum_netto_ust(get_the_ID())[1] . '€'; ?>
                    </td>
                    <td>
                        <?php
                        // Check rows exists.
                        if( have_rows('documents_article_items', get_the_ID()) ):

                            // Loop through rows.
                            while( have_rows('documents_article_items', get_the_ID()) ) : the_row();


                                $single_net_price = round(floatval(get_sub_field('documents_article_items_price_net_single')), 2);

                                $tax_num = "1." . round(floatval(get_sub_field('documents_article_items_tax_rate'))/ 10, 2);

                                $gross_price = $single_net_price * floatval($tax_num);

                                $only_ust = round(floatval($gross_price), 2) - $single_net_price;


                                // Load sub field value.
                                ?>
                        <span><?php echo get_sub_field('documents_article_items_article_name'); ?></span>
                                <span><?php echo get_sub_field('documents_article_items_quantity'). 'x'; ?></span>
                                <span>
                                    <?php
                                    $tax = get_sub_field('documents_article_items_tax_rate');
                                    echo $tax . '%';
                                    ?></span>
                                <span><?php echo get_sub_field('documents_article_items_privatnutzung') . '%'; ?></span>
                                <span><?php echo $single_net_price . get_sub_field('documents_article_items_currency'); ?></span>
                                <span><?php echo $only_ust . get_sub_field('documents_article_items_currency'); ?></span></br> <?php

                                // Do something, but make sure you escape the value if outputting directly...

                                // End loop.
                            endwhile;

// No value.
                        else :
                            // Do something...
                        endif;
                        ?>


                    </td>

                </tr>

            <?php
        }
    }
}

?>
