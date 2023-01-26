<?php
/**
 ***********************************************************************************************
 * update_preferences
 * 
 * This plugin for Admidio updates the current preferences with the default preferences.
 *
 * Author: rmb
 *  
 * Compatible with Admidio version 4.2
 *
 * @copyright 2020-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../adm_program/system/common.php');
include(__DIR__ . '/../../../adm_program/installation/db_scripts/preferences.php');

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

//if the sub-plugin was not called from the main-plugin tools.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], 'tools.php')))
{
    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-recycle');
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
        $form->addSubmitButton('btn_save', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => 'offset-sm-3'));       
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
        $GLOBALS['gLogger']->notice('UpdatePreferences: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $GLOBALS['gLogger']->notice('UpdatePreferences: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($GLOBALS['gL10n']->get('PLG_UPDATE_PREFERENCES_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
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



