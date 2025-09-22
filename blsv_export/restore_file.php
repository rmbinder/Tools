<?php
/**
 ***********************************************************************************************
 * restore_file
 *
 * Restores the config.php from plugin blsv_export.
 * 
 * Author: rmb
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/constants.php');

// only the main script can call and start this module
if (!StringUtils::strContains($gNavigation->getUrl(), 'blsv_export.php'))
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'save', 'validValues' => array('orig', 'save')));

$headline = $gL10n->get('PLG_BLSV_EXPORT_RESTORE_CONFIG_FILE');

$gNavigation->addUrl(CURRENT_URL, $headline);

// create html page object
$page = new HtmlPage('plg-blsv_export_restore', $headline);

//gibt es eine config_save?  
if ($getMode === 'save' && file_exists(CONFIG_SAVE))
{
    //config.php überschreiben mit config_save.php
    try
    {
        FileSystemUtils::copyFile(CONFIG_SAVE, CONFIG_CURR, array('overwrite' => true));
        FileSystemUtils::deleteFileIfExists(CONFIG_SAVE);
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
    
    $page->addHtml($gL10n->get('PLG_BLSV_EXPORT_SAVE_FILE_RESTORED'));
}
elseif ($getMode === 'orig' && file_exists(CONFIG_ORIG))
{
    //config.php überschreiben mit config_orig.php
    try
    {
        FileSystemUtils::copyFile(CONFIG_ORIG, CONFIG_CURR, array('overwrite' => true));
        FileSystemUtils::deleteFileIfExists(CONFIG_SAVE);
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
    
    $page->addHtml($gL10n->get('PLG_BLSV_EXPORT_ORIG_FILE_RESTORED'));
}

$page->show();

