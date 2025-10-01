<?php
/**
 ***********************************************************************************************
 * Exportdatei für das Admidio-Plugin blsv_export
 *
 * @copyright rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Entity\Entity;
use Admidio\Infrastructure\Utils\StringUtils;
use Admidio\Users\Entity\User;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/../system/common_function.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../system/version.php');

// only the main script can call and start this module
if (!StringUtils::strContains($gNavigation->getUrl(), 'blsv_export.php'))
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$postExportMode = admFuncVariableIsValid($_POST, 'export_mode', 'string', array('defaultValue' => 'xlsx', 'validValues' => array('csv-ms', 'csv-oo', 'xlsx', 'xml' )));

// initialize some special mode parameters
$separator   = '';
$valueQuotes = '';
$charset     = '';
$str_csv     = '';   // enthaelt die komplette CSV-Datei als String
$header      = array();
$rows        = array();

//$userField = new TableUserField($gDb);
$userField = new Entity($gDb, TBL_USER_FIELDS, 'usf');

switch ($postExportMode)
{
	case 'csv-ms':
		$separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
		$valueQuotes = '"';  // all values should be set with quotes
		$getMode     = 'csv';
		$charset     = 'iso-8859-1';
		break;
	case 'csv-oo':
		$separator   = ',';   // a CSV file should have a comma
		$valueQuotes = '"';   // all values should be set with quotes
		$getMode     = 'csv';
		$charset     = 'utf-8';
		break;
	case 'xlsx':
	    include_once(__DIR__ . '/libs/PHP_XLSXWriter/xlsxwriter.class.php');
	    $getMode     = 'xlsx';
	    break;
	case 'xml':
	    $getMode     = 'xml';
	    break;
}

$rols_blsv = array();
$rols_count = array();
$rols_dualmembership = array();
$sum_count = array();

// die erste Zeile (Kopf) zusammensetzen
foreach ($columns as $data)
{
    if ($getMode == 'csv')
    {
        $str_csv .= $valueQuotes. $data['headline']. $valueQuotes. $separator;
    }
    else               //'xlsx'
    {
        $header[$data['headline']] = 'string';
    }
	
	if (isset($data['rols_blsv']) && is_array($data['rols_blsv'])) 
	{
		$rols_blsv = $data['rols_blsv'];
		
		foreach ($data['rols_blsv'] as $roldata)
		{
			$rols_count[$roldata] = 0;
		}
	}
}

if ($getMode == 'csv')
{
    $str_csv = substr($str_csv, 0, -1);
    $str_csv .= "\n";
}

// jetzt die Mitgliederdaten zusammensetzen
$user = new User($gDb, $gProfileFields);

$sql = ' SELECT DISTINCT mem_usr_id
             	    FROM '.TBL_MEMBERS.', '.TBL_ROLES.', '.TBL_CATEGORIES. '
             	   WHERE mem_rol_id = rol_id
             	     AND rol_valid  = 1
             	     AND rol_cat_id = cat_id
             	     AND ( cat_org_id = ? -- $gCurrentOrganization->getValue(\'org_id\')
               		  OR cat_org_id IS NULL )
             	     AND mem_begin <= ? -- DATE_NOW
           		     AND mem_end    > ? -- DATE_NOW ';

$statement = $gDb->queryPrepared($sql, array((int) $gCurrentOrganization->getValue('org_id'), DATE_NOW, DATE_NOW));

while ($row = $statement->fetch())
{
	$userId = (int) $row['mem_usr_id'];
	$user->readDataById($userId);
	$user->getRoleMemberships();
	
	foreach ($rols_blsv as $roleId => $spartennummer)
	{
	    if (!isset($rols_dualmembership[$spartennummer]))
	    {
	        $rols_dualmembership[$spartennummer] = array();
	    }
	    
		if ($user->isMemberOfRole((int) $roleId))
		{
		    // prüfen, ob das Mitglied bereits für diese Sparte gemeldet ist
		    if (in_array($userId, $rols_dualmembership[$spartennummer]))
		    {
		        //wenn ja, nicht nochmals Daten auslesen und bearbeiten
		        continue;
		    }
		    else 
		    {
		        $rols_dualmembership[$spartennummer][] = $userId;
		    }
		    
		    $row = array();
			foreach ($columns as $data)
			{
				$content = '';
				if (isset($data['usf_uuid']) )
				{
				    $userField->readDataByUuid($data['usf_uuid']);
				    $usf_id = $userField->getValue('usf_id');
				    
				    if ( ($gProfileFields->getPropertyById( $usf_id, 'usf_type') == 'DROPDOWN'
				        || $gProfileFields->getPropertyById($usf_id, 'usf_type') == 'RADIO_BUTTON') )
					{
					    $content =  $user->getValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'), 'database');
						
						// show selected text of optionfield or combobox
					    $arrListValues = $gProfileFields->getPropertyById($usf_id, 'usf_value_list', 'text');
					    $content       = isset($arrListValues[$content]) ? $arrListValues[$content] : '';
					}
					else 
					{
					    $content = $user->getValue($gProfileFields->getPropertyById($usf_id, 'usf_name_intern'));
					}
					
					if (isset($data['subst'][$content]) )
					{
					    $content = $data['subst'][$content];
					}
				}
				elseif (isset($data['rols_blsv']) )
				{
					$content = $spartennummer;
					$rols_count[$spartennummer]++;
				}
				
				if ($getMode == 'csv')
				{
				    $str_csv .= $valueQuotes. $content. $valueQuotes. $separator;
				}
				else                  //'xlsx'
				{
				    $row[] = $content;
				}
			}
			
			if ($getMode == 'csv')
			{
			    $str_csv = substr($str_csv, 0, -1);
			    $str_csv .= "\n";
			}
			else                     //'xlsx'
			{
			    $rows[] = $row;
			}
			
			$sum_count[$userId] = 1;
		}
	}
}

$filename .= '_SUM-'.sizeof($sum_count);

foreach ($rols_count as $sparte => $count)
{
	$filename .= '_SPARTE'.$sparte.'-'.$count;
}

$filename .= '.'.$getMode;
	
if ($getMode == 'csv')
{
    // for IE the filename must have special chars in hexadecimal
    if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT']))
    {
        $filename = urlencode($filename);
    }
    
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    
    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');
    
    // nun die erstellte CSV-Datei an den User schicken
    header('Content-Type: text/comma-separated-values; charset='.$charset);
    
    if ($charset == 'iso-8859-1')
    {
        echo utf8_decode($str_csv);
    }
    else
    {
        echo $str_csv;
    }
}
elseif ($getMode == 'xlsx')                 
{
    header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    $keywords = array('BLSV', $gL10n->get('PLG_BLSV_EXPORT_DATA_COMPARISON'), $gL10n->get('PLG_BLSV_EXPORT_MEMBERSHIP_REPORT'));
    
    $writer = new XLSXWriter();
    $writer->setAuthor($gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME'));
    $writer->setTitle($filename);
    $writer->setSubject($gL10n->get('PLG_BLSV_EXPORT_DATA_COMPARISON_WITH_BLSV'));
    $writer->setCompany($gCurrentOrganization->getValue('org_longname'));
    $writer->setKeywords($keywords);
    $writer->setDescription($gL10n->get('PLG_BLSV_EXPORT_CREATED_WITH'));
    $writer->writeSheet($rows,'', $header);
    $writer->writeToStdOut();
}
else                    //'xml'
{
    // vorbelegte Variablen:
    $software_schluessel = 'ADMIDIO'.'-'.ADMIDIO_VERSION_TEXT.'-Plugin-'.$gL10n->get('PLG_BLSV_EXPORT_BLSV_EXPORT').'-'.$plugin_version;
    $verein_bezeichnung = $gCurrentOrganization->getValue('org_longname');
    $verein_ansprechpartner = $gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME');
    
    $xml = new SimpleXMLElement('<Jahrgangszahlen/>');
    
    $xml->addChild('Software')->addChild('Schluessel', $software_schluessel);
    
    $verein = $xml->addChild('Verein');
    $verein->addChild('Nummer', $verein_nummer);
    $verein->addChild('Bezeichnung', $verein_bezeichnung);
    $verein->addChild('Ansprechpartner', $verein_ansprechpartner);

    $bsbnet_arr= array();
    foreach ($rows as $row)
    {
        $jahrgang = date('Y', strtotime($row[5]));
        
        if (!isset($bsbnet_arr[$jahrgang]))
        {
            $bsbnet_arr[$jahrgang] = array('anzahlm' => 0, 'anzahlw' => 0, 'fachverband' => array());
        }
        
        if (!isset($bsbnet_arr[$jahrgang]['fachverband'][$row[6]]))
        {
            $bsbnet_arr[$jahrgang]['fachverband'][$row[6]] = array('anzahlm' => 0, 'anzahlw' => 0);
        }
        
        if ($row[4] === 'm')
        {
            $bsbnet_arr[$jahrgang]['anzahlm']++;
            $bsbnet_arr[$jahrgang]['fachverband'][$row[6]]['anzahlm']++;
        }
        elseif ($row[4] === 'w')
        {
            $bsbnet_arr[$jahrgang]['anzahlw']++;
            $bsbnet_arr[$jahrgang]['fachverband'][$row[6]]['anzahlw']++;
        }
        else
        {
            // wenn Geschlecht nicht 'w' und auch nicht 'm' ist, dann nicht inkrementieren
        }
    }
    
    ksort($bsbnet_arr);             // nach Jahrgang sortieren
    
    foreach ($bsbnet_arr as $jahrgang => $dataA)
    {
        $zahlen = $xml->addChild('Zahlen');
        $zahlen->addChild('Typ', 'A');
        $zahlen->addChild('Fachverband');
        $zahlen->addChild('Jahrgang', $jahrgang);
        $zahlen->addChild('AnzahlM', $dataA['anzahlm']);
        $zahlen->addChild('AnzahlW', $dataA['anzahlw']);
        
        foreach ($dataA['fachverband'] as $fachverband => $dataB)
        {
            $zahlen = $xml->addChild('Zahlen');
            $zahlen->addChild('Typ', 'B');
            $zahlen->addChild('Fachverband', $fachverband);
            $zahlen->addChild('Jahrgang', $jahrgang);
            $zahlen->addChild('AnzahlM', $dataB['anzahlm']);
            $zahlen->addChild('AnzahlW', $dataB['anzahlw']);
        }
    }								
        
    /******************************************************************************
    * XML-Datei schreiben
    *****************************************************************************/
        
    header('content-type: text/xml');
    header('Cache-Control: private'); // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    header('Content-Transfer-Encoding: binary'); // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
    header('Cache-Control: post-check=0, pre-check=0'); // Zwischenspeichern auf Proxies verhindern
    header('Content-Disposition: attachment; filename= "'.$filename.'" ');
          
   // diese Anweisung erzeugt zwar einen wohlgeformten XML-String, er ist aber schlecht lesbar, da er in einer einzigen Zeile geschrieben ist
    //echo $xml->asXML();                       
  
    // formatierten XML-String erzeugen
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    echo $dom->saveXML();
    
    exit();
}
