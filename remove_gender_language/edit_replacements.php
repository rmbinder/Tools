<?php
/**
 ***********************************************************************************************
 * Script to edit the replacements.php file
 *
 * @copyright rmb
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html   - Seite mit Editor (default)
 *            save   - Speichern der neuen Daten
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../system/common_function.php');

    // only the main script can call and start this module
    if (! StringUtils::strContains($gNavigation->getUrl(), 'remove_gender_language.php') && ! StringUtils::strContains($gNavigation->getUrl(), 'edit_replacements.php')) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'html',
        'validValues' => array(
            'html',
            'save'
        )
    ));

    $headline = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT_REPLACEMENTS');

    if ($getMode === 'save') {
        // $_POST can not be used, because admidio removes alls HTML & PHP-Code from the parameters

        $postReplacementsText = htmlspecialchars_decode($_REQUEST['replacementstext']);

        $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements.php';
        $filePathSave = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements_save.php';

        try {
            FileSystemUtils::copyFile($filePath, $filePathSave, array(
                'overwrite' => true
            ));
            FileSystemUtils::writeFile($filePath, $postReplacementsText);
        } catch (RuntimeException $exception) {
            $gMessage->show($exception->getMessage());
            // => EXIT
        } catch (UnexpectedValueException $exception) {
            $gMessage->show($exception->getMessage());
            // => EXIT
        }

        $gMessage->setForwardUrl($gNavigation->getPreviousUrl(), 2000);
        $gMessage->show($gL10n->get('SYS_SAVE_DATA'));
    } else {
        if (! StringUtils::strContains($gNavigation->getUrl(), 'edit_replacements.php')) {
            $gNavigation->addUrl(CURRENT_URL, $headline);
        }

        $page = PagePresenter::withHtmlIDAndHeadline('plg-remove_gender_language-edit_replacements');
        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $form = new FormPresenter('remove_gender_lanuage_file_edit_form', 'templates/edit_replacements.plugin.tools.subplugin.remove_gender_language.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/edit_replacements.php', array(
            'mode' => 'save'
        )), $page);

        $configFile = '';
        $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements.php';
        try {
            $configFile = FileSystemUtils::readFile($filePath);
        } catch (RuntimeException $exception) {
            $gMessage->show($exception->getMessage());
        } catch (UnexpectedValueException $exception) {
            $gMessage->show($exception->getMessage());
        }

        $replacementsFile = htmlspecialchars($configFile, ENT_QUOTES, 'UTF-8');

        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => ' btn-primary'
        ));

        $page->assignSmartyVariable('replacementstext', '<textarea id="replacementstext" name="replacementstext" cols="200" rows="18">' . $replacementsFile . '</textarea>');

        $form->addToHtmlPage(faLse);
        $page->show();
    }
} catch (Throwable $e) {
    $gMessage->show($e->getMessage());
}
