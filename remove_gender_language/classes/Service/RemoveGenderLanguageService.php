<?php
namespace Plugins\Tools\remove_gender_language\classes\Service;

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Language;
use Admidio\Infrastructure\Utils\FileSystemUtils;

/**
 *
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 *
 * RemoveGenderLanguageService is a modified (Admidio)PreferencesService
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class RemoveGenderLanguageService
{

    /**
     * Save the data.
     *
     * @param array $formData
     *            All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(array $formData)
    {
        global $gL10n, $gCurrentSession;

        // Einbinden der Konfigurationsdatei (darin ist die Sprachdatei definiert)
        include (ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/config.php');

        $languageFilePath = ADMIDIO_PATH . FOLDER_LANGUAGES . '/' . $languageFileName . '.xml';

        if (isset($formData['btn_create'])) {
            $mode = 'create';
        } elseif (isset($formData['btn_restore_or_delete']) && $formData['sct_restore_or_delete'] == 'restore') {
            $mode = 'restore';
        } elseif (isset($formData['btn_restore_or_delete']) && $formData['sct_restore_or_delete'] == 'delete') {
            $mode = 'delete';
        } elseif (isset($formData['btn_replace'])) {
            $mode = 'replace';
        } else {
            $mode = '';
        }

        $result = $gL10n->get('SYS_ERROR');

        switch ($mode) {

            case 'create':
                $languageBackupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/' . $languageFileName . '_' . ADMIDIO_VERSION_TEXT . '_' . DATE_NOW . '.xml';

                try {
                    FileSystemUtils::copyFile($languageFilePath, $languageBackupFilePath, array(
                        'overwrite' => true
                    ));
                } catch (\RuntimeException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                } catch (\UnexpectedValueException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }
                $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_CREATED');
                break;

            case 'restore':
                $backupFile = $formData['backup_file'];
                $backupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/' . $backupFile;

                try {
                    FileSystemUtils::copyFile($backupFilePath, $languageFilePath, array(
                        'overwrite' => true
                    ));
                } catch (\RuntimeException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                } catch (\UnexpectedValueException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }
                $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_RESTORED');
                break;

            case 'delete':
                $backupFile = $formData['backup_file'];
                $backupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/' . $backupFile;

                try {
                    FileSystemUtils::deleteFileIfExists($backupFilePath); // Rückgabe true or false auswerten
                } catch (\RuntimeException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                } catch (\UnexpectedValueException $exception) {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }
                $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_BACKUP_FILE_DELETED');
                break;

            case 'replace':
                include (ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER . '/replacements.php');

                $use_errors = libxml_use_internal_errors(true);
                try {
                    $xmlLanguageObjects = new \SimpleXMLElement($languageFilePath, 0, true);
                } catch (Exception $e) {
                    $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_OPEN');
                }

                if ($result !== $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_OPEN')) {
                    for ($i = 0; $i < count($xmlLanguageObjects->string); $i ++) {
                        $textId = (string) $xmlLanguageObjects->string[$i]['name'];

                        foreach ($replacements as $search => $replace) {
                            if (Language::isTranslationStringId($search)) {
                                if ($search === $textId) {
                                    $xmlLanguageObjects->string[$i] = $replace;
                                    continue;
                                }
                            } else {
                                $xmlLanguageObjects->string[$i] = str_replace($search, $replace, (string) $xmlLanguageObjects->string[$i]);
                            }
                        }
                    }

                    if (! is_writable($languageFilePath)) {
                        $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_SAVE');
                    } else {
                        if ($xmlLanguageObjects->asXML($languageFilePath) === false) {
                            $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_REPLACE_ERROR_SAVE');
                        } else {
                            $result = $gL10n->get('PLG_REMOVE_GENDER_LANGUAGE_UPDATED');
                        }
                    }
                }

                libxml_clear_errors();
                libxml_use_internal_errors($use_errors);
                break;
        }

        return $result;

        // clean up
        $gCurrentSession->reloadAllSessions();
    }
}
