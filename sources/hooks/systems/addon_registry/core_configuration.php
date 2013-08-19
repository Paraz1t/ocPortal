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
 * @package		core_configuration
 */

class Hook_addon_registry_core_configuration
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Set configuration options.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources/hooks/systems/config/max_moniker_length.php',
			'sources/hooks/systems/config/enable_seo_fields.php',
			'sources/hooks/systems/config/enable_staff_notes.php',
			'sources/hooks/systems/config/filetype_icons.php',
			'sources/hooks/systems/config/force_local_temp_dir.php',
			'sources/hooks/systems/config/general_safety_listing_limit.php',
			'sources/hooks/systems/config/hack_ban_threshold.php',
			'sources/hooks/systems/config/honeypot_phrase.php',
			'sources/hooks/systems/config/honeypot_url.php',
			'sources/hooks/systems/config/implied_spammer_confidence.php',
			'sources/hooks/systems/config/edit_under.php',
			'sources/hooks/systems/config/enable_animations.php',
			'sources/hooks/systems/config/breadcrumb_crop_length.php',
			'sources/hooks/systems/config/brute_force_instant_ban.php',
			'sources/hooks/systems/config/brute_force_login_minutes.php',
			'sources/hooks/systems/config/brute_force_threshold.php',
			'sources/hooks/systems/config/call_home.php',
			'sources/hooks/systems/config/cleanup_files.php',
			'sources/hooks/systems/config/jpeg_quality.php',
			'sources/hooks/systems/config/mail_queue.php',
			'sources/hooks/systems/config/mail_queue_debug.php',
			'sources/hooks/systems/config/modal_user.php',
			'sources/hooks/systems/config/password_cookies.php',
			'sources/hooks/systems/config/proxy.php',
			'sources/hooks/systems/config/proxy_password.php',
			'sources/hooks/systems/config/proxy_port.php',
			'sources/hooks/systems/config/proxy_user.php',
			'sources/hooks/systems/config/session_prudence.php',
			'sources/hooks/systems/config/tornevall_api_password.php',
			'sources/hooks/systems/config/tornevall_api_username.php',
			'sources/hooks/systems/config/use_true_from.php',
			'sources/hooks/systems/config/vote_member_ip_restrict.php',
			'sources/hooks/systems/config/spam_approval_threshold.php',
			'sources/hooks/systems/config/spam_ban_threshold.php',
			'sources/hooks/systems/config/spam_blackhole_detection.php',
			'sources/hooks/systems/config/spam_block_lists.php',
			'sources/hooks/systems/config/spam_block_threshold.php',
			'sources/hooks/systems/config/spam_cache_time.php',
			'sources/hooks/systems/config/spam_check_exclusions.php',
			'sources/hooks/systems/config/spam_check_level.php',
			'sources/hooks/systems/config/spam_check_usernames.php',
			'sources/hooks/systems/config/spam_stale_threshold.php',
			'sources/hooks/systems/config/stopforumspam_api_key.php',
			'sources/hooks/systems/config/cdn.php',
			'sources/hooks/systems/config/infinite_scrolling.php',
			'sources/hooks/systems/config/check_broken_urls.php',
			'sources/hooks/systems/config/google_analytics.php',
			'sources/hooks/systems/config/show_personal_sub_links.php',
			'sources/hooks/systems/config/show_content_tagging.php',
			'sources/hooks/systems/config/show_content_tagging_inline.php',
			'sources/hooks/systems/config/show_screen_actions.php',
			'sources/hooks/systems/config/allow_audio_videos.php',
			'sources/hooks/systems/config/allow_ext_images.php',
			'sources/hooks/systems/config/allowed_post_submitters.php',
			'sources/hooks/systems/config/anti_leech.php',
			'sources/hooks/systems/config/auto_submit_sitemap.php',
			'sources/hooks/systems/config/automatic_meta_extraction.php',
			'sources/hooks/systems/config/bcc.php',
			'sources/hooks/systems/config/bottom_show_admin_menu.php',
			'sources/hooks/systems/config/bottom_show_feedback_link.php',
			'sources/hooks/systems/config/bottom_show_rules_link.php',
			'sources/hooks/systems/config/bottom_show_privacy_link.php',
			'sources/hooks/systems/config/bottom_show_sitemap_button.php',
			'sources/hooks/systems/config/bottom_show_top_button.php',
			'sources/hooks/systems/config/cc_address.php',
			'sources/hooks/systems/config/closed.php',
			'sources/hooks/systems/config/comment_text.php',
			'sources/hooks/systems/config/comments_forum_name.php',
			'sources/hooks/systems/config/copyright.php',
			'sources/hooks/systems/config/deeper_admin_breadcrumbs.php',
			'sources/hooks/systems/config/description.php',
			'sources/hooks/systems/config/detect_lang_browser.php',
			'sources/hooks/systems/config/detect_lang_forum.php',
			'sources/hooks/systems/config/display_php_errors.php',
			'sources/hooks/systems/config/eager_wysiwyg.php',
			'sources/hooks/systems/config/enable_keyword_density_check.php',
			'sources/hooks/systems/config/enable_markup_validation.php',
			'sources/hooks/systems/config/enable_previews.php',
			'sources/hooks/systems/config/enable_spell_check.php',
			'sources/hooks/systems/config/enveloper_override.php',
			'sources/hooks/systems/config/force_meta_refresh.php',
			'sources/hooks/systems/config/forum_in_portal.php',
			'sources/hooks/systems/config/forum_show_personal_stats_posts.php',
			'sources/hooks/systems/config/forum_show_personal_stats_topics.php',
			'sources/hooks/systems/config/global_donext_icons.php',
			'sources/hooks/systems/config/gzip_output.php',
			'sources/hooks/systems/config/has_low_memory_limit.php',
			'sources/hooks/systems/config/url_scheme.php',
			'sources/hooks/systems/config/ip_forwarding.php',
			'sources/hooks/systems/config/ip_strict_for_sessions.php',
			'sources/hooks/systems/config/is_on_emoticon_choosers.php',
			'sources/hooks/systems/config/is_on_gd.php',
			'sources/hooks/systems/config/is_on_preview_validation.php',
			'sources/hooks/systems/config/is_on_strong_forum_tie.php',
			'sources/hooks/systems/config/java_ftp_host.php',
			'sources/hooks/systems/config/java_ftp_path.php',
			'sources/hooks/systems/config/java_password.php',
			'sources/hooks/systems/config/java_upload.php',
			'sources/hooks/systems/config/java_username.php',
			'sources/hooks/systems/config/keywords.php',
			'sources/hooks/systems/config/log_php_errors.php',
			'sources/hooks/systems/config/low_space_check.php',
			'sources/hooks/systems/config/main_forum_name.php',
			'sources/hooks/systems/config/max_download_size.php',
			'sources/hooks/systems/config/maximum_users.php',
			'sources/hooks/systems/config/stats_when_closed.php',
			'sources/hooks/systems/config/ocf_show_profile_link.php',
			'sources/hooks/systems/config/show_avatar.php',
			'sources/hooks/systems/config/show_conceded_mode_link.php',
			'sources/hooks/systems/config/show_personal_adminzone_link.php',
			'sources/hooks/systems/config/show_personal_last_visit.php',
			'sources/hooks/systems/config/show_personal_usergroup.php',
			'sources/hooks/systems/config/show_staff_page_actions.php',
			'sources/hooks/systems/config/show_su.php',
			'sources/hooks/systems/config/root_zone_login_theme.php',
			'sources/hooks/systems/config/send_error_emails_ocproducts.php',
			'sources/hooks/systems/config/session_expiry_time.php',
			'sources/hooks/systems/config/show_docs.php',
			'sources/hooks/systems/config/show_inline_stats.php',
			'sources/hooks/systems/config/show_post_validation.php',
			'sources/hooks/systems/config/simplified_donext.php',
			'sources/hooks/systems/config/site_closed.php',
			'sources/hooks/systems/config/site_name.php',
			'sources/hooks/systems/config/site_scope.php',
			'sources/hooks/systems/config/smtp_from_address.php',
			'sources/hooks/systems/config/smtp_sockets_host.php',
			'sources/hooks/systems/config/smtp_sockets_password.php',
			'sources/hooks/systems/config/smtp_sockets_port.php',
			'sources/hooks/systems/config/smtp_sockets_use.php',
			'sources/hooks/systems/config/smtp_sockets_username.php',
			'sources/hooks/systems/config/ssw.php',
			'sources/hooks/systems/config/staff_address.php',
			'sources/hooks/systems/config/thumb_width.php',
			'sources/hooks/systems/config/unzip_cmd.php',
			'sources/hooks/systems/config/unzip_dir.php',
			'sources/hooks/systems/config/use_contextual_dates.php',
			'sources/hooks/systems/config/user_postsize_errors.php',
			'sources/hooks/systems/config/users_online_time.php',
			'sources/hooks/systems/config/valid_images.php',
			'sources/hooks/systems/config/valid_types.php',
			'sources/hooks/systems/config/website_email.php',
			'sources/hooks/systems/config/long_google_cookies.php',
			'sources/hooks/systems/config/detect_javascript.php',
			'sources/hooks/systems/config/welcome_message.php',
			'sources/hooks/systems/config/remember_me_by_default.php',
			'sources/hooks/systems/config/mobile_support.php',
			'sources/hooks/systems/addon_registry/core_configuration.php',
			'CONFIG_CATEGORY_SCREEN.tpl',
			'CONFIG_GROUP.tpl',
			'adminzone/pages/modules/admin_config.php',
			'themes/default/images/bigicons/config.png',
			'themes/default/images/pagepics/config.png',
			'lang/EN/config.ini',
			'sources/hooks/systems/config/.htaccess',
			'sources/hooks/systems/config/index.html',
			'XML_CONFIG_SCREEN.tpl',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'CONFIG_GROUP.tpl'=>'administrative__config_category_screen',
			'CONFIG_CATEGORY_SCREEN.tpl'=>'administrative__config_category_screen',
			'XML_CONFIG_SCREEN.tpl'=>'administrative__xml_config_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__config_category_screen()
	{
		$groups=new ocp_tempcode();

		foreach (placeholder_array() as $k=>$group)
		{
			$group=do_lorem_template('CONFIG_GROUP', array(
				'GROUP_DESCRIPTION'=>lorem_word(),
				'GROUP_NAME'=>$group,
				'GROUP'=>placeholder_fields(),
				'GROUP_TITLE'=>"ID$k"
			));
			$groups->attach($group->evaluate());
		}

		return array(
			lorem_globalise(do_lorem_template('CONFIG_CATEGORY_SCREEN', array(
				'CATEGORY_DESCRIPTION'=>lorem_word_2(),
				'_GROUPS'=>placeholder_array(),
				'PING_URL'=>placeholder_url(),
				'WARNING_DETAILS'=>'',
				'TITLE'=>lorem_title(),
				'URL'=>placeholder_url(),
				'GROUPS'=>$groups,
				'SUBMIT_NAME'=>lorem_word()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__xml_config_screen()
	{
		return array(
			lorem_globalise(do_lorem_template('XML_CONFIG_SCREEN', array(
				'XML'=>'<test />',
				'POST_URL'=>placeholder_url(),
				'TITLE'=>lorem_title()
			)), NULL, '', true)
		);
	}
}
