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

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Roles\Entity\RolesRights;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/../system/common_function.php');

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/languages');

$headline = $gL10n->get('PLG_CHANGE_SYSTEM_LANGUAGE_PLUGIN_NAME');

//if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], PLUGIN_FOLDER.'/index.php', false)))
{   
    // only authorized user are allowed to start this module
    if (!isUserAuthorized(basename(__FILE__), true))
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
    
    $form = new HtmlForm('choose_db_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/change_system_language.php', array('mode' => 'change')), $page);
        
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

