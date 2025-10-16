<?php
/**
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 *
 * DocumentsPreferencesService is a modified (Admidio)PreferencesService
 *
 * @copyright rmb
 * @see https://github.com/rmbinder/documents/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */

namespace Plugins\Tools\classes\Service;

use Admidio\Infrastructure\Exception;
use Plugins\Tools\classes\Config\ConfigTable;

class ToolsPreferencesService
{

    /**
     * Save all form data of the panel to the database.
     * @param string $panel Name of the panel for which the data should be saved.
     * @param array $formData All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(string $panel, array $formData)
    {
        global $gL10n;
        
        require_once(__DIR__ . '/../../system/common_function.php');
        $pPreferences = new ConfigTable();
        $pPreferences->read();
        
        $result =  $gL10n->get('SYS_SAVE_DATA');

        // first check the fields of the submitted form
        switch ($panel) {
            
            case 'Settings':
 
           	break; 

        }
        $pPreferences->save();
        return $result;

        // clean up
        $gCurrentSession->reloadAllSessions();
    }
}
