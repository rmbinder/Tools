<?php
/**
 ***********************************************************************************************
 * Shows a list of all roles
 * 
 * This script is a modified groups_roles.php
 *
 * @copyright rmb
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * cat_uuid  : show only roles of this category, if UUID is not set than show all roles
 * role_type : The type of roles that should be shown within this page.
 *             1 - inactive roles
 *             2 - active roles
 *             3 - event participation roles
 ***********************************************************************************************
 */
require_once(__DIR__ . '/../../../system/common.php');

//With Admidio 4.3, the ModuleLists class was completely revised and replaced by the ModuleGroupsRoles class. 
//To ensure that list_roles continues to run, the old ModuleLists class is integrated and modified accordingly.
require_once(__DIR__ . '/classes/ModuleLists.php');

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

$headline = $gL10n->get('PLG_LIST_ROLES_NAME');

//if the sub-plugin was not called from the main-plugin tools.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], 'tools.php')))
{
    //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
    $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
    
    // only authorized user are allowed to start this module
    if (!isUserAuthorized($scriptName))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-layer-group');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// create html page object
$page = new HtmlPage('admidio-groups-roles', $headline);

// Initialize and check the parameters
$getCatUuid  = admFuncVariableIsValid($_GET, 'cat_uuid', 'string');
$getRoleType = admFuncVariableIsValid($_GET, 'role_type', 'int', array('defaultValue' => 2))-1;

// bei $getRoleType wird beim Übertragen mittels GET +1 hinzuaddiert, da 0 nicht übertragen wird
define('ROLE_TYPE_INACTIVE', 0);
define('ROLE_TYPE_ACTIVE', 1);
define('ROLE_TYPE_EVENT_PARTICIPATION', 2);

// check if the module is enabled and disallow access if it's disabled
if (!$gSettingsManager->getBool('groups_roles_enable_module')) 
{
    $gMessage->show($gL10n->get('SYS_MODULE_DISABLED'));
    // => EXIT
}

switch ($getRoleType) 
{
    case ROLE_TYPE_INACTIVE:
        $headline .= ' - ' .  $gL10n->get('SYS_INACTIVE_GROUPS_ROLES');
        break;

    case ROLE_TYPE_ACTIVE:
        $headline .= ' - ' .  $gL10n->get('SYS_GROUPS_ROLES');
        break;

    case ROLE_TYPE_EVENT_PARTICIPATION:
        $headline .= ' - ' .  $gL10n->get('SYS_ROLES_CONFIRMATION_OF_PARTICIPATION');
        break;
}

if (!$gCurrentUser->manageRoles()) 
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
        // => EXIT
}

// only users with the right to assign roles can view inactive roles
if (!$gCurrentUser->checkRolesRight('rol_assign_roles')) 
{
    $getRoleType = ROLE_TYPE_ACTIVE;
}

$category = new TableCategory($gDb);

if ($getCatUuid !== '') 
{
    $category->readDataByUuid($getCatUuid);
    $headline .= ' - '.$category->getValue('cat_name');
}

// New Modulelist object
$lists = new ModuleLists();
$lists->setParameter('cat_id', $category->getValue('cat_id'));
$lists->setParameter('role_type', (int) $getRoleType);

// create html page object
$page = new HtmlPage('admidio-groups-roles', $headline);

// add filter navbar
$page->addJavascript(
    '
    $("#cat_uuid").change(function() {
        $("#navbar_filter_form").submit();
    });
    $("#role_type").change(function() {
        $("#navbar_filter_form").submit();
    });',
    true
);

// create filter menu with elements for category
$filterNavbar = new HtmlNavbar('navbar_filter', '', null, 'filter');
$form = new HtmlForm('navbar_filter_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/list_roles.php', $page, array('type' => 'navbar', 'setFocus' => false));
$form->addSelectBoxForCategories(
    'cat_uuid',
    $gL10n->get('SYS_CATEGORY'),
    $gDb,
    'ROL',
    HtmlForm::SELECT_BOX_MODUS_FILTER,
    array('defaultValue' => $getCatUuid)
);
if ($gCurrentUser->manageRoles()) 
{
    $form->addSelectBox(
        'role_type',
        $gL10n->get('SYS_ROLE_TYPES'),
        array(1 => $gL10n->get('SYS_INACTIVE_GROUPS_ROLES'), 2 => $gL10n->get('SYS_ACTIVE_GROUPS_ROLES'), 3 => $gL10n->get('SYS_ROLES_CONFIRMATION_OF_PARTICIPATION')),
        array('defaultValue' => $getRoleType+1, 'showContextDependentFirstEntry' => false)
    );
}
$filterNavbar->addForm($form->show());
$page->addHtml($filterNavbar->show());

