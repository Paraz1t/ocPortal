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
 * Module page class.
 */
class Module_admin_ocf_join
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
		return array('menu'=>'MEMBERS','misc'=>'ADD_MEMBER','delurk'=>'DELETE_LURKERS','download_csv'=>'DOWNLOAD_MEMBER_CSV','import_csv'=>'IMPORT_MEMBER_CSV','group_member_timeouts'=>'GROUP_MEMBER_TIMEOUTS');
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('ocf');

		set_helper_panel_pic('pagepics/addmember');
		set_helper_panel_tutorial('tut_adv_members');

		if ($type=='misc')
		{
			set_helper_panel_pic('pagepics/editmember');
			set_helper_panel_tutorial('tut_members');
		}

		if ($type=='group_member_timeouts' || $type=='_group_member_timeouts')
		{
			set_helper_panel_pic('pagepics/usergroups_temp');
		}

		if ($type=='delurk' || $type=='_delurk' || $type=='__delurk')
		{
			set_helper_panel_pic('pagepics/deletelurkers');
		}

		if ($type=='import_csv' || $type=='_import_csv')
		{
			set_helper_panel_pic('pagepics/import_csv');
		}

		if ($type=='step1')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS'))));
			breadcrumb_set_self(do_lang_tempcode('ADD_MEMBER'));
		}

		if ($type=='step2')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS')),array('_SELF:_SELF:misc',do_lang_tempcode('ADD_MEMBER'))));
			breadcrumb_set_self(do_lang_tempcode('DETAILS'));
		}

		if ($type=='group_member_timeouts')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS'))));
		}

		if ($type=='delurk')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS'))));
		}

		if ($type=='_delurk')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS')),array('_SEARCH:admin_ocf_join:delurk',do_lang_tempcode('DELETE_LURKERS'))));
			breadcrumb_set_self(do_lang_tempcode('CONFIRM'));
		}

		if ($type=='__delurk')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS')),array('_SEARCH:admin_ocf_join:delurk',do_lang_tempcode('DELETE_LURKERS'))));
		}

		if ($type=='import_csv')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS'))));
		}

		if ($type=='_import_csv')
		{
			breadcrumb_set_parents(array(array('_SEARCH:admin_ocf_join:menu',do_lang_tempcode('MEMBERS')),array('_SEARCH:admin_ocf_join:import_csv',do_lang_tempcode('IMPORT_MEMBER_CSV'))));
			breadcrumb_set_self(do_lang_tempcode('DONE'));
		}

		if ($type=='step1' || $type=='step2')
		{
			$this->title=get_screen_title('ADD_MEMBER');
		}

		if ($type=='group_member_timeouts' || $type=='_group_member_timeouts')
		{
			$this->title=get_screen_title('GROUP_MEMBER_TIMEOUTS');
		}

		if ($type=='delurk' || $type=='_delurk' || $type=='__delurk')
		{
			$this->title=get_screen_title('DELETE_LURKERS');
		}

		if ($type=='import_csv' || $type=='_import_csv')
		{
			$this->title=get_screen_title('IMPORT_MEMBER_CSV');
		}

		if ($type=='download_csv')
		{
			$this->title=get_screen_title('DOWNLOAD_MEMBER_CSV');

			$GLOBALS['OUTPUT_STREAMING']=false; // Too complex to do a pre_run for this properly
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_members_action');
		require_code('ocf_members_action2');

		$type=get_param('type','misc');

		if ($type=='menu') return $this->menu();
		if ($type=='misc') return $this->step1();
		if ($type=='step2') return $this->step2();
		if ($type=='delurk') return $this->delurk();
		if ($type=='_delurk') return $this->_delurk();
		if ($type=='__delurk') return $this->__delurk();
		if ($type=='download_csv') return $this->download_csv();
		if ($type=='import_csv') return $this->import_csv();
		if ($type=='_import_csv') return $this->_import_csv();
		if ($type=='group_member_timeouts') return $this->group_member_timeouts();
		if ($type=='_group_member_timeouts') return $this->_group_member_timeouts();

		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for choosing what to do
	 *
	 * @return tempcode		The UI
	 */
	function menu()
	{
		require_lang('lookup');
		if (addon_installed('welcome_emails')) require_lang('ocf_welcome_emails');
		if (addon_installed('ecommerce')) require_lang('ecommerce');
		if (addon_installed('staff')) require_lang('staff');

		require_code('templates_donext');
		return do_next_manager(get_screen_title('MEMBERS'),comcode_lang_string('DOC_MEMBERS'),
			array(
				/*	 type							  page	 params													 zone	  */
				array('addmember',array('admin_ocf_join',array('type'=>'misc'),get_module_zone('admin_ocf_join')),do_lang_tempcode('ADD_MEMBER'),('DOC_ADD_MEMBER')),
				(!has_privilege(get_member(),'member_maintenance'))?NULL:array('editmember',array('members',array('type'=>'misc'),get_module_zone('members'),do_lang_tempcode('SWITCH_ZONE_WARNING')),do_lang_tempcode('EDIT_MEMBER'),('DOC_EDIT_MEMBER')),
				array('merge_members',array('admin_ocf_merge_members',array('type'=>'misc'),get_module_zone('admin_ocf_merge_members')),do_lang_tempcode('MERGE_MEMBERS'),('DOC_MERGE_MEMBERS')),
				array('deletelurkers',array('admin_ocf_join',array('type'=>'delurk'),get_module_zone('admin_ocf_join')),do_lang_tempcode('DELETE_LURKERS'),('DOC_DELETE_LURKERS')),
				array('download_csv',array('admin_ocf_join',array('type'=>'download_csv'),get_module_zone('admin_ocf_join')),do_lang_tempcode('DOWNLOAD_MEMBER_CSV'),('DOC_DOWNLOAD_MEMBER_CSV')),
				array('import_csv',array('admin_ocf_join',array('type'=>'import_csv'),get_module_zone('admin_ocf_join')),do_lang_tempcode('IMPORT_MEMBER_CSV'),('DOC_IMPORT_MEMBER_CSV')),
				addon_installed('ocf_cpfs')?array('customprofilefields',array('admin_ocf_customprofilefields',array('type'=>'misc'),get_module_zone('admin_ocf_customprofilefields')),do_lang_tempcode('CUSTOM_PROFILE_FIELDS'),('DOC_CUSTOM_PROFILE_FIELDS')):NULL,
				addon_installed('welcome_emails')?array('welcome_emails',array('admin_ocf_welcome_emails',array('type'=>'misc'),get_module_zone('admin_ocf_welcome_emails')),do_lang_tempcode('WELCOME_EMAILS'),('DOC_WELCOME_EMAILS')):NULL,
				addon_installed('securitylogging')?array('investigateuser',array('admin_lookup',array(),get_module_zone('admin_lookup')),do_lang_tempcode('INVESTIGATE_USER'),('DOC_INVESTIGATE_USER')):NULL,
				array('usergroups_temp',array('admin_ocf_join',array('type'=>'group_member_timeouts'),get_module_zone('admin_ocf_join')),do_lang_tempcode('GROUP_MEMBER_TIMEOUTS'),('DOC_GROUP_MEMBER_TIMEOUTS')),
				addon_installed('ecommerce')?array('ecommerce',array('admin_ecommerce',array('type'=>'misc'),get_module_zone('admin_ecommerce')),do_lang_tempcode('CUSTOM_PRODUCT_USERGROUP'),('DOC_ECOMMERCE')):NULL,
				array('usergroups',array('admin_ocf_groups',array('type'=>'misc'),get_module_zone('admin_ocf_groups'),do_lang_tempcode('SWITCH_SECTION_WARNING')),do_lang_tempcode('USERGROUPS'),('DOC_GROUPS')),
				addon_installed('staff')?array('staff',array('admin_staff',array('type'=>'misc'),get_module_zone('admin_staff'),do_lang_tempcode('SWITCH_SECTION_WARNING')),do_lang_tempcode('STAFF'),('DOC_STAFF')):NULL,
			),do_lang('MEMBERS')
		);
	}

	/**
	 * The UI for adding a member.
	 *
	 * @return tempcode		The UI
	 */
	function step1()
	{
		require_code('form_templates');

		url_default_parameters__enable();
		list($fields,$hidden)=ocf_get_member_fields(false);
		url_default_parameters__disable();

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('OPTIONS'))));
		$fields->attach(form_input_tick(do_lang_tempcode('FORCE_TEMPORARY_PASSWORD'),do_lang_tempcode('DESCRIPTION_FORCE_TEMPORARY_PASSWORD'),'temporary_password',false));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>false,'TITLE'=>do_lang_tempcode('OPTIONS'))));
		$fields->attach(form_input_tick(do_lang_tempcode('FORCE_TEMPORARY_PASSWORD'),do_lang_tempcode('DESCRIPTION_FORCE_TEMPORARY_PASSWORD'),'temporary_password',false));

		$text=do_lang_tempcode('_ENTER_PROFILE_DETAILS');

		$submit_name=do_lang_tempcode('ADD_MEMBER');
		$url=build_url(array('page'=>'_SELF','type'=>'step2'),'_SELF');
		return do_template('FORM_SCREEN',array('_GUID'=>'3724dec184e27bb1bfebc5712e8faec2','PREVIEW'=>true,'HIDDEN'=>$hidden,'TITLE'=>$this->title,'FIELDS'=>$fields,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'URL'=>$url));
	}

	/**
	 * The actualiser for adding a member.
	 *
	 * @return tempcode		The UI
	 */
	function step2()
	{
		// Read in data
		$username=trim(post_param('username'));
		$password=trim(post_param('password'));
		$email_address=trim(post_param('email_address',''));
		$dob_day=post_param_integer('dob_day',NULL);
		$dob_month=post_param_integer('dob_month',NULL);
		$dob_year=post_param_integer('dob_year',NULL);
		$reveal_age=post_param_integer('reveal_age',0);
		$timezone=post_param('timezone',get_site_timezone());
		$language=post_param('language',get_site_default_lang());
		$allow_emails=post_param_integer('allow_emails',0);
		$allow_emails_from_staff=post_param_integer('allow_emails_from_staff',0);
		$custom_fields=ocf_get_all_custom_fields_match(ocf_get_all_default_groups(true));
		$actual_custom_fields=ocf_read_in_custom_fields($custom_fields);
		$validated=post_param_integer('validated',0);
		$primary_group=(has_privilege(get_member(),'assume_any_member'))?post_param_integer('primary_group'):NULL;
		$theme=post_param('theme','');
		$views_signatures=post_param_integer('views_signatures',0);
		$preview_posts=post_param_integer('preview_posts',0);
		$auto_monitor_contrib_content=post_param_integer('auto_monitor_contrib_content',0);
		$pt_allow=array_key_exists('pt_allow',$_POST)?implode(',',$_POST['pt_allow']):'';
		$tmp_groups=$GLOBALS['OCF_DRIVER']->get_usergroup_list(true,true);
		$all_pt_allow='';
		foreach (array_keys($tmp_groups) as $key)
		{
			if ($key!=db_get_first_id())
			{
				if ($all_pt_allow!='') $all_pt_allow.=',';
				$all_pt_allow.=strval($key);
			}
		}
		if ($pt_allow==$all_pt_allow) $pt_allow='*';
		$pt_rules_text=post_param('pt_rules_text','');

		// Add member
		$password_compatibility_scheme=((post_param_integer('temporary_password',0)==1)?'temporary':'');
		$id=ocf_make_member($username,$password,$email_address,NULL,$dob_day,$dob_month,$dob_year,$actual_custom_fields,$timezone,$primary_group,$validated,time(),NULL,'',NULL,'',0,$preview_posts,$reveal_age,'','','',$views_signatures,$auto_monitor_contrib_content,$language,$allow_emails,$allow_emails_from_staff,'','',true,$password_compatibility_scheme,'',post_param_integer('zone_wide',0),NULL,NULL,post_param_integer('highlighted_name',0),$pt_allow,$pt_rules_text);

		if (addon_installed('content_reviews'))
		{
			require_code('content_reviews2');
			content_review_set('member',strval($id));
		}

		// Secondary groups
		if (array_key_exists('secondary_groups',$_POST))
		{
			require_code('ocf_groups_action2');
			$members_groups=array();
			$group_count=$GLOBALS['FORUM_DB']->query_select_value('f_groups','COUNT(*)');
			$groups=list_to_map('id',$GLOBALS['FORUM_DB']->query_select('f_groups',array('*'),($group_count>200)?array('g_is_private_club'=>0):NULL));
			foreach ($_POST['secondary_groups'] as $group_id)
			{
				$group=$groups[intval($group_id)];

				if (($group['g_hidden']==1) && (!in_array($group['id'],$members_groups)) && (!has_privilege(get_member(),'see_hidden_groups'))) continue;

				if ((in_array($group['id'],$members_groups)) || (has_privilege(get_member(),'assume_any_member')) || ($group['g_open_membership']==1))
					ocf_add_member_to_group($id,$group['id']);
			}
		}

		$special_links=array();

		if (addon_installed('galleries'))
		{
			require_lang('galleries');
			$special_links[]=array('galleries',array('cms_galleries',array('type'=>'gimp','member_id'=>$id),get_module_zone('cms_galleries')),do_lang('ADD_GALLERY'));
		}

		require_code('templates_donext');
		return do_next_manager($this->title,do_lang_tempcode('SUCCESS'),
			NULL,
			NULL,
			/*		TYPED-ORDERED LIST OF 'LINKS'		*/
			/*	 page	 params				  zone	  */
			array('_SELF',array('type'=>'misc'),'_SELF'),								 // Add one
			NULL,// Edit this
			NULL,																						// Edit one
			array('members',array('type'=>'view','id'=>$id),get_module_zone('members')),		 // View this
			array('members',array('type'=>'misc'),get_module_zone('members'),do_lang_tempcode('MEMBERS')),				// View archive
			NULL,						// Add to category
			NULL,							 // Add one category
			NULL,							 // Edit one category
			NULL,  // Edit this category
			NULL,						// View this category
			/*	  SPECIALLY TYPED 'LINKS'				  */
			$special_links,
			NULL,
			NULL,
			NULL,
			NULL,
			do_lang_tempcode('MEMBERS')
		);
	}

	/**
	 * The UI for managing temporary usergroup memberships.
	 *
	 * @return tempcode		The UI
	 */
	function group_member_timeouts()
	{
		if (!cron_installed()) attach_message(do_lang_tempcode('CRON_NEEDED_TO_WORK',escape_html(get_tutorial_url('tut_configuration'))),'warn');

		require_code('form_templates');
		require_code('templates_results_table');

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',100);
		$max_rows=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_select_value('f_group_member_timeouts','COUNT(*)');
		$fields_title=results_field_title(array(
			do_lang_tempcode('USERNAME'),
			do_lang_tempcode('_USERGROUP'),
			do_lang_tempcode('TIME'),
		));

		$timeouts=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_select('f_group_member_timeouts',array('member_id','group_id','timeout'),NULL,'',$max,$start);

		$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list();

		$tfields=new ocp_tempcode();
		foreach ($timeouts as $timeout)
		{
			$tfields->attach(results_entry(array(
				$GLOBALS['FORUM_DRIVER']->get_username($timeout['member_id']),
				isset($usergroups[$timeout['group_id']])?$usergroups[$timeout['group_id']]:do_lang('UNKNOWN'),
				display_time_period($timeout['timeout']-time()),
			),true));
		}

		$results_table=results_table(do_lang('GROUP_MEMBER_TIMEOUTS'),$start,'start',$max,'max',$max_rows,$fields_title,$tfields);

		$fields=new ocp_tempcode();
		$fields->attach(form_input_username(do_lang_tempcode('USERNAME'),'','username','',true));
		$_usergroups=new ocp_tempcode();
		foreach ($usergroups as $uid=>$name)
		{
			if ($uid!=db_get_first_id())
				$_usergroups->attach(form_input_list_entry($uid,false,$name));
		}
		require_lang('dates');
		$fields->attach(form_input_list(do_lang_tempcode('_USERGROUP'),'','group_id',$_usergroups,NULL,false,true));
		$fields->attach(form_input_integer(do_lang_tempcode('_MINUTES'),do_lang_tempcode('DESCRIPTION_GROUPMT_MINUTES'),'num_minutes',60,true));

		$post_url=build_url(array('page'=>'_SELF','type'=>'_group_member_timeouts'),'_SELF');
		$submit_name=do_lang_tempcode('ADD');

		$form=do_template('FORM',array('_GUID'=>'2afadffabe2becb6eac071db085edc57','TABINDEX'=>strval(get_form_field_tabindex()),'HIDDEN'=>'','TEXT'=>'','FIELDS'=>$fields,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name));

		$tpl=do_template('RESULTS_TABLE_SCREEN',array('_GUID'=>'e9ce4084126653162ad84839fb7f47e3','TITLE'=>$this->title,'RESULTS_TABLE'=>$results_table,'FORM'=>$form));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

	/**
	 * The actualiser for managing temporary usergroup memberships.
	 *
	 * @return tempcode		The UI
	 */
	function _group_member_timeouts()
	{
		$group_id=post_param_integer('group_id');

		if (!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) // Security issue, don't allow privilege elevation
		{
			$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
			if (in_array($group_id,$admin_groups)) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
		}

		$username=post_param('username');
		$num_minutes=post_param_integer('num_minutes');
		$prefer_for_primary_group=false;//(post_param_integer('prefer_for_primary_group',0)==1); Don't promote this bad choice

		$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
		if (is_null($member_id)) warn_exit(do_lang_tempcode('_MEMBER_NO_EXIST',escape_html($username)));

		require_code('group_member_timeouts');
		bump_member_group_timeout($member_id,$group_id,$num_minutes,$prefer_for_primary_group);

		$url=build_url(array('page'=>'_SELF','type'=>'group_member_timeouts'),'_SELF');

		return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The UI for choosing delurk criteria.
	 *
	 * @return tempcode		The UI
	 */
	function delurk()
	{
		require_code('form_templates');

		require_lang('ocf_lurkers');

		$hidden=new ocp_tempcode();

		url_default_parameters__enable();

		$_max_posts=get_value('delurk__max_posts');
		$_max_points=get_value('delurk__max_points');
		$_max_logged_actions=get_value('delurk__max_logged_actions');
		$_min_days_since_login=get_value('delurk__min_days_since_login');
		$_min_days_since_join=get_value('delurk__min_days_since_join');
		$_usergroups=get_value('delurk__usergroups');
		if (is_null($_max_posts)) $max_posts=2; else $max_posts=intval($_max_posts);
		if (is_null($_max_points)) $max_points=150; else $max_points=intval($_max_points);
		if (is_null($_max_logged_actions)) $max_logged_actions=2; else $max_logged_actions=intval($_max_logged_actions);
		if (is_null($_min_days_since_login)) $min_days_since_login=60; else $min_days_since_login=intval($_min_days_since_login);
		if (is_null($_min_days_since_join)) $min_days_since_join=90; else $min_days_since_join=intval($_min_days_since_join);
		if (is_null($_usergroups))
		{
			$usergroups=array();
		} else
		{
			$temp=explode(',',$_usergroups);
			$usergroups=array();
			foreach ($temp as $t)
			{
				$usergroups[]=intval($t);
			}
		}

		$fields=new ocp_tempcode();
		$fields->attach(form_input_integer(do_lang_tempcode('DELURK_MAX_POSTS'),do_lang_tempcode('DELURK_MAX_POSTS_DESCRIPTION'),'max_posts',$max_posts,true));
		if (addon_installed('points'))
		{
			$fields->attach(form_input_integer(do_lang_tempcode('DELURK_MAX_POINTS'),do_lang_tempcode('DELURK_MAX_POINTS_DESCRIPTION'),'max_points',$max_points,true));
		} else
		{
			$hidden->attach(form_input_hidden('max_points','0'));
		}
		$fields->attach(form_input_integer(do_lang_tempcode('DELURK_MAX_LOGGED_ACTIONS'),do_lang_tempcode('DELURK_MAX_LOGGED_ACTIONS_DESCRIPTION'),'max_logged_actions',$max_logged_actions,true));
		$fields->attach(form_input_integer(do_lang_tempcode('DELURK_MIN_DAYS_SINCE_LOGIN'),do_lang_tempcode('DELURK_MIN_DAYS_SINCE_LOGIN_DESCRIPTION'),'min_days_since_login',$min_days_since_login,true));
		$fields->attach(form_input_integer(do_lang_tempcode('DELURK_MIN_DAYS_SINCE_JOIN'),do_lang_tempcode('DELURK_MIN_DAYS_SINCE_JOIN_DESCRIPTION'),'min_days_since_join',$min_days_since_join,true));
		$groups=new ocp_tempcode();
		$group_count=$GLOBALS['FORUM_DB']->query_select_value('f_groups','COUNT(*)');
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name'),($group_count>200)?array('g_is_private_club'=>0):NULL);
		foreach ($rows as $row)
		{
			if ($row['id']!=db_get_first_id())
				$groups->attach(form_input_list_entry(strval($row['id']),in_array($row['id'],$usergroups),get_translated_text($row['g_name'],$GLOBALS['FORUM_DB'])));
		}
		$fields->attach(form_input_multi_list(do_lang_tempcode('EXCEPT_IN_USERGROUPS'),do_lang_tempcode('DELURK_USERGROUPS_DESCRIPTION'),'usergroups',$groups));

		url_default_parameters__disable();

		$submit_name=do_lang_tempcode('PROCEED');
		$post_url=build_url(array('page'=>'_SELF','type'=>'_delurk'),'_SELF');
		$text=do_lang_tempcode('CHOOSE_DELURK_CRITERIA');

		return do_template('FORM_SCREEN',array('_GUID'=>'f911fc5be2865bdd065abf7c636530d4','TITLE'=>$this->title,'HIDDEN'=>$hidden,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * Find a mapping of member IDs to usernames, of those who'll get delurked.
	 *
	 * @param  integer			Maximum forum posts
	 * @param  integer			Maximum points
	 * @param  integer			Maximum logged actions
	 * @param  integer			Minimum days since last login
	 * @param  integer			Minimum days since joining
	 * @param  array				List of usergroups
	 * @return array				Mapping of lurkers
	 */
	function find_lurkers($max_posts,$max_points,$max_logged_actions,$min_days_since_login,$min_days_since_join,$usergroups)
	{
		$start=0;
		do
		{
			$rows=$GLOBALS['FORUM_DB']->query('SELECT id,m_username FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE id<>'.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).' AND id<>'.strval(get_member()).' AND m_cache_num_posts<='.strval(intval($max_posts)).' AND m_last_visit_time<'.strval(time()-$min_days_since_login*60*60*24).' AND m_join_time<'.strval(time()-$min_days_since_join*60*60*24),500,$start);
			$out=array();
			if (addon_installed('points'))
			{
				require_code('points');
			}
			foreach ($rows as $row)
			{
				if (addon_installed('points'))
				{
					if (total_points($row['id'])>$max_points) continue;
				}
				$_usergroups=$GLOBALS['FORUM_DRIVER']->get_members_groups($row['id']);
				foreach ($_usergroups as $g_id)
				{
					if (in_array($g_id,$usergroups)) continue 2;
				}
				$num_actions=$GLOBALS['SITE_DB']->query_select_value('adminlogs','COUNT(*)',array('member_id'=>$row['id']));
				if ($num_actions>$max_logged_actions) continue;

				if (count($out)==500)
				{
					attach_message(do_lang_tempcode('TOO_MANY_LURKERS'),'warn');
					return $out;
				}

				$out[$row['id']]=$row['m_username'];
			}
			$start+=500;
		}
		while (count($rows)==500);

		return $out;
	}

	/**
	 * The UI for confirming the deletion results of delurk criteria.
	 *
	 * @return tempcode		The UI
	 */
	function _delurk()
	{
		if (function_exists('set_time_limit')) @set_time_limit(100);

		require_lang('ocf_lurkers');

		$max_posts=post_param_integer('max_posts');
		$max_points=post_param_integer('max_points');
		$max_logged_actions=post_param_integer('max_logged_actions');
		$min_days_since_login=post_param_integer('min_days_since_login');
		$min_days_since_join=post_param_integer('min_days_since_join');
		$usergroups=array();
		if (array_key_exists('usergroups',$_POST))
			foreach ($_POST['usergroups'] as $g_id)
				$usergroups[]=intval($g_id);
		$lurkers=$this->find_lurkers($max_posts,$max_points,$max_logged_actions,$min_days_since_login,$min_days_since_join,$usergroups);

		if (count($lurkers)==0) inform_exit(do_lang_tempcode('NO_LURKERS_FOUND'));

		$_lurkers=array();
		foreach ($lurkers as $id=>$username)
		{
			if (is_guest($id)) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

			$_lurkers[]=array('ID'=>strval($id),'USERNAME'=>$username,'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($id,false,true));
		}

		$url=build_url(array('page'=>'_SELF','type'=>'__delurk'),'_SELF');

		return do_template('OCF_DELURK_CONFIRM',array('_GUID'=>'52870b8546653782e354533602531970','TITLE'=>$this->title,'LURKERS'=>$_lurkers,'URL'=>$url));
	}

	/**
	 * The actualiser for deletion members according to delurk criteria.
	 *
	 * @return tempcode		The UI
	 */
	function __delurk()
	{
		require_lang('ocf_lurkers');

		foreach ($_POST as $key=>$val)
		{
			if (substr($key,0,7)=='lurker_')
			{
				$member_id=intval(substr($key,7));
				ocf_delete_member($member_id);
			}
		}

		return inform_screen($this->title,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser to download a CSV of members.
	 *
	 * @return tempcode		The UI
	 */
	function download_csv()
	{
		require_code('tasks');
		return call_user_func_array__long_task(do_lang('DOWNLOAD_MEMBER_CSV'),$this->title,'download_member_csv');
	}

	/**
	 * The UI for importing a CSV file.
	 *
	 * @return tempcode		The UI
	 */
	function import_csv()
	{
		require_code('form_templates');

		require_lang('ocf');

		$hidden=new ocp_tempcode();

		$fields=new ocp_tempcode();
		handle_max_file_size($hidden);
		$fields->attach(form_input_upload(do_lang_tempcode('UPLOAD'),do_lang_tempcode('DESCRIPTION_IMPORT_CSV'),'file',true,NULL,NULL,true,'csv,txt'));
		$fields->attach(form_input_line(do_lang_tempcode('DEFAULT_PASSWORD'),do_lang_tempcode('DESCRIPTION_DEFAULT_PASSWORD'),'default_password','',false));
		$fields->attach(form_input_tick(do_lang_tempcode('FORCE_TEMPORARY_PASSWORD'),do_lang_tempcode('DESCRIPTION_FORCE_TEMPORARY_PASSWORD'),'temporary_password',false));

		$submit_name=do_lang_tempcode('IMPORT_MEMBER_CSV');
		$post_url=build_url(array('page'=>'_SELF','type'=>'_import_csv'),'_SELF');
		$text='';

		return do_template('FORM_SCREEN',array('_GUID'=>'9196652a093d7f3a0e5dd0922f74cc51','TITLE'=>$this->title,'HIDDEN'=>$hidden,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * The actualiser for importing a CSV file.
	 *
	 * @return tempcode		The UI
	 */
	function _import_csv()
	{
		$default_password=post_param('default_password');

		$use_temporary_passwords=(post_param_integer('temporary_password',0)==1);

		require_code('uploads');
		if ((is_swf_upload(true)) || ((array_key_exists('file',$_FILES)) && (is_uploaded_file($_FILES['file']['tmp_name']))))
		{
			if (filesize($_FILES['file']['tmp_name'])<1024*1024*3) // Cleanup possible line ending problems, but only if file not too big
			{
				$fixed_contents=unixify_line_format(file_get_contents($_FILES['file']['tmp_name']));
				$myfile=@fopen($_FILES['file']['tmp_name'],'wb');
				if ($myfile!==false)
				{
					fwrite($myfile,$fixed_contents);
					fclose($myfile);
				}
			}

			$target_path=get_custom_file_base().'/safe_mode_temp/'.basename($_FILES['file']['tmp_name']);
			copy($_FILES['file']['tmp_name'],$target_path);
			fix_permissions($target_path);
			sync_file($target_path);
		} else
		{
			warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
		}

		require_code('tasks');
		return call_user_func_array__long_task(do_lang('IMPORT_MEMBER_CSV'),$this->title,'import_member_csv',array($default_password,$use_temporary_passwords,$target_path));
	}

}


