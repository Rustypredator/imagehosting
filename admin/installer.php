<?php

error_reporting(E_ALL & ~E_NOTICE);
define('SCRIPTSECURE', 1);
define('ROOT_PFAD', './');
define('SCRIPTNAME', 'installer.php');
define('SETUP_PFAD', './');

require_once 'includes/globale_funct_inc.php';
// Templateparser laden
$tparse = new template();

define('SUPPORTMAIL', 'webmaster@inspire-net.de');
define('SUPPORTURL', 'http://www.inspire-world.de');
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
case 'step8':
    step8();
    break;
case 'step7':
    step7();
    break;
case 'step6':
    step6();
    break;
case 'step5':
    step5();
    break;
case 'step4':
    step4();
    break;
case 'step3':
    step3();
    break;
case 'step2':
    step2();
    break;
default:
    step1();
    break;
}

/**
 * Installation Startseite.
 *
 * @return void
 */
function step1()
{
    global $tparse;

    $sektion = '1. Installation Startseite';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="step2">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(); ?>
    <tr>
        <td class="innend"><b>Installationsablauf und ben&ouml;tigte Daten</b></td>
    </tr>

    <tr>
    <td class="innenh">
    <?php echo $fehlerliste; ?>


    Diese Installationsroutine wird Sie nun durch den Installationspropzess des Scriptes leiten. 
    Sollten dabei Fragen auftreten oder Fehlermeldungen erscheinen die Sie nicht beheben k&ouml;nnen, kontaktieren Sie bitte
    den Herstellersupport.<br> Entweder per <a href="mailto:<?php echo SUPPORTMAIL; ?>">E-Mail</a> oder aber im <a href="<?php echo SUPPORTURL; ?>" target="_blank">Forum von Inspire-World</a>.
    <br><br>
    <b>Der Installationsprozess l&auml;uft in 4 Hauptschritten ab:</b>
    <ol>
    <li>Pr&uuml;fung der Dateien und Dateirechte (chmod) die f&uuml;r das Setup n&ouml;tig sind</li>
    <li>Eingabe, Anpassung der Datenbankdaten und Systemvariablen</li>
    <li>Speicherung der Datenbankdaten und anlegen der Datenbanktabellen</li>
    <li>Abschlie&szlig;ender Dateitest, testen der Funktionsf&auml;higkeit des Scriptes</li>
    </ol>

    <b>Bitte stellen Sie vor dem Installationsbeginn sicher das:</b>

    <ol>
    <li>Eine Datenbank existiert</li>
    <li>Sie die korrekten Datenbankzugangsdaten besitzen, dieses sind:</li>
    <ol type="A">
    <li>Der Datenbank Host (meist localhost)</li>
    <li>Den Datenbankname</li>
    <li>Den Datenbank Username</li>
    <li>Das Datenbankpasswort</li>
    </ol>
    <li>Alle Dateien sich laut Readme auf dem Server befinden</li>
    </ol>

    </td>
    </tr>

    <tr>
        <td class="innend" align="center"><input class="los" type="Submit" value="Weiter zu Schritt 1"></td>
    </tr>
    <?php echo trl(); ?>
    </table>
    </form>
    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
}

/**
 * Installation - Dateitest.
 *
 * @return void
 */
