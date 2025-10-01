<?php
/**
 ***********************************************************************************************
 * Common functions for the admidio plugin Tools
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
 
use Admidio\Roles\Entity\RolesRights;

require_once(__DIR__ . '/../../../system/common.php');

$folders = explode('/', $_SERVER['SCRIPT_FILENAME']);
while (array_search(substr(FOLDER_PLUGINS, 1), $folders))
{
    array_shift($folders);
}
array_shift($folders);

if(!defined('PLUGIN_FOLDER'))
{
    define('PLUGIN_FOLDER', '/'.$folders[0]);
}

if (!defined('PLUGIN_SUBFOLDER') && is_file(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER .'/'.$folders[1].'/'.$folders[1].'.php') )
{
    define('PLUGIN_SUBFOLDER', '/'.$folders[1]);
}
unset($folders);

spl_autoload_register('myAutoloader');

/**
 * Mein Autoloader
 * Script aus dem Netz
 * https://www.marcosimbuerger.ch/tech-blog/php-autoloader.html
 * @param   string  $className   Die übergebene Klasse
 * @return  string  Der überprüfte Klassenname
 */
function myAutoloader($className) {
    // Projekt spezifischer Namespace-Prefix.
    $prefix = 'Plugins\\';
    
    // Base-Directory für den Namespace-Prefix.
    $baseDir = __DIR__ . '/../../';
    
    // Check, ob die Klasse den Namespace-Prefix verwendet.
    $len = strlen($prefix);
    
    if (strncmp($prefix, $className, $len) !== 0) {
        // Wenn der Namespace-Prefix nicht verwendet wird, wird abgebrochen.
        return;
    }
    // Den relativen Klassennamen ermitteln.
    $relativeClassName = substr($className, $len);
    
    // Den Namespace-Präfix mit dem Base-Directory ergänzen,
    // Namespace-Trennzeichen durch Verzeichnis-Trennzeichen im relativen Klassennamen ersetzen,
    // .php anhängen.
    $file = $baseDir . str_replace('\\', '/', $relativeClassName) . '.php';
    // Pfad zur Klassen-Datei zurückgeben.
    if (file_exists($file)) {
        require $file;
    }
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * 
 * In Admidio im Modul Menü kann über 'Sichtbar für' die Sichtbarkeit eines Menüpunkts eingeschränkt werden.
 * Der Zugriff auf die darunter liegende Seite ist von dieser Berechtigung jedoch nicht betroffen.
 * 
 * Diese Funktion liest die unter 'Sichtbar für' eingetragenen Rollen ein 
 * und prüft, ob der angemeldete Benutzer Mitglied einer dieser Rollen ist
 * Wenn ja, ist der Benutzer berechtigt, das Plugin aufzurufen
 * Wichtiger Hinweis: Sind unter 'Sichtbar für' keine Rollen angegeben, so darf jeder Benutzer das Plugin ausführen
 * 
 * @param   string  $scriptName   Der Scriptname des Plugins (default: 'index.php')
 * @param   bool  $subplugin   Wenn true, dann wird die Prüfung für ein Sub-Plugin (derzeit nur Plugin Tools) durchgeführt
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized( string $scriptname = '',  bool $subplugin = false)
{
    global $gDb, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menIds = array();
    
    if (strlen($scriptname) !== 0)
    {
        $scriptname = '/'.$scriptname;
    }
    else 
    {
        $scriptname = '/'.'index.php';
    }
    
    // mit Admidio 5 wurden alle Hauptscripts meiner Plugins umbenannt zu index.php
    // als URL (unter "Sichtbar für") könnte deshalb stehen:
    // /adm_plugins/Formfiller/index.php oder /adm_plugins/Formfiller/ oder /adm_plugins/Formfiller
    // bei jeder dieser Möglichkeiten wird die index.php vom Browser aufgerufen. 
    // jede dieser Möglichkeiten muss in dieser Funktion abgedeckt werden
    $menuURLs = array(FOLDER_PLUGINS. PLUGIN_FOLDER. ($subplugin ? PLUGIN_SUBFOLDER : ''). $scriptname , FOLDER_PLUGINS. PLUGIN_FOLDER.($subplugin ? PLUGIN_SUBFOLDER : '').'/' , FOLDER_PLUGINS. PLUGIN_FOLDER.($subplugin ? PLUGIN_SUBFOLDER : '')) ;
   
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url IN (\''. implode('\', \'', $menuURLs) . '\')';
    
    $menuStatement = $gDb->queryPrepared($sql);
    
    if ( $menuStatement->rowCount() !== 0 )
    {
        while ($row = $menuStatement->fetch())
        {
            $menIds[] = (int) $row['men_id'];
        }
        
        foreach ($menIds as $menId)
        {
            // read current roles rights of the menu
            $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
            
            // check for right to show the menu
            if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
            {
                $userIsAuthorized = true;
            }
        }
    }
    return $userIsAuthorized;
}

