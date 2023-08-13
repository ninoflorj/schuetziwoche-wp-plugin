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
                <tr valign="top"><th scope="row">Erster Tag der Schütziwoche (Timestamp in GMT!):</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[start_date]" value="'. $options['start_date'] .'" /> (Unix Timestamp) Aktuelle Einstellung: '.date('d.m.Y', $options['start_date']).'</td>
                </tr>
                <tr valign="top"><th scope="row">Limit f&uuml;r Essensanmeldung:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[limit_eat]" value="'. $options['limit_eat'] .'" /> Uhr (Uhrzeit in Stunden)</td>
                </tr>
                <tr valign="top"><th scope="row">Limit f&uuml;r Schlafensanmeldung:</th>
                    <td><input type="text" name="'. SCHUETZIWOCHE_OPTIONS.'[limit_sleep]" value="'. $options['limit_sleep'] .'" /> Uhr (Uhrzeit in Stunden)</td>
                </tr>
                <tr valign="top"><th scope="row">Auswahlmöglichkeiten der Abteilungen <br>(durch Semikolon und <b>ohne</b> Abstand abtrennen)</br>:</th>
                    <td><input style="width: 100%;" type="textarea" name="'. SCHUETZIWOCHE_OPTIONS.'[abteilungen]" value="'. $options['abteilungen'] .'" /></td>
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


function schuetziwoche_admin_registrations(){
	$options = get_option(SCHUETZIWOCHE_OPTIONS);

    //Create an instance of our package class...
    $testListTable = new TT_Example_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();
    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Anmeldungen</h2>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
        </form>
        
    </div>
    <?php
}

function schuetziwoche_admin_add_page(){

    add_menu_page('Schütziwoche - Tabelle', 'Schütziwoche', 'edit_pages', SCHUETZIWOCHE_MENU_SLUG, 'schuetziwoche_admin_registrations', plugins_url() . '/schuetziwoche/img/plugin-logo.png', 0 );
	add_submenu_page( SCHUETZIWOCHE_MENU_SLUG, 'Schütziwoche - Optionen', 'Optionen', 'manage_options', SCHUETZIWOCHE_MENU_SLUG .'-options', 'schuetziwoche_admin_options_page');
	//add_options_page('Sch&uuml;tziwoche', 'Sch&uuml;tziwoche', 'manage_options', SCHUETZIWOCHE_MENU_SLUG, 'schuetziwoche_admin_options_page');
}


