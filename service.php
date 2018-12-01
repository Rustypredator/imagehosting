<?php
error_reporting(E_ALL & ~E_NOTICE);

define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './admin/');
define('SCRIPTNAME', 'service.php');

// Rel. Pfad zur DB-Daten oder Config Setup Datei 
define('SETUP_PFAD', './admin/');

// #########################################################
// Datenbank Class einbinden
require_once ROOT_PFAD. "includes/class_dbhandler_mysql.php";
// $db definieren
$db = new dbhandler_mysql();
// DB Verbindung aufbauen
$db->db_connect();
// Scriptconfig Daten holen

require_once ROOT_PFAD . "setup/setup.php";

// #########################################################
require_once ROOT_PFAD. "includes/globale_funct_inc.php";

// Templateparser laden
$tparse = new template();

require_once ROOT_PFAD. "includes/userlayout.php";

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Aktionen fuer diese Datei
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
$go = '';
if (isset($_GET['go']) && $_GET['go'] != "") { 
    $go = datensaver($_GET['go'], 50, 4); 
} elseif (isset($_POST['go']) && $_POST['go'] != "") { 
    $go = datensaver($_POST['go'], 50, 4);
} 

switch ($go) {
//##############################################
// Hilfe
case "help":
    help();
    break;
// Impressum
case "imp":
    imp();
    break;
// Statistik
case "st":
    st();
    break;
case "dpdel":
    dpdel();
    break;    
case "dp":
    dp();
    break;
// AGB/Regeln        
default:
    agb();
    break;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// AGB/Regeln
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function agb()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'AGB/Regeln';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    echo $tparse->get_tdata(WEB_PFAD . 'templates/agb.html');

    echo globaler_footer();
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Bild loeschen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function dp()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'Bild l&ouml;schen';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="dpdel">'."\n";

    echo $tparse->get_tdata(WEB_PFAD . 'templates/bild_loeschen_form.html');

    echo globaler_footer();
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Bild loeschen ausfuehren
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function dpdel()
{
    global $scriptconf, $db, $tparse;

    $delcode        = isset($_POST['dc']) && $_POST['dc'] != '' ?     datensaver($_POST['dc'], 35, 6) : 'leer'; 

    $picdata = $db->dbquery_first("SELECT up_id, up_picname, up_orginalname, up_endung, up_vz, up_bytesize, up_thumb, up_thumbbytesize, up_delkey FROM ".$db->db_prefix."uploads WHERE up_delkey = '$delcode'");
    $gefunden = $picdata['up_id'] == '' ? 0 : 1;

    // wenn nicht gefunden
    if (!$gefunden) {

        $sektion = 'Fehler';
        echo globaler_header($sektion, '', '', '');
        echo globallayoutoben($sektion);

        $contentarray2 = array(
        "TEXTTOP"     => '<b>Fehler, kein Bild gefunden</b>',
        "TEXTCONT"     => 'Es wurde kein Bild in der Datenbank gefunden!'
        ); 

        $tparse->get_tpldata(WEB_PFAD . 'templates/textausgaben.html');
        echo $tparse->templateparser($contentarray2);

        echo globaler_footer();
        exit;

    } else {

        if (file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
            @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung']);
        }
        if ($picdata['up_thumb'] == 1 && file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
            @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung']);
        }

        $gesamtbytes = $picdata['up_bytesize'] + $picdata['up_thumbbytesize'];

        $db->run_delete_query('DELETE FROM', 'uploads', "WHERE up_id = ".$picdata['up_id']."");
        $db->run_update_query('UPDATE', 'gesamtstat', "picanz = picanz - 1, picbytes = picbytes - $gesamtbytes");


        redirect($scriptconf['HTMLURL'].'/', 2, 'Bild wurde gel&ouml;scht');
        exit; 
    }
} 
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Impressum/Kontakt
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function imp()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'Impressum/Kontakt';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    echo $tparse->get_tdata(WEB_PFAD . 'templates/impressum.html');

    echo globaler_footer();
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Hilfe/FAQ
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function help()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'Hilfe/FAQ';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    echo $tparse->get_tdata(WEB_PFAD . 'templates/hilfe.html');

    echo globaler_footer();
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Statistik
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function st()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'Statistik';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);


    $statistik = $db->dbquery_first("SELECT picanz, picbytes, aktuelldate, heuteanzahl, alltraffic, allpics FROM ".$db->db_prefix."gesamtstat");
    $traffic = $db->dbquery_first("SELECT IF(SUM(traffic_bytes) > 0, SUM(traffic_bytes), 0) AS tgross, IF(SUM(traffic_thbytes) > 0, SUM(traffic_thbytes), 0) AS tklein FROM ".$db->db_prefix."trafficlog");

    $gesamttraffic = $traffic['tgross']+$traffic['tklein'];

    $contentarray = array(
    "ANZAKTUELL"        => $statistik['allpics'],
    "ANZGESAMT"         => $statistik['picanz'],
    "BYTESGESAMT"         => dateigroesse($statistik['picbytes']),
    "UPLOADHEUTE"         => $statistik['heuteanzahl'],
    "TRAFFICGESAMT"     => $statistik['alltraffic'] == 0 ? dateigroesse($gesamttraffic) : dateigroesse($statistik['alltraffic']),
    ); 

    $tparse->get_tpldata(WEB_PFAD . 'templates/statistik.html');
    echo $tparse->templateparser($contentarray);

    echo globaler_footer();
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

?>
