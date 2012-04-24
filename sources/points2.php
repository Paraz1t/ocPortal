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
 * @package		points
 */

/**
 * Standard code module initialisation function.
 */
function init__points2()
{
	require_code('points');
}

/**
 * Transfer gift-points into the specified member's account, courtesy of the system.
 *
 * @param  SHORT_TEXT	The reason for the transfer
 * @param  integer		The size of the transfer
 * @param  MEMBER			The member the transfer is to
 */
function system_gift_transfer($reason,$amount,$member_id)
{
	require_lang('points');
	require_code('points');

	if (is_guest($member_id)) return;
	if ($amount==0) return;

	$GLOBALS['SITE_DB']->query_insert('gifts',array('date_and_time'=>time(),'amount'=>$amount,'gift_from'=>$GLOBALS['FORUM_DRIVER']->get_guest_id(),'gift_to'=>$member_id,'reason'=>insert_lang_comcode($reason,4),'anonymous'=>1));
	$_before=point_info($member_id);
	$before=array_key_exists('points_gained_given',$_before)?$_before['points_gained_given']:0;
	$new=strval($before+$amount);
	$GLOBALS['FORUM_DRIVER']->set_custom_field($member_id,'points_gained_given',$new);

	global $TOTAL_POINTS_CACHE,$POINT_INFO_CACHE;
	if (array_key_exists($member_id,$TOTAL_POINTS_CACHE)) $TOTAL_POINTS_CACHE[$member_id]+=$amount;
	if ((array_key_exists($member_id,$POINT_INFO_CACHE)) && (array_key_exists('points_gained_given',$POINT_INFO_CACHE[$member_id])))
		$POINT_INFO_CACHE[$member_id]['points_gained_given']+=$amount;
	
	if (get_forum_type()=='ocf')
	{
		require_code('ocf_posts_action');
		require_code('ocf_posts_action2');
		ocf_member_handle_promotion($member_id);
	}
}

/**
 * Give a member some points, from another member.
 *
 * @param  integer		The amount being given
 * @param  MEMBER			The member receiving the points
 * @param  MEMBER			The member sending the points
 * @param  SHORT_TEXT	The reason for the gift
 * @param  boolean		Does the sender want to remain anonymous?
 * @param  boolean		Whether to send out an email about it
 */
