<?php
/**
 ***********************************************************************************************
 * login_as
 *
 * "login as" procedure for the admidio plugin user_info
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

global $gCurrentUser, $gMenu, $gDb, $gL10n, $gCurrentOrganization, $gProfileFields, $gCurrentSession,  $gSettingsManager,  $gSessionId;

// Initialize and check the parameters
$getUsrId = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0));

// remove all menu entries
$gMenu->initialize();

// create user object
$gCurrentUser = new User($gDb, $gProfileFields, (int) $getUsrId);

$gCurrentSession->setValue('ses_usr_id', (int) $gCurrentUser->getValue('usr_id'));
$gCurrentSession->save();

$gCurrentUser->setValue('usr_last_session_id', null);

// set cookie for session id
Session::setCookie(COOKIE_PREFIX . '_SESSION_ID', $gSessionId);

// count logins and update login dates
$gCurrentUser->saveChangesWithoutRights();
$gCurrentUser->updateLoginData();

// If no forward url has been set, then refer to the start page after login
if (array_key_exists('login_forward_url', $_SESSION))
{
    $forwardUrl = $_SESSION['login_forward_url'];
}
else
{
    $forwardUrl = ADMIDIO_URL . '/' . $gSettingsManager->getString('homepage_login');
}

unset($_SESSION['login_forward_url']);

admRedirect($forwardUrl);

