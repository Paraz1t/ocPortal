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
 * @package		import
 */

/**
 * Standard code module initialisation function.
 */
function init__hooks__modules__admin_import__phpbb2()
{
	global $TOPIC_FORUM_CACHE;
	$TOPIC_FORUM_CACHE=array();

	global $STRICT_FILE;
	$STRICT_FILE=false; // Disable this for a quicker import that is quite liable to go wrong if you don't have the files in the right place

	global $OLD_BASE_URL;
	$OLD_BASE_URL=NULL;
}

/**
 * Forum Driver.
 */
class Hook_phpbb2
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['supports_advanced_import']=false;
		$info['product']='phpBB 2.0.x';
		$info['prefix']='phpbb_';
		$info['import']=array(
								'ocf_groups',
								'ocf_members',
								'ocf_member_files',
								'ocf_forum_groupings',
								'ocf_forums',
								'ocf_topics',
								'ocf_posts',
								'ocf_polls_and_votes',
								//'notifications',
								'ocf_private_topics',
								'ocf_post_files',
								'wordfilter',
								'config',
								'ip_bans',
							);
		$info['dependencies']=array( // This dependency tree is overdefined, but I wanted to make it clear what depends on what, rather than having a simplified version
								'ocf_members'=>array('ocf_groups'),
								'ocf_member_files'=>array('ocf_members'),
								'ocf_forums'=>array('ocf_forum_groupings','ocf_members','ocf_groups'),
								'ocf_topics'=>array('ocf_forums','ocf_members'),
								'ocf_polls_and_votes'=>array('ocf_topics','ocf_members'),
								'ocf_posts'=>array('ocf_topics','ocf_members'),
								'ocf_post_files'=>array('ocf_posts','ocf_private_topics'),
								'notifications'=>array('ocf_topics','ocf_members'),
								'ocf_private_topics'=>array('ocf_members'),
							);
		$_cleanup_url=build_url(array('page'=>'admin_cleanup'),get_module_zone('admin_cleanup'));
		$cleanup_url=$_cleanup_url->evaluate();
		$info['message']=(get_param('type','misc')!='import' && get_param('type','misc')!='hook')?new ocp_tempcode():do_lang_tempcode('FORUM_CACHE_CLEAR',escape_html($cleanup_url));

		return $info;
	}

	/**
	 * Probe a file path for DB access details.
	 *
	 * @param  string			The probe path
	 * @return array			A quartet of the details (db_name, db_user, db_pass, table_prefix)
	 */
	function probe_db_access($file_base)
	{
		$dbname='';
		$dbuser='';
		$dbpasswd='';
		$table_prefix='';
		if (!file_exists($file_base.'/config.php'))
			warn_exit(do_lang_tempcode('BAD_IMPORT_PATH',escape_html('config.php')));
		require($file_base.'/config.php');
		$PROBED_FORUM_CONFIG=array();
		$PROBED_FORUM_CONFIG['sql_database']=$dbname;
		$PROBED_FORUM_CONFIG['sql_user']=$dbuser;
		$PROBED_FORUM_CONFIG['sql_pass']=$dbpasswd;
		$PROBED_FORUM_CONFIG['sql_tbl_prefix']=$table_prefix;

		return array($PROBED_FORUM_CONFIG['sql_database'],$PROBED_FORUM_CONFIG['sql_user'],$PROBED_FORUM_CONFIG['sql_pass'],$PROBED_FORUM_CONFIG['sql_tbl_prefix']);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_config($db,$table_prefix,$file_base)
	{
		$config_remapping=array(
			'require_activation'=>'require_new_member_validation',
			'board_disable'=>'site_closed',
			'sitename'=>'site_name',
			'site_desc'=>'site_scope',
			'posts_per_page'=>'forum_posts_per_page',
			'topics_per_page'=>'forum_topics_per_page',
			'board_email'=>'staff_address',
			'gzip_compress'=>'gzip_output',
			'smtp_delivery'=>'smtp_sockets_use',
			'smtp_host'=>'smtp_sockets_host',
			'smtp_username'=>'smtp_sockets_username',
			'smtp_password'=>'smtp_sockets_password',
		);

		$rows=$db->query('SELECT * FROM '.$table_prefix.'config');
		$PROBED_FORUM_CONFIG=array();
		foreach ($rows as $row)
		{
			if ($row['config_name']=='require_activation')
			{
				if ($row['config_value']=='2') $row['config_value']='1'; else $row['config_value']='0';
			}

			if (array_key_exists($row['config_name'],$config_remapping))
			{
				$value=$row['config_value'];
				$remapping=$config_remapping[$row['config_name']];
				if ($remapping[0]=='!')
				{
					$remapping=substr($remapping,1);
					$value=1-$value;
				}
				set_option($remapping,$value);
			}
			$PROBED_FORUM_CONFIG[$row['config_name']]=$row['config_value'];
		}

		set_value('timezone',$PROBED_FORUM_CONFIG['board_timezone']);

		// Now some usergroup options
		$groups=$GLOBALS['OCF_DRIVER']->get_usergroup_list();
		$super_admin_groups=$GLOBALS['OCF_DRIVER']->_get_super_admin_groups();
		foreach (array_keys($groups) as $id)
		{
			if (in_array($id,$super_admin_groups)) continue;

			$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_max_avatar_width'=>$PROBED_FORUM_CONFIG['avatar_max_width'],'g_max_avatar_height'=>$PROBED_FORUM_CONFIG['avatar_max_height'],'g_max_sig_length_comcode'=>$PROBED_FORUM_CONFIG['max_sig_chars']),array('id'=>$id),'',1);
			set_privilege($id,'comcode_dangerous',$PROBED_FORUM_CONFIG['allow_html']);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_groups($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'config');
		$PROBED_FORUM_CONFIG=array();
		foreach ($rows as $row)
		{
			$key=$row['config_name'];
			$val=$row['config_value'];
			$PROBED_FORUM_CONFIG[$key]=$val;
		}

		$rows=$db->query('SELECT * FROM '.$table_prefix.'groups WHERE group_single_user=0 ORDER BY group_id');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('group',strval($row['group_id']))) continue;

			$row_group_leader=NULL;
			if ($row['group_moderator']!=0) $row_group_leader=-$row['group_moderator']; // This will be fixed when we import members

			$is_super_admin=0;
			$is_super_moderator=0;

			$id_new=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$row['group_name']),'g.id');
			if (is_null($id_new))
			{
				$id_new=ocf_make_group($row['group_name'],0,$is_super_admin,$is_super_moderator,'','',NULL,NULL,$row_group_leader,5,0,5,5,$PROBED_FORUM_CONFIG['avatar_max_width'],$PROBED_FORUM_CONFIG['avatar_max_height'],30000,$PROBED_FORUM_CONFIG['max_sig_chars']);
			}

			// privileges
			set_privilege($id_new,'comcode_dangerous',$PROBED_FORUM_CONFIG['allow_html']);

			import_id_remap_put('group',strval($row['group_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_members($db,$table_prefix,$file_base)
	{
		$default_group=get_first_default_group();

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'users u LEFT JOIN '.$table_prefix.'banlist b ON u.user_id=b.ban_userid WHERE u.user_id<>-1 ORDER BY u.user_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member',strval($row['user_id']))) continue;

				$test=$GLOBALS['OCF_DRIVER']->get_member_from_username($row['username']);
				if (!is_null($test))
				{
					import_id_remap_put('member',strval($row['user_id']),$test);
					continue;
				}

				$language='';
				if ($row['user_lang']!='')
				{
					switch ($language) // Can be extended as needed
					{
						case 'english':
							$language='EN';
							break;
					}
				}

				$primary_group=$default_group;
				$rows2=$db->query('SELECT * FROM '.$table_prefix.'user_group WHERE user_id='.strval((integer)$row['user_id']),200,$row_start);
				$secondary_groups=array();
				foreach ($rows2 as $row2)
				{
					$g=import_id_remap_get('group',strval($row2['group_id']),true);
					if (!is_null($g)) $secondary_groups[]=array($g,$row2['user_pending']);
				}
				if ($row['user_level']==1) $secondary_groups[]=array(db_get_first_id()+1,0);

				$custom_fields=array(
										ocf_make_boiler_custom_field('im_icq')=>$row['user_icq'],
										ocf_make_boiler_custom_field('im_aim')=>$row['user_aim'],
										ocf_make_boiler_custom_field('im_msn')=>$row['user_msnm'],
										ocf_make_boiler_custom_field('im_yahoo')=>$row['user_yim'],
										ocf_make_boiler_custom_field('interests')=>$row['user_interests'],
										ocf_make_boiler_custom_field('location')=>$row['user_from'],
										ocf_make_boiler_custom_field('occupation')=>$row['user_occ'],
									);
				if ($row['user_website']!='')
					$custom_fields[ocf_make_boiler_custom_field('website')]=(strlen($row['user_website'])>0)?('[url]'.$row['user_website'].'[/url]'):'';

				$signature=$this->fix_links($row['user_sig'],$db,$table_prefix);
				$validated=$row['user_active'];
				$reveal_age=0;
				list($bday_day,$bday_month,$bday_year)=array(NULL,NULL,NULL);
				$views_signatures=1;
				$preview_posts=1;
				$track_posts=$row['user_notify'];
				$title='';

				// These are done in the members-files stage
				$avatar_url='';
				$photo_url='';
				$photo_thumb_url='';

				$password=$row['user_password'];
				$type='md5';
				$salt='';

				$id_new=ocf_make_member($row['username'],$password,$row['user_email'],NULL,$bday_day,$bday_month,$bday_year,$custom_fields,strval($row['user_timezone']),$primary_group,$validated,$row['user_regdate'],$row['user_lastvisit'],'',$avatar_url,$signature,(!is_null($row['ban_id']))?1:0,$preview_posts,$reveal_age,$title,$photo_url,$photo_thumb_url,$views_signatures,$track_posts,$language,$row['user_allow_pm'],1,'','',false,$type,$salt,1);

				// Fix usergroup leadership
				$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_group_leader'=>$id_new),array('g_group_leader'=>-$row['user_id']));

				import_id_remap_put('member',strval($row['user_id']),$id_new);

				// Set up usergroup membership
				foreach ($secondary_groups as $s)
				{
					list($group,$userpending)=$s;
					ocf_add_member_to_group($id_new,$group,1-$userpending);
				}
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_member_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$options=$db->query('SELECT * FROM '.$table_prefix.'config WHERE '.db_string_equal_to('config_name','avatar_path').' OR '.db_string_equal_to('config_name','avatar_gallery_path'));
		$avatar_path=$options[0]['config_value'];
		$avatar_gallery_path=$options[1]['config_value'];

		$row_start=0;
		$rows=array();
		do
		{
			$query='SELECT user_id,user_avatar,user_avatar_type FROM '.$table_prefix.'users WHERE user_id<>-1 ORDER BY user_id';
			$rows=$db->query($query,200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member_files',strval($row['user_id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['user_id']));

				$avatar_url='';
				switch ($row['user_avatar_type'])
				{
					case 0:
						break;
					case 1: // Upload
						$filename=$row['user_avatar'];
						if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($file_base.'/'.$avatar_path.'/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
						{
							$avatar_url='uploads/ocf_avatars/'.$filename;
							sync_file($avatar_url);
						} else
						{
							if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',escape_html($filename)));
							$avatar_url='';
						}
						break;
					case 2: // Remote
						$avatar_url=$row['user_avatar'];
						break;
					case 3: // Gallery
						$filename=$row['user_avatar'];
						if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($file_base.'/'.$avatar_gallery_path.'/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
						{
							$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
							sync_file($avatar_url);
						} else
						{
							// Try as a pack avatar then
							$striped_filename=str_replace('/','_',$filename);
							if (file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$striped_filename))
							{
								$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
							} else
							{
								if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',escape_html($filename)));
								$avatar_url='';
							}
						}
						break;
				}

				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_avatar_url'=>$avatar_url),array('id'=>$member_id),'',1);

				import_id_remap_put('member_files',strval($row['user_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ip_bans($db,$table_prefix,$file_base)
	{
		require_code('failure');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'banlist WHERE '.db_string_not_equal_to('ban_ip',''));
		foreach ($rows as $row)
		{
			if (import_check_if_imported('ip_ban',strval($row['ban_id']))) continue;

			add_ip_ban($this->_un_phpbb_ip($row['ban_ip']));

			import_id_remap_put('ip_ban',strval($row['ban_id']),0);
		}
	}

	/**
	 * Convert an IP address from phpBB hexadecimal string format.
	 *
	 * @param  string			The phpBB IP address
	 * @return IP				The normal IP address
	 */
	function _un_phpbb_ip($ip)
	{
		if (strlen($ip)<8) return '127.0.0.1';

		$_ip=strval(hexdec($ip[0].$ip[1])).'.'.strval(hexdec($ip[2].$ip[3])).'.'.strval(hexdec($ip[4].$ip[5])).'.'.strval(hexdec($ip[6].$ip[7]));
		return $_ip;
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_forum_groupings($db,$table_prefix,$old_base_dir)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'categories');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('category',strval($row['cat_id']))) continue;

			$title=$row['cat_title'];

			$test=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings','id',array('c_title'=>$title));
			if (!is_null($test))
			{
				import_id_remap_put('category',strval($row['cat_id']),$test);
				continue;
			}

			$id_new=ocf_make_forum_grouping($title,'',1);

			import_id_remap_put('category',strval($row['cat_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_forums($db,$table_prefix,$old_base_dir)
	{
		require_code('ocf_forums_action2');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'forums');
		foreach ($rows as $row)
		{
			$remapped=import_id_remap_get('forum',strval($row['forum_id']),true);
			if (!is_null($remapped))
			{
				continue;
			}

			$name=$row['forum_name'];
			ocf_over_msn();
			$description=html_to_comcode($row['forum_desc']);
			ocf_over_local();
			$position=$row['forum_order'];
			$post_count_increment=1;

			$category_id=import_id_remap_get('category',strval($row['cat_id']),true);
			$parent_forum=db_get_first_id();

			$access_mapping=array();
			if ($row['forum_status']==0)
			{
				$permissions=$db->query('SELECT * FROM '.$table_prefix.'auth_access WHERE forum_id='.strval((integer)$row['forum_id']));
//				$row['group_id']=-1;
//				$permissions[]=$row;
				foreach ($permissions as $p)
				{
					$v=0;
					if ($p['auth_read']==1) $v=1;
					if ($p['auth_post']==1) $v=2;
					if ($p['auth_reply']==1) $v=3;
					if ($p['auth_pollcreate']==1) $v=4; // This ones a bit hackerish, but closest we can get to concept
					if ((array_key_exists('auth_mod',$p)) && ($p['auth_mod']==1)) $v=5;

					//NOTE that if the group is not imported, this means that this group is a single user group
					if (!import_check_if_imported('group',strval($p['group_id']))) continue;

					$group_id=import_id_remap_get('group',strval($p['group_id']));

					$access_mapping[$group_id]=$v;
				}
			}

			$id_new=ocf_make_forum($name,$description,$category_id,$access_mapping,$parent_forum,$position,$post_count_increment,0,'');

			import_id_remap_put('forum',strval($row['forum_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_topics($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'topics WHERE topic_moved_id=0 ORDER BY topic_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic',strval($row['topic_id']))) continue;

				$forum_id=import_id_remap_get('forum',strval($row['forum_id']));

				$id_new=ocf_make_topic($forum_id,$row['topic_title'],'',1,($row['topic_status']==1)?0:1,($row['topic_type']>0)?1:0,($row['topic_type']>2)?1:0,0,NULL,NULL,false,$row['topic_views']);

				import_id_remap_put('topic',strval($row['topic_id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_posts($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'posts p LEFT JOIN '.$table_prefix.'posts_text t ON p.post_id=t.post_id ORDER BY p.post_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post',strval($row['post_id']))) continue;

				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id))
				{
					import_id_remap_put('post',strval($row['post_id']),-1);
					continue;
				}
				$member_id=import_id_remap_get('member',strval($row['poster_id']),true);
				if (is_null($member_id)) $member_id=db_get_first_id();

				$forum_id=import_id_remap_get('forum',strval($row['forum_id']),true);

				$title='';
				$topics=$db->query('SELECT topic_title,topic_time FROM '.$table_prefix.'topics WHERE topic_id='.strval((integer)$row['topic_id']));
				$first_post=$topics[0]['topic_time']==$row['post_time'];
				if ($first_post)
				{
					$title=$topics[0]['topic_title'];
				}
				elseif (!is_null($row['post_subject'])) $title=$row['post_subject'];

				$post=$this->fix_links($row['post_text'],$db,$table_prefix);

				$last_edit_by=NULL;
				$last_edit_time=$row['post_edit_time'];

				if ($row['post_username']=='') $row['post_username']=$GLOBALS['OCF_DRIVER']->get_username($member_id);

				$id_new=ocf_make_post($topic_id,$title,$post,0,$first_post,1,0,$row['post_username'],$this->_un_phpbb_ip($row['poster_ip']),$row['post_time'],$member_id,NULL,$last_edit_time,$last_edit_by,false,false,$forum_id,false);

				import_id_remap_put('post',strval($row['post_id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_topic($m)
	{
		return 'index.php?page=topicview&id='.strval(import_id_remap_get('topic',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_forum($m)
	{
		return 'index.php?page=forumview&id='.strval(import_id_remap_get('forum',strval($m[2]),true));
	}

	/**
	 * Substitution callback for 'fix_links'.
	 *
	 * @param  array				The match
	 * @return  string			The substitution string
	 */
	function _fix_links_callback_member($m)
	{
		return 'index.php?page=members&type=view&id='.strval(import_id_remap_get('member',strval($m[2]),true));
	}

	/**
	 * Convert phpBB URLs pasted in text fields into ocPortal ones.
	 *
	 * @param  string			The text field text (e.g. a post)
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @return string			The new text field text
	 */
	function fix_links($post,$db,$table_prefix)
	{
		global $OLD_BASE_URL;
		if (is_null($OLD_BASE_URL))
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'config WHERE '.db_string_equal_to('config_name','server_name').' OR '.db_string_equal_to('config_name','server_port').' OR '.db_string_equal_to('config_name','script_path').' ORDER BY config_name');
			$server_path=$rows[0]['config_value'];
			$server_name=$rows[1]['config_value'];
			$server_port=$rows[2]['config_value'];
			$OLD_BASE_URL=($server_port=='80')?('http://'.$server_name.$server_path):('http://'.$server_name.':'.$server_port.$server_path);
		}
		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(viewtopic\.php\?t=)(\d*)#',array($this,'_fix_links_callback_topic'),$post);
		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(viewforum\.php\?f=)(\d*)#',array($this,'_fix_links_callback_forum'),$post);
		$post=preg_replace_callback('#'.preg_quote($OLD_BASE_URL).'/(profile\.php\?mode=viewprofile&u=)(\d*)#',array($this,'_fix_links_callback_member'),$post);
		$post=preg_replace('#:[0-9a-f]{10}#','',$post);
		$post=preg_replace('#\[size="?(\d+)"?\]#','[size="${1}pt"]',$post);
		return $post;
	}

	/**
	 * Standard import function. Note that this is designed for a very popular phpBB mod, and will exit silently if the mod hasn't been installed.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_post_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;
		require_code('attachments2');
		require_code('attachments3');

		$options=$db->query('SELECT * FROM '.$table_prefix.'attachments_config WHERE '.db_string_equal_to('config_name','upload_dir').' OR '.db_string_equal_to('config_name','max_attachments').' OR '.db_string_equal_to('config_name','use_gd2'),NULL,NULL,true);
		if (is_null($options)) return;
		$upload_dir=$options[0]['config_value'];

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'attachments a LEFT JOIN '.$table_prefix.'attachments_desc d ON a.attach_id=d.attach_id ORDER BY attach_id',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post_files',strval($row['attach_id']))) continue;

				if ($row['post_id']==0)
				{
					$post_id=import_id_remap_get('pt',strval($row['privmsgs_id']));
				} else
				{
					$post_id=import_id_remap_get('post',strval($row['post_id']));
				}

				$post_row=$GLOBALS['FORUM_DB']->query_select('f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON p.p_post=t.id',array('p_time','text_original','p_poster','p_post'),array('p.id'=>$post_id),'',1);
				if (!array_key_exists(0,$post_row))
				{
					import_id_remap_put('post_files',strval($row['attach_id']),1);
					continue; // Orphaned post
				}
				$post=$post_row[0]['text_original'];
				$lang_id=$post_row[0]['p_post'];
				$member_id=import_id_remap_get('member',strval($row['user_id_1']),true);
				if (is_null($member_id)) $member_id=$post_row[0]['p_poster'];

				$source_path=$file_base.'/'.$upload_dir.'/'.$row['physical_filename'];
				$new_filename=find_derivative_filename('attachments',$row['physical_filename']);
				$target_path=get_custom_file_base().'/uploads/attachments/'.$new_filename;
				if ((@rename($source_path,$target_path)))
				{
					sync_file($target_path);

					$url='uploads/attachments/'.urlencode($new_filename);
					$thumb_url='';

					$a_id=$GLOBALS['SITE_DB']->query_insert('attachments',array('a_member_id'=>$member_id,'a_file_size'=>$row['filesize'],'a_url'=>$url,'a_thumb_url'=>$thumb_url,'a_original_filename'=>$row['real_filename'],'a_num_downloads'=>$row['download_count'],'a_last_downloaded_time'=>NULL,'a_add_time'=>$row['filetime'],'a_description'=>''),true);

					$GLOBALS['SITE_DB']->query_insert('attachment_refs',array('r_referer_type'=>'ocf_post','r_referer_id'=>strval($post_id),'a_id'=>$a_id));
					$post.="\n\n".'[attachment="'.$row['comment'].'"]'.strval($a_id).'[/attachment]';

					ocf_over_msn();
					update_lang_comcode_attachments($lang_id,$post,'ocf_post',strval($post_id));
					ocf_over_local();
				}

				import_id_remap_put('post_files',strval($row['attach_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_polls_and_votes($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'vote_desc');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('poll',strval($row['vote_id']))) continue;

			$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
			if (is_null($topic_id))
			{
				import_id_remap_put('poll',strval($row['vote_id']),-1);
				continue;
			}

			$is_open=($row['vote_start']>time()) && (($row['vote_length']==0) || (($row['vote_start']+$row['vote_length'])<time()));

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'vote_results WHERE vote_id='.strval($row['vote_id'].' ORDER BY vote_option_id'));
			$answers=array();
			foreach ($rows2 as $answer)
			{
				$answers[]=$answer['vote_option_text'];
			}
			$maximum=1;

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'vote_voters WHERE vote_id='.$row['vote_id']);
			foreach ($rows2 as $row2)
			{
				$row2['vote_user_id']=import_id_remap_get('member',strval($row2['vote_user_id']),true);
			}

			$id_new=ocf_make_poll($topic_id,$row['vote_text'],0,$is_open?1:0,1,$maximum,0,$answers,false);

			$answers=collapse_1d_complexity('id',$GLOBALS['FORUM_DB']->query_select('f_poll_answers',array('id'),array('pa_poll_id'=>$id_new))); // Effectively, a remapping from IPB vote number to ocP vote number

			foreach ($rows2 as $row2)
			{
				$member_id=$row2['vote_user_id'];
				if ((!is_null($member_id)) && ($member_id!=0))
				{
					if (($row2['vote_cast']==0) || (!array_key_exists($row2['vote_cast']-1,$answers)))
					{
						$answer=-1;
					} else
					{
						$answer=$answers[$row2['vote_cast']-1];
					}
					$GLOBALS['FORUM_DB']->query_insert('f_poll_votes',array('pv_poll_id'=>$id_new,'pv_member_id'=>$member_id,'pv_answer_id'=>$answer));
				}
			}

			import_id_remap_put('poll',strval($row['vote_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_private_topics($db,$table_prefix,$old_base_dir)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'privmsgs p LEFT JOIN '.$table_prefix.'privmsgs_text t ON p.privmsgs_id=t.privmsgs_text_id WHERE privmsgs_type<>2 AND privmsgs_type<>4 ORDER BY privmsgs_date');

		// Group them up into what will become topics
		$groups=array();
		foreach ($rows as $row)
		{
			// Do some fiddling around for duplication
			if ($row['privmsgs_from_userid']>$row['privmsgs_to_userid'])
			{
				$a=$row['privmsgs_to_userid'];
				$b=$row['privmsgs_from_userid'];
			} else
			{
				$a=$row['privmsgs_from_userid'];
				$b=$row['privmsgs_to_userid'];
			}
			$row['privmsgs_subject']=str_replace('Re: ','',$row['privmsgs_subject']);
			$groups[strval($a).':'.strval($b).':'.$row['privmsgs_subject']][]=$row;
		}

		// Import topics
		foreach ($groups as $group)
		{
			$row=$group[0];

			if (import_check_if_imported('pt',strval($row['privmsgs_id']))) continue;

			// Create topic
			$from_id=import_id_remap_get('member',strval($row['privmsgs_from_userid']),true);
			if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$to_id=import_id_remap_get('member',strval($row['privmsgs_to_userid']),true);
			if (is_null($to_id)) $to_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
			$topic_id=ocf_make_topic(NULL,'','',1,1,0,0,0,$from_id,$to_id,false);

			$first_post=true;
			foreach ($group as $_postdetails)
			{
				if ($first_post)
				{
					$title=$row['privmsgs_subject'];
				} else $title='';

				$post=$this->fix_links($_postdetails['privmsgs_text'],$db,$table_prefix);
				$validated=1;
				$from_id=import_id_remap_get('member',strval($_postdetails['privmsgs_from_userid']),true);
				if (is_null($from_id)) $from_id=$GLOBALS['OCF_DRIVER']->get_guest_id();
				$poster_name_if_guest=$GLOBALS['OCF_DRIVER']->get_username($from_id);
				$ip_address=$_postdetails['privmsgs_ip'];
				$time=$_postdetails['privmsgs_date'];
				$poster=$from_id;
				$last_edit_time=NULL;
				$last_edit_by=NULL;

				ocf_make_post($topic_id,$title,$post,0,$first_post,$validated,0,$poster_name_if_guest,$ip_address,$time,$poster,NULL,$last_edit_time,$last_edit_by,false,false,NULL,false);
				$first_post=false;
			}

			import_id_remap_put('pt',strval($row['privmsgs_id']),$topic_id);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_notifications($db,$table_prefix,$file_base)
	{
		require_code('notifications');

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'topics_watch',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic_notification',strval($row['topic_id']).'-'.strval($row['user_id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['user_id']),true);
				if (is_null($member_id)) continue;
				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id)) continue;
				enable_notifications('ocf_topic',strval($topic_id),$member_id);

				import_id_remap_put('topic_notification',strval($row['topic_id']).'-'.strval($row['user_id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_wordfilter($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'words');
		foreach ($rows as $row)
		{
			add_wordfilter_word($row['word'],$row['replacement']);
		}
	}

}


