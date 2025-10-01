<?php
/**
 ***********************************************************************************************
 * restore_db
 * 
 * This plugin for Admidio restores a backup made with "Admidio-Backup".
 *
 * Author: rmb
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/../system/common_function.php');

// Einbinden der Sprachdatei
$gL10n->addLanguageFolderPath(ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/languages');

$headline = $gL10n->get('PLG_RESTORE_DB_PLUGIN_NAME');

//if the sub-plugin was not called from the main-plugin /Tools/index.php, then check the permissions
$navStack = $gNavigation->getStack();
if (!(StringUtils::strContains($navStack[0]['url'], PLUGIN_FOLDER.'/index.php', false)))
{
    // only authorized user are allowed to start this module
    if (!isUserAuthorized(basename(__FILE__), true))
    {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }
    $gNavigation->addStartUrl(CURRENT_URL, $headline, 'bi-database');
}
else
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

// Initialize and check the parameters
$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'restore')));
$postBackupFile = admFuncVariableIsValid($_POST, 'backup_file', 'string');

$backupAbsolutePath = ADMIDIO_PATH . FOLDER_DATA . '/backup/'; 

if ($getMode == 'start')     //Default
{
    $page = new HtmlPage('plg-restore_db-main', $headline);
    
    $page->addHtml('<strong>'.$gL10n->get('PLG_RESTORE_DB_DESC').'</strong><br><br>');
    
    $existingBackupFiles = array();
    $lastBackupFile = '';
    
    // create a list with all valid files in the backup folder
    $dirHandle = @opendir($backupAbsolutePath);
    if ($dirHandle)
    {
        while (($entry = readdir($dirHandle)) !== false)
        {
            if($entry === '.' || $entry === '..')
            {
                continue;
            }
            
            try
            {
                StringUtils::strIsValidFileName($entry);
                
                // replace invalid characters in filename
                $entry = FileSystemUtils::removeInvalidCharsInFilename($entry);
                
                $existingBackupFiles[$entry] = $entry;
            }
            catch(AdmException $e)
            {
                $e->showHtml();          //todo
            }
        }
        closedir($dirHandle);
    }
    
    if (sizeof($existingBackupFiles) > 0)
    {
        $form = new HtmlForm('choose_db_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . PLUGIN_SUBFOLDER .'/restore_db.php', array('mode' => 'restore')), $page);
        $form->addSelectBox('backup_file', $gL10n->get('PLG_RESTORE_DB_CHOOSE_BACKUPFILE'), $existingBackupFiles, array('property' => HtmlForm::FIELD_REQUIRED , 'helpTextIdInline' => 'PLG_RESTORE_DB_CHOOSE_BACKUPFILE_DESC'));
        $form->addSubmitButton('btn_restore', $gL10n->get('PLG_RESTORE_DB_RESTORE'), array('icon' => 'bi-arrow-counterclockwise', 'class' => 'offset-sm-3'));

        $page->addHtml($form->show(false));
    }
    else
    {
        //Meldung keine Backupdateien vorhanden
        $page->addHtml('<strong>'.$gL10n->get('PLG_RESTORE_DB_NO_BACKUPFILE').'</strong><br><br>');
    }

    $page->show();
}
elseif ($getMode == 'restore')
{
    require_once('vendor/progressbar.php');
    echo $gL10n->get('PLG_RESTORE_DB_RESTORE_PROCESS').'<br /><br />';
    
    if (ini_get('max_execution_time') < 1200)
    {
        ini_set('max_execution_time', 1200); //600 seconds = 10 minutes
    }
   
    $sql = 'SELECT table_name
          FROM information_schema.tables
         WHERE table_schema = ?
           AND table_name LIKE ?';
    $statement = $gDb->queryPrepared($sql, array(DB_NAME, TABLE_PREFIX . '_%'));
    $tables = array();
    
    $tables_string = '';
    while($tableName = $statement->fetchColumn())
    {
        $tables[] = $tableName;
        $tables_string .= $tableName.', ';
    }
    
    $tables_string = substr($tables_string, 0, strlen($tables_string)-2);
    
    $sql = 'SET FOREIGN_KEY_CHECKS=0' ;
    $gDb->query($sql);
    
    $sql = 'DROP TABLE '.$tables_string ;
    $gDb->query($sql);
    
    $sql = 'SET FOREIGN_KEY_CHECKS=1' ;
    $gDb->query($sql);
   
    $data = array();
    $fileextension = strrchr($postBackupFile, '.');
    if ($fileextension == '.bz2')                    //OUTPUT_COMPRESSION_TYPE = 'bzip2'
    {
        $fp = bzopen($backupAbsolutePath.$postBackupFile, 'r');
        while ($data[] = fgets($fp));
    }
    elseif($fileextension == '.gz')                  //OUTPUT_COMPRESSION_TYPE = 'gzip'
    {
        $data = gzfile($backupAbsolutePath.$postBackupFile);
    }
    else                                            //OUTPUT_COMPRESSION_TYPE = none = pure sql
    {
        $data = file($backupAbsolutePath.$postBackupFile);
    }
    
    // Temporary variable, used to store current query
    $templine = '';
    $error = '';
    
    $restoreProgressbar = new progressbar(0, sizeof($data), 400, 40);
    
    $restoreProgressbar->print_code();
    
    // Loop through each line
    foreach ($data as $line)
    {
        $restoreProgressbar->step();
        
        // Skip it if it's a comment
        if(substr($line, 0, 2) == '--' || $line == '')
        {
            continue;
        }
        
        // Add this line to the current segment
        $templine .= $line;
        
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';')
        {

            // Perform the query
            if(!$gDb->query($templine)){
                $error .= 'Error performing query "<b>' . $templine . '</b>": ' . $gDb->error . '<br /><br />';
            }
            
            // Reset temp variable to empty
            $templine = '';
        }
    }

    if ($error == '')
    {
        $buttonURL = ADMIDIO_URL . '/system/logout.php';
        echo '<p>'.$gL10n->get('PLG_RESTORE_DB_RESTORE_OK').'</p>
              <p>'.$gL10n->get('PLG_RESTORE_DB_LOGOUT_DESC').'</p>
              <button class="btn btn-primary" onclick="window.location.href=\''.$buttonURL.'\'"> '.$gL10n->get('PLG_RESTORE_DB_LOGOUT').'</button>';
    }
    else
    {
        echo '<br />'.$gL10n->get('PLG_RESTORE_DB_RESTORE_ERROR').'<br /><br />'.$error;
        exit;
    }
}

