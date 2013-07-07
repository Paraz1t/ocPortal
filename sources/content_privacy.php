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
 * @package		content_privacy
 */

/**
 * Get the SQL extension clauses for implementing privacy.
 *
 * @param  ID_TEXT	The content type
 * @param  ID_TEXT	The table alias in the main query
 * @param  ?MEMBER	Viewing member to check privacy against (NULL: current member)
 * @param  string		Additional OR clause for letting the user through
 * @return array		A pair: extra JOIN clause, extra WHERE clause
 */
function get_privacy_where_clause($content_type,$table_alias,$viewing_member_id=NULL,$additional_or='')
{
	if (is_null($viewing_member_id)) $viewing_member_id=get_member();

	require_code('content');
	$cma_ob=get_content_object($content_type);
	$cma_info=$cma_ob->info();

	$override_page=$cma_info['cms_page'];
	if (has_privilege($viewing_member_id,'view_private_content',$override_page)) return array('','');

	if (!$cma_info['supports_privacy']) return array('','');

	$join=' LEFT JOIN '.get_table_prefix().'content_privacy priv ON priv.content_id='.$table_alias.'.'.$cma_info['id_field'].' AND '.db_string_equal_to('priv.content_type',$content_type);
	$where=' AND (priv.content_id IS NULL';
	$where.=' OR priv.guest_view=1';
	if (!is_guest($viewing_member_id))
	{
		$where.=' OR priv.member_view=1';
		$where.=' OR priv.friend_view=1 AND EXISTS(SELECT * FROM '.get_table_prefix().'chat_friends f WHERE f.member_liked='.$table_alias.'.'.$cma_info['submitter_field'].' AND f.member_likes='.strval($viewing_member_id).')';
		$where.=' OR '.$table_alias.'.'.$cma_info['submitter_field'].'='.strval($viewing_member_id);
		$where.=' OR EXISTS(SELECT * FROM '.get_table_prefix().'content_primary__members pm WHERE pm.member_id='.strval($viewing_member_id).' AND pm.content_id='.$table_alias.'.'.$cma_info['id_field'].' AND '.db_string_equal_to('pm.content_type',$content_type).')';
		if ($additional_or!='') $where.=' OR '.$additional_or;
	}
	$where.=') ';
	return array($join,$where);
}

/**
 * Check to see if some content may be viewed.
 *
 * @param  ID_TEXT	The content type
 * @param  ID_TEXT	The content ID
 * @param  ?MEMBER	Viewing member to check privacy against (NULL: current member)
 * @return boolean	Whether there is access
 */
function has_privacy_access($content_type,$content_id,$viewing_member_id=NULL)
{
	if (is_null($viewing_member_id)) $viewing_member_id=get_member();

	require_code('content');
	$cma_ob=get_content_object($content_type);
	$cma_info=$cma_ob->info();

	$override_page=$cma_info['cms_page'];
	if (has_privilege($viewing_member_id,'view_private_content',$override_page)) return true;

	list($privacy_join,$privacy_where)=get_privacy_where_clause($content_type,'e',$viewing_member_id);

	$results=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().$cma_info['table'].' e '.$privacy_join.' WHERE '.$privacy_where);
	return array_key_exists(0,$results);
}

/**
 * Check to see if some content may be viewed. Exit with an access denied if not.
 *
 * @param  ID_TEXT	The content type
 * @param  ID_TEXT	The content ID
 * @param  ?MEMBER	Viewing member to check privacy against (NULL: current member)
 */
function check_privacy($content_type,$content_id,$viewing_member_id=NULL)
{
	if (!has_privacy_access($content_type,$content_id,$viewing_member_id))
	{
		require_lang('content_privacy');
		access_denied('PRIVACY_BREACH');
	}
}

/**
 * Find list of members who may view some content.
 *
 * @param  ID_TEXT	The content type
 * @param  ID_TEXT	The content ID
 * @param  boolean	Whether to get a full list including friends even when there are over a thousand friends
 * @return ?array		A list of member IDs that have access (NULL: no restrictions)
 */
function privacy_limits_for($content_type,$content_id,$strict_all=false)
{
	$rows=$GLOBALS['SITE_DB']->query_select('content_privacy',array('*'),array('content_type'=>$content_type,'content_id'=>$content_id),'',1);
	if (!array_key_exists(0,$rows)) return NULL;

	$row=$rows[0];

	if ($row['guest_view']==1) return NULL;
	if ($row['member_view']==1) return NULL;

	$members=array();

	require_code('content');
	list(,$content_submitter)=content_get_details($content_type,$content_id);

	$members[]=$content_submitter;

	if ($row['friend_view']==1)
	{
		$cnt=$GLOBALS['SITE_DB']->query_select_value('chat_friends','COUNT(*)',array('chat_likes'=>$content_submitter));
		if (($strict_all) || ($cnt<=1000/*safety limit*/))
		{
			$friends=$GLOBALS['SITE_DB']->query_select('chat_friends',array('chat_liked'),array('chat_likes'=>$content_submitter));
			$members=array_merge($members,collapse_1d_complexity('member_liked',$friends));
		}
	}

	$GLOBALS['SITE_DB']->query_select('content_primary__members',array('member_id'),array('content_type'=>$content_type,'content_id'=>$content_id));
	$members=array_merge($members,collapse_1d_complexity('member_id',$friends));

	return $members;
}
