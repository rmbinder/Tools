<?php
/**
 ***********************************************************************************************
 * restore_blsv_export_config
 *
 * This plugin for Admidio restores the config.php from plugin restore_db.
 * 
 * Autor: rmb
 *
 * Compatible with Admidio version 4
 *
 * @copyright 2020-2022 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../adm_program/system/common.php');

//sowohl der plugin-ordner, als auch der übergeordnete Ordner (= /tools) könnten umbenannt worden sein, deshalb neu auslesen
$folders = explode(DIRECTORY_SEPARATOR, __DIR__);
if(!defined('PLUGIN_FOLDER'))
{
    define('PLUGIN_FOLDER', '/'.$folders[sizeof($folders)-1]);
}
if(!defined('PLUGIN_PARENT_FOLDER'))
{
    define('PLUGIN_PARENT_FOLDER', '/'.$folders[sizeof($folders)-2]);
}
unset($folders);

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/languages');

if (!(StringUtils::strContains($gNavigation->getUrl(), 'tools.php') || StringUtils::strContains($gNavigation->getPreviousUrl(), 'tools.php')))
{
    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
}

$gNavigation->addUrl(CURRENT_URL);

$headline = $gL10n->get('PLG_RESTORE_BLSV_EXPORT_CONFIG_NAME');

// create html page object
$page = new HtmlPage('plg-restore_blsv_export_config', $headline);

$filePathOrig = ADMIDIO_PATH . FOLDER_PLUGINS . '/blsv_export/config_orig.php';
$filePathAkt  = ADMIDIO_PATH . FOLDER_PLUGINS . '/blsv_export/config.php';
$filePathSave = ADMIDIO_PATH . FOLDER_PLUGINS . '/blsv_export/config_save.php';

//gibt es eine config_save?  
if (file_exists($filePathSave))
{
    //config.php überschreiben mit config_orig.php
    try
    {
        FileSystemUtils::copyFile($filePathOrig, $filePathAkt, array('overwrite' => true));
        FileSystemUtils::deleteFileIfExists($filePathSave);
    }
    catch (\RuntimeException $exception)
    {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
    catch (\UnexpectedValueException $exception)
    {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
    
    $page->addHtml($gL10n->get('PLG_RESTORE_BLSV_EXPORT_CONFIG_ORIG_FILE_RESTORED'));
}
else
{
    if (file_exists($filePathOrig))
    {
        $page->addHtml($gL10n->get('PLG_RESTORE_BLSV_EXPORT_CONFIG_NO_SAVE_FILE'));
    }
    else
    {
        //es gibt noch keine config_org.php --> anlegen
        try
        {
            FileSystemUtils::copyFile($filePathAkt, $filePathOrig, array('overwrite' => true));
        }
        catch (\RuntimeException $exception)
        {
            $gMessage->show($exception->getMessage());
            // => EXIT
        }
        catch (\UnexpectedValueException $exception)
        {
            $gMessage->show($exception->getMessage());
            // => EXIT
        }
        $page->addHtml($gL10n->get('PLG_RESTORE_BLSV_EXPORT_CONFIG_ORIG_FILE_CREATED'));
    }
}

$page->show();

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für'
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
    global $gMessage;
    
    $userIsAuthorized = false;
    $menId = 0;
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
    
    $menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($scriptName));
    
    if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
    {
        $GLOBALS['gLogger']->notice('RestoreBLSVExportConfig: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $GLOBALS['gLogger']->notice('RestoreBLSVExportConfig: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($GLOBALS['gL10n']->get('PLG_RESTORE_BLSV_EXPORT_CONFIG_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
    }
    else
    {
        while ($row = $menuStatement->fetch())
        {
            $menId = (int) $row['men_id'];
        }
    }
    
    $sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
    
    $menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($menId));
    while ($row = $menuStatement->fetch())
    {
        if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
        {
            // Read current roles rights of the menu
            $displayMenu = new RolesRights($GLOBALS['gDb'], 'menu_view', $row['men_id']);
            $rolesDisplayRight = $displayMenu->getRolesIds();
            
            // check for right to show the menu
            if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($GLOBALS['gCurrentUser']->getRoleMemberships()))
            {
                $userIsAuthorized = true;
            }
        }
    }
    return $userIsAuthorized;
}
