<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		workflows
 */

class Hook_page_groupings_workflows
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
	 */
	function run()
	{
		return array(
			array('setup','menu/workflows',array('admin_workflow',array('type'=>'misc'),get_module_zone('admin_workflow')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('workflows:WORKFLOWS'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value('workflow_requirements','COUNT(DISTINCT workflow_name)'))))),'workflows:DOC_WORKFLOWS'),
		);
	}

}

