<?php

/*
 * Imagehosting-Script by inspire-World.de
 * Rework by Rustypredator <contact@rusty.info>
 *
 * Original Project: http://inspire-world.de/phpscripte13.php
 * Reworked Project: https://github.com/Rustypredator/imagehosting
 */

error_reporting(E_ALL & ~E_NOTICE);

define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './admin/');
define('SCRIPTNAME', 'bilder.php');

// Rel. Pfad zur DB-Daten oder Config Setup Datei
define('SETUP_PFAD', './admin/');

// #########################################################
// Datenbank Class einbinden
require_once ROOT_PFAD.'includes/class_dbhandler_mysql.php';
// $db definieren
$db = new dbhandler_mysql();
// DB Verbindung aufbauen
$db->db_connect();
// Scriptconfig Daten holen

require_once ROOT_PFAD.'setup/setup.php';

// #########################################################
require_once ROOT_PFAD.'includes/globale_funct_inc.php';

// Templateparser laden
$tparse = new template();

require_once ROOT_PFAD.'includes/userlayout.php';

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Aufgerufene Bilder anzeigen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

$sektion = 'Bildausgabe';
echo globaler_header($sektion, '', '', '');
echo globallayoutoben($sektion);

$picname = isset($_GET['id']) && $_GET['id'] != '' ? datensaver($_GET['id'], 35, 6) : '';

$picdata = $db->dbquery_first("SELECT up_id, up_picname, up_orginalname, up_endung, up_vz, up_bytesize, up_width, up_height, DATE_FORMAT(up_datetime, '%d.%m.%Y') AS uploaddate  FROM ".$db->db_prefix."uploads WHERE up_picname = '$picname'");
$gefunden = $picdata['up_id'] == '' ? 0 : 1;

// wenn nicht gefunden
if (!$gefunden) {
    $contentarray1 = [
        'TEXTTOP'  => '<b>Fehler, kein Bild gefunden</b>',
        'TEXTCONT' => 'Es wurde kein Bild in der Datenbank gefunden!',
    ];
    $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray1);
} else {
    // Bild gefunden, Infos fuer User ausgeben
    // Statistik auslesen, Daten zusammenstellen

    $trstat1 = $db->dbquery_first('SELECT SUM(traffic_bytes + traffic_thbytes) AS gesamttraffic FROM '.$db->db_prefix."trafficlog WHERE bild_id = '".$picdata['up_id']."' GROUP BY bild_id");
    $trstat2 = $db->dbquery_first('SELECT COUNT(bild_id) AS anz FROM '.$db->db_prefix."trafficlog WHERE bild_id = '".$picdata['up_id']."' AND trafic_art = 1");

    $imgurl = 'img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'];
    $imgstat = 'old';

    $contentarray2 = [
        'BILDHTML'            => '<img src="'.$imgurl.'" alt="Bild" width="'.$picdata['up_width'].'" height="'.$picdata['up_height'].'" border="0" align="left" title="Orginalbildname von: '.$picdata['up_orginalname'].'" style="padding: 5px;">',
        'BILDURL'             => $imgurl,
        'BILDSTAT_UPLOADNAME' => $picdata['up_orginalname'],
        'BILDSTAT_UPLOAD'     => $picdata['uploaddate'],
        'BILDSTAT_VISITS'     => ($trstat2['anz'] + 1),
        'BILDSTAT_TRAFFIC'    => dateigroesse($trstat1['gesamttraffic'] + $picdata['up_bytesize']),
        'BILDSTAT_SIZE'       => dateigroesse($picdata['up_bytesize']),
        'BILDSTAT'            => $imgstat,
    ];

    $tparse->get_tpldata(WEB_PFAD.'templates/bildausgabe.html');
    echo $tparse->templateparser($contentarray2);

    // Trafficlog grosse Bilder
    $trafic_art = 1;
    $traffic_picname = $picdata['up_picname'].'.'.$picdata['up_endung'];
    $traffic_bytes = $picdata['up_bytesize'];
    $traffic_thpicname = '';
    $traffic_thbytes = 0;
    $user_ip = get_uip();

    $db->run_insert_query('INSERT INTO', 'trafficlog', "(traffic_id, bild_id, traffic_picname, traffic_thpicname, traffic_bytes, traffic_thbytes, trafic_art, traffic_datetime, traffic_ip) VALUES (NULL, '".$picdata['up_id']."', '$traffic_picname', '$traffic_thpicname', '$traffic_bytes', '$traffic_thbytes', '$trafic_art', NOW(), '$user_ip')");
}

echo globaler_footer();

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
