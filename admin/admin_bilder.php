<?php
error_reporting(E_ALL & ~E_NOTICE);

define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './');
define('SCRIPTNAME', 'admin_bilder.php');

define('BEREICHSNAVI', 'adminnavi.html');
// Rel. Pfad zur DB-Daten oder Config Setup Datei
define('SETUP_PFAD', './');
// Rel. Pfad zum Hauptverz. ausserhalb Admin
define('RELPFAD', '../');

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
require_once ROOT_PFAD.'includes/adminlayout.php';

// Templateparser laden
$tparse = new template();
// #########################################################
// Admin Logincheck
passdatencheck();
// #########################################################
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
case 'tldel':
    tldel();
    break;
case 'tl':
    tl();
    break;
case 'opdel':
    opdel();
    break;
case 'op':
    op();
    break;
case 'dp':
    dp();
    break;
case 'neue':
    neue();
    break;
default:
    liste();
    break;
}

/**
 * Alle Bilder auflisten.
 *
 * @return void
 */
function liste()
{
    global $scriptconf, $db;

    if (isset($_REQUEST['los']) && $_REQUEST['los'] != '') {
        $los = datensaver($_REQUEST['los'], 10, 3);
    } else {
        $los = 1;
    }

    $sektion = 'Alle Bilder auflisten';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    // #######################################################
    $per_page = 21;
    // Eintraege pro Seite
    $anz = ($los - 1) * $per_page;

    list($result, $gesamt, $alle) = $db->run_dbqueryanz(
        "SELECT SQL_CALC_FOUND_ROWS a.up_id, a.up_picname, a.up_orginalname, a.up_endung, a.up_vz, a.up_bytesize, a.up_width, a.up_height, a.up_thumb, a.up_thumbwidth, a.up_thumbheight, a.up_thumbbytesize, DATE_FORMAT(a.up_datetime, '%d.%m.%Y %T') AS uploaddate, a.up_ip, a.up_delkey,
        b.traffic_id, COUNT(b.bild_id) AS aufrufe, b.traffic_picname, b.traffic_thpicname, SUM(b.traffic_bytes + b.traffic_thbytes) AS gesamttr, b.trafic_art
        FROM ".$db->db_prefix.'uploads a
        LEFT JOIN '.$db->db_prefix."trafficlog b
        ON(a.up_id = b.bild_id)
        GROUP BY a.up_id 
        ORDER BY a.up_id DESC LIMIT $anz, $per_page", 1
    );

    //traffic_id, bild_id, traffic_picname, traffic_thpicname, traffic_bytes, traffic_thbytes, trafic_art, traffic_datetime, traffic_ip

    // Seitennavidaten generieren, falls noetig
    if ($alle > $per_page) {
        $navilinks = pager($alle, $los, $per_page, './'.SCRIPTNAME.'?go=liste&amp;los', 1, 4, 0);
    } else {
        $navilinks = '&nbsp;';
    }

    if ($gesamt > 0) {
        $stt = 0;
        $output = '';
        while ($picdata = $db->get_single_row($result)) {
            $stt++;

            $thumbnail_img = $picdata['up_thumb'] == 1 ? '<a href="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" target="_blank"><img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_thumbwidth'].'" height="'.$picdata['up_thumbheight'].'" border="0" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;"></a>' : '<img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_bwidth'].'" height="'.$picdata['up_height'].'" border="0" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;">';
            $bildinfos = '<div align="left" class="innenhd">Upload am: '.$picdata['uploaddate'].'<br>Aufrufe: '.$picdata['aufrufe'].'<br>Traffic: '.dateigroesse($picdata['gesamttr']).'<br>Abmessungen: '.$picdata['up_width'].' x '.$picdata['up_height'].'<br>Dateigr&ouml;&szlig;e: '.dateigroesse($picdata['up_bytesize']).'<br><a href="'.SCRIPTNAME.'?go=dp&amp;id='.$picdata['up_id'].'&amp;los='.$los.'&amp;back=liste">Bild sofort l&ouml;schen?</a></div>';

            if ($stt == 1) {
                $output .= '<tr><td class="innenh" width="33%" align="center">'.$thumbnail_img.$bildinfos.'</td>';
            } elseif ($stt == 2) {
                $output .= '<td class="innenh" width="34%" align="center">'.$thumbnail_img.$bildinfos.'</td>';
            } elseif ($stt == 3) {
                $output .= '<td class="innenh" width="33%" align="center">'.$thumbnail_img.$bildinfos.'</td></tr>';
                $stt = 0;
            }
        }

        //####################
        if ($stt == 1) {
            $output .= '<td class="innenh">&nbsp;</td><td class="innenh">&nbsp;</td></tr>';
        }

        if ($stt == 2) {
            $output .= '<td class="innenh">&nbsp;</td></tr>';
        }

        //####################
    } else {
        $output = '<tr><td class="innenh" colspan="3" align="center"><span class="tippred">Es sind noch keine Eintr&auml;ge vorhanden</span></td></tr>';
    } ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(3); ?>
    <tr>
        <td class="innenhd" colspan="3"><?php echo $alle; ?> Bilder sind derzeit in der Datenbank gespeichert</td>
    </tr>
    <tr>
        <td class="innenh" colspan="3" align="right"><?php echo $navilinks; ?></td>
    </tr>
    <?php echo $output; ?>
    <tr>
        <td class="innenh" colspan="3" align="right"><?php echo $navilinks; ?></td>
    </tr>
    <?php echo trl(3); ?>
    </table>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Neue Bilder der letzten 24 Stunden auflisten.
 *
 * @return void
 */
