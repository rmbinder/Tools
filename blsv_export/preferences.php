<?php
/**
 ***********************************************************************************************
 * Editieren der config.php für das Admidio-Plugin BLSV_Export
 *
 * @copyright 2004-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html   - Seite mit Editor (default)
 *            save   - Speichern der neuen Daten
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../adm_program/system/common.php');

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

if (isset($_GET['mode']) && $_GET['mode'] === 'save')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'save')));

$headline = $gL10n->get('PLG_BLSV_EXPORT_BLSV_EXPORT').' - '.$gL10n->get('PLG_BLSV_EXPORT_SETTINGS');

if ($getMode === 'save')
{
    // $_POST can not be used, because admidio removes alls HTML & PHP-Code from the parameters
    
    $postConfigText = htmlspecialchars_decode($_REQUEST['configtext']);
    
    $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER .PLUGIN_FOLDER .'/config.php';
    $filePathSave = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config_save.php';
    
    try
    {
        FileSystemUtils::copyFile($filePath, $filePathSave, array('overwrite' => true));
        FileSystemUtils::writeFile($filePath, $postConfigText);
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
    if ( !StringUtils::strContains($gNavigation->getUrl(), 'preferences.php'))
    {
        $gNavigation->addUrl(CURRENT_URL);
    }
    
    $page = new HtmlPage('blsv_export-preferences', $headline);
    
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
    
    $form = new HtmlForm('blsv_export-form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/preferences.php', array('mode' => 'save')), $page);

    $form->addDescription($gL10n->get('PLG_BLSV_EXPORT_EDIT'));

    $configFile = '';
    $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config.php';
    try
    {
        $configFile = FileSystemUtils::readFile($filePath);
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
