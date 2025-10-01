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

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/../system/common_function.php');
include(__DIR__ . '/../../../install/db_scripts/preferences.php');

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/languages');

$headline = $gL10n->get('PLG_UPDATE_PREFERENCES_PLUGIN_NAME');

//if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], PLUGIN_FOLDER.'/index.php', false)))
{   
    // only authorized user are allowed to start this module
    if (!isUserAuthorized(basename(__FILE__), true))
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
    $form = new HtmlForm('update_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/update_preferences.php', array('mode' => 'update')), $page); 
 
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

