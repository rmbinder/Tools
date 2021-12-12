<?php
/**
 ***********************************************************************************************
 * multiple_memperships
 *
 * This plugin for Admidio recognizes multiple role memberships.
 * 
 * Autor: rmb
 *
 * Compatible with Admidio version 4
 *
 * @copyright 2020-2021 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../adm_program/system/common.php');

//sowohl der plugin-ordner, als auch der übergeordnete Ordner (= /tools) könnten umbenannt worden sein, deshalb neu auslesen
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

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/languages');

if (!(StringUtils::strContains($gNavigation->getUrl(), 'tools.php') || StringUtils::strContains($gNavigation->getPreviousUrl(), 'tools.php')))
{
    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
}

$user = new User($gDb, $gProfileFields);

$gNavigation->addUrl(CURRENT_URL);

$headline = $gL10n->get('PLG_MULTIPLE_MEMBERSHIPS_NAME');

// create html page object
$page = new HtmlPage('plg-multiple_memberships', $headline);

$sql = 'SELECT '.TBL_MEMBERS.'.mem_rol_id, '.TBL_MEMBERS.'.mem_usr_id, '.TBL_MEMBERS.'.mem_begin, '.TBL_MEMBERS.'.mem_end , rol_name,last_name.usd_value AS last_name, first_name.usd_value AS first_name FROM `adm_members`
     LEFT JOIN '.TBL_USER_DATA.' AS last_name
            ON last_name.usd_usr_id = mem_usr_id
           AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
     LEFT JOIN ' . TBL_USER_DATA . ' AS first_name
            ON first_name.usd_usr_id = `mem_usr_id`
           AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
     LEFT JOIN ' . TBL_ROLES . ' AS rol_name
            ON rol_name.rol_id = mem_rol_id    
    INNER JOIN (
        SELECT mem_rol_id, mem_usr_id from '.TBL_MEMBERS.'  
         WHERE mem_begin <= ? -- DATE_NOW
           AND mem_end    > ? -- DATE_NOW
      GROUP BY mem_rol_id, mem_usr_id
  HAVING COUNT(mem_id) > 1 )
           DUP 
            ON '.TBL_MEMBERS.'.mem_rol_id = DUP.mem_rol_id 
           AND '.TBL_MEMBERS.'.mem_usr_id = DUP.mem_usr_id    ';

$queryParams = array(
    $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
    $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
    DATE_NOW,
    DATE_NOW
);
$statement = $gDb->queryPrepared($sql, $queryParams);
	
$datatable = true;
$hoverRows = false;
$classTable  = 'table table-condensed';
$table = new HtmlTable('table_role_overview', $page, $hoverRows, $datatable, $classTable);

$columnAlign  = array('left', 'left',  'right');
$table->setColumnAlignByArray($columnAlign);

$columnValues = array('', '',  '');
$table->addRowHeadingByArray($columnValues);

while ($row = $statement->fetch())
{
    $user->readDataById($row['mem_usr_id']);
    $columnValues = array();
    $columnValues[] = '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$row['last_name'].', '.$row['first_name']. '</a>';
    $columnValues[] = $row['rol_name'];
    
    // date must be formated
    $date = \DateTime::createFromFormat('Y-m-d', $row['mem_begin']);
    $columnValues[] = $gL10n->get('SYS_SINCE', array($date->format($gSettingsManager->getString('system_date'))));

    $table->addRowByArray($columnValues);
}

$table->setDatatablesGroupColumn(1);
$table->setDatatablesRowsPerPage(10);

$page->addHtml($table->show(false));
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
        $GLOBALS['gLogger']->notice('MultipleMemberships: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $GLOBALS['gLogger']->notice('MultipleMemberships: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($GLOBALS['gL10n']->get('PLG_MULTIPLE_MEMBERSHIPS_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
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
