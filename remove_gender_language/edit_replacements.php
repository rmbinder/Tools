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

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../system/common_function.php');

    if (isset($_GET['mode']) && $_GET['mode'] === 'save') {
        // ajax mode then only show text if error occurs
        $gMessage->showTextOnly(true);
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

        $postConfigText = htmlspecialchars_decode($_REQUEST['configtext']);

        $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements.php';
        $filePathSave = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements_save.php';

        try {
            FileSystemUtils::copyFile($filePath, $filePathSave, array(
                'overwrite' => true
            ));
            FileSystemUtils::writeFile($filePath, $postConfigText);
        } catch (RuntimeException $exception) {
            $gMessage->show($exception->getMessage());
            // => EXIT
        } catch (UnexpectedValueException $exception) {
            $gMessage->show($exception->getMessage());
            // => EXIT
        }
        echo 'success';
    } else {
        if (! StringUtils::strContains($gNavigation->getUrl(), 'edit_replacements.php')) {
            $gNavigation->addUrl(CURRENT_URL, $headline);
        }

        $page = new HtmlPage('edit_replacements-preferences', $headline);

        $page->addJavascript('
    $("#edit_replacements-form").submit(function(event) {
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
                if (data === "success") {
        
                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"bi bi-check-lg\"></i><strong>' . $gL10n->get('SYS_SAVE_DATA') . '</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                    //window.location.replace("' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/edit_replacements.php");
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"bi bi-exclamation-circle-fill\"></i>" + data);
                }
            }
        });
    });', true);

        $form = new HtmlForm('edit_replacements-form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/edit_replacements.php', array(
            'mode' => 'save'
        )), $page);

        $form->addDescription($gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT'));

        $configFile = '';
        $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements.php';
        try {
            $configFile = FileSystemUtils::readFile($filePath);
        } catch (RuntimeException $exception) {
            $gMessage->show($exception->getMessage());
        } catch (UnexpectedValueException $exception) {
            $gMessage->show($exception->getMessage());
        }

        $configFile = htmlspecialchars($configFile, ENT_QUOTES, 'UTF-8');

        $form->addDescription('<textarea id="configtext" name="configtext" cols="120" rows="12">' . $configFile . '</textarea>');
        $form->addDescription('<strong>' . $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_EDIT_INFO') . '</strong>');
        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => ' btn-primary'
        ));

        $page->addHtml($form->show(false));
        $page->show();
    }
} catch (Throwable $e) {
    $gMessage->show($e->getMessage());
}