function give_points($amount,$recipient_id,$sender_id,$reason,$anonymous=false,$send_email=true)
{
	require_lang('points');
	require_code('points');

	$your_username=$GLOBALS['FORUM_DRIVER']->get_username($sender_id);
	$GLOBALS['SITE_DB']->query_insert('gifts',array('date_and_time'=>time(),'amount'=>$amount,'gift_from'=>$sender_id,'gift_to'=>$recipient_id,'reason'=>insert_lang_comcode($reason,4),'anonymous'=>$anonymous?1:0));
	$sender_gift_points_used=point_info($sender_id);
	$sender_gift_points_used=array_key_exists('gift_points_used',$sender_gift_points_used)?$sender_gift_points_used['gift_points_used']:0;
	$GLOBALS['FORUM_DRIVER']->set_custom_field($sender_id,'gift_points_used',strval($sender_gift_points_used+$amount));
	$temp_points=point_info($recipient_id);
	$GLOBALS['FORUM_DRIVER']->set_custom_field($recipient_id,'points_gained_given',strval((array_key_exists('points_gained_given',$temp_points)?$temp_points['points_gained_given']:0)+$amount));
	$their_username=$GLOBALS['FORUM_DRIVER']->get_username($recipient_id);
	if (is_null($their_username)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',$recipient_id));
	$yes=$GLOBALS['FORUM_DRIVER']->get_member_email_allowed($recipient_id);
	if (($yes) && ($send_email))
	{
		$_url=build_url(array('page'=>'points','type'=>'member','id'=>$recipient_id),get_module_zone('points'),NULL,false,false,true);
		$url=$_url->evaluate();
		require_code('notifications');
		if ($anonymous)
		{
			$message_raw=do_lang('GIVEN_POINTS_FOR_ANON',comcode_escape(get_site_name()),comcode_escape(integer_format($amount)),array(comcode_escape($reason),comcode_escape($url)),get_lang($recipient_id));
			dispatch_notification('received_points',NULL,do_lang('YOU_GIVEN_POINTS',number_format($amount),NULL,NULL,get_lang($recipient_id)),$message_raw,array($recipient_id),A_FROM_SYSTEM_UNPRIVILEGED);
		} else
		{
			$message_raw=do_lang('GIVEN_POINTS_FOR',comcode_escape(get_site_name()),comcode_escape(integer_format($amount)),array(comcode_escape($reason),comcode_escape($url),comcode_escape($your_username)),get_lang($recipient_id));
			dispatch_notification('received_points',NULL,do_lang('YOU_GIVEN_POINTS',number_format($amount),NULL,NULL,get_lang($recipient_id)),$message_raw,array($recipient_id),$sender_id);
		}
		$message_raw=do_lang('USER_GIVEN_POINTS_FOR',comcode_escape($their_username),comcode_escape(integer_format($amount)),array(comcode_escape($reason),comcode_escape($url),comcode_escape($your_username)),get_site_default_lang());
		dispatch_notification('receive_points_staff',NULL,do_lang('USER_GIVEN_POINTS',number_format($amount),NULL,NULL,get_site_default_lang()),$message_raw,NULL,$sender_id);
	}

	if (get_forum_type()=='ocf')
	{
		require_code('ocf_posts_action');
		require_code('ocf_posts_action2');
		ocf_member_handle_promotion($recipient_id);
	}

	global $TOTAL_POINTS_CACHE,$POINT_INFO_CACHE;
	if (array_key_exists($recipient_id,$TOTAL_POINTS_CACHE)) $TOTAL_POINTS_CACHE[$recipient_id]+=$amount;
	if ((array_key_exists($recipient_id,$POINT_INFO_CACHE)) && (array_key_exists('points_gained_given',$POINT_INFO_CACHE[$recipient_id])))
		$POINT_INFO_CACHE[$recipient_id]['points_gained_given']+=$amount;
	if ((array_key_exists($sender_id,$POINT_INFO_CACHE)) && (array_key_exists('gift_points_used',$POINT_INFO_CACHE[$sender_id])))
		$POINT_INFO_CACHE[$sender_id]['gift_points_used']+=$amount;

	if (!$anonymous)
	{
		if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'points'))
			syndicate_described_activity(((is_null($recipient_id)) || (is_guest($recipient_id)))?'points:_ACTIVITY_GIVE_POINTS':'points:ACTIVITY_GIVE_POINTS',$reason,number_format($amount),'','_SEARCH:points:member:'.strval($recipient_id),'','','points',1,NULL,false,$recipient_id);
	}
}

/**
 * Charge points from a specified member's account.
 *
 * @param  MEMBER			The member that is being charged
 * @param  integer		The amount being charged
 * @param  SHORT_TEXT	The reason for the charging
 */
function charge_member($member_id,$amount,$reason)
{
	require_lang('points');
	require_code('points');

	$_before=point_info($member_id);
	$before=array_key_exists('points_used',$_before)?intval($_before['points_used']):0;
	$new=$before+$amount;
	$GLOBALS['FORUM_DRIVER']->set_custom_field($member_id,'points_used',strval($new));
	add_to_charge_log($member_id,$amount,$reason);

	global $TOTAL_POINTS_CACHE,$POINT_INFO_CACHE;
	if (array_key_exists($member_id,$TOTAL_POINTS_CACHE)) $TOTAL_POINTS_CACHE[$member_id]-=$amount;
	if ((array_key_exists($member_id,$POINT_INFO_CACHE)) && (array_key_exists('points_used',$POINT_INFO_CACHE[$member_id])))
		$POINT_INFO_CACHE[$member_id]['points_used']+=$amount;
}

/**
 * Add an entry to the change log.
 *
 * @param  MEMBER			The member that is being charged
 * @param  integer		The amount being charged
 * @param  SHORT_TEXT	The reason for the charging
 * @param  ?TIME			The time this is recorded to have happened (NULL: use current time)
 */
function add_to_charge_log($member_id,$amount,$reason,$time=NULL)
{
	if (is_null($time)) $time=time();
	$GLOBALS['SITE_DB']->query_insert('chargelog',array('user_id'=>$member_id,'amount'=>$amount,'reason'=>insert_lang_comcode($reason,4),'date_and_time'=>$time));
}

