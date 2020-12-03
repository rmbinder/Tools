<?php
/**
 ***********************************************************************************************
 * multiple memperships
 * 
 * Mehrfache Rollenmitgliedschaften
 *
 * Version 2.0 Beta 1
 * 
 * Stand 03.12.2020
 *
 * Dieses Admidio-Plugin erkennt mehrfache Rollenmitgliedschaften.
 * 
 * Author: rmb
 *
 * Compatible with Admidio version 4
 *
 * @copyright 2004-2020 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

$headline = $gL10n->get('PLG_MULTIPLE_MEMBERSHIPS_NAME');

$gNavigation->addStartUrl(CURRENT_URL);

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
    $columnValues = array();
    $columnValues[] = '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_id' => $row['mem_usr_id'])).'">'.$row['last_name'].', '.$row['first_name']. '</a>';
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
