<?php
error_reporting(E_ALL & ~E_NOTICE);

if (!defined('SCRIPTSECURE')) {
    echo 'Unzul&auml;ssiger Scriptaufruf';
    exit;
}

require_once SETUP_PFAD.'setup/dbdaten.php';
define('DBSERVER', $dbdaten['server']);
define('DBDATENBANK', $dbdaten['datenbank']);
define('DBUSER', $dbdaten['user']);
define('DBPASS', $dbdaten['passwort']);
define('DBPREFIX', $dbdaten['prefix']);

/**
 * DB-Handler.
 *
 * @category Blah
 *
 * @author   Name <email@email.com>
 * @license  http://url.com MIT
 *
 * @link     http://url.com
 */
class dbhandler_mysql
{
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Variablendefinition
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

    public $affected_rows = 0;
    public $sqllink_id = 0;
    public $sqlquery_id = 0;

    /**
     * DB Daten Constructor.
     *
     * @return void
     */
    public function dbhandler_mysql()
    {
        $this->db_server = DBSERVER;
        $this->db_username = DBUSER;
        $this->db_passwort = DBPASS;
        $this->db_datenbank = DBDATENBANK;
        $this->db_prefix = DBPREFIX;
    }

    /**
     * Datenbankverbindung herstellen.
     *
     * @return void
     */
    public function db_connect()
    {
        global $scriptconf;
        $this->sqllink_id = @mysql_connect($this->db_server, $this->db_username, $this->db_passwort);

        if (!$this->sqllink_id) {
            $this->print_fatal_error('', '', 'Datenbankserver nicht erreichbar: <b>'.$this->db_server.'</b>.', '');
        }

        $db_select_ok = 0;
        if (!@mysql_select_db($this->db_datenbank, $this->sqllink_id)) {
            $db_select_ok = 0;
            $this->print_fatal_error('', '', 'Datenbank nicht vorhanden: <b>'.$this->db_datenbank.'</b>.', '');
        } else {
            $db_select_ok = 1;
        }

        /*
        if($db_select_ok && $scriptconf['DBCHARSET'] != ''){
        $this->db_query("SET NAMES '".$scriptconf['DBCHARSET']."'");
        }
        */

        $this->db_server = '';
        //$this->db_datenbank = '';
        $this->db_username = '';
        $this->db_passwort = '';
    }

    /**
     * Setupwerte holen.
     *
     * @param string $prog_id_string TODO: this
     *
     * @return void
     */
    public function get_systemdaten($prog_id_string = '')
    {
        if ($prog_id_string != '') {
            list($result, $gesamt, $alle) = $this->run_dbqueryanz('SELECT cname, cwert FROM '.$this->db_prefix.'system WHERE prog_nr IN('.$prog_id_string.')', 0);
        } else {
            list($result, $gesamt, $alle) = $this->run_dbqueryanz('SELECT cname, cwert FROM '.$this->db_prefix.'system', 0);
        }

        $setup = [];

        if ($gesamt > 0) {
            while ($row = mysql_fetch_array($result)) {
                $setup[$row['cname']] = $row['cwert'];
            }
        } else {
            $this->print_fatal_error('', '', 'Setupdaten konnten nicht gelesen werden.', '');
        }

        return $setup;
    }

    /**
     * Datenbankverbindung beenden.
     *
     * @return void
     */
    public function db_close()
    {
        if (!mysql_close()) {
            $this->print_fatal_error('', '', 'Datenbankverbindung konnte nicht geschlossen werden.', '');
        }
    }

    /**
     * Datenbankdaten escapen.
     *
     * @param string $value Text to escape
     *
     * @return void
     */
    public function quoteval($value)
    {
        if (get_magic_quotes_gpc()) {
            $value = stripslashes($value);
        }

        return mysql_real_escape_string($value);
    }

    /**
     * Datenbankquery ausfuehren.
     *
     * @param string $querysql SQL-Befehl
     *
     * @return void
     */
    public function db_query($querysql)
    {
        $this->sqlquery_id = @mysql_query($querysql, $this->sqllink_id);

        if (!$this->sqlquery_id) {
            $this->print_fatal_error(__LINE__, __FILE__, 'Kann SQL Query nicht verarbeiten.<br><b>Query:</b><br><br><code>'.$querysql.'</code> ', mysql_error());
        }

        $this->affected_rows = @mysql_affected_rows();

        return $this->sqlquery_id;
    }

