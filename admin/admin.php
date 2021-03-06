<?php
error_reporting(E_ALL & ~E_NOTICE);

define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './');
define('SCRIPTNAME', 'admin.php');
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
//$scriptconf = $db->get_systemdaten();
require_once ROOT_PFAD.'setup/setup.php';

// #########################################################
require_once ROOT_PFAD.'includes/globale_funct_inc.php';
require_once ROOT_PFAD.'includes/adminlayout.php';

// Templateparser laden
$tparse = new template();
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
case 'info':
    info();
    break;
case 'logincheck':
    logincheck();
    break;
case 'startseite':
    startseite();
    break;
case 'systemdaten':
    systemdaten();
    break;
case 'systemdatensave':
    systemdatensave();
    break;
case 'admindaten':
    admindaten();
    break;
case 'newadmindatensave':
    newadmindatensave();
    break;
case 'adminlog':
    adminlog();
    break;
case 'adminlogdel':
    adminlogdel();
    break;
case 'logout':
    logout();
    break;
default:
    login();
    break;
}

/**
 * Logindateneingabe Formular.
 *
 * @param string $loginerr TODO: Beschreibung
 *
 * @return void
 */
function login($loginerr = '')
{
    global $scriptconf, $db, $tparse;

    if (file_exists('installer.php')) {
        $sektion = 'Administrator Login';
        echo globaler_header($sektion, '', '', '');
        $contentarray2 = [
        'FEHLERTITEL'     => '&#187; INSTALLERDATEI VORHANDEN',
        'FEHLERTEXT'      => 'Bitte l&ouml;schen Sie nach der Installation des Scriptes die installer.php. Erst dann k&ouml;nnen Sie sich in den Adminbereich einloggen.',
        ];
        $tparse->get_tpldata(ROOT_PFAD.'templates/adminloginerror.html');
        echo $tparse->templateparser($contentarray2);
        echo globaler_footer(1);
        exit;
    }

    $logzeit = time();
    $gesamtlog = $db->dbquery_first('SELECT SUM(aktion) AS anz FROM '.$db->db_prefix."adminlog WHERE aktion = 1 AND ($logzeit - UNIX_TIMESTAMP(aktzeit)) < 900");
    $gesamtlogerr = $gesamtlog['anz'] == '' ? 0 : $gesamtlog['anz'];
    $versuche = 5 - $gesamtlogerr;
    $versuchetxt = $versuche > 1 ? ' Versuche' : ' Versuch';
    $loginsperrtext = $gesamtlog['anz'] > 0 ? '<br><br>Noch '.$versuche.$versuchetxt.' dann wird der Login f&uuml;r 15 Minuten gesperrt!' : '';

    // Meldung wenn falsche Logindaten...
    if (isset($loginerr)) {
        $loginmeldung = datensaver($loginerr, 1, 3);
    }
    if (isset($_GET['err']) && $_GET['err'] != '') {
        $loginmeldung = datensaver($_GET['err'], 1, 3);
    }

    if ($loginmeldung == 1) {
        $hinweis = 'Logindaten leider falsch...'.$loginsperrtext;
    } elseif ($loginmeldung == 2) {
        $hinweis = 'Sie haben sich ausgeloggt';
    } elseif ($loginmeldung == 3) {
        $hinweis = 'Es konnten keine g&uuml;ltigen Logindaten gefunden werden'.$loginsperrtext;
    } elseif ($loginmeldung == '') {
        $hinweis = '&nbsp;';
    }

    $sektion = 'Administrator Login';
    echo globaler_header($sektion, '', '', '');

    if ($gesamtlogerr < 5) {
        // ... oder Adminlogin anzeigen
        echo '<form action="'.$scriptconf['ADMINURL'].'/'.SCRIPTNAME.'" method="POST">'."\n";
        echo '<input type="hidden" name="go" value="logincheck">'."\n";

        $contentarray1 = [
        'LOGINBEREICH'     => $sektion,
        'LOGINFEHLER'      => $hinweis,
        ];

        $tparse->get_tpldata(ROOT_PFAD.'templates/adminlogin.html');
        echo $tparse->templateparser($contentarray1);
    } else {
        $contentarray2 = [
        'FEHLERTITEL'     => '&#187; LOGIN GESPERRT',
        'FEHLERTEXT'      => 'Der Administratorzugang wurde wegen wiederholter Falscheingaben der Zugangsdaten gesperrt!',
        ];
        $tparse->get_tpldata(ROOT_PFAD.'templates/adminloginerror.html');
        echo $tparse->templateparser($contentarray2);
    } // if / else installer/updater/Loginsperre
    echo globaler_footer(1);
}

