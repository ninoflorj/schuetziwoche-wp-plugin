<?php
/*
Plugin Name: Schuetziwoche
Plugin URI: http://www.holderegger.org
Description: Schuetziwoche Anmeldungs Plugin
Version: 1.2
Author: Demian Holderegger / Nino Florjancic
Author URI: http://www.holderegger.org
*/

/*
Schuetziwoche (Wordpress Plugin)
Copyright (C) 2013 Demian Holderegger
Contact me at http://www.holderegger.org

The Plugin was Modified and Optimized in 2023 by Nino Florjancic v/o Sueno

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// For debugging, uncomment these 2 Lines:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

DEFINE("SCHUETZIWOCHE_TABLE", "schuetziwoche_anmeldungen");
DEFINE("SCHUETZIWOCHE_OPTIONS", "schuetziwoche-options");
DEFINE("SCHUETZIWOCHE_OPTIONS_GROUP", "schuetziwoche-options-group");
DEFINE("SCHUETZIWOCHE_MENU_SLUG", "schuetziwoche-menu");

if ( is_admin() )
	require_once dirname( __FILE__ ) . '/admin.php';

//tell wordpress to register the schuetziwoche shortcode
add_shortcode("schuetziwoche", "schuetziwoche_handler");
add_action('wp_head', 'schuetziwoche_css');

// Set the Timezone to Display when date() is called
date_default_timezone_set('Europe/Zurich');

function schuetziwoche_handler() {
	$output = schuetziwoche_function();
	return $output;
}

function schuetziwoche_function() {
	global $wpdb;
	$config = schuetziwoche_get_config();

	if ($config['start_date'] + 60*60*24*6 < time()) {
		return '<h2>Anmeldung geschlossen</h2><h4>Die Anmeldung wird spätestens zwei Wochen vor der Schütziwoche geöffnet</h4>';
	}
	elseif (($_REQUEST['swpage']=='bearbeiten' && ($_REQUEST['sw_s'] || $_REQUEST['email'])) || ($_REQUEST['swpage']=='anmeldung' || $_REQUEST['swpage']=='bearbeiten_email') && isset($_COOKIE['schuetziwoche_user'])){
		$output = schuetziwoche_bearbeiten();
	}elseif ($_REQUEST['swpage']=='update'){
		$output = schuetziwoche_update();
	}elseif ($_REQUEST['swpage']=='force_anmeldung' || $_REQUEST['swpage']=='anmeldung'){
		$output = schuetziwoche_anmeldung();
	}elseif ($_REQUEST['swpage']=='save'){
		$output = schuetziwoche_save();
	}elseif ($_REQUEST['swpage']=='liste'){
		$output = schuetziwoche_liste();
	}elseif ($_REQUEST['swpage']=='bearbeiten_email' || $_REQUEST['swpage']=='force_bearbeiten_email'){
		$output = schuetziwoche_bearbeiten_email();
	}
	else{
		if (isset($_COOKIE['schuetziwoche_user'])){
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$config['table']." WHERE hash = %s LIMIT 1", $_COOKIE['schuetziwoche_user']));
			if ($row->name){
				$output = schuetziwoche_bearbeiten();
			}
			else{
				$output = schuetziwoche_liste();
			}
		}else{
			$output = schuetziwoche_liste();
		}
	}

	return '<div id="schuetziwoche">'.$output.'<div>';
}

function schuetziwoche_get_config() {

	global $wpdb;

	$config = get_option(SCHUETZIWOCHE_OPTIONS);

	$eventdate[1] = $config['start_date'];
	$eventdate[2] = $config['start_date'] + 60*60*24*1;
	$eventdate[3] = $config['start_date'] + 60*60*24*2;
	$eventdate[4] = $config['start_date'] + 60*60*24*3;
	$eventdate[5] = $config['start_date'] + 60*60*24*4;
	$eventdate[6] = $config['start_date'] + 60*60*24*5;

	
	$config['date'] = $eventdate;
	$config['table'] = $wpdb->prefix . SCHUETZIWOCHE_TABLE;
	$config['imgurl'] = plugins_url() . '/schuetziwoche/img/';
	$config['limit_eat'] = $config['limit_eat']*60*60;
	$config['limit_sleep'] = $config['limit_sleep']*60*60;

	return $config;
}

function schuetziwoche_css(){
	$config = schuetziwoche_get_config();
	
	$css = file_get_contents(dirname( __FILE__ ) . '/css/styles.css');
	$css = str_replace('{COLOR_BG}', $config['color_bg'], $css);
	$css = str_replace('{COLOR_TEXT}', $config['color_text'], $css);
	$css = str_replace('{COLOR_BORDER}', $config['color_border'], $css);
	echo '<style type="text/css">
	<!--
	'.$css.'
	-->
	</style>';
}

function schuetziwoche_bearbeiten() {

	global $wpdb;
	$config = schuetziwoche_get_config();

	if (isset($_REQUEST['email'])) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$config['table']." WHERE email = %s LIMIT 1", $_REQUEST['email']));
	}
	else if (isset($_REQUEST['sw_s'])) {
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$config['table']." WHERE hash = %s LIMIT 1", $_REQUEST['sw_s']));
	}
	else{
		$hash = $_COOKIE['schuetziwoche_user'];
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$config['table']." WHERE hash = %s LIMIT 1", $_COOKIE['schuetziwoche_user']));
	}

	if ($row->name){
		$hash = $row->hash;
		$out  = '<h2>Anmeldung von '.$row->name.' bearbeiten</h2>';
		$out .= '<a href="'.add_query_arg('swpage','force_anmeldung').'"><b>Neue Person anmelden &raquo;</b></a><br>';
		$out .= '<a href="'.add_query_arg('swpage','force_bearbeiten_email').'"><b>Bestehende Anmeldung bearbeiten &raquo;</b></a><br><br>';
		$out .= '<form action="'.add_query_arg(array('swpage' => 'update', 'sw_s' => $hash)).'" method="post">';
		$out .= '<table class="anmeldung_tagwahl" cellspacing="1">
			<tr>
				<th>Vegi</th>
				<th colspan="2">Mo '.date('d.n',$config['date'][1]).'</th>
				<th colspan="2">Di '.date('d.n',$config['date'][2]).'</th>
				<th colspan="2">Mi '.date('d.n',$config['date'][3]).'</th>
				<th colspan="2">Do '.date('d.n',$config['date'][4]).'</th>
				<th colspan="2">Fr '.date('d.n',$config['date'][5]).'</th>
			</tr>
			<tr>';
		$out .= '<td><img src="'.$config['imgurl'].'vegi.png" title="Ich esse keine Tiere!"><br><input type="checkbox" '.($row->isvegi?'checked="checked"':'').' name="isvegi" value="1"></td>';
		$out .= '<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" '.($row->mo_eat?'checked="checked"':'').' name="mo_eat" value="1" '.(time()>($config['date'][1]+$config['limit_eat'])?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" '.($row->mo_sleep?'checked="checked"':'').' name="mo_sleep" value="1" '.(time()>$config['date'][1]+$config['limit_sleep']?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" '.($row->di_eat?'checked="checked"':'').' name="di_eat" value="1" '.(time()>($config['date'][2]+$config['limit_eat'])?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" '.($row->di_sleep?'checked="checked"':'').' name="di_sleep" value="1" '.(time()>$config['date'][2]+$config['limit_sleep']?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" '.($row->mi_eat?'checked="checked"':'').' name="mi_eat" value="1" '.(time()>($config['date'][3]+$config['limit_eat'])?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" '.($row->mi_sleep?'checked="checked"':'').' name="mi_sleep" value="1" '.(time()>$config['date'][3]+$config['limit_sleep']?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" '.($row->do_eat?'checked="checked"':'').' name="do_eat" value="1" '.(time()>($config['date'][4]+$config['limit_eat'])?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" '.($row->do_sleep?'checked="checked"':'').' name="do_sleep" value="1" '.(time()>$config['date'][4]+$config['limit_sleep']?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" '.($row->fr_eat?'checked="checked"':'').' name="fr_eat" value="1" '.(time()>($config['date'][5]+$config['limit_eat'])?'disabled="disabled"':'').'></td>';
		$out .= '<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" '.($row->fr_sleep?'checked="checked"':'').' name="fr_sleep" value="1" '.(time()>$config['date'][5]+$config['limit_sleep']?'disabled="disabled"':'').'></td>';
		$out .= '</tr>
			</table>';
		$out .= '<input type="submit" name="submit" value="Speichern">';
		$out .= '</form>';
		$out .= '<a href="'.add_query_arg(array('swpage' => 'liste', 'sw_s' => false)).'">Zur&uuml;ck zur &Uuml;bersicht</a>';
		// Please dont kill me for the following dynamically generated javascript (setting a Cookie from a shortcode is pain in the ass otherwise)
		$out .= '<script>
		const d = new Date();
		d.setTime(d.getTime() + (3*30*24*60*60*1000));
		let expires = "expires="+ d.toUTCString();
		// Yes, the next line is dynamically generated on the server. I know its shit.
		document.cookie = "schuetziwoche_user='.$hash.';" + expires;
		</script>';

	}else{
		$out  =  '<script>document.cookie = "schuetziwoche_user=; expires=Thu, 01 Jan 1970 00:00:00 UTC;";</script>';
		$out .= 'Anmeldung nicht gefunden. Hast du die richtige Email eingegeben oder den richtigen Link genommen?<br><br>';
		$out .= '<b><a href="'.add_query_arg('swpage','bearbeiten_email').'"><b>Nochmals probieren &raquo;</b></a></b><br><br>';
		$out .= '<b><a href="'.add_query_arg('swpage','force_anmeldung').'"><b>Stattdessen als neuer Benutzer anmelden &raquo;</b></a></b><br><br>';
		$out .= '<a href="?swpage=liste"><b>Zur&uuml;ck zur &Uuml;bersicht &raquo;</b></a>';
	}
	return $out;
}

function schuetziwoche_bearbeiten_email() {
	$config = schuetziwoche_get_config();

	return '<h2>Anmeldung bearbeiten</h2>
		Bitte benutze <b>die selbe Email wie du zur ursprünglichen Anmeldung genutzt hast</b>, sonst wird wird deine Anmeldung nicht gefunden!<br>
		<br>
		<form action="'.add_query_arg('swpage','bearbeiten').'" method="post">
		<div class="fluid_form">
			<div class="row">
				<div class="label">Emailadresse: </div>
				<div class="value"><input name="email" type="email" size="30" maxlength="100"></div>
			</div>
		</div>
		<br>
		<br>

		<input type="submit" name="submit" value="Anmeldung bearbeiten">

		</form>
		<br>
		<a href="?swpage=liste">Zur&uuml;ck zur &Uuml;bersicht</a>';
}

function schuetziwoche_update() {
	global $wpdb;
	$config = schuetziwoche_get_config();

	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$config['table']." WHERE hash = %s LIMIT 1", $_REQUEST['sw_s']));
	if ($row->name){
		$query = "UPDATE ".$config['table']." SET
			isvegi = '".($_POST['isvegi']?1:0)."' 
			".(time()<($config['date'][1]+$config['limit_eat'])?", mo_eat = '".($_POST['mo_eat']?1:0)."'":'')."
			".(time()< $config['date'][1]+$config['limit_sleep']?", mo_sleep = '".($_POST['mo_sleep']?1:0)."'":'')."
			".(time()<($config['date'][2]+$config['limit_eat'])?", di_eat = '".($_POST['di_eat']?1:0)."'":'')."
			".(time()< $config['date'][2]+$config['limit_sleep']?", di_sleep = '".($_POST['di_sleep']?1:0)."'":'')."
			".(time()<($config['date'][3]+$config['limit_eat'])?", mi_eat = '".($_POST['mi_eat']?1:0)."'":'')."
			".(time()< $config['date'][3]+$config['limit_sleep']?", mi_sleep = '".($_POST['mi_sleep']?1:0)."'":'')."
			".(time()<($config['date'][4]+$config['limit_eat'])?", do_eat = '".($_POST['do_eat']?1:0)."'":'')."
			".(time()< $config['date'][4]+$config['limit_sleep']?", do_sleep = '".($_POST['do_sleep']?1:0)."'":'')."
			".(time()<($config['date'][5]+$config['limit_eat'])?", fr_eat = '".($_POST['fr_eat']?1:0)."'":'')."
			".(time()< $config['date'][5]+$config['limit_sleep']?", fr_sleep = '".($_POST['fr_sleep']?1:0)."'":'')."
			WHERE hash = %s LIMIT 1";
			
		$wpdb->query($wpdb->prepare($query, $_REQUEST['sw_s']));
		$out  = 'Angaben ge&auml;ndert!<br><br>';
		$out .= '<a href="'.add_query_arg(array('swpage' => 'liste', 'sw_s' => false)).'"><b>Zur&uuml;ck zur &Uuml;bersicht &raquo;</b></a>';
	} else {
		$out = 'Anmeldung nicht gefunden. Hast du den richtigen Link genommen?';
	}
	return $out;
}

function get_abteilungen_dropdown() {
	$config = schuetziwoche_get_config();
	$output = "";
	$output = '<option value="">Bitte wähle eine Abteilung</option>';
	$abteilungen = explode(";", $config["abteilungen"]);
	foreach ($abteilungen as $abteilung) {
		$output .= '<option value="' . $abteilung . '">' . $abteilung . '</option>';
	}
	return $output;
}

function schuetziwoche_anmeldung() {
	$config = schuetziwoche_get_config();

	return '<h2>Anmeldung</h2>
		Falls du Znacht essen willst, solltest du dich sp&auml;testens <b>um '.floor($config['limit_eat']/(60*60)).' Uhr am selben Tag</b><br>angemeldet haben, <b>sonst bezahlst du 2.- mehr!</b> (Du kannst dich dann auch nicht mehr über dieses Tool anmelden. Komm dann einfach unangemeldet vorbei.)<br>
		<br>
		<form action="'.add_query_arg('swpage','save').'" method="post">
		<div class="fluid_form">
			<div class="row">
				<div class="label">Pfadiname: </div>
				<div class="value"><input name="pfadiname" type="text" size="30" maxlength="100" required></div>
			</div>
			<div class="row">
				<div class="label">Emailadresse: </div>
				<div class="value"><input name="email" type="email" size="30" maxlength="100" required></div>
			</div>
			<div class="row">
				<div class="label">Abteilung: </div>
				<div class="value">
					<select name="abteilung" required>'
						. get_abteilungen_dropdown()
					. '</select>
				</div>
			</div>
			<div class="row">
				<div class="label">Vegi: </div>
				<div class="value"><label><input type="checkbox" name="isvegi" value="1"> Ich esse keine Tiere <img src="'.$config['imgurl'].'vegi.png" /></label></div>
			</div>
		</div>
		<div class="clear"></div>
		<br>
		<br>
		<b>Nachtessen und &Uuml;bernachtungen w&auml;hlen:</b><br>
		<table class="anmeldung_tagwahl" cellspacing="1">
			<tr>
				<th colspan="2">Mo '. date('d.n',$config['date'][1]) .'</th>
				<th colspan="2">Di '. date('d.n',$config['date'][2]) .'</th>
				<th colspan="2">Mi '. date('d.n',$config['date'][3]) .'</th>
				<th colspan="2">Do '. date('d.n',$config['date'][4]) .'</th>
				<th colspan="2">Fr '. date('d.n',$config['date'][5]) .'</th>
			</tr>
			<tr>
				<td><label><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" name="mo_eat" value="1" '. (time()>($config['date'][1]+$config['limit_eat'])?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" name="mo_sleep" value="1" '. (time()>$config['date'][1]+$config['limit_sleep']?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" name="di_eat" value="1" '. (time()>($config['date'][2]+$config['limit_eat'])?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" name="di_sleep" value="1" '. (time()>$config['date'][2]+$config['limit_sleep']?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" name="mi_eat" value="1" '. (time()>($config['date'][3]+$config['limit_eat'])?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" name="mi_sleep" value="1" '. (time()>$config['date'][3]+$config['limit_sleep']?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" name="do_eat" value="1" '. (time()>($config['date'][4]+$config['limit_eat'])?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" name="do_sleep" value="1" '. (time()>$config['date'][4]+$config['limit_sleep']?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"><br><input type="checkbox" name="fr_eat" value="1" '. (time()>($config['date'][5]+$config['limit_eat'])?'disabled="disabled"':'') .'></label></td>
				<td><label><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"><br><input type="checkbox" name="fr_sleep" value="1" '. (time()>$config['date'][5]+$config['limit_sleep']?'disabled="disabled"':'') .'></label></td>
			</tr>
		</table>
		<br>
		<br>

		<input type="submit" name="submit" value="Anmelden">

		</form>
		<br>
		<a href="?swpage=liste">Zur&uuml;ck zur &Uuml;bersicht</a>';

}

function schuetziwoche_save() {
	if (!$_POST['pfadiname'] || !$_POST['email']){
		return 'Bitte mindestens Name und Emailadresse eingeben!';
	}else{
		global $wpdb;
		$config = schuetziwoche_get_config();

		$time = time();
		$hash = substr(md5($time), 0, 14);
		$query = "INSERT INTO ".$config['table']." (date, hash, name, email, abteilung, isvegi, mo_eat, mo_sleep, di_eat, di_sleep, mi_eat, mi_sleep, do_eat, do_sleep, fr_eat, fr_sleep) VALUES (
			'".$time."',
			'".$hash."',
			'%s',
			'%s',
			'%s',
			'".($_POST['isvegi']?1:0)."',
			'".($_POST['mo_eat']&&time()<($config['date'][1]+$config['limit_eat'])?1:0)."',
			'".($_POST['mo_sleep']&&time()<$config['date'][1]+$config['limit_sleep']?1:0)."',
			'".($_POST['di_eat']&&time()<($config['date'][2]+$config['limit_eat'])?1:0)."',
			'".($_POST['di_sleep']&&time()<$config['date'][2]+$config['limit_sleep']?1:0)."',
			'".($_POST['mi_eat']&&time()<($config['date'][3]+$config['limit_eat'])?1:0)."',
			'".($_POST['mi_sleep']&&time()<$config['date'][3]+$config['limit_sleep']?1:0)."',
			'".($_POST['do_eat']&&time()<($config['date'][4]+$config['limit_eat'])?1:0)."',
			'".($_POST['do_sleep']&&time()<$config['date'][4]+$config['limit_sleep']?1:0)."',
			'".($_POST['fr_eat']&&time()<($config['date'][5]+$config['limit_eat'])?1:0)."',
			'".($_POST['fr_sleep']&&time()<$config['date'][5]+$config['limit_sleep']?1:0)."'
			)";
		$wpdb->query($wpdb->prepare($query, $_POST['pfadiname'], $_POST['email'], $_POST['abteilung']));
		
		$out  = '<h2>Anmelden</h2>';
		$out .= 'Danke f&uuml;r deine Anmeldung '.$_POST['pfadiname'].', man sieht sich bald an der Sch&uuml;tziwoche!<br><br>';
		$out .= 'Du hast auch ein Best&auml;tigungsmail bekommen mit dem Link, um die Anmeldung zu ändern. Bitte schau auch im Spam-Ordner nach, falls du es nicht findest.';
		$out .= '<br><b>Bitte ändere deine Anmeldung über den Link im Mail oder über den "Anmeldung ändern" Link auf der Anmeldeseite, falls sich deine Pläne ändern.</b>';
		$out .= '<br>Dieses Gerät sollte sich auch automatisch an dich erinnern, falls du deine Anmeldung später nochmals ändern willst.<br><br>';
		$out .= '<a href="'.add_query_arg('swpage','liste').'"><b>Wer hat sich sonst noch angemeldet? &raquo;</b></a><br><br>';
		$out .= '<a href="'.add_query_arg(array('swpage' => 'bearbeiten', 'sw_s' => $hash)).'"><b>Anmeldung nochmals &auml;ndern &raquo;</b></a><br><br>';
		// Please dont kill me for the following dynamically generated javascript (setting a Cookie from a shortcode is pain in the ass otherwise)
		$out .= '<script>
		const d = new Date();
		d.setTime(d.getTime() + (3*30*24*60*60*1000));
		let expires = "expires="+ d.toUTCString();
		// Yes, the next line is dynamically generated on the server. I know its shit.
		document.cookie = "schuetziwoche_user='.$hash.';" + expires;
		</script>';

		$nachricht = 'Hallo '.$_POST['pfadiname'].',' . "\r\n" .
			'Du hast dich für die Schütziwoche '.date('Y', $config['date'][1]).' angemeldet. ' . "\r\n" .
			'Falls du deine Anmeldung ändern mächtest kannst du dies mit folgendem Link tun:' . "\r\n" .
			get_option('home') . add_query_arg(array('swpage' => 'bearbeiten', 'sw_s' => $hash)) . "\r\n" .
			'Wir bitten dich, dies auch wirklich zu tun, falls sich deine Pläne ändern!' . "\r\n" . "\r\n" .
			'Ausserdem sollte sich das Gerät auf welchem du dich angemeldet hast an dich erinnern, wenn du die Seite erneut aufrufst. Der Link sollte dann also gar nicht nötig sein.' . "\r\n" .
			'Du kannst ausserdem auch auf einem anderen Gerät deine Anmeldung bearbeiten ohne dass du den Link benötigst. Alles was du machen musst, ist deine E-Mail einzugeben auf der Webseite.'. "\r\n" . "\r\n" .
			'Pfadigrüsse,'. "\r\n" .
			'Dein Schütziwoche Team';
		$subject = '=?UTF-8?B?' . base64_encode('Anmeldung Schütziwoche') . '?=';
		$header = 'From: ' . $config['email_sender_address'] . "\r\n" .
          'Reply-To: ' . $config['email_sender_address'] . "\r\n" .
          'MIME-Version: 1.0' . "\r\n" .
          'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
          'Content-Transfer-Encoding: 8bit' . "\r\n" .
          'X-Mailer: PHP/' . phpversion();
		
		mail($_POST['email'], $subject, $nachricht, $header);
		mail($config['email_notification_address'],'[Anmeldung] '.$_POST['pfadiname'],'Neue Anmeldung von '.$_POST['pfadiname'].' ('.$_POST['abteilung'].'), '.$_POST['email']."\n\n ".get_option('home') . add_query_arg(array('swpage' => 'list')), $header);
		
		return $out;			
	}

}

function schuetziwoche_liste() {

	global $wpdb;
	$config = schuetziwoche_get_config();

	$orderby = 'name';
	if ($_GET['orderby']=='date') $orderby = 'date';
	if ($_GET['orderby']=='abteilung') $orderby = 'abteilung';

	$result = $wpdb->get_results("SELECT * FROM ".$config['table']." ORDER BY ".$orderby." ASC");
	
	$out = '<h2>Wer kommt?</h2>
	Wer kommt sonst noch alles an die Sch&uuml;tziwoche? Hier kannst du es sehen!<br><br>
	<b><a href="'.add_query_arg('swpage','anmeldung').'">Ich will mich Anmelden &raquo;</a></b><br />
	<b><a href="'.add_query_arg('swpage','bearbeiten_email').'">Ich will meine Anmeldung ändern &raquo;</a></b><br /><br />
	<table class="uebersicht_anmeldungen" cellspacing="1">
		<tr>
			<th colspan="2">Name / Abteilung</th>
			<th colspan="2">Mo '. date('d.n',$config['date'][1]) .'</th>
			<th colspan="2">Di '. date('d.n',$config['date'][2]) .'</th>
			<th colspan="2">Mi '. date('d.n',$config['date'][3]) .'</th>
			<th colspan="2">Do '. date('d.n',$config['date'][4]) .'</th>
			<th colspan="2">Fr '. date('d.n',$config['date'][5]) .'</th>
		</tr>';
	
	$total = 0;	
	foreach ($result as $row) {
		$out .= '<tr>';
		$out .= '<td title="'.$row->name.'">'.schuetziwoche_kuerzen($row->name,11).'</td>';
		$out .= '<td title="'.$row->abteilung.'">'.($row->abteilung?schuetziwoche_kuerzen($row->abteilung,18):'&nbsp;').'</td>';
		$out .= ($row->mo_eat?'<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->mo_sleep?'<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->di_eat?'<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->di_sleep?'<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->mi_eat?'<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->mi_sleep?'<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->do_eat?'<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->do_sleep?'<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->fr_eat?'<td><img src="'.$config['imgurl'].'eat.gif" title="Nachtessen"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= ($row->fr_sleep?'<td><img src="'.$config['imgurl'].'sleep.gif" title="&Uuml;bernachtung & Zmorge"></td>':'<td class="uebersicht_tag_na">&nbsp;</td>');
		$out .= '</tr>';

		$total++;
	}
	
	$row = $wpdb->get_row("SELECT SUM(isvegi) as isvegi, SUM(mo_eat) as mo_eat, SUM(mo_sleep) as mo_sleep ,SUM(di_eat) as di_eat, SUM(di_sleep) as di_sleep ,SUM(mi_eat) as mi_eat, SUM(mi_sleep) as mi_sleep ,SUM(do_eat) as do_eat, SUM(do_sleep) as do_sleep ,SUM(fr_eat) as fr_eat, SUM(fr_sleep) as fr_sleep  FROM ".$config['table']."");

	$out .= '<tr>';
	$out .= '<td>'.$total.'</td><td>&nbsp;</td>';
	$out .= '<td class="uebersicht_tot">'.$row->mo_eat.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->mo_sleep.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->di_eat.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->di_sleep.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->mi_eat.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->mi_sleep.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->do_eat.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->do_sleep.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->fr_eat.'</td>';
	$out .= '<td class="uebersicht_tot">'.$row->fr_sleep.'</td>';
	$out .= '</tr></table>';

	return $out;
}

function schuetziwoche_kuerzen($str, $len){
	if (strlen($str)>$len){
		return substr($str, 0, $len-2).'...';
	}else{
		return $str;
	}
}

function schuetziwoche_install() {

	global $wpdb;
	//global $jal_db_version;
	
	$config = schuetziwoche_get_config();
	$table_name = $config['table'];

	$sql = "CREATE TABLE $table_name (
		id int(11) NOT NULL auto_increment,
		date varchar(255) collate latin1_general_ci default NULL,
		name varchar(255) collate latin1_general_ci default NULL,
		email varchar(255) collate latin1_general_ci default NULL,
		hash varchar(255) collate latin1_general_ci default NULL,
		abteilung varchar(255) collate latin1_general_ci default NULL,
		isvegi smallint(1) default '0',
		mo_eat smallint(1) default '0',
		mo_sleep smallint(1) default '0',
		di_eat smallint(1) default '0',
		di_sleep smallint(1) default '0',
		mi_eat smallint(1) default '0',
		mi_sleep smallint(1) default '0',
		do_eat smallint(1) default '0',
		do_sleep smallint(1) default '0',
		fr_eat smallint(1) default '0',
		fr_sleep smallint(1) default '0',
		hastopay tinyint(1) default '1',
		PRIMARY KEY  (id)
	) ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci AUTO_INCREMENT=110 ;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	//add_option( "jal_db_version", $jal_db_version );

	update_option(SCHUETZIWOCHE_OPTIONS, array(
		'start_date' => '1378684800',
		'limit_eat' => 10,
		'limit_sleep' => 24,
		'email_sender_address' => 'info@example.com',
		'email_notification_address' => 'info@example.com',
		'color_bg' => '#EEE',
		'color_border' => '#AAA',
		'color_text' => '#333',
		'abteilungen' => 'Schütziwoche-OK;PRW;Andelfingen;Avalon;Bubenberg;Diviko;Dunant;Elgg;Eschenberg;Gallispitz;Hartmannen;Heidegg;NE/WA;Orion;PTA Atlantis;Seuzi;Waldmann;Wart;Andere',
		'abteilungen_paying' => 'Schütziwoche-OK',
		'cost_eat' => 9,
		'cost_sleep' => 4,
	));

}
register_activation_hook( __FILE__, 'schuetziwoche_install' );


?>