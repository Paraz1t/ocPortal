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
 * @package		core
 */

/**
 * Find a list of all the tables that can be imported/exported as XML.
 *
 * @return array			List of tables
 */
function find_all_xml_tables()
{
	$skip=array('group_category_access','gsp','seo_meta','sessions','ip_country','f_moderator_logs','download_logging','url_title_cache','cached_comcode_pages','stats','import_id_remap','import_parts_done','import_session','cache','cache_on','blocks','modules','addons','addon_dependencies','db_meta','db_meta_indices','adminlogs','autosave','translate','translate_history');
	$all_tables=$GLOBALS['SITE_DB']->query_select('db_meta',array('DISTINCT m_table'));
	$tables=array();
	foreach ($all_tables as $table)
	{
		if (!in_array($table['m_table'],$skip)) $tables[]=$table['m_table'];
	}
	return $tables;
}

/**
 * Export ocPortal database tables to an equivalent XML format, automatically.
 *
 * @param  ?array			List of tables to export (NULL: all tables except those skippable)
 * @param  boolean		Whether to export Comcode as Comcode XML
 * @return string			Exported data in XML format
 */
function export_to_xml($tables=NULL,$comcode_xml=true)
{
	if ($comcode_xml) require_code('comcode_conversion');

	$GLOBALS['NO_QUERY_LIMIT']=true;
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;

	if (is_null($tables)) // Find table list
	{
		$tables=find_all_xml_tables();
	}

	// Build up data
	$xml_data='';
	$xml_data.='<!-- Exported on '.xmlentities(date('Y-m-d h:i:s')).' by '.xmlentities($GLOBALS['FORUM_DRIVER']->get_username(get_member())).' -->'.chr(10);
	$xml_data.='<ocportal origin="'.xmlentities(get_base_url()).'" version="'.xmlentities(float_format(ocp_version_number())).'">'.chr(10);
	foreach ($tables as $table)
	{
		$table_xml=_export_table_to_xml($table,$comcode_xml);
		if ($table_xml!='') $xml_data.=_tab($table_xml).chr(10);
	}
	$xml_data=rtrim($xml_data).chr(10);
	$xml_data.='</ocportal>'.chr(10);
	return $xml_data;
}

/**
 * Add layer(s) of tabbing to some XML.
 *
 * @param  string			XML in
 * @param  integer		The tab depth
 * @return string			XML out
 */
function _tab($in,$depth=1)
{
	$ret=rtrim(preg_replace('#^#',str_repeat("\t",$depth),$in),"\t");
	$ret=rtrim(preg_replace('#(\n\s*)<#','${1}'.str_repeat("\t",$depth).'<',$ret),"\t");
	return $ret;
}

/**
 * Export an ocPortal database table to an equivalent XML format.
 *
 * @param  ID_TEXT		Table to export
 * @param  boolean		Whether to export Comcode as Comcode XML
 * @return string			Exported data in XML format
 */
function _export_table_to_xml($table,$comcode_xml)
{
	$seo_type_code=mixed();
	$permissions_type_code=mixed();
	$id_field=mixed();
	$parent_field=mixed();
	$hooks=find_all_hooks('systems','content_meta_aware');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/content_meta_aware/'.$hook);
		$ob=object_factory('Hook_content_meta_aware_'.$hook);
		$info=$ob->info();
		if (is_null($info)) continue;
		if ($info['table']==$table)
		{
			$seo_type_code=array_key_exists('seo_type_code',$info)?$info['seo_type_code']:NULL;
			$permissions_type_code=array_key_exists('permissions_type_code',$info)?$info['permissions_type_code']:NULL;
			$id_field=array_key_exists('id_field',$info)?$info['id_field']:'id';
			if ((array_key_exists('is_category',$info)) && ((!array_key_exists('id_field_numeric',$info)) || ($info['id_field_numeric'])) && (array_key_exists('parent_category_field',$info)) && ($info['is_category']))
			{
				$parent_field=$info['parent_category_field'];
			}
		}
	}

	$xml_data='';
	$db_fields=$GLOBALS['SITE_DB']->query('SELECT m_name,m_type,m_table FROM '.get_table_prefix().'db_meta WHERE '.db_string_equal_to('m_table',$table).' OR '.db_string_equal_to('m_table','seo_meta').' OR '.db_string_equal_to('m_table','group_category_access').' OR '.db_string_equal_to('m_table','gsp'));
	$where=mixed();
	if ($table=='gsp') $where=array('category_name'=>'');
	if (is_null($parent_field))
	{
		$rows=$GLOBALS['SITE_DB']->query_select($table,array('*'),$where,'',NULL,NULL,false,array());
		foreach ($rows as $row) // Each row
		{
			$xml_data.=_export_xml_row($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml);
		}
	} else
	{
		$rows=$GLOBALS['SITE_DB']->query_select($table,array('*'),array($parent_field=>NULL),'',NULL,NULL,false,array());
		foreach ($rows as $row) // Each row
		{
			$xml_data.=_export_recurse_for_children($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml,$parent_field);
		}
	}
	return $xml_data;
}

