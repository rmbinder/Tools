<?php
/**
 ***********************************************************************************************
 * remove_gender_language
 * 
 * This plugin for Admidio removes the german gender language.
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

//Einbinden der Konfigurationsdatei
include(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config.php');

$headline = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_PLUGIN_NAME');

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
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-gender-ambiguous');
}
else
{    
    $gNavigation->addUrl(CURRENT_URL, $headline);
}
  
$page = new HtmlPage('plg-remove_gender_language', $headline);

// show link to edit_replacements
$page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT_REPLACEMENTS'),
        ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/edit_replacements.php',  'bi-pencil-fill');

$page->addJavascript('
    $(".form-remove_gender_language").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();
    
        // disable default form submit
        event.preventDefault();
    
        $.post({
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if (data === "create" || data === "delete" || data === "restore"|| data === "replace") {
                    formAlert.attr("class", "alert alert-success form-alert");
                    if (data === "create") {
                        formAlert.html("<i class=\"bi bi-check-lg\"></i><strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_CREATED').'</strong>");
                    } else if (data === "delete") {
                        formAlert.html("<i class=\"bi bi-check-lg\"></i><strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_DELETED').'</strong>");
                    } else if (data === "restore") {
                        formAlert.html("<i class=\"bi bi-check-lg\"></i><strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_RESTORED').'</strong>");
                    } else {
                        formAlert.html("<i class=\"bi bi-check-lg\"></i><strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_UPDATED').'</strong>");
                    } 
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                    if (data === "create" || data === "delete" || data === "restore") {
                        setTimeout(3000);
                        window.location.replace("'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/remove_gender_language.php");
                    }
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    if (data === "replace_error_open") {
                        formAlert.html("<i class=\"bi bi-exclamation-circle-fill\"></i>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_OPEN').' ");
                    } else if (data === "replace_error_save") {
                        formAlert.html("<i class=\"bi bi-exclamation-circle-fill\"></i>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_SAVE').' ");
                    } else {
                        formAlert.html("<i class=\"bi bi-exclamation-circle-fill\"></i>" + data);
                    } 
                }
            }
        });
    });',
    true
    );

$page->addHtml('<strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_DESC').'</strong><br><br>');

$languageBackupGlobFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/'.$languageFileName.'*.xml';

$backupFiles = glob($languageBackupGlobFilePath);
$obsoleteBackupFile = '';

$backupFilesNames = array();
foreach ($backupFiles as $data)
{
    $fileName = strrchr($data,$languageFileName);
    $backupFilesNames[] = $fileName;

    if (ADMIDIO_VERSION_TEXT !== substr($fileName, strlen($languageFileName)+1, -15))
    {
        $obsoleteBackupFile = $fileName;
    }
}

if (count($backupFiles) < 4)
{
    $formCreate = new HtmlForm('remove_gender_language_create_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/remove_gender_language_function.php', array('mode' => 'create')), $page, array('class' => 'form-remove_gender_language'));
    $formCreate->addCustomContent($gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE_DESC'), array('helpTextIdLabel' => 'PLG_REMOVE_GENDER_LANGUAGE_MAX_FILES'));
    $formCreate->addSubmitButton('btn_create', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_CREATE'), array('icon' => 'bi-copy', 'class' => 'offset-sm-3'));
    $page->addHtml($formCreate->show(false));
}

if (count($backupFiles) !== 0)
{
    $formBackupAndRestore = new HtmlForm('remove_gender_language_backup_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/remove_gender_language_function.php', array('mode' => 'restore_delete')), $page, array('class' => 'form-remove_gender_language'));
    if (count($backupFiles) > 3)
    {
        $formBackupAndRestore->addCustomContent($gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_RESTORE'), array('helpTextIdLabel' => 'PLG_REMOVE_GENDER_LANGUAGE_MAX_FILES'));
    }
    else 
    {
        $formBackupAndRestore->addCustomContent('', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_RESTORE'));
    }
    
    if ($obsoleteBackupFile !== '')
    {
        $formBackupAndRestore->addCustomContent('', '<strong>'.$gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_OBSOLETE', array($obsoleteBackupFile, ADMIDIO_VERSION_TEXT)).'</strong>');
    }
    
    $formBackupAndRestore->addSelectBox('backup_file', '',$backupFilesNames, array( 'showContextDependentFirstEntry' => false, 'arrayKeyIsNotValue' => true));
    $selectBoxEntries = array('delete' => $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_DELETE'), 'restore' => $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_RESTORE'));
    $formBackupAndRestore->addSelectBox('restore_or_delete', '',$selectBoxEntries, array( 'showContextDependentFirstEntry' => false));
    $formBackupAndRestore->addSubmitButton('btn_restore_delete', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EXECUTE'), array('icon' => 'bi-check-lg', 'class' => 'offset-sm-3')); 
    $page->addHtml($formBackupAndRestore->show(false));
}

$formUndo = new HtmlForm('remove_gender_language_undo_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/remove_gender_language_function.php', array('mode' => 'replace')), $page, array('class' => 'form-remove_gender_language'));
$formUndo->addCustomContent($gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_DESC'), array('helpTextIdLabel' => 'PLG_REMOVE_GENDER_LANGUAGE_INFO_CHANGE_TEXT'));
$formUndo->addSubmitButton('btn_replace', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_BTN'), array('icon' => 'bi-backspace-fill', 'class' => 'offset-sm-3'));    
$page->addHtml($formUndo->show(false));

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
        $gLogger->notice('RemoveGenderLanguage: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('RemoveGenderLanguage: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
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