    /**
     * Datenbankquerys (SELECT, einzelne Zeile als Array).
     *
     * @param int $sqlquery_id Query-Id? TODO: Beschreibung
     *
     * @return void
     */
    public function get_single_row($sqlquery_id = -1)
    {
        if ($sqlquery_id != -1) {
            $this->sqlquery_id = $sqlquery_id;
        }
        if (isset($this->sqlquery_id)) {
            $this->ergebnis = @mysql_fetch_array($this->sqlquery_id);
        } else {
            $this->print_fatal_error(__LINE__, __FILE__, 'Fehler bei der Datenbankabfrage.<br><b>Diese Query ID:</b> '.$this->sqlquery_id.' liefert keine Ergebnisse', mysql_error());
        }

        return $this->ergebnis;
    }

    /**
     * Datenbankquerys (SELECT, ID und Trefferanzahl fuer While)
     * oder
     * Datenbankquerys (SELECT, ID, SELECT FOUND_ROWS und Trefferanzahl fuer While).
     *
     * @param string $querysql SQL-Befehl
     * @param int    $foundrow TODO: Beschreibung
     *
     * @return void
     */
    public function run_dbqueryanz($querysql, $foundrow = 0)
    {
        $sqlquery_id = $this->db_query($querysql);
        $menge = mysql_num_rows($sqlquery_id);
        $anzahl_gesamt = 0;
        if ($foundrow) {
            $anzahl_gesamt = $this->found_row($sqlquery_id);
        }

        return [$sqlquery_id, $menge, $anzahl_gesamt];
    }

    /**
     * Datenbankquerys (SELECT, alle Zeilen der Ergebnismenge als Array).
     *
     * @param string $querysql SQL-Befehl
     * @param int    $foundrow TODO: Beschreibung
     *
     * @return void
     */
    public function get_all_rows($querysql, $foundrow = 0)
    {
        $sqlquery_id = $this->db_query($querysql);
        $returnarray = [];
        $start = 0;
        while ($row = $this->get_single_row($sqlquery_id, $querysql)) {
            $start++;
            $returnarray[] = $row;
        }
        $anzahl_gesamt = 0;
        if ($foundrow) {
            $anzahl_gesamt = $this->found_row();
        }
        $this->resultset_free($sqlquery_id);

        return [$returnarray, $start];
    }

    /**
     * FOUND_ROWS.
     *
     * @return void
     */
    public function found_row()
    {
        $anzahl_gesamt = 0;
        $sqlquery_id = $this->db_query('SELECT FOUND_ROWS() AS gesamttreffer');
        $gesamt = $this->get_single_row($sqlquery_id);
        $anzahl_gesamt = $gesamt['gesamttreffer'];

        return $anzahl_gesamt;
    }

    /**
     * Datenbankdaten freigeben.
     *
     * @param int $sqlquery_id TODO: Beschreibung
     *
     * @return void
     */
    public function resultset_free($sqlquery_id = -1)
    {
        if ($sqlquery_id != -1) {
            $this->sqlquery_id = $sqlquery_id;
        }

        if (!@mysql_free_result($this->sqlquery_id)) {
            $this->print_fatal_error('', '', 'Ergebnismenge der Abfrage: <b>'.$this->sqlquery_id.'</b> konnte nicht freigegeben werden.', mysql_error());
        }
    }

    /**
     * Datenbankquerys (SELECT, erste Zeile der Ergebnismenge als Array).
     *
     * @param string $query_sql SQL-Befehl
     *
     * @return void
     */
    public function dbquery_first($query_sql)
    {
        $query_id = $this->db_query($query_sql);
        $returnarray = $this->get_single_row($query_id);

        return $returnarray;
    }

    /**
     * Datenbankquerys (UPDATE).
     *
     * @param string $updatebefehl TODO: Beschreibung
     * @param [type] $dbtab        TODO: Beschreibung
     * @param [type] $sqldata      TODO: Beschreibung
     *
     * @return void
     */
    public function run_update_query($updatebefehl, $dbtab, $sqldata)
    {
        $qry = $updatebefehl.' `'.$this->db_prefix.$dbtab.'` SET '.$sqldata.';';
        if ($this->db_query($qry)) {
            return $this->affected_rows = @mysql_affected_rows();
        } else {
            return 0;
        }
    }

