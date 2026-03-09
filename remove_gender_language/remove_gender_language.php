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
 * Parameters:
 *
 * mode     : html           - (default) Show page with all preferences panels
 *            save           - Save organization preferences
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;

use Plugins\Tools\remove_gender_language\classes\Service\RemoveGenderLanguageService;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../system/common_function.php');

    // Einbinden der Sprachdatei
    $gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/languages');

    // Einbinden der Konfigurationsdatei
    include (ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/config.php');

    $headline = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_PLUGIN_NAME');

    // if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
    $navStack = $gNavigation->getStack();
    if (! (StringUtils::strContains($navStack[0]['url'], PLUGIN_FOLDER . '/index.php', false))) {
        // only authorized user are allowed to start this module
        if (! isUserAuthorized(basename(__FILE__), true)) {
            throw new Exception('SYS_NO_RIGHTS');
        }
        $gNavigation->addStartUrl(strtok(CURRENT_URL, '?'), $headline, 'bi-gender-ambiguous');
    } else {
        $gNavigation->addUrl(strtok(CURRENT_URL, '?'), $headline);
    }

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'html',
        'validValues' => array(
            'html',
            'save'
        )
    ));

    switch ($getMode) {
        case 'html':

            $page = PagePresenter::withHtmlIDAndHeadline('plg-remove_gender_language');
            $page->setContentFullWidth();
            $page->setHeadline($headline);

            $page->addHtml('<strong>' . $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_DESC') . '</strong><br><br>');

            // show link to edit_replacements
            $page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT_REPLACEMENTS'), ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/edit_replacements.php', 'bi-pencil-fill');

            $form = new FormPresenter('remove_gender_language_form', 'templates/view.plugin.tools.subplugin.remove_gender_language.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/remove_gender_language.php', array(
                'mode' => 'save'
            )), $page);

            $languageBackupGlobFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/' . $languageFileName . '*.xml';

            $backupFiles = glob($languageBackupGlobFilePath);
            $obsoleteBackupFile = '';

            $backupFilesNames = array();
            foreach ($backupFiles as $data) {
                $fileName = strrchr($data, $languageFileName);
                $backupFilesNames[] = $fileName;

                if (ADMIDIO_VERSION_TEXT !== substr($fileName, strlen($languageFileName) + 1, - 15)) {
                    $obsoleteBackupFile = $fileName;
                }
            }

            if (count($backupFiles) < 4) {

                $form->addCustomContent('cc_create', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE_DESC'));

                $form->addSubmitButton('btn_create', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_CREATE'), array(
                    'icon' => 'bi-copy',
                    'class' => 'offset-sm-3'
                ));
            }

            if (count($backupFiles) !== 0) {
                if (count($backupFiles) > 3) {
                    $form->addCustomContent('cc_save', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_SAVE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_RESTORE'));
                } else {
                    $form->addCustomContent('cc_backup_restore', '', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_RESTORE'));
                }

                if ($obsoleteBackupFile !== '') {
                    $form->addCustomContent('cc_obselete', '', '<strong>' . $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_OBSOLETE', array(
                        $obsoleteBackupFile,
                        ADMIDIO_VERSION_TEXT
                    )) . '</strong>');
                }

                $form->addSelectBox('backup_file', '', $backupFilesNames, array(
                    'showContextDependentFirstEntry' => false,
                    'arrayKeyIsNotValue' => true
                ));

                $selectBoxEntries = array(
                    'delete' => $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_DELETE'),
                    'restore' => $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_RESTORE')
                );
                $form->addSelectBox('sct_restore_or_delete', '', $selectBoxEntries, array(
                    'showContextDependentFirstEntry' => false
                ));

                $form->addSubmitButton('btn_restore_or_delete', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EXECUTE'), array(
                    'icon' => 'bi-check-lg',
                    'class' => 'offset-sm-3'
                ));
            }

            $form->addCustomContent('cc_replace', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE'), $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_DESC'));
            $form->addSubmitButton('btn_replace', $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_BTN'), array(
                'icon' => 'bi-backspace-fill',
                'class' => 'offset-sm-3'
            ));

            $form->addToHtmlPage(false);
            $page->show();
            break;

        case 'save':
            $removeLanguage = new RemoveGenderLanguageService();
            $result = $removeLanguage->save($_POST);

            $gMessage->setForwardUrl($gNavigation->getUrl(), 2000);
            $gMessage->show($result, $headline);
            break;
    }
} catch (Throwable $exception) {
    $gMessage->show($exception->getMessage());
}
