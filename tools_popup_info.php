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

require_once(__DIR__ . '/../../system/common.php');

// set headline of the script
$headline = $gL10n->get('SYS_INFORMATIONS');

// create html page object
$page = new HtmlPage('plg-tools-info', $headline);

header('Content-type: text/html; charset=utf-8');

$form = new HtmlForm('plugin_informations_form', '', $page);
$form->addHtml('
    <div class="modal-header">
        <h3 class="modal-title">'.$headline.'</h3>
        <button type="button" class="close" data-bs-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
    ');

$form->addDescription($gL10n->get('PLG_TOOLS_INFO1'));
$form->addDescription($gL10n->get('PLG_TOOLS_INFO2'));
$form->addDescription($gL10n->get('PLG_TOOLS_INFO3'));

$form->addHtml('</div>');
echo $form->show();
