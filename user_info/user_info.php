<?php
/**
 ***********************************************************************************************
 * user_info
 *
 * This plugin for Admidio shows information about registered users and offers the possibility to log in under the user's account.
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

$gNavigation->addUrl(CURRENT_URL);

$headline = $gL10n->get('PLG_USER_INFO_NAME');

// create html page object
$page = new HtmlPage('plg-user_info', $headline);

$page->addHtml($gL10n->get('PLG_USER_INFO_DESC'));

// show all registerd users
$sql = 'SELECT usr_id, CONCAT(last_name.usd_value, \', \', first_name.usd_value) AS name,
                   email.usd_value AS email, gender.usd_value AS gender, birthday.usd_value AS birthday,
                   usr_login_name, usr_last_login, usr_actual_login, usr_number_login, usr_timestamp_change, usr_timestamp_create
              FROM '.TBL_USERS.'
        INNER JOIN '.TBL_USER_DATA.' AS last_name
                ON last_name.usd_usr_id = usr_id
               AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
        INNER JOIN '.TBL_USER_DATA.' AS first_name
                ON first_name.usd_usr_id = usr_id
               AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
         LEFT JOIN '.TBL_USER_DATA.' AS email
                ON email.usd_usr_id = usr_id
               AND email.usd_usf_id = ? -- $gProfileFields->getProperty(\'EMAIL\', \'usf_id\')
         LEFT JOIN '.TBL_USER_DATA.' AS gender
                ON gender.usd_usr_id = usr_id
               AND gender.usd_usf_id = ? -- $gProfileFields->getProperty(\'GENDER\', \'usf_id\')
         LEFT JOIN '.TBL_USER_DATA.' AS birthday
                ON birthday.usd_usr_id = usr_id
               AND birthday.usd_usf_id = ? -- $gProfileFields->getProperty(\'BIRTHDAY\', \'usf_id\')
             WHERE usr_valid = 1
               AND usr_login_name <> \'\'    ';

$queryParams = array(
    $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
    $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
    $gProfileFields->getProperty('EMAIL', 'usf_id'),
    $gProfileFields->getProperty('GENDER', 'usf_id'),
    $gProfileFields->getProperty('BIRTHDAY', 'usf_id')
); 

$statement = $gDb->queryPrepared($sql, $queryParams);
	
$datatable = true;
$hoverRows = true;
$classTable  = 'table table-condensed';
$table = new HtmlTable('table_role_overview', $page, $hoverRows, $datatable, $classTable);

$columnHeading = array();
$columnAlign   = array();

// 1. spalte name
$columnHeading[] = $gL10n->get('SYS_NAME');
$columnAlign[]   = 'left';

// 2. spalte benutzername
$columnHeading[] = $gL10n->get('SYS_USERNAME');
$columnAlign[]   = 'left';

// 3. spalte geschlecht
$columnHeading[] = $gL10n->get('SYS_GENDER');
$columnAlign[]   = 'center';

// 4. spalte geburtstag
$columnHeading[] = $gL10n->get('SYS_BIRTHDAY');
$columnAlign[]   = 'left';

// 5. spalte anzahl logins
$columnHeading[] = $gL10n->get('PLG_USER_INFO_NUMBER_LOGINS');
$columnAlign[]   = 'center';

// 6. spalte letzte logins
$columnHeading[] = $gL10n->get('PLG_USER_INFO_LAST_LOGINS');
$columnAlign[]   = 'left';

// 7. spalte erstellt am
$columnHeading[] = $gL10n->get('PLG_USER_INFO_CREATED_ON');
$columnAlign[]   = 'left';

// 8. spalte geändert am
$columnHeading[] = $gL10n->get('PLG_USER_INFO_CHANGED_ON');
$columnAlign[]   = 'left';

// 9. spalte login as
$columnHeading[] = '<i class="fas fa-sign-in-alt" data-toggle="tooltip" title="'.$gL10n->get('PLG_USER_INFO_LOGIN_AS_DESC').'"></i>';
$columnAlign[]   = 'center';

$table->setColumnAlignByArray($columnAlign);
$table->disableDatatablesColumnsSort(array(3, 9));
$table->addRowHeadingByArray($columnHeading);

while ($row = $statement->fetch())
{
    $columnValues = array();
    
   // 1. spalte name
   // Add "Lastname" and "Firstname"
    $columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_id' => $row['usr_id'])).'">'.$row['name'].'</a>';
    
    // 2. spalte
    // Add "Loginname"
    if(strlen($row['usr_login_name']) > 0)
    {
        $columnValues[] = $row['usr_login_name'];
    }
    else
    {
        $columnValues[] = '';
    }
    
    // 3. palte
    // Add icon for "gender"
    if(strlen($row['gender']) > 0)
    {
        // show selected text of optionfield or combobox
        $arrListValues  = $gProfileFields->getProperty('GENDER', 'usf_value_list');
        $columnValues[] = $arrListValues[$row['gender']];
    }
    else
    {
        $columnValues[] = '';
    }
    
    // 4. spalte
    // Add "birthday"
    if(strlen($row['birthday']) > 0)
    {
        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d', $row['birthday']);
        $columnValues[] = $date->format($gSettingsManager->getString('system_date'));
    }
    else
    {
        $columnValues[] = '';
    }
 
    // 5. spalte
    // number logins
    if(strlen($row['usr_number_login']) > 0)
    {
        $columnValues[] = $row['usr_number_login'];
    }
    else
    {
        $columnValues[] = '';
    }
    
    // 6. spalte
    // letzte anmeldungen
    $tempValue = '';
    if(strlen($row['usr_actual_login']) > 0)
    {
        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $row['usr_actual_login']);
        $tempValue = $date->format($gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time'));
    }

    if(strlen($row['usr_last_login']) > 0)
    {
        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $row['usr_last_login']);
        $tempValue .= ' / '.$date->format($gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time'));
    }
    $columnValues[] = $tempValue;
    
    // 7. spalte
    // creation date
    if(strlen($row['usr_timestamp_create']) > 0)
    {
        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $row['usr_timestamp_create']);
        $columnValues[] = $date->format($gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time'));
    }
    else
    {
        $columnValues[] = '';
    }
    
    // 8. spalte
    // change
    if(strlen($row['usr_timestamp_change']) > 0)
    {
        // date must be formated
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $row['usr_timestamp_change']);
        $columnValues[] = $date->format($gSettingsManager->getString('system_date').' '.$gSettingsManager->getString('system_time'));
    }
    else
    {
        $columnValues[] = '';
    }
    
    // 9. spalte
    //login as
    if ($gCurrentUser->isAdministrator() && ($gCurrentUser->getValue('usr_id') !== $row['usr_id']))
    {
        $targetUrl = SecurityUtils::encodeUrl('login_as.php', array('usr_id' =>  $row['usr_id']));
        $columnValues[] = '<a class="admidio-icon-link" href="' . $targetUrl . '" ><i class="fas fa-sign-in-alt" data-toggle="tooltip" title="'.$gL10n->get('PLG_USER_INFO_LOGIN_AS').' '.$row['usr_login_name'].'"></i></a>';
    }
    else
    {
        $columnValues[] = '<i class="fas fa-times" data-toggle="tooltip" title="'.$gL10n->get('PLG_USER_INFO_LOGIN_AS_NOT_POSSIBLE', array($row['usr_login_name'])).'"></i>';
    }
    
    $table->addRowByArray($columnValues);
}

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
    global $gDb, $gCurrentUser, $gMessage, $gL10n, $gLogger;
    
    $userIsAuthorized = false;
    $menId = 0;
    
    $sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
    
    $menuStatement = $gDb->queryPrepared($sql, array($scriptName));
    
    if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
    {
        $gLogger->notice('MultipleMemberships: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
        $gLogger->notice('MultipleMemberships: Error with menu entry: ScriptName: '. $scriptName);
        $gMessage->show($gL10n->get('PLG_USER_INFO_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
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
    
    $menuStatement = $gDb->queryPrepared($sql, array($menId));
    while ($row = $menuStatement->fetch())
    {
        if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
        {
            // Read current roles rights of the menu
            $displayMenu = new RolesRights($gDb, 'menu_view', $row['men_id']);
            $rolesDisplayRight = $displayMenu->getRolesIds();
            
            // check for right to show the menu
            if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
            {
                $userIsAuthorized = true;
            }
        }
    }
    return $userIsAuthorized;
}