    /**
     * Datenbankquerys (DELETE).
     *
     * @param [type] $deletebefehl TODO: Beschreibung
     * @param [type] $dbtab        TODO: Beschreibung
     * @param [type] $sqldata      TODO: Beschreibung
     *
     * @return void
     */
    public function run_delete_query($deletebefehl, $dbtab, $sqldata)
    {
        $qry = $deletebefehl.' `'.$this->db_prefix.$dbtab.'` '.$sqldata.';';
        if ($this->db_query($qry)) {
            return $this->affected_rows = @mysql_affected_rows();
        } else {
            return 0;
        }
    }

    /**
     * Datenbankquerys (INSERT).
     *
     * @param [type] $insertbefehl TODO: Beschreibung
     * @param [type] $dbtab        TODO: Beschreibung
     * @param [type] $sqldata      TODO: Beschreibung
     *
     * @return void
     */
    public function run_insert_query($insertbefehl, $dbtab, $sqldata)
    {
        $qry = $insertbefehl.' `'.$this->db_prefix.$dbtab.'` '.$sqldata.';';
        if ($this->db_query($qry)) {
            return mysql_insert_id();
        //return $this->affected_rows = @mysql_affected_rows();
        } else {
            return 0;
        }
    }

    /**
     * Tabellen optimieren.
     *
     * @param [type] $dbtab TODO: Beschreibung
     *
     * @return void
     */
    public function optimize_table($dbtab)
    {
        $this->db_query('OPTIMIZE TABLE `'.$this->db_prefix.$dbtab.'`');
    }

    /**
     * Tabellen leeren.
     *
     * @param [type] $dbtab TODO: Beschreibung
     *
     * @return void
     */
    public function truncate_table($dbtab)
    {
        $this->db_query('TRUNCATE TABLE `'.$this->db_prefix.$dbtab.'`');
    }

    /**
     * Tabellen sperren.
     *
     * @param [type] $dbtab TODO: Beschreibung
     *
     * @return void
     */
    public function lock_table($dbtab)
    {
        $this->db_query('LOCK TABLES `'.$this->db_prefix.$dbtab.'` WRITE');
    }

    /**
     * Tabellen entsperren.
     *
     * @return void
     */
    public function unlock_table()
    {
        $this->db_query('UNLOCK TABLES');
    }

    /**
     * Datenbankquerys (SELECT, einzelne Zeile als Array).
     *
     * @param int $sqlquery_id TODO: Beschreibung
     *
     * @return void
     */
    public function fetch_fieldnamen($sqlquery_id = -1)
    {
        if ($sqlquery_id != -1) {
            $this->sqlquery_id = $sqlquery_id;
        }
        if (isset($this->sqlquery_id)) {
            $this->fetchergebnis = @mysql_fetch_field($this->sqlquery_id);
        } else {
            $this->print_fatal_error(__LINE__, __FILE__, 'Fehler bei der Datenbankabfrage.<br><b>Diese Query ID:</b> '.$this->sqlquery_id.' liefert keine Ergebnisse', mysql_error());
        }

        return $this->fetchergebnis;
    }

    /**
     * Datenbankquerys (SELECT, einzelne Zeile als Array) mysql_fetch_row.
     *
     * @param int $sqlquery_id TODO: Beschreibung
     *
     * @return void
     */
    public function fetch_single_row($sqlquery_id = -1)
    {
        if ($sqlquery_id != -1) {
            $this->sqlquery_id = $sqlquery_id;
        }

        if (isset($this->sqlquery_id)) {
            $this->rowergebnis = @mysql_fetch_row($this->sqlquery_id);
        } else {
            $this->print_fatal_error(__LINE__, __FILE__, 'Fehler bei der Datenbankabfrage.<br><b>Diese Query ID:</b> '.$this->sqlquery_id.' liefert keine Ergebnisse', mysql_error());
        }

        return $this->rowergebnis;
    }

