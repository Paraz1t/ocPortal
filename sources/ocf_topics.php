<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

/**
 * Get an SQL 'WHERE' clause for the posts in a topic.
 *
 * @param  AUTO_LINK		The ID of the topic we are getting details of.
 * @return string			The WHERE clause.
 */
function ocf_get_topic_where($topic_id)
{
	$where='p_topic_id='.strval((integer)$topic_id);
	if (is_guest())
		$where.=' AND p_intended_solely_for IS NULL';
	elseif (!has_specific_permission(get_member(),'view_other_pt'))
		$where.=' AND (p_intended_solely_for='.strval((integer)get_member()).' OR p_poster='.strval((integer)get_member()).' OR p_intended_solely_for IS NULL)';
	if (!has_specific_permission(get_member(),'see_unvalidated')) $where.=' AND (p_validated=1 OR ((p_poster<>'.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).' OR '.db_string_equal_to('p_ip_address',get_ip_address()).') AND p_poster='.strval((integer)get_member()).'))';
	return $where;
}

/**
 * Find whether a member may make a Private Topic.
 *
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	The answer.
 */
function ocf_may_make_private_topic($member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	if (!has_specific_permission($member_id,'use_pt')) return false;

	return $member_id!=$GLOBALS['OCF_DRIVER']->get_guest_id();
}

/**
 * Find whether a member may post a topic in a certain forum.
 *
 * @param  AUTO_LINK The forum the topic would be in.
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	The answer.
 */
function ocf_may_post_topic($forum_id,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	if (!has_specific_permission($member_id,'submit_midrange_content','topics',array('forums',$forum_id))) return false;
	if (is_null($forum_id)) return true;

	$test=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_warnings WHERE p_silence_from_forum='.strval($forum_id).' AND w_member_id='.strval($member_id));
	if (!is_null($test)) return false;

	return true;
}

/**
 * Find whether a member may report a post.
 *
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	The answer.
 */
function ocf_may_report_post($member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	return has_specific_permission($member_id,'may_report_post');
}

/**
 * Find whether a member has replied to a certain topic.
 *
 * @param  AUTO_LINK The topic.
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	The answer.
 */
function ocf_has_replied_topic($topic_id,$member_id=NULL)
{
	$test=$GLOBALS['FORUM_DB']->query_value('f_posts','id',array('p_topic_id'=>$topic_id,'p_poster'=>$member_id));
	return !is_null($test);
}

/**
 * Find whether a member may edit topics in a certain forum.
 *
 * @param  AUTO_LINK		The forum the topic would be in.
 * @param  MEMBER			The member checking access for.
 * @param  MEMBER			The member that owns this resource
 * @return boolean		The answer.
 */
function ocf_may_edit_topics_by($forum_id,$member_id,$resource_owner)
{
	if (is_null($member_id)) $member_id=get_member();

	if (is_null($forum_id)) return has_specific_permission($member_id,'moderate_personal_topic');

	return has_edit_permission('mid',$member_id,$resource_owner,'topics',array('forums',$forum_id));
}

/**
 * Find whether a member may delete topics in a certain forum.
 *
 * @param  AUTO_LINK		The forum the topic would be in.
 * @param  MEMBER			The member checking access for.
 * @param  MEMBER			The member that owns this resource
 * @return boolean		The answer.
 */
function ocf_may_delete_topics_by($forum_id,$member_id,$resource_owner)
{
	if (is_null($member_id)) $member_id=get_member();

	if (is_null($forum_id)) return has_specific_permission($member_id,'moderate_personal_topic');

	return has_delete_permission('mid',$member_id,$resource_owner,'topics',array('forums',$forum_id));
}

/**
 * Mark a topic as read by the current member.
 *
 * @param  AUTO_LINK The Id of the topic to mark as read.
 */
function ocf_ping_topic_read($topic_id)
{
	$member_id=get_member();
	$GLOBALS['FORUM_DB']->query_delete('f_read_logs',array('l_member_id'=>$member_id,'l_topic_id'=>$topic_id),'',1);
	$GLOBALS['FORUM_DB']->query_insert('f_read_logs',array('l_member_id'=>$member_id,'l_topic_id'=>$topic_id,'l_time'=>time()),false,true); // race condition
}

/**
 * Find whether a member has read a certain topic, such that they have possibly read all posts within it already.
 *
 * @param  AUTO_LINK The ID of the topic.
 * @param  ?TIME		The time of the last post in the topic (NULL: get it from the DB).
 * @param  ?MEMBER	The member (NULL: current member).
 * @param  ?TIME		The time the member last viewed the topic (NULL: get it from the DB).
 * @return boolean	They have read it as such, yes.
 */
function ocf_has_read_topic($topic_id,$topic_last_time=NULL,$member_id=NULL,$member_last_time=NULL)
{
	if (is_null($member_id)) $member_id=get_member();
	if ($member_id==$GLOBALS['OCF_DRIVER']->get_guest_id()) return true;

	if (is_null($topic_last_time))
		$topic_last_time=$GLOBALS['FORUM_DB']->query_value('f_topics','t_cache_last_time',array('id'=>$topic_id));

	$seven_days_ago=time()-60*60*24*intval(get_option('post_history_days'));

	// Occasionally we need to delete old entries
	if (mt_rand(0,1000)==123)
	{
		if (!$GLOBALS['SITE_DB']->table_is_locked('f_read_logs'))
			$GLOBALS['FORUM_DB']->query('DELETE FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs WHERE l_time<'.strval($seven_days_ago));
	}

	if ($topic_last_time<$seven_days_ago) return true; // We don't store that old
	if (is_null($member_last_time))
		$member_last_time=$GLOBALS['FORUM_DB']->query_value_null_ok('f_read_logs','l_time',array('l_member_id'=>$member_id,'l_topic_id'=>$topic_id));
	if (is_null($member_last_time)) return false;
	if ($member_last_time<$topic_last_time) return false;
	return true;
}

/**
 * Find whether a member has special access to a certain PT.
 *
 * @param  AUTO_LINK The ID of the topic.
 * @param  ?MEMBER	The member (NULL: current member).
 * @return boolean	Whether they have special access.
 */
function ocf_has_special_pt_access($topic_id,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	static $special_pt_access_cache=array();

	if (!array_key_exists($topic_id,$special_pt_access_cache))
		$special_pt_access_cache[$topic_id]=$GLOBALS['FORUM_DB']->query_select('f_special_pt_access',array('s_member_id'),array('s_topic_id'=>$topic_id));
	foreach ($special_pt_access_cache[$topic_id] as $t)
	{
		if ($t['s_member_id']==$member_id) return true;
	}
	return false;
}
