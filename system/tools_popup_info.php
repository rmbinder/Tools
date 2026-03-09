<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Informationen
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:      none
 *****************************************************************************/

 use Admidio\Infrastructure\Exception;
 
try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // set headline of the script
    $headline = $gL10n->get('SYS_INFORMATIONS');

    $infoText = '
        <div class="row">
            <a>'     . $gL10n->get('PLG_TOOLS_INFO1') . '</a> 
            <a><br>' . $gL10n->get('PLG_TOOLS_INFO2') . '</a> 
            <a><br>' . $gL10n->get('PLG_TOOLS_INFO3') . '</a> 
        </div>';

    $gMessage->showInModalWindow();
    $gMessage->show($infoText, $headline);
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
