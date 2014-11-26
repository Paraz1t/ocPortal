<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		galleries
 */

/**
 * Script handler for downloading a gallery, as specified by GET parameters.
 */
function download_gallery_script()
{
	if (function_exists('set_time_limit')) @set_time_limit(0);

	require_code('galleries');

	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_specific_permission(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain; charset='.get_charset());
		@exit(get_option('closed'));
	}

	require_lang('galleries');
	require_code('zip');

	$cat=get_param('cat');

	if (!has_category_access(get_member(),'galleries',$cat)) access_denied('CATEGORY_ACCESS');

	check_specific_permission('may_download_gallery',array('galleries',$cat));
	if ((strpos($cat,chr(10))!==false) || (strpos($cat,chr(13))!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');

	$gallery_rows=$GLOBALS['SITE_DB']->query_select('galleries',array('*'),array('name'=>$cat),'',1);
	if (!array_key_exists(0,$gallery_rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$gallery_row=$gallery_rows[0];

	// Send header
	header('Content-Type: application/octet-stream'.'; authoritative=true;');
	header('Content-Disposition: attachment; filename="gallery-'.$cat.'.zip"');

	if (ocp_srv('REQUEST_METHOD')=='HEAD') return;

	disable_php_memory_limit();

	$rows=array_merge($GLOBALS['SITE_DB']->query_select('videos',array('url','add_date'),array('cat'=>$cat,'validated'=>1)),$GLOBALS['SITE_DB']->query_select('images',array('url','add_date'),array('cat'=>$cat,'validated'=>1)));
	$array=array();
	foreach ($rows as $row)
	{
		$full_path=NULL;
		$data=NULL;
		if ((url_is_local($row['url'])) && (file_exists(get_file_base().'/'.urldecode($row['url']))))
		{
			$path=urldecode($row['url']);
			$full_path=get_file_base().'/'.$path;
			if (file_exists($full_path))
			{
				$time=filemtime($full_path);
				$name=$path;
			} else continue;
		} else
		{
			continue; // Actually we won't include them, if they are not local it implies it is not reasonable for them to lead to server load, and they may not even be native files

			$time=$row['add_date'];
			$name=basename(urldecode($row['url']));
			$data=http_download_file($row['url']);
		}

		$array[]=array('name'=>preg_replace('#^uploads/galleries/#','',$name),'time'=>$time,'data'=>$data,'full_path'=>$full_path);
	}

	if ($gallery_row['rep_image']!='')
	{
		if ((url_is_local($gallery_row['rep_image'])) && (file_exists(get_file_base().'/'.urldecode($gallery_row['rep_image']))))
		{
			$path=urldecode($gallery_row['rep_image']);
			$full_path=get_file_base().'/'.$path;
			if (file_exists($full_path))
			{
				$time=filemtime($full_path);
				$name=$path;
				$data=file_get_contents($full_path);
			}
		} else
		{
			$time=$gallery_row['add_date'];
			$name=basename(urldecode($gallery_row['rep_image']));
			$data=http_download_file($gallery_row['rep_image']);
		}
		$array[]=array('name'=>preg_replace('#^uploads/(galleries|grepimages)/#','',$name),'time'=>$time,'data'=>$data);
	}

	@ini_set('zlib.output_compression','Off');

	//$zip_file=create_zip_file($array);
	//header('Content-Length: '.strval(strlen($zip_file)));
	//echo $zip_file;
	create_zip_file($array,true);
}

