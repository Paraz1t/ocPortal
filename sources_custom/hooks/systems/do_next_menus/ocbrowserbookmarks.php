<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocbrowserbookmarks
 */


class Hook_do_next_menus_ocbrowserbookmarks
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		return array(
			array('tools','admin_home',array('admin_generate_bookmarks',array(),'adminzone'),make_string_tempcode('Generate bookmarks.html for browser'))
		);
	}

}


