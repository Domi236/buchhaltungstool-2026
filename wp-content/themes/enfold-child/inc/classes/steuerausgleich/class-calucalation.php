<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    die;
}
class Calucalation {

    //two types, from jsonfile or gravity form
    public function get_calcuclate_betriebskosten_netto_ust($gewerbe, $cat, $extra, $current_year, $only_specific_month = 0)
    {
        //var_dump($tax);
        $netto = [];
        $ust = [];
        $NETs_0 = [];
        $NETs_10 = [];
        $NETs_13 = [];
        $NETs_19 = [];
        $NETs_20 = [];

        $USTs_0 = [];
        $USTs_10 = [];
        $USTs_13 = [];
        $USTs_19 = [];
        $USTs_20 = [];


        $args3 = array(
            'taxonomy' => 'document_cat',
            'orderby' => 'name',
            'order'   => 'ASC'
        );

        //$cats = get_categories($args3);
        //var_dump($cats);


       /* $args = array(
            'post_type' => 'documents',
            'posts_per_page'   => -1,
            'meta_key'      => 'documents_gewerbeschein',
            'meta_value'    => $gewerbe,
            'tax_query' => array(
                array(
                    'taxonomy' => 'document_cat',
                    'field' => 'term_id',
                    'terms' => $cat,
                )
            )
        );*/

            $args = array(
                'post_type' => 'documents',
                'posts_per_page'   => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'documents_gewerbeschein',
                        'value' => $gewerbe,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'documents_abschreibungsart',
                        'value' => $extra,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'documents_year',
                        'value' => $current_year,
                        'compare' => 'LIKE',
                    )
                ),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'document_cat',
                        'field' => 'term_id',
                        'terms' => $cat,
                    )
                )
            );

            //extra funktion schreiben,
            //alle dokumente hier durchgehen und in 4 verschiedene array packen je nachdem welche steuerklasse
        //Programmieren:
        //Berechnugnen für die einzelnen Steuersätze


        //U1 Abgabe, E1 Abage, WA1 Abgabe

        //Löhne aus Bild einfügen - hat finanzonline schon



        // query
        $documents = new WP_Query( $args );

        if ($documents->have_posts()) :
             while ($documents->have_posts()) : $documents->the_post();
            //$document = get_post();
                $document_ID = get_the_ID();

                 /**
                  * check that the query is working corectly
                  */
                /*
                $data_viewer = new AI_Data_Viewer;
                $document_data = $data_viewer->get_document_data_from_document($document_ID, true);
                $term = get_term($document_data['document_type']);
                echo $term->name; //gets term name
                echo get_field('documents_gewerbeschein', get_the_ID());
                */

             if($only_specific_month === 0) {
                 $netto[] = $this->calculate_single_sum_netto_ust($document_ID)[0];
                 $ust[] = $this->calculate_single_sum_netto_ust($document_ID)[1];

                 $NETs_0[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_0'];
                 $NETs_10[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_10'];
                 $NETs_13[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_13'];
                 $NETs_19[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_19'];
                 $NETs_20[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_20'];

                 $USTs_0[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_0'];
                 $USTs_10[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_10'];
                 $USTs_13[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_13'];
                 $USTs_19[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_19'];
                 $USTs_20[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_20'];
             } else {
                 if( have_rows('documents_document_data',$document_ID) ): ?>
                     <?php while( have_rows('documents_document_data',$document_ID) ): the_row();
                         $date = get_sub_field('documents_document_data_document_date',$document_ID);
                         $month = date("m",strtotime($date));
                         if($month == $only_specific_month) {
                             $netto[] = $this->calculate_single_sum_netto_ust($document_ID)[0];
                             $ust[] = $this->calculate_single_sum_netto_ust($document_ID)[1];

                             $NETs_0[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_0'];
                             $NETs_10[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_10'];
                             $NETs_13[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_13'];
                             $NETs_19[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_19'];
                             $NETs_20[] = $this->calculate_single_sum_netto_ust($document_ID)[2]['net_20'];

                             $USTs_0[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_0'];
                             $USTs_10[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_10'];
                             $USTs_13[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_13'];
                             $USTs_19[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_19'];
                             $USTs_20[] = $this->calculate_single_sum_netto_ust($document_ID)[3]['ust_20'];

                             /*var_dump($NETs_20);
                             var_dump($USTs_20);*/
                         }
                         ?>
                     <?php endwhile; ?>
                 <?php endif;
            }

                 //echo $this->calculate_single_sum_netto_ust($document_ID)[1] . '-';

            endwhile;
        endif;
        wp_reset_postdata();

        $NETs = [
            'net_0' => array_sum($NETs_0),
            'net_10' => array_sum($NETs_10),
            'net_13' => array_sum($NETs_13),
            'net_19' => array_sum($NETs_19),
            'net_20' => array_sum($NETs_20)
        ];

        $USTs = [
            'ust_0' => array_sum($USTs_0),
            'ust_10' => array_sum($USTs_10),
            'ust_13' => array_sum($USTs_13),
            'ust_19' => array_sum($USTs_19),
            'ust_20' => array_sum($USTs_20)
        ];

        $Brutto = [
            'brutto_0' => $NETs['net_0'] + $USTs['ust_0'],
            'brutto_10' => $NETs['net_10'] + $USTs['ust_10'],
            'brutto_13' => $NETs['net_13'] + $USTs['ust_13'],
            'brutto_19' => $NETs['net_19'] + $USTs['ust_19'],
            'brutto_20' => $NETs['net_20'] + $USTs['ust_20']
        ];


        //echo '<pre>';
        //echo '!!!';
        //var_dump($netto);
        //echo '!!!';
        //var_dump($ust);
        //echo '!!!';
        $nettosum = array_sum($netto);
        $ustsum = array_sum($ust);
        $sumbrutto = $nettosum + $ustsum;
        /*echo '<pre>';
        var_dump($NETs);
        var_dump($USTs);
        echo '</pre>';*/

        return [$nettosum,$ustsum,$sumbrutto, $NETs, $USTs, $Brutto];
    }

    // can only come from json
    public function get_calcuclate_betriebskosten_ust($gewerbe, $cat)
    {
        $ust = [];

        return $ust;
    }


    public function calculate_single_sum_netto_ust($id)
    {
        $net_price_sum = [];
        $net_price_0 = [];
        $net_price_10 = [];
        $net_price_13 = [];
        $net_price_19 = [];
        $net_price_20 = [];

        $ust_sum = [];
        $ust_0 = [];
        $ust_10 = [];
        $ust_13 = [];
        $ust_19 = [];
        $ust_20 = [];
        if( have_rows('documents_article_items', $id) ):
            while( have_rows('documents_article_items', $id) ) : the_row();
                        //echo '-DRINNEN ' . $tax . '-' . intval(get_sub_field('documents_article_items_tax_rate',$id)) . '|';
                        $default_net_price = round(floatval(get_sub_field('documents_article_items_price_net_single',$id)), 2);
                        $privat_percent = intval(get_sub_field('documents_article_items_privatnutzung',$id));
                        $quantity = intval(get_sub_field('documents_article_items_quantity',$id));
                        $tax = get_sub_field('documents_article_items_tax_rate',$id);

                        $real_net_price = 0;

                        if( $privat_percent > 0) {
                            $privat_part = $default_net_price / 100 * $privat_percent;
                            $real_net_price = $default_net_price - round(floatval($privat_part), 2);
                            $net_price_sum[] = $real_net_price * $quantity;

                            $current_netto = $real_net_price * $quantity;
                            if($tax == 0) {
                                $net_price_0[] = $current_netto;
                            } else if($tax == 10){
                                $net_price_10[] = $current_netto;
                            } else if($tax == 13){
                                $net_price_13[] = $current_netto;
                            }  else if($tax == 19){
                                $net_price_19[] = $current_netto;
                            } else if($tax == 20){
                                $net_price_20[] = $current_netto;
                            } else {
                                echo 'NEEEEEIN -'. $tax . '-';
                            }
                        } else {
                            $net_price_sum[] = $default_net_price  * $quantity;

                            $current_netto = $default_net_price  * $quantity;
                            if($tax == 0) {
                                $net_price_0[] = $current_netto;
                            } else if($tax == 10){
                                $net_price_10[] = $current_netto;
                            } else if($tax == 13){
                                $net_price_13[] = $current_netto;
                            } else if($tax == 19){
                                $net_price_19[] = $current_netto;
                            } else if($tax == 20){
                                $net_price_20[] = $current_netto;
                            } else {
                                echo 'NEEEEEIN -'. $tax . '-';
                            }
                        }

                        $tax_num = "1." . round(intval(get_sub_field('documents_article_items_tax_rate',$id))/ 10, 2);

                        //weil bei firmenessen kann die ust zu 100%, jedoch die einkommenssteuer nur zu 50% abgeschrieben werden
                        if(get_field('documents_abschreibungsart',$id) == 'firmenessen' && $privat_percent > 0 || $privat_percent == 0) {

                            $gross_price = $default_net_price * floatval($tax_num);
                            $only_ust = round(floatval($gross_price), 2) - $default_net_price;
                            $ust_sum[] = $only_ust * $quantity;

                            $current_UST = $only_ust * $quantity;
                            if($tax == 0) {
                                $ust_0[] = $current_UST;
                            } else if($tax == 10){
                                $ust_10[] = $current_UST;
                            }  else if($tax == 13){
                                $ust_13[] = $current_UST;
                            }  else if($tax == 19){
                                $ust_19[] = $current_UST;
                            } else if($tax == 20){
                                $ust_20[] = $current_UST;
                            } else {
                                echo 'NEEEEEIN -'. $tax . '-';
                            }
                        } else {
                            $gross_price = $real_net_price * floatval($tax_num);
                            $only_ust = round(floatval($gross_price), 2) - $real_net_price;
                            $ust_sum[] = $only_ust * $quantity;

                            $current_UST = $only_ust * $quantity;
                            if($tax == 0) {
                                $ust_0[] = $current_UST;
                            } else if($tax == 10){
                                $ust_10[] = $current_UST;
                            }  else if($tax == 13){
                                $ust_13[] = $current_UST;
                            }   else if($tax == 19){
                                $ust_19[] = $current_UST;
                            } else if($tax == 20){
                                $ust_20[] = $current_UST;
                            } else {
                                echo 'NEEEEEIN -'. $tax . '-';
                            }
                        }
                        //echo '-NEIN ' . $tax . '-' . intval(get_sub_field('documents_article_items_tax_rate',$id)) . '|';


            endwhile;
        endif;


        $net_price_0[] = 0;
        $net_price_10[] = 0;
        $net_price_13[] = 0;
        $net_price_19[] = 0;
        $net_price_20[] = 0;
        $net_price_0_sum = array_sum($net_price_0);
        $net_price_10_sum = array_sum($net_price_10);
        $net_price_13_sum = array_sum($net_price_13);
        $net_price_19_sum = array_sum($net_price_19);
        $net_price_20_sum = array_sum($net_price_20);
        //echo ' $ ' . $net_price_0_sum . ' $ ';
        $NETs = [
            'net_0' => $net_price_0_sum,
            'net_10' => $net_price_10_sum,
            'net_13' => $net_price_13_sum,
            'net_19' => $net_price_19_sum,
            'net_20' => $net_price_20_sum
        ];
        //var_dump($NETs);

        $ust_0[] = 0;
        $ust_10[] = 0;
        $ust_13[] = 0;
        $ust_19[] = 0;
        $ust_20[] = 0;
        $ust_0_sum = array_sum($ust_0);
        $ust_10_sum = array_sum($ust_10);
        $ust_13_sum = array_sum($ust_13);
        $ust_19_sum = array_sum($ust_19);
        $ust_20_sum = array_sum($ust_20);

        $USTs = [
            'ust_0' => $ust_0_sum,
            'ust_10' => $ust_10_sum,
            'ust_13' => $ust_13_sum,
            'ust_19' => $ust_19_sum,
            'ust_20' => $ust_20_sum
        ];

        $net_price_sum[] = 0;
        $ust_sum[] = 0;

        return [array_sum($net_price_sum),array_sum($ust_sum), $NETs, $USTs];
    }


    public function generate_calculation_template($monthName,$current_year, $month = 0, $u1_voranmeldung = false)

        /*
         * ust voranmeldung varibale mit false/true mitgeben -> hier assoziatives Array erstellen und
         * die gleiche funktion 3x durchlaufen lassen nur mit der Änderung des Monats mit counter und dann am ende alles zusammen zählen und ausgeben.
         * Am besten als neue Funktion deklarieren extra
         */
    {
        ob_start(); ?>
        <h3 style="color:darkred">Rechnungen - <?php echo $monthName . ' ' . $current_year; ?></h3>
        <div>
            <h4>EXTRA  (Betriebskosten)</h4>
            <?php echo $this->generate_single_template_calculation('betriebsausgaben',$current_year, 3, $month); ?>
            <h4>EXTRA  (Geschäftsessen)</h4>
                <?php echo $this->generate_single_template_calculation('firmenessen',$current_year,3, $month); ?>
            <h4>EXTRA  (sprit)</h4>
            <?php echo $this->generate_single_template_calculation('sprit',$current_year,3, $month); ?>
            <h4>EXTRA  (ticket)</h4>
            <?php echo $this->generate_single_template_calculation('ticket',$current_year,3, $month); ?>
            <h4>EXTRA  (weiterbildung)</h4>
            <?php echo $this->generate_single_template_calculation('weiterbildung',$current_year,3, $month); ?>
            <h4>EXTRA  (presentaion)</h4>
            <?php echo $this->generate_single_template_calculation('presentaion ',$current_year,3, $month); ?>
            <h4>EXTRA  (lizensgebuhren)</h4>
            <?php echo $this->generate_single_template_calculation('lizensgebuhren ',$current_year,3, $month); ?>
            <h4>EXTRA  (waren)</h4>
            <?php echo $this->generate_single_template_calculation('waren ',$current_year,3, $month); ?>
            <h4>EXTRA  (miete)</h4>
            <?php echo $this->generate_single_template_calculation('miete ',$current_year,3, $month); ?>
            <h4>EXTRA  (Fremdpersonal)</h4>
            <?php echo $this->generate_single_template_calculation('Fremdpersonal ',$current_year,3, $month); ?>
            <h4>EXTRA  (vorsorgeleistung)</h4>
            <?php echo $this->generate_single_template_calculation('vorsorgeleistung',$current_year,3, $month); ?>
            <h4>EXTRA  (Verpflegungspauschale/Reisepauschale)</h4>
            <?php // speziell berechnen
            echo $this->generate_single_template_calculation('reisepauschale',$current_year, 3, $month); ?>
            <h4>EXTRA  (Kilometergeld -> ab 30.000km wirds versteuert)</h4>
            <?php // speziell berechnen
            echo $this->generate_single_template_calculation('reisepauschale',$current_year, 3, $month); ?>
            <h4>EXTRA  (Firmenfeier-Werbeveranstaltung)</h4>
            <?php echo $this->generate_single_template_calculation('Firmenfeier-Werbeveranstaltung',$current_year, 3, $month); ?>
            <h4>EXTRA  (Anlagegüter)</h4>
            <?php echo $this->generate_single_template_calculation('Anlagegueter',$current_year, 3, $month); ?>
            <h4>EXTRA  (Degressive Abschreibung Anlagegüter)</h4>
            <?php echo $this->generate_single_template_calculation('degressive-abschreibung',$current_year, 3, $month); ?>

            <h4>EXTRA Ausgaben (Kammerumlage)</h4>
                <span>Kammerumlage:
                        <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'kammerumlage', $current_year, $month)[0]; ?> €</strong>
                        <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'kammerumlage', $current_year, $month)[2]; ?> €</strong>
                    </span><br/>
            <h4>EXTRA Ausgaben (SVS)</h4>
                <span>Sozialversicherung:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'svs', $current_year, $month)[0]; ?> €</strong>
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'svs', $current_year, $month)[2]; ?> €</strong>
                </span><br/>
            <h4>EXTRA Ausgaben (Arztkosten)</h4>
            <span>Arztkosten:
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'arztkosten', $current_year, $month)[0]; ?> €</strong> -
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'arztkosten', $current_year, $month)[1]; ?> €</strong> -
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'arztkosten', $current_year, $month)[2]; ?> €</strong>
            </span><br/>
            <h4>EXTRA Ausgaben (steuerberater)</h4>
            <span>steuerberater:
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'steuerberater', $current_year, $month)[0]; ?> €</strong> -
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'steuerberater', $current_year, $month)[1]; ?> €</strong> -
                <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust('event_technik', 3, 'steuerberater', $current_year, $month)[2]; ?> €</strong>
            </span><br/>
        </div>
        <div class="">
            <h3 style="color:darkblue">Einnahmen</h3>
            <?php echo $this->generate_single_template_calculation(false, $current_year, 6, $month, ['event_technik', 'catering', 'werbe_agentur', 'event_management']); ?>
        </div>
        <div class="">
            <h3 style="color:darkblue">Löhne</h3>
            <?php echo $this->generate_single_template_calculation(false, $current_year, 7, $month, ['event_technik', 'catering', 'werbe_agentur', 'event_management']); ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function generate_single_template_calculation($abschreibungsart, $current_year, $cat = 3, $month = 0, $gewerbe = ['event_technik', 'catering', 'werbe_agentur', 'event_management']) {
        ob_start();
        ?>
        <span>Event Technik, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[0]; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[1]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[2]; ?> €</strong>|
            </span><br/>
            <span>Gast Catering, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[0]; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[1]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[2]; ?> €</strong>|
                </span><br/>
            <span>Werbeagentur, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[0]; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[1]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[2]; ?> €</strong>|
                </span><br/>
            <!--<span>Event Management, Summe Netto:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[0]; ?> €</strong> | Summe UST:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[1]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[2]; ?> €</strong>|
                </span><br/>
        <?php
        return ob_get_clean();
    }

    public function generate_single_ust_template_calculation($abschreibungsart, $current_year,$cat = 3, $month = 0, $gewerbe = ['event_technik', 'catering', 'werbe_agentur', 'event_management']) {
        ob_start();



            ?>
            <span>Event Technik 0%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year,$month)[3]['net_0']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[4]['ust_0']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_0']; ?> €</strong>|
            </span><br/>
            <span>Event Technik 10%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[3]['net_10']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[4]['ust_10']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_10']; ?> €</strong>|
            </span><br/
        <span>Event Technik 13%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[3]['net_13']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[4]['ust_13']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_13']; ?> €</strong>|
            </span><br/>
            <span>Event Technik 19%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[3]['net_19']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[4]['ust_19']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_19']; ?> €</strong>|
            </span><br/>
            <span>Event Technik 20%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[3]['net_20']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[4]['ust_20']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[0], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_20']; ?> €</strong>|
            </span><br/>
                <?php
        ?>


        <span>Gast Catering 0%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[3]['net_0']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[4]['ust_0']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_0']; ?> €</strong>|
            </span><br/>
        <span>Gast Catering 10%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[3]['net_10']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[4]['ust_10']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_10']; ?> €</strong>|
            </span><br/>
        <span>Gast Catering 13%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[3]['net_13']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[4]['ust_13']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_13']; ?> €</strong>|
            </span><br/>
        <span>Gast Catering 19%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[3]['net_19']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[4]['ust_19']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_19']; ?> €</strong>|
            </span><br/>
        <span>Gast Catering 20%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[3]['net_20']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[4]['ust_20']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[1], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_20']; ?> €</strong>|
            </span><br/>

        <span>Werbeagentur 0%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[3]['net_0']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[4]['ust_0']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $month)[5]['brutto_0']; ?> €</strong>|
            </span><br/>
        <span>Werbeagentur 10%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[3]['net_10']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[4]['ust_10']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_10']; ?> €</strong>|
            </span><br/>
        <span>Werbeagentur 13%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[3]['net_13']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[4]['ust_13']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_13']; ?> €</strong>|
            </span><br/>
        <span>Werbeagentur 19%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[3]['net_19']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[4]['ust_19']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_19']; ?> €</strong>|
            </span><br/>
        <span>Werbeagentur 20%, Summe Netto:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[3]['net_20']; ?> €</strong> | Summe UST:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[4]['ust_20']; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[2], $cat, $abschreibungsart, $current_year, $month)[5]['brutto_20']; ?> €</strong>|
            </span><br/>


        <!--<span>Event Management 0%, Summe Netto:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[3][0]; ?> €</strong> | Summe UST:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[4][0]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[2]; ?> €</strong>|
            </span><br/>
        <span>Event Management 10%, Summe Netto:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[3][1]; ?> €</strong> | Summe UST:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[4][1]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[2]; ?> €</strong>|
            </span><br/>
        <span>Event Management 19%, Summe Netto:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[3][2]; ?> €</strong> | Summe UST:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[4][2]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[2]; ?> €</strong>|
            </span><br/>
        <span>Event Management 20%, Summe Netto:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[3][3]; ?> €</strong> | Summe UST:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[4][3]; ?> €</strong> | SUMME BRUTTO:
                    <strong><?php //echo $this->get_calcuclate_betriebskosten_netto_ust($gewerbe[3], $cat, $abschreibungsart, $month)[2]; ?> €</strong>|
            </span><br/>-->

        <?php
        return ob_get_clean();
    }
}