function step2()
{
    global $tparse;

    $sektion = '2. Installation Dateitest';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    // chmod Vorgabe, falls noetig hier aendern
    $chm777 = 777;

    // Verzeichnisse checken
    $dateiverz = [
        "logins|$chm777",
        "setup|$chm777",
        "tmpdaten|$chm777",
        "../img/|$chm777",
        "../img/gif|$chm777",
        "../img/jpg|$chm777",
        "../img/png|$chm777",
        "../uploads|$chm777",
    ];

    // Dateien checken
    $dateien = [
        "setup/dbdaten.php|$chm777",
        "setup/progmenue.dat|$chm777",
        "setup/setup.php|$chm777",
    ];

    $chmcount = 0;

    // #################################################################################
    foreach ($dateiverz as $verztest) {
        $test_vz = explode('|', $verztest);

        $chmod = file_perms($test_vz[0], true);
        clearstatcache();
        if ($chmod == $test_vz[1]) {
            $tab_rowv .= '<tr>
            <td class="innenh"><b>Verzeichnis</b> '.$test_vz[0].'</td>
            <td class="innenh"><b>CHMOD</b> '.$chmod.'</td>
            <td class="innenh"><b>Status</b> <span class="tippgreen">OK</span></td>
            </tr>
            ';
        } else {
            $chmcount++;
            $tab_rowv .= '<tr>
            <td class="innenh"><b>Verzeichnis</b> '.$test_vz[0].'</td>
            <td class="innenh"><b>CHMOD</b> <span class="tippred">'.$chmod.'</span></td>
            <td class="innenh"><b>Status</b> <span class="tippred">NICHT OK,</span> Soll: '.$test_vz[1].'</td>
            </tr>
            ';
        }
    }

    // #################################################################################
    foreach ($dateien as $dateitest) {
        $test_fi = explode('|', $dateitest);

        $chmodf = file_perms($test_fi[0], false);
        clearstatcache();
        if ($chmodf == $test_fi[1]) {
            $tab_rowf .= '<tr>
            <td class="innenh"><b>Datei</b> '.$test_fi[0].'</td>
            <td class="innenh"><b>CHMOD</b> '.$chmodf.'</td>
            <td class="innenh"><b>Status</b> <span class="tippgreen">OK</span></td>
            </tr>
            ';
        } else {
            $chmcount++;
            $tab_rowf .= '<tr>
            <td class="innenh"><b>Datei</b> '.$test_fi[0].'</td>
            <td class="innenh"><b>CHMOD</b> <span class="tippred">'.$chmodf.'</span></td>
            <td class="innenh"><b>Status</b> <span class="tippred">NICHT OK,</span> Soll: '.$test_fi[1].'</td>
            </tr>
            ';
        }
    }
    // #################################################################################

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="step3">'."\n"; ?>
    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(3); ?>
    <tr>
        <td class="innend" colspan="3"><b>Verzeichnistest</b></td>
    </tr>
    <?php echo $tab_rowv; ?>
    <?php echo trl(3); ?>
    <tr>
        <td class="innend" colspan="3"><b>Dateitest</b></td>
    </tr>
    <?php echo $tab_rowf; ?>

    <?php 
    if ($chmcount > 0) {
        echo '<tr>
        <td  colspan="3" class="innend" align="center"><span class="tippred">FEHLER, Bitte <a href="javascript:history.go(-1)">zur&uuml;ckgehen</a> und berichtigen</span></td>
        </tr>';
    } else {
        echo '<tr>
        <td  colspan="3" class="innend" align="center"><input class="los" type="Submit" value="Weiter zu Schritt 3"></td>
        </tr>';
    } ?>
    <?php echo trl(3); ?>
    </table>
    </form>
    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
}

/**
 * Installation - Datenbankdaten.
 *
 * @return void
 */
function step3()
{
    global $tparse;

    $sektion = '3. Installation Datenbankdaten eingeben';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="step4">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(2); ?>
    <tr>
        <td class="innend" colspan="2"><b>Geben Sie hier die Datenbankdaten und einen Prefix f&uuml;r die Datenbanktabellen ein</b></td>
    </tr>
    <tr>
        <td class="innenh" width="40%">Datenbankhost (meist localhost)</td>
        <td class="innenh" width="60%"><input type="text" class="inpu" style="width: 200px;" name="dbhost" size="35" value="localhost" maxlength="50"></td>
    </tr>
    <tr>
        <td class="innenh">Datenbankname</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 200px;" name="dbname" size="35" maxlength="50"></td>
    </tr>
    <tr>
        <td class="innenh">Datenbank Benutzername</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 200px;" name="dbuser" size="35" maxlength="50"></td>
    </tr>
    <tr>
        <td class="innenh">Datenbank Passwort</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 200px;" name="dbpass" size="35" maxlength="50"></td>
    </tr>
    <tr>
        <td class="innend" colspan="2"><b>Der Datenbank Tabellen Prefix darf nur aus den Zeichen A - Z und dem Unterstrich bestehen, es sollten Gro&szlig;buchstaben verwendet werden.</b></td>
    </tr>
    <tr>
        <td class="innenh">Datenbanktabellen Prefix</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 100px;" name="dbprefix" value="PICUP_" size="35" maxlength="15"></td>
    </tr>
    <tr>
        <td  colspan="2" class="innend" align="center"><input class="los" type="Submit" value="Daten speichern"></td>
    </tr>
    <?php echo trl(2); ?>
    </table>
    </form>
    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
}

/**
 * Installation - Datenbankdaten speichern.
 *
 * @return void
 */
