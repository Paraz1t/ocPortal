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

require_code('aed_module');

/**
 * Module page class.
 */
class Module_admin_ocf_groups extends standard_aed_module
{
	var $lang_type='GROUP';
	var $select_name='NAME';
	var $javascript='if (document.getElementById(\'delete\')) { var form=document.getElementById(\'delete\').form; var crf=function() { if (form.elements[\'new_usergroup\']) form.elements[\'new_usergroup\'].disabled=(form.elements[\'delete\'] && !form.elements[\'delete\'].checked); }; crf(); if (form.elements[\'delete\']) form.elements[\'delete\'].onchange=crf; } if (document.getElementById(\'is_presented_at_install\')) { var form=document.getElementById(\'is_presented_at_install\').form; var crf2=function() { if (form.elements[\'is_default\']) form.elements[\'is_default\'].disabled=(form.elements[\'is_presented_at_install\'].checked); if (form.elements[\'is_presented_at_install\'].checked) form.elements[\'is_default\'].checked=false; }; crf2(); form.elements[\'is_presented_at_install\'].onchange=crf2; var crf3=function() { if (form.elements[\'absorb\']) form.elements[\'absorb\'].disabled=(form.elements[\'is_private_club\'] && form.elements[\'is_private_club\'].checked); }; crf3(); if (form.elements[\'is_private_club\']) form.elements[\'is_private_club\'].onchange=crf3; }';
	var $award_type='group';
	var $possibly_some_kind_of_upload=true;
	var $output_of_action_is_confirmation=true;
	var $do_preview=NULL;
	var $archive_entry_point='_SEARCH:groups';
	var $archive_label='USERGROUPS';
	var $view_entry_point='_SEARCH:groups:view:id=_ID';
	var $menu_label='USERGROUPS';
	var $orderer='g_name';
	var $title_is_multi_lang=true;

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array_merge(array('misc'=>'MANAGE_USERGROUPS'),parent::get_entry_points());
	}

	/**
	 * Standard aed_module run_start.
	 *
	 * @param  ID_TEXT		The type of module execution
	 * @return tempcode		The output of the run
	 */
	function run_start($type)
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/usergroups';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_subcom';

		if (get_forum_type()!='ocf') warn_exit(do_lang_tempcode('NO_OCF')); else ocf_require_all_forum_stuff();
		require_code('ocf_groups_action');
		require_code('ocf_groups_action2');

		$this->add_text=do_lang_tempcode('GROUP_TEXT');
		$this->edit_text=do_lang_tempcode('GROUP_TEXT');

		if ($type=='ad')
		{
			require_javascript('javascript_ajax');
			$script=find_script('snippet');
			$this->javascript.="
				var form=document.getElementById('main_form');
				form.old_submit=form.onsubmit;
				form.onsubmit=function()
					{
						document.getElementById('submit_button').disabled=true;
						var url='".addslashes($script)."?snippet=exists_usergroup&name='+window.encodeURIComponent(form.elements['name'].value);
						if (!do_ajax_field_test(url))
						{
							document.getElementById('submit_button').disabled=false;
							return false;
						}
						document.getElementById('submit_button').disabled=false;
						if (typeof form.old_submit!='undefined' && form.old_submit) return form.old_submit();
						return true;
					};
			";
		}

		$this->add_one_label=do_lang_tempcode('ADD_GROUP');
		$this->edit_this_label=do_lang_tempcode('EDIT_THIS_GROUP');
		$this->edit_one_label=do_lang_tempcode('EDIT_GROUP');

		if ($type=='misc') return $this->misc();
		return new ocp_tempcode();
	}

	/**
	 * The do-next manager for before content management.
	 *
	 * @return tempcode		The UI
	 */
	function misc()
	{
		require_code('templates_donext');
		require_code('fields');
		return do_next_manager(get_screen_title('MANAGE_USERGROUPS'),comcode_lang_string('DOC_GROUPS'),
					array_merge(array(
						/*	 type							  page	 params													 zone	  */
						array('add_one',array('_SELF',array('type'=>'ad'),'_SELF'),do_lang('ADD_GROUP')),
						array('edit_one',array('_SELF',array('type'=>'ed'),'_SELF'),do_lang('EDIT_GROUP')),
					),manage_custom_fields_donext_link('group')),
					do_lang('MANAGE_USERGROUPS')
		);
	}

	/**
	 * Get tempcode for a adding/editing form.
	 *
	 * @param  ?GROUP			The usergroup being edited (NULL: adding, not editing)
	 * @param  SHORT_TEXT	The usergroup name
	 * @param  BINARY			Whether this is a default usergroup
	 * @param  BINARY			Whether members of the usergroup are super-administrators
	 * @param  BINARY			Whether members of the usergroup are super-moderators
	 * @param  ID_TEXT		The username of the usergroup leader
	 * @param  SHORT_TEXT	The default title for members with this as their primary usergroup
	 * @param  URLPATH		The usergroup rank image
	 * @param  ?GROUP			The target for promotion from this usergroup (NULL: no promotion prospects)
	 * @param  ?integer		The point threshold upon which promotion occurs (NULL: no promotion prospects)
	 * @param  integer		The number of seconds between submission flood controls
	 * @param  integer		The number of seconds between access flood controls
	 * @param  integer		The number of gift points members of this usergroup get when they start
	 * @param  integer		The number of gift points members of this usergroup get per-day
	 * @param  integer		The number of megabytes members can upload per day
	 * @param  integer		The maximum number of attachments members of this usergroup may have per post
	 * @param  integer		The maximum avatar width members of this usergroup may have
	 * @param  integer		The maximum avatar height members of this usergroup may have
	 * @param  integer		The maximum post length members of this usergroup may have
	 * @param  integer		The maximum signature length members of this usergroup may have
	 * @param  BINARY			Whether to lock out unverified IP addresses until e-mail confirmation
	 * @param  BINARY			Whether the usergroup is presented for joining at joining (implies anyone may be in the usergroup, but only choosable at joining)
	 * @param  BINARY			Whether the name and membership of the usergroup is hidden
	 * @param  ?integer		The display order this usergroup will be given, relative to other usergroups. Lower numbered usergroups display before higher numbered usergroups (NULL: last).
	 * @param  BINARY			Whether the rank image will not be shown for secondary membership
	 * @param  BINARY			Whether members may join this usergroup without requiring any special permission
	 * @param  BINARY			Whether this usergroup is a private club. Private clubs may be managed in the CMS zone, and do not have any special permissions - except over their own associated forum.
	 * @return array			A pair: The input fields, Hidden fields
	 */
	function get_form_fields($id=NULL,$name='',$is_default=0,$is_super_admin=0,$is_super_moderator=0,$group_leader='',$title='',$rank_image='',$promotion_target=NULL,$promotion_threshold=NULL,$flood_control_submit_secs=0,$flood_control_access_secs=0,$gift_points_base=25,$gift_points_per_day=1,$max_daily_upload_mb=5,$max_attachments_per_post=20,$max_avatar_width=80,$max_avatar_height=80,$max_post_length_comcode=40000,$max_sig_length_comcode=1000,$enquire_on_new_ips=0,$is_presented_at_install=0,$group_is_hidden=0,$order=NULL,$rank_image_pri_only=1,$open_membership=0,$is_private_club=0)
	{
		if ($GLOBALS['SITE_DB']->connection_write!=$GLOBALS['SITE_DB']->connection_write)
		{
			attach_message(do_lang_tempcode('EDITING_ON_WRONG_MSN'),'warn');
		}

		if (is_null($group_leader)) $group_leader='';

		$fields=new ocp_tempcode();
		$hidden=new ocp_tempcode();

		require_code('form_templates');
		$fields->attach(form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_USERGROUP_TITLE'),'name',$name,true));

		if ((addon_installed('ocf_clubs')) && (!is_null($id)))
			$fields->attach(form_input_tick(do_lang_tempcode('IS_PRIVATE_CLUB'),do_lang_tempcode('IS_PRIVATE_CLUB_DESCRIPTION'),'is_private_club',$is_private_club==1));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>$title=='' && $group_leader=='','TITLE'=>do_lang_tempcode('ADVANCED'))));

		$fields->attach(form_input_line(do_lang_tempcode('TITLE'),do_lang_tempcode('DESCRIPTION_GROUP_TITLE'),'title',$title,false));
		$fields->attach(form_input_username(do_lang_tempcode('GROUP_LEADER'),do_lang_tempcode('DESCRIPTION_GROUP_LEADER'),'group_leader',$group_leader,false));

		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name','g_is_super_admin'),array('g_is_private_club'=>0));
		$orderlist=new ocp_tempcode();
		$group_count=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)');
		$num_groups=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)',($group_count>200)?array('g_is_private_club'=>0):NULL);
		if (is_null($id)) $num_groups++;
		for ($i=0;$i<$num_groups;$i++)
		{
			$orderlist->attach(form_input_list_entry(strval($i),(($i===$order) || ((is_null($id)) && ($i==$num_groups-1))),integer_format($i+1)));
		}
		$fields->attach(form_input_list(do_lang_tempcode('ORDER'),do_lang_tempcode('USERGROUP_DISPLAY_ORDER_DESCRIPTION'),'order',$orderlist));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('JOINING'))));
		if ((is_null($id)) || ($id!=db_get_first_id()))
		{
			$fields->attach(form_input_tick(do_lang_tempcode('IS_PRESENTED_AT_INSTALL'),do_lang_tempcode('DESCRIPTION_IS_PRESENTED_AT_INSTALL'),'is_presented_at_install',$is_presented_at_install==1));
			$fields->attach(form_input_tick(do_lang_tempcode('DEFAULT_GROUP'),do_lang_tempcode('DESCRIPTION_IS_DEFAULT_GROUP'),'is_default',$is_default==1));
		}
		$fields->attach(form_input_tick(do_lang_tempcode('OPEN_MEMBERSHIP'),do_lang_tempcode('OPEN_MEMBERSHIP_DESCRIPTION'),'open_membership',$open_membership==1));
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('RANK'))));
		if (addon_installed('points'))
		{
			$promotion_target_groups=form_input_list_entry('-1',false,do_lang_tempcode('NA_EM'));
			foreach ($rows as $group)
			{
				if (($group['id']!=$id) && ($group['id']!=db_get_first_id()))
					$promotion_target_groups->attach(form_input_list_entry(strval($group['id']),($group['id']==$promotion_target),get_translated_text($group['g_name'],$GLOBALS['FORUM_DB'])));
			}
			$fields->attach(form_input_list(do_lang_tempcode('PROMOTION_TARGET'),do_lang_tempcode('DESCRIPTION_PROMOTION_TARGET'),'promotion_target',$promotion_target_groups));
			$fields->attach(form_input_integer(do_lang_tempcode('PROMOTION_THRESHOLD'),do_lang_tempcode('DESCRIPTION_PROMOTION_THRESHOLD'),'promotion_threshold',$promotion_threshold,false));
		}

		require_code('themes2');
		$ids=get_all_image_ids_type('ocf_rank_images',false,$GLOBALS['FORUM_DB']);

		if (get_base_url()==get_forum_base_url())
		{
			$set_name='rank_image';
			$required=false;
			$set_title=do_lang_tempcode('RANK_IMAGE');
			$field_set=(count($ids)==0)?new ocp_tempcode():alternate_fields_set__start($set_name);

			$field_set->attach(form_input_upload(do_lang_tempcode('UPLOAD'),'','file',$required,NULL,NULL,true,str_replace(' ','',get_option('valid_images'))));

			$image_chooser_field=form_input_theme_image(do_lang_tempcode('STOCK'),'','theme_img_code',$ids,NULL,$rank_image,NULL,false,$GLOBALS['FORUM_DB']);
			$field_set->attach($image_chooser_field);

			$fields->attach(alternate_fields_set__end($set_name,$set_title,do_lang_tempcode('DESCRIPTION_RANK_IMAGE'),$field_set,$required));

			handle_max_file_size($hidden,'image');
		} else
		{
			if (count($ids)==0) warn_exit(do_lang_tempcode('NO_SELECTABLE_THEME_IMAGES_MSN','ocf_rank_images'));

			$image_chooser_field=form_input_theme_image(do_lang_tempcode('STOCK'),'','theme_img_code',$ids,NULL,$rank_image,NULL,true,$GLOBALS['FORUM_DB']);
			$fields->attach($image_chooser_field);
		}

		$fields->attach(form_input_tick(do_lang_tempcode('RANK_IMAGE_PRI_ONLY'),do_lang_tempcode('RANK_IMAGE_PRI_ONLY_DESCRIPTION'),'rank_image_pri_only',$rank_image_pri_only==1));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('BENEFITS'))));
		$sa_descrip=do_lang_tempcode('DESCRIPTION_IS_SUPER_ADMIN');
		if ($is_super_admin==1)
		{
			$sa_descrip->attach(do_lang_tempcode('DESCRIPTION_IS_SUPER_ADMIN_B'));
		}
		$fields->attach(form_input_tick(do_lang_tempcode('SUPER_ADMIN'),$sa_descrip,'is_super_admin',$is_super_admin==1));
		$fields->attach(form_input_tick(do_lang_tempcode('SUPER_MODERATOR'),do_lang_tempcode('DESCRIPTION_IS_SUPER_MODERATOR'),'is_super_moderator',$is_super_moderator==1));
		if (addon_installed('points'))
		{
			$fields->attach(form_input_integer(do_lang_tempcode('GIFT_POINTS_BASE'),do_lang_tempcode('DESCRIPTION_GIFT_POINTS_BASE'),'gift_points_base',$gift_points_base,true));
			$fields->attach(form_input_integer(do_lang_tempcode('GIFT_POINTS_PER_DAY'),do_lang_tempcode('DESCRIPTION_GIFT_POINTS_PER_DAY'),'gift_points_per_day',$gift_points_per_day,true));
		}

		require_lang('security');
		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('SECURITY'))));
		$fields->attach(form_input_tick(do_lang_tempcode('HIDDEN_USERGROUP'),do_lang_tempcode('DESCRIPTION_GROUP_HIDDEN'),'hidden',$group_is_hidden==1));
		$fields->attach(form_input_tick(do_lang_tempcode('ENQUIRE_ON_NEW_IPS'),do_lang_tempcode('DESCRIPTION_ENQUIRE_ON_NEW_IPS'),'enquire_on_new_ips',$enquire_on_new_ips==1));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('RESTRICTIONS'))));
		$fields->attach(form_input_integer(do_lang_tempcode('MAX_ATTACHMENTS_PER_POST'),do_lang_tempcode('DESCRIPTION_MAX_ATTACHMENTS_PER_POST'),'max_attachments_per_post',$max_attachments_per_post,true));
		$fields->attach(form_input_integer(do_lang_tempcode('MAX_DAILY_UPLOAD_MB'),do_lang_tempcode('DESCRIPTION_MAX_DAILY_UPLOAD_MB'),'max_daily_upload_mb',$max_daily_upload_mb,true));
		if (addon_installed('ocf_member_avatars'))
		{
			$fields->attach(form_input_integer(do_lang_tempcode('MAX_AVATAR_WIDTH'),do_lang_tempcode('DESCRIPTION_MAX_AVATAR_WIDTH'),'max_avatar_width',$max_avatar_width,true));
			$fields->attach(form_input_integer(do_lang_tempcode('MAX_AVATAR_HEIGHT'),do_lang_tempcode('DESCRIPTION_MAX_AVATAR_HEIGHT'),'max_avatar_height',$max_avatar_height,true));
		}
		$fields->attach(form_input_integer(do_lang_tempcode('MAX_POST_LENGTH_COMCODE'),do_lang_tempcode('DESCRIPTION_MAX_POST_LENGTH_COMCODE'),'max_post_length_comcode',$max_post_length_comcode,true));
		if (addon_installed('ocf_signatures'))
		{
			$fields->attach(form_input_integer(do_lang_tempcode('MAX_SIG_LENGTH_COMCODE'),do_lang_tempcode('DESCRIPTION_MAX_SIG_LENGTH_COMCODE'),'max_sig_length_comcode',$max_sig_length_comcode,true));
		}

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('FLOOD_CONTROL'))));
		$fields->attach(form_input_integer(do_lang_tempcode('FLOOD_CONTROL_ACCESS_SECS'),do_lang_tempcode('DESCRIPTION_FLOOD_CONTROL_ACCESS_SECS'),'flood_control_access_secs',$flood_control_access_secs,true));
		$fields->attach(form_input_integer(do_lang_tempcode('FLOOD_CONTROL_SUBMIT_SECS'),do_lang_tempcode('DESCRIPTION_FLOOD_CONTROL_SUBMIT_SECS'),'flood_control_submit_secs',$flood_control_submit_secs,true));

		$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('ACTIONS'))));

		//if ((is_null($id)) || ($id>db_get_first_id()+8))
		{
			$copy_members_from_groups=new ocp_tempcode();
			foreach ($rows as $row)
			{
				if (($row['id']!=db_get_first_id()) && ($row['id']!=$id))
					$copy_members_from_groups->attach(form_input_list_entry(strval($row['id']),false,get_translated_text($row['g_name'],$GLOBALS['FORUM_DB'])));
			}
			$fields->attach(form_input_multi_list(do_lang_tempcode('COPY_MEMBERS_INTO'),do_lang_tempcode('DESCRIPTION_COPY_MEMBERS_INTO'),'copy_members_into',$copy_members_from_groups));
		}

		// Take permissions from
		$permissions_from_groups=new ocp_tempcode();
		$permissions_from_groups=form_input_list_entry('-1',false,do_lang_tempcode('NA_EM'));
		foreach ($rows as $group)
		{
			if ($group['id']!=$id)
				$permissions_from_groups->attach(form_input_list_entry(strval($group['id']),false,get_translated_text($group['g_name'],$GLOBALS['FORUM_DB'])));
		}
		$fields->attach(form_input_list(do_lang_tempcode('DEFAULT_PERMISSIONS_FROM'),do_lang_tempcode(is_null($id)?'DESCRIPTION_DEFAULT_PERMISSIONS_FROM_NEW':'DESCRIPTION_DEFAULT_PERMISSIONS_FROM'),'absorb',$permissions_from_groups));

		$this->appended_actions_already=true;

		return array($fields,$hidden);
	}

	/**
	 * Standard aed_module table function.
	 *
	 * @param  array			Details to go to build_url for link to the next screen.
	 * @return array			A quartet: The choose table, Whether re-ordering is supported from this screen, Search URL, Archive URL.
	 */
	function nice_get_choose_table($url_map)
	{
		require_code('templates_results_table');

		$default_order='g_promotion_threshold ASC,id ASC';
		$current_ordering=get_param('sort',$default_order,true);
		$sortables=array(
			'g_name'=>do_lang_tempcode('NAME'),
			'g_is_presented_at_install'=>do_lang_tempcode('IS_PRESENTED_AT_INSTALL'),
			'g_is_default'=>do_lang_tempcode('DEFAULT_GROUP'),
			'g_open_membership'=>do_lang_tempcode('OPEN_MEMBERSHIP'),
			'g_promotion_threshold ASC,id'=>do_lang_tempcode('PROMOTION_TARGET'),
			'g_is_super_admin'=>do_lang_tempcode('SUPER_ADMIN'),
			'g_order'=>do_lang_tempcode('ORDER'),
		);
		if ($current_ordering=='g_promotion_threshold ASC,id ASC')
		{
			list($sortable,$sort_order)=array('g_promotion_threshold ASC,id','ASC');
		}
		elseif (($current_ordering=='g_promotion_threshold DESC,id DESC') || ($current_ordering=='g_promotion_threshold ASC,id DESC'))
		{
			list($sortable,$sort_order)=array('g_promotion_threshold DESC,id','DESC');
		} else
		{
			if (strpos($current_ordering,' ')===false) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
			list($sortable,$sort_order)=explode(' ',$current_ordering,2);
			if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
				log_hack_attack_and_exit('ORDERBY_HACK');
			global $NON_CANONICAL_PARAMS;
			$NON_CANONICAL_PARAMS[]='sort';
		}

		$header_row=results_field_title(array(
			do_lang_tempcode('NAME'),
			do_lang_tempcode('IS_PRESENTED_AT_INSTALL'),
			do_lang_tempcode('DEFAULT_GROUP'),
			//do_lang_tempcode('IS_PRIVATE_CLUB'),
			//do_lang_tempcode('GROUP_LEADER'),
			do_lang_tempcode('OPEN_MEMBERSHIP'),
			do_lang_tempcode('PROMOTION_TARGET'),
			do_lang_tempcode('SUPER_ADMIN'),
			do_lang_tempcode('ORDER'),
			do_lang_tempcode('ACTIONS'),
		),$sortables,'sort',$sortable.' '.$sort_order);

		$fields=new ocp_tempcode();

		$group_count=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)');

		require_code('form_templates');
		list($rows,$max_rows)=$this->get_entry_rows(false,$current_ordering,($group_count>300)?array('g_is_private_club'=>0):NULL);
		$changed=false;
		foreach ($rows as $row)
		{
			$new_order=post_param_integer('order_'.strval($row['id']),NULL);
			if (!is_null($new_order)) // Ah, it's been set, better save that
			{
				$GLOBALS['FORUM_DB']->query_update('f_groups',array('g_order'=>$new_order),array('id'=>$row['id']),'',1);
				$changed=true;
			}
		}
		if ($changed)
		{
			list($rows,$max_rows)=$this->get_entry_rows(true,$current_ordering,($group_count>300)?array('g_is_private_club'=>0):NULL);
		}
		foreach ($rows as $row)
		{
			$edit_link=build_url($url_map+array('id'=>$row['id']),'_SELF');

			if (($row['id']==db_get_first_id()+8) && ($GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)',array('g_is_presented_at_install'=>'1'))==0))
				$row['g_is_presented_at_install']=1;

			$fr=array(
				protect_from_escaping(ocf_get_group_link($row['id'])),
				($row['g_is_presented_at_install']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				($row['g_is_default']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				//($row['g_is_private_club']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				//is_null($row['g_group_leader'])?do_lang_tempcode('NA_EM'):make_string_tempcode($GLOBALS['FORUM_DRIVER']->get_username($row['g_group_leader'])),
				($row['g_open_membership']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
				is_null($row['g_promotion_target'])?do_lang_tempcode('NA_EM'):(make_string_tempcode(ocf_get_group_name($row['g_promotion_target']).' ('.strval($row['g_promotion_threshold']).')')),
				($row['g_is_super_admin']==1)?do_lang_tempcode('YES'):do_lang_tempcode('NO'),
			);

			$orderlist=new ocp_tempcode();
			$selected_one=false;
			$order=$row['g_order'];
			for ($i=0;$i<max(count($rows),$order);$i++)
			{
				$selected=($i===$order);
				if ($selected) $selected_one=true;
				$orderlist->attach(form_input_list_entry(strval($i),$selected,integer_format($i+1)));
			}
			if (!$selected_one)
			{
				$orderlist->attach(form_input_list_entry(strval($order),true,integer_format($order+1)));
			}
			$ordererx=protect_from_escaping(do_template('COLUMNED_TABLE_ROW_CELL_SELECT',array('LABEL'=>do_lang_tempcode('ORDER'),'NAME'=>'order_'.strval($row['id']),'LIST'=>$orderlist)));

			$fr[]=$ordererx;

			$fr[]=protect_from_escaping(hyperlink($edit_link,do_lang_tempcode('EDIT'),false,true,'#'.strval($row['id'])));

			$fields->attach(results_entry($fr,true));
		}

		$search_url=NULL;//build_url(array('page'=>'search','id'=>'ocf_clubs'),get_module_zone('search'));
		$archive_url=build_url(array('page'=>'groups'),get_module_zone('groups'));

		return array(results_table(do_lang($this->menu_label),get_param_integer('start',0),'start',get_param_integer('max',20),'max',$max_rows,$header_row,$fields,$sortables,$sortable,$sort_order,'sort',NULL,NULL,NULL,8,'gdfg43tfdgdfgdrfgd',true),true,$search_url,$archive_url);
	}

	/**
	 * Standard aed_module list function.
	 *
	 * @return tempcode		The selection list
	 */
	function nice_get_entries()
	{
		$fields=new ocp_tempcode();
		$order=(get_param_integer('keep_id_order',0)==0)?'g_promotion_threshold,id':'id';
		$group_count=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)');
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_order','g_name','g_promotion_target'),($group_count>300)?array('g_is_private_club'=>0):NULL,'ORDER BY '.$order);
		require_code('ocf_groups2');
		foreach ($rows as $row)
		{
			$num_members=ocf_get_group_members_raw_count($row['id'],true,false,true,true);

			if (is_null($row['g_promotion_target']))
			{
				$text=do_lang_tempcode('EXTENDED_GROUP_TITLE_NORMAL',get_translated_text($row['g_name'],$GLOBALS['FORUM_DB']),strval($row['id']),array(integer_format($row['g_order']+1),integer_format($num_members)));
			} else
			{
				$text=do_lang_tempcode('EXTENDED_GROUP_TITLE_RANK',get_translated_text($row['g_name'],$GLOBALS['FORUM_DB']),strval($row['id']),array(strval($row['g_promotion_target']),integer_format($row['g_order']+1),integer_format($num_members)));
			}
			$fields->attach(form_input_list_entry(strval($row['id']),false,$text));
		}

		return $fields;
	}

	/**
	 * Standard aed_module delete possibility checker.
	 *
	 * @param  ID_TEXT		The entry being potentially deleted
	 * @return boolean		Whether it may be deleted
	 */
	function may_delete_this($id)
	{
		return ((intval($id)!=db_get_first_id()+0) && (intval($id)!=db_get_first_id()+1) && (intval($id)!=db_get_first_id()+8));
	}

	/**
	 * Standard aed_module edit form filler.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return array			A triple: fields, hidden-fields, delete-fields
	 */
	function fill_in_edit_form($id)
	{
		$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('*'),array('id'=>intval($id)),'',1);
		if (!array_key_exists(0,$rows))
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}
		$myrow=$rows[0];

		$username=$GLOBALS['FORUM_DRIVER']->get_username($myrow['g_group_leader']);
		if (is_null($username)) $username='';//do_lang('UNKNOWN');

		if ((intval($id)==db_get_first_id()+8) && ($GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)',array('g_is_presented_at_install'=>'1'))==0))
			$myrow['g_is_presented_at_install']=1;

		list($fields,$hidden)=$this->get_form_fields(intval($id),get_translated_text($myrow['g_name'],$GLOBALS['FORUM_DB']),$myrow['g_is_default'],$myrow['g_is_super_admin'],$myrow['g_is_super_moderator'],$username,get_translated_text($myrow['g_title'],$GLOBALS['FORUM_DB']),$myrow['g_rank_image'],$myrow['g_promotion_target'],$myrow['g_promotion_threshold'],$myrow['g_flood_control_submit_secs'],$myrow['g_flood_control_access_secs'],$myrow['g_gift_points_base'],$myrow['g_gift_points_per_day'],$myrow['g_max_daily_upload_mb'],$myrow['g_max_attachments_per_post'],$myrow['g_max_avatar_width'],$myrow['g_max_avatar_height'],$myrow['g_max_post_length_comcode'],$myrow['g_max_sig_length_comcode'],$myrow['g_enquire_on_new_ips'],$myrow['g_is_presented_at_install'],$myrow['g_hidden'],$myrow['g_order'],$myrow['g_rank_image_pri_only'],$myrow['g_open_membership'],$myrow['g_is_private_club']);

		$default_group=get_first_default_group();

		$groups=new ocp_tempcode();
		$group_count=$GLOBALS['FORUM_DB']->query_value('f_groups','COUNT(*)');
		if (($myrow['g_is_private_club']==1) && ($group_count>300))
		{
			$delete_fields=form_input_integer(do_lang_tempcode('NEW_USERGROUP'),do_lang_tempcode('DESCRIPTION_NEW_USERGROUP'),'new_usergroup',NULL,false);
		} else
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_groups',array('id','g_name'),($group_count>300)?array('g_is_private_club'=>0):NULL);
			foreach ($rows as $row)
			{
				if (($row['id']!=db_get_first_id()) && ($row['id']!=intval($id)))
					$groups->attach(form_input_list_entry(strval($row['id']),$row['id']==($default_group),get_translated_text($row['g_name'],$GLOBALS['FORUM_DB'])));
			}
			$delete_fields=form_input_list(do_lang_tempcode('NEW_USERGROUP'),do_lang_tempcode('DESCRIPTION_NEW_USERGROUP'),'new_usergroup',$groups);
		}

		$text=$this->edit_text;
		if (addon_installed('ecommerce'))
		{
			$usergroup_subs=$GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('id','s_title'),array('s_group_id'=>intval($id)));
			if (count($usergroup_subs)!=0)
			{
				$subs=new ocp_tempcode();
				foreach ($usergroup_subs as $i=>$sub)
				{
					if ($i!=0)
						$subs->attach(do_lang_tempcode('LIST_SEP'));
					$subs->attach(hyperlink(build_url(array('page'=>'admin_ecommerce','type'=>'_ed','id'=>$sub['id']),get_module_zone('admin_ecommerce')),get_translated_text($sub['s_title'])));
				}
				require_lang('ecommerce');
				$text->attach(paragraph(do_lang_tempcode('HAS_THESE_SUBS',$subs)));
			}
		}

		return array($fields,$hidden,$delete_fields,$text);
	}

	/**
	 * Handle the "copy members from" feature.
	 *
	 * @param  GROUP			The usergroup to copy members from
	 */
	function copy_members_into($g)
	{
		if (function_exists('set_time_limit')) @set_time_limit(0);

		if (!array_key_exists('copy_members_into',$_POST))
		{
			return;
		}
		$start=0;
		do
		{
			$members=$GLOBALS['FORUM_DRIVER']->member_group_query(array_map('intval',$_POST['copy_members_into']),300,$start);
			foreach (array_keys($members) as $member_id)
			{
				ocf_add_member_to_group($member_id,$g,1);
			}

			$start+=300;
		}
		while (array_key_exists(0,$members));
	}

	/**
	 * Read in data posted by an add/edit form
	 *
	 * @return array		A triplet of integers: (group leader, promotion target, promotion threshold)
	 */
	function read_in_data()
	{
		$_group_leader=post_param('group_leader');
		if ($_group_leader!='')
		{
			$group_leader=$GLOBALS['FORUM_DRIVER']->get_member_from_username($_group_leader);
			if (is_null($group_leader)) warn_exit(do_lang_tempcode('_USER_NO_EXIST',$_group_leader));
		} else $group_leader=NULL;

		$promotion_target=post_param_integer('promotion_target',-1);
		if ($promotion_target==-1) $promotion_target=NULL;
		$promotion_threshold=post_param_integer('promotion_threshold',-1);
		if ($promotion_threshold==-1) $promotion_threshold=NULL;

		return array($group_leader,$promotion_target,$promotion_threshold);
	}

	/**
	 * Standard aed_module add actualiser.
	 *
	 * @return ID_TEXT		The entry added
	 */
	function add_actualisation()
	{
		require_code('themes2');

		list($group_leader,$promotion_target,$promotion_threshold)=$this->read_in_data();
		$rank_img=get_theme_img_code('ocf_rank_images',true,'file','theme_img_code',$GLOBALS['FORUM_DB']);
		$id=ocf_make_group(post_param('name'),post_param_integer('is_default',0),post_param_integer('is_super_admin',0),post_param_integer('is_super_moderator',0),post_param('title',''),$rank_img,$promotion_target,$promotion_threshold,$group_leader,post_param_integer('flood_control_submit_secs'),post_param_integer('flood_control_access_secs'),post_param_integer('max_daily_upload_mb'),post_param_integer('max_attachments_per_post'),post_param_integer('max_avatar_width',100),post_param_integer('max_avatar_height',100),post_param_integer('max_post_length_comcode'),post_param_integer('max_sig_length_comcode',10000),post_param_integer('gift_points_base',0),post_param_integer('gift_points_per_day',0),post_param_integer('enquire_on_new_ips',0),post_param_integer('is_presented_at_install',0),post_param_integer('hidden',0),post_param_integer('order'),post_param_integer('rank_image_pri_only',0),post_param_integer('open_membership',0),post_param_integer('is_private_club',0));
		$this->copy_members_into($id);

		$absorb=post_param_integer('absorb',-1);
		if ($absorb!=-1) ocf_group_absorb_privileges_of($id,$absorb);

		if (post_param_integer('is_private_club',0)==1)
		{
			$GLOBALS['SITE_DB']->query_delete('gsp',array('group_id'=>$id));
			$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('group_id'=>$id));
			$GLOBALS['SITE_DB']->query_delete('group_category_access',array('group_id'=>$id));
			$GLOBALS['SITE_DB']->query_delete('group_page_access',array('group_id'=>$id));
		}

		if (!is_null($group_leader))
			ocf_add_member_to_group($group_leader,$id);

		if (addon_installed('ecommerce'))
		{
			require_lang('ecommerce');
			$this->extra_donext_whatever=array(
				array('ecommerce',array('admin_ecommerce',array('type'=>'ad','group_id'=>$id),'_SELF'),do_lang_tempcode('ADD_USERGROUP_SUBSCRIPTION')),
			);
			$this->extra_donext_whatever_title=do_lang_tempcode('MODULE_TRANS_NAME_subscriptions');
		}

		return strval($id);
	}

	/**
	 * Standard aed_module edit actualiser.
	 *
	 * @param  ID_TEXT		The entry being edited
	 * @return ?tempcode		Confirm message (NULL: continue)
	 */
	function edit_actualisation($id)
	{
		require_code('themes2');

		list($group_leader,$promotion_target,$promotion_threshold)=$this->read_in_data();
		if ((!is_null($group_leader)) && (post_param_integer('confirm',0)==0) && (!in_array(intval($id),$GLOBALS['FORUM_DRIVER']->get_members_groups($group_leader))))
		{
			require_code('templates_confirm_screen');
			return confirm_screen(get_screen_title('EDIT_GROUP'),paragraph(do_lang_tempcode('MAKE_MEMBER_GROUP_LEADER',post_param('group_leader'))),'__ed','_ed',array('confirm'=>1));
		}

		$was_club=($GLOBALS['FORUM_DB']->query_value('f_groups','g_is_private_club',array('id'=>intval($id)))==1);

		$rank_img=get_theme_img_code('ocf_rank_images',true,'file','theme_img_code',$GLOBALS['FORUM_DB']);
		ocf_edit_group(intval($id),post_param('name'),post_param_integer('is_default',0),post_param_integer('is_super_admin',0),post_param_integer('is_super_moderator',0),post_param('title'),$rank_img,$promotion_target,$promotion_threshold,$group_leader,post_param_integer('flood_control_submit_secs'),post_param_integer('flood_control_access_secs'),post_param_integer('max_daily_upload_mb'),post_param_integer('max_attachments_per_post'),post_param_integer('max_avatar_width',100),post_param_integer('max_avatar_height',100),post_param_integer('max_post_length_comcode'),post_param_integer('max_sig_length_comcode',10000),post_param_integer('gift_points_base',0),post_param_integer('gift_points_per_day',0),post_param_integer('enquire_on_new_ips',0),post_param_integer('is_presented_at_install',0),post_param_integer('hidden',0),post_param_integer('order'),post_param_integer('rank_image_pri_only',0),post_param_integer('open_membership',0),post_param_integer('is_private_club',0));

		if (addon_installed('ecommerce'))
		{
			require_lang('ecommerce');
			$this->extra_donext_whatever=array(
				array('ecommerce',array('admin_ecommerce',array('type'=>'ad','group_id'=>$id),'_SELF'),do_lang_tempcode('ADD_USERGROUP_SUBSCRIPTION')),
			);
			$this->extra_donext_whatever_title=do_lang_tempcode('MODULE_TRANS_NAME_subscriptions');
		}

		if ((!is_null($group_leader)) && (!in_array(intval($id),$GLOBALS['FORUM_DRIVER']->get_members_groups($group_leader))))
			ocf_add_member_to_group($group_leader,intval($id));

		$absorb=post_param_integer('absorb',-1);
		if ($absorb!=-1) ocf_group_absorb_privileges_of(intval($id),$absorb);

		if ((post_param_integer('is_private_club',0)==1) && (!$was_club))
		{
			$GLOBALS['SITE_DB']->query_delete('gsp',array('group_id'=>intval($id)));
			$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('group_id'=>intval($id)));
			$GLOBALS['SITE_DB']->query_delete('group_category_access',array('group_id'=>intval($id)));
			$GLOBALS['SITE_DB']->query_delete('group_page_access',array('group_id'=>intval($id)));
		}
		return NULL;
	}

	/**
	 * Standard aed_module delete actualiser.
	 *
	 * @param  ID_TEXT		The entry being deleted
	 */
	function delete_actualisation($id)
	{
		ocf_delete_group(intval($id),post_param_integer('new_usergroup'));
	}
}


