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
 * @package		ocf_forum
 */

/**
 * Find the URL to a post.
 *
 * @param  AUTO_LINK		The post ID.
 * @return URLPATH		The URL.
 */
function find_post_id_url($post_id)
{
	$max=intval(get_option('forum_posts_per_page'));
	if ($max==0) $max=1;

	$id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_posts','p_topic_id',array('id'=>$post_id));
	if (is_null($id)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

	// What page is it on?
	$before=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE id<'.strval((integer)$post_id).' AND '.ocf_get_topic_where($id));
	$start=intval(floor(floatval($before)/floatval($max)))*$max;

	// Now redirect accordingly
	$map=array('page'=>'topicview','type'=>NULL,'id'=>$id,'start'=>($start==0)?NULL:$start);
	foreach ($_GET as $key=>$val)
		if ((substr($key,0,3)=='kfs') || (in_array($key,array('start','max')))) $map[$key]=$val;
	$_redirect=build_url($map,'_SELF',NULL,true);
	$redirect=$_redirect->evaluate();
	$redirect.='#post_'.strval($post_id);

	return $redirect;
}

/**
 * Find the URL to the latest unread post in a topic.
 *
 * @param  AUTO_LINK		The topic ID.
 * @return URLPATH		The URL.
 */
function find_first_unread_url($id)
{
	$max=intval(get_option('forum_posts_per_page'));
	if ($max==0) $max=1;

	$last_read_time=$GLOBALS['FORUM_DB']->query_value_null_ok('f_read_logs','l_time',array('l_member_id'=>get_member(),'l_topic_id'=>$id));
	if (is_null($last_read_time))
	{
		// Assumes that everything made in the last two weeks has not been read
		$unread_details=$GLOBALS['FORUM_DB']->query('SELECT id,p_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_topic_id='.strval((integer)$id).' AND p_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))).' ORDER BY id',1);
		if (array_key_exists(0,$unread_details))
		{
			$last_read_time=$unread_details[0]['p_time']-1;
		} else $last_read_time=0;
	}
	$first_unread_id=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE p_topic_id='.strval((integer)$id).' AND p_time>'.strval((integer)$last_read_time).' ORDER BY id');
	if (!is_null($first_unread_id))
	{
		// What page is it on?
		$before=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE id<'.strval((integer)$first_unread_id).' AND '.ocf_get_topic_where($id));
		$start=intval(floor(floatval($before)/floatval($max)))*$max;
	} else
	{
		$first_unread_id=-2;

		// What page is it on?
		$before=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE '.ocf_get_topic_where($id));
		$start=intval(floor(floatval($before)/floatval($max)))*$max;
		if ($start==$before) $start=$before-$max;
	}

	// Now redirect accordingly
	$map=array('page'=>'topicview','id'=>$id,'type'=>NULL,'start'=>($start==0)?NULL:$start);
	foreach ($_GET as $key=>$val)
		if ((substr($key,0,3)=='kfs') || (in_array($key,array('start','max')))) $map[$key]=$val;
	$_redirect=build_url($map,'_SELF',NULL,true);
	$redirect=$_redirect->evaluate();
	if ($first_unread_id>0) $redirect.='#post_'.strval($first_unread_id); else $redirect.='#first_unread';

	return $redirect;
}

/**
 * Turn a post row, into a detailed map of information that is suitable for use as display parameters for that post.
 *
 * @param  array		The post row.
 * @param  boolean	Whether the post is the only post in the topic.
 * @return array		The detailed map.
 */
function ocf_get_details_to_show_post($_postdetails,$only_post=false)
{
	$forum_id=$_postdetails['p_cache_forum_id'];

	$primary_group=ocf_get_member_primary_group($_postdetails['p_poster']);
	if (is_null($primary_group))
	{
		$_postdetails['p_poster']=db_get_first_id();
		$primary_group=db_get_first_id();
	}

	$post=array('id'=>$_postdetails['id'],
					'topic_id'=>$_postdetails['p_topic_id'],
					'title'=>$_postdetails['p_title'],
					'post'=>$_postdetails['message'],
					'time'=>$_postdetails['p_time'],
					'time_string'=>get_timezoned_date($_postdetails['p_time']),
					'validated'=>$_postdetails['p_validated'],
					'is_emphasised'=>$_postdetails['p_is_emphasised'],
					'poster_username'=>$_postdetails['p_poster_name_if_guest'],
					'poster'=>$_postdetails['p_poster'],
					'has_history'=>!is_null($_postdetails['h_post_id'])
	);

	if (array_key_exists('message_comcode',$_postdetails))
	{
		$post['message_comcode']=$_postdetails['message_comcode'];
	}

	// Edited?
	if (!is_null($_postdetails['p_last_edit_by']))
	{
		$post['last_edit_by']=$_postdetails['p_last_edit_by'];
		$post['last_edit_time']=$_postdetails['p_last_edit_time'];
		$post['last_edit_time_string']=get_timezoned_date($_postdetails['p_last_edit_time']);
		$post['last_edit_by_username']=$GLOBALS['OCF_DRIVER']->get_username($_postdetails['p_last_edit_by']);
		if ($post['last_edit_by_username']=='') $post['last_edit_by_username']=do_lang('UNKNOWN'); // Shouldn't happen, but imported data can be weird
	}

	// Find title
	$title=addon_installed('ocf_member_titles')?$GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_title'):'';
	if ($title=='') $title=get_translated_text(ocf_get_group_property($primary_group,'title'),$GLOBALS['FORUM_DB']);
	$post['poster_title']=$title;

	// If this isn't guest posted, we can put some member details in
	if ((!is_null($_postdetails['p_poster'])) && ($_postdetails['p_poster']!=$GLOBALS['OCF_DRIVER']->get_guest_id()))
	{
		if (addon_installed('points'))
		{
			require_code('points');
			$post['poster_points']=total_points($_postdetails['p_poster']);
		}
		$post['poster_posts']=$GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_cache_num_posts');
		$post['poster_highlighted_name']=$GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_highlighted_name');

		// Signature
		if ((($GLOBALS['OCF_DRIVER']->get_member_row_field(get_member(),'m_views_signatures')==1) || (get_value('disable_views_sigs_option')==='1')) && ($_postdetails['p_skip_sig']==0) && (addon_installed('ocf_signatures')))
		{
			global $SIGNATURES_CACHE;
			if (array_key_exists($_postdetails['p_poster'],$SIGNATURES_CACHE))
			{
				$sig=$SIGNATURES_CACHE[$_postdetails['p_poster']];
			} else
			{
				$sig=get_translated_tempcode($GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_signature'),$GLOBALS['FORUM_DB']);
				$SIGNATURES_CACHE[$_postdetails['p_poster']]=$sig;
			}
			$post['signature']=$sig;
		}

		// Any custom fields to show?
		$post['custom_fields']=ocf_get_all_custom_fields_match_member($_postdetails['p_poster'],((get_member()!=$_postdetails['p_poster']) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL,((get_member()==$_postdetails['p_poster']) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL,NULL,NULL,NULL,1);

		// Usergroup
		$post['primary_group']=$primary_group;
		$post['primary_group_name']=ocf_get_group_name($primary_group);

		// Find avatar
		$avatar=$GLOBALS['OCF_DRIVER']->get_member_avatar_url($_postdetails['p_poster']);
		if ($avatar!='')
		{
			$post['poster_avatar']=$avatar;
		}

		// Any warnings?
		if ((has_specific_permission(get_member(),'see_warnings')) && (addon_installed('ocf_warnings')))
		{
			$num_warnings=$GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_cache_warnings');
			/*if ($num_warnings!=0)*/ $post['poster_num_warnings']=$num_warnings;
		}

		// Join date
		$post['poster_join_date']=$GLOBALS['OCF_DRIVER']->get_member_row_field($_postdetails['p_poster'],'m_join_time');
		$post['poster_join_date_string']=get_timezoned_date($post['poster_join_date']);
	}
	elseif ($_postdetails['p_poster']==$GLOBALS['OCF_DRIVER']->get_guest_id())
	{
		if ($_postdetails['p_poster_name_if_guest']==do_lang('SYSTEM'))
		{
			$post['poster_avatar']=find_theme_image('ocf_default_avatars/default_set/ocp_fanatic',true);
		}
	}

	// Do we have any special controls over this post?
	require_code('ocf_posts');
	if (ocf_may_edit_post_by($_postdetails['p_poster'],$forum_id)) $post['may_edit']=true;
	if ((ocf_may_delete_post_by($_postdetails['p_poster'],$forum_id)) && (!$only_post)) $post['may_delete']=true;

	// More
	if (has_specific_permission(get_member(),'see_ip')) $post['ip_address']=$_postdetails['p_ip_address'];
	if (!is_null($_postdetails['p_intended_solely_for'])) $post['intended_solely_for']=$_postdetails['p_intended_solely_for'];

	return $post;
}

/**
 * Read in a great big map of details relating to a topic.
 *
 * @param  ?AUTO_LINK	The ID of the topic we are getting details of (NULL: whispers).
 * @param  integer		The start row for getting details of posts in the topic (i.e. 0 is start of topic, higher is further through).
 * @param  integer		The maximum number of posts to get detail of.
 * @param  boolean		Whether we are viewing poll results for the topic (if there is no poll for the topic, this is irrelevant).
 * @param  boolean		Whether to check permissions.
 * @return array			The map of details.
 */
function ocf_read_in_topic($topic_id,$start,$max,$view_poll_results=false,$check_perms=true)
{
	if (!is_null($topic_id))
	{
		$_topic_info=$GLOBALS['FORUM_DB']->query_select('f_topics t LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums f ON f.id=t.t_forum_id',array('t.*','f.f_is_threaded'),array('t.id'=>$topic_id),'',1);
		if (!array_key_exists(0,$_topic_info)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$topic_info=$_topic_info[0];

		// Are we allowed into here?
		//  Check forum
		$forum_id=$topic_info['t_forum_id'];
		if (!is_null($forum_id))
		{
			if ($check_perms)
			{
				if (!has_category_access(get_member(),'forums',strval($forum_id))) access_denied('CATEGORY_ACCESS_LEVEL');
			}
		} else
		{
			// It must be a personal topic. Do we have access?
			$from=$topic_info['t_pt_from'];
			$to=$topic_info['t_pt_to'];

			if (($from!=get_member()) && ($to!=get_member()) && (!ocf_has_special_pt_access($topic_id)) && (!has_specific_permission(get_member(),'view_other_pt')))
			{
				access_denied('SPECIFIC_PERMISSION','view_other_pt');
			}

			decache('_new_pp',array(get_member()));
			decache('side_ocf_personal_topics',array(get_member()));
		}
		// Check validated
		if ($topic_info['t_validated']==0)
		{
			if (!has_specific_permission(get_member(),'jump_to_unvalidated'))
				access_denied('SPECIFIC_PERMISSION','jump_to_unvalidated');
		}

		if (is_null(get_param_integer('threaded',NULL)))
		{
			if ($start>0)
			{
				if ($topic_info['f_is_threaded']==1)
				{
					$_GET['threaded']='0';
				}
			}
		}
		$is_threaded=get_param_integer('threaded',(is_null($topic_info['f_is_threaded'])?0:$topic_info['f_is_threaded']));

		// Some general info
		$out=array(
			'num_views'=>$topic_info['t_num_views'],
			'num_posts'=>$topic_info['t_cache_num_posts'],
			'validated'=>$topic_info['t_validated'],
			'title'=>$topic_info['t_cache_first_title'],
			'description'=>$topic_info['t_description'],
			'description_link'=>$topic_info['t_description_link'],
			'emoticon'=>$topic_info['t_emoticon'],
			'forum_id'=>$topic_info['t_forum_id'],
			'first_post'=>$topic_info['t_cache_first_post'],
			'first_poster'=>$topic_info['t_cache_first_member_id'],
			'first_post_id'=>$topic_info['t_cache_first_post_id'],
			'pt_from'=>$topic_info['t_pt_from'],
			'pt_to'=>$topic_info['t_pt_to'],
			'is_open'=>$topic_info['t_is_open'],
			'is_threaded'=>$is_threaded,
			'is_really_threaded'=>is_null($topic_info['f_is_threaded'])?0:$topic_info['f_is_threaded'],
			'last_time'=>$topic_info['t_cache_last_time'],
			'meta_data'=>array(
				'created'=>date('Y-m-d',$topic_info['t_cache_first_time']),
				'creator'=>$topic_info['t_cache_first_username'],
				'publisher'=>'', // blank means same as creator
				'modified'=>date('Y-m-d',$topic_info['t_cache_last_time']),
				'type'=>'Forum topic',
				'title'=>$topic_info['t_cache_first_title'],
				'identifier'=>'_SEARCH:topicview:misc:'.strval($topic_id),
				'numcomments'=>strval($topic_info['t_cache_num_posts']),
				'image'=>find_theme_image('bigicons/forums'),
			),
		);

		// Poll?
		if (!is_null($topic_info['t_poll_id']))
		{
			require_code('ocf_polls');
			$voted_already=$GLOBALS['FORUM_DB']->query_value_null_ok('f_poll_votes','pv_member_id',array('pv_poll_id'=>$topic_info['t_poll_id'],'pv_member_id'=>get_member()));
			$out['poll']=ocf_poll_get_results($topic_info['t_poll_id'],$view_poll_results || (!is_null($voted_already)));
			$out['poll']['voted_already']=$voted_already;
			$out['poll_id']=$topic_info['t_poll_id'];
		}

		// Post query
		$where=ocf_get_topic_where($topic_id);
		$query='SELECT p.*,t.text_parsed AS text_parsed,t.text_original AS message_comcode,h.h_post_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_post_history h ON (h.h_post_id=p.id AND h.h_action_date_and_time=p.p_last_edit_time) LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND p.p_post=t.id WHERE '.$where.' ORDER BY p_time,p.id';
	} else
	{
		$out=array(
			'num_views'=>0,
			'num_posts'=>0,
			'validated'=>1,
			'title'=>do_lang('INLINE_PERSONAL_POSTS'),
			'description'=>'',
			'description_link'=>'',
			'emoticon'=>'',
			'forum_id'=>NULL,
			'first_post'=>NULL,
			'first_poster'=>NULL,
			'first_post_id'=>NULL,
			'pt_from'=>NULL,
			'pt_to'=>NULL,
			'is_open'=>1,
			'is_threaded'=>0,
			'last_time'=>time(),
			'meta_data'=>array(),
		);

		// Post query
		$where='p_intended_solely_for='.strval(get_member());
		$query='SELECT p.*,t.text_parsed AS text_parsed,t.text_original AS message_comcode,h.h_post_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_post_history h ON (h.h_post_id=p.id AND h.h_action_date_and_time=p.p_last_edit_time) LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND p.p_post=t.id WHERE '.$where.' ORDER BY p_time,p.id';
	}

	// Posts
	if ($out['is_threaded']==0)
	{
		$_postdetailss=list_to_map('id',$GLOBALS['FORUM_DB']->query($query,$max,$start));
		if (($start==0) && (count($_postdetailss)<$max)) $out['max_rows']=$max; // We know that they're all on this screen
		else $out['max_rows']=$GLOBALS['FORUM_DB']->query_value_null_ok_full('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE '.$where);
		$posts=array();
		// Precache member/group details in one fell swoop
		$members=array();
		foreach ($_postdetailss as $_postdetails)
		{
			$members[$_postdetails['p_poster']]=1;
			if ($out['title']=='') $out['title']=$_postdetails['p_title'];
		}
		ocf_cache_member_details(array_keys($members));

		$i=0;
		foreach ($_postdetailss as $_postdetails)
		{
			if (is_null($_postdetails['message_comcode'])) $_postdetails['message_comcode']=get_translated_text($_postdetails['p_post'],$GLOBALS['FORUM_DB']);

			$linked_type='';
			$linked_id='';
			$linked_url='';

			// If it's a spacer post, see if we can detect it better
			$is_spacer_post=(($i==0) && (substr($_postdetails['message_comcode'],0,strlen('[semihtml]'.do_lang('SPACER_POST_MATCHER')))=='[semihtml]'.do_lang('SPACER_POST_MATCHER')));
			if ($is_spacer_post)
			{
				$c_prefix=do_lang('COMMENT').': #';
				if ((substr($out['description'],0,strlen($c_prefix))==$c_prefix) && ($out['description_link']!=''))
				{
					list($linked_type,$linked_id)=explode('_',substr($out['description'],strlen($c_prefix)),2);
					$linked_url=$out['description_link'];
					$out['description']='';
				}
			}

			// Load post
			if ((get_page_name()=='search') || (is_null($_postdetails['text_parsed'])) || ($_postdetails['text_parsed']=='') || ($_postdetails['p_post']==0))
			{
				$_postdetails['message']=get_translated_tempcode($_postdetails['p_post'],$GLOBALS['FORUM_DB']);
			} else
			{
				$_postdetails['message']=new ocp_tempcode();
				if (!$_postdetails['message']->from_assembly($_postdetails['text_parsed'],true))
					$_postdetails['message']=get_translated_tempcode($_postdetails['p_post'],$GLOBALS['FORUM_DB']);
			}

			// Fake a quoted post? (kind of a nice 'tidy up' feature if a forum's threading has been turned off, leaving things for flat display)
			if ((!is_null($_postdetails['p_parent_id'])) && (strpos($_postdetails['message_comcode'],'[quote')===false))
			{
				$p=mixed(); // NULL
				if (array_key_exists($_postdetails['p_parent_id'],$_postdetailss)) // Ah, we're already loading it on this page
				{
					$p=$_postdetailss[$_postdetails['p_parent_id']];

					// Load post
					if ((get_page_name()=='search') || (is_null($p['text_parsed'])) || ($p['text_parsed']=='') || ($p['p_post']==0))
					{
						$p['message']=get_translated_tempcode($p['p_post'],$GLOBALS['FORUM_DB']);
					} else
					{
						$p['message']=new ocp_tempcode();
						if (!$p['message']->from_assembly($p['text_parsed'],true))
							$p['message']=get_translated_tempcode($p['p_post'],$GLOBALS['FORUM_DB']);
					}
				} else // Drat, we need to load it
				{
					$_p=$GLOBALS['FORUM_DB']->query_select('f_posts',array('*'),array('id'=>$_postdetails['p_parent_id']),'',1);
					if (array_key_exists(0,$_p))
					{
						$p=$_p[0];
						$p['message']=get_translated_tempcode($p['p_post'],$GLOBALS['FORUM_DB']);
					}
				}
				$temp=$_postdetails['message'];
				$_postdetails['message']=new ocp_tempcode();
				$_postdetails['message']=do_template('COMCODE_QUOTE_BY',array('SAIDLESS'=>false,'BY'=>$p['p_poster_name_if_guest'],'CONTENT'=>$p['message']));
				$_postdetails['message']->attach($temp);
			}

			// Spacer posts may have a better first post put in place
			if ($is_spacer_post)
			{
				require_code('ocf_posts');
				list($new_description,$new_post)=ocf_display_spacer_post($linked_type,$linked_id);
				//if (!is_null($new_description)) $out['description']=$new_description;	Actually, it's a bit redundant
				if (!is_null($new_post)) $_postdetails['message']=$new_post;

				$out['title']=do_lang('SPACER_TOPIC_TITLE_WRAP',$out['title']);
				$_postdetails['p_title']=do_lang('SPACER_TOPIC_TITLE_WRAP',$_postdetails['p_title']);
			}

			// Put together
			$collated_post_details=ocf_get_details_to_show_post($_postdetails,($start==0) && (count($_postdetailss)==1));
			$collated_post_details['is_spacer_post']=$is_spacer_post;
			$posts[]=$collated_post_details;

			$i++;
		}

		$out['posts']=$posts;
	}

	// Any special topic/for-any-post-in-topic controls?
	if (!is_null($topic_id))
	{
		$out['last_poster']=$topic_info['t_cache_last_member_id'];
		$out['last_post_id']=$topic_info['t_cache_last_post_id'];
		if ((is_null($forum_id)) || (ocf_may_post_in_topic($forum_id,$topic_id,$topic_info['t_cache_last_member_id'])))
			$out['may_reply']=true;
		if (ocf_may_report_post()) $out['may_report_posts']=true;
		if (ocf_may_make_personal_topic()) $out['may_pt_members']=true;
		if (ocf_may_edit_topics_by($forum_id,get_member(),$topic_info['t_cache_first_member_id'])) $out['may_edit_topic']=true;
		require_code('ocf_moderation');
		require_code('ocf_forums');
		if (ocf_may_warn_members()) $out['may_warn_members']=true;
		if (ocf_may_delete_topics_by($forum_id,get_member(),$topic_info['t_cache_first_member_id'])) $out['may_delete_topic']=true;
		if (ocf_may_perform_multi_moderation($forum_id)) $out['may_multi_moderate']=true;
		if (has_specific_permission(get_member(),'use_quick_reply')) $out['may_use_quick_reply']=true;
		$may_moderate_forum=ocf_may_moderate_forum($forum_id);
		if ($may_moderate_forum)
		{
			if ($topic_info['t_is_open']==0) $out['may_open_topic']=1; else $out['may_close_topic']=1;
			if ($topic_info['t_pinned']==0) $out['may_pin_topic']=1; else $out['may_unpin_topic']=1;
			if ($topic_info['t_sunk']==0) $out['may_sink_topic']=1; else $out['may_unsink_topic']=1;
			if ($topic_info['t_cascading']==0) $out['may_cascade_topic']=1; else $out['may_uncascade_topic']=1;
			$out['may_move_topic']=1;
			$out['may_post_closed']=1;
			$out['may_move_posts']=1;
			$out['may_delete_posts']=1;
			$out['may_validate_posts']=1;
			$out['may_make_personal']=1;
			$out['may_change_max']=1;
		} else
		{
			if (($topic_info['t_cache_first_member_id']==get_member()) && (has_specific_permission(get_member(),'close_own_topics')) && ($topic_info['t_is_open']==1))
			{
				$out['may_close_topic']=1;
			}
		}
		if (!is_null($topic_info['t_poll_id']))
		{
			require_code('ocf_polls');

			if (ocf_may_edit_poll_by($forum_id,$topic_info['t_cache_first_member_id']))
				$out['may_edit_poll']=1;
			if (ocf_may_delete_poll_by($forum_id,$topic_info['t_cache_first_member_id']))
				$out['may_delete_poll']=1;
		} else
		{
			require_code('ocf_polls');

			if (ocf_may_attach_poll($topic_id,$topic_info['t_cache_first_member_id'],!is_null($topic_info['t_poll_id']),$forum_id))
				$out['may_attach_poll']=1;
		}
	} else
	{
		$out['last_poster']=NULL;
		$out['last_post_id']=NULL;
		$out['may_reply']=false;
	}

	return $out;
}

/**
 * Mass-load details for a list of members into memory, to reduce queries when we access it later.
 *
 * @param  array			List of members.
 */
function ocf_cache_member_details($members)
{
	require_code('ocf_members');

	$member_or_list='';
	foreach ($members as $member)
	{
		if ($member_or_list!='') $member_or_list.=' OR ';
		$member_or_list.='m.id='.strval((integer)$member);
	}
	if ($member_or_list!='')
	{
		$member_rows=$GLOBALS['FORUM_DB']->query('SELECT m.*,text_parsed AS signature FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members m LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND m.m_signature=t.id WHERE '.$member_or_list);
		global $TABLE_LANG_FIELDS;
		$member_rows_2=$GLOBALS['FORUM_DB']->query('SELECT f.* FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_member_custom_fields f WHERE '.str_replace('m.id','mf_member_id',$member_or_list),NULL,NULL,false,false,array_key_exists('f_member_custom_fields',$TABLE_LANG_FIELDS)?$TABLE_LANG_FIELDS['f_member_custom_fields']:array());
		$member_rows_3=$GLOBALS['FORUM_DB']->query('SELECT gm_group_id,gm_member_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_group_members WHERE gm_validated=1 AND ('.str_replace('m.id','gm_member_id',$member_or_list).')');
		global $MEMBER_CACHE_FIELD_MAPPINGS,$GROUP_MEMBERS_CACHE,$SIGNATURES_CACHE;
		$found_groups=array();
		foreach ($member_rows as $row)
		{
			$GLOBALS['OCF_DRIVER']->MEMBER_ROWS_CACHED[$row['id']]=$row;

			if (!ocf_is_ldap_member($row['id']))
			{
				// Primary
				$pg=$GLOBALS['OCF_DRIVER']->get_member_row_field($row['id'],'m_primary_group');
				$found_groups[$pg]=1;
				$GROUP_MEMBERS_CACHE[$row['id']][false][false]=array($pg=>1);
			}

			// Signature
			if ((get_page_name()!='search') && (!is_null($row['signature'])) && ($row['signature']!='') && ($row['m_signature']!=0))
			{
				$SIGNATURES_CACHE[$row['id']]=new ocp_tempcode();
				if (!$SIGNATURES_CACHE[$row['id']]->from_assembly($row['signature'],true))
					unset($SIGNATURES_CACHE[$row['id']]);
			}
		}
		foreach ($member_rows_2 as $row)
		{
			$MEMBER_CACHE_FIELD_MAPPINGS[$row['mf_member_id']]=$row;
		}
		foreach ($member_rows_3 as $row)
		{
			if (!ocf_is_ldap_member($row['gm_member_id']))
			{
				$GROUP_MEMBERS_CACHE[$row['gm_member_id']][false][false][$row['gm_group_id']]=1;
				$found_groups[$row['gm_group_id']]=1;
			}
		}

		require_code('ocf_groups');
		ocf_ensure_groups_cached(array_keys($found_groups));
	}
}

/**
 * Get buttons for showing under a post.
 *
 * @param  array			Map of topic info.
 * @param  array			Map of post info.
 * @param  boolean		Whether the current member may reply to the topic
 * @return tempcode		The buttons.
 */
function ocf_render_post_buttons($topic_info,$_postdetails,$may_reply)
{
	require_lang('ocf');
	require_code('ocf_members2');
	$buttons=new ocp_tempcode();
	if ((array_key_exists('may_validate_posts',$topic_info)) && ((($topic_info['validated']==0) && ($_postdetails['id']==$topic_info['first_post_id'])) || ($_postdetails['validated']==0)))
	{
		$map=array('page'=>'topics','type'=>'validate_post','id'=>$_postdetails['id']);
		$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
		if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
		$test=get_param_integer('threaded',-1);
		if ($test!=-1) $map['threaded']=$test;
		$action_url=build_url($map,get_module_zone('topics'));
		$_title=do_lang_tempcode('VALIDATE_POST');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'712fdaee35f378e37b007f3a73246690','REL'=>'validate','IMMEDIATE'=>true,'IMG'=>'validate','TITLE'=>$_title,'URL'=>$action_url)));
	}
	if (($may_reply) && (is_null(get_bot_type())))
	{
		$map=array('page'=>'topics','type'=>'new_post','id'=>$_postdetails['topic_id'],'parent_id'=>$_postdetails['id']);
		if ($topic_info['is_threaded']==0)
		{
			$map['quote']=$_postdetails['id'];
		}
		if (array_key_exists('intended_solely_for',$_postdetails))
		{
			$map['intended_solely_for']=$_postdetails['poster'];
		}
		$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
		if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
		$test=get_param_integer('threaded',-1);
		if ($test!=-1) $map['threaded']=$test;
		$action_url=build_url($map,get_module_zone('topics'));
		$_title=do_lang_tempcode(($topic_info['is_threaded']==1)?'REPLY':'QUOTE_POST');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$javascript=NULL;

		if ((array_key_exists('message_comcode',$_postdetails)) && (!is_null($_postdetails['message_comcode'])) && (strlen($_postdetails['message_comcode'])<1024*10/*10kb limit, for reasonable performance*/) && ($topic_info['may_use_quick_reply']) && (!array_key_exists('intended_solely_for',$map)))
		{
			$javascript='return topic_reply('.($topic_info['is_threaded']?'true':'false').',this,\''.strval($_postdetails['id']).'\',\''.addslashes($_postdetails['poster_username']).'\',\''.str_replace(chr(10),'\n',addslashes($_postdetails['message_comcode'])).'\',\''.str_replace(chr(10),'\n',addslashes(($topic_info['is_threaded']==0)?'':strip_comcode($_postdetails['message_comcode']))).'\');';
		}
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'fc13d12cfe58324d78befec29a663b4f','REL'=>'add reply','IMMEDIATE'=>false,'IMG'=>($topic_info['is_threaded']==1)?'reply':'quote','TITLE'=>$_title,'URL'=>$action_url,'JAVASCRIPT'=>$javascript)));
	}
	if ((addon_installed('points')) && (!is_guest()) && (!is_guest($_postdetails['poster'])) && (has_specific_permission($_postdetails['poster'],'use_points')))
	{
		$action_url=build_url(array('page'=>'points','type'=>'member','id'=>$_postdetails['poster']),get_module_zone('points'));
		$_title=do_lang_tempcode('POINTS_THANKS');
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'a66f98cb4d56bd0d64e9ecc44d357141','IMMEDIATE'=>false,'IMG'=>'points','TITLE'=>$_title,'URL'=>$action_url)));
	}
	if ((array_key_exists('may_pt_members',$topic_info)) && ($may_reply) && ($_postdetails['poster']!=get_member()) && ($_postdetails['poster']!=$GLOBALS['OCF_DRIVER']->get_guest_id()) && (ocf_may_whisper($_postdetails['poster'])) && (get_option('overt_whisper_suggestion')=='1'))
	{
		$whisper_type=(get_value('no_inline_pp_advertise')==='1')?'new_pt':'whisper';
		$action_url=build_url(array('page'=>'topics','type'=>$whisper_type,'id'=>$_postdetails['topic_id'],'quote'=>$_postdetails['id'],'intended_solely_for'=>$_postdetails['poster']),get_module_zone('topics'));
		$_title=do_lang_tempcode('WHISPER');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'fb1c74bae9c553dc160ade85adf289b5','REL'=>'add reply contact','IMMEDIATE'=>false,'IMG'=>(get_value('no_inline_pp_advertise')==='1')?'send_message':'whisper','TITLE'=>$_title,'URL'=>$action_url)));
	}
	if ((array_key_exists('may_report_posts',$topic_info)) && (addon_installed('ocf_reported_posts')) && (is_null(get_bot_type())))
	{
		$action_url=build_url(array('page'=>'topics','type'=>'report_post','id'=>$_postdetails['id']),get_module_zone('topics'));
		$_title=do_lang_tempcode('REPORT_POST');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'f81cbe84f524b4ed9e089c6e89a7c717','REL'=>'report','IMMEDIATE'=>false,'IMG'=>'report_post','TITLE'=>$_title,'URL'=>$action_url,'JAVASCRIPT'=>'return open_link_as_overlay(this,null,\'100%\');')));
	}
	if (array_key_exists('may_edit',$_postdetails))
	{
		$map=array('page'=>'topics','type'=>'edit_post','id'=>$_postdetails['id']);
		$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
		if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
		$test=get_param_integer('threaded',-1);
		if ($test!=-1) $map['threaded']=$test;
		$edit_url=build_url($map,get_module_zone('topics'));
		$_title=do_lang_tempcode('EDIT_POST');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'f341cfc94b3d705437d43e89f572bff6','REL'=>'edit','IMMEDIATE'=>false,'IMG'=>'edit','TITLE'=>$_title,'URL'=>$edit_url)));
	}
	if (array_key_exists('may_delete',$_postdetails))
	{
		$map=array('page'=>'topics','type'=>'delete_post','id'=>$_postdetails['id']);
		$test=get_param_integer('kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id'])),-1);
		if (($test!=-1) && ($test!=0)) $map['kfs'.(is_null($topic_info['forum_id'])?'':strval($topic_info['forum_id']))]=$test;
		$test=get_param_integer('threaded',-1);
		if ($test!=-1) $map['threaded']=$test;
		$delete_url=build_url($map,get_module_zone('topics'));
		$_title=do_lang_tempcode('DELETE_POST');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'8bf6d098ddc217eef75718464dc03d41','REL'=>'delete','IMMEDIATE'=>false,'IMG'=>'delete','TITLE'=>$_title,'URL'=>$delete_url)));
	}
	if ((array_key_exists('may_warn_members',$topic_info)) && ($_postdetails['poster']!=$GLOBALS['OCF_DRIVER']->get_guest_id()) && (addon_installed('ocf_warnings')))
	{
		$redir_url=get_self_url(true);
		$redir_url.='#post_'.strval($_postdetails['id']);
		$action_url=build_url(array('page'=>'warnings','type'=>'ad','id'=>$_postdetails['poster'],'post_id'=>$_postdetails['id'],'redirect'=>$redir_url),get_module_zone('warnings'));
		$_title=do_lang_tempcode('WARN_MEMBER');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'2698c51b06a72773ac7135bbfe791318','IMMEDIATE'=>false,'IMG'=>'punish','TITLE'=>$_title,'URL'=>$action_url)));
	}
	if ((has_specific_permission(get_member(),'view_content_history')) && ($_postdetails['has_history']))
	{
		$action_url=build_url(array('page'=>'admin_ocf_history','type'=>'misc','post_id'=>$_postdetails['id']),'adminzone');
		$_title=do_lang_tempcode('POST_HISTORY');
		$_title->attach(do_lang_tempcode('ID_NUM',strval($_postdetails['id'])));
		$buttons->attach(do_template('SCREEN_ITEM_BUTTON',array('_GUID'=>'a66f98cb4d56bd0d64e9ecc44d357141','REL'=>'history','IMMEDIATE'=>false,'IMG'=>'history','TITLE'=>$_title,'URL'=>$action_url)));
	}
	return $buttons;
}

/**
 * Get post emphasis Tempcode.
 *
 * @param  array			Map of post info.
 * @return tempcode		The tempcode.
 */
function ocf_get_post_emphasis($_postdetails)
{
	$emphasis=new ocp_tempcode();
	if ($_postdetails['is_emphasised'])
	{
		$emphasis=do_lang_tempcode('IMPORTANT');
	}
	elseif (array_key_exists('intended_solely_for',$_postdetails))
	{
		$pp_to_username=$GLOBALS['FORUM_DRIVER']->get_username($_postdetails['intended_solely_for']);
		if (is_null($pp_to_username)) $pp_to_username=do_lang('UNKNOWN');
		$emphasis=do_lang('PP_TO',$pp_to_username);
	}
	return $emphasis;
}