/**
 * Logindaten checken und Cookie setzen, oder zurueck zum Login.
 *
 * @return void
 */
function logincheck()
{
    global $scriptconf, $db;

    $username = datensaver($_POST['username'], 50, 4);
    $passwort = datensaver($_POST['passwort'], 50, 4);

    //-----------------------------------------------------------------//
    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = '';

    if ($username == '') {
        $fehler_gefunden = 1;
    }

    if ($passwort == '') {
        $fehler_gefunden = 1;
    }

    // Wenn Fehler...
    if ($fehler_gefunden) {
        login(1);
        exit;
    }

    $logzeit = time();
    $gesamtlog = $db->dbquery_first('SELECT SUM(aktion) AS anz FROM '.$db->db_prefix."adminlog WHERE aktion = 1 AND ($logzeit - UNIX_TIMESTAMP(aktzeit)) < 900");
    $gesamtlogerr = $gesamtlog['anz'] == '' ? 0 : $gesamtlog['anz'];
    if ($gesamtlog['anz'] >= 5) {
        header('Location: http://www.google.com');
        exit;
    }

    $user_ip = get_uip();

    $admindata = $db->dbquery_first('SELECT username, passwort, chili FROM '.$db->db_prefix."admin WHERE BINARY(username) = '".$username."' AND passwort = MD5(CONCAT('".$passwort."', chili))");
    $gefunden = $admindata['passwort'] != '' ? 1 : 0;

    if ($gefunden) {
        $cookiestringval = bin2hex($admindata['username'].'#'.$admindata['passwort']);
        setcookie($scriptconf['COOKPREFIX'].'admin', $cookiestringval, time() + 86400, '/');

        $db->run_update_query('UPDATE', 'admin', "lastlogin = NOW(), lastlogin_ip = '".$db->quoteval($user_ip)."'");
        $db->run_insert_query('INSERT INTO', 'adminlog', "(logid, aktion, aktzeit, aktip, aktionkomm, mem_id) VALUES (NULL, 2, NOW(), '".$db->quoteval($user_ip)."', 'Erfolgreicher Login in Administrationsbereich', 0)");

        redirect($scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go=startseite', 1, '<span class="tippgreen">Sie haben sich erfolgreich angemeldet</span>');
        exit;
    } else {
        $db->run_insert_query('INSERT INTO', 'adminlog', "(logid, aktion, aktzeit, aktip, aktionkomm, mem_id) VALUES (NULL, 1, NOW(), '".$db->quoteval($user_ip)."', 'Versuchter Login in Administrationsbereich mit ".$db->quoteval($username).' und '.$db->quoteval($passwort)."', 0)");

        // Fehler
        login(1);
    }
}

/**
 * Startseite der Administration.
 *
 * @return void
 */
function startseite()
{
    global $scriptconf, $db, $tparse;
    passdatencheck();

    $sektion = 'Startseite Administration';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    // #####################################################
    // Startseitenausgabe
    // #####################################################

    $aktuell = datumausgabe('10');
    $datepart = explode('-', $aktuell);

    // Bilder
    $gesamtpic = $db->dbquery_first('SELECT COUNT(*) AS anz FROM '.$db->db_prefix.'uploads');
    if ($gesamtpic['anz'] > 0) {
        $bildlink = '<a href="admin_bilder.php">Liste ansehen</a>';
    } else {
        $bildlink = '';
    }

    // neue Bilder
    $gesamtpicneu = $db->dbquery_first('SELECT COUNT(*) AS anz FROM '.$db->db_prefix.'uploads WHERE UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(up_datetime) < 86400');
    if ($gesamtpicneu['anz'] > 0) {
        $bildlinkneu = '<a href="admin_bilder.php?go=neue">Liste ansehen</a>';
    } else {
        $bildlinkneu = '';
    }

    // Belegter Speicherplatz
    $gesamtmb = $db->dbquery_first('SELECT SUM(up_bytesize + up_thumbbytesize) AS mb FROM '.$db->db_prefix.'uploads');

    // Traffic
    $gesamttr = $db->dbquery_first("SELECT SUM(traffic_bytes + traffic_thbytes) AS traffic, COUNT(*) AS eintraege, DATE_FORMAT(traffic_datetime, '%m-%Y')AS deldate FROM ".$db->db_prefix.'trafficlog  WHERE MONTH(traffic_datetime) = '.$datepart[1].' AND YEAR(traffic_datetime) = '.$datepart[0].' GROUP BY MONTH(traffic_datetime), YEAR(traffic_datetime)');

    // #####################################################
    $mysqlversion = $db->dbquery_first("SHOW VARIABLES LIKE 'version'");
    // #####################################################

    $contentarray = [
        'PHPVERSION'     => phpversion(),
        'MYSQLVERSION'   => $mysqlversion['Value'],
        'IMAGECOUNT'     => $gesamtpic['anz'],
        'IMAGESLINK'     => $bildlink,
        'IMAGESCOUNTNEW' => $gesamtpicneu['anz'],
        'IMAGESLINKNEW'  => $bildlinkneu,
        'USEDSPACE'      => dateigroesse($gesamtmb['mb']),
        'USEDTRAFFIC'    => dateigroesse($gesamttr['traffic']),
    ];
    $tparse->get_tpldata(ROOT_PFAD.'templates/adminstart.html');
    echo $tparse->templateparser($contentarray);
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Systemdaten.
 *
 * @return void
 */
function systemdaten()
{
    global $scriptconf, $db, $tparse;
    passdatencheck();

    $sektion = 'Systemdaten editieren';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    // Byte in MB umwandeln
    $mbwert1 = sprintf('%01.2f', ($scriptconf['MAXPICUPLOADMB'] / 1024 / 1024));
    $mbwert2 = sprintf('%01.2f', ($scriptconf['MAXSPEICHERPLATZ'] / 1024 / 1024));

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="systemdatensave">'."\n";

    $content = [
        'HTMLPATH'    => $scriptconf['HTMLPFAD'],
        'ADMINPATH'   => $scriptconf['ADMINPFAD'],
        'HTMLURL'     => $scriptconf['HTMLURL'],
        'ADMINURL'    => $scriptconf['ADMINURL'],
        'HOMEPAGEURL' => $scriptconf['HOMEPAGEURL'],
        'SITETITLE'   => $scriptconf['SITETITEL'],
        'MAXIMGSIZE'  => $mbwert1,
        'MAXSPACE'    => $mbwert2,
        'DELTIME'     => $scriptconf['PICDELETE'],
        'THUMBWIDTH'  => $scriptconf['THUMBWIDTH'],
        'THUMBHEIGHT' => $scriptconf['THUMBHEIGHT'],
        'ADMINNAME'   => $scriptconf['ADMINNAME'],
        'ADMINMAIL'   => $scriptconf['ADMINMAIL'],
    ];
    $tparse->get_tpldata(ROOT_PFAD.'templates/adminsystemdata.html');
    echo $tparse->templateparser($content);

    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Systemdaten speichern.
 *
 * @return void
 */
function systemdatensave()
{
    global $scriptconf,$db;
    passdatencheck();

    // #########################################################################
    //  Systempfade, URLs und Webseitentitel

    $htmlpfad = datensaver($_POST['htmlpfad'], 100, 1);
    $adminpfad = datensaver($_POST['adminpfad'], 100, 1);
    $htmlurl = datensaver($_POST['htmlurl'], 100, 1);
    $adminurl = datensaver($_POST['adminurl'], 100, 1);
    $homepageurl = datensaver($_POST['homepageurl'], 100, 1);
    $sitetitel = datensaver($_POST['sitetitel'], 100, 1);

    // #########################################################################
    //  Allg. Admindaten

    $adminname = datensaver($_POST['adminname'], 100, 1);
    $adminmail = datensaver($_POST['adminmail'], 100, 9);

    // #########################################################################
    // Uploader

    $maxpicuploadmb = format_preis($_POST['maxpicuploadmb'], 100, 1);
    $maxspeicherplatz = format_preis($_POST['maxspeicherplatz'], 100, 1);
    $picdelete = datensaver($_POST['picdelete'], 10, 3);
    $thumbwidth = datensaver($_POST['thumbwidth'], 10, 3);
    $thumbheight = datensaver($_POST['thumbheight'], 10, 3);

    // #########################################################################
    // Setupdefaults bei Werten setzen

    $maxpicuploadmb = $maxpicuploadmb != '' ? ($maxpicuploadmb * 1024 * 1024) : (1 * 1024 * 1024);
    $maxspeicherplatz = $maxspeicherplatz != '' ? ($maxspeicherplatz * 1024 * 1024) : (100 * 1024 * 1024);

    // #########################################################################
    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    $errormeldung = '<li>Die Pfadangabe f&uuml;r den "Pfad zum html Verzeichnis" ist falsch</li>'."\n";
    if (!@opendir($htmlpfad)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Die Pfadangabe f&uuml;r den "Pfad zum Admin Verzeichnis" ist falsch</li>'."\n";
    if (!@opendir($adminpfad)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "URL zum html Verzeichnis" enth&auml;lt keine oder keine g&uuml;ltigen URL Daten</li>'."\n";
    if ($htmlurl == '' || urlcounter($htmlurl) < 1) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "URL zum Adminverzeichnis" enth&auml;lt keine oder keine g&uuml;ltigen URL Daten</li>'."\n";
    if ($adminurl == '' || urlcounter($adminurl) < 1) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Betreiber/Administrator Name" enth&auml;lt keine Daten</li>'."\n";
    if ($adminname == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "E-Mail Adresse" wurde nicht ausgef�llt oder keine g&uuml;ltige E-Mail Adresse eingetragen.</li>'."\n";
    if ($adminmail == 'nm') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabe('Fehler beim Setupdaten &auml;ndern', '<ul>'.$fehlermeldung.'</ul>', 1, BEREICHSNAVI);
        exit;
    }

    //#########################################################

    // art = S, T oder Z = STRING, TEXT, ZAHL

    // Systempfade und URL's
    $db->run_update_query('UPDATE', 'system', "cwert = '".$htmlpfad."', art = 'S' WHERE cname = 'HTMLPFAD' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$adminpfad."', art = 'S' WHERE cname = 'ADMINPFAD' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$htmlurl."', art = 'S' WHERE cname = 'HTMLURL' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$adminurl."', art = 'S' WHERE cname = 'ADMINURL' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$homepageurl."', art = 'S' WHERE cname = 'HOMEPAGEURL' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$sitetitel."', art = 'S' WHERE cname = 'SITETITEL' AND prog_nr = 1");

    // Allg. Admindaten
    $db->run_update_query('UPDATE', 'system', "cwert = '".$adminname."', art = 'S' WHERE cname = 'ADMINNAME' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$adminmail."', art = 'S' WHERE cname = 'ADMINMAIL' AND prog_nr = 1");

    // Uploader
    $db->run_update_query('UPDATE', 'system', "cwert = '".$maxpicuploadmb."', art = 'Z' WHERE cname = 'MAXPICUPLOADMB' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$maxspeicherplatz."', art = 'Z' WHERE cname = 'MAXSPEICHERPLATZ' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$picdelete."', art = 'Z' WHERE cname = 'PICDELETE' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$thumbwidth."', art = 'Z' WHERE cname = 'THUMBWIDTH' AND prog_nr = 1");
    $db->run_update_query('UPDATE', 'system', "cwert = '".$thumbheight."', art = 'Z' WHERE cname = 'THUMBHEIGHT' AND prog_nr = 1");

    rewrite_setup();

    redirect($adminurl.'/'.SCRIPTNAME.'?go=systemdaten', 1, 'Setupdaten wurden gespeichert...');
    exit;
}

/**
 * Admindaten.
 *
 * @return void
 */
function admindaten()
{
    global $scriptconf, $db;
    passdatencheck();

    $sektion = 'Administrator Daten &auml;ndern';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="newadmindatensave">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(2); ?>
    <tr>
        <td colspan="2" class="innend"><b>Jetzige Daten</b></td>
    </tr>
    <tr>
        <td class="innenh" width="50%">Jetziger Username</td>
        <td class="innenh" width="50%"><input class="inpu" style="width: 200px;" type="text" name="oldusername" size="30" maxlength="30" autocomplete="off"></td>
    </tr>
    <tr>
        <td class="innenh" width="50%">Jetziges Passwort</td>
        <td class="innenh" width="50%"><input class="inpu" style="width: 200px;" type="password" name="oldpasswort" size="30" maxlength="30" autocomplete="off"></td>
    </tr>
    <tr>
        <td colspan="2" class="innenhd"><b>Neue Daten</b><br>Benutzen Sie bei der Eingabe nur Buchstaben von A-Z a-z sowie Umlaute und Zahlen von 0-9, au&szlig;erdem m&ouml;glich . _ und Bindestrich - Alle anderen Zeichen werden entfernt.<br><br><b>Mindestl&auml;nge f&uuml;r Username und Passwort 5 Zeichen, maximal 30 Zeichen!</b></td>
    </tr>
    <tr>
        <td class="innenh" width="50%">Neuer Username</td>
        <td class="innenh" width="50%"><input class="inpu" style="width: 200px;" type="text" name="newusername" size="30" maxlength="30" autocomplete="off"></td>
    </tr>
    <tr>
        <td class="innenh" width="50%">Neues Passwort</td>
        <td class="innenh" width="50%"><input class="inpu" style="width: 200px;" type="password" name="newpasswort" size="30" maxlength="30" autocomplete="off"></td>
    </tr>
    <tr>
        <td colspan="2" class="innend" align="center"><input class="los" type="Submit" value="Neue Daten speichern"></td>
    </tr>
    <?php echo trl(2); ?>
    </table>
    </form>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Neue Admindaten speichern.
 *
 * @return void
 */
function newadmindatensave()
{
    global $scriptconf, $db;
    passdatencheck();

    $oldusername = datensaver($_POST['oldusername'], 35, 8);
    $oldpasswort = datensaver($_POST['oldpasswort'], 35, 8);

    $newusername = datensaver($_POST['newusername'], 35, 8);
    $newpasswort = datensaver($_POST['newpasswort'], 35, 8);

    $laengeuser = strlen($newusername);
    $laengepass = strlen($newpasswort);

    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    // Erlaubte Zeichen Testen
    $errormeldung = '<li>Das Feld "Jetziger Username" wurde nicht korrekt ausgef�llt. Es d&uuml;rfen nur nur Buchstaben von A-Z a-z inkl. Umlaute, Zahlen von 0-9,der Punkt, der Unterstrich und Bindestrich verwendet werden.</li>'."\n";
    if (!preg_match("/^[0-9a-zA-Z�-��-��\._\-]{5,30}$/", $oldusername)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Erlaubte Zeichen Testen
    $errormeldung = '<li>Das Feld \"Jetziges Passwort\" wurde nicht korrekt ausgef�llt. Es d&uuml;rfen nur nur Buchstaben von A-Z a-z inkl. Umlaute, Zahlen von 0-9,der Punkt, der Unterstrich und Bindestrich verwendet werden.</li>'."\n";
    if (!preg_match("/^[0-9a-zA-Z�-��-��\._\-]{5,30}$/", $oldpasswort)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabe('Fehler beim Admindaten &auml;ndern', '<ul>'.$fehlermeldung.'</ul>', 1, BEREICHSNAVI);
        exit;
    }

    $gefunden = 0;

    if ($oldusername != '' && $oldpasswort != '') {
        $testadmindata = $db->dbquery_first('SELECT username, passwort, chili FROM '.$db->db_prefix."admin WHERE BINARY(username) = '".$oldusername."' AND passwort = MD5(CONCAT('".$oldpasswort."', chili))");
        $gefunden = $testadmindata['passwort'] != '' ? 1 : 0;
    }

    //-----------------------------------------------------------------//
    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    $errormeldung = '<li>Es konnten keine Daten f&uuml;r  "Jetziger Username" und  "Jetziges Passwort" ermittelt werden!</li>'."\n";
    if (!$gefunden) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Pr�fung auf bestimmte Minimall�nge
    $errormeldung = '<li>Das Feld "Neuer Username" wurde nicht korrekt ausgef�llt. Mindestens 5 Zeichen m&uuml;ssen in dieses Feldes eingegeben werden.<br><b>Sie haben '.$laengeuser.' Zeichen eingetragen.</b></li>'."\n";
    if (strlen($newusername) < 5) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Erlaubte Zeichen Testen
    $errormeldung = '<li>Das Feld "Neuer Username" wurde nicht korrekt ausgef�llt. Es d&uuml;rfen nur nur Buchstaben von A-Z a-z inkl. Umlaute, Zahlen von 0-9,der Punkt, der Unterstrich und Bindestrich verwendet werden.</li>'."\n";
    if (!preg_match("/^[0-9a-zA-Z�-��-��\._\-]{5,30}$/", $newusername)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Pr�fung auf bestimmte Minimal und Maximall�nge
    $errormeldung = '<li>Das Feld "Neues Passwort" wurde nicht korrekt ausgef�llt. Mindestens 5 Zeichen m&uuml;ssen in dieses Feldes eingegeben werden.<br><b>Sie haben '.$laengepass.' Zeichen eingetragen.</b></li>'."\n";
    if (strlen($newpasswort) < 5) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Erlaubte Zeichen Testen
    $errormeldung = '<li>Das Feld "Neues Passwort" wurde nicht korrekt ausgef�llt. Es d&uuml;rfen nur nur Buchstaben von A-Z a-z inkl. Umlaute, Zahlen von 0-9,der Punkt, der Unterstrich und Bindestrich verwendet werden.</li>'."\n";
    if (!preg_match("/^[0-9a-zA-Z�-��-��\._\-]{5,30}$/", $newpasswort)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Der Username und das Passwort sind gleich, dies ist aus Sicherheitsgr&uuml;nden nicht gestattet</li>'."\n";
    if ($newusername == $newpasswort) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabe('Fehler beim Admindaten &auml;ndern', '<ul>'.$fehlermeldung.'</ul>', 1, BEREICHSNAVI);
        exit;
    }
    $chili = multirandom(1, 6, 9, '');

    $db->run_update_query('UPDATE', 'admin', "username = '".$newusername."', passwort = MD5('".$newpasswort.$chili."'), chili = '$chili' WHERE username = '".$testadmindata['username']."' AND passwort = '".$testadmindata['passwort']."'");

    // =======================================================
    // Maildaten zusamenstellen

    $pwchangedatum = datumausgabe('3');
    $login_url = $scriptconf['ADMINURL'].'/'.SCRIPTNAME;

    $messageadmin = 'Hallo '.$scriptconf['ADMINNAME'].',

    in dieser Mail erhalten Sie die am '.$pwchangedatum.'
    geänderten Logindaten für den Zugang zum Administratorlogin

    Login URL: '.$login_url.'
    ====================================

    Login mit:
    Username: '.$newusername.'
    Passwort: '.$newpasswort.'

    ====================================
    ';

    $empfaenger = $scriptconf['ADMINNAME'].'<'.$scriptconf['ADMINMAIL'].'>';
    $absender = $scriptconf['ADMINNAME'].'<'.$scriptconf['ADMINMAIL'].'>';
    $betreff = 'Ihre neuen Logindaten';

    textmail($empfaenger, $absender, $betreff, $messageadmin, $scriptconf['ADMINMAIL'], $scriptconf['ADMINMAIL']);

    $newcookdata = $db->dbquery_first('SELECT username, passwort FROM '.$db->db_prefix."admin WHERE BINARY(username) = '".$newusername."' AND passwort = MD5(CONCAT('".$newpasswort."', chili))");

    $cookiestringval = bin2hex($newcookdata['username'].'#'.$newcookdata['passwort']);
    setcookie($scriptconf['COOKPREFIX'].'admin', $cookiestringval, time() + 86400, '/');

    redirect($scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go=startseite', 1, 'Admindaten erfolgreich ge&auml;ndert');
    exit;
}

/**
 * Ausloggen.
 *
 * @return void
 */
function logout()
{
    global $scriptconf;

    setcookie($scriptconf['COOKPREFIX'].'admin', '', time() - 1586400, '/');

    login(2);
    exit;
}

/**
 * Adminlog.
 *
 * @return void
 */
function adminlog()
{
    passdatencheck();
    global $scriptconf, $db;

    $los = isset($_GET['los']) && $_GET['los'] != '' ? datensaver($_GET['los'], 10, 3) : 1;
    $per_page = 50;
    $anz = ($los - 1) * $per_page;

    $sektion = 'Statistik der Adminloginversuche';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI);

    list($result, $sumad, $alle) = $db->run_dbqueryanz('SELECT SQL_CALC_FOUND_ROWS logid, aktion, '.$db->datumsformate('aktzeit', 1, 2).' AS logtime, aktip, aktionkomm FROM '.$db->db_prefix.'adminlog WHERE mem_id = 0 AND aktion IN(1,2) ORDER BY aktzeit DESC LIMIT '.$anz.', '.$per_page, 1);

    // Seitennavidaten generieren, falls noetig
    if ($alle > $per_page) {
        $navilinks = pager($alle, $los, $per_page, SCRIPTNAME.'?go=adminlog&amp;los', 1, 3, 0);
        $navigationslinks = '<tr>
            <td class="innenh" colspan="4" align="right">'.$navilinks.'</td>
        </tr>
        ';
    } else {
        $navigationslinks = '';
    }

    $logdata = '';
    if ($sumad > 0) {
        while ($logins = $db->get_single_row($result)) {
            $status = $logins['aktion'] == 1 ? '<span class="tippred">&bull;</span>' : '<span class="tippgreen">&bull;</span>';
            $cssval = $logins['aktion'] == 1 ? 'auswahlcol' : 'innenh';
            $logdata .= '<tr>
                <td class="'.$cssval.'">'.$status.'</td>
                <td class="'.$cssval.'" style="white-space: nowrap;">'.$logins['aktip'].'</td>
                <td class="'.$cssval.'" style="white-space: nowrap;">'.$logins['aktionkomm'].'</td>
                <td class="'.$cssval.'" style="white-space: nowrap;">'.$logins['logtime'].'</td>
            </tr>
            ';
        }
    } else {
        $logdata = '<tr>
            <td class="innenh" colspan="4"><b>Es sind derzeit keine Adminloginstatistiken vorhanden</b></td>
        </tr>
        ';
    }

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="adminlogdel">'."\n"; ?>


    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(4); ?>
    <tr>
        <td class="innend" colspan="4"><b>Auflistung aller erfolgreicher Logins und fehlgeschlagener Loginversuche</b></td>
    </tr>
    <tr>
        <td class="innenh" colspan="4">
        <b>Legende</b><br><br>
        <span class="tippred">&bull; Login fehlgeschlagen</span><br> 
        <span class="tippgreen">&bull; Login OK</span><br><br>
        Sie k&ouml;nnen diese Daten von Zeit zu Zeit l&ouml;schen, diese Statistik dient dazu Ihnen einen &Uuml;berblick zu verschaffen ob und wann versucht wurde in die Administration einzudringen.</td>
    </tr>
    <?php echo $navigationslinks; ?>
    <tr>
        <td class="innenhd">&nbsp;</td>
        <td class="innenhd"><b>IP-Nummer</b></td>
        <td class="innenhd"><b>Logtext</b></td>
        <td class="innenhd"><b>Datum & Zeit</b></td>
    </tr>
    <?php echo $logdata; ?>
    <?php echo $navigationslinks; ?>
    <tr>
        <td class="innend" align="center" colspan="4"><input class="los" type="submit" value="Alle <?php echo $alle; ?> Adminlogdaten l&ouml;schen"></td>
    </tr>
    <?php echo trl(4); ?>
    </table>
    </form>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}

/**
 * Adminlog leeren.
 *
 * @return void
 */
function adminlogdel()
{
    passdatencheck();
    global $scriptconf, $db;

    $db->run_delete_query('DELETE FROM', 'adminlog', 'WHERE mem_id = 0 AND aktion IN(1,2)');

    redirect($scriptconf['ADMINURL'].'/'.SCRIPTNAME.'?go=adminlog', 1, 'Logeintr&auml;ge gel&ouml;scht...');
    exit;
}

/**
 * PHP Infos.
 *
 * @return void
 */
function info()
{
    global $scriptconf;
    passdatencheck();

    $sektion = 'PHP Infos';
    echo globaler_header($sektion, '', '', '');
    echo globallayoutoben($sektion, BEREICHSNAVI); ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(); ?>
    <tr>
        <td class="innenh">
        <?php phpinfo(); ?>
        </td>
    </tr>
    <?php echo trl(); ?>
    </table>

    <?php
    // Seitenfooter fuer alle Seiten
    echo globaler_footer();
}