function neue()
{
    global $scriptconf, $db;

    if (isset($_REQUEST['los']) && $_REQUEST['los'] != '') {
        $los = datensaver($_REQUEST['los'], 10, 3);
    } else {
        $los = 1;
    }

    $sektion = 'Neue Bilder der letzten 24 Stunden auflisten';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    // #######################################################
    $per_page = 21;
    // Eintraege pro Seite
    $anz = ($los - 1) * $per_page;

    list($result, $gesamt, $alle) = $db->run_dbqueryanz(
        "SELECT SQL_CALC_FOUND_ROWS a.up_id, a.up_picname, a.up_orginalname, a.up_endung, a.up_vz, a.up_bytesize, a.up_width, a.up_height, a.up_thumb, a.up_thumbwidth, a.up_thumbheight, a.up_thumbbytesize, DATE_FORMAT(a.up_datetime, '%d.%m.%Y %T') AS uploaddate, a.up_ip, a.up_delkey,
        b.traffic_id, COUNT(b.bild_id) AS aufrufe, b.traffic_picname, b.traffic_thpicname, SUM(b.traffic_bytes + b.traffic_thbytes) AS gesamttr, b.trafic_art
        FROM ".$db->db_prefix.'uploads a
        LEFT JOIN '.$db->db_prefix."trafficlog b
        ON(a.up_id = b.bild_id)
        WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(a.up_datetime) < 86400  
        GROUP BY a.up_id 
        ORDER BY a.up_id DESC LIMIT $anz, $per_page", 1
    );

    //traffic_id, bild_id, traffic_picname, traffic_thpicname, traffic_bytes, traffic_thbytes, trafic_art, traffic_datetime, traffic_ip

    // Seitennavidaten generieren, falls noetig
    if ($alle > $per_page) {
        $navilinks = pager($alle, $los, $per_page, './'.SCRIPTNAME.'?go=liste&amp;los', 1, 4, 0);
    } else {
        $navilinks = '&nbsp;';
    }

    if ($gesamt > 0) {
        $stt = 0;
        $output = '';
        while ($picdata = $db->get_single_row($result)) {
            $stt++;

            $thumbnail_img = $picdata['up_thumb'] == 1 ? '<a href="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" target="_blank"><img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_thumbwidth'].'" height="'.$picdata['up_thumbheight'].'" border="0" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;"></a>' : '<img src="'.$scriptconf['HTMLURL'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'].'" alt="Bild" width="'.$picdata['up_bwidth'].'" height="'.$picdata['up_height'].'" border="0" title="Vorschaubild von: '.$picdata['up_orginalname'].'" style="padding: 5px;">';
            $bildinfos = '<div align="left" class="innenhd">Upload am: '.$picdata['uploaddate'].'<br>Aufrufe: '.$picdata['aufrufe'].'<br>Traffic: '.dateigroesse($picdata['gesamttr']).'<br>Abmessungen: '.$picdata['up_width'].' x '.$picdata['up_height'].'<br>Dateigr&ouml;&szlig;e: '.dateigroesse($picdata['up_bytesize']).'<br><a href="'.SCRIPTNAME.'?go=dp&amp;id='.$picdata['up_id'].'&amp;los='.$los.'&amp;back=neue">Bild sofort l&ouml;schen?</a></div>';

            if ($stt == 1) {
                $output .= '<tr><td class="innenh" width="33%" align="center">'.$thumbnail_img.$bildinfos.'</td>';
            } elseif ($stt == 2) {
                $output .= '<td class="innenh" width="34%" align="center">'.$thumbnail_img.$bildinfos.'</td>';
            } elseif ($stt == 3) {
                $output .= '<td class="innenh" width="33%" align="center">'.$thumbnail_img.$bildinfos.'</td></tr>';
                $stt = 0;
            }
        }

        //####################
        if ($stt == 1) {
            $output .= '<td class="innenh">&nbsp;</td><td class="innenh">&nbsp;</td></tr>';
        }

        if ($stt == 2) {
            $output .= '<td class="innenh">&nbsp;</td></tr>';
        }

        //####################
    } else {
        $output = '<tr><td class="innenh" colspan="3" align="center"><span class="tippred">Es sind noch keine Eintr&auml;ge vorhanden</span></td></tr>';
    } ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(3); ?>
    <tr>
        <td class="innenhd" colspan="3"><?php echo $alle; ?> Bilder die in den letzten 24 Stunden hochgeladen wurden sind derzeit in der Datenbank gespeichert</td>
    </tr>
    <tr>
        <td class="innenh" colspan="3" align="right"><?php echo $navilinks; ?></td>
    </tr>
    <?php echo $output; ?>
    <tr>
        <td class="innenh" colspan="3" align="right"><?php echo $navilinks; ?></td>
    </tr>
    <?php echo trl(3); ?>
    </table>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Bild loeschen ausfuehren.
 *
 * @return void
 */
function dp()
{
    global $scriptconf, $db;

    $id = isset($_GET['id']) && $_GET['id'] != '' ? datensaver($_GET['id'], 10, 3) : 0;
    $los = isset($_GET['los']) && $_GET['los'] != '' ? datensaver($_GET['los'], 10, 3) : 1;
    $back = isset($_GET['back']) && $_GET['back'] != '' ? datensaver($_GET['back'], 10, 6) : 'liste';

    $picdata = $db->dbquery_first('SELECT up_id, up_picname, up_orginalname, up_endung, up_vz, up_bytesize, up_thumb, up_thumbbytesize, up_delkey FROM '.$db->db_prefix."uploads WHERE up_id = $id");
    $gefunden = $picdata['up_id'] == '' ? 0 : 1;

    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    $errormeldung = '<li>Es wurde kein Bild in der Datenbank gefunden!</li>'."\n";
    if (!$gefunden) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabe('Fehler', '<ul>'.$fehlermeldung.'</ul>', 1, BEREICHSNAVI);
        exit;
    }

    if (file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
        @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung']);
    }
    if ($picdata['up_thumb'] == 1 && file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
        @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung']);
    }

    $gesamtbytes = $picdata['up_bytesize'] + $picdata['up_thumbbytesize'];

    $db->run_delete_query('DELETE FROM', 'uploads', 'WHERE up_id = '.$picdata['up_id'].'');
    $db->run_update_query('UPDATE', 'gesamtstat', "picanz = picanz - 1, picbytes = picbytes - $gesamtbytes");

    redirect($scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go='.$back.'&amp;los='.$los, 1, 'Bild gel&ouml;scht...');
    exit;
}

/**
 * Alte Bilder loeschen.
 *
 * @return void
 */
function op()
{
    global $scriptconf, $db;

    $anz = 0;
    if ($scriptconf['PICDELETE'] > 0) {
        $db->run_update_query('UPDATE', 'uploads', 'del_ok = 1 WHERE TO_DAYS(CURDATE()) - TO_DAYS(up_datetime) > '.$scriptconf['PICDELETE'].'');
    }

    $gesamt = $db->dbquery_first('SELECT COUNT(*) AS dpanz FROM '.$db->db_prefix.'uploads WHERE del_ok = 1');
    $anz = $gesamt['dpanz'];

    $sektion = 'Alte Bilder loeschen';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    $ez_mz = $anz > 1 ? 'Es wurden <span class="tippred">'.$anz.' Bilder</span> gefunden die &auml;lter als '.$scriptconf['PICDELETE'].' Tage sind.' : 'Es wurde <span class="tippred">'.$anz.' Bild</span> gefunden welches &auml;lter als '.$scriptconf['PICDELETE'].' Tage ist.';
    $button = $anz > 0 ? '<input class="los" type="Submit" value="Alle gefundenen Bilder l&ouml;schen">' : 'Keine Bilder zum l&ouml;schen  vorhanden';
    $info = $anz > 0 ? $ez_mz.' Klicken Sie auf den Button unterhalb um diese Bilder zu l&ouml;schen.<br><br>Wenn viele Bilder gefunden wurden, wird diese Aktion automatisch in mehreren Schritten nacheinander ausgef&uuml;hrt' : 'Es wurden keine Bilder gefunden bei denen der L&ouml;schdatum erreicht ist.';

    echo '<form action="'.SCRIPTNAME.'" method="GET">'."\n";
    echo '<input type="hidden" name="go" value="opdel">'."\n";
    echo '<input type="hidden" name="ba" value="'.$anz.'">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(2); ?>
    <tr>
        <td class="innend"><b>Bilder l&ouml;schen die &auml;lter als <?php echo $scriptconf['PICDELETE']; ?> Tage sind</b></td>
    </tr>
    <tr>
        <td class="innenh"><?php echo $info; ?></td>
    </tr>
    <tr>
        <td class="innend" align="center"><?php echo $button; ?></td>
    </tr>
    <?php echo trl(2); ?>
    </table>
    </form>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Alte Bilder endgueltig loeschen.
 *
 * @return void
 */
function opdel()
{
    global $scriptconf, $db;

    $wert = isset($_GET['wert']) && $_GET['wert'] != '' ? datensaver($_GET['wert'], 10, 3) : 0;
    $ba = isset($_GET['ba']) && $_GET['ba'] != '' ? datensaver($_GET['ba'], 10, 3) : 0;

    $intervall = 3;

    // ##############################################################

    $gesamtbytes = 0;
    $finsishstatus = '';
    list($result, $gesamt, $alle) = $db->run_dbqueryanz('SELECT up_id, up_picname, up_endung, up_vz, up_bytesize, up_thumb, up_thumbbytesize, del_ok FROM '.$db->db_prefix."uploads WHERE del_ok = 1 LIMIT $intervall", 0);
    if ($gesamt > 0) {
        while ($picdata = $db->get_single_row($result)) {
            $gesamtbytes += ($picdata['up_bytesize'] + $picdata['up_thumbbytesize']);

            $db->run_delete_query('DELETE FROM', 'uploads', 'WHERE up_id = '.$picdata['up_id'].' AND del_ok = 1');

            if (file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
                @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/'.$picdata['up_picname'].'.'.$picdata['up_endung']);
            }
            if ($picdata['up_thumb'] == 1 && file_exists($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung'])) {
                @unlink($scriptconf['HTMLPFAD'].'/img/'.$picdata['up_vz'].'/TH_'.$picdata['up_picname'].'.'.$picdata['up_endung']);
            }

            $finsishstatus .= '<li><b>Verarbeite Daten von Bild ID:</b> '.$picdata['up_id'].'</li>'."\n";
            $wert++;
        }

        $db->run_update_query('UPDATE', 'gesamtstat', "picanz = picanz - $gesamt, picbytes = picbytes - $gesamtbytes");
    }

    // ##############################################################
    // Weiterleitungs URL Daten zusammenstellen
    $restanzahl = $ba - $wert;
    if ($restanzahl > 0) {
        $metaurl = '<meta http-equiv="refresh" content="3; URL='.$scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?wert='.$wert.'&amp;go=opdel&amp;ba='.$ba.'">';
    } else {
        $restanzahl = 0;
        $metaurl = '<meta http-equiv="refresh" content="3; URL='.$scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go=liste">';
    }

    $topstatus = 'Alte Bilder werden gel&ouml;scht, noch '.$restanzahl.' von '.$ba.' zu erledigen';
    // ##############################################################

    $sektion = 'Alte Bilder werden gel&ouml;scht';
    echo globaler_header($sektion, $metaurl, '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI); ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(); ?>
    <tr>
        <td class="innend"><b><?php echo $topstatus; ?></b> Wert: <?php echo $wert; ?></td>
    </tr>
    <tr>
        <td class="innenh"><ul><?php echo $finsishstatus; ?></ul></td>
    </tr>
    <?php echo trl(); ?>
    </table>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Alte Trafficlogs loeschen - Auswahl nach Monat/Jahr.
 *
 * @return void
 */
function tl()
{
    global $scriptconf, $db;

    $sektion = 'Alte Trafficlogs l&ouml;schen - Auswahl nach Monat/Jahr';
    echo globaler_header($sektion, $metaurl, '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    $trennlinie4 = trl(4);

    list($result, $gesamtlog, $alle) = $db->run_dbqueryanz("SELECT MONTH(traffic_datetime) AS monat, YEAR(traffic_datetime) AS jahr, SUM(traffic_bytes + traffic_thbytes) AS traffic, COUNT(*) AS eintraege, DATE_FORMAT(traffic_datetime, '%m-%Y')AS deldate FROM ".$db->db_prefix.'trafficlog GROUP BY MONTH(traffic_datetime), YEAR(traffic_datetime) ORDER BY YEAR(traffic_datetime) DESC, MONTH(traffic_datetime) DESC');

    if ($gesamtlog > 0) {
        $start = 0;
        $cssclass = 'innenhd';

        while ($spalte = mysql_fetch_array($result)) {
            $cssclass = $cssclass == 'innenh' ? 'innenhd' : 'innenh';

            $delcheckbox = $start > 1 ? '<input type="checkbox" name="logmoja[]" value="'.$spalte['deldate'].'">' : '<img src="../misc/pixel.gif" alt="" width="10" height="20" border="0">';
            $amo = $spalte['monat'] < 10 ? '0'.$spalte['monat'] : $spalte['monat'];

            $logoutput .= '<tr>
            <td class="'.$cssclass.'" style="white-space: nowrap;">'.$amo.'.'.$spalte['jahr'].'</td>
            <td class="'.$cssclass.'" style="white-space: nowrap;">'.dateigroesse($spalte['traffic']).'</td>
            <td class="'.$cssclass.'" style="white-space: nowrap;">'.$spalte['eintraege'].'</td>
            <td class="'.$cssclass.'" style="white-space: nowrap;" align="center">'.$delcheckbox.'</td></tr>';
            $start++;
        }
    } else {
        $logoutput = '<tr><td class="innenh" colspan="4"><span class="tippred">Es sind keine Logdaten vorhanden</span></td></tr>';
    }

    if ($start > 1) {
        $submitrow = '<tr><td class="innend" colspan="4" align="center"><input type="submit" class="los" value="Markierte Trafficlogs l&ouml;schen?" onClick="return confirm(\'Aktion wirklich ausf�hren? Wenn Sie jetzt auf OK klicken werden alle ausgew�hlten Trafficlogs gel�scht!\')"></td></tr>';
    }
    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="tldel">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo $trennlinie4; ?>
    <tr>
        <td class="innenhd" colspan="4">Aktuelle Trafficlogs</td>
    </tr>
    <tr>
        <td class="innenh" colspan="4"><b>Hinweis!</b> L&ouml;schen von Logs ist erst m&ouml;glich wenn diese &auml;lter als 2 Monate sind</td>
    </tr>
    <tr>
        <td class="innend" style="white-space: nowrap;"><b>Monat/Jahr</b></td>
        <td class="innend" style="white-space: nowrap;" width="40%"><b>Traffic</b></td>
        <td class="innend" style="white-space: nowrap;" width="40%"><b>Anzahl Logeintr&auml;ge</b></td>
        <td class="innend" style="white-space: nowrap;" align="center"><b>L&ouml;schen</b></td>
    </tr>
    <?php echo $logoutput; ?>
    <?php echo $submitrow; ?>
    <?php echo $trennlinie4; ?>
    </table>
    </form>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Alte Counter Logs loeschen.
 *
 * @return void
 */
function tldel()
{
    global $scriptconf, $db;

    $start = 0;
    foreach ($_POST['logmoja'] as $kt) {
        $logmodel = datensaver($_POST['logmoja'][$start], 8, 4);
        $delmoja = explode('-', $logmodel);
        $start++;

        if ($delmoja[0] != '' && $delmoja[1] != '') {
            $db->run_delete_query('DELETE FROM', 'trafficlog', "WHERE MONTH(traffic_datetime) = '$delmoja[0]' AND YEAR(traffic_datetime) = '$delmoja[1]'");
        }
    }

    redirect($scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go=tl', 1, 'Trafficlogs gel&ouml;scht...');
    exit;
}