$previousCategoryId = 0;

// Get Lists
$listsResult = $lists->getDataSet(0,0);

if ($listsResult['totalCount'] === 0) 
{
    // If login valid, than show message for non available roles
    if ($getRoleType === ROLE_TYPE_ACTIVE) 
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS_VIEW_LIST'));
        // => EXIT
    } 
    else 
    {
        $gMessage->show($gL10n->get('SYS_NO_ROLES_VISIBLE'));
        // => EXIT
    }
}

// Create table
$table = new HtmlTable('roles_table', $page, true, true);

// create array with all column heading values
$columnHeading = array(
    $gL10n->get('SYS_CATEGORY'),
    $gL10n->get('SYS_GROUPS_ROLES'),
    $gL10n->get('SYS_DESCRIPTION'),
    $gL10n->get('SYS_CONTRIBUTION'),
    '<i class="fas fa-user" data-toggle="tooltip" title="'.$gL10n->get('SYS_ROLE_MEMBERS').'"></i>
    (<i class="fas fa-user-times" data-toggle="tooltip" title="'.$gL10n->get('SYS_FORMER_PL').'"></i>)',
    '<i class="fas fa-user-graduate" data-toggle="tooltip" title="'.$gL10n->get('SYS_LEADER').'"></i>',
    '&nbsp;'
);

$table->setColumnAlignByArray(array('left', 'left', 'left', 'left', 'left', 'left', 'right'));
$table->disableDatatablesColumnsSort(array(7));
$table->setDatatablesColumnsNotHideResponsive(array(7));
$table->setDatatablesGroupColumn(1);
$table->addRowHeadingByArray($columnHeading);

// Create role object
$role = new TableRoles($gDb);

