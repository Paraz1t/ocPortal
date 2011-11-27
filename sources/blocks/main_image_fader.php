<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

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

class Block_main_image_fader
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('param','time','zone');
		return $info;
	}
	
	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		$cat=array_key_exists('param',$map)?$map['param']:'';
		if ($cat=='') $cat='root';
		$mill=array_key_exists('time',$map)?intval($map['time']):8000; // milliseconds between animations
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('galleries');

		$images=array();
		$images_full=array();
		$image_rows=$GLOBALS['SITE_DB']->query_select('images',array('thumb_url','url'),array('cat'=>$cat),'',100/*reasonable amount*/);
		$video_rows=$GLOBALS['SITE_DB']->query_select('videos',array('thumb_url','thumb_url AS url'),array('cat'=>$cat),'',100/*reasonable amount*/);
		$image_rows=array_merge($image_rows,$video_rows);
		require_code('images');
		foreach ($image_rows as $row)
		{
			$url=$row['thumb_url'];
			if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;
			$images[]=$url;

			$full_url=$row['url'];
			if (url_is_local($full_url)) $full_url=get_custom_base_url().'/'.$full_url;
			$images_full[]=$full_url;
		}

		if (count($images)==0) return do_template('INLINE_WIP_MESSAGE',array('MESSAGE'=>do_lang_tempcode('NO_ENTRIES')));

		$gallery_url=build_url(array('page'=>'galleries','type'=>'misc','id'=>$cat),$zone);

		return do_template('BLOCK_MAIN_IMAGE_FADER',array(
			'GALLERY_URL'=>$gallery_url,
			'RAND'=>uniqid(''),
			'PREVIOUS_URL'=>$images[count($images)-1],
			'PREVIOUS_URL_FULL'=>$images[count($images_full)-1],
			'FIRST_URL'=>$images[0],
			'FIRST_URL_FULL'=>$images_full[0],
			'NEXT_URL'=>isset($images[1])?$images[1]:'',
			'NEXT_URL_FULL'=>isset($images_full[1])?$images_full[1]:'',
			'IMAGES'=>$images,
			'IMAGES_FULL'=>$images_full,
			'MILL'=>strval($mill),
		));
	}

}
