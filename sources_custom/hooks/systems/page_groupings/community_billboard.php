<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 */

class Hook_page_groupings_community_billboard
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
	 */
	function run()
	{
		return array(
			array('audit','menu/adminzone/audit/community_billboard',array('admin_community_billboard',array('type'=>'misc'),get_module_zone('admin_community_billboard')),do_lang_tempcode('community_billboard:COMMUNITY_BILLBOARD'),'community_billboard:DOC_COMMUNITY_BILLBOARD'),
		);
	}

}


