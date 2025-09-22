<?php
/**
 ***********************************************************************************************
 * Editieren der config.php für das Admidio-Plugin BLSV_Export
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html   - Seite mit Editor (default)
 *            save   - Speichern der neuen Daten
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/constants.php');

// only the main script can call and start this module
if (!StringUtils::strContains($gNavigation->getUrl(), 'blsv_export.php') && !StringUtils::strContains($gNavigation->getUrl(), 'edit_file.php'))
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

if (isset($_GET['mode']) && $_GET['mode'] === 'save')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'save')));

$headline = $gL10n->get('PLG_BLSV_EXPORT_EDIT_CONFIG_FILE');

if ($getMode === 'save')
{
    // $_POST can not be used, because admidio removes alls HTML & PHP-Code from the parameters
    
    $postConfigText = htmlspecialchars_decode($_REQUEST['configtext']);
        
    try
    {
        if (!file_exists(CONFIG_ORIG))
        {
            FileSystemUtils::copyFile(CONFIG_CURR, CONFIG_ORIG);
        }
        
        FileSystemUtils::copyFile(CONFIG_CURR, CONFIG_SAVE, array('overwrite' => true));
        FileSystemUtils::writeFile(CONFIG_CURR, $postConfigText);
    }
    catch (\RuntimeException $exception)
    {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
    catch (\UnexpectedValueException $exception)
    {
        $gMessage->show($exception->getMessage());
        // => EXIT
    }
    echo 'success';
}
else
{
    if ( !StringUtils::strContains($gNavigation->getUrl(), 'edit_file.php'))
    {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }
    
    $page = new HtmlPage('plg-blsv_export-edit-file', $headline);
    
    $page->addJavascript('
    $("#blsv_export-form").submit(function(event) {
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
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                    window.location.replace("'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/blsv_export.php");
                } else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
            }
        });
    });',
    true
    );
    
    $form = new HtmlForm('blsv_export-form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/edit_file.php', array('mode' => 'save')), $page);

    $form->addDescription($gL10n->get('PLG_BLSV_EXPORT_EDIT'));

    $configFile = '';
   
    try
    {
        $configFile = FileSystemUtils::readFile(CONFIG_CURR);
    }
    catch (\RuntimeException $exception)
    {
        $gMessage->show($exception->getMessage());
    }
    catch (\UnexpectedValueException $exception)
    {
        $gMessage->show($exception->getMessage());
    }
    
    $configFile = htmlspecialchars($configFile, ENT_QUOTES,'UTF-8');

    $form->addDescription('<textarea id="configtext" name="configtext" cols="200" rows="18">'.$configFile .'</textarea>');
    $form->addDescription('<strong>'.$gL10n->get('PLG_BLSV_EXPORT_EDIT_INFO').'</strong>');
    $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' btn-primary'));

    $page->addHtml($form->show(false));
    $page->show();
}
