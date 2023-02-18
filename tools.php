<?php
/**
 ***********************************************************************************************
 * Tools
 *
 * Version 3.2.0
 *
 * (Version 1 and 2 were released under the name MultipleMemberships)
 * 
 * Stand 18.02.2023
 * 
 * Tools provides a platform for smaller Admidio plugins.
 * Each plugin must be in a separate subfolder of Tools and the plugin name and folder name must be identical.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 2
 *
 * @copyright 2004-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
include(__DIR__ . '/version.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

//$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
$scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

// only authorized user are allowed to start this module
if (!isUserAuthorized($scriptName))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$headline = $gL10n->get('PLG_TOOLS_PLUGIN_NAME');

$gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-cogs');
    
$page = new HtmlPage('plg-tools-mainpage', $headline.' <small>v'.$plugin_version.'</small>');

// icon-link to info
$html = '<p align="right">
            <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_PLUGINS . PLUGIN_FOLDER .'/tools_popup_info.php').'">'.'
                <i class="fas fa-info-circle" data-toggle="tooltip" title="' . $gL10n->get('SYS_INFORMATIONS') . '"></i>
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

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für'
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
	global $gMessage;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$GLOBALS['gLogger']->notice('Tools: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$GLOBALS['gLogger']->notice('Tools: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($GLOBALS['gL10n']->get('PLG_TOOLS_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
	}
	else
	{
		while ($row = $menuStatement->fetch())
		{
			$menId = (int) $row['men_id'];
		}
	}
	
	$sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($menId));
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($GLOBALS['gDb'], 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($GLOBALS['gCurrentUser']->getRoleMemberships()))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}
