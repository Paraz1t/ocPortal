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
 * @package		core
 */

/**
 * Module page class.
 */
class Module_admin_version
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
		$info['version']=17;
		$info['locked']=true;
		$info['update_require_upgrade']=1;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('url_id_monikers');
		$GLOBALS['SITE_DB']->drop_table_if_exists('cache');
		$GLOBALS['SITE_DB']->drop_table_if_exists('cache_on');
		$GLOBALS['SITE_DB']->drop_table_if_exists('captchas');
		$GLOBALS['SITE_DB']->drop_table_if_exists('rating');
		$GLOBALS['SITE_DB']->drop_table_if_exists('member_tracking');
		$GLOBALS['SITE_DB']->drop_table_if_exists('trackbacks');
		$GLOBALS['SITE_DB']->drop_table_if_exists('menu_items');
		$GLOBALS['SITE_DB']->drop_table_if_exists('long_values');
		$GLOBALS['SITE_DB']->drop_table_if_exists('tutorial_links');
		$GLOBALS['SITE_DB']->drop_table_if_exists('translate_history');
		$GLOBALS['SITE_DB']->drop_table_if_exists('edit_pings');
		$GLOBALS['SITE_DB']->drop_table_if_exists('validated_once');
		$GLOBALS['SITE_DB']->drop_table_if_exists('member_privileges');
		$GLOBALS['SITE_DB']->drop_table_if_exists('member_zone_access');
		$GLOBALS['SITE_DB']->drop_table_if_exists('member_page_access');
		$GLOBALS['SITE_DB']->drop_table_if_exists('member_category_access');
		$GLOBALS['SITE_DB']->drop_table_if_exists('tracking');
		$GLOBALS['SITE_DB']->drop_table_if_exists('autosave');
		$GLOBALS['SITE_DB']->drop_table_if_exists('messages_to_render');
		$GLOBALS['SITE_DB']->drop_table_if_exists('url_title_cache');
		$GLOBALS['SITE_DB']->drop_table_if_exists('review_supplement');
		$GLOBALS['SITE_DB']->drop_table_if_exists('logged_mail_messages');
		$GLOBALS['SITE_DB']->drop_table_if_exists('link_tracker');
		$GLOBALS['SITE_DB']->drop_table_if_exists('incoming_uploads');
		$GLOBALS['SITE_DB']->drop_table_if_exists('f_group_member_timeouts');
		$GLOBALS['SITE_DB']->drop_table_if_exists('temp_block_permissions');
		$GLOBALS['SITE_DB']->drop_table_if_exists('cron_caching_requests');
		$GLOBALS['SITE_DB']->drop_table_if_exists('notifications_enabled');
		$GLOBALS['SITE_DB']->drop_table_if_exists('digestives_tin');
		$GLOBALS['SITE_DB']->drop_table_if_exists('digestives_consumed');
		$GLOBALS['SITE_DB']->drop_table_if_exists('alternative_ids');
		$GLOBALS['SITE_DB']->drop_table_if_exists('content_privacy');
		$GLOBALS['SITE_DB']->drop_table_if_exists('content_primary__members');
		$GLOBALS['SITE_DB']->drop_table_if_exists('task_queue');

		delete_privilege('edit_meta_fields');
		delete_privilege('view_private_content');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		// A lot of "peripheral architectural" tables are defined here. Central ones are defined in the installer -- as they need to be installed before any module.
		// This is always the first module to be installed.

		// A lot of core upgrade is also here. When absolutely necessary it is put in upgrade.php.

		if (($upgrade_from<10) || (is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->create_table('url_id_monikers',array(
				'id'=>'*AUTO',
				'm_resource_page'=>'ID_TEXT',
				'm_resource_type'=>'ID_TEXT',
				'm_resource_id'=>'ID_TEXT',
				'm_moniker'=>'SHORT_TEXT',
				'm_deprecated'=>'BINARY',
				'm_manually_chosen'=>'BINARY',
			));
			$GLOBALS['SITE_DB']->create_index('url_id_monikers','uim_page_link',array('m_resource_page','m_resource_type','m_resource_id'));
			$GLOBALS['SITE_DB']->create_index('url_id_monikers','uim_moniker',array('m_moniker'));

			$GLOBALS['SITE_DB']->create_table('review_supplement',array(
				'r_post_id'=>'*AUTO_LINK',
				'r_rating_type'=>'*ID_TEXT',
				'r_rating'=>'SHORT_INTEGER',
				'r_topic_id'=>'AUTO_LINK',
				'r_rating_for_id'=>'ID_TEXT',
				'r_rating_for_type'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('review_supplement','rating_for_id',array('r_rating_for_id'));

			$GLOBALS['SITE_DB']->create_table('logged_mail_messages',array(
				'id'=>'*AUTO',
				'm_subject'=>'LONG_TEXT', // Whilst data for a subject would be tied to SHORT_TEXT, a language string could bump it up higher
				'm_message'=>'LONG_TEXT',
				'm_to_email'=>'LONG_TEXT',
				'm_extra_cc_addresses'=>'LONG_TEXT',
				'm_extra_bcc_addresses'=>'LONG_TEXT',
				'm_to_name'=>'LONG_TEXT',
				'm_from_email'=>'SHORT_TEXT',
				'm_from_name'=>'SHORT_TEXT',
				'm_priority'=>'SHORT_INTEGER',
				'm_attachments'=>'LONG_TEXT',
				'm_no_cc'=>'BINARY',
				'm_as'=>'MEMBER',
				'm_as_admin'=>'BINARY',
				'm_in_html'=>'BINARY',
				'm_date_and_time'=>'TIME',
				'm_member_id'=>'MEMBER',
				'm_url'=>'LONG_TEXT',
				'm_queued'=>'BINARY',
				'm_template'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('logged_mail_messages','recentmessages',array('m_date_and_time'));
			$GLOBALS['SITE_DB']->create_index('logged_mail_messages','queued',array('m_queued'));

			$GLOBALS['SITE_DB']->create_table('link_tracker',array(
				'id'=>'*AUTO',
				'c_date_and_time'=>'TIME',
				'c_member_id'=>'MEMBER',
				'c_ip_address'=>'IP',
				'c_url'=>'URLPATH',
			));
			$GLOBALS['SITE_DB']->create_index('link_tracker','c_url',array('c_url'));

			$GLOBALS['SITE_DB']->create_table('incoming_uploads',array(
				'id'=>'*AUTO',
				'i_submitter'=>'MEMBER',
				'i_date_and_time'=>'TIME',
				'i_orig_filename'=>'URLPATH',
				'i_save_url'=>'URLPATH'
			));
		}

		if (($upgrade_from<11) && (!is_null($upgrade_from)))
		{
			$GLOBALS['SITE_DB']->query_update('comcode_pages',array('p_submitter'=>2),array('p_submitter'=>$GLOBALS['FORUM_DRIVER']->get_guest_id()));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<12))
		{
			$GLOBALS['SITE_DB']->drop_table_if_exists('cache');
			$GLOBALS['SITE_DB']->create_table('cache',array(
				'cached_for'=>'*ID_TEXT',
				'identifier'=>'*MINIID_TEXT',
				'the_value'=>'LONG_TEXT',
				'date_and_time'=>'TIME',
				'the_theme'=>'*ID_TEXT',
				'lang'=>'*LANGUAGE_NAME',
				'dependencies'=>'LONG_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('cache','cached_ford',array('date_and_time'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_fore',array('cached_for'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_fore2',array('cached_for','identifier'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forf',array('lang'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forg',array('identifier'));
			$GLOBALS['SITE_DB']->create_index('cache','cached_forh',array('the_theme'));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<13))
		{
			if (!$GLOBALS['SITE_DB']->table_exists('f_group_member_timeouts'))
			{
				$GLOBALS['SITE_DB']->create_table('f_group_member_timeouts',array(
					'member_id'=>'*MEMBER',
					'group_id'=>'*GROUP',
					'timeout'=>'TIME',
				));
			}
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<13))
		{
			if (substr(get_db_type(),0,5)=='mysql')
			{
				$GLOBALS['SITE_DB']->create_index('translate','equiv_lang',array('text_original(4)'));
				$GLOBALS['SITE_DB']->create_index('translate','decache',array('text_parsed(2)'));
			}
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<14))
		{
			$GLOBALS['SITE_DB']->drop_table_if_exists('tracking');
			$GLOBALS['SITE_DB']->add_table_field('logged_mail_messages','m_template','ID_TEXT');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from==14))
		{
			$GLOBALS['SITE_DB']->alter_table_field('digestives_tin','d_from_member_id','?USER');
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<14))
		{
			$GLOBALS['SITE_DB']->create_table('temp_block_permissions',array(
				'id'=>'*AUTO',
				'p_session_id'=>'AUTO_LINK',
				'p_block_constraints'=>'LONG_TEXT',
				'p_time'=>'TIME',
			));

			$GLOBALS['SITE_DB']->create_table('cron_caching_requests',array(
				'id'=>'*AUTO',
				'c_codename'=>'ID_TEXT',
				'c_map'=>'LONG_TEXT',
				'c_timezone'=>'ID_TEXT',
				'c_is_bot'=>'BINARY',
				'c_store_as_tempcode'=>'BINARY',
				'c_lang'=>'LANGUAGE_NAME',
				'c_theme'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_compound',array('c_codename','c_theme','c_lang','c_timezone'));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_is_bot',array('c_is_bot'));
			$GLOBALS['SITE_DB']->create_index('cron_caching_requests','c_store_as_tempcode',array('c_store_as_tempcode'));

			$GLOBALS['SITE_DB']->create_table('notifications_enabled',array(
				'id'=>'*AUTO',
				'l_member_id'=>'MEMBER',
				'l_notification_code'=>'ID_TEXT',
				'l_code_category'=>'SHORT_TEXT',
				'l_setting'=>'INTEGER',
			));
			$GLOBALS['SITE_DB']->create_index('notifications_enabled','l_member_id',array('l_member_id','l_notification_code'));
			$GLOBALS['SITE_DB']->create_index('notifications_enabled','l_code_category',array('l_code_category'));

			$GLOBALS['SITE_DB']->create_table('digestives_tin',array( // Notifications queued up ready for the regular digest email
				'id'=>'*AUTO',
				'd_subject'=>'LONG_TEXT',
				'd_message'=>'LONG_TRANS',
				'd_from_member_id'=>'?MEMBER',
				'd_to_member_id'=>'MEMBER',
				'd_priority'=>'SHORT_INTEGER',
				'd_no_cc'=>'BINARY',
				'd_date_and_time'=>'TIME',
				'd_notification_code'=>'ID_TEXT',
				'd_code_category'=>'SHORT_TEXT',
				'd_frequency'=>'INTEGER', // e.g. A_DAILY_EMAIL_DIGEST
				'd_read'=>'BINARY',
			));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_date_and_time',array('d_date_and_time'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_frequency',array('d_frequency'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_to_member_id',array('d_to_member_id'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_read',array('d_read'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','unread',array('d_to_member_id','d_read'));
			$GLOBALS['SITE_DB']->create_table('digestives_consumed',array(
				'c_member_id'=>'*MEMBER',
				'c_frequency'=>'*INTEGER', // e.g. A_DAILY_EMAIL_DIGEST
				'c_time'=>'TIME',
			));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from==15))
		{
			$GLOBALS['SITE_DB']->delete_table_field('cron_caching_requests','c_interlock');
			$GLOBALS['SITE_DB']->delete_table_field('cron_caching_requests','c_in_panel');
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<17))
		{
			$GLOBALS['SITE_DB']->rename_table('security_images','captchas');

			$GLOBALS['SITE_DB']->alter_table_field('cache','langs_required','LONG_TEXT','dependencies');

			$GLOBALS['SITE_DB']->add_table_field('url_id_monikers','m_manually_chosen','BINARY');

			$GLOBALS['SITE_DB']->change_primary_key('member_privileges',array('member_id','privilege','the_page','module_the_name','category_name'));
			$GLOBALS['SITE_DB']->change_primary_key('member_zone_access',array('member_id','zone_name'));
			$GLOBALS['SITE_DB']->change_primary_key('member_page_access',array('member_id','page_name','zone_name'));
			$GLOBALS['SITE_DB']->change_primary_key('member_category_access',array('member_id','module_the_name','category_name'));

			$GLOBALS['SITE_DB']->alter_table_field('member_privileges','active_until','?TIME');
			$GLOBALS['SITE_DB']->alter_table_field('member_zone_access','active_until','?TIME');
			$GLOBALS['SITE_DB']->alter_table_field('member_page_access','active_until','?TIME');
			$GLOBALS['SITE_DB']->alter_table_field('member_category_access','active_until','?TIME');

			$GLOBALS['SITE_DB']->promote_text_field_to_comcode('digestives_tin','d_message','id',4);
			$GLOBALS['SITE_DB']->add_table_field('digestives_tin','d_read','BINARY');
			$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'notifications_enabled SET l_setting=l_setting+'.strval(A_WEB_NOTIFICATION).' WHERE l_setting<>0');
			$GLOBALS['SITE_DB']->create_index('digestives_tin','d_read',array('d_read'));
			$GLOBALS['SITE_DB']->create_index('digestives_tin','unread',array('d_to_member_id','d_read'));
		}

		if ((is_null($upgrade_from)) || ($upgrade_from<17))
		{
			$GLOBALS['SITE_DB']->create_table('alternative_ids',array( // Needs to be first, as install_create_custom_field needs it
				'resource_type'=>'*ID_TEXT',
				'resource_id'=>'*ID_TEXT',
				'resource_moniker'=>'ID_TEXT',
				'resource_label'=>'SHORT_TEXT',
				'resource_guid'=>'ID_TEXT',
				'resource_resourcefs_hook'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('alternative_ids','resource_guid',array('resource_guid'));
			$GLOBALS['SITE_DB']->create_index('alternative_ids','resource_label',array('resource_label'/*,'resource_type'key would be too long*/));
			$GLOBALS['SITE_DB']->create_index('alternative_ids','resource_moniker',array('resource_moniker','resource_type'));
			//$GLOBALS['SITE_DB']->create_index('alternative_ids','resource_label_uniqueness',array('resource_label','resource_resourcefs_hook'));key would be too long
			$GLOBALS['SITE_DB']->create_index('alternative_ids','resource_moniker_uniq',array('resource_moniker','resource_resourcefs_hook'));

			add_privilege('SUBMISSION','edit_meta_fields');
			$GLOBALS['FORUM_DRIVER']->install_create_custom_field('smart_topic_notification',20,1,0,1,0,'','tick');

			$GLOBALS['SITE_DB']->create_table('content_privacy',array(
				'content_type'=>'*ID_TEXT',
				'content_id'=>'*ID_TEXT',
				'guest_view'=>'BINARY',
				'member_view'=>'BINARY',
				'friend_view'=>'BINARY'
			));
			$GLOBALS['SITE_DB']->create_table('content_primary__members',array(
				'content_type'=>'*ID_TEXT',
				'content_id'=>'*ID_TEXT',
				'member_id'=>'*MEMBER',
			));
			add_privilege('SUBMISSION','view_private_content',false,true);

			$GLOBALS['SITE_DB']->create_table('task_queue',array(
				'id'=>'*AUTO',
				't_title'=>'SHORT_TEXT',
				't_hook'=>'ID_TEXT',
				't_args'=>'LONG_TEXT',
				't_member_id'=>'MEMBER',
				't_secure_ref'=>'ID_TEXT', // Used like a temporary password to initiate the task
				't_send_notification'=>'BINARY',
				't_locked'=>'BINARY',
			));

			require_code('users_active_actions');
			$admin_user=get_first_admin_user();

			$GLOBALS['SITE_DB']->query_insert('comcode_pages',array(
				'the_zone'=>'site',
				'the_page'=>'userguide_comcode',
				'p_parent_page'=>'help',
				'p_validated'=>1,
				'p_edit_date'=>NULL,
				'p_add_date'=>time(),
				'p_submitter'=>$admin_user,
				'p_show_as_edit'=>0
			));

			$GLOBALS['SITE_DB']->query_insert('comcode_pages',array(
				'the_zone'=>'site',
				'the_page'=>'keymap',
				'p_parent_page'=>'help',
				'p_validated'=>1,
				'p_edit_date'=>NULL,
				'p_add_date'=>time(),
				'p_submitter'=>$admin_user,
				'p_show_as_edit'=>0
			));
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<17))
		{
			rename_config_option('ocp_show_conceded_mode_link','show_conceded_mode_link');
			rename_config_option('ocp_show_personal_adminzone_link','show_personal_adminzone_link');
			rename_config_option('ocp_show_personal_last_visit','show_personal_last_visit');
			rename_config_option('ocp_show_personal_sub_links','show_personal_sub_links');
			rename_config_option('ocp_show_personal_usergroup','show_personal_usergroup');
			rename_config_option('ocp_show_staff_page_actions','show_staff_page_actions');
			rename_config_option('ocp_show_su','show_su');
			rename_config_option('ocp_show_avatar','show_avatar');

			$GLOBALS['SITE_DB']->add_table_field('logged_mail_messages','m_extra_cc_addresses','LONG_TEXT',serialize(array()));
			$GLOBALS['SITE_DB']->add_table_field('logged_mail_messages','m_extra_bcc_addresses','LONG_TEXT',serialize(array()));

			$GLOBALS['SITE_DB']->query_delete('url_title_cache');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_meta_title','LONG_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_keywords','LONG_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_description','LONG_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_image_url','URLPATH');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_mime_type','ID_TEXT');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_json_discovery','URLPATH');
			$GLOBALS['SITE_DB']->add_table_field('url_title_cache','t_xml_discovery','URLPATH');

			$GLOBALS['SITE_DB']->add_table_field('menu_items','i_include_sitemap','SHORT_INTEGER',0);

			$GLOBALS['SITE_DB']->delete_table_field('zones','zone_displayed_in_menu');
			$GLOBALS['SITE_DB']->delete_table_field('zones','zone_wide');
		}

		if (is_null($upgrade_from)) // These are only for fresh installs
		{
			set_value('version',float_to_raw_string(ocp_version_number()));
			set_value('ocf_version',float_to_raw_string(ocp_version_number()));

			$GLOBALS['SITE_DB']->create_table('menu_items',array(
				'id'=>'*AUTO',
				'i_menu'=>'ID_TEXT', // Foreign key in the future - currently it just binds together
				'i_order'=>'INTEGER',
				'i_parent'=>'?AUTO_LINK',
				'i_caption'=>'SHORT_TRANS', // Comcode
				'i_caption_long'=>'SHORT_TRANS', // Comcode
				'i_url'=>'SHORT_TEXT', // Supports page-links
				'i_check_permissions'=>'BINARY',
				'i_expanded'=>'BINARY',
				'i_new_window'=>'BINARY',
				'i_include_sitemap'=>'SHORT_INTEGER',
				'i_page_only'=>'ID_TEXT', // Only show up if the page is this (allows page specific menus)
				'i_theme_img_code'=>'ID_TEXT',
			));
			$GLOBALS['SITE_DB']->create_index('menu_items','menu_extraction',array('i_menu'));

			$GLOBALS['SITE_DB']->create_table('trackbacks',array(
				'id'=>'*AUTO',
				'trackback_for_type'=>'ID_TEXT',
				'trackback_for_id'=>'ID_TEXT',
				'trackback_ip'=>'IP',
				'trackback_time'=>'TIME',
				'trackback_url'=>'SHORT_TEXT',
				'trackback_title'=>'SHORT_TEXT',
				'trackback_excerpt'=>'LONG_TEXT',
				'trackback_name'=>'SHORT_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_for_type',array('trackback_for_type'));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_for_id',array('trackback_for_id'));
			$GLOBALS['SITE_DB']->create_index('trackbacks','trackback_time',array('trackback_time'));

			$GLOBALS['SITE_DB']->create_table('captchas',array(
				'si_session_id'=>'*INTEGER',
				'si_time'=>'TIME',
				'si_code'=>'INTEGER'
			));
			$GLOBALS['SITE_DB']->create_index('captchas','si_time',array('si_time'));

			$GLOBALS['SITE_DB']->create_table('member_tracking',array(
				'mt_member_id'=>'*MEMBER',
				'mt_cache_username'=>'ID_TEXT',
				'mt_time'=>'*TIME',
				'mt_page'=>'*ID_TEXT',
				'mt_type'=>'*ID_TEXT',
				'mt_id'=>'*ID_TEXT'
			));
			$GLOBALS['SITE_DB']->create_index('member_tracking','mt_page',array('mt_page'));
			$GLOBALS['SITE_DB']->create_index('member_tracking','mt_id',array('mt_page','mt_id','mt_type'));
			$GLOBALS['SITE_DB']->create_index('member_tracking','mt_time',array('mt_time'));

			$GLOBALS['SITE_DB']->create_table('cache_on',array(
				'cached_for'=>'*ID_TEXT',
				'cache_on'=>'LONG_TEXT',
				'cache_ttl'=>'INTEGER',
			));

			$GLOBALS['SITE_DB']->create_table('validated_once',array(
				'hash'=>'*MD5'
			));

			$GLOBALS['SITE_DB']->create_table('edit_pings',array(
				'id'=>'*AUTO',
				'the_page'=>'ID_TEXT',
				'the_type'=>'ID_TEXT',
				'the_id'=>'ID_TEXT',
				'the_time'=>'TIME',
				'the_member'=>'MEMBER'
			));
			$GLOBALS['SITE_DB']->create_index('edit_pings','edit_pings_on',array('the_page','the_type','the_id'));

			$GLOBALS['SITE_DB']->create_table('translate_history',array(
				'id'=>'*AUTO',
				'lang_id'=>'AUTO_LINK',
				'language'=>'*LANGUAGE_NAME',
				'text_original'=>'LONG_TEXT',
				'broken'=>'BINARY',
				'action_member'=>'MEMBER',
				'action_time'=>'TIME'
			));

			$GLOBALS['SITE_DB']->create_table('long_values',array(
				'the_name'=>'*ID_TEXT',
				'the_value'=>'LONG_TEXT',
				'date_and_time'=>'TIME',
			));
			set_long_value('call_home',strval(post_param_integer('advertise_on',0))); // Relayed from installer

			$GLOBALS['SITE_DB']->create_table('tutorial_links',array(
				'the_name'=>'*ID_TEXT',
				'the_value'=>'LONG_TEXT',
			));

			$GLOBALS['SITE_DB']->create_table('member_privileges',array(
				'member_id'=>'*INTEGER',
				'privilege'=>'*ID_TEXT',
				'the_page'=>'*ID_TEXT',
				'module_the_name'=>'*ID_TEXT',
				'category_name'=>'*ID_TEXT',
				'the_value'=>'BINARY',
				'active_until'=>'?TIME',
			));
			$GLOBALS['SITE_DB']->create_index('member_privileges','member_privileges_name',array('privilege','the_page','module_the_name','category_name'));
			$GLOBALS['SITE_DB']->create_index('member_privileges','member_privileges_member',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_zone_access',array(
				'zone_name'=>'*ID_TEXT',
				'member_id'=>'*MEMBER',
				'active_until'=>'?TIME',
			));
			$GLOBALS['SITE_DB']->create_index('member_zone_access','mzazone_name',array('zone_name'));
			$GLOBALS['SITE_DB']->create_index('member_zone_access','mzamember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_page_access',array(
				'page_name'=>'*ID_TEXT',
				'zone_name'=>'*ID_TEXT',
				'member_id'=>'*MEMBER',
				'active_until'=>'?TIME',
			));
			$GLOBALS['SITE_DB']->create_index('member_page_access','mzaname',array('page_name','zone_name'));
			$GLOBALS['SITE_DB']->create_index('member_page_access','mzamember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('member_category_access',array(
				'module_the_name'=>'*ID_TEXT',
				'category_name'=>'*ID_TEXT',
				'member_id'=>'*MEMBER',
				'active_until'=>'?TIME',
			));
			$GLOBALS['SITE_DB']->create_index('member_category_access','mcaname',array('module_the_name','category_name'));
			$GLOBALS['SITE_DB']->create_index('member_category_access','mcamember_id',array('member_id'));

			$GLOBALS['SITE_DB']->create_table('autosave',array(
				'id'=>'*AUTO',
				'a_member_id'=>'MEMBER',
				'a_key'=>'LONG_TEXT',
				'a_value'=>'LONG_TEXT',
				'a_time'=>'TIME',
			));
			$GLOBALS['SITE_DB']->create_index('autosave','myautosaves',array('a_member_id'));

			$GLOBALS['SITE_DB']->create_table('messages_to_render',array(
				'id'=>'*AUTO',
				'r_session_id'=>'AUTO_LINK',
				'r_message'=>'LONG_TEXT',
				'r_type'=>'ID_TEXT',
				'r_time'=>'TIME',
			));
			$GLOBALS['SITE_DB']->create_index('messages_to_render','forsession',array('r_session_id'));

			$GLOBALS['SITE_DB']->create_table('url_title_cache',array(
				'id'=>'*AUTO',
				't_url'=>'URLPATH',
				't_title'=>'SHORT_TEXT',
				't_meta_title'=>'LONG_TEXT',
				't_keywords'=>'LONG_TEXT',
				't_description'=>'LONG_TEXT',
				't_image_url'=>'URLPATH',
				't_mime_type'=>'ID_TEXT',
				// oEmbed...
				't_json_discovery'=>'URLPATH',
				't_xml_discovery'=>'URLPATH',
			));
			$GLOBALS['SITE_DB']->create_index('url_title_cache','t_url',array('t_url'));

			$GLOBALS['SITE_DB']->create_table('rating',array(
				'id'=>'*AUTO',
				'rating_for_type'=>'ID_TEXT',
				'rating_for_id'=>'ID_TEXT',
				'rating_member'=>'MEMBER',
				'rating_ip'=>'IP',
				'rating_time'=>'TIME',
				'rating'=>'SHORT_INTEGER'
			));
			$GLOBALS['SITE_DB']->create_index('rating','alt_key',array('rating_for_type','rating_for_id'));
			$GLOBALS['SITE_DB']->create_index('rating','rating_for_id',array('rating_for_id'));
		}
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		// This used to be a real module, before ocPortal was free
		return new ocp_tempcode();
	}

}


