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
 * @package		ocf_forum
 */

class Hook_rss_ocf_topicview
{
	/**
	 * Standard modular run function for RSS hooks.
	 *
	 * @param  string			A list of categories we accept from
	 * @param  TIME			Cutoff time, before which we do not show results from
	 * @param  string			Prefix that represents the template set we use
	 * @set    RSS_ ATOM_
	 * @param  string			The standard format of date to use for the syndication type represented in the prefix
	 * @param  integer		The maximum number of entries to return, ordering by date
	 * @return ?array			A pair: The main syndication section, and a title (NULL: error)
	 */
	function run($_filters,$cutoff,$prefix,$date_string,$max)
	{
		if (!addon_installed('ocf_forum')) return NULL;

		if (get_forum_type()!='ocf') return NULL;
		if (!has_actual_page_access(get_member(),'forumview')) return NULL;

		$filters=ocfilter_to_sqlfragment($_filters,'p_topic_id','f_forums','f_parent_forum','p_cache_forum_id','id',true,true,$GLOBALS['FORUM_DB']);

		$cutoff=max($cutoff,time()-60*60*24*60);

		if (!is_guest()) $filters.=' AND (p_poster<>'.strval(get_member()).')';

		$rows=$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_time>'.strval($cutoff).(((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated')))?' AND p_validated=1 ':'').' AND '.$filters.' ORDER BY p_time DESC,id DESC',$max,NULL,false,true);
		$categories=list_to_map('id',$GLOBALS['FORUM_DB']->query('SELECT id,t_cache_first_title FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE t_cache_last_time>'.strval($cutoff)));

		$content=new ocp_tempcode();
		foreach ($rows as $row)
		{
			if (!array_key_exists($row['p_topic_id'],$categories)) continue;
			$category=$categories[$row['p_topic_id']]['t_cache_first_title'];
			if (((!is_null($row['p_cache_forum_id'])) || ($category['t_pt_from']==get_member()) || ($category['t_pt_to']==get_member())) && ((is_null($row['p_intended_solely_for']) || ($row['p_intended_solely_for']==get_member()))) && (has_category_access(get_member(),'forums',strval($row['p_cache_forum_id']))))
			{
				$id=strval($row['id']);
				$author=$row['p_poster_name_if_guest'];

				$news_date=date($date_string,$row['p_time']);
				$edit_date=is_null($row['p_last_edit_time'])?'':date($date_string,$row['p_last_edit_time']);
				if ($edit_date==$news_date) $edit_date='';

				$news_title=xmlentities($row['p_title']);
				$_summary=get_translated_tempcode($row['p_post']);
				$summary=xmlentities($_summary->evaluate());
				$news='';

				$category_raw=strval($row['p_topic_id']);

				$view_url=build_url(array('page'=>'topicview','type'=>'findpost','id'=>$row['id']),get_module_zone('forumview'),NULL,false,false,true);

				if ($prefix=='RSS_')
				{
					$if_comments=do_template('RSS_ENTRY_COMMENTS',array('_GUID'=>'ed06bc8f174a5427e1789820666fdd81','COMMENT_URL'=>$view_url,'ID'=>strval($row['p_topic_id'])));
				} else $if_comments=new ocp_tempcode();

				$content->attach(do_template($prefix.'ENTRY',array('VIEW_URL'=>$view_url,'SUMMARY'=>$summary,'EDIT_DATE'=>$edit_date,'IF_COMMENTS'=>$if_comments,'TITLE'=>$news_title,'CATEGORY_RAW'=>$category_raw,'CATEGORY'=>$category,'AUTHOR'=>$author,'ID'=>$id,'NEWS'=>$news,'DATE'=>$news_date)));
			}
		}

		require_lang('ocf');
		return array($content,do_lang('FORUM_TOPICS'));
	}
}


