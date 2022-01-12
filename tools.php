<?php
/**
 ***********************************************************************************************
 * tools
 *
 * Version 3.1.0
 *
 * (Version 1 and 2 were released under the name MultipleMemberships)
 * 
 * Stand 12.01.2022
 * 
 * Tools provides a platform for smaller Admidio plugins.
 * Each plugin must be in a separate subfolder of Tools and the plugin name and folder name must be identical.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 4.1
 *
 * @copyright 2020-2022 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

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

// define title (html) and headline
$title = $gL10n->get('PLG_TOOLS_PLUGIN_NAME');
$headline = $gL10n->get('PLG_TOOLS_PLUGIN_NAME');

$gNavigation->addStartUrl(CURRENT_URL, $headline);
    
$page = new HtmlPage('plg-tools-mainpage', $headline);
$page->setTitle($title);

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
