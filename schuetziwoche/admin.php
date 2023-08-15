<?php

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once dirname( __FILE__ ) . '/admin-list.php';

add_action('admin_init', 'schuetziwoche_admin_init');

function schuetziwoche_admin_init(){
	register_setting(SCHUETZIWOCHE_OPTIONS_GROUP, SCHUETZIWOCHE_OPTIONS);
}

add_action('admin_menu', 'schuetziwoche_admin_add_page');


function schuetziwoche_admin_options_page(){
	$options = get_option(SCHUETZIWOCHE_OPTIONS);

	echo '<div class="wrap">
        <h2>Sch&uuml;tziwoche Optionen</h2>
        <form method="post" action="options.php">';
            echo settings_fields(SCHUETZIWOCHE_OPTIONS_GROUP);
            echo '<table class="form-table">
                <tr valign="top"><th scope="row">Erster Tag der Schütziwoche (Unix-Timestamp in GMT!):</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[start_date]" value="'. $options['start_date'] .'" /> Aktuelle Einstellung: '.date('d.m.Y H:i', $options['start_date']).' (Lokalzeit)</td>
                    <td><b>Achtung: Es MUSS die Uhrzeit 00:00 (Lokalzeit) gewählt werden im Timestamp!</b> (Ansonsten sind die Anmeldelimiten verschoben)</td>
                </tr>
                <tr valign="top"><th scope="row">Limit f&uuml;r Essensanmeldung:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[limit_eat]" value="'. $options['limit_eat'] .'" /> Uhr (Uhrzeit in Stunden)</td>
                </tr>
                <tr valign="top"><th scope="row">Limit f&uuml;r Schlafensanmeldung:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[limit_sleep]" value="'. $options['limit_sleep'] .'" /> Uhr (Uhrzeit in Stunden)</td>
                </tr>
                <tr valign="top"><th scope="row">Kosten (Znacht & Übernachtung):</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[cost_eat]" value="'. $options['cost_eat'] .'" /> Fr.</td>
                </tr>
                <tr valign="top"><th scope="row">Kosten (nur Übernachtung):</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[cost_sleep]" value="'. $options['cost_sleep'] .'" /> Fr.</td>
                </tr>
                <tr valign="top"><th scope="row">Auswahlmöglichkeiten der Abteilungen <br>(durch Semikolon und <b>ohne</b> Abstand abtrennen)</br>:</th>
                    <td><input style="width: 100%;" type="textarea" name="'. SCHUETZIWOCHE_OPTIONS.'[abteilungen]" value="'. $options['abteilungen'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">Abteilungen, welche Kosten Übernehmen <br>(durch Semikolon und <b>ohne</b> Abstand abtrennen,<br>unbedingt gleich schreiben wie oben!):</th>
                <td><input style="width: 100%;" type="textarea" name="'. SCHUETZIWOCHE_OPTIONS.'[abteilungen_paying]" value="'. $options['abteilungen_paying'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">E-Mail Absender:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[email_sender_address]" value="'. $options['email_sender_address'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">E-Mail Notifications to:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[email_notification_address]" value="'. $options['email_notification_address'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">Farbe Hintergrund:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[color_bg]" value="'. $options['color_bg'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">Farbe Text:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[color_text]" value="'. $options['color_text'] .'" /></td>
                </tr>
                <tr valign="top"><th scope="row">Farbe Rahmen:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[color_border]" value="'. $options['color_border'] .'" /></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" class="button-primary" value="Speichern" />
            </p>
        </form>
    </div>';
}


function schuetziwoche_admin_overview() {
    global $wpdb;
	$config = schuetziwoche_get_config();

        $result = $wpdb->get_results("SELECT * FROM ".$config['table']." ORDER BY abteilung ASC");
        $abteilungen = explode(";", $config["abteilungen"]);
    
        $out = '<style>
                .sw_overview ul {
                    list-style-type: none;
                    margin: 0;
                    padding: 0;
                }
            
                .sw_overview li:before {
                    content: "•";
                    font-size: 1.5em;
                    margin-right: 0.5em;
                }

                .sw_overview .tab {
                    margin-left: 2em;
                }

                .sw_overview .abteilung {
                    margin-bottom: 1em;
                }

                .sw_overview .double_ul {
                    display: inline-block;
                    border-bottom: 3px double;
                }
            </style>';

        $out .= '<div class="sw_overview wrap">';
        $out .= '<h1>Übersicht und Statistiken Schütziwoche</h1>';
        $out .= '<h2>Inhaltsverzeichnis</h2>
                <ul>
                    <li><a href="#wochentag">Statistik nach Wochentag</a></li>
                    <li><a href="#abteilung">Statistik und Abrechnung nach Abteilung</a>
                    <ul class="tab">';
        foreach ($abteilungen as $abteilung) {
            $out .= '<li><a href="#' .$abteilung .'">' .$abteilung .'</a></li>';
        }
        $out .= '</ul></li>
                <li><a href="#gesamt">Gesamtstatistik</a></li>
                </ul>';
        $out .= '<hr><h2 id="wochentag">Statistik nach Wochentag</h2>';
        
        $total = 0;
        $total_sleep = 0;
        $total_eat = 0;
        $total_sleeponly = 0;
        $total_cost = 0;
        foreach ($result as $row) {
            $total++;
        }
        
        $row = $wpdb->get_row("SELECT SUM(isvegi) as isvegi, SUM(mo_eat) as mo_eat, SUM(mo_sleep) as mo_sleep ,SUM(di_eat) as di_eat, SUM(di_sleep) as di_sleep ,SUM(mi_eat) as mi_eat, SUM(mi_sleep) as mi_sleep ,SUM(do_eat) as do_eat, SUM(do_sleep) as do_sleep ,SUM(fr_eat) as fr_eat, SUM(fr_sleep) as fr_sleep  FROM ".$config['table']."");
        $row->isvegi = $row->isvegi !== NULL ? $row->isvegi : 0;
        $total = $total !== NULL ? $total : 0;
        
        $out .= '<h3>Mo, '. date('d.n',$config['date'][1]) .'</h3>';
        $vegi = $wpdb->get_var("SELECT SUM(isvegi) FROM ".$config['table']." WHERE mo_eat = 1");
        $vegi = $vegi !== NULL ? $vegi : 0;
        $row->mo_eat = $row->mo_eat !== NULL ? $row->mo_eat : 0;
        $row->mo_sleep = $row->mo_sleep !== NULL ? $row->mo_sleep : 0;
        $out .= '<ul>
                    <li>Znacht: <b>' .$row->mo_eat .' Personen </b>(' .$vegi .' davon Vegi)</li>
                    <li>Übernachtung: <b>' .$row->mo_sleep .' Personen</b></li>
                </ul>';

        $out .= '<h3>Di, '. date('d.n',$config['date'][2]) .'</h3>';
        $vegi = $wpdb->get_var("SELECT SUM(isvegi) FROM ".$config['table']." WHERE di_eat = 1");
        $vegi = $vegi !== NULL ? $vegi : 0;
        $row->di_eat = $row->di_eat !== NULL ? $row->di_eat : 0;
        $row->di_sleep = $row->di_sleep !== NULL ? $row->di_sleep : 0;
        $out .= '<ul>
                    <li>Znacht: <b>' .$row->di_eat .' Personen </b>(' .$vegi .' davon Vegi)</li>
                    <li>Übernachtung: <b>' .$row->di_sleep .' Personen</b></li>
                </ul>';

        $out .= '<h3>Mi, '. date('d.n',$config['date'][3]) .'</h3>';
        $vegi = $wpdb->get_var("SELECT SUM(isvegi) FROM ".$config['table']." WHERE mi_eat = 1");
        $vegi = $vegi !== NULL ? $vegi : 0;
        $row->mi_eat = $row->mi_eat !== NULL ? $row->mi_eat : 0;
        $row->mi_sleep = $row->mi_sleep !== NULL ? $row->mi_sleep : 0;
        $out .= '<ul>
                    <li>Znacht: <b>' .$row->mi_eat .' Personen </b>(' .$vegi .' davon Vegi)</li>
                    <li>Übernachtung: <b>' .$row->mi_sleep .' Personen</b></li>
                </ul>';

        $out .= '<h3>Do, '. date('d.n',$config['date'][4]) .'</h3>';
        $vegi = $wpdb->get_var("SELECT SUM(isvegi) FROM ".$config['table']." WHERE do_eat = 1");
        $vegi = $vegi !== NULL ? $vegi : 0;
        $row->do_eat = $row->do_eat !== NULL ? $row->do_eat : 0;
        $row->do_sleep = $row->do_sleep !== NULL ? $row->do_sleep : 0;
        $out .= '<ul>
                    <li>Znacht: <b>' .$row->do_eat .' Personen </b>(' .$vegi .' davon Vegi)</li>
                    <li>Übernachtung: <b>' .$row->do_sleep .' Personen</b></li>
                </ul>';

        $out .= '<h3>Fr, '. date('d.n',$config['date'][5]) .'</h3>';
        $vegi = $wpdb->get_var("SELECT SUM(isvegi) FROM ".$config['table']." WHERE fr_eat = 1");
        $vegi = $vegi !== NULL ? $vegi : 0;
        $row->fr_eat = $row->fr_eat !== NULL ? $row->fr_eat : 0;
        $row->fr_sleep = $row->fr_sleep !== NULL ? $row->fr_sleep : 0;
        $out .= '<ul>
                    <li>Znacht: <b>' .$row->fr_eat .' Personen </b>(' .$vegi .' davon Vegi)</li>
                    <li>Übernachtung: <b>' .$row->fr_sleep .' Personen</b></li>
                </ul>';

        $out .= '<hr><h2 id="abteilung">Statistik und Abrechnung nach Abteilung</h2>';
        $out .= '<i>(Wieder Abgemeldete in Anzahl inkludiert, jedoch nicht in Kosten)</i>';
        foreach ($abteilungen as $abteilung) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM ".$config['table']." WHERE abteilung = '" .$abteilung ."'");
            $count_sleep = $wpdb->get_var("SELECT SUM(mo_sleep + di_sleep + mi_sleep + do_sleep + fr_sleep) FROM ".$config['table']." WHERE abteilung = '" .$abteilung ."'");
            $count_sleep = $count_sleep !== NULL ? $count_sleep : 0;
            $total_sleep += $count_sleep;
            $count_eat = $wpdb->get_var("SELECT SUM(mo_eat + di_eat + mi_eat + do_eat + fr_eat) FROM ".$config['table']." WHERE abteilung = '" .$abteilung ."'");
            $count_eat = $count_eat !== NULL ? $count_eat : 0;
            $total_eat += $count_eat;
            $count_sleeponly = $wpdb->get_var("SELECT SUM(CASE WHEN mo_sleep = 1 AND mo_eat = 0 THEN 1 ELSE 0 END + 
                                                CASE WHEN di_sleep = 1 AND di_eat = 0 THEN 1 ELSE 0 END + 
                                                CASE WHEN mi_sleep = 1 AND mi_eat = 0 THEN 1 ELSE 0 END + 
                                                CASE WHEN do_sleep = 1 AND do_eat = 0 THEN 1 ELSE 0 END + 
                                                CASE WHEN fr_sleep = 1 AND fr_eat = 0 THEN 1 ELSE 0 END) 
                                    FROM ".$config['table']." 
                                    WHERE abteilung = '".$abteilung."'");
            $count_sleeponly = $count_sleeponly !== NULL ? $count_sleeponly : 0;
            $total_sleeponly += $count_sleeponly;
            $cost = $count_sleeponly * $config['cost_sleep'] + $count_eat * $config['cost_eat'];
            $total_cost += $cost;
            $out .= '<ul>';
            $out .= '<li id="' .$abteilung .'" class="abteilung"><b>' .$abteilung .'</b> (' .$count .' Personen)';
            $out .= '<ul class="tab">
                        <li><b>' .$count_sleep .' Übernachtungen</b> (nicht verrechnungsrelevant)</li>
                        <li><b>' .$count_sleeponly .' Übernachtungen ohne Znacht</b> (à ' .$config['cost_sleep'] .' Fr.)</li>
                        <li><b>' .$count_eat .' Znacht mit (oder ohne) Übernachtung</b> (à ' .$config['cost_eat'] .' Fr.)</li>
                        <li class="double_ul">Kosten (wenn der Abteilung verrechnet wird): <b>' .$cost .' Fr.</b></li>
                    </ul></li>';
            $out .= '</ul>';
        }

        $out .= '<hr><h2 id="gesamt">Gesamtstatistik</h2>';
        $out .= '<ul>
                    <li>Angemeldete Personen: <b>' .$total .' Personen </b></li>
                    <li>Vegis: <b>' .$row->isvegi .' Personen</b></li>
                    <li>Übernachtungen: <b>' .$total_sleep .' Übernachtungen</b></li>
                    <li>Übernachtungen ohne Znacht: <b>' .$total_sleeponly .' Übernachtungen</b></li>
                    <li>Znacht: <b>' .$total_eat .' Znachts</b></li>
                    <li>Einnahmen: <b>' .$total_cost .' Fr.</b></li>

                </ul>';

        $out .= '</div>';
        echo $out;
}


