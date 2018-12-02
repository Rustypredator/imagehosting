<?php
error_reporting(E_ALL & ~E_NOTICE);

define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './admin/');
define('SCRIPTNAME', 'index.php');

// Rel. Pfad zur DB-Daten oder Config Setup Datei
define('SETUP_PFAD', './admin/');

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
// Wartungsfunktionen anstossen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
$wartungsdaten = $db->dbquery_first('SELECT CURDATE() AS aktueller_datum, cwert FROM '.$db->db_prefix."system WHERE cname = 'WARTUNGSDATE' LIMIT 1");

if ($wartungsdaten['aktueller_datum'] != $wartungsdaten['cwert']) {
    run_wartung($wartungsdaten['aktueller_datum']);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Aktionen fuer diese Datei
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
$go = '';
if (isset($_GET['go']) && $_GET['go'] != '') {
    $go = datensaver($_GET['go'], 50, 4);
} elseif (isset($_POST['go']) && $_POST['go'] != '') {
    $go = datensaver($_POST['go'], 50, 4);
}

switch ($go) {
case 'info':
    info();
    break;
case 'picup':
    picup();
    break;
// Startseite
default:
    start();
    break;
}

/**
 * Bildupload.
 *
 * @return void
 */
function start()
{
    global $scriptconf, $db, $tparse;

    $sektion = 'Bildupload';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    $dateitypstring = und_string($scriptconf['UPLOADENDUNGEN'], ',');
    $implodetypes = implode('|', explode(',', $scriptconf['UPLOADENDUNGEN']));
    $uploadsize = dateigroesse($scriptconf['MAXPICUPLOADMB']); ?>
    <script language="JavaScript" type="text/javascript">
    var endungen = "<?php echo $implodetypes; ?>";

    function uptest() {
    TFA = 0;
    for (var i = 0; i < document.upform.length; i++){
    current = document.upform.elements[i];
    if(current.type =='file' && current.value !=''){
    if(!check_endung(current.value)) return false;
    TFA++;
    }
    }
    if(TFA == 0){
    alert('Es ist noch keine Datei zum hochladen vorhanden.');
    return false;
    } else {
    upload_info_layer();
    return sende_dateien();
    }
    }

    function check_endung(wert){
    if(wert == '') return true;
    var re = new RegExp("^.+\.("+endungen+")$","i");
    if(!re.test(wert)){

    var dateiname = wert.split('\\');
    var anz = dateiname.length - 1; 

    alert("Die Datei: \"" + dateiname[anz] + "\" ist nicht gestattet\nErlaubt sind nur diese Dateiendungen:\n <?php echo $dateitypstring; ?>");
    return false;
    }

    return true;
    }


    function upload_info_layer() {
    document.getElementById('layer_upstatus').style.visibility = 'visible'; 
    document.getElementById('layer_upstatus').style.display = 'block';
    }
    </script>
    <?php

    $statistik = $db->dbquery_first('SELECT picbytes FROM '.$db->db_prefix.'gesamtstat');

    if ($statistik['picbytes'] >= $scriptconf['MAXSPEICHERPLATZ']) {
        $contentarray = [
            'TEXTTOP'  => '<b>Speicherplatz belegt</b>',
            'TEXTCONT' => 'Derzeit sind leider keine Uploads mehr m&ouml;glich, Grund der Speicherplatz ist belegt!',
        ];

        $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
        echo $tparse->templateparser($contentarray);
    } else {
        echo '<form onSubmit="return uptest();" name="upform" action="'.SCRIPTNAME.'" method="POST" enctype="multipart/form-data">'."\n";
        echo '<input type="hidden" name="go" value="picup">'."\n"; ?>
        <!--<div id="layer_upstatus">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center" valign="middle"><br><br>
                <img src="misc/loader.gif" alt="Bild wird hochgeladen." width="128" height="15" border="0"><br><br><br>
                <img src="misc/loadertext.gif" alt="Bild wird hochgeladen." width="313" height="46" border="0"><br><br>
            </td>
        </tr>
        </table>
        </div>-->
        <?php

        $uploadinfo = 'Bitte beachten Sie:<br>
        <ol>
        <li>Max. Dateigr&ouml;&szlig;e: '.$uploadsize.'</li>
        <li>Erlaubte Dateiformate: '.$dateitypstring.'</li>
        <li>Laden Sie nur Bilder hoch die Ihnen geh&ouml;ren</li>
        <li>Durch Ihr Bild d&uuml;rfen die Privatsph&auml;re und Rechte anderer nicht verletzt werden.</li>
        <li>Keine pornografischen, Bilder, bzw. Bilder die anderweitig gegen Deutsche Gesetze versto&szlig;en.</li>
        <li>Alle Regeln finden Sie unter <a href="./service.php?go=agb">AGB/Regeln</a></li>
        </ol>
        ';

        $contentarray = [
        'UPLOADINFO'     => $uploadinfo,
        ];

        $tparse->get_tpldata(WEB_PFAD.'templates/uploadform.html');
        echo $tparse->templateparser($contentarray);
    }

    echo globaler_footer();
}

/**
 * Upload ausfuehren.
 *
 * @return void
 */
function picup()
{
    global $scriptconf, $db, $tparse;

    $user_ip = get_uip();

    // max. Uploadgroesse
    $max_upl_size = $scriptconf['MAXPICUPLOADMB'];

    // Erlaubte Dateiendungen, Dateiendungen mit Komma trennen und klein schreiben
    $arr_erlaube_dateityp = explode(',', $scriptconf['UPLOADENDUNGEN']);

    // Zufallsid erzeugen fuer Fehlerauswertung
    $zkey = md5(uniqid('', true));

    $fehleranzahl = 0;

    // Uploaddurchlauf Start
    for ($i = 0; $i < count($_FILES['UPFILE']['name']); $i++) {
        if ($_FILES['UPFILE']['name'][$i] == '') {
            continue;
        }

        $File = preg_replace("/[^0-9a-zA-Z\.]/", '_', $_FILES['UPFILE']['name'][$i]);

        // Dateiendung ermitteln
        $punkt = strrpos($File, '.');
        $laenge = strlen($File);
        $endung = strtolower(substr($File, -($laenge - $punkt - 1)));
        $orginal = substr($File, 0, -($laenge - $punkt));
        $orginal = datensaver($orginal, 100, 1);

        // logid, shopid, errorid, meldung, errordate, dateiname
        // Fehlerausgabe wenn Dateiendung nicht erlaubt
        if (!in_array($endung, $arr_erlaube_dateityp)) {
            $fehler_dateityp = 'Die Dateiendung <b>'.$endung.'</b> ist nicht erlaubt';
            $fehleranzahl++;
            // Daten speichern
            $db->run_insert_query('INSERT INTO', 'uploadfehlerlog', "(logid, errorid ,meldung, errordate, dateiname) VALUES(NULL, '".$zkey."', '".$fehler_dateityp."',  NOW(), '".$orginal.'.'.$endung."')");
            continue;
        }

        // Dateigroessencheck
        if ($_FILES['UPFILE']['size'][$i] > $max_upl_size) {
            $zugross = round($_FILES['UPFILE']['size'][$i] / 1024, 2);
            $fehler_dateigroesse = 'Datei zu gro&szlig; <b>'.$zugross.' KB</b>';
            $fehleranzahl++;
            // Daten speichern
            $db->run_insert_query('INSERT INTO', 'uploadfehlerlog', "(logid, errorid ,meldung, errordate, dateiname) VALUES(NULL, '".$zkey."', '".$fehler_dateigroesse."', NOW(), '".$orginal.'.'.$endung."')");

            continue;
        }

        // Array mit Bildverzeichnissen
        $ziel_vz = [
        'gif'      => 'gif',
        'jpg'      => 'jpg',
        'jpeg'     => 'jpg',
        'png'      => 'png',
        ];

        // Zufaelligen Dateiname aus Orginalbildname und Zeit basteln
        $savename = md5(uniqid('', true));

        // Datei in Temp. Verzeichnis abspeichern
        $uploadverzeichnis = $scriptconf['HTMLPFAD'].'/uploads';

        if (@move_uploaded_file($_FILES['UPFILE']['tmp_name'][$i], $uploadverzeichnis.'/'.$savename.'.'.$endung)) {
            clearstatcache();
            @chmod($uploadverzeichnis.'/'.$savename.'.'.$endung, 0777);

            $info = getimagesize($uploadverzeichnis.'/'.$savename.'.'.$endung);

            if ($info[0] != '') {
                // ################################################################
                // Infos fuer Tagesstatistik
                $statistik = $db->dbquery_first('SELECT CURDATE() AS heute, IF(CURDATE() = aktuelldate, 1, 0) AS statup FROM '.$db->db_prefix.'gesamtstat');
                // ################################################################

                // Orginal in Zielverzeichnis kopieren
                copy($uploadverzeichnis.'/'.$savename.'.'.$endung, $scriptconf['HTMLPFAD'].'/img/'.$ziel_vz[$endung].'/'.$savename.'.'.$endung) || print_script_error(__LINE__, __FILE__, 'Fehler: Kann Bild nicht nach ../img/'.$ziel_vz[$endung].'/'.$savename.'.'.$endung.' kopieren!', '');
                @chmod($scriptconf['HTMLPFAD'].'/img/'.$ziel_vz[$endung].'/'.$savename.'.'.$endung, 0777);
                $filesize_bigpic = filesize($scriptconf['HTMLPFAD'].'/img/'.$ziel_vz[$endung].'/'.$savename.'.'.$endung);

                if ($info[0] > $scriptconf['THUMBWIDTH'] or $info[1] > $scriptconf['THUMBHEIGHT']) {
                    list($newbreite, $newhoehe, $filesize_thumb) = picconverter($uploadverzeichnis, $scriptconf['HTMLPFAD'].'/img/'.$ziel_vz[$endung], $savename, $savename, $endung, $scriptconf['THUMBWIDTH'], $scriptconf['THUMBHEIGHT'], $info[0], $info[1]);
                    $up_thumb = 1;
                    $up_thumbwidth = $newbreite;
                    $up_thumbheight = $newhoehe;
                    $up_thumbbytesize = $filesize_thumb;
                } else {
                    $up_thumb = 0;
                    $up_thumbwidth = 0;
                    $up_thumbheight = 0;
                    $up_thumbbytesize = 0;
                }

                $delkey = md5(uniqid(rand()));

                // Bilddaten speichern
                $thumbpicname = 'TH_'.$savename;
                $db->run_insert_query('INSERT INTO', 'uploads', "(up_id, up_picname, up_orginalname, up_endung, up_vz, up_bytesize, up_width, up_height, up_thumb, up_thumbwidth, up_thumbheight, up_thumbbytesize, up_datetime, up_ip, up_userid, up_delkey, del_ok) VALUES (NULL , '$savename', '$orginal', '$endung', '$ziel_vz[$endung]', '$filesize_bigpic', '$info[0]', '$info[1]', '$up_thumb', '$up_thumbwidth', '$up_thumbheight', '$up_thumbbytesize', NOW(), '$user_ip', '0', '$delkey', 0)");

                // ################################################################
                $gesamtbytes = $filesize_bigpic + $up_thumbbytesize;
                if ($statistik['statup'] == 1) {
                    $db->run_update_query('UPDATE', 'gesamtstat', "picanz = picanz + 1, picbytes = picbytes + $gesamtbytes, heuteanzahl = heuteanzahl +1, allpics = allpics + 1");
                } else {
                    $db->run_update_query('UPDATE', 'gesamtstat', "picanz = picanz + 1, picbytes = picbytes + $gesamtbytes, aktuelldate = CURDATE(), heuteanzahl = 1, allpics = allpics + 1");
                }
                // ################################################################
            } // if info[0]
        }
    }

    $fehlerquery = $fehleranzahl > 0 ? '&amp;uperror='.$zkey : '';
    redirect($scriptconf['HTMLURL'].'/'.SCRIPTNAME.'?go=info&amp;pic='.$delkey.$fehlerquery, 1, 'Bildupload beendet');
    exit;
}

/**
 * Info nach Bildupload.
 *
 * @return void
 */
function info()
{
    global $scriptconf, $db, $tparse;

    $picname = isset($_GET['pic']) && $_GET['pic'] != '' ? datensaver($_GET['pic'], 35, 6) : '';
    $uperror = isset($_GET['uperror']) && $_GET['uperror'] != '' ? datensaver($_GET['uperror'], 35, 6) : '';

    $sektion = 'Informationen zum Bildupload';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion);

    // ################################################################

    // Infos auslesen wenn Fehler bei upload
    $gesamterr = 0;
    if ($uperror != '') {
        list($resulterr, $gesamterr, $alleerr) = $db->run_dbqueryanz('SELECT logid, errorid ,meldung, dateiname FROM '.$db->db_prefix."uploadfehlerlog WHERE errorid = '$uperror'", 0);
    }

    if ($gesamterr > 0) {
        $gesamterr = 1;

        $user_error_info = '';
        while ($errinfo = $db->get_single_row($resulterr)) {
            $user_error_info .= '<li>'.$errinfo['meldung'].'</li>';
        }

        $contentarray1 = [
            'TEXTTOP'      => '<b>Fehler beim Bildupload</b>',
            'TEXTCONT'     => '<ol>'.$user_error_info.'</ol>',
        ];

        $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
        echo $tparse->templateparser($contentarray1);

        echo globaler_footer();
        exit;
    } else {

        // ################################################################
        // Infos auslesen wenn upload OK

        $picdata = $db->dbquery_first('SELECT up_id, up_picname, up_orginalname, up_endung, up_vz, up_bytesize, up_width, up_height, up_thumb, up_thumbwidth, up_thumbheight, up_thumbbytesize, up_delkey FROM '.$db->db_prefix."uploads WHERE up_delkey = '$picname'");
        $gefunden = $picdata['up_id'] == '' ? 0 : 1;

        // wenn nicht gefunden
        if (!$gefunden) {
            $contentarray2 = [
                'TEXTTOP'      => '<b>Fehler, kein Bild gefunden</b>',
                'TEXTCONT'     => 'Es wurde kein Bild in der Datenbank gefunden!',
            ];

            $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
            echo $tparse->templateparser($contentarray2);
        } else {
            // Bild gefunden, Infos fuer User ausgeben

            $thumbnail_img = $picdata['up_thumb'] == 1 ? '<img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_thumbwidth'].'" height="'.$picdata['up_thumbheight'].'" border="0" align="left" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;">' : '<img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_bwidth'].'" height="'.$picdata['up_height'].'" border="0" align="left" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;">';
            $thumbnail_imgurl = $picdata['up_thumb'] == 1 ? $scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'] : $scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'];

            $bildlink_direkt = $scriptconf['HTMLURL'].'/bilder.php?id='.$picdata['up_picname'];
            $bildlink_foren = '[URL='.$scriptconf['HTMLURL'].'/bilder.php?id='.$picdata['up_picname'].'][IMG]'.$thumbnail_imgurl.'[/IMG][/URL]';
            $bildlink_html = $picdata['up_thumb'] == 1 ? '&lt;a href="'.$scriptconf['HTMLURL'].'/bilder.php?id='.$picdata['up_picname'].'" target="_blank"&gt;&lt;img src="'.$thumbnail_imgurl.'" width="'.$picdata['up_thumbwidth'].'" height="'.$picdata['up_thumbheight'].'" alt= "" border="1" title="Kostenlos Bilder hochladen"&gt;&lt;/a&gt;' : '&lt;a href="'.$scriptconf['HTMLURL'].'/bilder.php?id='.$picdata['up_picname'].'" target="_blank"&gt;&lt;img src="'.$thumbnail_imgurl.'" width="'.$picdata['up_width'].'" height="'.$picdata['up_height'].'" alt= "" border="1" title="Kostenlos Bilder hochladen"&gt;&lt;/a&gt;';

            $contentarray3 = [
            'BILDPREV'  => '<a href="'.$bildlink_direkt.'" target="_blank">'.$thumbnail_img.'</a>',
            'THUMBURL'  => $thumbnail_imgurl,
            'BILDURL'   => $bildlink_direkt,
            'FORENCODE' => $bildlink_foren,
            'HTMLCODE'  => $bildlink_html,
            'DELCODE'   => $picdata['up_delkey'],
            ];

            $tparse->get_tpldata(WEB_PFAD.'templates/uploadinfo.html');
            echo $tparse->templateparser($contentarray3);

            // ################################################################
            // Trafficlog Vorschaubilder
            $trafic_art = $picdata['up_thumb'] == 1 ? 2 : 1;
            $traffic_picname = '';
            $traffic_bytes = 0;
            $traffic_thpicname = $picdata['up_thumb'] == 1 ? 'TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'] : $picdata['up_picname'].'.'.$picdata['up_endung'];
            $traffic_thbytes = $picdata['up_thumb'] == 1 ? $picdata['up_thumbbytesize'] : $picdata['up_bytesize'];
            $user_ip = get_uip();

            $db->run_insert_query('INSERT INTO', 'trafficlog', "(traffic_id, bild_id, traffic_picname, traffic_thpicname, traffic_bytes, traffic_thbytes, trafic_art, traffic_datetime, traffic_ip) VALUES (NULL, '".$picdata['up_id']."', '$traffic_picname', '$traffic_thpicname', '$traffic_bytes', '$traffic_thbytes', '$trafic_art', NOW(), '$user_ip')");
            // ################################################################
        } // else $gefunden

        echo globaler_footer();
    } // else $gesamterr
}

