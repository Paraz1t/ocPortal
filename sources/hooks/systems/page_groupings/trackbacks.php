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
 * @package		core_feedback_features
 */

class Hook_page_groupings_trackbacks
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
		if ((get_option('is_on_trackbacks')=='0') || ($GLOBALS['SITE_DB']->query_select_value_if_there('trackbacks','COUNT(*)',NULL,'',true)==0)) return array();
		return array(
			array('audit','menu/adminzone/audit/trackbacks',array('admin_trackbacks',array('type'=>'misc'),get_module_zone('admin_trackbacks')),do_lang_tempcode('trackbacks:MANAGE_TRACKBACKS'),'trackbacks:DOC_TRACKBACKS'),
		);
	}

}


