<?php
/**
 ***********************************************************************************************
 * change_system_language
 * 
 * This plugin for Admidio makes it possible to switch the system language without administrator rights.
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

$headline = $gL10n->get('PLG_CHANGE_SYSTEM_LANGUAGE_PLUGIN_NAME');

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
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-globe');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// Initialize and check the parameters
$getMode      = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'change')));
$postLanguage = admFuncVariableIsValid($_POST, 'system_language', 'string');

if ($getMode == 'start')     //Default
{
    $page = new HtmlPage('plg-change-system-language-main', $headline);
    
    $page->addHtml('<strong>'.$gL10n->get('PLG_CHANGE_SYSTEM_LANGUAGE_DESC').'</strong><br><br>');
    
    $form = new HtmlForm('choose_db_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/change_system_language.php', array('mode' => 'change')), $page);
        
    $form->addSelectBox(
        'system_language',
        $gL10n->get('SYS_LANGUAGE'),
        $gL10n->getAvailableLanguages(),
        array('property' => HtmlForm::FIELD_REQUIRED, 'defaultValue' => $gSettingsManager->getString('system_language'), 'helpTextIdInline' => 'PLG_CHANGE_SYSTEM_LANGUAGE_HELPTEXT')
        );
   
    $form->addSubmitButton('btn_restore', $gL10n->get('SYS_SAVE'), array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3'));

    $page->addHtml($form->show(false));
 
    $page->show();
}
elseif ($getMode == 'change')
{
    if (!StringUtils::strIsValidFolderName($postLanguage)
        || !is_file(ADMIDIO_PATH . FOLDER_LANGUAGES . '/' . $postLanguage . '.xml')) 
    {
        $gMessage->show($gL10n->get('SYS_FIELD_EMPTY', array($gL10n->get('SYS_LANGUAGE'))));
        // => EXIT
    }
        
    $gSettingsManager->set('system_language', $postLanguage);
    
    // now save the new language
    $gCurrentOrganization->save();
    
    // refresh language if necessary
    if ($gL10n->getLanguage() !== $gSettingsManager->getString('system_language')) {
        $gL10n->setLanguage($gSettingsManager->getString('system_language'));
    }
    
    // clean up
    $gCurrentSession->reloadAllSessions();
    
    $curUrlArr = explode('?', $gNavigation->getUrl());    
    admRedirect($curUrlArr[0]);
    // => EXIT
}

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
        $gLogger->notice('ChangeSystemLanguage: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('ChangeSystemLanguage: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_CHANGE_SYSTEM_LANGUAGE_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
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



