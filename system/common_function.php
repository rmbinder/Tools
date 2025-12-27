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
 
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Roles\Entity\RolesRights;
use Plugins\Tools\classes\Config\ConfigTable;

require_once(__DIR__ . '/../../../system/common.php');

$folders = explode('/', $_SERVER['SCRIPT_FILENAME']);
while (array_search(substr(FOLDER_PLUGINS, 1), $folders)) {
    array_shift($folders);
}
array_shift($folders);
array_pop($folders);

if (! defined('PLUGIN_FOLDER')) {
    define('PLUGIN_FOLDER', '/' . $folders[0]);
}

if (! defined('PLUGIN_SUBFOLDER') && isset($folders[1]) && is_file(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . '/' . $folders[1] . '/' . $folders[1] . '.php')) {
    define('PLUGIN_SUBFOLDER', '/' . $folders[1]);
}
unset($folders);

spl_autoload_register('myAutoloader');

/**
 * Mein Autoloader
 * Script aus dem Netz
 * https://www.marcosimbuerger.ch/tech-blog/php-autoloader.html
 *
 * @param string $className    Die übergebene Klasse
 * @return string Der überprüfte Klassenname
 */
function myAutoloader($className)
{
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
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin auszuführen.
 * 
 * In Admidio im Modul Menü kann über 'Sichtbar für' die Sichtbarkeit eines Menüpunkts eingeschränkt werden.
 * Der Zugriff auf die darunter liegende Seite ist von dieser Berechtigung jedoch nicht betroffen.
 * 
 * Mit Admidio 5 werden alle Startcripte meiner Plugins umbenannt zu index.php
 * Um die index.php auszuführen, kann die bei einem Menüpunkt angegebene URL wie folgt angegeben sein:
 * /adm_plugins/<Installationsordner des Plugins>
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/
 *   oder
 * /adm_plugins/<Installationsordner des Plugins>/<Dateiname.php>
 * 
 * Das Installationsscript des Plugins erstellt automatisch einen Menüpunkt in der Form: /adm_plugins/<Installationsordner des Plugins>/index.php
 * Standardmäßig wird deshalb für die Prüfung index.php als <Dateiname.php> verwendet, alternativ die übergebene Datei ($scriptname).
 * 
 * Diese Funktion ermittelt nur die Menüpunkte, die einen Dateinamen am Ende (index.php oder $scriptname) aufweisen, liest bei diesen Menüpunkten
 * die unter 'Sichtbar für' eingetragenen Rollen ein und prüft, ob der angemeldete Benutzer Mitglied mindestens einer dieser Rollen ist.
 * Wenn ja, ist der Benutzer berechtigt, das Plugin auszuführen (auch, wenn es weitere Menüpunkte ohne Dateinamen am Ende gibt).
 * Wichtiger Hinweis: Sind unter 'Sichtbar für' keine Rollen angegeben, so darf jeder Benutzer das Plugin ausführen
 * 
 * @param   string  $scriptName   Der Scriptname des Plugins (default: 'index.php')
 * @param   bool    $subplugin   Wenn true, dann wird die Prüfung für ein Sub-Plugin (derzeit nur Plugin Tools) durchgeführt
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized( string $scriptname = '',  bool $subplugin = false)
{
    global $gDb, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menIds = array();
  
    $menuItemURL = FOLDER_PLUGINS. PLUGIN_FOLDER. ($subplugin ? PLUGIN_SUBFOLDER : ''). '/'. ((strlen($scriptname) === 0) ? 'index.php' : $scriptname);
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $menuItemURL';
    
    $menuStatement = $gDb->queryPrepared($sql, array($menuItemURL));
    
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

/**
 * Liest alle Subplugins ein.
 * Definition eines Subplugin:
 *     Unterordner von Tools + php-Datei mit demselben Namen wie der Ordner
 *     z.B. .../Tools/blsv_export/blsv_export.php
 * Rückgabe-Array:
 *     'name'   = Name des Plugins in Großbuchstaben
 *     'url'    = URL des Plugins für den Aufruf-Link
 *     'enabled'= true, wenn das Plugin aktiviert ist
 * @param none
 * @return array $existingPlugins Das Rückgabe-Array
 */
function getExistingPlugins()
{
    $pPreferences = new ConfigTable();
    $pPreferences->read();
    
    $existingPlugins = array();
    $folders = FileSystemUtils::getDirectoryContent(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER, false, true, array(FileSystemUtils::CONTENT_TYPE_DIRECTORY));

    foreach ($folders as $folderAbsolutePath => $dummy) {
       
        $pluginFolderAndName = substr($folderAbsolutePath, strrpos($folderAbsolutePath, DIRECTORY_SEPARATOR) + 1);
        if (is_file($folderAbsolutePath . '/' . $pluginFolderAndName . '.php')) {
            $tempArr = array();    
            $tempArr['name'] = strtoupper($pluginFolderAndName);
            $tempArr['url'] = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/' . $pluginFolderAndName . '/' . $pluginFolderAndName . '.php';
            $tempArr['enabled'] =(bool) in_array($tempArr['name'], $pPreferences->config['settings']['subplugins']);
            $existingPlugins[] = $tempArr;
        }
    }
    
    array_multisort(array_column($existingPlugins, 'name'), SORT_ASC, $existingPlugins);   

    return $existingPlugins;
}