/**
 * Bildergroesse anpassen.
 *
 * @param string $startverzeichnis TODO: Beschreibung
 * @param string $dateizielvz      TODO: Beschreibung
 * @param string $tempname         TODO: Beschreibung
 * @param string $savename         TODO: Beschreibung
 * @param string $endung           TODO: Beschreibung
 * @param int    $maxbreite        TODO: Beschreibung
 * @param int    $maxhoehe         TODO: Beschreibung
 * @param int    $width            TODO: Beschreibung
 * @param int    $height           TODO: Beschreibung
 *
 * @return void
 */
function picconverter($startverzeichnis, $dateizielvz, $tempname, $savename, $endung, $maxbreite, $maxhoehe, $width, $height)
{

    // 1. Wenn Bildbreite und Hoehe groesser als erlaubt
    if ($width - $height <= 0 && $width > $maxbreite && $height > $maxhoehe) {
        // welcher Wert ist groesser - width oder height?
        $faktor = sprintf('%.2f', $width / $height);
        $breite = $maxbreite;
        $hoehe = sprintf('%.0f', $breite / $faktor);
        if ($hoehe > $maxhoehe) {
            $faktor = sprintf('%.2f', $height / $width);
            $hoehe = $maxhoehe;
            $breite = sprintf('%.0f', $hoehe / $faktor);
        }
    } elseif ($height - $width <= 0 && $width > $maxbreite && $height > $maxhoehe) {
        $faktor = sprintf('%.2f', $height / $width);
        $hoehe = $maxhoehe;
        $breite = sprintf('%.0f', $hoehe / $faktor);
        if ($breite > $maxbreite) {
            $faktor = sprintf('%.2f', $width / $height);
            $breite = $maxbreite;
            $hoehe = sprintf('%.0f', $breite / $faktor);
        }
    } elseif ($width > $maxbreite && $height <= $maxhoehe) {
        // 2. Wenn Bildbreite groesser und Hoehe kleiner/gleich als erlaubt
        $faktor = sprintf('%.2f', $width / $height);
        $breite = $maxbreite;
        $hoehe = sprintf('%.0f', $breite / $faktor);
    } elseif ($width <= $maxbreite && $height > $maxhoehe) {
        // 3. Wenn Bildbreite kleiner/gleich und Hoehe groesser als erlaubt
        $faktor = sprintf('%.2f', $height / $width);
        $hoehe = $maxhoehe;
        $breite = sprintf('%.0f', $hoehe / $faktor);
    } elseif (($width <= $maxbreite && $height == $maxhoehe) || ($width == $maxbreite && $height <= $maxhoehe) || ($width == $maxbreite && $height == $maxhoehe) || $width < $maxbreite && $height < $maxhoehe) {
        // 4. Wenn Bildbreite kleiner/gleich und Hoehe gleich als erlaubt
        $hoehe = $height;
        $breite = $width;
    }

    // ################################################################ //
    if ($endung == 'gif') {
        // GIF
        $altesBild = imagecreatefromgif($startverzeichnis.'/'.$tempname.'.'.$endung);
        $neuesBild = imagecreate($breite, $hoehe);
        imagecopyresampled($neuesBild, $altesBild, 0, 0, 0, 0, $breite, $hoehe, $width, $height);
        imagegif($neuesBild, $dateizielvz.'/TH_'.$savename.'.'.$endung);
        imagedestroy($neuesBild);
    } elseif ($endung == 'jpg') {
        // JPG
        $altesBild = imagecreatefromjpeg($startverzeichnis.'/'.$tempname.'.'.$endung);
        $neuesBild = imagecreatetruecolor($breite, $hoehe);
        imagecopyresampled($neuesBild, $altesBild, 0, 0, 0, 0, $breite, $hoehe, $width, $height);
        imagejpeg($neuesBild, $dateizielvz.'/TH_'.$savename.'.'.$endung);
        imagedestroy($neuesBild);
    } elseif ($endung == 'jpeg') {
        // JPEG
        $altesBild = imagecreatefromjpeg($startverzeichnis.'/'.$tempname.'.'.$endung);
        $neuesBild = imagecreatetruecolor($breite, $hoehe);
        imagecopyresampled($neuesBild, $altesBild, 0, 0, 0, 0, $breite, $hoehe, $width, $height);
        imagejpeg($neuesBild, $dateizielvz.'/TH_'.$savename.'.'.$endung);
        imagedestroy($neuesBild);
    } elseif ($endung == 'png') {
        // PNG
        $altesBild = imagecreatefrompng($startverzeichnis.'/'.$tempname.'.'.$endung);
        $neuesBild = imagecreatetruecolor($breite, $hoehe);
        imagecopyresampled($neuesBild, $altesBild, 0, 0, 0, 0, $breite, $hoehe, $width, $height);
        imagepng($neuesBild, $dateizielvz.'/TH_'.$savename.'.'.$endung);
        imagedestroy($neuesBild);
    }

    $newfilesize = filesize($dateizielvz.'/TH_'.$savename.'.'.$endung);
    clearstatcache();
    @chmod($dateizielvz.'/'.$savename.'.'.$endung, 0777);
    @unlink($startverzeichnis.'/'.$tempname.'.'.$endung);

    // ################################################################ //
    return [$breite, $hoehe, $newfilesize];
}

/**
 * Wartungsfunktionen.
 *
 * @param [type] $aktuellerdatum TODO: Beschreibung
 *
 * @return void
 */
function run_wartung($aktuellerdatum)
{
    global $scriptconf, $db;
    $traffic = $db->dbquery_first('SELECT IF(SUM(traffic_bytes) > 0, SUM(traffic_bytes), 0) AS tgross, IF(SUM(traffic_thbytes) > 0, SUM(traffic_thbytes), 0) AS tklein FROM '.$db->db_prefix."trafficlog WHERE DATE_SUB(CURDATE(), INTERVAL 1 DAY) = DATE_FORMAT(traffic_datetime, '%Y-%m-%d')");
    $gesamttraffic = $traffic['tgross'] + $traffic['tklein'];
    $db->run_update_query('UPDATE', 'gesamtstat', "alltraffic = alltraffic + $gesamttraffic");
    $db->run_update_query('UPDATE', 'system', "cwert = CURDATE() WHERE cname = 'WARTUNGSDATE' AND prog_nr = 1");
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

?>

