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
 * @package		core_ocf
 */

/**
 * Edit a topic.
 *
 * @param  ?AUTO_LINK	The ID of the topic to edit (NULL: Private Topic).
 * @param  ?SHORT_TEXT	Description of the topic (NULL: do not change).
 * @param  ?SHORT_TEXT	The image code of the emoticon for the topic (NULL: do not change).
 * @param  ?BINARY		Whether the topic is validated (NULL: do not change).
 * @param  ?BINARY		Whether the topic is open (NULL: do not change).
 * @param  ?BINARY		Whether the topic is pinned (NULL: do not change).
 * @param  ?BINARY		Whether the topic is sunk (NULL: do not change).
 * @param  ?BINARY		Whether the topic is cascading (NULL: do not change).
 * @param  LONG_TEXT		The reason for this action.
 * @param  ?string		New title for the topic (NULL: do not change).
 * @param  ?SHORT_TEXT	Link related to the topic (e.g. link to view a ticket) (NULL: do not change).
 * @param  boolean		Whether to check permissions.
 * @param  ?integer		Number of views (NULL: do not change)
 * @param  boolean		Determines whether some NULLs passed mean 'use a default' or literally mean 'set to NULL'
 */
function ocf_edit_topic($topic_id,$description=NULL,$emoticon=NULL,$validated=NULL,$open=NULL,$pinned=NULL,$sunk=NULL,$cascading=NULL,$reason='',$title=NULL,$description_link=NULL,$check_perms=true,$views=NULL,$null_is_literal=false)
{
	$info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_pt_from','t_pt_to','t_cache_first_member_id','t_cache_first_title','t_forum_id','t_cache_first_post_id'),array('id'=>$topic_id),'',1);
	if (!array_key_exists(0,$info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$name=$info[0]['t_cache_first_title'];
	$forum_id=$info[0]['t_forum_id'];

	$update=array();

	require_code('ocf_forums');

	if ($check_perms)
	{
		if (!ocf_may_moderate_forum($forum_id))
		{
			$pinned=0;
			$sunk=0;
			if (($info[0]['t_cache_first_member_id']!=get_member()) || (!has_privilege(get_member(),'close_own_topics')))
				$open=1;
			$cascading=0;
		}

		if (!(($info[0]['t_cache_first_member_id']==get_member()) && (has_privilege(get_member(),'close_own_topics'))))
		{
			require_code('ocf_topics');
			if ((!ocf_may_edit_topics_by($forum_id,get_member(),$info[0]['t_cache_first_member_id'])) || ((($info[0]['t_pt_from']!=get_member()) && ($info[0]['t_pt_to']!=get_member())) && (!ocf_has_special_pt_access($topic_id)) && (!has_privilege(get_member(),'view_other_pt')) && (is_null($forum_id))))
				access_denied('I_ERROR');
		}

		if ((!is_null($forum_id)) && (!has_privilege(get_member(),'bypass_validation_midrange_content','topics',array('forums',$forum_id)))) $validated=NULL;
	}

	if (!is_null($title))
	{
		require_code('urls2');
		suggest_new_idmoniker_for('topicview','misc',strval($topic_id),'',$title);
	}

	if (!is_null($description)) $update['t_description']=$description;
	if (!is_null($description_link)) $update['t_description_link']=$description_link;
	if (!is_null($emoticon)) $update['t_emoticon']=$emoticon;
	if (!addon_installed('unvalidated')) $validated=1;
	if (!is_null($validated)) $update['t_validated']=$validated;
	if (!is_null($pinned)) $update['t_pinned']=$pinned;
	if (!is_null($sunk)) $update['t_sunk']=$sunk;
	if (!is_null($cascading)) $update['t_cascading']=$cascading;
	if (!is_null($open)) $update['t_is_open']=$open;
	if (!is_null($views)) $update['t_num_views']=$views;

	if ((!is_null($title)) && ($title!=''))
	{
		$update['t_cache_first_title']=$title;
		$GLOBALS['FORUM_DB']->query_update('f_posts',array('p_title'=>$title),array('id'=>$info[0]['t_cache_first_post_id']),'',1);
	}

	require_code('submit');
	$just_validated=(!content_validated('topic',strval($topic_id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('topic',strval($topic_id));
	}

	$GLOBALS['FORUM_DB']->query_update('f_topics',$update,array('id'=>$topic_id),'',1);

	if ((!is_null($title)) && ($title!=''))
	{
		require_code('ocf_posts_action2');
		ocf_force_update_forum_cacheing($forum_id,0,0);
	}
	require_code('ocf_general_action2');
	ocf_mod_log_it('EDIT_TOPIC',strval($topic_id),$name,$reason);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('topic',strval($topic_id));
	}

	if (!is_null($forum_id))
	{
		require_code('ocf_posts_action');
		ocf_decache_ocp_blocks($forum_id);
	} else
	{
		decache('side_ocf_private_topics');
		decache('_new_pp');
	}
}

/**
 * Delete a topic.
 *
 * @param  AUTO_LINK		The ID of the topic to delete.
 * @param  LONG_TEXT		The reason for this action .
 * @param  ?AUTO_LINK	Where topic to move posts in this topic to (NULL: delete the posts).
 * @param  boolean		Whether to check permissions.
 * @return AUTO_LINK		The forum ID the topic is in (could be found without calling the function, but as we've looked it up, it is worth keeping).
 */
function ocf_delete_topic($topic_id,$reason='',$post_target_topic_id=NULL,$check_perms=true)
{
	// Info about source
	$info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_pt_to','t_pt_from','t_cache_first_title','t_cache_first_member_id','t_poll_id','t_forum_id','t_cache_num_posts','t_validated'),array('id'=>$topic_id));
	if (!array_key_exists(0,$info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$name=$info[0]['t_cache_first_title'];
	$poll_id=$info[0]['t_poll_id'];
	$forum_id=$info[0]['t_forum_id'];
	$num_posts=$info[0]['t_cache_num_posts'];
	$validated=$info[0]['t_validated'];

	require_code('ocf_topics');
	if ($check_perms)
	{
		if (
			(!ocf_may_delete_topics_by($forum_id,get_member(),$info[0]['t_cache_first_member_id'])) ||
			(
				(
					((!is_null($info[0]['t_pt_from'])) && ($info[0]['t_pt_from']!=get_member())) &&
					((!is_null($info[0]['t_pt_to'])) && ($info[0]['t_pt_to']!=get_member()))
				) &&
				(!ocf_has_special_pt_access($topic_id)) &&
				(!has_privilege(get_member(),'view_other_pt')) &&
				(is_null($forum_id))
			)
		)
			access_denied('I_ERROR');
	}

	if (!is_null($post_target_topic_id))
	{
		$to=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_topics','t_forum_id',array('id'=>$post_target_topic_id));
		if (is_null($to)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	}

	if (!is_null($forum_id))
	{
		// Update member post counts if we've switched between post-count countable forums
		$post_count_info=$GLOBALS['FORUM_DB']->query('SELECT id,f_post_count_increment FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($forum_id).(!is_null($post_target_topic_id)?(' OR id='.strval($to)):''),2,NULL,false,true);
		if ($post_count_info[0]['id']==$forum_id)
		{
			$from_cnt=$post_count_info[0]['f_post_count_increment'];
			$to_cnt=(array_key_exists(1,$post_count_info))?$post_count_info[1]['f_post_count_increment']:0;
		} else
		{
			$from_cnt=$post_count_info[1]['f_post_count_increment'];
			$to_cnt=$post_count_info[0]['f_post_count_increment'];
		}
		require_code('ocf_posts_action');
		if ($from_cnt!=$to_cnt)
		{
			$_member_post_counts=collapse_1d_complexity('p_poster',$GLOBALS['FORUM_DB']->query_select('f_posts',array('p_poster'),array('p_topic_id'=>$topic_id)));
			$member_post_counts=array_count_values($_member_post_counts);

			foreach ($member_post_counts as $member_id=>$member_post_count)
			{
				if ($to_cnt==0) $member_post_count=-$member_post_count;
				ocf_force_update_member_post_count($member_id,$member_post_count);
			}
		}
	}

	// What to do with our posts
	if (!is_null($post_target_topic_id)) // If we were asked to move the posts into another topic
	{
		$GLOBALS['FORUM_DB']->query_update('f_posts',array('p_cache_forum_id'=>$to,'p_topic_id'=>$post_target_topic_id),array('p_topic_id'=>$topic_id));

		require_code('ocf_posts_action2');

		ocf_force_update_topic_cacheing($post_target_topic_id);

		if (!is_null($forum_id))
		{
			ocf_force_update_forum_cacheing($forum_id,$to,1,$num_posts);
		}
	} else
	{
		$_postdetails=array();
		do
		{
			$_postdetails=$GLOBALS['FORUM_DB']->query_select('f_posts',array('p_post','id'),array('p_topic_id'=>$topic_id),'',200);
			foreach ($_postdetails as $post)
			{
				delete_lang($post['p_post'],$GLOBALS['FORUM_DB']);
				$GLOBALS['FORUM_DB']->query_delete('f_posts',array('id'=>$post['id']),'',1);
			}
		}
		while (count($_postdetails)!=0);
	}

	// Delete stuff
	if (!is_null($poll_id))
	{
		require_code('ocf_polls_action');
		require_code('ocf_polls_action2');
		ocf_delete_poll($poll_id,'',false);
	}
	$GLOBALS['FORUM_DB']->query_delete('f_topics',array('id'=>$topic_id),'',1);
	$GLOBALS['FORUM_DB']->query_delete('f_read_logs',array('l_topic_id'=>$topic_id));
	require_code('notifications');
	delete_all_notifications_on('ocf_topic',strval($topic_id));

	// Delete the ticket row if it's a ticket
	if (addon_installed('tickets'))
	{
		require_code('tickets');
		if ((!is_null($forum_id)) && (is_ticket_forum($forum_id)))
		{
			require_code('tickets2');
			delete_ticket_by_topic_id($topic_id);
		}
	}

	// Update forum view cacheing
	if (!is_null($forum_id))
	{
		require_code('ocf_posts_action2');
		ocf_force_update_forum_cacheing($forum_id,($validated==0)?0:-1,-$num_posts);
	}

	require_code('ocf_general_action2');
	ocf_mod_log_it('DELETE_TOPIC',strval($topic_id),$name,$reason);

	if (!is_null($forum_id))
	{
		require_code('ocf_posts_action');
		ocf_decache_ocp_blocks($forum_id);
	} else
	{
		decache('side_ocf_private_topics');
		decache('_new_pp');
	}

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('topic',strval($topic_id));
	}

	return $forum_id;
}

/**
 * Move some topics.
 *
 * @param  AUTO_LINK		The forum the topics are currently in.
 * @param  AUTO_LINK		The forum the topics are being moved to.
 * @param  ?array 		A list of the topic IDs to move (NULL: move all topics from source forum).
 * @param  boolean		Whether to check permissions.
 */
function ocf_move_topics($from,$to,$topics=NULL,$check_perms=true) // NB: From is good to add a additional security/integrity. We'll never move from more than one forum. Extra constraints that cause no harm are good in a situation that doesn't govern general efficiency.
{
	if ($from==$to) return; // That would be nuts, and interfere with our logic

	require_code('notifications');
	require_code('ocf_topics');
	require_code('ocf_forums_action2');

	$forum_name=ocf_ensure_forum_exists($to);

	if ($check_perms)
	{
		require_code('ocf_forums');
		if (!ocf_may_moderate_forum($from))
			access_denied('I_ERROR');
	}

	$topic_count=0;

	if (is_null($topics)) // All of them
	{
		if (is_null($from)) access_denied('I_ERROR');

		$all_topics=$GLOBALS['FORUM_DB']->query_select('f_topics',array('id','t_cache_num_posts','t_validated'),array('t_forum_id'=>$from));
		$or_list='';
		$post_count=0;
		$topics=array();
		foreach ($all_topics as $topic_info)
		{
			$topics[]=$topic_info['id'];
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='id='.strval($topic_info['id']);
			$post_count+=$topic_info['t_cache_num_posts'];
			if ($topic_info['t_validated']==1) $topic_count++;
		}

		$GLOBALS['FORUM_DB']->query_update('f_topics',array('t_forum_id'=>$to),array('t_forum_id'=>$from));

		// Update forum IDs' for posts
		$GLOBALS['FORUM_DB']->query_update('f_posts',array('p_cache_forum_id'=>$to),array('p_cache_forum_id'=>$from));

		$or_list_2=str_replace('id','p_topic_id',$or_list);
		if ($or_list_2=='') return;
	}
	elseif (count($topics)==1) // Just one
	{
		$topic_info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_forum_id','t_pt_from','t_pt_to','t_cache_first_title','t_cache_num_posts','t_validated'),array('id'=>$topics[0]));
		if (!array_key_exists(0,$topic_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		if (($topic_info[0]['t_forum_id']!=$from) || ((($topic_info[0]['t_pt_from']!=get_member()) && ($topic_info[0]['t_pt_to']!=get_member())) && (!ocf_has_special_pt_access($topics[0])) && (!has_privilege(get_member(),'view_other_pt')) && (is_null($topic_info[0]['t_forum_id']))))
			access_denied('I_ERROR');
		if ($topic_info[0]['t_validated']==1) $topic_count++;
		$topic_title=$topic_info[0]['t_cache_first_title'];
		$post_count=$topic_info[0]['t_cache_num_posts'];
		$GLOBALS['FORUM_DB']->query_update('f_topics',array('t_pt_from'=>NULL,'t_pt_to'=>NULL,'t_forum_id'=>$to),array('t_forum_id'=>$from,'id'=>$topics[0]),'',1); // Extra where constraint for added security
		log_it('MOVE_TOPICS',$topic_title,strval($topics[0]));
		$or_list='id='.strval($topics[0]);
		$or_list_2='p_topic_id='.strval($topics[0]);

		// Update forum IDs' for posts
		$GLOBALS['FORUM_DB']->query_update('f_posts',array('p_cache_forum_id'=>$to),array('p_topic_id'=>$topics[0]));
	}
	else // Unknown number
	{
		if (count($topics)==0) return; // Nuts, lol

		$or_list='';
		foreach ($topics as $topic_id)
		{
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='id='.strval($topic_id);

			if (is_null($from))
			{
				$topic_info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_forum_id','t_pt_from','t_pt_to'),array('id'=>$topic_id));
				if (array_key_exists(0,$topic_info))
				{
					if ($topic_info[0]['t_validated']==1) $topic_count++;

					if (($topic_info[0]['t_forum_id']!=$from) || ((($topic_info[0]['t_pt_from']!=get_member()) && ($topic_info[0]['t_pt_to']!=get_member())) && (!ocf_has_special_pt_access($topic_id)) && (!has_privilege(get_member(),'view_other_pt'))))
						access_denied('I_ERROR');
				}
			} else
			{
				$topic_count++; // Might not be validated, which means technically we shouldn't do this, but it's low chance, low impact, and the indicator is only a cache thing anyway
			}
		}

		$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics SET t_forum_id='.strval($to).',t_pt_from=NULL,t_pt_to=NULL WHERE t_forum_id'.(is_null($from)?' IS NULL':('='.strval($from))).' AND ('.$or_list.')',NULL,NULL,false,true);
		log_it('MOVE_TOPICS',do_lang('MULTIPLE'));

		$post_count=$GLOBALS['FORUM_DB']->query_value_if_there('SELECT SUM(t_cache_num_posts) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE '.$or_list,false,true);

		// Update forum IDs' for posts
		$or_list_2=str_replace('id','p_topic_id',$or_list);
		$GLOBALS['FORUM_DB']->query('UPDATE '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts SET p_cache_forum_id='.strval($to).' WHERE '.$or_list_2,NULL,NULL,false,true);
	}

	require_code('ocf_posts_action2');

	// Update source forum cache view
	if (!is_null($from)) ocf_force_update_forum_cacheing($from,-$topic_count,-$post_count);

	// Update dest forum cache view
	ocf_force_update_forum_cacheing($to,$topic_count,$post_count);

	if (!is_null($from))
	{
		// Update member post counts if we've switched between post-count countable forums
		$post_count_info=$GLOBALS['FORUM_DB']->query('SELECT id,f_post_count_increment FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE id='.strval($from).' OR id='.strval($to),2);
		if ($post_count_info[0]['id']==$from)
		{
			$from_cnt=$post_count_info[0]['f_post_count_increment'];
			$to_cnt=$post_count_info[1]['f_post_count_increment'];
		} else
		{
			$from_cnt=$post_count_info[1]['f_post_count_increment'];
			$to_cnt=$post_count_info[0]['f_post_count_increment'];
		}
		require_code('ocf_posts_action');
		if ($from_cnt!=$to_cnt)
		{
			$_member_post_counts=collapse_1d_complexity('p_poster',$GLOBALS['FORUM_DB']->query('SELECT p_poster FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE '.$or_list_2,NULL,NULL,false,true));
			$member_post_counts=array_count_values($_member_post_counts);

			foreach ($member_post_counts as $member_id=>$member_post_count)
			{
				if ($to==0) $member_post_count=-$member_post_count;
				ocf_force_update_member_post_count($member_id,$member_post_count);
			}
		}
	}

	require_code('ocf_posts_action');
	if (!is_null($from))
	{
		ocf_decache_ocp_blocks($from);
	} else
	{
		decache('side_ocf_private_topics');
		decache('_new_pp');
	}
	ocf_decache_ocp_blocks($to,$forum_name);

	require_code('tasks');
	call_user_func_array__long_task(do_lang('MOVE_TOPICS'),get_screen_title('MOVE_TOPICS'),'notify_topics_moved',array($or_list,$forum_name),false,false,false);
}

/**
 * Invite a member to a PT.
 *
 * @param  MEMBER			Member getting access
 * @param  AUTO_LINK		The topic
 */
function ocf_invite_to_pt($member_id,$topic_id)
{
	$topic_info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('*'),array('id'=>$topic_id),'',1);
	if (!array_key_exists(0,$topic_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	if (($topic_info[0]['t_pt_from']!=get_member()) && ($topic_info[0]['t_pt_to']!=get_member()) && (!has_privilege(get_member(),'view_other_pt')))
		warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

	if (($topic_info[0]['t_pt_from']==$member_id) || ($topic_info[0]['t_pt_to']==$member_id))
		warn_exit(do_lang_tempcode('NO_INVITE_SENSE'));

	$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_special_pt_access','s_member_id',array(
		's_member_id'=>$member_id,
		's_topic_id'=>$topic_id,
	));
	if (!is_null($test)) warn_exit(do_lang_tempcode('NO_INVITE_SENSE_ALREADY'));
	$GLOBALS['FORUM_DB']->query_insert('f_special_pt_access',array(
		's_member_id'=>$member_id,
		's_topic_id'=>$topic_id,
	));

	$current_displayname=$GLOBALS['FORUM_DRIVER']->get_username(get_member(),true);
	$current_username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
	$displayname=$GLOBALS['FORUM_DRIVER']->get_username($member_id,true);
	$username=$GLOBALS['FORUM_DRIVER']->get_username($member_id);

	$_topic_url=build_url(array('page'=>'topicview','type'=>'view','id'=>$topic_id),get_module_zone('topicview'),NULL,false,false,true);
	$topic_url=$_topic_url->evaluate();
	$topic_title=$topic_info[0]['t_cache_first_title'];

	require_code('ocf_posts_action');
	$post=do_lang('INVITED_TO_PT',$username,$current_displayname,$current_username,$displayname);
	ocf_make_post($topic_id,'',$post,0,false,1,1,do_lang('SYSTEM'),NULL,NULL,db_get_first_id(),NULL,NULL,NULL,false);

	require_code('notifications');
	$subject=do_lang('INVITED_TO_TOPIC_SUBJECT',get_site_name(),$topic_title,get_lang($member_id));
	$mail=do_lang('INVITED_TO_TOPIC_BODY',get_site_name(),comcode_escape($topic_title),array(comcode_escape($current_username),$topic_url),get_lang($member_id));
	dispatch_notification('ocf_topic_invite',NULL,$subject,$mail,array($member_id));
}

/**
 * Send a new-PT notification.
 *
 * @param  AUTO_LINK		The ID of the post made
 * @param  SHORT_TEXT	PT title
 * @param  AUTO_LINK		ID of the topic
 * @param  MEMBER			Member getting the PT
 * @param  ?MEMBER		Member posting the PT (NULL: current member)
 * @param  ?mixed			Post language ID or post text (NULL: unknown, lookup from $post_id)
 * @param  boolean		Whether to also mark the topic as unread
 */
function send_pt_notification($post_id,$subject,$topic_id,$to_id,$from_id=NULL,$post=NULL,$mark_unread=false)
{
	if (is_null($from_id)) $from_id=get_member();

	$post_lang_id=is_integer($post)?$post:$GLOBALS['FORUM_DB']->query_select_value('f_posts','p_post',array('id'=>$post_id));
	$post_comcode=get_translated_text((integer)$post_lang_id,$GLOBALS['FORUM_DB']);

	require_code('notifications');
	$msubject=do_lang('NEW_PRIVATE_TOPIC_SUBJECT',$subject,NULL,NULL,get_lang($to_id));
	$mmessage=do_lang('NEW_PRIVATE_TOPIC_MESSAGE',comcode_escape($GLOBALS['FORUM_DRIVER']->get_username($from_id,true)),comcode_escape($subject),array(comcode_escape($GLOBALS['FORUM_DRIVER']->topic_url($topic_id)),$post_comcode,strval($from_id)),get_lang($to_id));
	dispatch_notification('ocf_new_pt',NULL,$msubject,$mmessage,array($to_id),$from_id);

	if ($mark_unread)
	{
		$GLOBALS['FORUM_DB']->query_delete('f_read_logs',array('l_topic_id'=>$topic_id,'l_member_id'=>$to_id),'',1);
	}
}

/**
 * If necessary, send out a support ticket reply
 *
 * @param  AUTO_LINK		Forum ID
 * @param  AUTO_LINK		Topic ID
 * @param  SHORT_TEXT	Topic title
 * @param  LONG_TEXT		Post made
 */
function handle_topic_ticket_reply($forum_id,$topic_id,$topic_title,$post)
{
	// E-mail the user or staff if the post is a new one in a support ticket
	if (addon_installed('tickets'))
	{
		require_code('tickets');
		require_code('tickets2');
		require_code('feedback');
		if (is_ticket_forum($forum_id))
		{
			$topic_info=$GLOBALS['FORUM_DB']->query_select('f_topics',array('t_cache_first_title','t_sunk','t_forum_id','t_is_open','t_description'),array('id'=>$topic_id),'',1);
			if (!array_key_exists(0,$topic_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

			$topic_description=$topic_info[0]['t_description'];
			$ticket_id=extract_topic_identifier($topic_description);
			$home_url=build_url(array('page'=>'tickets','type'=>'ticket','id'=>$ticket_id),'site',NULL,false,true);

			send_ticket_email($ticket_id,$topic_title,$post,$home_url->evaluate(),'',-1);
		}
	}
}