/**
 * Export a table by tree recursion.
 *
 * @param  ID_TEXT		Table to export
 * @param  array			The row we're at
 * @param  array			List of field definitions for the row
 * @param  ID_TEXT		SEO type code
 * @param  ID_TEXT		Permission type code
 * @param  ID_TEXT		ID field name
 * @param  boolean		Whether to export Comcode as Comcode XML
 * @param  ID_TEXT		Parent ID field name
 * @return string			Exported data in XML format
 */
function _export_recurse_for_children($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml,$parent_field)
{
	$xml_data='';
	$xml_data.=_export_xml_row($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml,false);
	$rows=$GLOBALS['SITE_DB']->query_select($table,array('*'),array($parent_field=>$row[$id_field]),'',NULL,NULL,false,array());
	foreach ($rows as $row) // Each row
	{
		$row[$parent_field]='PARENT_INSERT_ID';
		$xml_data.=chr(10).chr(10);
		$xml_data.=_tab(_export_recurse_for_children($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml,$parent_field));
	}
	$xml_data.='</'.$table.'>'.chr(10).chr(10);
	return $xml_data;
}

/**
 * Export an ocPortal database row to an equivalent XML format.
 *
 * @param  ID_TEXT		Table to export
 * @param  array			DB row
 * @param  array			List of field definitions for the row
 * @param  ?ID_TEXT		SEO type code (NULL: N/A)
 * @param  ?ID_TEXT		Permission type code (NULL: N/A)
 * @param  ?ID_TEXT		ID field name (NULL: N/A)
 * @param  boolean		Whether to export Comcode as Comcode XML
 * @param  boolean		Whether to include the end tag for the row
 * @return string			Exported data in XML format
 */
