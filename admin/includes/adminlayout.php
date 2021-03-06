<?php

/*
 * Imagehosting-Script by inspire-World.de
 * Rework by Rustypredator <contact@rusty.info>
 *
 * Original Project: http://inspire-world.de/phpscripte13.php
 * Reworked Project: https://github.com/Rustypredator/imagehosting
 */

error_reporting(E_ALL & ~E_NOTICE);

if (!defined('SCRIPTSECURE')) {
    echo 'Unzul&auml;ssiger Scriptaufruf';
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
    $cshtml = $cs != '' ? "colspan=\"$cs\"" : '';
    $trhtml = "<tr>
        <th $cshtml class=\"smalltrenn\"><img src=\"../misc/pixel.gif\" alt=\"\" width=\"1\" height=\"1\" border=\"0\"></th>
    </tr>
    ";

    return $trhtml;
}

/**
 * Globaler Header.
 *
 * @param string $seitentitel Seitentitel
 * @param string $meta        Meta Infos
 * @param string $jsscript    Javascript
 * @param string $zusatzdaten Zusatz
 *
 * @return void
 */
function globaler_header($seitentitel = '', $meta = '', $jsscript = '', $zusatzdaten = '')
{
    global $tparse;

    $contentarray = [
        'SEITENTITEL' => $seitentitel,
        'META'        => $meta,
        'JSSCRIPT'    => $jsscript,
        'ZUSATZDATEN' => $zusatzdaten,
    ];
    $tparse->get_tpldata(ROOT_PFAD.'templates/globalheader.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Globaler Header Popup.
 *
 * @param string $seitentitel Seitentitel
 * @param string $meta        Metadaten
 * @param string $jsscript    Javascript
 * @param string $zusatzdaten Zusatz
 * @param string $bodyzusatz  Zusatz zum Body
 *
 * @return void
 */
function globaler_header_pop($seitentitel = '', $meta = '', $jsscript = '', $zusatzdaten = '', $bodyzusatz = '')
{
    global $tparse;

    $contentarray = [
        'SEITENTITEL' => $seitentitel,
        'META'        => $meta,
        'JSSCRIPT'    => $jsscript,
        'ZUSATZDATEN' => $zusatzdaten,
        'BODYZUSATZ'  => $bodyzusatz,
    ];

    $tparse->get_tpldata(ROOT_PFAD.'templates/globalheaderpopup.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Globales oberes Layout - Admin.
 *
 * @param string $navifile    Name der Navifile
 * @param string $seitentitel Seitentitel
 *
 * @return void
 */
function globallayoutoben($navifile, $seitentitel = '')
{
    global $scriptconf, $db, $tparse;
    $contentarray = [
        'SEITENTITEL'  => $seitentitel,
        'NAVIGATION'   => get_navi($navifile),
        'PROGAMMMENUE' => $tparse->get_tdata(ROOT_PFAD.'setup/progmenue.dat'),
    ];
    $tparse->get_tpldata(ROOT_PFAD.'templates/globallayoutoben.html');

    return $tparse->templateparser($contentarray);
}

/**
 * Globaler Footer.
 *
 * @param int $simple 1|2 Simpler Footer
 *
 * @return void
 */
function globaler_footer($simple = 0)
{
    if ($simple == 1) {
        return "\n</div>\n</body>\n</html>";
    } elseif ($simple == 2) {
        return "\n</body>\n</html>";
    } else {
        global $scriptconf, $tparse;
        $contentarray = [
        'VERSION' => $scriptconf['VERSION'],
        ];
        $tparse->get_tpldata(ROOT_PFAD.'templates/globalfooter.html');

        return $tparse->templateparser($contentarray);
    }
}

/**
 * Navigation.
 *
 * @param string $navifile Name der Navifile
 *
 * @return void
 */
function get_navi($navifile = '')
{
    global $tparse;
    if ($navifile != '') {
        return $tparse->get_tdata(ROOT_PFAD."templates/$navifile");
    } else {
        return '&nbsp';
    }
}

/**
 * Script oder Benutzerfehlerausgabe Adminbereich.
 *
 * @param string $fehlertitel   Titel
 * @param string $fehlermeldung Meldung
 * @param string $navdatei      Name der Navidatei
 * @param int    $backlink      0|1 Zurück-Link ja/nein
 *
 * @return void
 */
function fehlerausgabe($fehlertitel, $fehlermeldung, $navdatei, $backlink = 1)
{
    global $tparse;
    echo globaler_header('Fehler!', '', '', '');
    echo globallayoutoben('Fehler!', $navdatei);

    $backlinkcode = $backlink == 1 ? '<br><br><div align="center"><a href="javascript:history.go(-1)">Bitte zur&uuml;ckgehen und berichtigen</a></div>' : '';

    $contentarray = [
    'TEXTTOP'  => $fehlertitel,
    'TEXTCONT' => "$fehlermeldung $backlinkcode",
    ];

    $tparse->get_tpldata(ROOT_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);

    echo globaler_footer();
    exit;
}

/**
 * Script oder Benutzerfehlerausgabe Adminbereich - Popup.
 *
 * @param string $fehlertitel   Titel
 * @param string $fehlermeldung Meldung
 * @param int    $backlink      0|1 Link Ja/Nein
 *
 * @return void
 */
function fehlerausgabepop($fehlertitel, $fehlermeldung, $backlink = 1)
{
    global $tparse;
    echo globaler_header_pop('Fehler!', '', '', '', '');

    $backlinkcode = $backlink == 1 ? '<br><br><div align="center"><a href="javascript:history.go(-1)">Bitte zur&uuml;ckgehen und berichtigen</a></div>' : '';

    $contentarray = [
    'TEXTTOP'  => $fehlertitel,
    'TEXTCONT' => "$fehlermeldung $backlinkcode",
    ];

    $tparse->get_tpldata(ROOT_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);

    echo globaler_footer(2);
    exit;
}

/**
 * Redirect Routine.
 *
 * @param string $url     Weiterleitungs-Ziel
 * @param int    $wlz     Weiterleitungs-Zeit
 * @param string $infotxt Informationstext
 *
 * @return void
 */
function redirect($url, $wlz = 1, $infotxt = '')
{
    global $tparse;

    if ($wlz > 1) {
        $wlwort = "in $wlz Sekunden";
    } elseif ($wlz == 1) {
        $wlwort = 'einer Sekunde';
    } elseif ($wlz == 0) {
        $wlwort = 'sofort';
    }

    echo globaler_header('Weiterleitung', "<meta http-equiv=\"refresh\" content=\"$wlz; URL=$url\">", '', '');

    echo '<br><br><br><table cellspacing="2" cellpadding="2" border="0" width="100%"><tr><td width="20%">&nbsp;</td><td width="60%">';
    $contentarray = [
        'TEXTTOP'  => '<b>Weiterleitung</b>',
        'TEXTCONT' => $infotxt.'<br><br>Sie werden '.$wlwort.' weitergeleitet, sollte das nicht funktionieren bitte <a href="'.$url.'">hier klicken</a><br><br><br>',
    ];

    $tparse->get_tpldata(ROOT_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);
    echo '</td><td width="20%">&nbsp;</td></tr></table>';
    echo globaler_footer(1);
    exit;
}
