<?php
/**
 ***********************************************************************************************
 * Tools
 *
 * Version 4.0.0 Beta 1
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
 * Compatible with Admidio version 5
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\Tools\classes\Config\ConfigTable;

// Fehlermeldungen anzeigen
error_reporting(E_ALL);

try {

    require_once (__DIR__ . '/../../system/common.php');
    require_once (__DIR__ . '/system/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Konfiguration initialisieren
    $pPreferences = new ConfigTable();
    if ($pPreferences->checkforupdate()) {
        $pPreferences->init();
    }
    $pPreferences->read();
    
    $headline = $gL10n->get('PLG_TOOLS_NAME');
    $title = $gL10n->get('PLG_TOOLS_NAME');
    
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-tools');

    // create html page object
    $page = PagePresenter::withHtmlIDAndHeadline('plg-tools-main-html');
    $page->setTitle($title);
    $page->setHeadline($headline. '<font size="-1">  v' . $pPreferences->config['Plugininformationen']['version'] .'</font>');
    
    $existingPlugins = array();
    $folders = FileSystemUtils::getDirectoryContent(__DIR__, false, true, array(
        FileSystemUtils::CONTENT_TYPE_DIRECTORY
    ));
    
    foreach ($folders as $folderAbsolutePath => $dummy) {
        $pluginFolderAndName = substr($folderAbsolutePath, strrpos($folderAbsolutePath, DIRECTORY_SEPARATOR) + 1);
        if (is_file($folderAbsolutePath . '/' . $pluginFolderAndName . '.php')) {
            $existingPlugins[strtoupper($pluginFolderAndName)] = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/' . $pluginFolderAndName . '/' . $pluginFolderAndName . '.php';
        }
    }
    ksort($existingPlugins);  

    $page->assignSmartyVariable('urlPopup', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/tools_popup_info.php'));
    $page->assignSmartyVariable('urlSettings', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php'));
    $page->assignSmartyVariable('existingPlugins', $existingPlugins);
    
    $form = new FormPresenter(
        'tools_form',
        'templates/main.script.plugin.tools.tpl',
        '',
        $page
    );

    $form->addToHtmlPage(false);
    
    // show complete html page
    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
