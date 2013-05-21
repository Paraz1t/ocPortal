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
 * @package		stats
 */

class Hook_stats
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) return NULL;

		$info=array();
		$info['title']=do_lang_tempcode('STATS_CACHE');
		$info['description']=do_lang_tempcode('DESCRIPTION_STATS_CACHE');
		$info['type']='cache';

		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	Results
	 */
	function run()
	{
		if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) return new ocp_tempcode();

		$hooks=find_all_hooks('systems','disposable_values');
		foreach (array_keys($hooks) as $hook)
		{
			$GLOBALS['SITE_DB']->query_delete('values',array('the_name'=>$hook),'',1);
		}
		persistent_cache_delete('VALUES');

		return new ocp_tempcode();
	}

}

