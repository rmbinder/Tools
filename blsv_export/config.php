<?php
/**
 ***********************************************************************************************
 * Konfigurationsdatei fuer das Admidio-Plugin blsv_export
 *
 * @copyright 2018-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

// Wenn das Plugin BLSV_Export zum Erstellen einer Datenaustauschdatei zu BLSV oder BSBnet verwendet wird,
// dann darf die Struktur der Tabelle (1. Spalte Titel, 2. Spalte Name, 3. Spalte Vorname......) nicht verändert werden. 
// Es darf nur die Zuordnung 'Rolle' => 'Spalte' über der Schlüsselwert 'rols_blsv' angepasst werden.

// Weitere Informationen zur Konfigurierung finden sich in der Dokumentation.
 
// Der Dateiname der Exportdatei (hier nur Präfix) 
$filename    = 'BLSV-Export'.'_'.date('Y-m-d');

// In einer BSBnet kompatiblen Austauschdatei (XML) kann eine Vereinsnummer übermittelt werden
// Beispiel: $verein_nummer =  '70328';
$verein_nummer =  '';

// Definition der Spalten der Exportdatei. 
// Die Struktur ist vom BLSV (Bayerischer Landes-Sportverband) vorgegebenen und darf nicht geändert werden.

$columns = array(); 

// 1. Spalte - Überschrift 'Titel' 
$columns[] = array('headline' => 'Titel');

// 2. Spalte - Überschrift 'Name'
$columns[] = array('headline' => 'Name',         'usf_uuid' => $gProfileFields->getProperty('LAST_NAME', 'usf_uuid'));

// 3. Spalte - Überschrift 'Vorname'
$columns[] = array('headline' => 'Vorname',      'usf_uuid' => $gProfileFields->getProperty('FIRST_NAME', 'usf_uuid'));

// 4. Spalte - Überschrift 'Namenszusatz'
$columns[] = array('headline' => 'Namenszusatz');

// 5. Spalte - Überschrift 'Geschlecht'
$columns[] = array('headline' => 'Geschlecht',   'usf_uuid' => $gProfileFields->getProperty('GENDER', 'usf_uuid'), 'subst' => array('Männlich' => 'm', 'Weiblich' => 'w'));

// 6. Spalte - Überschrift 'Geburtsdatum'
$columns[] = array('headline' => 'Geburtsdatum', 'usf_uuid' => $gProfileFields->getProperty('BIRTHDAY', 'usf_uuid'));

// 7. Spalte - Überschrift 'Spartennummer'
// BLSV und BSBnet: Diese Zeile ist entsprechend anzupassen: 'rols_blsv' => array('Rol-ID in Admidio' => 'Spartennummer im BLSV (bzw. Fachverband im BSB)', ...)
$columns[] = array('headline' => 'Spartennummer', 'rols_blsv' => array('135' => '09', '221' => '34'));