function _export_xml_row($table,$row,$db_fields,$seo_type_code,$permissions_type_code,$id_field,$comcode_xml,$include_end=true)
{
	$xml_data='';

	$inner='';
	$fields='';
	$auto_key_id=NULL;

	foreach ($db_fields as $field) // Assemble the fields of the row
	{
		if ($field['m_table']!=$table) continue;

		$name=$field['m_name'];
		$value='';
		if ((strpos($field['m_type'],'TRANS')!==false) || (($table=='config') && ($name=='config_value') && ($row[$name]!='') && (strpos($row['the_type'],'trans')!==false))) // Translation layer integration.
		{
			$translate_rows=$GLOBALS['SITE_DB']->query_select('translate',array('*'),array('id'=>$row[$name]));
			foreach ($translate_rows as $t)
			{
				if (($comcode_xml) && ($t['text_parsed']!='') && ($t['text_original']!=''))
				{
					$value=chr(10)._tab(comcode_text__to__comcode_xml($t['text_original'])).chr(10);
				} else
				{
					$value=xmlentities($t['text_original']);
				}

				$inner.=_tab('<'.$name.' language="'.xmlentities($t['language']).'" importance_level="'.xmlentities(strval($t['importance_level'])).'" source_user="'.xmlentities(strval($t['source_user'])).'">'.$value.'</'.$name.'>').chr(10);
			}

			if (strpos($field['m_type'],'*')!==false) // Special case if lang string forms key. We need to put in an extra attribute so we can bind an existing lang string code if it exists
			{
				if ($field['m_type']=='*AUTO') $auto_key_id=$field['m_name'];
				$fields.=' '.$name.'="'.xmlentities(strval($row[$name])).'"';
			}
		} else // Simple field.
		{
			if (!array_key_exists($name,$row)) continue; // Shouldn't happen, but corruption could lead to this
			switch (gettype($row[$name])) // Serialise as string
			{
				case 'integer':
					switch (str_replace('?','',str_replace('*','',$field['m_type'])))
					{
						case 'TIME':
							$value=strftime('%a, %d %b %Y %H:%M:%S %z',$row[$name]);
							break;

						default:
							$value=strval($row[$name]);
							break;
					}
					break;
				case 'double': // float
					$value=float_to_raw_string($row[$name]);
					break;
				case 'NULL':
					$value='';
					break;
				default:
					$value=$row[$name];
					break;
			}

			// Place data
			if (strpos($field['m_type'],'*')!==false) // Key
			{
				if ($field['m_type']=='*AUTO') $auto_key_id=$field['m_name'];
				$fields.=' '.$name.'="'.xmlentities($value).'"';
			} else // Other data type
			{
				$inner.=_tab('<'.$name.'>'.xmlentities($value).'</'.$name.'>').chr(10);
			}
		}
	}

	// Assemble full row in XML format
	$xml_data.=chr(10).chr(10);
	if (!is_null($auto_key_id)) $xml_data.='<!-- If copying to another site you may wish to remove the '.$auto_key_id.' attribute/value-pair so that an appropriate new key is chosen (otherwise could update the wrong record) -->'.chr(10);
	$xml_data.='<'.$table.$fields.'>'.chr(10);
	$xml_data.=$inner;

	// SEO
	if (!is_null($seo_type_code))
	{
		$rows=$GLOBALS['SITE_DB']->query_select('seo_meta',array('*'),array('meta_for_type'=>$seo_type_code,'meta_for_id'=>is_integer($row[$id_field])?strval($row[$id_field]):$row[$id_field]),'',1);
		if (array_key_exists(0,$rows))
		{
			if (is_integer($rows[0]['meta_for_id']))
			{
				$export_row=array('meta_for_id'=>'LAST_INSERT_ID_'.$table)+$rows[0];
			} else
			{
				$export_row=$rows[0];
			}
			$xml_data.=_tab(_export_xml_row('seo_meta',$export_row,$db_fields,NULL,NULL,NULL,$comcode_xml));
		}
	}

	// Permissions
	if (!is_null($permissions_type_code))
	{
		$rows=$GLOBALS['SITE_DB']->query_select('group_category_access',array('*'),array('module_the_name'=>$permissions_type_code,'category_name'=>is_integer($row[$id_field])?strval($row[$id_field]):$row[$id_field]));
		foreach ($rows as $_row)
		{
			if (is_integer($_row['category_name']))
			{
				$export_row=array('category_name'=>'LAST_INSERT_ID_'.$table)+$_row;
			} else
			{
				$export_row=$_row;
			}
			$xml_data.=_tab(_export_xml_row('group_category_access',$export_row,$db_fields,NULL,NULL,NULL,$comcode_xml));
		}
		$rows=$GLOBALS['SITE_DB']->query_select('gsp',array('*'),array('module_the_name'=>$permissions_type_code,'category_name'=>is_integer($row[$id_field])?strval($row[$id_field]):$row[$id_field]));
		foreach ($rows as $_row)
		{
			if (is_integer($_row['category_name']))
			{
				$export_row=array('category_name'=>'LAST_INSERT_ID_'.$table)+$_row;
			} else
			{
				$export_row=$_row;
			}
			$xml_data.=_tab(_export_xml_row('gsp',$export_row,$db_fields,NULL,NULL,NULL,$comcode_xml));
		}
	}

	if ($include_end) $xml_data.='</'.$table.'>';

	return $xml_data;
}

/**
 * Take a PHP map array, and make it look nice.
 *
 * @param  array			Map array
 * @return string			Pretty version
 */
function make_map_nice($map)
{
	$out='';
	foreach ($map as $key=>$val)
	{
		if (!is_string($val))
		{
			if (is_float($val)) $val=float_to_raw_string($val);
			else $val=strval($val);
		}

		if ($out!='') $out.=chr(10);
		$out.=$key.' = '.$val;
	}
	return $out;
}

/**
 * Import to ocPortal database table from the equivalent XML format.
 *
 * @param  string			Data in XML format
 * @param  boolean		Synchronise deletes as well as inserts/updates
 * @return array			List of operations performed
 */
