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
 * @package		catalogues
 */

class Hook_block_ui_renderers_catalogues
{

	/**
	 * See if a particular block parameter's UI input can be rendered by this.
	 *
	 * @param  ID_TEXT		The block
	 * @param  ID_TEXT		The parameter of the block
	 * @param  boolean		Whether there is a default value for the field, due to this being an edit
	 * @param  string			Default value for field
	 * @param  tempcode		Field description
	 * @return ?tempcode		Rendered field (NULL: not handled).
	 */
	function render_block_ui($block,$parameter,$has_default,$default,$description)
	{
		if (($parameter=='param') && (in_array($block,array('main_cc_embed'))) && ($GLOBALS['SITE_DB']->query_select_value('catalogue_categories','COUNT(*)')<500)) // catalogue category
		{
			$list=new ocp_tempcode();
			$structured_list=new ocp_tempcode();
			$categories=$GLOBALS['SITE_DB']->query_select('catalogue_categories',array('id','cc_title','c_name'),array('cc_parent_id'=>NULL),'ORDER BY c_name,id',100);
			$last_cat=mixed();
			foreach ($categories as $cat)
			{
				if ((is_null($last_cat)) || ($cat['c_name']!=$last_cat))
				{
					$structured_list->attach(form_input_list_group($cat['c_name'],$list));
					$list=new ocp_tempcode();
					$last_cat=$cat['c_name'];
				}
				$list->attach(form_input_list_entry(strval($cat['id']),$has_default && strval($cat['id'])==$default,get_translated_text($cat['cc_title'])));
			}
			$structured_list->attach(form_input_list_group($cat['c_name'],$list));
			return form_input_list(titleify($parameter),escape_html($description),$parameter,$structured_list,NULL,false,false);
		}
		return NULL;
	}

}