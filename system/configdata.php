<?php
/**
 ***********************************************************************************************
 * Configuration data for the Admidio plugin tools
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//Standardwerte einer Neuinstallation oder beim Anfuegen einer zusaetzlichen Konfiguration       		
$config_default['settings']['subplugins'] = array();     	
      
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';
$config_default['Plugininformationen']['table_name'] = '';
$config_default['Plugininformationen']['shortcut'] = '';

//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array();

//Infos für Uninstall
$config_default['install']['access_role_id'] = 0;
$config_default['install']['menu_item_id'] = 0;

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Muessen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuaendern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Aenderung muss eine Deinstallation durchgefuehrt werden!
 *  Bereits gespeicherte Werte in der Datenbank koennen nach einer Aenderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  
