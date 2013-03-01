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
 * @package		galleries
 */

class Hook_fields_video_multi
{

	// ==============
	// Module: search
	// ==============

	/**
	 * Get special Tempcode for inputting this field.
	 *
	 * @param  array			The row for the field to input
	 * @return ?array			List of specially encoded input detail rows (NULL: nothing special)
	 */
	function get_search_inputter($row)
	{
		return NULL;
	}

	/**
	 * Get special SQL from POSTed parameters for this field.
	 *
	 * @param  array			The row for the field to input
	 * @param  integer		We're processing for the ith row
	 * @return ?array			Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (NULL: nothing special)
	 */
	function inputted_to_sql_for_search($row,$i)
	{
		return NULL;
	}

	// ===================
	// Backend: fields API
	// ===================

	/**
	 * Get some info bits relating to our field type, that helps us look it up / set defaults.
	 *
	 * @param  ?array			The field details (NULL: new field)
	 * @param  ?boolean		Whether a default value cannot be blank (NULL: don't "lock in" a new default value)
	 * @param  ?string		The given default value as a string (NULL: don't "lock in" a new default value)
	 * @return array			Tuple of details (row-type,default-value-to-use,db row-type)
	 */
	function get_field_value_row_bits($field,$required=NULL,$default=NULL)
	{
		unset($field);
		return array('long_unescaped',$default,'long');
	}

	/**
	 * Convert a field value to something renderable.
	 *
	 * @param  array			The field details
	 * @param  mixed			The raw value
	 * @param  integer		Position in fieldset
	 * @param  ?array			List of fields the output is being limited to (NULL: N/A)
	 * @return mixed			Rendered field (tempcode or string)
	 */
	function render_field_value($field,$ev,$i,$only_fields)
	{
		if (is_object($ev)) return $ev;

		if ($ev=='') return '';

		require_code('galleries');
		require_code('galleries2');
		require_code('transcoding');

		$ret=new ocp_tempcode();
		$evs=explode(chr(10),$ev);
		foreach ($evs as $ev)
		{
			$ev=transcode_video($ev,'catalogue_efv_long','cv_value',NULL,NULL,NULL);

			$thumb_url=create_video_thumb($ev);

			if (substr($ev,0,strlen(get_custom_base_url().'/'))==get_custom_base_url().'/') $ev=substr($ev,strlen(get_custom_base_url().'/'));
			if (url_is_local($ev))
			{
				$width=600;
				$height=400;
				$length=0;
			} else
			{
				list($width,$height,$length)=get_video_details(get_custom_file_base().'/'.rawurldecode($ev),basename($ev));
			}

			$ret->attach(show_gallery_media($ev,$thumb_url,$width,$height,$length));
		}
		return $ret;
	}

	// ======================
	// Frontend: fields input
	// ======================

	/**
	 * Get form inputter.
	 *
	 * @param  string			The field name
	 * @param  string			The field description
	 * @param  array			The field details
	 * @param  ?string		The actual current value of the field (NULL: none)
	 * @param  boolean		Whether this is for a new entry
	 * @return ?array			A pair: The Tempcode for the input field, Tempcode for hidden fields (NULL: skip the field - it's not input)
	 */
	function get_field_inputter($_cf_name,$_cf_description,$field,$actual_value,$new)
	{
		$say_required=($field['cf_required']==1) && (($actual_value=='') || (is_null($actual_value)));
		require_code('galleries');
		$ffield=form_input_upload_multi($_cf_name,$_cf_description,'field_'.strval($field['id']),$say_required,NULL,($field['cf_required']==1)?NULL/*so unlink option not shown*/:(($actual_value=='')?NULL:explode(chr(10),$actual_value)),true,get_allowed_video_file_types());

		$hidden=new ocp_tempcode();
		handle_max_file_size($hidden);

		return array($ffield,$hidden);
	}

	/**
	 * Find the posted value from the get_field_inputter field
	 *
	 * @param  boolean		Whether we were editing (because on edit, it could be a fractional edit)
	 * @param  array			The field details
	 * @param  string			Where the files will be uploaded to
	 * @param  ?string		Former value of field (NULL: none)
	 * @return string			The value
	 */
	function inputted_to_field_value($editing,$field,$upload_dir='uploads/catalogues',$old_value=NULL)
	{
		if (!fractional_edit())
		{
			$id=$field['id'];

			$value='';

			$_old_value=((is_null($old_value)) || ($old_value==''))?array():explode(chr(10),$old_value);

			require_code('uploads');
			is_swf_upload(true);

			$i=1;
			do
			{
				$tmp_name='field_'.strval($id).strval($i);
				$temp=get_url('',$tmp_name,$upload_dir,0,OCP_UPLOAD_VIDEO);
				$_value=$temp[0];

				if (($editing) && ($_value=='') && (post_param_integer($tmp_name.'_unlink',0)!=1))
				{
					if (array_key_exists($i,$_old_value))
					{
						if ($value!='') $value.=chr(10);
						$value.=$_old_value[$i];
					}
				} else
				{
					if ((!is_null($old_value)) && ($old_value!='') && (($_value!='') || (post_param_integer('custom_'.strval($field['id']).'_value_unlink',0)==1)))
					{
						@unlink(get_custom_file_base().'/'.rawurldecode($old_value));
						sync_file(rawurldecode($old_value));
					}
					if ($_value!='')
					{
						if ($value!='') $value.=chr(10);
						$value.=$_value;
					}
				}

				$i++;
			}
			while (array_key_exists($tmp_name,$_FILES));
		} else
		{
			return STRING_MAGIC_NULL;
		}
		return $value;
	}

	/**
	 * The field is being deleted, so delete any necessary data
	 *
	 * @param  mixed			Current field value
	 */
	function cleanup($value)
	{
		if ($value!='')
		{
			@unlink(get_custom_file_base().'/'.rawurldecode($value));
			sync_file(rawurldecode($value));
		}
	}
}
