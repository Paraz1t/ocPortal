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
 * @package		core_fields
 */

/**
 * Get a fields hook, from a given codename.
 *
 * @param  ID_TEXT		Codename
 * @return object			Hook object
 */
function get_fields_hook($type)
{
	static $fields_hook_cache=array();
	if (isset($fields_hook_cache[$type])) return $fields_hook_cache[$type];

	$path='hooks/systems/fields/'.filter_naughty($type);
	if ((!/*common ones we know have hooks*/in_array($type,array('author','auto_increment','codename','color','content_link','date','email','float','guid','integer','just_date','just_time','list','long_text','long_trans','page_link','password','picture','video','posting_field','radiolist','random','reference','short_text','short_trans','theme_image','tick','upload','url','user'))) && (!is_file(get_file_base().'/sources/'.$path.'.php')) && (!is_file(get_file_base().'/sources_custom/'.$path.'.php')))
	{
		$hooks=find_all_hooks('systems','fields');
		foreach (array_keys($hooks) as $hook)
		{
			$path='hooks/systems/fields/'.filter_naughty($hook);
			require_code($path);
			$ob=object_factory('Hook_fields_'.filter_naughty($hook));
			if (method_exists($ob,'get_field_types'))
			{
				if (array_key_exists($type,$ob->get_field_types()))
				{
					$fields_hook_cache[$type]=$ob;
					return $ob;
				}
			}
		}
	}
	require_code($path);
	$ob=object_factory('Hook_fields_'.filter_naughty($type));
	$fields_hook_cache[$type]=$ob;
	return $ob;
}

/**
 * Get extra do-next icon for managing custom fields for a content type.
 *
 * @param  ID_TEXT		Award hook codename
 * @return array			Extra do-next icon (single item array, or empty array if catalogues not installed)
 */
function manage_custom_fields_donext_link($content_type)
{
	if (addon_installed('catalogues'))
	{
		require_lang('fields');

		require_code('hooks/systems/awards/'.$content_type);
		$ob=object_factory('Hook_awards_'.$content_type);
		$info=$ob->info();

		if ((array_key_exists('supports_custom_fields',$info)) && ($info['supports_custom_fields']) && (has_specific_permission(get_member(),'submit_cat_highrange_content','cms_catalogues')) && (has_specific_permission(get_member(),'edit_cat_highrange_content','cms_catalogues')))
		{
			$exists=!is_null($GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'_'.$content_type)));

			return array(
				array('edit_one_catalogue',array('cms_catalogues',array('type'=>$exists?'_edit_catalogue':'add_catalogue','id'=>'_'.$content_type,'redirect'=>get_self_url(true)),get_module_zone('cms_catalogues')),do_lang('EDIT_CUSTOM_FIELDS',$info['title'])),
			);
		}
	}

	return array();
}

/**
 * Find whether a content type has a tied catalogue.
 *
 * @param  ID_TEXT		Award hook codename
 * @return boolean		Whether it has
 */
function has_tied_catalogue($content_type)
{
	if (addon_installed('catalogues'))
	{
		require_code('hooks/systems/awards/'.$content_type);
		$ob=object_factory('Hook_awards_'.$content_type);
		$info=$ob->info();
		if ((array_key_exists('supports_custom_fields',$info)) && ($info['supports_custom_fields']))
		{
			$exists=!is_null($GLOBALS['SITE_DB']->query_value_null_ok('catalogues','c_name',array('c_name'=>'_'.$content_type)));
			if ($exists)
			{
				$first_cat=$GLOBALS['SITE_DB']->query_value_null_ok('catalogue_categories','MIN(id)',array('c_name'=>'_'.$content_type));
				if (is_null($first_cat)) // Repair needed, must have a category
				{
					require_code('catalogues2');
					require_lang('catalogues');
					actual_add_catalogue_category('_'.$content_type,do_lang('CUSTOM_FIELDS_FOR',$info['title']->evaluate()),'','',NULL);
				}

				return true;
			}
		}
	}
	return false;
}

/**
 * Get catalogue entry ID bound to a content entry.
 *
 * @param  ID_TEXT		Award hook codename
 * @param  ID_TEXT		Content entry ID
 * @return ?AUTO_LINK	Bound catalogue entry ID (NULL: none)
 */
function get_bound_content_entry($content_type,$id)
{
	return $GLOBALS['SITE_DB']->query_value_null_ok('catalogue_entry_linkage','catalogue_entry_id',array(
		'content_type'=>$content_type,
		'content_id'=>$id,
	));
}

