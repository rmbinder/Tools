<?php
/**
 ***********************************************************************************************
 * update_preferences
 * 
 * This plugin for Admidio updates the current preferences with the default preferences.
 *
 * Author: rmb
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

use Admidio\Components\Entity\Component;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Roles\Entity\RolesRights;

require_once(__DIR__ . '/../../../system/common.php');
include(__DIR__ . '/../../../install/db_scripts/preferences.php');

//sowohl der plugin-Ordner, als auch der übergeordnete Ordner (= /tools) könnten umbenannt worden sein, deshalb neu auslesen
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

$headline = $gL10n->get('PLG_UPDATE_PREFERENCES_PLUGIN_NAME');

//if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], PLUGIN_PARENT_FOLDER.'/index.php', false)))
{
    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-recycle');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'update')));

$missingSettingsArr   = array();
$missingSettingsTxt   = '';
$importOrgPreferences = $gSettingsManager->getAll();

foreach ($defaultOrgPreferences as $key => $value)
{
    if ( !array_key_exists ( $key, $importOrgPreferences ) )
    {
        $missingSettingsArr[$key] = $value;
        $missingSettingsTxt .= $key.'<br/>';
    }
}

$page = new HtmlPage('plg-update_preferences', $headline);

$page->addHtml('<strong>'.$gL10n->get('PLG_UPDATE_PREFERENCES_DESC').'</strong><br><br>');

if ($getMode == 'start')     //Default
{
    $form = new HtmlForm('update_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/update_preferences.php', array('mode' => 'update')), $page); 
 
    if (!empty($missingSettingsTxt))
    {
        $form->addDescription($gL10n->get('PLG_UPDATE_PREFERENCES_FOUND'));
        $form->addDescription($missingSettingsTxt);
        $form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3'));       
    }
    else 
    {
        $form->addDescription($gL10n->get('PLG_UPDATE_PREFERENCES_NOTHING_FOUND'));
    
        //seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
        $form->addStaticControl('', '', '');
    }
    $page->addHtml($form->show(false));
   
}
elseif ($getMode == 'update')
{
    foreach ($missingSettingsArr as $key => $value)
    {
        $gSettingsManager->set($key, $value);
    }
    $page->addHtml($gL10n->get('PLG_UPDATE_PREFERENCES_UPDATED'));
}

$page->show();

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung wird die Einstellung von 'Sichtbar für' verwendet,
 * die im Modul Menü für dieses Plugin gesetzt wurde.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
    global $gDb, $gMessage, $gLogger, $gL10n, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menId = 0;
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
    
    $menuStatement = $gDb->queryPrepared($sql, array($scriptName));
    
    if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
    {
        $gLogger->notice('UpdatePreferences: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('UpdatePreferences: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_UPDATE_PREFERENCES_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
    }
    else
    {
        while ($row = $menuStatement->fetch())
        {
            $menId = (int) $row['men_id'];
        }
    }
    
    // read current roles rights of the menu
    $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
    
    // check for right to show the menu
    if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
    {
        $userIsAuthorized = true;
    }
    return $userIsAuthorized;
}



