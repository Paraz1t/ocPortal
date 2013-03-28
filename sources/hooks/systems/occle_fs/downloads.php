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
 * @package		downloads
 */

require_code('content_fs');

class Hook_occle_fs_downloads extends content_fs_base
{
	var $folder_content_type='download_category';
	var $file_content_type='download';

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_folder_properties()
	{
		return array(
			'description'=>'LONG_TRANS',
			'notes'=>'LONG_TEXT',
			'rep_image'=>'URLPATH',
			'meta_keywords'=>'LONG_TRANS',
			'meta_description'=>'LONG_TRANS',
			'add_date'=>'TIME',
		);
	}

	/**
	 * Standard modular date fetch function for OcCLE-fs resource hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Content row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_folder_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_DOWNLOAD_CATEGORY').' OR '.db_string_equal_to('the_type','EDIT_DOWNLOAD_CATEGORY').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
	}

	/**
	 * Standard modular add function for OcCLE-fs resource hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error)
	 */
	function _folder_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->_folder_convert_filename_to_id($path);
		if ($category=='') return false; // Can't create more than one root

		require_code('downloads2');

		$parent_id=$this->_integer_category($category);
		$description=$this->_default_property_str($properties,'description');
		$notes=$this->_default_property_str($properties,'notes');
		$rep_image=$this->_default_property_str($properties,'rep_image');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');
		$id=add_download_category($label,$parent_id,$description,$notes,$rep_image,NULL,$add_time,$meta_keywords,$meta_description);
		return strval($id);
	}

	/**
	 * Standard modular load function for OcCLE-fs resource hooks. Finds the properties for some content.
	 *
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return ~array			Details of the content (false: error)
	 */
	function _folder_load($filename,$path)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		$rows=$GLOBALS['SITE_DB']->query_select('download_categories',array('*'),array('id'=>intval($content_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['category'],
			'description'=>$row['description'],
			'notes'=>$row['notes'],
			'rep_image'=>$row['rep_image'],
			'meta_keywords'=>$this->get_meta_keywords('downloads_category',strval($row['id'])),
			'meta_description'=>$this->get_meta_description('downloads_category',strval($row['id'])),
			'add_date'=>$row['add_date'],
		);
	}

	/**
	 * Standard modular edit function for OcCLE-fs resource hooks. Edits the content to the given properties.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return boolean		Success status
	 */
	function _folder_edit($filename,$path,$properties)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		require_code('downloads2');

		$label=$this->_default_property_str($properties,'label');
		$parent_id=$this->_integer_category($category);
		$description=$this->_default_property_str($properties,'description');
		$notes=$this->_default_property_str($properties,'notes');
		$rep_image=$this->_default_property_str($properties,'rep_image');
		$add_time=$this->_default_property_int_null($properties,'add_date');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');

		edit_download_category(intval($content_id),$label,$parent_id,$description,$notes,$rep_image,$meta_keywords,$meta_description,$add_time);

		return true;
	}

	/**
	 * Standard modular delete function for OcCLE-fs resource hooks. Deletes the content.
	 *
	 * @param  ID_TEXT		The filename
	 * @return boolean		Success status
	 */
	function _folder_delete($filename)
	{
		list($content_type,$content_id)=$this->_folder_convert_filename_to_id($filename);

		require_code('downloads2');
		delete_download_category(intval($content_id));

		return true;
	}

	/**
	 * Standard modular introspection function.
	 *
	 * @return array			The properties available for the content type
	 */
	function _enumerate_file_properties()
	{
		return array(
			'url'=>'URLPATH',
			'description'=>'LONG_TRANS',
			'author'=>'author',
			'additional_details'=>'LONG_TRANS',
			'out_mode_id'=>'?download',
			'validated'=>'BINARY',
			'allow_rating'=>'BINARY',
			'allow_comments'=>'SHORT_INTEGER',
			'allow_trackbacks'=>'BINARY',
			'notes'=>'LONG_TEXT',
			'original_filename'=>'SHORT_TEXT',
			'file_size'=>'INTEGER',
			'cost'=>'INTEGER',
			'submitter_gets_points'=>'BINARY',
			'licence'=>'download_licence',
			'num_downloads'=>'INTEGER',
			'views'=>'INTEGER',
			'meta_keywords'=>'LONG_TRANS',
			'meta_description'=>'LONG_TRANS',
			'submitter'=>'member',
			'add_date'=>'TIME',
			'edit_date'=>'?TIME',
		);
	}

	/**
	 * Standard modular date fetch function for OcCLE-fs resource hooks. Defined when getting an edit date is not easy.
	 *
	 * @param  array			Content row (not full, but does contain the ID)
	 * @return ?TIME			The edit date or add date, whichever is higher (NULL: could not find one)
	 */
	function _get_file_edit_date($row)
	{
		$query='SELECT MAX(date_and_time) FROM '.get_table_prefix().'adminlogs WHERE '.db_string_equal_to('param_a',strval($row['id'])).' AND  ('.db_string_equal_to('the_type','ADD_DOWNLOAD').' OR '.db_string_equal_to('the_type','EDIT_DOWNLOAD').')';
		return $GLOBALS['SITE_DB']->query_value_if_there($query);
	}

	/**
	 * Standard modular add function for OcCLE-fs resource hooks. Adds some content with the given label and properties.
	 *
	 * @param  SHORT_TEXT	Filename OR Content label
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return ~ID_TEXT		The content ID (false: error, could not create via these properties / here)
	 */
	function _file_add($filename,$path,$properties)
	{
		list($category_content_type,$category)=$this->_folder_convert_filename_to_id($path);
		list($properties,$label)=$this->_file_magic_filter($filename,$path,$properties);

		if ($category=='') return false;

		require_code('downloads2');

		$category_id=$this->_integer_category($category);
		$url=$this->_default_property_str($properties,'url');
		$description=$this->_default_property_str($properties,'description');
		$author=$this->_default_property_str($properties,'author');
		$additional_details=$this->_default_property_str($properties,'additional_details');
		$out_mode_id=$this->_default_property_int_null($properties,'out_mode_id');
		$validated=$this->_default_property_int_null($properties,'validated');
		if (is_null($validated)) $validated=1;
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','download_downloads',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','download_downloads',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','download_downloads',1);
		$notes=$this->_default_property_str($properties,'notes');
		$original_filename=$this->_default_property_str($properties,'original_filename');
		if ($original_filename=='') $original_filename=$label;
		$file_size=$this->_default_property_int($properties,'file_size');
		if (($file_size==0) && ($url!='') && (url_is_local($url)) && (file_exists(get_custom_file_base().'/'.rawurldecode($url))))
		{
			$file_size=filesize(get_custom_file_base().'/'.rawurldecode($url));
		}
		$cost=$this->_default_property_int($properties,'cost');
		$submitter_gets_points=$this->_default_property_int($properties,'submitter_gets_points');
		$licence=$this->_default_property_int_null($properties,'licence');
		$add_date=$this->_default_property_int_null($properties,'add_date');
		$num_downloads=$this->_default_property_int($properties,'num_downloads');
		$views=$this->_default_property_int($properties,'views');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');
		$id=add_download($category_id,$label,$url,$description,$author,$additional_details,$out_mode_id,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$original_filename,$file_size,$cost,$submitter_gets_points,$licence,$add_date,$num_downloads,$views,$submitter,$edit_date,NULL,$meta_keywords,$meta_description);
		return strval($id);
	}

	/**
	 * Standard modular load function for OcCLE-fs resource hooks. Finds the properties for some content.
	 *
	 * @param  SHORT_TEXT	Filename
	 * @param  string			The path (blank: root / not applicable)
	 * @return ~array			Details of the content (false: error)
	 */
	function _file_load($filename,$path)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('*'),array('id'=>intval($content_id)),'',1);
		if (!array_key_exists(0,$rows)) return false;
		$row=$rows[0];

		return array(
			'label'=>$row['name'],
			'url'=>$row['url'],
			'description'=>$row['description'],
			'author'=>$row['author'],
			'additional_details'=>$row['additional_details'],
			'out_mode_id'=>$row['out_mode_id'],
			'validated'=>$row['validated'],
			'allow_rating'=>$row['allow_rating'],
			'allow_comments'=>$row['allow_comments'],
			'allow_trackbacks'=>$row['allow_trackbacks'],
			'notes'=>$row['notes'],
			'original_filename'=>$row['original_filename'],
			'file_size'=>$row['file_size'],
			'cost'=>$row['cost'],
			'submitter_gets_points'=>$row['submitter_gets_points'],
			'licence'=>$row['licence'],
			'num_downloads'=>$row['num_downloads'],
			'views'=>$row['views'],
			'meta_keywords'=>$this->get_meta_keywords('downloads_download',strval($row['id'])),
			'meta_description'=>$this->get_meta_description('downloads_download',strval($row['id'])),
			'submitter'=>$row['submitter'],
			'add_date'=>$row['add_date'],
			'edit_date'=>$row['edit_date'],
		);
	}

	/**
	 * Standard modular edit function for OcCLE-fs resource hooks. Edits the content to the given properties.
	 *
	 * @param  ID_TEXT		The filename
	 * @param  string			The path (blank: root / not applicable)
	 * @param  array			Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
	 * @return boolean		Success status
	 */
	function _file_edit($filename,$path,$properties)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		require_code('downloads2');

		$label=$this->_default_property_str($properties,'label');
		$category_id=$this->_integer_category($category);
		$url=$this->_default_property_str($properties,'url');
		$description=$this->_default_property_str($properties,'description');
		$author=$this->_default_property_str($properties,'author');
		$additional_details=$this->_default_property_str($properties,'additional_details');
		$out_mode_id=$this->_default_property_int_null($properties,'out_mode_id');
		$validated=$this->_default_property_int_null($properties,'validated');
		if (is_null($validated)) $validated=1;
		$allow_rating=$this->_default_property_int_modeavg($properties,'allow_rating','download_downloads',1);
		$allow_comments=$this->_default_property_int_modeavg($properties,'allow_comments','download_downloads',1);
		$allow_trackbacks=$this->_default_property_int_modeavg($properties,'allow_trackbacks','download_downloads',1);
		$notes=$this->_default_property_str($properties,'notes');
		$original_filename=$this->_default_property_str($properties,'original_filename');
		if ($original_filename=='') $original_filename=$label;
		$file_size=$this->_default_property_int($properties,'file_size');
		if (($file_size==0) && ($url!='') && (url_is_local($url)) && (file_exists(get_custom_file_base().'/'.rawurldecode($url))))
		{
			$file_size=filesize(get_custom_file_base().'/'.rawurldecode($url));
		}
		$cost=$this->_default_property_int($properties,'cost');
		$submitter_gets_points=$this->_default_property_int($properties,'submitter_gets_points');
		$licence=$this->_default_property_int_null($properties,'licence');
		$add_date=$this->_default_property_int_null($properties,'add_date');
		$num_downloads=$this->_default_property_int($properties,'num_downloads');
		$views=$this->_default_property_int($properties,'views');
		$submitter=$this->_default_property_int_null($properties,'submitter');
		$edit_date=$this->_default_property_int_null($properties,'edit_date');
		$meta_keywords=$this->_default_property_str($properties,'meta_keywords');
		$meta_description=$this->_default_property_str($properties,'meta_description');

		edit_download(intval($content_id),$category_id,$label,$url,$description,$author,$additional_details,$out_mode_id,$default_pic,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$original_filename,$file_size,$cost,$submitter_gets_points,$licence,$meta_keywords,$meta_description,$edit_time,$add_time,$views,$submitter,true);

		return true;
	}

	/**
	 * Standard modular delete function for OcCLE-fs resource hooks. Deletes the content.
	 *
	 * @param  ID_TEXT		The filename
	 * @return boolean		Success status
	 */
	function _file_delete($filename)
	{
		list($content_type,$content_id)=$this->_file_convert_filename_to_id($filename);

		require_code('downloads2');
		delete_download(intval($content_id));

		return true;
	}
}
