<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ecommerce
 */

class Hook_page_groupings_ecommerce
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @param  ?MEMBER		Member ID to run as (NULL: current member)
	 * @param  boolean		Whether to use extensive documentation tooltips, rather than short summaries
	 * @return array			List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
	 */
	function run($member_id=NULL,$extensive_docs=false)
	{
		if (!addon_installed('ecommerce')) return array();

		$ret=array(
			array('setup','menu/adminzone/audit/ecommerce/ecommerce',array('admin_ecommerce',array('type'=>'misc'),get_module_zone('admin_ecommerce')),do_lang_tempcode('ecommerce:CUSTOM_PRODUCT_USERGROUP'),'ecommerce:DOC_ECOMMERCE'),
			array('audit','menu/adminzone/audit/ecommerce/ecommerce',array('admin_ecommerce_logs',array('type'=>'misc'),get_module_zone('admin_ecommerce')),do_lang_tempcode('ecommerce:ECOMMERCE'),'ecommerce:DOC_ECOMMERCE'),
			array('rich_content','menu/rich_content/ecommerce/purchase',array('purchase',array(),get_module_zone('purchase')),do_lang_tempcode('ecommerce:PURCHASING')),
		);
		if (addon_installed('shopping'))
		{
			$ret=array_merge($ret,array(
				array('rich_content','menu/rich_content/ecommerce/shopping_cart',array('shopping',array(),get_module_zone('shopping')),do_lang_tempcode('ecommerce:SHOPPING')),
			));
		}
		return $ret;
	}

}


