<?php
/**
 ***********************************************************************************************
 * Tools
 *
 * Version 3.3.1
 *
 * (Version 1 and 2 were released under the name MultipleMemberships)
 * 
 * Stand 13.02.2025
 * 
 * Tools provides a platform for smaller Admidio plugins.
 * Each plugin must be in a separate subfolder of Tools and the plugin name and folder name must be identical.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 4.3
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;

require_once(__DIR__ . '/../../system/common.php');
require_once(__DIR__ . '/system/common_function.php');
 
include(__DIR__ . '/system/version.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized())
{
    //throw new Exception('SYS_NO_RIGHTS');                     // über Exception wird nur SYS_NO_RIGHTS angezeigt
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}
                                
$headline = $gL10n->get('PLG_TOOLS_PLUGIN_NAME');

$gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-gear-fill');
    
$page = new HtmlPage('plg-tools-mainpage', $headline.' <small>v'.$plugin_version.'</small>');

// icon-link to info
$html = '<p align="right">
            <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/system/tools_popup_info.php').'">'.'
                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="' . $gL10n->get('SYS_INFORMATIONS') . '"></i>
            </a>
        </p>';

$page->addHtml($html);
$page->addHtml($gL10n->get('PLG_TOOLS_DESC'));

$existingPlugins = array();

$folders = FileSystemUtils::getDirectoryContent(__DIR__, false, true, array(FileSystemUtils::CONTENT_TYPE_DIRECTORY));

foreach ($folders as $folderAbsolutePath => $dummy)
{
    $pluginFolderAndName = substr($folderAbsolutePath,strrpos($folderAbsolutePath,DIRECTORY_SEPARATOR)+1);
    if (is_file($folderAbsolutePath.'/'.$pluginFolderAndName.'.php'))
    {
        $existingPlugins[strtoupper($pluginFolderAndName)] = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/'.$pluginFolderAndName.'/'.$pluginFolderAndName.'.php';
    }
}
ksort($existingPlugins);

foreach ($existingPlugins as $pluginname => $pluginPath)
{
    $page->addHtml('<button type="button" class="btn btn-primary" style= "text-align: center;width:75%" onclick="window.location.href=\''.$pluginPath.'\'">'.$pluginname.'</button>');
    $page->addHtml('<br><br>');
}
        
// show complete html page
$page->show();
