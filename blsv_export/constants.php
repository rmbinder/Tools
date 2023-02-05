<?php
/**
 ***********************************************************************************************
 * Constants for the admidio plugin blsv_export
 * 
 *
 * @copyright 2018-2023 rmb
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

//sowohl der plugin-ordner, als auch der übergeordnete Ordner (= /tools) könnten umbenannt worden sein, deshalb neu auslesen
$folders = explode(DIRECTORY_SEPARATOR, __DIR__);
define('PLUGIN_FOLDER', '/'.$folders[sizeof($folders)-1]);
define('PLUGIN_PARENT_FOLDER', '/'.$folders[sizeof($folders)-2]);
unset($folders);

define('CONFIG_CURR', ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config.php');
define('CONFIG_ORIG', ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config_orig.php');
define('CONFIG_SAVE', ADMIDIO_PATH . FOLDER_PLUGINS . PLUGIN_PARENT_FOLDER . PLUGIN_FOLDER .'/config_save.php');