/**
 * Append fields to content add/edit form for gathering custom fields.
 *
 * @param  ID_TEXT		Award hook codename
 * @param  ?ID_TEXT		Content entry ID (NULL: new entry)
 * @param  tempcode		Fields (passed by reference)
 * @param  tempcode		Hidden Fields (passed by reference)
 */
function append_form_custom_fields($content_type,$id,&$fields,&$hidden)
{
	require_code('catalogues');

	$catalogue_entry_id=get_bound_content_entry($content_type,$id);
	if (!is_null($catalogue_entry_id))
	{
		$special_fields=get_catalogue_entry_field_values('_'.$content_type,$catalogue_entry_id);
	} else
	{
		$special_fields=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('*'),array('c_name'=>'_'.$content_type),'ORDER BY cf_order');
	}

	$field_groups=array();

	require_code('fields');
	foreach ($special_fields as $field_num=>$field)
	{
		$ob=get_fields_hook($field['cf_type']);
		$default=get_param('field_'.strval($field['id']),$field['cf_default']);
		if (array_key_exists('effective_value_pure',$field)) $default=$field['effective_value_pure'];
		elseif (array_key_exists('effective_value',$field)) $default=$field['effective_value'];

		$_cf_name=get_translated_text($field['cf_name']);
		$field_cat='';
		$matches=array();
		if (strpos($_cf_name,': ')!==false)
		{
			$field_cat=substr($_cf_name,0,strpos($_cf_name,': '));
			if ($field_cat.': '==$_cf_name)
			{
				$_cf_name=$field_cat; // Just been pulled out as heading, nothing after ": "
			} else
			{
				$_cf_name=substr($_cf_name,strpos($_cf_name,': ')+2);
			}
		}
		if (!array_key_exists($field_cat,$field_groups)) $field_groups[$field_cat]=new ocp_tempcode();

		$_cf_description=escape_html(get_translated_text($field['cf_description']));

		$GLOBALS['NO_DEBUG_MODE_FULLSTOP_CHECK']=true;
		$result=$ob->get_field_inputter($_cf_name,$_cf_description,$field,$default,true,!array_key_exists($field_num+1,$special_fields));
		$GLOBALS['NO_DEBUG_MODE_FULLSTOP_CHECK']=false;

		if (is_null($result)) continue;

		if (is_array($result))
		{
			$field_groups[$field_cat]->attach($result[0]);
		} else
		{
			$field_groups[$field_cat]->attach($result);
		}

		$hidden->attach(form_input_hidden('label_for__field_'.strval($field['id']),$_cf_name));

		unset($result);
		unset($ob);
	}

	if (array_key_exists('',$field_groups)) // Blank prefix must go first
	{
		$field_groups_blank=$field_groups[''];
		unset($field_groups['']);
		$field_groups=array_merge(array($field_groups_blank),$field_groups);
	}
	foreach ($field_groups as $field_group_title=>$extra_fields)
	{
		if (is_integer($field_group_title)) $field_group_title=($field_group_title==0)?'':strval($field_group_title);

		if ($field_group_title!='')
			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>$field_group_title)));
		$fields->attach($extra_fields);
	}
}

/**
 * Save custom fields to a content item.
 *
 * @param  ID_TEXT		Award hook codename
 * @param  ID_TEXT		Content entry ID
 */
function save_form_custom_fields($content_type,$id)
{
	if (fractional_edit()) return;

	$existing=get_bound_content_entry($content_type,$id);

	require_code('catalogues');

	// Get field values
	$fields=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('*'),array('c_name'=>'_'.$content_type),'ORDER BY cf_order');
	$map=array();
	require_code('fields');
	foreach ($fields as $field)
	{
		$ob=get_fields_hook($field['cf_type']);

		list(,,$storage_type)=$ob->get_field_value_row_bits($field);

		$value=$ob->inputted_to_field_value(!is_null($existing),$field,'uploads/catalogues',is_null($existing)?NULL:_get_catalogue_entry_field($field['id'],$existing,$storage_type));

		$map[$field['id']]=$value;
	}

	$first_cat=$GLOBALS['SITE_DB']->query_value('catalogue_categories','MIN(id)',array('c_name'=>'_'.$content_type));

	require_code('catalogues2');

	if (!is_null($existing))
	{
		actual_edit_catalogue_entry($existing,$first_cat,1,'',0,0,0,$map);
	} else
	{
		$catalogue_entry_id=actual_add_catalogue_entry($first_cat,1,'',0,0,0,$map);

		$GLOBALS['SITE_DB']->query_insert('catalogue_entry_linkage',array(
			'catalogue_entry_id'=>$catalogue_entry_id,
			'content_type'=>$content_type,
			'content_id'=>$id,
		));
	}
}