function import_from_xml($xml_data,$delete_missing_rows=false)
{
	$parsed=new ocp_simple_xml_reader($xml_data);
	if (!is_null($parsed->error)) warn_exit($parsed->error);

	$GLOBALS['NO_QUERY_LIMIT']=true;
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;

	$ops=array();

	$insert_ids=array();

	list($root_tag,$root_attributes,,$this_children)=$parsed->gleamed;
	if ($root_tag=='ocportal')
	{
		$_all_fields=$GLOBALS['SITE_DB']->query_select('db_meta',array('*'));
		$all_fields=array();
		foreach ($_all_fields as $f)
		{
			if (!array_key_exists($f['m_table'],$all_fields)) $all_fields[$f['m_table']]=array();
			$all_fields[$f['m_table']][]=$f;
		}
		$version=array_key_exists('version',$root_attributes)?floatval($root_attributes['version']):ocp_version_number();
		$origin=array_key_exists('origin',$root_attributes)?$root_attributes['origin']:get_base_url();

		$all_id_fields=array();
		$hooks=find_all_hooks('systems','content_meta_aware');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/content_meta_aware/'.$hook);
			$ob=object_factory('Hook_content_meta_aware_'.$hook);
			$info=$ob->info();
			if (is_null($info)) continue;
			if (array_key_exists('id_field',$info))
			{
				$all_id_fields[$info['table']]=$info['id_field'];
			}
		}

		// Table rows
		$all_existing_data=array();
		foreach ($this_children as $table)
		{
			$_ops=_import_xml_row($parsed,$all_existing_data,$all_fields,$all_id_fields,$table,$insert_ids,NULL);
			$ops=array_merge($ops,$_ops);
		}
	}

	// Sync deletes
	if ($delete_missing_rows)
	{
		foreach ($all_existing_data as $table=>$es)
		{
			foreach ($es as $e)
			{
				$GLOBALS['SITE_DB']->query_delete($table[0],$e,'',1);
				$ops[]=array(do_lang('DELETED_FROM_TABLE',$table[0]),do_lang('RECORD_IDENTIFIED_BY',make_map_nice($e)));
			}
		}
	}

	return $ops;
}

/**
 * Import to ocPortal database table from an XML row (possibly having descendant rows, via tree structure).
 *
 * @param  object			The XML parser object
 * @param  array			Existing data in table
 * @param  array			Field meta data for all fields
 * @param  array			Meta data about table IDs
 * @param  array			The record details being imported
 * @param  array			The insert IDs thus far
 * @param  ?AUTO_LINK	The ID of the auto-inserted parent to this row (NULL: N/A)
 * @return array			List of operations performed
 */