    /**
     * Datumsformatquerys.
     *
     * @param [type] $datefeldname   TODO: Beschreibung
     * @param [type] $datumsetupwert TODO: Beschreibung
     * @param int    $hms            TODO: Beschreibung
     *
     * @return void
     */
    public function datumsformate($datefeldname, $datumsetupwert, $hms = 1)
    {
        if ($hms == 1) {
            if ($datumsetupwert == 1 && $hms == 1) {
                $dq = "DATE_FORMAT($datefeldname, '%d.%m.%Y')";
            } elseif ($datumsetupwert == 2) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( ELT( WEEKDAY($datefeldname)+1, 'Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'),', %e. ', ELT( MONTH($datefeldname), 'Januar','Februar','M&auml;rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),' %Y'))";
            } elseif ($datumsetupwert == 3) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( ELT( WEEKDAY($datefeldname)+1, 'Mo','Di','Mi','Do','Fr','Sa','So'),', %e. ', ELT( MONTH($datefeldname), 'Jan.','Feb.','Mrz.','Apr.','Mai','Jun.','Jul.','Aug.','Sep.','Okt.','Nov.','Dez.'),' %Y'))";
            } elseif ($datumsetupwert == 4) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( '%e. ', ELT( MONTH($datefeldname), 'Jan.','Feb.','Mrz.','Apr.','Mai','Jun.','Jul.','Aug.','Sep.','Okt.','Nov.','Dez.'),' %Y'))";
            } elseif ($datumsetupwert == 5) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( '%e. ', ELT( MONTH($datefeldname), 'Januar','Februar','M�rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),' %Y'))";
            } elseif ($datumsetupwert == 6) {
                $dq = "DATE_FORMAT($datefeldname, '%Y-%m-%d')";
            }
        } elseif ($hms == 2) {
            if ($datumsetupwert == 1) {
                $dq = "DATE_FORMAT($datefeldname, '%d.%m.%Y um %T')";
            } elseif ($datumsetupwert == 2) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( ELT( WEEKDAY($datefeldname)+1, 'Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'),', %e. ', ELT( MONTH($datefeldname), 'Januar','Februar','M&auml;rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),' %Y um %T'))";
            } elseif ($datumsetupwert == 3) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( ELT( WEEKDAY($datefeldname)+1, 'Mo','Di','Mi','Do','Fr','Sa','So'),', %e. ', ELT( MONTH($datefeldname), 'Jan.','Feb.','Mrz.','Apr.','Mai','Jun.','Jul.','Aug.','Sep.','Okt.','Nov.','Dez.'),' %Y um %T'))";
            } elseif ($datumsetupwert == 4) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( '%e. ', ELT( MONTH($datefeldname), 'Jan.','Feb.','Mrz.','Apr.','Mai','Jun.','Jul.','Aug.','Sep.','Okt.','Nov.','Dez.'),' %Y um %T'))";
            } elseif ($datumsetupwert == 5) {
                $dq = "DATE_FORMAT($datefeldname, CONCAT( '%e. ', ELT( MONTH($datefeldname), 'Januar','Februar','M�rz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'),' %Y um %T'))";
            } elseif ($datumsetupwert == 6) {
                $dq = "DATE_FORMAT($datefeldname, '%Y-%m-%d um %T')";
            }
        }

        return $dq;
    }

    /**
     * Script Fehlermeldungen aller Art ausgeben.
     *
     * @param string $lineinfo         TODO: Beschreibung
     * @param string $fileinfo         TODO: Beschreibung
     * @param string $fehlertext       TODO: Beschreibung
     * @param string $mysql_error_info TODO: Beschreibung
     *
     * @return void
     */
    public function print_fatal_error($lineinfo = '', $fileinfo = '', $fehlertext = '', $mysql_error_info = '')
    {
        $fehlerinfo = '';
        if ($lineinfo != '' && $fileinfo != '') {
            $fehlerinfo .= '<b>Fehler in Zeile '.$lineinfo.' im Script '.$fileinfo."</b><br><br>\n\n";
        }
        $fehlerinfo .= $fehlertext."<br><br>\n\n";

        if ($mysql_error_info != '') {
            $fehlerinfo .= '<b>MySQL-Error:</b><br>'.$mysql_error_info;
        } ?>
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
        <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
        <title>Fehler festgestellt!</title>
        <style type="text/css">
        body, html { background-color: #F5F6F8; font-family: Verdana, Arial, sans-serif; font-size: 12px; color: #000000; margin: 0 0 5px 0; padding: 0;}
        #ausgabefehler { margin:10px auto; padding: 8px; text-align:left; width: 600px; border: 1px solid #000000; background-color: #ffffff; }
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
}
?>