function schuetziwoche_admin_registrations(){
	$options = get_option(SCHUETZIWOCHE_OPTIONS);

    //Create an instance of our package class...
    $testListTable = new TT_Example_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();
    
    $out = '<div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="'.$_REQUEST['page'] .'"/>
            <!-- Now we can render the completed list table -->'
            .$testListTable->display()
        .'</form>
        
    </div>';

    echo $out;
}

function schuetziwoche_admin_add_page(){
    add_menu_page('Schütziwoche - Übersicht', 'Schütziwoche', 'edit_pages', SCHUETZIWOCHE_MENU_SLUG, 'schuetziwoche_admin_overview', plugins_url() . '/schuetziwoche/img/plugin-logo.png', 0 );
    add_submenu_page( SCHUETZIWOCHE_MENU_SLUG, 'Schütziwoche - Tabelle', 'Anmeldetabelle', 'manage_options', SCHUETZIWOCHE_MENU_SLUG .'-table', 'schuetziwoche_admin_registrations');
	add_submenu_page( SCHUETZIWOCHE_MENU_SLUG, 'Schütziwoche - Optionen', 'Optionen', 'manage_options', SCHUETZIWOCHE_MENU_SLUG .'-options', 'schuetziwoche_admin_options_page');
	//add_options_page('Sch&uuml;tziwoche', 'Sch&uuml;tziwoche', 'manage_options', SCHUETZIWOCHE_MENU_SLUG, 'schuetziwoche_admin_options_page');
}