function _import_xml_row($parsed,&$all_existing_data,$all_fields,$all_id_fields,$table,&$insert_ids,$last_parent_id=NULL)
{
	$ops=array();

	if (!array_key_exists($table[0],$all_fields)) return array(); // No such table

	if (!array_key_exists($table[0],$all_existing_data))
		$all_existing_data[$table[0]]=$GLOBALS['SITE_DB']->query_select($table[0],array('*'),NULL,'',NULL,NULL,false,array());

	$data=array();

	// Collate simple data
	$data=array();
	foreach ($table[1] as $key=>$val) // key attributes
	{
		// Find corresponding field
		foreach ($all_fields[$table[0]] as $field) if ($field['m_name']==$key) break;
		if ($field['m_name']!=$key) continue; // No such field

		$value=mixed();

		switch (str_replace('?','',str_replace('*','',$field['m_type']))) // Serialise as string
		{
			case 'TIME':
				$value=($val=='')?NULL:strtotime($val);
				break;
			case 'GROUP':
			case 'USER':
			case 'BINARY':
			case 'SHORT_INTEGER':
			case 'INTEGER':
			case 'AUTO_LINK':
			case 'AUTO':
				$value=($val=='')?NULL:intval($val);
				break;
			case 'REAL': // float
				$value=floatval($val);
				break;
			default:
				$value=$val;
				break;
		}
		if ($value==='PARENT_INSERT_ID') $value=$last_parent_id;
		elseif (substr($value,0,strlen('LAST_INSERT_ID_'))==='LAST_INSERT_ID_') $value=isset($insert_ids[substr($value,strlen('LAST_INSERT_ID_'))])?$insert_ids[substr($value,strlen('LAST_INSERT_ID_'))]:NULL;
		$data[$key]=$value;
	}
	$tree_children=array();
	foreach ($table[3] as $__) // remaining attributes / tree children
	{
		if (!is_array($__)) continue;

		list($row_tag,$row_attributes,$row_value,$row_children)=$__;

		// Find corresponding field
		foreach ($all_fields[$table[0]] as $field) if ($field['m_name']==$row_tag) break;
		if ($field['m_name']!=$row_tag) // Tree child
		{
			$tree_children[]=$__;
		} else // attribute
		{
			if ((count($row_children)!=0) && (trim($row_value)==''))	$row_value=$parsed->pull_together($row_children);

			if (strpos($field['m_type'],'TRANS')===false) // Simple field.
			{
				$value=mixed();

				switch (str_replace('?','',str_replace('*','',$field['m_type']))) // Serialise as string
				{
					case 'TIME':
						$value=($row_value=='')?NULL:strtotime($row_value);
						break;
					case 'GROUP':
					case 'USER':
					case 'BINARY':
					case 'SHORT_INTEGER':
					case 'INTEGER':
					case 'AUTO_LINK':
					case 'AUTO':
						$value=($row_value=='')?NULL:intval($row_value);
						break;
					case 'REAL': // float
						$value=floatval($row_value);
						break;
					default:
						$value=$row_value;
						break;
				}
				if ($value==='PARENT_INSERT_ID') $value=$last_parent_id;
				elseif ((is_string($value)) && (substr($value,0,strlen('LAST_INSERT_ID_'))==='LAST_INSERT_ID_')) $value=isset($insert_ids[substr($value,strlen('LAST_INSERT_ID_'))])?$insert_ids[substr($value,strlen('LAST_INSERT_ID_'))]:NULL;
				$data[$row_tag]=$value;
			}
		}
	}

	// Does it already exist
	$key_map=array();
	$update=NULL;
	$existing_data=NULL;
	foreach ($all_fields[$table[0]] as $field)
	{
		if (strpos($field['m_type'],'*')!==false)
		{
			if (!array_key_exists($field['m_name'],$data))
			{
				$update=false;
				break;
			}
			$key_map[$field['m_name']]=$data[$field['m_name']];
		}
	}
	if (is_null($update))
	{
		$same=false;
		foreach ($all_existing_data[$table[0]] as $i=>$e)
		{
			$same=true;
			foreach ($key_map as $xk=>$xv)
			{
				if ($e[$xk]!==$xv)
				{
					$same=false; // will reset to true right away except for the last iteration - in which case the "$update=$same;" line will take note
					continue 2;
				}
			}
			// If we're still here we got a match
			$existing_data=$all_existing_data[$table[0]][$i];
			unset($all_existing_data[$table[0]][$i]);
			break;
		}
		$update=$same;
	}

	// Collate lang string data
	foreach ($table[3] as $__) // remaining attributes
	{
		if (!is_array($__)) continue;

		list($row_tag,$row_attributes,$row_value,$row_children)=$__;

		if ((count($row_children)!=0) && (trim($row_value)==''))	$row_value=$parsed->pull_together($row_children);

		// Find corresponding field
		foreach ($all_fields[$table[0]] as $field) if ($field['m_name']==$row_tag) break;
		if ($field['m_name']!=$row_tag) continue; // No such field

		if ((strpos($field['m_type'],'TRANS')!==false) || (($table[0]=='config') && ($field['m_name']=='config_value') && ($row_value!='') && (strpos($data['the_type'],'trans')!==false))) // Translation layer integration.
		{
			if ($update) // Update in lang layer
			{
				$lang_update_map=array('text_original'=>$row_value,'text_parsed'=>'');
				if (array_key_exists('source_user',$row_attributes)) $lang_update_map['source_user']=intval($row_attributes['source_user']);
				if (array_key_exists('importance_level',$row_attributes)) $lang_update_map['importance_level']=intval($row_attributes['importance_level']);
				$lang_where_map=array('id'=>$existing_data[$row_tag],'language'=>array_key_exists('language',$row_attributes)?$row_attributes['language']:get_site_default_lang());
				$GLOBALS['SITE_DB']->query_update('translate',$lang_update_map,$lang_where_map,'',1);
				$data[$row_tag]=$existing_data[$row_tag];
			} else // Insert in lang layer
			{
				$data[$row_tag]=$GLOBALS['SITE_DB']->query_insert('translate',array('source_user'=>array_key_exists('source_user',$row_attributes)?intval($row_attributes['source_user']):get_member(),'broken'=>0,'importance_level'=>array_key_exists('importance_level',$row_attributes)?intval($row_attributes['importance_level']):2,'text_original'=>$row_value,'text_parsed'=>'','language'=>array_key_exists('language',$row_attributes)?$row_attributes['language']:get_site_default_lang()),true);
			}
		}
	}

	// Amend DB
	$id_field=array_key_exists($table[0],$all_id_fields)?$all_id_fields[$table[0]]:NULL;
	if ($update)
	{
		$GLOBALS['SITE_DB']->query_update($table[0],$data,$key_map,'',1);
		$data_diff=$data;
		foreach ($existing_data as $key=>$val)
		{
			if ((array_key_exists($key,$data_diff)) && ($data_diff[$key]==$val)) unset($data_diff[$key]);
		}
		$ops[]=array(do_lang('UPDATED_IN_TABLE',$table[0]),do_lang('RECORD_IDENTIFIED_BY',make_map_nice($key_map)),($data_diff==array())?do_lang('NO_CHANGES_MADE'):make_map_nice($data_diff));

		$insert_ids[$table[0]]=array_key_exists($id_field,$key_map)?$key_map[$id_field]:NULL;
	} else
	{
		$insert_ids[$table[0]]=$GLOBALS['SITE_DB']->query_insert($table[0],$data,!is_null($id_field) && !array_key_exists($id_field,$data));
		$ops[]=array(do_lang('INSERTED_TO_TABLE',$table[0]),make_map_nice($data));
	}

	// Special case for CPF's
	if ($table[0]=='f_custom_fields')
	{
		$test=$GLOBALS['SITE_DB']->query_select('f_member_custom_fields',array('*'),NULL,'',1);
		if (!array_key_exists('field_'.strval($insert_ids[$table[0]]),$test[0]))
		{
			$_record=$GLOBALS['SITE_DB']->query_select($table[0],array('*'),array('id'=>$insert_ids[$table[0]]));
			$record=$_record[0];

			$encrypted=$record['cf_encrypted'];
			$type=$record['cf_type'];
			$id=$insert_ids[$table[0]];

			$index=false;
			switch ($type)
			{
				case 'multilist':
				case 'long_text':
					$index=true;
					$_type='LONG_TEXT';
					break;
				case 'short_trans':
					$_type='?SHORT_TRANS';
					break;
				case 'long_trans':
					$_type='?LONG_TRANS';
					break;
				case 'tick':
					$_type='BINARY';
					break;
				case 'integer':
					$_type='?INTEGER';
					break;
				default:
					$index=true;
					$_type=($encrypted==1)?'LONG_TEXT':'SHORT_TEXT';
			}
			require_code('database_action');
			// ($index?'#':'').
			$GLOBALS['SITE_DB']->add_table_field('f_member_custom_fields','field_'.strval($id),$_type); // Default will be made explicit when we insert rows
			if ($index)
			{
				$indices_count=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.get_table_prefix().'f_custom_fields WHERE '.db_string_not_equal_to('cf_type','integer').' AND '.db_string_not_equal_to('cf_type','tick').' AND '.db_string_not_equal_to('cf_type','long_trans').' AND '.db_string_not_equal_to('cf_type','short_trans'));
				if ($indices_count<60) // Could be 64 but trying to be careful here...
				{
					$GLOBALS['SITE_DB']->create_index('f_member_custom_fields','#mcf'.strval($id),array('field_'.strval($id)),'mf_member_id');
				}
			}
		}
	}

	// Handle tree children
	$this_id=isset($insert_ids[$table[0]])?$insert_ids[$table[0]]:NULL;
	foreach ($tree_children as $__)
	{
		$_ops=_import_xml_row($parsed,$all_existing_data,$all_fields,$all_id_fields,$__,$insert_ids,$this_id);
		$ops=array_merge($ops,$_ops);
	}

	return $ops;
}

