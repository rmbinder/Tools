<?php
/**
 ***********************************************************************************************
 * remove_gender_language_function
 * 
 * Functions for plugin remove_gender_language.
 *
 * Author: rmb
 *  
 * Compatible with Admidio version 4
 *
 * @copyright 2020-2022 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../../adm_program/system/common.php');

//sowohl der plugin-Ordner, als auch der übergeordnete Ordner (= /tools) könnten umbenannt worden sein, deshalb neu auslesen
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

$gMessage->showHtmlTextOnly(true);

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string');

$languageFileName =  'de';
$languageFilePath = ADMIDIO_PATH . FOLDER_LANGUAGES .'/'.$languageFileName.'.xml';

$ret = 'error'  ;

try
{
    switch($getMode)
    {
        case 'create':
            $languageBackupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/'.$languageFileName.'_'.ADMIDIO_VERSION_TEXT.'_'.DATE_NOW.'.xml';
            
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
            $backupFilePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/'.$backupFile;
                     
            $ret = $_POST['restore_or_delete']  ;
            
            if ($ret === 'restore')
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
            elseif  ($ret === 'delete')
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
            include( ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/replacements.php');
            
            $languageFile = '';
            $languageFilePath = ADMIDIO_PATH . FOLDER_LANGUAGES .'/de.xml';
        
            try
            {
                $languageFile = FileSystemUtils::readFile($languageFilePath);
            }
            catch (\RuntimeException $exception)
            {
                $gMessage->show($exception->getMessage());
            }
            catch (\UnexpectedValueException $exception)
            {
                $gMessage->show($exception->getMessage());
            }

       //     $result = array();
            $result = str_replace(array_keys($replacements), $replacements, $languageFile);
            
     //       $filePath = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/replacements.php';
   //         $filePathSave = ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/replacements_save.php';
            
            try
            {
                FileSystemUtils::writeFile($languageFilePath, $result);
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
            
            $ret='replace'   ;
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