foreach ($listsResult['recordset'] as $row) 
{
    // Put data to Roleobject
    $role->setArray($row);
    
    $catId = (int) $role->getValue('cat_id');
    $rolId = (int) $role->getValue('rol_id');
    $roleUuid = $role->getValue('rol_uuid');
    $rolName = $role->getValue('rol_name');
   
    $roleDescription = '';

    if (strlen($role->getValue('rol_description')) > 0) 
    {
        $roleDescription = strip_tags($role->getValue('rol_description'));

        if (strlen($roleDescription) > 50) 
        {
            // read first 200 chars of text, then search for last space and cut the text there. After that add a "more" link
            $textPrev = substr($roleDescription, 0, 50);
            $maxPosPrev = strrpos($textPrev, ' ');
            $roleDescription = substr($textPrev, 0, $maxPosPrev).
                            ' <span class="collapse" id="viewdetails-'.$roleUuid.'">'.substr($roleDescription, $maxPosPrev).'.
                            </span> <a class="admidio-icon-link" data-toggle="collapse" data-target="#viewdetails-'.$roleUuid.'"><i class="fas fa-angle-double-right" data-toggle="tooltip" title="'.$gL10n->get('SYS_MORE').'"></i></a>';
        }
    }

    $roleContribution = '';
    
    // show members fee
    if (strlen((string) $role->getValue('rol_cost')) > 0 || $role->getValue('rol_cost_period') > 0) 
    {
        // Member fee
        if (strlen($role->getValue('rol_cost')) > 0) 
        {
            $roleContribution .= (float) $role->getValue('rol_cost').' '.$gSettingsManager->getString('system_currency');
        }

        // Contributory period
        if (strlen($role->getValue('rol_cost_period')) > 0 && $role->getValue('rol_cost_period') != 0) 
        {
            $periodsArr = TableRoles::getCostPeriods();
            $roleContribution .= ' - ' . $periodsArr[$role->getValue('rol_cost_period')];
        }
    }
    
    // show count of members and leaders of this role
    $numMember = '';
    $numLeader = '';
    $numMember .= $row['num_members'];

    if ($gCurrentUser->hasRightViewFormerRolesMembers($rolId) && $getRoleType === ROLE_TYPE_ACTIVE && $row['num_former'] > 0) 
    {
        // show former members
        $numMember .=  ' ('.$row['num_former'].')';
    }

    if ($row['num_leader'] > 0) 
    {
        $numLeader =  $row['num_leader'] ;
    }

    $iconLinks = '';
    
    // send a mail to all role members
    if ($gCurrentUser->hasRightSendMailToRole($rolId) && $gSettingsManager->getBool('enable_mail_module')) 
    {
        $iconLinks .= '<a class="admidio-icon-link" href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/messages/messages_write.php', array('role_uuid' => $roleUuid)).'">'.
                    '<i class="fas fa-envelope" data-toggle="tooltip" title="'.$gL10n->get('SYS_EMAIL_TO_MEMBERS').'"></i></a>';
    }

    // link to assign or remove members if you are allowed to do it
    if ($role->allowedToAssignMembers($gCurrentUser)) 
    {
        $iconLinks .= '<a class="admidio-icon-link" href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/members_assignment.php', array('role_uuid' => $roleUuid)).'">'.
                            '<i class="fas fa-user-plus" data-toggle="tooltip" title="'.$gL10n->get('SYS_ASSIGN_MEMBERS').'"></i></a>';
    }
    
    // edit roles of you are allowed to assign roles
    if ($gCurrentUser->manageRoles()) 
    {
        if ($getRoleType === ROLE_TYPE_INACTIVE) 
        {
            $iconLinks .= '<a class="admidio-icon-link openPopup" href="javascript:void(0);"
                                data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.'/system/popup_message.php', array('type' => 'rol_enable', 'element_id' => 'row_'.$roleUuid, 'name' => $rolName, 'database_id' => $roleUuid)).'">'.
                                '<i class="fas fa-check-square" data-toggle="tooltip" title="'.$gL10n->get('SYS_ACTIVATE_ROLE').'"></i></a>';
        } 
        elseif ($getRoleType === ROLE_TYPE_ACTIVE) 
        {
            $iconLinks .= '<a class="admidio-icon-link openPopup" href="javascript:void(0);"
                                data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.'/system/popup_message.php', array('type' => 'rol_disable', 'element_id' => 'row_'.$roleUuid, 'name' => $rolName, 'database_id' => $roleUuid)).'">'.
                                '<i class="fas fa-ban" data-toggle="tooltip" title="'.$gL10n->get('SYS_DEACTIVATE_ROLE').'"></i></a>';
        }
    }
    
    $iconLinks .= '<a class="admidio-icon-link" href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/groups_roles_new.php', array('role_uuid' => $roleUuid)).'">'.
                        '<i class="fas fa-edit" data-toggle="tooltip" title="'.$gL10n->get('SYS_EDIT_ROLE').'"></i></a>';
    $iconLinks .= '<a class="admidio-icon-link openPopup" href="javascript:void(0);"
                         data-href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.'/system/popup_message.php', array('type' => 'rol', 'element_id' => 'row_'.$roleUuid, 'name' => $rolName, 'database_id' => $roleUuid)).'">'.
                         '<i class="fas fa-trash-alt" data-toggle="tooltip" title="'.$gL10n->get('SYS_DELETE_ROLE').'"></i></a>';

    // create array with all column values
    $columnValues = array(
        array('value' => $role->getValue('cat_name'), 'order' => (int) $role->getValue('cat_sequence')),
        '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/lists_show.php', array('mode' => 'html', 'rol_ids' => $rolId)).'">'.$rolName.'</a>',
        $roleDescription,
        $roleContribution,
        $numMember,
        $numLeader,
        $iconLinks
    );

    $table->addRowByArray($columnValues, 'row_'. $roleUuid);
}

$page->addHtml($table->show());
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
        $GLOBALS['gLogger']->notice('ListRoles: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $GLOBALS['gLogger']->notice('ListRoles: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($GLOBALS['gL10n']->get('PLG_LIST_ROLES_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
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

