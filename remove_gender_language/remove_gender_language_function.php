<?php
/**
 ***********************************************************************************************
 * remove_gender_language_function
 * 
 * Functions for plugin remove_gender_language.
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Language;
use Admidio\Infrastructure\Utils\FileSystemUtils;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/../system/common_function.php');

//Einbinden der Konfigurationsdatei (darin ist die Sprachdatei definiert)
include(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/config.php');

$gMessage->showHtmlTextOnly(true);

$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'create','validValues' => array('create', 'restore_delete', 'replace')));
$postRestoreOrDelete = admFuncVariableIsValid($_POST, 'restore_or_delete', 'string', array('defaultValue' => 'delete','validValues' => array('restore', 'delete')));

$languageFilePath = ADMIDIO_PATH . FOLDER_LANGUAGES .'/'.$languageFileName.'.xml';

$ret = 'error'  ;

try
{
    switch($getMode)
    {
        case 'create':
            $languageBackupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/'.$languageFileName.'_'.ADMIDIO_VERSION_TEXT.'_'.DATE_NOW.'.xml';
            
            try
            {
                FileSystemUtils::copyFile($languageFilePath, $languageBackupFilePath, array('overwrite' => true));
            }
            catch (\RuntimeException $exception)
            {
                $gMessage->show($exception->getMessage());
                // => EXIT
            }
            catch (\UnexpectedValueException $exception)
            {
                $gMessage->show($exception->getMessage());
                // => EXIT
            }                  
            $ret = 'create'    ;
            break;
                          
        case 'restore_delete':
            $backupFile = $_POST['backup_file'];
            $backupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/'.$backupFile;
                     
            $ret = $postRestoreOrDelete ;
            
            if ($postRestoreOrDelete === 'restore')
            {
                try
                {
                    FileSystemUtils::copyFile($backupFilePath, $languageFilePath, array('overwrite' => true));
                }
                catch (\RuntimeException $exception)
                {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }
                catch (\UnexpectedValueException $exception)
                {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }        
            }
            elseif  ($postRestoreOrDelete === 'delete')
            {
                try
                {
                    FileSystemUtils::deleteFileIfExists($backupFilePath);               // Rückgabe true or false auswerten
                }
                catch (\RuntimeException $exception)
                {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }
                catch (\UnexpectedValueException $exception)
                {
                    $gMessage->show($exception->getMessage());
                    // => EXIT
                }     
            }             
            break;
            
        case 'replace':
            include( ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/replacements.php');
        
            $use_errors = libxml_use_internal_errors(true);
            try
            {
                $xmlLanguageObjects = new \SimpleXMLElement($languageFilePath, 0, true);
            }
            catch (Exception $e)
            {
                $ret = 'replace_error_open';
            }
           
            if ($ret !== 'replace_error_open')
            {
                for ($i = 0; $i < count($xmlLanguageObjects->string); $i++)
                {
                    $textId = (string) $xmlLanguageObjects->string[$i]['name'];
                    
                    foreach ($replacements as $search => $replace)
                    {
                        if (Language::isTranslationStringId($search))                
                        {
                            if ($search === $textId)                
                            {
                                $xmlLanguageObjects->string[$i] = $replace;
                                continue;
                            }
                        }
                        else
                        {
                            $xmlLanguageObjects->string[$i] = str_replace($search, $replace, (string) $xmlLanguageObjects->string[$i]);
                        }
                    }
                }

                if (!is_writable($languageFilePath))
                {
                    $ret = 'replace_error_save';
                }
                else 
                {
                    if ($xmlLanguageObjects->asXML($languageFilePath) === false)
                    {
                        $ret = 'replace_error_save';
                    }
                    else
                    {
                        $ret='replace';
                    }
                }
            }
            
            libxml_clear_errors();
            libxml_use_internal_errors($use_errors);
            
            break;
            
        default:
            $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
}
catch(AdmException $e)
{
    $e->showText();
}

echo $ret;

