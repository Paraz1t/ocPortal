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
 * @package		core_cleanup_tools
 */

class Hook_broken_urls
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$skip_hooks=find_all_hooks('systems','non_active_urls');
		$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
		$GLOBALS['NO_DB_SCOPE_CHECK']=true;
		$urlpaths=$GLOBALS['SITE_DB']->query('SELECT m_name,m_table FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'db_meta WHERE m_type LIKE \''.db_encode_like('%URLPATH%').'\'');
		$count=0;
		foreach ($urlpaths as $urlpath)
		{
			if ($urlpath['m_table']=='hackattack') continue;
			if ($urlpath['m_table']=='url_title_cache') continue;
			if ($urlpath['m_table']=='theme_images') continue;
			if (array_key_exists($urlpath['m_table'],$skip_hooks)) continue;
			$count+=$GLOBALS['SITE_DB']->query_select_value($urlpath['m_table'],'COUNT(*)');
			if ($count>10000) return NULL; // Too much!
		}
		$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;

		$info=array();
		$info['title']=do_lang_tempcode('BROKEN_URLS');
		$info['description']=do_lang_tempcode('DESCRIPTION_BROKEN_URLS');
		$info['type']='optimise';

		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	Results
	 */
	function run()
	{
		require_code('tasks');
		return call_user_func_array__long_task(do_lang('BROKEN_URLS'),NULL,'find_broken_urls');
	}
}


