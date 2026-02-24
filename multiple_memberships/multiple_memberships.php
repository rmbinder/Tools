<?php
/**
 ***********************************************************************************************
 * multiple_memperships
 *
 * This plugin for Admidio recognizes multiple role memberships.
 * 
 * Author: rmb
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *   
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/../system/common_function.php');

    // Einbinden der Sprachdatei
    $gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/languages');

    $user = new User($gDb, $gProfileFields);

    $headline = $gL10n->get('PLG_MULTIPLE_MEMBERSHIPS_NAME');

    // if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
    $navStack = $gNavigation->getStack();
    if (! (StringUtils::strContains($navStack[0]['url'], PLUGIN_FOLDER . '/index.php', false))) {
        // only authorized user are allowed to start this module
        if (! isUserAuthorized(basename(__FILE__), true)) {
            throw new Exception('SYS_NO_RIGHTS');
        }
        $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-people-fill');
    } else {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    $page = PagePresenter::withHtmlIDAndHeadline('plg-multiple_memberships');
    $page->setContentFullWidth();
    $page->setHeadline($headline);

    $smarty = $page->createSmartyObject();
    $smarty->assign('l10n', $gL10n);
    $smarty->assign('classTable', 'table table-condensed table-hover');

    $table = new DataTables($page, 'adm_multiple_memberships_table');
    $table->setGroupColumn(1);
    $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));

    // data array
    $data = array(
        'headers' => array(),
        'rows' => array(),
        'column_align' => array(),
        'column_width' => array()
    );

    $data['column_align'] = array(
        'left',
        'left',
        'right'
    );

    $data['headers'] = array(
        '&nbsp;',
        '&nbsp;',
        '&nbsp;'
    );

    $data['column_width'] = array(
        '',
        '70%',
        '30%'
    );

    $sql = 'SELECT ' . TBL_MEMBERS . '.mem_rol_id, ' . TBL_MEMBERS . '.mem_usr_id, ' . TBL_MEMBERS . '.mem_begin, ' . TBL_MEMBERS . '.mem_end , rol_name,last_name.usd_value AS last_name, first_name.usd_value AS first_name FROM ' . TBL_MEMBERS . '
     LEFT JOIN ' . TBL_USER_DATA . ' AS last_name
            ON last_name.usd_usr_id = mem_usr_id
           AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
     LEFT JOIN ' . TBL_USER_DATA . ' AS first_name
            ON first_name.usd_usr_id = mem_usr_id
           AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
     LEFT JOIN ' . TBL_ROLES . ' AS rol_name
            ON rol_name.rol_id = mem_rol_id    
    INNER JOIN (
        SELECT mem_rol_id, mem_usr_id from ' . TBL_MEMBERS . '  
         WHERE mem_begin <= ? -- DATE_NOW
           AND mem_end    > ? -- DATE_NOW
      GROUP BY mem_rol_id, mem_usr_id
  HAVING COUNT(mem_id) > 1 )
           DUP 
            ON ' . TBL_MEMBERS . '.mem_rol_id = DUP.mem_rol_id 
           AND ' . TBL_MEMBERS . '.mem_usr_id = DUP.mem_usr_id    ';

    $queryParams = array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        DATE_NOW,
        DATE_NOW
    );
    $statement = $gDb->queryPrepared($sql, $queryParams);

    $listRowNumber = 1;
    while ($row = $statement->fetch()) {
        $user->readDataById($row['mem_usr_id']);
        $columnValues = array();
        $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
            'user_uuid' => $user->getValue('usr_uuid')
        )) . '">' . $row['last_name'] . ', ' . $row['first_name'] . '</a>';
        $columnValues[] = $row['rol_name'];

        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d', $row['mem_begin']);
        $columnValues[] = $gL10n->get('SYS_SINCE', array(
            $date->format($gSettingsManager->getString('system_date'))
        ));

        $data['rows'][] = array(
            'id' => 'row-' . $listRowNumber,
            'data' => $columnValues
        );

        ++ $listRowNumber;
    }

    $table->createJavascript(count($data['rows']), count($data['headers']));
    $table->setColumnAlignByArray($data['column_align']);

    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('columnWidth', $data['column_width']);
    $smarty->assign('headers', $data['headers']);
    $smarty->assign('rows', $data['rows']);

    // Fetch the HTML table from our Smarty template
    $htmlTable = $smarty->fetch('templates/view.plugin.tools.subplugin.multiple_memberships.tpl');
    // add table list to the page
    $page->addHtml($htmlTable);

    $page->show();
} catch (Throwable $e) {
    $gMessage->show($e->getMessage());
}
