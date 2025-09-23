<?php
/**
 ***********************************************************************************************
 * delete_contacts
 *
 * 
 * This plugin for Admidio allows you to delete multiple contacts at once.
 *
 * Author: rmb
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

use Admidio\Components\Entity\Component;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Roles\Entity\Membership;
use Admidio\Roles\Entity\Role;
use Admidio\Roles\Entity\RolesRights;
use Admidio\Users\Entity\User;

require_once(__DIR__ . '/../../../system/common.php');

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

$headline = $gL10n->get('PLG_DELETE_CONTACTS_PLUGIN_NAME');

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
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'fa-globe');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// only administrators are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode      = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'delete')));
$postRole     = admFuncVariableIsValid($_POST, 'selection_role', 'int');

$postDeletionType = 'former';
if (isset($_POST['btn_remove_contact']))
{
    $postDeletionType = 'delete';
}

$page = new HtmlPage('plg-delete-contacts-main', $headline);

$page->addHtml('<strong>'.$gL10n->get('PLG_DELETE_CONTACTS_DESC').'</strong><br><br>');

if ($getMode == 'start')     //Default
{
    $form = new HtmlForm('delete_contacts_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/delete_contacts.php', array('mode' => 'delete')), $page);
        
    $sql = 'SELECT rol_id, rol_name, cat_name
              FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.'
             WHERE cat_id = rol_cat_id
               AND ( cat_org_id = '.$gCurrentOrgId.'
                OR cat_org_id IS NULL )
          ORDER BY cat_sequence, rol_name';
    $form->addSelectBoxFromSql('selection_role', $gL10n->get('SYS_ROLE'), $gDb, $sql, array('property' => HtmlForm::FIELD_REQUIRED, 'helpTextIdInline' => 'PLG_DELETE_CONTACTS_ROLE_SELECTION_DESC'));

    $form->addStaticControl('make_former', '', $gL10n->get('PLG_DELETE_CONTACTS_MAKE_FORMER_DESC'));
    $form->addSubmitButton('btn_make_former', $gL10n->get('SYS_FORMER_PL'), array('icon' => 'fa-user-clock', 'class' => 'offset-sm-3'));

    $form->addStaticControl('remove_contact', '', $gL10n->get('PLG_DELETE_CONTACTS_REMOVE_CONTACT_DESC'));
    $form->addSubmitButton('btn_remove_contact', $gL10n->get('SYS_DELETE'), array('icon' => 'fa-trash-alt', 'class' => 'offset-sm-3'));

    $page->addHtml($form->show(false));
}
elseif ($getMode == 'delete')
{
    $sql = 'SELECT mem_usr_id
              FROM ' . TBL_MEMBERS . '
        INNER JOIN '.TBL_ROLES.'
                ON rol_id = mem_rol_id
        INNER JOIN '.TBL_CATEGORIES.'
                ON cat_id = rol_cat_id
             WHERE rol_valid  = true
               AND ( cat_org_id = ? -- $gCurrentOrgId
                   OR cat_org_id IS NULL )
               AND  mem_rol_id  = ? -- $postRole
               AND ? BETWEEN mem_begin AND mem_end -- DATE_NOW ';                                         
    $pdoStatement = $gDb->queryPrepared($sql, array($gCurrentOrgId, $postRole, DATE_NOW)); 
    
    $userIds = array();
    while ($row = $pdoStatement->fetch())
    {
        $userIds[] = $row['mem_usr_id'];
    }
    
    // Create user-object
    $user = new User($gDb, $gProfileFields);
    $message = '';
    
    if (count($userIds) === 0)
    {
        $message .= '<strong>'.$gL10n->get('PLG_DELETE_CONTACTS_ROLE_HAS_NO_MEMBER').'</strong><br/><br/>';
    }
    
    foreach ($userIds as $userID)
    {
        $user->readDataById($userID);
        
        if ($gCurrentUserId === (int) $user->getValue('usr_id'))
        {
            $message .= '<strong>'.$gL10n->get('PLG_DELETE_CONTACTS_USER_IS_YOURSELF', array($user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME'))).'</strong><br/>';
            continue;
        }
        elseif ($user->isAdministrator())
        {
            $message .= '<strong>'.$gL10n->get('PLG_DELETE_CONTACTS_USER_IS_ADMIN', array($user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME'))).'</strong><br/>';
            continue;
        }
        elseif ($postDeletionType === 'former') 
        {
            try 
            {
                $member = new Membership($gDb);
                    
                $sql = 'SELECT mem_id, mem_rol_id, mem_usr_id, mem_begin, mem_end, mem_leader
                          FROM '.TBL_MEMBERS.'
                    INNER JOIN '.TBL_ROLES.'
                            ON rol_id = mem_rol_id
                    INNER JOIN '.TBL_CATEGORIES.'
                            ON cat_id = rol_cat_id
                         WHERE rol_valid  = true
                           AND ( cat_org_id = ? -- $gCurrentOrgId
                                OR cat_org_id IS NULL )
                           AND mem_begin <= ? -- DATE_NOW
                           AND mem_end    > ? -- DATE_NOW
                           AND mem_usr_id = ? -- $user->getValue(\'usr_id\')';
                $pdoStatement = $gDb->queryPrepared($sql, array($gCurrentOrgId, DATE_NOW, DATE_NOW, $user->getValue('usr_id')));
                    
                while ($row = $pdoStatement->fetch()) 
                {
                    // stop all role memberships of this organization
                    $role = new Role($gDb, $row['mem_rol_id']);
                    $role->stopMembership($row['mem_usr_id']);
                }
                $message .= $gL10n->get('SYS_END_MEMBERSHIP_OF_USER_OK', array($user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME'), $gCurrentOrganization->getValue('org_longname'))).'<br/>';
            } 
            catch (AdmException|Exception $e) 
            {
                $message .= $e->getMessage();
            }
        } 
        elseif ($postDeletionType === 'delete') 
        {
            try 
            {
                $username = $user->getValue('FIRST_NAME') . ' ' . $user->getValue('LAST_NAME');
                
                // Delete user from database
                $user->delete();

                $message .= $gL10n->get('PLG_DELETE_CONTACTS_USER_DELETE_OK', array($username)).'<br/>';
            } 
            catch (AdmException|Exception $e) 
            {
                $message .= $e->getMessage();
            }
        }
    }
        
    $page->addHtml($message);
}
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
        $GLOBALS['gLogger']->notice('DeleteContacts: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $GLOBALS['gLogger']->notice('DeleteContacts: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($GLOBALS['gL10n']->get('PLG_DELETE_CONTACTS_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
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



