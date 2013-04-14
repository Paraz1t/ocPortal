<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_addon_management
 */

/**
 * Find the icon for an addon.
 *
 * @param  ID_TEXT	Addon name
 * @param  boolean	Whether to use a default icon
 * @param  ?PATH		Path to tar file (NULL: don't look inside a TAR / it's installed already)
 * @return string		Theme image URL (may be a "data:" URL rather than a normal URLPATH)
 */
function find_addon_icon($addon_name,$pick_default=true,$tar_path=NULL)
{
	$path=get_custom_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($addon_name).'.php';
	if (!is_file($path))
	{
		$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($addon_name).'.php';
	}

	$addon_files=array();
	if (is_file($path))
	{
		$hook_file=file_get_contents($path);
		$matches=array();
		if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#',$hook_file,$matches)!=0)
		{
			if (!defined('HIPHOP_PHP'))
			{
				$addon_files=eval($matches[1]);
			} else
			{
				require_code('hooks/systems/addon_registry/'.$addon_name);
				$hook_ob=object_factory('Hook_addon_registry_'.$addon_name);
				$addon_files=$hook_ob->get_file_list();
			}
		}
	} else
	{
		if (!is_null($tar_path))
		{
			require_code('tar');
			$tar_file=tar_open($tar_path,'rb');
			$directory=tar_get_directory($tar_file,true);
			if (!is_null($directory))
			{
				foreach ($directory as $d)
				{
					$file=$d['path'];
					if (preg_match('#^themes/default/(images|images_custom)/bigicons/.*\.(png|jpg|jpeg|gif)$#',$file)!=0)
					{
						require_code('mime_types');
						$data=tar_get_file($tar_file,$file);
						return 'data:'.get_mime_type(get_file_extension($file)).';base64,'.base64_encode($data['data']);
					}
				}
			}
		} else
		{
			$addon_files=array_unique(collapse_1d_complexity('filename',$GLOBALS['SITE_DB']->query_select('addons_files',array('filename'),array('addon_name'=>$addon_name))));
		}
	}

	foreach ($addon_files as $file)
	{
		if (preg_match('#^themes/default/(images|images_custom)/bigicons/.*\.(png|jpg|jpeg|gif)$#',$file)!=0)
		{
			return find_theme_image('bigicons/'.basename($file,'.png'));
		}
	}

	// Default, as not found
	return $pick_default?find_theme_image('bigicons/addons'):NULL;
}
