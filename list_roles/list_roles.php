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
use Admidio\Categories\Entity\Category;
use Admidio\Components\Entity\Component;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Roles\Entity\RolesRights;
use Admidio\Roles\Service\RolesService;
use Plugins\Tools\list_roles\classes\Presenter\ListRolesPresenter;

try {
    require_once(__DIR__ . '/../../../system/common.php');
    require_once(__DIR__ . '/system/common_function.php');
    
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
    
    // Initialize and check the parameters
    $getCategoryUUID = admFuncVariableIsValid($_GET, 'cat_uuid', 'uuid');
    $getRoleUUID     = admFuncVariableIsValid($_GET, 'role_uuid', 'uuid');
    $getRoleType     = admFuncVariableIsValid($_GET, 'role_type', 'int', array('defaultValue' => 1));

    // check if the module is enabled and disallow access if it's disabled
    if (!$gSettingsManager->getBool('groups_roles_module_enabled')) {
        throw new Exception('SYS_MODULE_DISABLED');
    }
    
    // only users with the special right are allowed to manage roles
    if (!$gCurrentUser->isAdministratorRoles()) {
        //throw new Exception('SYS_NO_RIGHTS');                     // über Exception wird nur SYS_NO_RIGHTS angezeigt
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }

    $headline = $gL10n->get('PLG_LIST_ROLES_NAME');

    // only users with the right to assign roles can view inactive roles
    if (!$gCurrentUser->checkRolesRight('rol_assign_roles')) {
        $getRoleType = ListRolesPresenter::ROLE_TYPE_ACTIVE;
    }

    $category = new Category($gDb);

    //if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
    $navStack = $gNavigation->getStack();
    if (!(StringUtils::strContains($navStack[0]['url'], PLUGIN_PARENT_FOLDER.'/index.php', false)))
    {
        //$scriptName ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/formfiller...
        $scriptName = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));
        
        // only authorized user are allowed to start this module
        if (!isUserAuthorized($scriptName))
        {
            $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
        }
        $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-stack');
    }
    else
    {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }
    
    // create html page object
    $groupsRoles = new ListRolesPresenter('adm_groups_roles', $headline);

    $rolesService = new RolesService($gDb);
    $data = $rolesService->findAll($getRoleType, $getCategoryUUID);

    if (count($data) === 0) {
        if ($gValidLogin) {
            // If login valid, then show message for not available roles
            if ($getRoleType === ListRolesPresenter::ROLE_TYPE_ACTIVE) {
                $gMessage->show($gL10n->get('SYS_NO_RIGHTS_VIEW_LIST'));
                // => EXIT
            } else {
                $gMessage->show($gL10n->get('SYS_NO_ROLES_VISIBLE'));
                // => EXIT
            }
        } else {
            // forward to login page
            require(__DIR__ . '/../../../system/login_valid.php');
        }
    }

    $groupsRoles->createList($getCategoryUUID, $getRoleType);
    $groupsRoles->show();
} catch (Throwable $e) {
    $gMessage->show($e->getMessage());
}


/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung wird die Einstellung von 'Sichtbar für' verwendet,
 * die im Modul Menü für dieses Plugin gesetzt wurde.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
    global $gDb, $gMessage, $gLogger, $gL10n, $gCurrentUser;
    
    $userIsAuthorized = false;
    $menId = 0;
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
    
    $menuStatement = $gDb->queryPrepared($sql, array($scriptName));
    
    if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
    {
        $gLogger->notice('ListRoles: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('ListRoles: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_LIST_ROLES_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
    }
    else
    {
        while ($row = $menuStatement->fetch())
        {
            $menId = (int) $row['men_id'];
        }
    }
    
    // read current roles rights of the menu
    $displayMenu = new RolesRights($gDb, 'menu_view', $menId);
    
    // check for right to show the menu
    if (count($displayMenu->getRolesIds()) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
    {
        $userIsAuthorized = true;
    }
    return $userIsAuthorized;
}