class ocp_simple_xml_reader
{
	// Used during parsing
	var $tag_stack,$attribute_stack,$children_stack,$text_stack;

	var $gleamed,$error;

	/**
	 * Constructs the XML reader: parses the given data. Check $gleamed and $error after constructing.
	 *
	 * @param  string			The XML data
	 */
	function ocp_simple_xml_reader($xml_data)
	{
		$this->gleamed=array();
		$this->error=NULL;

		$this->tag_stack=array();
		$this->attribute_stack=array();
		$this->children_stack=array();
		$this->text_stack=array();

		if (!function_exists('xml_parser_create'))
		{
			$this->error=do_lang_tempcode('XML_NEEDED');
			return;
		}

		// Our internal charset
		$parser_charset=get_charset();
		if (!in_array(strtoupper($parser_charset),array('ISO-8859-1','US-ASCII','UTF-8')))
			$parser_charset='ISO-8859-1';

		// Create and setup our parser
		$xml_parser=function_exists('xml_parser_create_ns')?@xml_parser_create_ns($parser_charset):@xml_parser_create($parser_charset);
		if ($xml_parser===false)
		{
			$this->error=do_lang_tempcode('XML_PARSING_NOT_SUPPORTED');
			return; // PHP5 default build on windows comes with this function disabled, so we need to be able to escape on error
		}
		xml_set_object($xml_parser,$this);
		@xml_parser_set_option($xml_parser,XML_OPTION_TARGET_ENCODING,$parser_charset);
		xml_set_element_handler($xml_parser,'startElement','endElement');
		xml_set_character_data_handler($xml_parser,'startText');

		$xml_data=unixify_line_format('<'.'?xml version="1.0" encoding="'.xmlentities($parser_charset).'"?'.'>'.$xml_data,$parser_charset); // Fixes Windows characters

		if (xml_parse($xml_parser,$xml_data,true)==0)
		{
			warn_exit(xml_error_string(xml_get_error_code($xml_parser)));
		}

		@xml_parser_free($xml_parser);
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The name of the element found
	 * @param  array			Array of attributes of the element
	 */
	function startElement($parser,$name,$attributes)
	{
		unset($parser);

		array_push($this->tag_stack,strtolower($name));
		if ($attributes!=array())
		{
			$attributes_lowered=array();
			foreach ($attributes as $key=>$val)
			{
				$attributes_lowered[strtolower($key)]=$val;
			}
			$attributes=$attributes_lowered;
		}
		array_push($this->attribute_stack,$attributes);
		array_push($this->children_stack,array());
		array_push($this->text_stack,'');
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 */
	function endElement($parser)
	{
		unset($parser);

		$this_tag=array_pop($this->tag_stack);
		$this_attributes=array_pop($this->attribute_stack);
		$this_children=array_pop($this->children_stack);
		$this_text=array_pop($this->text_stack);

		if (count($this->tag_stack)==0)
		{
			$this->gleamed=array($this_tag,$this_attributes,$this_text,$this_children);
		} else
		{
			$next_top_tags_children=array_pop($this->children_stack);
			$next_top_tags_children[]=array($this_tag,$this_attributes,$this_text,$this_children);
			array_push($this->children_stack,$next_top_tags_children);
		}
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The text
	 */
	function startText($parser,$data)
	{
		unset($parser);

		$next_top_tags_text=array_pop($this->text_stack);
		$next_top_tags_text.=$data;
		array_push($this->text_stack,$next_top_tags_text);

		$next_top_tags_children=array_pop($this->children_stack);
		$next_top_tags_children[]=$data;
		array_push($this->children_stack,$next_top_tags_children);
	}

	/**
	 * Pull a portion of an XML tree structure back into textual XML.
	 *
	 * @param  array			Level of XML tree
	 * @return string			The combined XML
	 */
	function pull_together($children)
	{
		$data='';
		foreach ($children as $_)
		{
			if (is_array($_))
			{
				list($tag,$attributes,,$children)=$_;
				$drawn='';
				foreach ($attributes as $key=>$val)
				{
					$drawn.=$key.'='.xmlentities($val);
				}
				$data.='<'.$tag.$drawn.'>'.$this->pull_together($children).'</'.$tag.'>';
			} else
			{
				$data.=xmlentities($_);
			}
		}
		return $data;
	}
}
