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
define('WEB_PFAD', './');
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Globaler Header
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function globaler_header($seitentitel = '', $meta = '', $jsscript = '')
{
    global $scriptconf, $tparse;

    $contentarray = [
'SEITENTITEL' 	=> $seitentitel,
'META' 			     => $meta,
'JSSCRIPT' 		  => $jsscript,
];

    $tparse->get_tpldata(WEB_PFAD.'templates/global_header.html');

    return $tparse->templateparser($contentarray);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Globales oberes Layout
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function globallayoutoben($seitentitel = '')
{
    global $scriptconf, $tparse;

    $contentarray = [
'SEITENTITEL' 	=> $seitentitel,
];

    $tparse->get_tpldata(WEB_PFAD.'templates/global_layoutoben.html');

    return $tparse->templateparser($contentarray);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Globaler Footer
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function globaler_footer($simple = '')
{
    global $scriptconf, $tparse;
    //################################
    if ($simple == 1) {
        $tparse->get_tdata(WEB_PFAD.'templates/global_footereinfach.html');
    //################################
    } else {
        $tparse->get_tdata(WEB_PFAD.'templates/global_footer.html');
    }
    //################################
    return $tparse->templateparser($contentarray);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Script oder Benutzerfehlerausgabe Userbereich
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function fehlerausgabe($fehlertitel, $fehlermeldung, $backlink = 1)
{
    global $tparse;
    echo globaler_header($fehlertitel, '', '', '');
    echo globallayoutoben($fehlertitel);

    $backlinkcode = $backlink == 1 ? '<br><br><div align="center"><a href="javascript:history.go(-1)">Bitte zur&uuml;ckgehen und berichtigen</a></div>' : '';

    $contentarray = [
'TEXTTOP' 	 => $fehlertitel,
'TEXTCONT' 	=> $fehlermeldung.$backlinkcode,
];

    $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);

    echo globaler_footer();
    exit;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Redirect Routine
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
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

    echo globaler_header('Weiterleitung', '<meta http-equiv="refresh" content="'.$wlz.'; URL='.$url.'">', '', '');

    echo '<br><br><br><table cellspacing="2" cellpadding="2" border="0" width="100%"><tr><td width="20%">&nbsp;</td><td width="60%">';
    $contentarray = [
'TEXTTOP' 	 => '<b>Weiterleitung</b>',
'TEXTCONT' 	=> $infotxt.'<br><br>Sie werden '.$wlwort.' weitergeleitet, sollte das nicht funktionieren bitte <a href="'.$url.'">hier klicken</a><br><br><br>',
];

    $tparse->get_tpldata(WEB_PFAD.'templates/textausgaben.html');
    echo $tparse->templateparser($contentarray);
    echo '</td><td width="20%">&nbsp;</td></tr></table>';
    echo globaler_footer(1);
    exit;
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
