<?php
error_reporting(E_ALL & ~E_NOTICE);

if (!defined('SCRIPTSECURE')) {
    echo 'Unzul&auml;ssiger Scriptaufruf';
    exit;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Popuptemplatejavascript
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function popupjs($pb, $ph)
{
    $popupjs = "<script language=\"JavaScript\" type=\"text/javascript\">
    <!--
    function auf(url) {
    windowName = \"content\";
    options  = \"\";
    options += \"toolbar=0,\";
    options += \"location=0,\";
    options += \"directories=0,\";
    options += \"status=0,\";
    options += \"menubar=0,\";
    options += \"scrollbars=1,\";
    options += \"resizable=1,\";
    options += \"width=".$pb.",\";
    options += \"height=".$ph.",\";
    options += \"dependent=1\";
    win = window.open(url, windowName , options);
    if (!win.opener) {
    win.opener = window;
    }
    };
    //-->
    </script>";

    return $popupjs;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Strings mit , und "und" korrekt ausgeben
// Aufruf mit $wertestring und Trennzeichen
// echo und_string("gif|jpeg|png|bmp", '|');
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function und_string($werte, $trennzeichen)
{
    $wertearray = explode($trennzeichen, $werte);
    $anzahl = count($wertearray);

    if ($anzahl == 1) {
        $und_string = $wertearray[0];
    } elseif ($anzahl == 2) {
        $und_string = implode(' und ', $wertearray);
    } elseif ($anzahl > 2) {
        $letztes = array_pop($wertearray); 
        $und_string = implode(', ', $wertearray) .' und ' . $letztes;
    }
    
    return $und_string;
}


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Auswahlfeld Selectform erstellen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function make_selectform($conf_wert, $optionsvalues, $optionstexte)
{
    $optionsvalarray = explode('||', $optionsvalues);
    $optionstxtarray = explode('||', $optionstexte);

    $genselect = '';
    for ($i = 0; $i < count($optionsvalarray); $i++) {
        $selected = $optionsvalarray[$i] == $conf_wert ? ' selected="selected"' : '';
        $genselect .= "\t<option value=\"$optionsvalarray[$i]\"$selected>$optionstxtarray[$i]</option>\n";
    }

    return $genselect;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Usercookie fuer Admin holen und testen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function passdatencheck()
{
    global $scriptconf, $db;

    $adusercook = isset($_COOKIE[$scriptconf['COOKPREFIX'].'admin']) && $_COOKIE[$scriptconf['COOKPREFIX'].'admin'] != '' ? $_COOKIE[$scriptconf['COOKPREFIX'].'admin'] : '';

    $un = '';
    $up = '';

    if ($adusercook != '') {
        $entpackt = pack("H*", $adusercook);
        $userarray = explode('#', $entpackt);
        $un         = datensaver($userarray[0], 50, 5);
        $up         = datensaver($userarray[1], 33, 6);
    }
    $gefunden = 0;
    if ($un != '' && $up != '') { 
        $memdatacount = $db->dbquery_first("SELECT COUNT(*) AS anzahl FROM ".$db->db_prefix."admin WHERE BINARY(username) = '".$un."' AND passwort = '".$up."'");
        $gefunden = $memdatacount['anzahl'] != '' ? 1 : 0;
    }

    if (!$gefunden) {
        redirect($scriptconf['ADMINURL'] .'/admin.php?err=3', 0, '');
        exit;
    } 
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Mail senden
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function textmail($empfaenger_name_email, $absender_name_email, $betreff, $messagetext, $empfaengeremail, $absenderemail)
{
    $empfaenger_name_email     = datensaver($empfaenger_name_email, 200, 8);
    $absender_name_email     = datensaver($absender_name_email, 200, 8);
    $betreff                 = datensaver($betreff, 150, 8);
    $messagetext             = datensaver($messagetext, 60000, 7);
    $empfaengeremail         = datensaver($empfaengeremail, 100, 8);
    $absenderemail             = datensaver($absenderemail, 100, 8);


    $headers = '';
    $headers .= 'From:    '.$absender_name_email."\n";
    $headers .= 'Reply-To: '.$absender_name_email."\n"; 
    $headers .= 'X-Mailer: PHP'."\n";
    $headers .= 'X-Sender: '.$absenderemail."\n";
    $headers .= "Content-type: text/plain; charset=\"ISO-8859-1\"\n";

    // Versenden der Mail
    mail($empfaenger_name_email, $betreff, $messagetext, $headers);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Setupdaten neu schreiben
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function rewrite_setup()
{ 
    global $db;

    list($result, $gesamt, $alle) = $db->run_dbqueryanz("SELECT cname, cwert, art FROM ".$db->db_prefix."system", 0);

    $setupvars = '';

    if ($gesamt > 0) {
        $start = 0;
        while ($row = $db->get_single_row($result)) {
            $start++;
            $komma = $start < $gesamt ? ',' : '';
            $setupvars .= '// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //'."\n";
            if ($row['art'] == 'S') {
                $setupvars .= "'".$row['cname']."' => '".$row['cwert']."'".$komma."\n";
            } elseif ($row['art'] == 'T') {
                $setupvars .= "'".$row['cname']. "' => '".addslashes($row['cwert'])."'".$komma."\n";
            } elseif ($row['art'] == 'Z') {
                $setupvars .= "'".$row['cname']. "' => '".$row['cwert']."'".$komma."\n";
            } 
        }

        $fp = fopen("setup/setup.php", "w") or fehlerausgabe('Fehler', '<li>Kann Datei setup/setup.php nicht oeffnen', 1);
        fputs($fp, "<?php\n");
        fputs($fp, "// Zugriff ueber \$scriptconf['varname']\n");
        fputs($fp, "\$scriptconf = array(\n");
        fputs($fp, $setupvars);
        fputs($fp, '// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //'."\n");
        fputs($fp, ");\n");
        fputs($fp, "?>\n");
        fclose($fp);
        @chmod("setup/setup.php", 0777);
    }
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Templateclass
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
/**
/* Aufruf:
/* $tparse = new template();
/* Template holen
/* $tparse->get_tpldata("template.html");
/* Templatevars parsen
/* $tparse->templateparser($content);
/* oder nur Template holen ohne Templatevars
/* $tparse->get_tdata("$pfad/template.html");
*/
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
class template {
    /**
     * Templatedaten holen fuer Templates ohne Ersatzvariablen
     */
    function get_tdata($tmplname, $no_replace = 0)
    {
        if (file_exists($tmplname)) {
            $this->template = implode('', file($tmplname));
            if ($no_replace) {
                if (strstr($this->template, '<%%')) {
                    $this->entferne_platzhalter();
                }
            }
            return $this->template;
        } else {
            print_script_error(__LINE__, __FILE__, 'Fehler!, Die Datei: '.$tmplname.' kann nicht ge&ouml;ffnet werden', '');
            exit;
        }
    }

    /**
     * Template als String fuer Parser holen
     */
    function get_tpldata($templatename)
    {

        if (file_exists($templatename)) {
            $this->template = implode('',file($templatename));
        } else {
            print_script_error(__LINE__,__FILE__,'Fehler!, Die Datei: '.$templatename.' kann nicht ge&ouml;ffnet werden', '');
            exit;
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Templateparser
    // $wertearray = Zu ersetztende Platzhalterdaten
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    function templateparser($wertearray) { 

    if(is_array($wertearray)) {
    foreach($wertearray as $key => $value) { 
    $this->template = str_replace('<%%'.strtoupper($key).'%%>', $value, $this->template);
    } 
    }

    // Nicht ersetzte Platzhalter aus Template entfernen
    if (strstr($this->template, '<%%')) {
    $this->entferne_platzhalter();
    }

    return $this->template; 
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Nicht ersetzte Platzhalter aus Template entfernen
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    function entferne_platzhalter() { 
    $this->template = preg_replace("/((<%%)(.+?)(%%>))/si", '', $this->template);
    }
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
} // Ende Templateclass
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Formulardaten in einzeiligen Textfeldern, Checkboxen, 
// Radiobuttons und Auswahlfeldern entschaerfen
// Aufruf:
// $value = datensaver($mustertext, 400, 2);
// $formdaten     = Formulardaten
// $maximal        = Maximale Eingabelaenge, bei Ueberschreitung kuerzen 
// $type        = Numerischer Wert fuer Regexanweisung, wenn leer wird 1 angewendet
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function datensaver($formdaten = '', $maximal = 255, $regextype = 1) {

if(isset($maximal)) {
$maximal = preg_replace ("/[^0-9]/", '',  $maximal);
}
if ($maximal == '') {
$maximal = 255;
}

if (get_magic_quotes_gpc()) {
$formdaten = stripslashes($formdaten);
}

// Einzeilige Inputfelder die vorwiegend normalen Text ohne Sonderzeichen enthalten sollen
if ($regextype == 1) {
$suche_nach = array ('/</', '/>/', '/\r/', '/\n/', '/\015\012|\015|\012/', '/"/', '/\'/', '/\|/', '/`/', '/\\\/');                    
$ersetze_mit = array ('&lt;', '&gt;', '', ' ', ' ', '&quot;', '&#39;', '&#124;', '&#96;', '&#92;');
}

// Mehrzeilige Inputfelder, Sonderzeichen erlaubt, diese werden aber umgewandelt
elseif ($regextype == 2) {


$formdaten = preg_replace('/&(?#[0-9]+|[a-z]+;)/', "&amp;$1", $formdaten);
$suche_nach = array ('/</', '/>/' ,'/\r/', '/"/', '/\'/', '/\|/', '/`/', '/\\\/', '/--/');                    
$ersetze_mit = array ('&lt;', '&gt;', '', '&quot;', '&#39;', '&#124;', '&#96;', '&#92;', '&#45;&#45;');
$formdaten = str_replace('$', '&#36;', $formdaten);
} 

// nur Zahlen erlauben
elseif ($regextype == 3) {
$suche_nach = '/[^0-9]/';                    
$ersetze_mit = '';
}

// nur Buchstaben, Zahlen, Bindestrich, Punkt, Slash und Leerzeichen erlauben
elseif ($regextype == 4) {
$suche_nach = '/[^0-9a-zA-Z�-��-��,\.\-_ \/]/';                     
$ersetze_mit = '';
}

// nur Buchstaben und Zahlen mit Umlauten
elseif ($regextype == 5) {
$suche_nach = '/[^0-9a-zA-Z�-��-��]/';                    
$ersetze_mit = '';
}

// nur Buchstaben und Zahlen, ohne Umlaute und Unterstrich
elseif ($regextype == 6) {
$suche_nach = '/[^0-9a-zA-Z_]/';                    
$ersetze_mit = '';
}

// Mehrzeilige Inputfelder, (Mailtext)
elseif ($regextype == 7) {
$suche_nach = array ('/\r/');                    
$ersetze_mit = array ('');
} 

// Einzeilige Inputfelder, bereinigt von allen Zeilenumbruechen
elseif ($regextype == 8) {
$suche_nach = array ('/\r/', '/\n/', '/\015\012|\015|\012/');                    
$ersetze_mit = array ('', '', '');
}

// Emailtester
elseif ($regextype == 9) {
trim($formdaten);

if (!preg_match ("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/",  $formdaten)) {
$formdaten = 'nm';
} else {
$formdaten = strtolower($formdaten);
}

}

// Nur Text, kein HTML
elseif ($regextype == 10) {
$suche_nach = array ('/"/', '/\'/', '/[^0-9a-zA-Z�-��-��\-_ \.\!\?,;:&=�%()@+# \n]/');                    
$ersetze_mit = array ('&quot;', '&#39;', '');
}


// #########################################################

if ($regextype != 9) {
$formdaten = preg_replace($suche_nach, $ersetze_mit, trim($formdaten));
}

if (get_magic_quotes_gpc()) {
$formdaten = stripslashes($formdaten);
}

if (strlen($formdaten) > $maximal) {
$formdaten = substr($formdaten, 0, $maximal);
}

return $formdaten;
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Preisangaben mit 2 nachkommastellen und . statt Komma aufbereiten
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function format_preis($preis = '', $max = 10) {

if (strlen($preis) > $max) {
$preis = substr($preis, 0, $max);
}

$preis             = preg_replace('/[^0-9,\.]/', '', trim($preis));
$preis             = preg_replace('/(,)/', '.', trim($preis));
$preis             = sprintf("%.2f", $preis);
return $preis;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Texte fuer Mails aufbereiten
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function printouttext($text) {

$suche_nach = array ('&lt;', '&gt;', '&#124;', '&#39;', '&quot;', '&#96;', '&#45;&#45;');
$ersetze_mit = array ('<', '>', '\|', '\'', '"', '`', '--');

$returntext = str_replace($suche_nach, $ersetze_mit, $text);
return $returntext; 
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - #
// URL Detektor
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - #
function urlcounter($text = '') {

// Regex zum auffinden von URL's in Daten
preg_match_all('/((http|https|ftp)\:\/\/([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)?((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.[a-zA-Z]{2,4})(\:[0-9]+)?(\/[a-zA-Z0-9\.\,\?\'\\/\+&amp;%\$#\=~_\-@]*)*)/', $text, $treffer, PREG_SET_ORDER);

return count($treffer);
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Userip holen
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function get_uip() {

if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), 'unknown')) {          
    $uip = getenv("HTTP_CLIENT_IP");       
} elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), 'unknown')) {          
    $uip = getenv("HTTP_X_FORWARDED_FOR");       
} elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), 'unknown')) {           
    $uip = getenv("REMOTE_ADDR");       
} elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {          
    $uip = $_SERVER['REMOTE_ADDR'];      
} else {           
    $uip = 'unknown';   
}

return datensaver($uip, 25, 4);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Zufallskey erzeugen von unterschiedlicher laenge (x-y Zeichen)
// Aufruf: $key = multirandom($art, $von, $bis, $prefix );
// Parameter:
// $art = 1, 2, 3 oder 4  == Buchstaben/Zahlen - GROSSBUCHSTABEN - nur Zahlen - uniqid mit MD5
// $von - bis == Lange der Zeichenkette
// $prefix == Vorangestellter Prefix, z.B. Userkennung (12_$key) OPTIONAL
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function multirandom($art = 1, $von = 5, $bis = 10, $prefix = '') {

mt_srand(crc32(microtime()));

if ($bis AND $bis <= mt_getrandmax()){
$keyrandlength = mt_rand($von,$bis);
} else {
$keyrandlength = mt_rand();
}
mt_srand();

$keyarrayall         = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','Y','Z','a','b','c','d','e','f','g','h','j','k','m','n','p','q','r','s','t','u','v','w','y','z','1','2','3','4','5','6','7','8','9');
$keyarraycha         = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','Y','Z'); 
$keyarraynum         = array('1','2','3','4','5','6','7','8','9'); 

// Welches Array von Zeichen nutzen? 
// 1 = Buchstaben und Zahlen
// 2 = Nur Grossbuchstaben
// 3 = Nur Zahlen
// 4 = uniqid mit MD5
$keydata = '';
if($art == 1) {
$usedarray = $keyarrayall;
} elseif ($art == 2) {
$usedarray = $keyarraycha;
} elseif ($art == 3) {
$usedarray = $keyarraynum;
} elseif ($art == 4) {
$keydata = md5(uniqid('', true));
}

if ($art < 4) {
// Zufallszeichen ermitteln
for($i = 0; $i < $keyrandlength; $i++) { 
shuffle($usedarray);
$keydata .= $usedarray[1];    
}
}

// Prefix voranstellen?
$returnkey = $prefix != '' ? $prefix . $keydata : $keydata;

return $returnkey;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Datumsausgabe
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function datumausgabe($did){

$zeit = time();
$datum = getdate($zeit);

$tage = array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
$tagkurz = array('So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa');
$monate = array('Januar','Februar','M&auml;rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember');
$monatkurz = array('Jan.','Feb.','M&auml;rz','Apr.','Mai','Jun.','Jul.','Aug.','Sep.','Okt.','Nov.','Dez.');

$datum = array (
"0" => "$datum[seconds]",
"1" => "$datum[minutes]",
"2" => "$datum[hours]",
"3" => "$datum[mday]",
"4" => "$datum[wday]",
"5" => "$datum[mon]",
"6" => "$datum[year]",
"7" => "$datum[yday]",
"8" => "$datum[weekday]",
"9" => "$datum[month]"
);

$uhr_stunde = $datum[2];
$uhr_min = $datum[1];
$uhr_sec = $datum[0];

// UHRZEIT /////////////////////////////
if($uhr_stunde < 10) { $uhr_stunde = "0$uhr_stunde"; }
if ($uhr_min < 10) { $uhr_min = "0$uhr_min"; }
if ($uhr_sec < 10) { $uhr_sec = "0$uhr_sec"; }
$uhrzeit = "$uhr_stunde:$uhr_min:$uhr_sec";
$uhrzeitdate = "$uhr_stunde$uhr_min$uhr_sec";
// UHRZEIT /////////////////////////////

// Wochentag
$ar_wotag = $datum[4];
// Wochentag kurz
$ar_wotagk = $datum[4];
// Monatlang
$ar_monat = $datum[5];
// Monatkurz
$ar_monatkurz = $datum[5];
if ($datum[5] < 10) { $datum[5] = "0$datum[5]"; }
if ($datum[3] < 10) { $datum[3] = "0$datum[3]"; }

$wochentag = $tage[$ar_wotag];
$wochentagkurz = $tagkurz[$ar_wotagk];
$monat = $monate[$ar_monat-1];
$monatk = $monatkurz[$ar_monatkurz-1];

if ($did == '1') {
//Beispiel:  Donnerstag, 30. Oktober 2003 um 15:07:18 Uhr
$datumsoutput = "$wochentag, $datum[3]. $monat $datum[6] um $uhrzeit Uhr";

} elseif ($did == '2') {
//Beispiel: Donnerstag 30. Oktober 2003
$datumsoutput = "$wochentag $datum[3]. $monat $datum[6]";

} elseif ($did == '3') {
//Beispiel: Do 30. Okt. 2003
$datumsoutput = "$wochentagkurz $datum[3]. $monatk $datum[6]";

} elseif ($did == '4') {
//Beispiel: 30.10.2003
$datumsoutput = "$datum[3].$datum[5].$datum[6]";

} elseif ($did == '5') {
//Beispiel: 15:07:18
$datumsoutput = $uhrzeit;

} elseif ($did == '6') {
//Beispiel: 2452943


if ($datum[5] > 2) {
$datum[5] = $datum[5] - 3;
} else {
$datum[5] = $datum[5] + 9;
$datum[6] = $datum[6] - 1;
}
$c = floor($datum[6] / 100);
$ya = $datum[6] - (100 * $c);
$j = floor((146097 * $c) / 4);
$j += floor((1461 * $ya)/4);
$j += floor(((153 * $datum[5]) + 2) / 5);
$j += $datum[3] + 1721119;

$datumsoutput = $j;

} elseif ($did == '7') {
//Beispiel: 1181566286
$datumsoutput = time();

} elseif ($did == '8') {
//Beispiel: 2007_06_26_10_101245
$datumsoutput = "$datum[6]_$datum[5]_$datum[3]_$uhrzeitdate";
} 

elseif ($did == '9') {
//Beispiel: 2007062610101245
$datumsoutput = "$datum[6]$datum[5]$datum[3]$uhrzeitdate";
}

elseif ($did == '10') {
//Beispiel: 2007-06-26
$datumsoutput = "$datum[6]-$datum[5]-$datum[3]";
}

return $datumsoutput;
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Dateigroesse
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function dateigroesse($groesse) {
settype ($groesse, 'integer');

if ($groesse) {

    if($groesse > 1099511627776){
    return number_format($groesse/1099511627776, 2, ",", ".")." TB";
    }
    
    elseif ($groesse > 1073741824){
    return number_format($groesse/1073741824, 2, ",", ".")." GB";
    }
    
    elseif ($groesse > 1048576){
    return number_format($groesse/1048576, 2, ",", ".")." MB";
    }
    
    elseif ($groesse >= 1024){
    return number_format($groesse/1024, 0, ",", ".")." KB";
    }
    
    else {
    return $groesse ." Bytes";
    }

} else {
return "Nicht ermittelbar";
}

}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - #
// Seitennavigation
// # Limit fuer Query erstellen - Eintraege pro Seite 
// $anz = ($seite-1) * $daten_per_site;
// $navigationslinks = pager($zeilen, $seite, $daten_per_site, $url, $show_pageinfo);
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - #
function pager($zeilen, $seite, $pro_seite, $url, $show_pageinfo = 1, $linkanzahl = 2, $show_pageselect = 0) {

$max_ausgabe = $pro_seite;
$gesamtseiten = floor(($zeilen - 1) / $pro_seite+1);

$aktuelle_seite = $seite ? $seite : 1;
$linkanzahlausgabe = $linkanzahl;

$letzte = $linkanzahlausgabe + $aktuelle_seite;
if ($letzte > $gesamtseiten) {
$letzte = $gesamtseiten;
}

$startback = $aktuelle_seite - $linkanzahlausgabe;
if ($startback < 1) {
$startback = 1;
}

$navigationslinks = "&nbsp;";
if ($gesamtseiten != 1 && $zeilen) {
$seitenlink = "";

if ($gesamtseiten > 1) {
$prevbl = $aktuelle_seite > 1 ? $aktuelle_seite - 1 : 1;
$nohrefpr = $aktuelle_seite == 1 ? 'no' : '';
$seitenlink .=  "\t<td class=\"pl\"><a ".$nohrefpr."href=\"".$url."=1\" title=\"Erste Seite aufrufen\">&#171; &#171;</a></td>\n\t<td class=\"pl\"><a ".$nohrefpr."href=\"".$url."=".$prevbl."\" title=\"Eine Seite zur�ck\">&#171;</a></td>\n";
}

for ($i = $startback; $i <= $letzte; $i++) {
if ($aktuelle_seite == "$i") {
$seitenlink .= "\t<td class=\"aktuelleseite\">".$i."</td>\n";
} else {
$seitenlink .= "\t<td class=\"pl\"><a href=\"".$url."=".$i."\">$i</a></td>\n";
}
}

#if ($letzte < $gesamtseiten) {
$nextbl = $aktuelle_seite < $letzte ? $aktuelle_seite + 1 : $letzte;
$nohref = $aktuelle_seite == $letzte ? 'no' : '';
$seitenlink .= "\t<td class=\"pl\"><a ".$nohref."href=\"".$url."=".$nextbl."\" title=\"Eine Seite weiter\">&#187;</a></td>\n\t<td class=\"pl\"><a ".$nohref."href=\"".$url."=".$gesamtseiten."\" title=\"Letzte Seite aufrufen\">&#187; &#187;</a></td>";
#}

if ($show_pageinfo == 1) { 
$pageinfo = "\t<td class=\"seiteninfo\">Seite: ".$aktuelle_seite." von ".$gesamtseiten."</td>\n";
} else {
$pageinfo = '';
}

$navigationslinks = "\n<table cellspacing=\"1\" cellpadding=\"0\" border=\"0\" class=\"sitenav\">\n<tr>".$pageinfo . $seitenlink ."</tr>\n</table>\n";
}

return $navigationslinks;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
// Script Fehlermeldungen aller Art ausgeben 
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
function print_script_error($lineinfo = '', $fileinfo = '', $fehlertext = '', $mysql_error_info = '') {

$fehlerinfo = '';
if ($lineinfo != '' && $fileinfo != '') {
$fehlerinfo .= "<b>Fehler in Zeile ".$lineinfo." im Script ".$fileinfo."</b><br><br>\n\n";
}
$fehlerinfo .= $fehlertext . "<br><br>\n\n";

if ($mysql_error_info != '') {
$fehlerinfo .= "<b>MySQL-Error:</b><br>".$mysql_error_info;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
<title>Fehler festgestellt!</title>
<style type="text/css">
body, html { background-color: #F5F6F8; font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #000000; margin: 0 0 5px 0; padding: 0;}
#ausgabefehler { margin:70px auto; padding: 8px; text-align:left; width: 600px; border: 1px solid #000000; background-color: #ffffff; }
.err { background-color: #000000; }
.errtop { background-color: #ffcc00; font-size: 12px; color: #000000; padding: 4px; }
.errcont { background-color: #ffffff; font-size: 12px; color: #000000; padding: 4px; }
.errcontbot { background-color: #f9f3df; font-size: 12px; color: #000000; padding: 4px; }
</style>

</head>
<body>

<div id="ausgabefehler">
<table cellspacing="1" cellpadding="0" border="0" width="100%" class="err">
<tr>
    <td class="errtop"><b>FEHLER FESTGESTELLT!</b></td>
</tr>
<tr>
    <td class="errcont"><?php echo $fehlerinfo; ?>&nbsp;</td>
</tr>
<tr>
    <td class="errcontbot"><b>Wichtig!</b><br><br>Geben Sie bitte bei Problemen die komplette Fehlerausgabe an den Support weiter!</td>
</tr>
</table>
</div>
</body>
</html>    
<?php
exit;    
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
?>