/**
 * Delete custom fields for content item.
 *
 * @param  ID_TEXT		Award hook codename
 * @param  ID_TEXT		Content entry ID
 */
function delete_form_custom_fields($content_type,$id)
{
	require_code('catalogues2');

	$existing=get_bound_content_entry($content_type,$id);
	if (!is_null($existing))
	{
		actual_delete_catalogue_entry($existing);

		$GLOBALS['SITE_DB']->query_delete('catalogue_entry_linkage',array(
			'catalogue_entry_id'=>$existing,
		));
	}
}

/**
 * Get a list of all field types to choose from.
 *
 * @param  ID_TEXT		Field type to select
 * @param  boolean		Whether to only show options in the same storage set as $type
 * @return tempcode		List of field types
 */
function nice_get_field_type($type='',$limit_to_storage_set=false)
{
	require_lang('fields');

	$all_types=find_all_hooks('systems','fields');
	if ($limit_to_storage_set) // Already set, so we need to do a search to see what we can limit our types to (things with the same backend DB storage)
	{
		$ob=get_fields_hook($type);
		$types=array();
		list(,,$db_type)=$ob->get_field_value_row_bits(NULL);
		foreach ($all_types as $this_type=>$hook_type)
		{
			$ob=get_fields_hook($this_type);
			list(,,$this_db_type)=$ob->get_field_value_row_bits(NULL);

			if ($this_db_type==$db_type)
				$types[$this_type]=$hook_type;
		}
	} else $types=$all_types;
	$orderings=array(
		do_lang_tempcode('FIELD_TYPES__TEXT'),'short_trans','short_trans_multi','short_text','short_text_multi','long_trans','long_text','posting_field','codename','password','email',
		do_lang_tempcode('FIELD_TYPES__NUMBERS'),'integer','float',
		do_lang_tempcode('FIELD_TYPES__CHOICES'),'list','radiolist','tick','multilist','tick_multi',
		do_lang_tempcode('FIELD_TYPES__UPLOADSANDURLS'),'upload','picture','video','url','page_link','theme_image','theme_image_multi',
		do_lang_tempcode('FIELD_TYPES__MAGIC'),'auto_increment','random','guid',
		do_lang_tempcode('FIELD_TYPES__REFERENCES'),'isbn','reference','content_link','content_link_multi','user','user_multi','author',
//			do_lang_tempcode('FIELD_TYPES__OTHER'),'date',			Will go under OTHER automatically
	);
	$_types=array();
	$done_one_in_section=true;
	foreach ($orderings as $o)
	{
		if (is_object($o))
		{
			if (!$done_one_in_section) array_pop($_types);
			$_types[]=$o;
			$done_one_in_section=false;
		} else
		{
			if (array_key_exists($o,$types))
			{
				$_types[]=$o;
				unset($types[$o]);
				$done_one_in_section=true;
			}
		}
	}
	if (!$done_one_in_section) array_pop($_types);
	if (count($types)!=0)
	{
		$types=array_merge($_types,array(do_lang_tempcode('FIELD_TYPES__OTHER')),array_keys($types));
	} else $types=$_types;
	$type_list=new ocp_tempcode();
	foreach ($types as $_type)
	{
		if (is_object($_type))
		{
			if (!$type_list->is_empty()) $type_list->attach(form_input_list_entry('',false,escape_html(''),false,true));
			$type_list->attach(form_input_list_entry('',false,$_type,false,true));
		} else
		{
			$ob=get_fields_hook($_type);
			if (method_exists($ob,'get_field_types'))
			{
				$sub_types=$ob->get_field_types();
			} else
			{
				$sub_types=array($_type=>do_lang_tempcode('FIELD_TYPE_'.$_type));
			}

			foreach ($sub_types as $__type=>$_title)
			{
				$type_list->attach(form_input_list_entry($__type,($__type==$type),$_title));
			}
		}
	}

	return make_string_tempcode($type_list->evaluate()); // XHTMLXHTML
}
