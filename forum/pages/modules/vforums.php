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
 * @package		ocf_forum
 */

/**
 * Module page class.
 */
class Module_vforums
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return is_guest()?array():array('misc'=>'POSTS_SINCE','unread'=>'TOPICS_UNREAD','recently_read'=>'RECENTLY_READ','unanswered_topics'=>'UNANSWERED_TOPICS','involved_topics'=>'INVOLVED_TOPICS');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_forumview');

		require_css('ocf');

		$type=get_param('type','misc');
		if ($type=='misc') list($title,$content)=$this->new_posts();
		elseif ($type=='unread') list($title,$content)=$this->unread_topics();
		elseif ($type=='recently_read') list($title,$content)=$this->recently_read();
		elseif ($type=='unanswered_topics') list($title,$content)=$this->unanswered_topics();
		elseif ($type=='involved_topics') list($title,$content)=$this->involved_topics();
		else
		{
			$title=new ocp_tempcode();
			$content=new ocp_tempcode();
		}

		return do_template('OCF_VFORUM_SCREEN',array('_GUID'=>'8dca548982d65500ab1800ceec2ddc61','TITLE'=>$title,'CONTENT'=>$content));
	}

	/**
	 * The UI to show topics with new posts since last visit time.
	 *
	 * @return array			A pair: The Title, The UI
	 */
	function new_posts()
	{
		$title=do_lang_tempcode('POSTS_SINCE');

		$seconds_back=get_param_integer('seconds_back',NULL);
		if (is_null($seconds_back))
		{
			if (array_key_exists('last_visit',$_COOKIE))
			{
				$last_time=intval($_COOKIE['last_visit']);
			} else
			{
				$last_time=time()-60*60*24*7;
				if (!$GLOBALS['DEV_MODE'])
					attach_message(do_lang_tempcode('NO_LAST_VISIT'),'notice');
			}
		} else
		{
			$last_time=time()-$seconds_back;
		}

		$condition='t_cache_last_time>'.strval($last_time);

		$order2='t_cache_last_time DESC';

		if (get_value('disable_sunk')!=='1')
			$order2='t_sunk ASC,'.$order2;

		$extra_tpl_map=array('FILTERING'=>do_template('OCF_VFORUM_FILTERING',array()));

		return array(get_screen_title('POSTS_SINCE'),$this->_vforum($title,$condition,'t_cascading DESC,t_pinned DESC,'.$order2,true,$extra_tpl_map));
	}

	/**
	 * The UI to show unanswered topics.
	 *
	 * @return array			A pair: The Title, The UI
	 */
	function unanswered_topics()
	{
		$title=do_lang_tempcode('UNANSWERED_TOPICS');
		$condition=array('(SELECT COUNT(DISTINCT p_poster) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts p WHERE p.p_topic_id=top.id)=1');

		return array(get_screen_title('UNANSWERED_TOPICS'),$this->_vforum($title,$condition,'t_cache_last_time DESC',true));
	}

	/**
	 * The UI to show topics you're involved with.
	 *
	 * @return array			A pair: The Title, The UI
	 */
	function involved_topics()
	{
		if (is_guest()) access_denied('NOT_AS_GUEST');

		$title=do_lang_tempcode('INVOLVED_TOPICS');
		$condition=array('p_poster='.strval(get_member()));

		return array(get_screen_title('INVOLVED_TOPICS'),$this->_vforum($title,$condition,'t_cache_last_time DESC',true,NULL,$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts pos LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics top ON top.id=pos.p_topic_id'));
	}

	/**
	 * The UI to show topics with unread posts.
	 *
	 * @return array			A pair: The Title, The UI
	 */
	function unread_topics()
	{
		if (is_guest()) access_denied('NOT_AS_GUEST');

		$title=do_lang_tempcode('TOPICS_UNREAD');
		$condition=array('l_time IS NOT NULL AND l_time<t_cache_last_time','l_time IS NULL AND t_cache_last_time>'.strval(time()-60*60*24*intval(get_option('post_history_days'))));

		return array(get_screen_title('TOPICS_UNREAD'),$this->_vforum($title,$condition,'t_cache_last_time DESC',true));
	}

	/**
	 * The UI to show topics which have been recently read by the current member.
	 *
	 * @return array			A pair: The Title, The UI
	 */
	function recently_read()
	{
		if (is_guest()) access_denied('NOT_AS_GUEST');

		$title=do_lang_tempcode('RECENTLY_READ');
		$condition='l_time>'.strval(time()-60*60*24*2);

		return array(get_screen_title('RECENTLY_READ'),$this->_vforum($title,$condition,'l_time DESC',true));
	}

	/**
	 * The UI to show a virtual forum.
	 *
	 * @param  tempcode		The title to show for the v-forum
	 * @param  mixed			The condition (a fragment of an SQL query that gets embedded in the context of a topic selection query). May be string, or array of strings (separate queries to run and merge; done for performance reasons relating to DB indexing)
	 * @param  string			The ordering of the results
	 * @param  boolean		Whether to not show pinning in a separate section
	 * @param  ?array			Extra template parameters to pass through (NULL: none)
	 * @param  ?string		The table to query (NULL: topic table)
	 * @return tempcode		The UI
	 */
	function _vforum($title,$condition,$order,$no_pin=false,$extra_tpl_map=NULL,$initial_table=NULL)
	{
		$breadcrumbs=ocf_forum_breadcrumbs(db_get_first_id(),$title,get_param_integer('keep_forum_root',db_get_first_id()));
		breadcrumb_add_segment($breadcrumbs);

		$max=get_param_integer('forum_max',intval(get_option('forum_topics_per_page')));
		$start=get_param_integer('forum_start',0);
		$type=get_param('type','misc');
		$forum_name=do_lang_tempcode('VIRTUAL_FORUM');

		// Find topics
		$extra='';
		if ((!has_privilege(get_member(),'see_unvalidated')) && (addon_installed('unvalidated'))) $extra='t_validated=1';
		if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()))
		{
			$groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(),false,true);
			$group_or_list='';
			foreach ($groups as $group)
			{
				if ($group_or_list!='') $group_or_list.=' OR ';
				$group_or_list.='group_id='.strval($group);
			}

			if ($extra!='') $extra.=' AND ';
			$or_list='';
			global $SITE_INFO;
			if (is_guest())
			{
				$forum_access=$GLOBALS['FORUM_DB']->query('SELECT category_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'group_category_access WHERE ('.$group_or_list.') AND '.db_string_equal_to('module_the_name','forums'),NULL,NULL,false,true);
			} else
			{
				$forum_access=$GLOBALS['FORUM_DB']->query('SELECT category_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'group_category_access WHERE ('.$group_or_list.') AND '.db_string_equal_to('module_the_name','forums').' UNION ALL SELECT category_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'member_category_access WHERE (member_id='.strval(get_member()).' AND active_until>'.strval(time()).') AND '.db_string_equal_to('module_the_name','forums'),NULL,NULL,false,true);
			}
			foreach ($forum_access as $access)
			{
				if ($or_list!='') $or_list.=' OR ';
				$or_list.='t_forum_id='.strval($access['category_name']);
			}
			$extra.='('.$or_list.')';
		}
		if ($extra!='') $extra=' AND ('.$extra.') ';
		$max_rows=0;
		$topic_rows=array();
		foreach (is_array($condition)?$condition:array($condition) as $_condition)
		{
			$query=' FROM ';
			if (!is_null($initial_table))
			{
				$query.=$initial_table;
			} else
			{
				$query.=$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics top';
			}
			if (!is_guest())
				$query.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_read_logs l ON (top.id=l.l_topic_id AND l.l_member_id='.strval(get_member()).')';
			$query.=' LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND top.t_cache_first_post=t.id WHERE (('.$_condition.')'.$extra.') AND t_forum_id IS NOT NULL';
			if (can_arbitrary_groupby()) $query.=' GROUP BY top.id';
			$query.=' ORDER BY '.$order;
			$topic_rows=array_merge($topic_rows,$GLOBALS['FORUM_DB']->query('SELECT top.*,t.text_parsed AS _trans_post,'.(is_guest()?'NULL as l_time':'l_time').$query,$max,$start));
			//if (($start==0) && (count($topic_rows)<$max)) $max_rows+=$max; // We know that they're all on this screen
			/*else */$max_rows+=$GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) '.$query);
		}
		$hot_topic_definition=intval(get_option('hot_topic_definition'));
		$or_list='';
		foreach ($topic_rows as $topic_row)
		{
			if ($or_list!='') $or_list.=' OR ';
			$or_list.='p_topic_id='.strval($topic_row['id']);
		}
		$involved=array();
		if (($or_list!='') && (!is_guest()))
		{
			$involved=$GLOBALS['FORUM_DB']->query('SELECT DISTINCT p_topic_id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE ('.$or_list.') AND p_poster='.strval(get_member()));
			$involved=collapse_1d_complexity('p_topic_id',$involved);
		}
		$topics_array=array();
		foreach ($topic_rows as $topic_row)
		{
			$topics_array[]=ocf_get_topic_array($topic_row,get_member(),$hot_topic_definition,in_array($topic_row['id'],$involved));
		}

		// Display topics
		$topics=new ocp_tempcode();
		$pinned=false;
		require_code('templates_pagination');
		$topic_wrapper=new ocp_tempcode();
		$forum_name_map=collapse_2d_complexity('id','f_name',$GLOBALS['FORUM_DB']->query('SELECT id,f_name FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_forums WHERE f_cache_num_posts>0'));
		foreach ($topics_array as $topic)
		{
			if ((!$no_pin) && ($pinned) && (!in_array('pinned',$topic['modifiers'])))
			{
				$topics->attach(do_template('OCF_PINNED_DIVIDER'));
			}
			$pinned=in_array('pinned',$topic['modifiers']);
			$forum_id=array_key_exists('forum_id',$topic)?$topic['forum_id']:NULL;
			$_forum_name=array_key_exists($forum_id,$forum_name_map)?$forum_name_map[$forum_id]:do_lang_tempcode('PRIVATE_TOPICS');
			$topics->attach(ocf_render_topic($topic,true,false,$_forum_name));
		}
		if (!$topics->is_empty())
		{
			$pagination=pagination(do_lang_tempcode('FORUM_TOPICS'),$start,'forum_start',$max,'forum_max',$max_rows);

			$moderator_actions='';
			$moderator_actions.='<option value="mark_topics_read">'.do_lang('MARK_READ').'</option>';
			if ($title->evaluate()!=do_lang('TOPICS_UNREAD'))
				$moderator_actions.='<option value="mark_topics_unread">'.do_lang('MARK_UNREAD').'</option>';
			if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($moderator_actions);

			$action_url=build_url(array('page'=>'topics','redirect'=>get_self_url(true)),get_module_zone('topics'));
			$topic_wrapper=do_template('OCF_FORUM_TOPIC_WRAPPER',array(
				'_GUID'=>'67356b4daacbed3e3d960d89a57d0a4a',
				'MAX'=>strval($max),
				'ORDER'=>'',
				'MAY_CHANGE_MAX'=>false,
				'BREADCRUMBS'=>$breadcrumbs,
				'BUTTONS'=>'',
				'STARTER_TITLE'=>'',
				'PAGINATION'=>$pagination,
				'MODERATOR_ACTIONS'=>$moderator_actions,
				'ACTION_URL'=>$action_url,
				'TOPICS'=>$topics,
				'FORUM_NAME'=>$forum_name,
			));
		}

		$_buttons=new ocp_tempcode();
		$archive_url=$GLOBALS['FORUM_DRIVER']->forum_url(db_get_first_id());
		$_buttons->attach(do_template('SCREEN_BUTTON',array('_GUID'=>'8c928f1f703e9ba232a7033adee19a31','TITLE'=>do_lang_tempcode('ROOT_FORUM'),'IMG'=>'all','IMMEDIATE'=>false,'URL'=>$archive_url)));

		breadcrumb_add_segment($breadcrumbs);
		$tpl_map=array(
			'_GUID'=>'d3fa84575727af935eadb2ce2b7c7b3e',
			'FILTERS'=>'',
			'FORUM_NAME'=>$forum_name,
			'STARTER_TITLE'=>'',
			'BUTTONS'=>$_buttons,
			'TOPIC_WRAPPER'=>$topic_wrapper,
			'CATEGORIES'=>'',
		);
		if (!is_null($extra_tpl_map)) $tpl_map+=$extra_tpl_map;
		return do_template('OCF_FORUM',$tpl_map);
	}

}


