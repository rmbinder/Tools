<?php
/**
 ***********************************************************************************************
 * BLSV_Export
 *
 *
 * Seit Anfang 2018 muss eine Mitgliedermeldung an den BLSV (Bayrischer-Landessportverband) 
 * immer als Excel-Liste mit allen Vereinsmitgliedern erfolgen.
 * 
 * Dieses Plugin erstellt diese Exportliste als CSV- oder als XLSX-Datei.
 * 
 * 
 * Since the beginning of 2018, a membership report to the BLSV (Bayrischer-Landessportverband) must always be made as an Excel list with all club members.
 * 
 * This plugin creates this export list as a CSV or XLSX file.
 * 
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
require_once(__DIR__ . '/constants.php');

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/languages');

$headline = $gL10n->get('PLG_BLSV_EXPORT_BLSV_EXPORT');

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
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-file-earmark-arrow-down-fill');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

$page = new HtmlPage('plg-blsv-export', $headline);

$page->addHtml($gL10n->get('PLG_BLSV_EXPORT_DESC'));
$page->addHtml('<br><br>');
$page->addHtml($gL10n->get('PLG_BLSV_EXPORT_DESC2'));
$page->addHtml('<br><br>');

if ($gCurrentUser->isAdministrator())
{
    // show link to edit config file
    $page->addPageFunctionsMenuItem('admMenuItemPreferences', $gL10n->get('PLG_BLSV_EXPORT_EDIT_CONFIG_FILE'),
        ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/edit_file.php', 
        'bi-gear-fill');
    
    if (file_exists(CONFIG_ORIG) || file_exists(CONFIG_SAVE))
    {
        $page->addPageFunctionsMenuItem('admMenuItemRestore', $gL10n->get('PLG_BLSV_EXPORT_RESTORE_CONFIG_FILE'), '#', 'bi-reply-fill');
        if (file_exists(CONFIG_ORIG))
        {
            $page->addPageFunctionsMenuItem('admMenuItemRestoreOrig', $gL10n->get('PLG_BLSV_EXPORT_ORIG_FILE'),
                SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/restore_file.php', array('mode' => 'orig')),
                'bi-reply-fill', 
                'admMenuItemRestore');
        }
        if (file_exists(CONFIG_SAVE))
        {
            $page->addPageFunctionsMenuItem('admMenuItemRestoreSave', $gL10n->get('PLG_BLSV_EXPORT_SAVE_FILE'),
                SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/restore_file.php', array('mode' => 'save')),
                'bi-reply-fill', 
                'admMenuItemRestore');
        }
    }  
}
    
// show link to documentation
$page->addPageFunctionsMenuItem('admMenuItemOpenDoc', $gL10n->get('PLG_FORMFILLER_DOCUMENTATION'),
    ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/documentation.pdf',  
    'bi-file-pdf-fill');

// show form
$form = new HtmlForm('blsv_export_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/export.php'), $page);

$radioButtonEntries = array('xlsx'   => $gL10n->get('SYS_MICROSOFT_EXCEL').' (XLSX)', 
                            'csv-ms' => $gL10n->get('SYS_MICROSOFT_EXCEL').' (CSV)',
                            'csv-oo' => $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')',
                            'xml'    => $gL10n->get('PLG_BLSV_EXPORT_BSBNET').' (XML)' );
$form->addRadioButton('export_mode',$gL10n->get('PLG_BLSV_EXPORT_SELECT_EXPORTFORMAT'), $radioButtonEntries, array('defaultValue' => 'xlsx'));
$form->addSubmitButton('btn_export', $gL10n->get('PLG_BLSV_EXPORT_CREATE_FILE'), array('icon' => 'bi-file-earmark-arrow-down-fill', 'class' => ' col-sm-offset-3'));

// add form to html page and show page
$page->addHtml($form->show(false));
$page->show();

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung wird die Einstellung von 'Sichtbar für' verwendet,
 * die im Modul Menü für dieses Plugin gesetzt wurde.
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
    global $gDb, $gCurrentUser, $gMessage, $gL10n, $gLogger;
    
    $userIsAuthorized = false;
    $menId = 0;
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
    
    $menuStatement = $gDb->queryPrepared($sql, array($scriptName));
    
    if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
    {
        $gLogger->notice('BlsvExport: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('BlsvExport: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_BLSV_EXPORT_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
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
