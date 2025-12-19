<?php
namespace Plugins\Tools\list_roles\classes\Presenter;

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
use Admidio\Roles\ValueObject\RoleDependency;
use Admidio\Roles\Service\RolesService;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Changelog\Service\ChangelogService;

/**
 * @brief Class with methods to display the module pages and helpful functions.
 *
 * This class adds some functions that are used in the groups and roles module to keep the
 * code easy to read and short
 *
 * **Code example**
 * ```
 * // generate html output with available registrations
 * $page = new ModuleGroupsRoles('admidio-groups-roles', $headline);
 * $page->createRegistrationList();
 * $page->show();
 * ```
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class ListRolesPresenter extends PagePresenter
{
    /**
     * @var int Type of the role e.g. ROLE_TYPE_INACTIVE, ROLE_TYPE_ACTIVE, ROLE_TYPE_EVENT_PARTICIPATION
     */
    public const ROLE_TYPE_INACTIVE = 0;
    public const ROLE_TYPE_ACTIVE = 1;
    public const ROLE_TYPE_EVENT_PARTICIPATION = 2;
    
    /**
     * Show all roles of the organization in card view. The roles must be read before with the method readData.
     * The cards will show various functions like activate, deactivate, vcard export, edit or delete. Also, the
     * role information e.g. description, start and end date, number of active and former members. A button with
     * the link to the default list will be shown.
     * @param string $categoryUUID UUID of the category for which the roles should be shown.
     * @param string $roleType The type of roles that should be shown within this page.
     *                         0 - inactive roles
     *                         1 - active roles
     *                         2 - event participation roles
     * @throws \Smarty\Exception|Exception
     * @throws Exception
     */
    public function createList(string $categoryUUID, string $roleType): void
    {
        global $gSettingsManager, $gL10n, $gDb, $gCurrentSession, $gCurrentUser;

        $templateData = array();
        $this->createHeader($categoryUUID, $roleType);
        $this->setContentFullWidth();
        
        $rolesService = new RolesService($gDb);
        $data = $rolesService->findAll($roleType, $categoryUUID);

        foreach ($data as $row) {
            $role = new Role($gDb);
            $role->setArray($row);

            $templateRow = array();
            $templateRow['category'] = $role->getValue('cat_name');
            $templateRow['categoryOrder'] = $role->getValue('cat_sequence');
            $templateRow['role'] = $role->getValue('rol_name');
            $templateRow['roleUUID'] = $role->getValue('rol_uuid');
            $templateRow['roleUrl'] = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'edit', 'role_uuid' => $row['rol_uuid']));
    
            $roleDescription = '';
            if (strlen($role->getValue('rol_description')) > 0)
            {
                $roleDescription = strip_tags($role->getValue('rol_description'));
                
                if (strlen($roleDescription) > 50)
                {
                    // read first 50 chars of text, then search for last space and cut the text there. After that add a "more" link
                    $textPrev = substr($roleDescription, 0, 50);
                    $maxPosPrev = strrpos($textPrev, ' ');
                    $roleDescription = substr($textPrev, 0, $maxPosPrev).
                    ' <span class="collapse" id="viewdetails-'.$row['rol_uuid'].'">'.substr($roleDescription, $maxPosPrev).'.
                            </span> <a class="admidio-icon-link" data-bs-toggle="collapse" data-bs-target="#viewdetails-'.$row['rol_uuid'].'"><i class="bi bi-chevron-double-right" data-bs-toggle="tooltip" title="'.$gL10n->get('SYS_MORE').'"></i></a>';
                }
            }
            $templateRow['roleDescription'] = $roleDescription;
            
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
                    $periodsArr = Role::getCostPeriods();
                    $roleContribution .= ' - ' . $periodsArr[$role->getValue('rol_cost_period')];
                }
            }
            $templateRow['roleContribution'] = $roleContribution;
            
            // show count of members and leaders of this role
            $numMember = '';
            $numLeader = '';
            $numMember .= $row['num_members'];
            
            if ($gCurrentUser->hasRightViewFormerRolesMembers((int) $role->getValue('rol_id')) && $roleType == $this::ROLE_TYPE_ACTIVE && $row['num_former'] > 0)
            {
                // show former members
                $numMember .=  ' ('.$row['num_former'].')';
            }
            $templateRow['numMember'] = $numMember;
            
            if ($row['num_leader'] > 0)
            {
                $numLeader =  $row['num_leader'] ;
            }
            $templateRow['numLeader'] = $numLeader;
            
            $templateRow['actions'][] = array(
                'url' => SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/messages/messages_write.php', array('role_uuid' => $row['rol_uuid'])),
                'icon' => 'bi bi-envelope',
                'tooltip' => $gL10n->get('SYS_EMAIL_TO_MEMBERS')
            );
            
            // show link to export vCard if user is allowed to see the profiles of members and the role has members
            if ($gCurrentUser->hasRightViewProfiles($row['rol_id'])
                && ($row['num_members'] > 0 || $row['num_leader'] > 0)) {
                    $templateRow['actions'][] = array(
                        'url' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'export', 'role_uuid' => $row['rol_uuid'])),
                        'icon' => 'bi bi-download',
                        'tooltip' => $gL10n->get('SYS_EXPORT_VCARD')
                    );
                }
                
            $templateRow['actions'][] = array(
                'url' => SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/members_assignment.php', array('role_uuid' => $row['rol_uuid'])),
                'icon' => 'bi bi-person-plus',
                'tooltip' => $gL10n->get('SYS_ASSIGN_MEMBERS')
            );

            $templateRow['actions'][] = array(
                'url' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/lists_show.php', array('mode' => 'html', 'role_list' => $row['rol_uuid'])),
                'icon' => 'bi bi-card-list',
                'tooltip' => $gL10n->get('SYS_SHOW_ROLE_MEMBERSHIP')
            );
       
            if ($roleType == $this::ROLE_TYPE_INACTIVE && !$role->getValue('rol_administrator')) {
                $templateRow['actions'][] = array(
                    'dataHref' => 'callUrlHideElement(\'role_' . $row['rol_uuid'] . '\', \'' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'activate', 'role_uuid' => $row['rol_uuid'])) . '\', \'' . $gCurrentSession->getCsrfToken() . '\')',
                    'dataMessage' => $gL10n->get('SYS_ACTIVATE_ROLE_DESC', array($row['rol_name'])),
                    'icon' => 'bi bi-eye',
                    'tooltip' => $gL10n->get('SYS_ACTIVATE_ROLE')
                );
            } elseif ($roleType == $this::ROLE_TYPE_ACTIVE && !$role->getValue('rol_administrator')) {
                $templateRow['actions'][] = array(
                    'dataHref' => 'callUrlHideElement(\'role_' . $row['rol_uuid'] . '\', \'' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'deactivate', 'role_uuid' => $row['rol_uuid'])) . '\', \'' . $gCurrentSession->getCsrfToken() . '\')',
                    'dataMessage' => $gL10n->get('SYS_DEACTIVATE_ROLE_DESC', array($row['rol_name'])),
                    'icon' => 'bi bi-eye-slash',
                    'tooltip' => $gL10n->get('SYS_DEACTIVATE_ROLE')
                );
            }
            
            if (!$role->getValue('rol_administrator')) {
                $templateRow['actions'][] = array(
                    'dataHref' => 'callUrlHideElement(\'role_' . $row['rol_uuid'] . '\', \'' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'delete', 'role_uuid' => $row['rol_uuid'])) . '\', \'' . $gCurrentSession->getCsrfToken() . '\')',
                    'dataMessage' => $gL10n->get('SYS_DELETE_ENTRY', array($row['rol_name'])),
                    'icon' => 'bi bi-trash',
                    'tooltip' => $gL10n->get('SYS_DELETE_ROLE')
                );
            }
            $templateData[] = $templateRow;
        }

        // initialize and set the parameter for DataTables
        $dataTables = new DataTables($this, 'adm_role_permissions_table');
        $dataTables->setGroupColumn(1);
        $dataTables->disableColumnsSort(array( 7));
        $dataTables->setColumnsNotHideResponsive(array(7));
        $dataTables->createJavascript(count($data), 6);

        $this->smarty->assign('list', $templateData);
        $this->smarty->assign('l10n', $gL10n);
        
        $this->smarty->assign('members', '<i class="bi bi-person-fill" data-bs-toggle="tooltip" title="'.$gL10n->get('SYS_ROLE_MEMBERS').'"></i>
                                             (<i class="bi bi-person-x-fill" data-bs-toggle="tooltip" title="'.$gL10n->get('SYS_FORMER_PL').'"></i>)');
       
        $this->smarty->assign('leader', '<i class="bi bi-mortarboard" data-bs-toggle="tooltip" title="'.$gL10n->get('SYS_LEADER').'"></i>');
        
        $this->pageContent .= $this->smarty->fetch('templates/listroles.plugin.tools.subplugin.listroles.tpl');
    }

    /**
     * Create content that is used on several pages and could be called in other methods. It will
     * create a functions menu and a filter navbar.
     * @param string $categoryUUID UUID of the category for which the roles should be shown.
     * @param string $roleType The type of roles that should be shown within this page.
     *                         0 - inactive roles
     *                         1 - active roles
     *                         2 - event participation roles
     *
     * @return void
     * @throws Exception
     */
    protected function createHeader(string $categoryUUID, string $roleType): void
    {
        global $gCurrentUser, $gSettingsManager, $gL10n, $gDb;

        // add filter navbar
        $this->addJavascript('
            $("#cat_uuid").change(function() {
                $("#adm_navbar_filter_form").submit();
            });
            $("#role_type").change(function() {
                $("#adm_navbar_filter_form").submit();
            });',
            true
        );

        // create filter menu with elements for category
        $form = new FormPresenter(
            'adm_navbar_filter_form',
            'sys-template-parts/form.filter.tpl',
            ADMIDIO_URL.FOLDER_PLUGINS.'/Tools/list_roles/list_roles.php',
            $this,
            array('type' => 'navbar', 'setFocus' => false)
        );

        $form->addSelectBoxForCategories(
            'cat_uuid',
            $gL10n->get('SYS_CATEGORY'),
            $gDb,
            'ROL',
            FormPresenter::SELECT_BOX_MODUS_FILTER,
            array('defaultValue' => $categoryUUID)
        );
        if ($gCurrentUser->isAdministratorRoles()) {
            $form->addSelectBox(
                'role_type',
                $gL10n->get('SYS_ROLE_TYPES'),
                array(0 => $gL10n->get('SYS_INACTIVE_GROUPS_ROLES'), 1 => $gL10n->get('SYS_ACTIVE_GROUPS_ROLES'), 2 => $gL10n->get('SYS_ROLES_CONFIRMATION_OF_PARTICIPATION')),
                array('defaultValue' => $roleType, 'showContextDependentFirstEntry' => false)
            );
        }
        $form->addToHtmlPage();
    }
}