function step4()
{
    global $tparse;

    $dbhost = datensaver($_POST['dbhost'], 50, 1);
    $dbname = datensaver($_POST['dbname'], 50, 1);
    $dbuser = datensaver($_POST['dbuser'], 50, 1);
    $dbpass = datensaver($_POST['dbpass'], 50, 1);
    $dbprefix = datensaver($_POST['dbprefix'], 15, 1);

    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    $errormeldung = '<li>Das Script wurde schon installiert, weitere Installationsversuche sind nicht m&ouml;glich. Sollte es wegen eines Fehlers n&ouml;tig sein diese Installroutine erneut auszuf&uuml;hren, so m&uuml;ssen zuerst alle Datenbanktabellen und die Datei setup/install_ok.dat gel&ouml;scht werden.</li>'."\n";
    if (file_exists('setup/install_ok.dat')) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Datenbankhost" enth&auml;lt keine Daten</li>'."\n";
    if ($dbhost == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Datenbankname" enth&auml;lt keine Daten</li>'."\n";
    if ($dbname == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Datenbank Benutzername" enth&auml;lt keine Daten</li>'."\n";
    if ($dbuser == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Datenbank Passwort" enth&auml;lt keine Daten</li>'."\n";
    if ($dbpass == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Datenbanktabellen Prefix" enth&auml;lt g&uuml;ltigen Daten. Der Prefix muss nach dem Schema BUCHSTABEN plus Unterstrich _ aufgebaut sein. G&uuml;ltig w&auml;re z.B. LOS_ oder LOSE_</li>'."\n";
    if (!preg_match('/^[A-Z]{2,14}_$/', $dbprefix)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabeinstall('Fehler', '<ul>'.$fehlermeldung.'</ul>', 1);
        exit;
    }

    @MYSQL_CONNECT($dbhost, $dbuser, $dbpass) or fehlerausgabeinstall('Fehler', '<li>Datenbankserver nicht erreichbar, bitte pr&uuml;fen Sie die Eingaben', 1);
    @MYSQL_SELECT_DB($dbname) or fehlerausgabeinstall('Fehler', '<li>Datenbank nicht vorhanden, bitte pr&uuml;fen Sie die Eingaben', 1);

    if (!file_exists('setup/install_ok.dat')) {
        $ergebnis = mysql_query("SHOW TABLE STATUS FROM `$dbname` LIKE '".$dbprefix."system'");
        $ausgabe = mysql_fetch_row($ergebnis) != '' ? 1 : 0;

        // Fehlerbehandlung
        $fehlermeldung = '';
        $fehler_gefunden = 0;

        $errormeldung = '<li><span class="tippred">Es gibt bereits eine Tabelle in der Datenbank mit dem gew&auml;hlten Prefix: '.$dbprefix.'!</span><br><br>Bitte w&auml;hlen Sie einen anderen Tabellen Prefix.</li>'."\n";
        if ($ausgabe == 1) {
            $fehlermeldung = $fehlermeldung.$errormeldung;
            $fehler_gefunden = 1;
        }

        // Wenn Fehler - dann aufruf der Fehlerfunktion...
        if ($fehler_gefunden) {
            fehlerausgabeinstall('Fehler', '<ul>'.$fehlermeldung.'</ul>', 1);
            exit;
        }

        $fp = fopen('setup/dbdaten.php', 'w') or fehlerausgabeinstall('Fehler', '<li>Kann Datei setup/dbdaten.php nicht oeffnen', 1);
        fwrite($fp, "<?php\n");
        fwrite($fp, "// Zugriff ueber \$dbdaten['server'], \$dbdaten['datenbank'] usw.\n");
        fwrite($fp, "\$dbdaten = array(\n");
        fwrite($fp, "// DATENBANKSERVER\n");
        fwrite($fp, "'server' 			=> '$dbhost',\n");
        fwrite($fp, "// DATENBANK\n");
        fwrite($fp, "'datenbank' 		=> '$dbname',\n");
        fwrite($fp, "// DATENBANK USERNAME\n");
        fwrite($fp, "'user' 				=> '$dbuser',\n");
        fwrite($fp, "// DATENBANK PASSWORT\n");
        fwrite($fp, "'passwort' 			=> '$dbpass',\n");
        fwrite($fp, "// DATENBANK TABELLEN PREFIX\n");
        fwrite($fp, "'prefix' 			=> '$dbprefix'\n");
        fwrite($fp, ");\n");
        fwrite($fp, "?>\n");
        fclose($fp);
        @chmod('setup/dbdaten.php', 0777);

        // #################################################################################
        // .htaccess Dateien anlegen in noetigen Unterverzeichnissen
        $htaccessfileinhalt = 'AuthType Basic
        <Limit GET>
            Order Allow,Deny
            Deny from all
        </Limit>
        <Limit POST>
            Order Allow,Deny
            Deny from all
        </Limit>
        ';

        $fp2 = fopen('logins/.htaccess', 'w');
        fwrite($fp2, $htaccessfileinhalt);
        fclose($fp2);
        @chmod('logins/.htaccess', 0666);

        $fp3 = fopen('setup/.htaccess', 'w');
        fwrite($fp3, $htaccessfileinhalt);
        fclose($fp3);
        @chmod('setup/.htaccess', 0666);
    }

    redirect('installer.php?go=step5', 1, '<span class="tippgreen">Datenbankdaten erfolgreich gespeichert</span>');
    exit;
}
/**
 * Installation - Datenbankdaten.
 *
 * @return void
 */
function step5()
{
    global $tparse;

    $sektion = '4. Installation Setupdaten eingeben';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    $GESAMTURI = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
    $scripturi = preg_replace("/\/admin\/installer.php/", '', $GESAMTURI);
    $scripturiadmin = preg_replace("/\/installer.php/", '', $GESAMTURI);

    $PFAD = $_SERVER['SCRIPT_FILENAME'];
    $scriptpfad = preg_replace("/\/admin\/installer.php/", '', $PFAD);
    $scriptpfadadmin = preg_replace("/\/installer.php/", '', $PFAD);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="step6">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(2); ?>
    <tr>
        <td class="innend" colspan="2"><b>Setup Daten f&uuml;r Name, E-Mail, URL und Pfade</b></td>
    </tr>
    <tr>
        <td class="innenh">Betreiber/Administrator Name</td>
        <td class="innenh"><input class="inpu" style="width: 300px;" size="30" type="Text" name="adminname" value=""></td>
    </tr>
    <tr>
        <td class="innenh">E-Mail Adresse</td>
        <td class="innenh"><input class="inpu" style="width: 300px;" size="30" type="Text" name="adminemail" value=""></td>
    </tr>

    <tr>
        <td class="innenhd" colspan="2"><span class="tippred">Bitte beachten Sie beim ausf&uuml;llen folgendes!</span><br>
        Das Installerscript versucht so gut es geht die passenden Werte f&uuml;r die folgenden URL und Pfadangaben herauszufinden. Dies muss aber nicht auf jedem Server gleich gut
        funktionieren. Pr&uuml;fen Sie deshalb die Vorgaben genau und korrigieren Sie diese n&ouml;tigenfalls. 
        </td>
    </tr>
    <tr>
        <td class="innenh">Script URL zum Hauptverzeichnis ohne / am Ende</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 400px;" name="homeurl" value="<?php echo $scripturi; ?>" size="35" maxlength="100"></td>
    </tr>
    <tr>
        <td class="innenh">Script URL zum Adminverzeichnis / am Ende</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 400px;" name="adminurl" value="<?php echo $scripturiadmin; ?>" size="35" maxlength="100"></td>
    </tr>

    <tr>
        <td class="innenh">Script Pfad zum Hauptverzeichnis ohne / am Ende</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 400px;" name="htmlpfad" value="<?php echo $scriptpfad; ?>" size="35" maxlength="100"></td>
    </tr>
    <tr>
        <td class="innenh">Script Pfad zum Adminverzeichnis / am Ende</td>
        <td class="innenh"><input type="text" class="inpu" style="width: 400px;" name="adminpfad" value="<?php echo $scriptpfadadmin; ?>" size="35" maxlength="100"></td>
    </tr>
    <tr>
        <td  colspan="2" class="innend" align="center"><input class="los" type="Submit" value="Daten speichern"></td>
    </tr>
    <?php echo trl(2); ?>
    </table>
    </form>
    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
}

/**
 * Installation - Datenbankdaten.
 *
 * @return void
 */
function step6()
{
    global $tparse, $db;

    include_once ROOT_PFAD.'includes/class_dbhandler_mysql.php';
    // $db definieren
    $db = new dbhandler_mysql();
    // DB Verbindung aufbauen
    $db->db_connect();

    $htmlpfad = datensaver($_POST['htmlpfad'], 100, 1);
    $adminpfad = datensaver($_POST['adminpfad'], 100, 1);
    $homeurl = datensaver($_POST['homeurl'], 100, 1);
    $adminurl = datensaver($_POST['adminurl'], 100, 1);

    $adminname = datensaver($_POST['adminname'], 80, 1);
    $adminemail = datensaver($_POST['adminemail'], 100, 9);

    // Fehlerbehandlung
    $fehlermeldung = '';
    $fehler_gefunden = 0;

    $errormeldung = '<li>Das Feld "Betreiber/Administrator Name" enth&auml;lt keine oder keine g&uuml;ltigen Daten</li>'."\n";
    if ($adminname == '') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "E-Mail Adresse" enth&auml;lt keine oder keine g&uuml;ltigen Daten</li>'."\n";
    if ($adminemail == 'nm') {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Script URL zum Hauptverzeichnis" enth&auml;lt keine oder keine g&uuml;ltigen URL Daten</li>'."\n";
    if ($homeurl == '' || urlcounter($homeurl) < 1) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Das Feld "Script URL zum Adminverzeichnis" enth&auml;lt keine oder keine g&uuml;ltigen URL Daten</li>'."\n";
    if ($adminurl == '' || urlcounter($adminurl) < 1) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Die Pfadangabe f&uuml;r den "Script Pfad zum Hauptverzeichnis" ist falsch</li>'."\n";
    if (!@opendir($htmlpfad)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    $errormeldung = '<li>Die Pfadangabe f&uuml;r den "Script Pfad zum Adminverzeichnis" ist falsch</li>'."\n";
    if (!@opendir($adminpfad)) {
        $fehlermeldung = $fehlermeldung.$errormeldung;
        $fehler_gefunden = 1;
    }

    // Wenn Fehler - dann aufruf der Fehlerfunktion...
    if ($fehler_gefunden) {
        fehlerausgabeinstall('Fehler', '<ul>'.$fehlermeldung.'</ul>', 1);
        exit;
    }

    // #################################################################################
    // Datenbanktabellen anlegen

    // Cookieprefix
    $cookkey = multirandom(1, 6, 9, '');
    $chili = multirandom(1, 6, 9, '');
    $standarduser = 'admin';
    $standardpass = 'admin';
    $zeit = time();

    $mysqlversion = $db->dbquery_first("SHOW VARIABLES LIKE 'version'");
    $enginetyp = (version_compare($mysqlversion['Value'], '4.0.18', '<')) ? 'TYPE' : 'ENGINE';

    if (!file_exists('setup/install_ok.dat')) {

        // =======================================================
        // Setupdatentabelle anlegen und Daten speichern
        // =======================================================
        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."system (
            `cname` varchar(200) NOT NULL default '',
            `cwert` text NOT NULL,
            `prog_nr` smallint(4) unsigned NOT NULL default '0',
            `art` char(1) NOT NULL default 'S',
            UNIQUE KEY `cname` (`cname`)
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Setupdatentabelle anlegen und Daten speichern
        // =======================================================

        $db->run_insert_query(
            'INSERT INTO', 'system', "(`cname`, `cwert`, `prog_nr`, `art`) 
            VALUES 
            ('COOKPREFIX', '$cookkey', 1, 'S'),
            ('VERSION', '1.0', 1, 'S'),
            ('HTMLPFAD', '$htmlpfad', 1, 'S'),
            ('ADMINPFAD', '$adminpfad', 1, 'S'),
            ('HTMLURL', '$homeurl', 1, 'S'),
            ('ADMINURL', '$adminurl', 1, 'S'),
            ('HOMEPAGEURL', '$homeurl', 1, 'S'),
            ('SITETITEL', 'Bildhosting', 1, 'S'),
            ('ADMINNAME', '$adminname', 1, 'S'),
            ('ADMINMAIL', '$adminemail', 1, 'S'),
            ('MAXPICUPLOADMB', '1048576', 1, 'Z'),
            ('MAXSPEICHERPLATZ', '104857600', 1, 'Z'),
            ('PICDELETE', '365', 1, 'Z'),
            ('THUMBWIDTH', '180', 1, 'Z'),
            ('THUMBHEIGHT', '140', 1, 'Z'),
            ('UPLOADENDUNGEN', 'gif,jpg,jpeg,png', 1, 'S'),
            ('WARTUNGSDATE', CURDATE(), 1, 'S')
            "
        );

        // =======================================================
        // Tabelle fuer Admindaten
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."admin (
            `username` varchar(50) NOT NULL default '',
            `passwort` varchar(33) NOT NULL default '',
            `chili` varchar(10) NOT NULL default '',
            `regdate` datetime NOT NULL default '0000-00-00 00:00:00',
            `lastlogin` datetime NOT NULL default '0000-00-00 00:00:00',
            `lastlogin_ip` varchar(30) NOT NULL default ''
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Inserts fuer Admindaten
        // =======================================================

        $db->run_insert_query('INSERT INTO', 'admin', "(`username`, `passwort`, `chili`, `regdate`, `lastlogin`, `lastlogin_ip`) VALUES ('$standarduser', MD5('".$standardpass.$chili."'), '$chili', NOW(), '0000-00-00 00:00:00', '')");

        // =======================================================
        // Tabelle fuer adminlog
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."adminlog (
            `logid` int(10) unsigned NOT NULL auto_increment,
            `aktion` tinyint(1) unsigned NOT NULL default '0',
            `aktzeit` datetime NOT NULL default '0000-00-00 00:00:00',
            `aktip` varchar(30) NOT NULL default '',
            `aktionkomm` varchar(250) NOT NULL default '',
            `mem_id` int(10) unsigned NOT NULL default '0',
            PRIMARY KEY  (`logid`)
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Tabelle fuer gesamtstat
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."gesamtstat (
            `picanz` int(10) unsigned NOT NULL default '0',
            `picbytes` bigint(12) unsigned NOT NULL default '0',
            `aktuelldate` date NOT NULL default '0000-00-00',
            `heuteanzahl` mediumint(6) unsigned NOT NULL default '0',
            `alltraffic` bigint(12) unsigned NOT NULL default '0',
            `allpics` int(10) unsigned NOT NULL default '0'
            )".$enginetyp.'=MyISAM'
        );

        $db->run_insert_query('INSERT INTO', 'gesamtstat', "(`picanz`, `picbytes`, `aktuelldate`, `heuteanzahl`, `alltraffic`, `allpics`) VALUES ('0', '0', CURDATE(), '0', '0', '0')");

        // =======================================================
        // Tabelle fuer progmenue
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."progmenue (
            `prog_id` int(10) unsigned NOT NULL auto_increment,
            `prog_url` varchar(150) NOT NULL default '',
            `prog_link` varchar(150) NOT NULL default '',
            `prog_rf` mediumint(6) unsigned NOT NULL default '0',
            `prog_bereich` tinyint(1) unsigned NOT NULL default '1',
            PRIMARY KEY  (`prog_id`)
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Inserts fuer progmenue
        // =======================================================

        $db->run_insert_query(
            'INSERT INTO', 'progmenue', "(`prog_id`, `prog_url`, `prog_link`, `prog_rf`, `prog_bereich`) 
            VALUES 
            (1, 'admin.php?go=startseite', 'Startseite Administration', 10, 1),
            (2, 'admin.php?go=systemdaten', 'Systemdaten einstellen', 20, 1),
            (3, 'admin_bilder.php?go=neue', 'Neue Bilder', 30, 1),
            (4, 'admin_bilder.php', 'Liste aller Bilder', 40, 1),
            (5, 'admin_bilder.php?go=op', 'Alte Bilder l&ouml;schen', 50, 1),
            (6, 'admin_bilder.php?go=tl', 'Trafficlogs l&ouml;schen', 60, 1)"
        );

        // =======================================================
        // Inserts fuer Trafficlog
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."trafficlog (
            `traffic_id` int(10) unsigned NOT NULL auto_increment,
            `bild_id` int(10) unsigned NOT NULL default '0',
            `traffic_picname` varchar(33) NOT NULL,
            `traffic_thpicname` varchar(40) NOT NULL,
            `traffic_bytes` int(10) unsigned NOT NULL default '0',
            `traffic_thbytes` int(10) unsigned NOT NULL default '0',
            `trafic_art` tinyint(1) unsigned NOT NULL default '0',
            `traffic_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
            `traffic_ip` varchar(30) NOT NULL,
            PRIMARY KEY (`traffic_id`),
            KEY `bild_id` (`bild_id`)
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Tabelle fuer uploadfehlerlog
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."uploadfehlerlog (
            `logid` int(10) unsigned NOT NULL auto_increment,
            `errorid` varchar(50) NOT NULL default '',
            `meldung` varchar(250) NOT NULL default '',
            `errordate` datetime NOT NULL default '0000-00-00 00:00:00',
            `dateiname` varchar(150) NOT NULL default '',
            PRIMARY KEY  (`logid`)
            )".$enginetyp.'=MyISAM'
        );

        // =======================================================
        // Tabelle fuer uploads
        // =======================================================

        $db->db_query(
            'CREATE TABLE IF NOT EXISTS '.$db->db_prefix."uploads (
            `up_id` int(10) unsigned NOT NULL auto_increment,
            `up_picname` varchar(33)  NOT NULL,
            `up_orginalname` varchar(100) NOT NULL,
            `up_endung` varchar(5) NOT NULL,
            `up_vz` varchar(5) NOT NULL,
            `up_bytesize` int(10) unsigned NOT NULL default '0',
            `up_width` mediumint(5) unsigned NOT NULL default '0',
            `up_height` mediumint(5) unsigned NOT NULL default '0',
            `up_thumb` tinyint(1) unsigned NOT NULL default '0',
            `up_thumbwidth` smallint(4) unsigned NOT NULL default '0',
            `up_thumbheight` smallint(4) unsigned NOT NULL default '0',
            `up_thumbbytesize` mediumint(6) unsigned NOT NULL default '0',
            `up_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
            `up_ip` varchar(30) NOT NULL,
            `up_userid` int(10) unsigned NOT NULL default '0',
            `up_delkey` varchar(33) NOT NULL,
            `del_ok` tinyint(1) unsigned NOT NULL default '0',
            PRIMARY KEY  (`up_id`),
            KEY `up_picname` (`up_picname`),
            KEY `up_delkey` (`up_delkey`)
            )".$enginetyp.'=MyISAM'
        );
    } // if install OK

    // Install Testdatei um wiederholte Installationen zu vermeiden
    $fpinstall = fopen('setup/install_ok.dat', 'w') or fehlerausgabeinstall('Fehler', '<li>Kann Datei setup/install_ok.dat nicht oeffnen', 1);
    fclose($fpinstall);
    @chmod('setup/install_ok.dat', 0777);

    // #################################################################################

    rewrite_setup();

    redirect('installer.php?go=step7', 1, '<span class="tippgreen">Datenbanktabellen angelegt</span>');
    exit;
}

/**
 * Installation - Zwischenschritt vor Abschlusstest.
 *
 * @return void
 */
function step7()
{
    global $tparse;
    $sektion = '5. Installation Zwischenschritt vor Abschlusstest';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    echo '<form action="'.SCRIPTNAME.'" method="POST">'."\n";
    echo '<input type="hidden" name="go" value="step8">'."\n"; ?>

    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(); ?>
    <tr>
        <td class="innend"><b>Datenbank und Setupdaten wurden erfolgreich gespeichert</b></td>
    </tr>
    <tr>
        <td class="innenh">Rufen Sie nun den letzten Schritt auf, werden dort keine Fehler angezeigt ist das Script einsatzbereit.</td>
    </tr>
    <tr>
        <td class="innend" align="center"><input class="los" type="Submit" value="Weiter zum letzten Schritt..."></td>
    </tr>
    <?php echo trl(); ?>
    </table>
    </form>
    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
}

/**
 * Installation - Abschlusstest.
 *
 * @return void
 */
function step8()
{
    global $tparse, $db;

    $sektion = '6. Installation Abschlusstest';
    echo install_header($sektion, '', '', '');
    echo install_oben($sektion);

    // #################################################################################
    // Test

    // Test
    // #################################################################################?>
    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="innen">
    <?php echo trl(); ?>
    <tr>
        <td class="innend"><span class="tippgreen">Installationstest erfolgreich abgeschlossen</span></td>
    </tr>

    <tr>
        <td class="innenh">
    <span class="tippred">Wichtiger Hinweis!</span><br><br>
    L&ouml;schen Sie, wenn die Installation erfolgreich abgeschlossen wurde, nun die Datei <b>installer.php</b> vom Server.<br><br>
    Danach rufen Sie die Datei <b>admin.php</b> auf und loggen sich mit dem<br> 
    Username admin<br> 
    und<br> 
    Passwort admin<br> 
    ein.
    <br><br>
    Solange wie die Datei <b>installer.php</b> existiert k&ouml;nnen Sie das Script nicht administrieren!<br><br>

    &raquo; <a href="admin.php">Administration aufrufen... </a>
    <br><br>
        </td>
    </tr>
    <?php echo trl(); ?>
    </table>

    <?php
    // Seitenfooter fuer alle Seiten
    echo install_unten();
    exit;
}

/**
 * Hilfsroutinen.
 *
 * @param [type] $file  TODO: Beschreibung
 * @param bool   $octal TODO: Beschreibung
 *
 * @return void
 */
function file_perms($file, $octal = false)
{
    if (!file_exists($file)) {
        return false;
    }

    $perms = fileperms($file);
    $cut = $octal ? 2 : 3;

    return substr(decoct($perms), $cut);
}

/**
 * Index html Datei fuer Shopverzeichnisse anlegen.
 *
 * @param [type] $save_pfad TODO: Beschreibung
 *
 * @return void
 */
function create_index_file($save_pfad)
{
    $fp = fopen($save_pfad, 'w');
    fclose($fp);
    @chmod($save_pfad, 0777);
}

/**
 * Globaler Header.
 *
 * @param string $seitentitel TODO: Beschreibung
 * @param string $meta        TODO: Beschreibung
 * @param string $jsscript    TODO: Beschreibung
 * @param string $zusatzdaten TODO: Beschreibung
 *
 * @return void
 */
function install_header($seitentitel = '', $meta = '', $jsscript = '', $zusatzdaten = '')
{
    global $tparse;

    $contentarray = [
        'SEITENTITEL' => $seitentitel,
        'META'        => $meta,
        'JSSCRIPT'    => $jsscript,
        'ZUSATZDATEN' => $zusatzdaten,
    ];
    // Templatename
    $tparse->get_tpldata(ROOT_PFAD.'templates/installheader.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Globales oberes Layout - Installer.
 *
 * @param string $seitentitel TODO: Beschreibung
 *
 * @return void
 */
function install_oben($seitentitel = '')
{
    global $tparse;

    $contentarray = [
        'SEITENTITEL' => $seitentitel,
    ];
    // Templatename
    $tparse->get_tpldata(ROOT_PFAD.'templates/installer_oben.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Globales unteres Layout - Installer.
 *
 * @return void
 */
function install_unten()
{
    global $tparse;

    $tparse->get_tdata(ROOT_PFAD.'templates/installer_unten.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Script oder Benutzerfehlerausgabe Adminbereich.
 *
 * @param [type] $fehlertitel   TODO: Beschreibung
 * @param [type] $fehlermeldung TODO: Beschreibung
 * @param int    $backlink      TODO: Beschreibung
 *
 * @return void
 */
function fehlerausgabeinstall($fehlertitel, $fehlermeldung, $backlink = 1)
{
    global $tparse;
    echo install_header('Fehler!', '', '', '');
    echo install_oben('Fehler!');

    $backlinkcode = $backlink == 1 ? '<br><br><div align="center"><a href="javascript:history.go(-1)">Bitte zur&uuml;ckgehen und berichtigen</a></div>' : '';

    $contentarray = [
        'TEXTTOP'  => $fehlertitel,
        'TEXTCONT' => "$fehlermeldung $backlinkcode",
    ];
    // Templatename
    $tparse->get_tpldata(ROOT_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);

    echo install_unten();
    exit;
}

/**
 * Trennlinie.
 *
 * @param string $cs colspan
 *
 * @return void
 */
function trl($cs = '')
{
    $cshtml = $cs != '' ? 'colspan="'.$cs.'"' : '';
    $trhtml = '<tr>
    <th '.$cshtml.' class="smalltrenn"><img src="../misc/pixel.gif" alt="" width="1" height="1" border="0"></th>
    </tr>
    ';

    return $trhtml;
}

/**
 * Redirect Routine.
 *
 * @param [type] $url     TODO: Beschreibung
 * @param int    $wlz     TODO: Beschreibung
 * @param string $infotxt TODO: Beschreibung
 *
 * @return void
 */
function redirect($url, $wlz = 1, $infotxt = '')
{
    global $tparse;

    if ($wlz > 1) {
        $wlwort = 'in '.$wlz.' Sekunden';
    } elseif ($wlz == 1) {
        $wlwort = 'einer Sekunde';
    } elseif ($wlz == 0) {
        $wlwort = 'sofort';
    }

    echo install_header('Weiterleitung', '<meta http-equiv="refresh" content="'.$wlz.'; URL='.$url.'">', '', '');
    echo '<br><br><br><table cellspacing="2" cellpadding="2" border="0" width="100%"><tr><td width="20%">&nbsp;</td><td width="60%">';
    $contentarray = [
        'TEXTTOP'  => '<b>Weiterleitung</b>',
        'TEXTCONT' => $infotxt.'<br><br>Sie werden '.$wlwort.' weitergeleitet, sollte das nicht funktionieren bitte <a href="'.$url.'">hier klicken</a><br><br><br>',
    ];

    $tparse->get_tpldata(ROOT_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);
    echo '</td><td width="20%">&nbsp;</td></tr></table>';
    echo '</body></html>';
    exit;
}
