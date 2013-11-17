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
 * @package		actionlog
 */

class Hook_Notification_actionlog extends Hook_Notification__Staff
{
	/**
	 * Find whether a handled notification code supports categories.
	 * (Content types, for example, will define notifications on specific categories, not just in general. The categories are interpreted by the hook and may be complex. E.g. it might be like a regexp match, or like FORUM:3 or TOPIC:100)
	 *
	 * @param  ID_TEXT		Notification code
	 * @return boolean		Whether it does
	 */
	function supports_categories($notification_code)
	{
		return true;
	}

	/**
	 * Standard function to create the standardised category tree
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?ID_TEXT		The ID of where we're looking under (NULL: N/A)
	 * @return array 			Tree structure
	 */
	function create_category_tree($notification_code,$id)
	{
		$page_links=array();

		require_all_lang();

		$types=$GLOBALS['SITE_DB']->query_select('adminlogs',array('DISTINCT the_type'));
		if (get_forum_type()=='ocf')
			$types=array_merge($types,$GLOBALS['FORUM_DB']->query_select('f_moderator_logs',array('DISTINCT l_the_type AS the_type')));
		foreach ($types as $type)
		{
			$lang=do_lang($type['the_type'],NULL,NULL,NULL,NULL,false);
			if (is_null($lang)) continue;
			$page_links[]=array(
				'id'=>$type['the_type'],
				'title'=>$lang,
			);
		}
		sort_maps_by($page_links,'title');

		return $page_links;
	}

	/**
	 * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return integer		Initial setting
	 */
	function get_initial_setting($notification_code,$category=NULL)
	{
		return A_NA;
	}

	/**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
	function list_handled_codes()
	{
		$list=array();
		$list['actionlog']=array(do_lang('STAFF'),do_lang('actionlog:NOTIFICATION_TYPE_actionlog'));
		return $list;
	}
}
