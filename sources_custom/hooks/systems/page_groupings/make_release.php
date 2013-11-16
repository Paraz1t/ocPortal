<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocportal_release_build
 */

class Hook_page_groupings_make_release
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
	 */
	function run()
	{
		return array(
			array('tools','menu/_generic_admin/tool',array('plug_guid',array(),get_page_zone('plug_guid')),make_string_tempcode('Release tools: Plug in missing GUIDs')),
			array('tools','menu/_generic_admin/tool',array('make_release',array(),get_page_zone('make_release')),make_string_tempcode('Release tools: Make an ocPortal release')),
			array('tools','menu/_generic_admin/tool',array('push_bugfix',array(),get_page_zone('push_bugfix')),make_string_tempcode('Release tools: Push an ocPortal bugfix')),
			array('tools','menu/_generic_admin/tool',array('doc_index_build',array(),get_page_zone('doc_index_build')),make_string_tempcode('Doc tools: Make addon tutorial index')),
		);
	}

}

