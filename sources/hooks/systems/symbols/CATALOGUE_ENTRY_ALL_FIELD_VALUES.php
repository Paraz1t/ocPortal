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
 * @package		catalogues
 */

class Hook_symbol_CATALOGUE_ENTRY_ALL_FIELD_VALUES
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		$value='';
		if (isset($param[0]))
		{
			$entry_id=intval($param[0]);

			global $CATALOGUE_MAPPER_SYMBOL_CACHE;
			if (!isset($CATALOGUE_MAPPER_SYMBOL_CACHE)) $CATALOGUE_MAPPER_SYMBOL_CACHE=array();
			if (isset($CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id]))
			{
				$map=$CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id];

				if ((array_key_exists(1,$param)) && ($param[1]=='1'))
				{
					$value=$map['FIELDS']->evaluate();
				} else
				{
					$tpl_set=$map['CATALOGUE'];
					$_value=do_template('CATALOGUE_'.$tpl_set.'_FIELDMAP_ENTRY_WRAP',$map+array('ENTRY_SCREEN'=>true),NULL,false,'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP');
					$value=$_value->evaluate();
				}
			} else
			{
				$entry=$GLOBALS['SITE_DB']->query_select('catalogue_entries',array('*'),array('id'=>$entry_id),'',1);
				if (isset($entry[0]))
				{
					require_code('catalogues');
					$catalogue_name=$entry[0]['c_name'];
					$catalogue=load_catalogue_row($catalogue_name,true);
					if ($catalogue!==NULL)
					{
						$tpl_set=$catalogue_name;
						$map=get_catalogue_entry_map($entry[0],array('c_display_type'=>C_DT_FIELDMAPS)+$catalogue,'PAGE',$tpl_set,NULL);
						if ((array_key_exists(1,$param)) && ($param[1]=='1'))
						{
							$value=$map['FIELDS']->evaluate();
						} else
						{
							$_value=do_template('CATALOGUE_'.$tpl_set.'_FIELDMAP_ENTRY_WRAP',$map+array('GIVE_CONTEXT'=>false,'ENTRY_SCREEN'=>true),NULL,false,'CATALOGUE_DEFAULT_FIELDMAP_ENTRY_WRAP');
							$value=$_value->evaluate();
						}

						$CATALOGUE_MAPPER_SYMBOL_CACHE[$entry_id]=$map;
					}
				}
			}
		}
		return $value;
	}

}
