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
 * @package		setupwizard
 */

/**
 * Module page class.
 */
class Module_admin_setupwizard
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
		return array('misc'=>'SETUP_WIZARD');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$type=get_param('type','misc');

		set_helper_panel_pic('pagepics/configwizard');
		set_helper_panel_tutorial('tut_configuration');

		require_lang('config');

		if ($type=='misc') return $this->step1();
		if ($type=='step2') return $this->step2();
		if ($type=='step3') return $this->step3();
		if ($type=='step4') return $this->step4();
		if ($type=='step5') return $this->step5();
		if ($type=='step6') return $this->step6();
		if ($type=='step7') return $this->step7();
		if ($type=='step8') return $this->step8();
		if ($type=='step9') return $this->step9();
		if ($type=='step10') return $this->step10();
		if ($type=='step11') return $this->step11();

		return new ocp_tempcode();
	}

	/**
	 * UI for a setup wizard step (welcome).
	 *
	 * @return tempcode		The UI
	 */
	function step1()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(1),integer_format(10)));

		require_code('form_templates');

		$dh=opendir(get_custom_file_base().'/imports/addons/');
		$addons_available=array();
		while (($file=readdir($dh))!==false)
		{
			if (substr($file,-4)=='.tar')
			{
				$addons_available[]=basename($file,'.tar');
			}
		}
		closedir($dh);
		foreach ($addons_available as $aa)
		{
			if (!addon_installed($aa))
			{
				$addon_management=build_url(array('page'=>'admin_addons'),get_module_zone('admin_addons'));
				attach_message(do_lang_tempcode('ADDONS_NOT_INSTALLED_IN_SETUP_WIZARD',escape_html($addon_management->evaluate())),'warn');
				break;
			}
		}

		$_done_once=get_value('setup_wizard_completed');
		$done_once=!is_null($_done_once);

		$post_url=build_url(array('page'=>'_SELF','type'=>'step2'),'_SELF',array('keep_theme_seed','keep_theme_dark','keep_theme_source','keep_theme_algorithm'));
		$text=new ocp_tempcode();
		$text->attach(paragraph(do_lang_tempcode($done_once?'SETUP_WIZARD_1_DESCRIBE_ALT':'SETUP_WIZARD_1_DESCRIBE')));
		$rescue_url=build_url(array('page'=>'','keep_safe_mode'=>'1'),'');
		$text->attach(paragraph(do_lang_tempcode('SETUP_WIZARD_SAFE_MODE',escape_html($rescue_url->evaluate()),escape_html(find_theme_image('footer/ocpchat')))));
		$submit_name=do_lang_tempcode('PROCEED');

		$fields=new ocp_tempcode();

		//breadcrumb_set_self(do_lang_tempcode('START'));

		return do_template('FORM_SCREEN',array('_GUID'=>'71316d91703e3549301f57182405c997','SKIP_VALIDATION'=>true,'TITLE'=>$title,'FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>''));
	}

	/**
	 * UI for a setup wizard step (information).
	 *
	 * @return tempcode		The UI
	 */
	function step2()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(2),integer_format(10)));

		require_code('form_templates');

		$post_url=build_url(array('page'=>'_SELF','type'=>'step3'),'_SELF');
		$submit_name=do_lang_tempcode('PROCEED');

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('SETUPWIZARD_2_SCREEN',array('_GUID'=>'2042f3786d10c7c5be5d38ea28942b47','SKIP_VALIDATION'=>true,'TITLE'=>$title,'URL'=>$post_url,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * UI for a setup wizard step (config).
	 *
	 * @return tempcode		The UI
	 */
	function step3()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(3),integer_format(10)));

		$post_url=build_url(array('page'=>'_SELF','type'=>'step4'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_3_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');
		$fields=new ocp_tempcode();
		require_lang('zones');

		$site_name=get_option('site_name');
		$description=get_option('description');
		$site_scope=get_option('site_scope');
		$_header_text=$GLOBALS['SITE_DB']->query_select_value('zones','zone_header_text',array('zone_name'=>''));
		$header_text=get_translated_text($_header_text);
		$copyright=get_option('copyright');
		$staff_address=get_option('staff_address');
		$keywords=get_option('keywords');
		$google_analytics=get_option('google_analytics');

		if ($site_name=='???') $site_name=do_lang('EXAMPLE_SITE_NAME');
		if ($description=='???') $description=do_lang('EXAMPLE_DESCRIPTION');
		if ($site_scope=='???') $site_scope=do_lang('EXAMPLE_SITE_SCOPE');
		if ($header_text=='A site about ???') $header_text=do_lang('EXAMPLE_HEADER_TEXT');
		if ($copyright=='Copyright &copy;, ???, 2006') $copyright=do_lang('EXAMPLE_COPYRIGHT');
		if ($keywords=='') $keywords=do_lang('EXAMPLE_KEYWORDS');

		$installprofiles=new ocp_tempcode();
		$hooks=find_all_hooks('modules','admin_setupwizard_installprofiles');
		$installprofiles->attach(form_input_list_entry('',true,do_lang_tempcode('NA_EM')));
		require_code('zones2');
		foreach (array_keys($hooks) as $hook)
		{
			$path=get_file_base().'/sources_custom/modules/systems/admin_setupwizard_installprofiles/'.filter_naughty_harsh($hook).'.php';
			if (!file_exists($path))
				$path=get_file_base().'/sources/hooks/modules/admin_setupwizard_installprofiles/'.filter_naughty_harsh($hook).'.php';
			$_hook_bits=extract_module_functions($path,array('info'));
			$installprofile=is_array($_hook_bits[0])?call_user_func_array($_hook_bits[0][0],$_hook_bits[0][1]):@eval($_hook_bits[0]);
			$installprofiles->attach(form_input_list_entry($hook,false,$installprofile['title']));
		}
		$fields->attach(form_input_list(do_lang_tempcode('INSTALLPROFILE'),do_lang_tempcode('DESCRIPTION_INSTALLPROFILE'),'installprofile',$installprofiles,NULL,true,false));
		$fields->attach(form_input_line(do_lang_tempcode('SITE_NAME'),do_lang_tempcode('CONFIG_OPTION_site_name'),'site_name',$site_name,true));
		$fields->attach(form_input_line(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('CONFIG_OPTION_description'),'description',$description,false));
		$fields->attach(form_input_line(do_lang_tempcode('SITE_SCOPE'),do_lang_tempcode('CONFIG_OPTION_site_scope'),'site_scope',$site_scope,true));
		$fields->attach(form_input_line(do_lang_tempcode('HEADER_TEXT'),do_lang_tempcode('DESCRIPTION_HEADER_TEXT'),'header_text',$header_text,false));
		$fields->attach(form_input_line(do_lang_tempcode('COPYRIGHT'),do_lang_tempcode('CONFIG_OPTION_copyright'),'copyright',$copyright,false));
		$fields->attach(form_input_line(do_lang_tempcode('STAFF_EMAIL'),do_lang_tempcode('CONFIG_OPTION_staff_address'),'staff_address',$staff_address,true));
		$fields->attach(form_input_line(do_lang_tempcode('KEYWORDS'),do_lang_tempcode('CONFIG_OPTION_keywords'),'keywords',$keywords,false));
		$fields->attach(form_input_line(do_lang_tempcode('GOOGLE_ANALYTICS'),do_lang_tempcode('CONFIG_OPTION_google_analytics'),'google_analytics',$google_analytics,false));
		$fields->attach(form_input_tick(do_lang_tempcode('FIXED_WIDTH'),do_lang_tempcode('CONFIG_OPTION_fixed_width'),'fixed_width',get_option('fixed_width')=='1'));
		$panel_path=get_custom_file_base().'/pages/comcode_custom/'.get_site_default_lang().'/panel_left.txt';
		if (file_exists($panel_path))
		{
			$include_ocp_advert=strpos(file_get_contents($panel_path),'logos/')!==false;
		} else $include_ocp_advert=false;
		$fields->attach(form_input_tick(do_lang_tempcode('INCLUDE_OCP_ADVERT'),do_lang_tempcode('DESCRIPTION_INCLUDE_OCP_ADVERT'),'include_ocp_advert',$include_ocp_advert));

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'3126441524b51cba6a1e0de336c8a9d5','SKIP_VALIDATION'=>true,'TITLE'=>$title,'SKIPPABLE'=>'skip_3','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>''));
	}

	/**
	 * UI for a setup wizard step (addons).
	 *
	 * @return tempcode		The UI
	 */
	function step4()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(4),integer_format(10)));

		$post_url=build_url(array('page'=>'_SELF','type'=>'step5'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_4_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');

		$fields='';
		$fields_hidden='';
		$hidden=static_evaluate_tempcode(build_keep_post_fields());

		$installprofile=post_param('installprofile','');
		if ($installprofile!='')
		{
			require_code('hooks/modules/admin_setupwizard_installprofiles/'.$installprofile);
			$object=object_factory('Hook_admin_setupwizard_installprofiles_'.$installprofile);
			list($addon_list_on_by_default,$addon_list_advanced_on_by_default)=$object->get_addon_list();
			$addon_list_on_by_default=array_merge($addon_list_on_by_default,array(
				'banners',
				'ecommerce',
				'ocf_avatars',
				'ocf_cartoon_avatars',
				'ocf_member_avatars',
				'ocf_thematic_avatars',
				'wordfilter',
			));
		} else
		{
			$addon_list_on_by_default=NULL;
			$addon_list_advanced_on_by_default=array();
		}
		$addon_list_advanced_on_by_default=array_merge($addon_list_advanced_on_by_default,array(
			'actionlog',
			'awards',
			'breadcrumbs',
			'captcha',
			'catalogues',
			'counting_blocks',
			'custom_comcode',
			'errorlog',
			'help_page',
			'import',
			'jwplayer',
			'language_block',
			'occle',
			'ocf_cpfs',
			'page_management',
			'printer_friendly_block',
			'redirects_editor',
			'search',
			'securitylogging',
			'setupwizard',
			'staff_messaging',
			'stats',
			'stats_block',
			'syndication',
			'syndication_blocks',
			'themewizard',
			'uninstaller',
			'unvalidated',
			'phpinfo',
			'hphp_buildkit',
			'apache_config_files',
			'code_editor',
			'linux_helper_scripts',
			'windows_helper_scripts',
			'weather',
			'users_online_block',
		));

		$addon_list_advanced_off_by_default=array(
			'installer',
			'textbased_persistent_cacheing',
			'rootkit_detector',
			'msn',
			'staff',
			'backup',
			'bookmarks',
			'devguide',
			'supermember_directory',
		);

		require_lang('addons');
		require_code('addons');
		$addons_installed=find_installed_addons();
		foreach ($addons_installed as $row)
		{
			if ((substr($row['addon_name'],0,5)!='core_') && (substr($row['addon_name'],-7)!='_shared') && ($row['addon_name']!='setupwizard') && (file_exists(get_file_base().'/sources/hooks/systems/addon_registry/'.$row['addon_name'].'.php')))
			{
				$is_hidden_on_by_default=in_array($row['addon_name'],$addon_list_advanced_on_by_default);
				$is_hidden_off_by_default=in_array($row['addon_name'],$addon_list_advanced_off_by_default);
				$install_by_default=(is_null($addon_list_on_by_default) || in_array($row['addon_name'],$addon_list_on_by_default) || $is_hidden_on_by_default) && (!$is_hidden_off_by_default);
				if ((substr($row['addon_description'],-1)!='.') && ($row['addon_description']!='')) $row['addon_description'].='.';
				$field=form_input_tick($row['addon_name'],$row['addon_description'],'addon_'.$row['addon_name'],$install_by_default);
				if ((!$is_hidden_on_by_default) && (!$is_hidden_off_by_default))
				{
					$fields.=$field->evaluate();
				} else
				{
					$fields_hidden.=$field->evaluate();
				}
			} else
			{
				$hidden.=static_evaluate_tempcode(form_input_hidden('addon_'.$row['addon_name'],'1'));
			}
		}

		$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('SECTION_HIDDEN'=>true,'TITLE'=>do_lang_tempcode('ADVANCED'))));
		$fields.=$fields_hidden;

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'0f361a3ac0e020ba71f3a7a900eca0e4','SKIP_VALIDATION'=>true,'TITLE'=>$title,'SKIPPABLE'=>'skip_4','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>$hidden));
	}

	/**
	 * UI for a setup wizard step (the zone/feature configuration).
	 *
	 * @return tempcode		The UI
	 */
	function step5()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(5),integer_format(10)));

		require_lang('menus');

		$post_url=build_url(array('page'=>'_SELF','type'=>'step6'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_5_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');
		$fields='';

		$installprofile=post_param('installprofile','');
		if ($installprofile!='')
		{
			$path=get_file_base().'/sources_custom/modules/systems/admin_setupwizard_installprofiles/'.filter_naughty_harsh($installprofile).'.php';
			if (!file_exists($path))
				$path=get_file_base().'/sources/hooks/modules/admin_setupwizard_installprofiles/'.filter_naughty_harsh($installprofile).'.php';
			$_hook_bits=extract_module_functions($path,array('field_defaults'));
			$field_defaults=is_array($_hook_bits[0])?call_user_func_array($_hook_bits[0][0],$_hook_bits[0][1]):@eval($_hook_bits[0]);
		} else $field_defaults=array();

		$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('FEATURES'))));

		$hooks=find_all_hooks('modules','admin_setupwizard');
		foreach (array_keys($hooks) as $hook)
		{
			if (post_param_integer('addon_'.$hook,0)==1)
			{
				$path=get_file_base().'/sources_custom/modules/systems/admin_setupwizard/'.filter_naughty_harsh($hook).'.php';
				if (!file_exists($path))
					$path=get_file_base().'/sources/hooks/modules/admin_setupwizard/'.filter_naughty_harsh($hook).'.php';
				if (strpos(file_get_contents($path),'get_fields')!==false) // Memory optimisation
				{
					require_code('hooks/modules/admin_setupwizard/'.filter_naughty_harsh($hook));
					$hook=object_factory('Hook_sw_'.filter_naughty_harsh($hook),true);
					if (is_null($hook)) continue;
					if (method_exists($hook,'get_fields'))
					{
						$hook_fields=$hook->get_fields($field_defaults);
						$fields.=static_evaluate_tempcode($hook_fields);
					}
				}
			}
		}

		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('SHOW_CONTENT_TAGGING'),do_lang_tempcode('CONFIG_OPTION_show_content_tagging'),'show_content_tagging',array_key_exists('show_content_tagging',$field_defaults)?($field_defaults['show_content_tagging']=='1'):(get_option('show_content_tagging')=='1')));
		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('SHOW_CONTENT_TAGGING_INLINE'),do_lang_tempcode('CONFIG_OPTION_show_content_tagging_inline'),'show_content_tagging_inline',array_key_exists('show_content_tagging_inline',$field_defaults)?($field_defaults['show_content_tagging_inline']=='1'):(get_option('show_content_tagging_inline')=='1')));
		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('SHOW_SCREEN_ACTIONS'),do_lang_tempcode('CONFIG_OPTION_show_screen_actions'),'show_screen_actions',array_key_exists('show_screen_actions',$field_defaults)?($field_defaults['show_screen_actions']=='1'):(get_option('show_screen_actions')=='1')));

		$fields.=static_evaluate_tempcode(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('STRUCTURE'),'HELP'=>do_lang_tempcode('SETUP_WIZARD_5x_DESCRIBE'))));

		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('COLLAPSE_USER_ZONES'),do_lang_tempcode('CONFIG_OPTION_collapse_user_zones'),'collapse_user_zones',array_key_exists('collapse_user_zones',$field_defaults)?($field_defaults['collapse_user_zones']=='1'):(get_option('collapse_user_zones')=='1')));
		$fields.=static_evaluate_tempcode(form_input_tick(do_lang_tempcode('GUEST_ZONE_ACCESS'),do_lang_tempcode('DESCRIPTION_GUEST_ZONE_ACCESS'),'guest_zone_access',array_key_exists('guest_zone_access',$field_defaults)?($field_defaults['guest_zone_access']=='1'):true));

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		$js='var cuz=document.getElementById("collapse_user_zones"); var cuz_func=function() { var gza=document.getElementById("guest_zone_access"); gza.disabled=cuz.checked; if (cuz.checked) gza.checked=true; }; cuz.onchange=cuz_func; cuz_func();';

		return do_template('FORM_SCREEN',array('_GUID'=>'f1e9a4d271c7d68ff9da6dc0438f6e3f','SKIP_VALIDATION'=>true,'JAVASCRIPT'=>$js,'TITLE'=>$title,'SKIPPABLE'=>'skip_5','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>static_evaluate_tempcode(build_keep_post_fields())));
	}

	/**
	 * UI for a setup wizard step (block choice).
	 *
	 * @return tempcode		The UI
	 */
	function step6()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(6),integer_format(10)));

		require_all_lang();

		$installprofile=post_param('installprofile','');
		if ($installprofile!='')
		{
			require_code('hooks/modules/admin_setupwizard_installprofiles/'.$installprofile);
			$object=object_factory('Hook_admin_setupwizard_installprofiles_'.$installprofile);
			$default_blocks=$object->default_blocks();
		} else $default_blocks=NULL;

		$main_blocks=array();
		$side_blocks=array();
		if ($installprofile!='') $side_blocks['side_personal_stats']='PANEL_LEFT';
		$hooks=find_all_hooks('modules','admin_setupwizard');
		foreach (array_keys($hooks) as $hook)
		{
			if (post_param_integer('addon_'.$hook,0)==1)
			{
				require_code('hooks/modules/admin_setupwizard/'.filter_naughty_harsh($hook));
				$ob=object_factory('Hook_sw_'.filter_naughty_harsh($hook),true);
				if (is_null($ob)) continue;
				if (method_exists($ob,'get_blocks'))
				{
					$ret=$ob->get_blocks();
					if (count($ret)!=0)
					{
						list($a,$b)=$ret;
						$main_blocks=array_merge($main_blocks,$a);
						$side_blocks=array_merge($side_blocks,$b);
					}
				}
			}
		}
		ksort($main_blocks);
		ksort($side_blocks);

		$post_url=build_url(array('page'=>'_SELF','type'=>'step7'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_6_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');
		$fields='';
		require_lang('blocks');
		require_lang('zones');
		require_code('zones2');

		$tmp=do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('FRONT_PAGE')));
		$fields.=$tmp->evaluate(); /*XHTMLXHTML*/
		foreach ($main_blocks as $block=>$position_bits)
		{
			if (!file_exists(get_file_base().'/sources/blocks/'.$block.'.php')) continue;

			$description=paragraph(do_lang_tempcode('BLOCK_'.$block.'_DESCRIPTION'));
			$description->attach(paragraph(do_lang_tempcode('BLOCK_'.$block.'_USE')));
			$block_nice=cleanup_block_name($block);
			if (is_null($default_blocks))
			{
				$position=$position_bits[1];
			} else
			{
				$position='NO';
				foreach (array('YES','YES_CELL','PANEL_LEFT','PANEL_RIGHT') as $p)
				{
					if (in_array($block,$default_blocks[$p])) $position=$p;
				}
			}
			$main_list=new ocp_tempcode();
			$main_list->attach(form_input_list_entry('NO',$position=='NO',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_NO')));
			$main_list->attach(form_input_list_entry('YES',$position=='YES',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_YES')));
			$main_list->attach(form_input_list_entry('YES_CELL',$position=='YES_CELL',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_YES_CELL')));
			$main_list->attach(form_input_list_entry('PANEL_LEFT',$position=='PANEL_LEFT',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_LEFT')));
			$main_list->attach(form_input_list_entry('PANEL_RIGHT',$position=='PANEL_RIGHT',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_RIGHT')));
			$tmp=form_input_list($block_nice,$description,'block_SITE_'.$block,$main_list);
			$fields.=$tmp->evaluate(); /*XHTMLXHTML*/
		}

		$tmp=do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>do_lang_tempcode('PANELS')));
		$fields.=$tmp->evaluate(); /*XHTMLXHTML*/
		foreach ($side_blocks as $block=>$position_bits)
		{
			if (!file_exists(get_file_base().'/sources/blocks/'.$block.'.php')) continue;

			$description=paragraph(do_lang_tempcode('BLOCK_'.$block.'_DESCRIPTION'));
			$description->attach(paragraph(do_lang_tempcode('BLOCK_'.$block.'_USE')));
			$block_nice=cleanup_block_name($block);
			if (is_null($default_blocks))
			{
				$position=$position_bits[1];
			} else
			{
				$position='NO';
				foreach (array('YES','YES_CELL','PANEL_LEFT','PANEL_RIGHT') as $p)
				{
					if (in_array($block,$default_blocks[$p])) $position=$p;
				}
			}
			$side_list=new ocp_tempcode();
			$side_list->attach(form_input_list_entry('PANEL_NONE',$position=='PANEL_NONE',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_NONE')));
			$side_list->attach(form_input_list_entry('PANEL_LEFT',$position=='PANEL_LEFT',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_LEFT')));
			$side_list->attach(form_input_list_entry('PANEL_RIGHT',$position=='PANEL_RIGHT',do_lang_tempcode('BLOCK_CONFIGURATION__PANEL_RIGHT')));
			$tmp=form_input_list($block_nice,$description,'block_SITE_'.$block,$side_list);
			$fields.=$tmp->evaluate(); /*XHTMLXHTML*/
		}

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'d463906b9e2cd8c37577d64783aa844c','SKIP_VALIDATION'=>true,'TITLE'=>$title,'SKIPPABLE'=>'skip_6','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'PREVIEW'=>true,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>static_evaluate_tempcode(build_keep_post_fields())));
	}

	/**
	 * Get Comcode to save as the rules.
	 *
	 * @param  ID_TEXT		A code relating to which rules set to get
	 * @return string			The Comcode
	 */
	function get_rules_file($code)
	{
		require_code('textfiles');
		return read_text_file('rules_'.$code,'');
	}

	/**
	 * UI for a setup wizard step (rules).
	 *
	 * @return tempcode		The UI
	 */
	function step7()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(7),integer_format(10)));

		$post_url=build_url(array('page'=>'_SELF','type'=>(addon_installed('themewizard') && (function_exists('imagecreatefromstring')))?'step8':'step9'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_7_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		$installprofile=post_param('installprofile','');
		if ($installprofile!='')
		{
			require_code('hooks/modules/admin_setupwizard_installprofiles/'.$installprofile);
			$object=object_factory('Hook_admin_setupwizard_installprofiles_'.$installprofile);
			$field_defaults=$object->field_defaults();
		} else $field_defaults=array();

		require_code('form_templates');
		$list=new ocp_tempcode();
		$list->attach(form_input_list_entry('balanced',array_key_exists('rules',$field_defaults)?($field_defaults['rules']=='balanced'):true,do_lang_tempcode('SETUP_WIZARD_RULES_balanced')));
		$list->attach(form_input_list_entry('liberal',array_key_exists('rules',$field_defaults)?($field_defaults['rules']=='liberal'):false,do_lang_tempcode('SETUP_WIZARD_RULES_liberal')));
		$list->attach(form_input_list_entry('corporate',array_key_exists('rules',$field_defaults)?($field_defaults['rules']=='corporate'):false,do_lang_tempcode('SETUP_WIZARD_RULES_corporate')));
		$fields=form_input_list(do_lang_tempcode('RULES'),do_lang_tempcode('DESCRIPTION_RULES'),'rules',$list,NULL,true);
		$javascript="document.getElementById('rules').onchange=function () { var items=['preview_box_balanced','preview_box_liberal','preview_box_corporate']; var i; for (i=0;i<items.length;i++) document.getElementById(items[i]).style.display=(this.selectedIndex!=i)?'none':'block'; }";
		$form=do_template('FORM',array('_GUID'=>'bf01a2b90967e86213ae0672c36a4b4e','TITLE'=>$title,'SKIPPABLE'=>'skip_7','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>static_evaluate_tempcode(build_keep_post_fields()),'JAVASCRIPT'=>$javascript));

		$balanced=comcode_to_tempcode($this->get_rules_file('balanced'),NULL,true);
		$liberal=comcode_to_tempcode($this->get_rules_file('liberal'),NULL,true);
		$corporate=comcode_to_tempcode($this->get_rules_file('corporate'),NULL,true);

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('SETUPWIZARD_7_SCREEN',array('_GUID'=>'5e46c3a989e42fa6eec5a017e8c644c2','TITLE'=>$title,'FORM'=>$form,'BALANCED'=>$balanced,'LIBERAL'=>$liberal,'CORPORATE'=>$corporate));
	}

	/**
	 * UI for a setup wizard step (theme).
	 *
	 * @return tempcode		The UI
	 */
	function step8()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(8),integer_format(10)));

		require_lang('themes');
		require_code('themewizard');

		$post_url=build_url(array('page'=>'_SELF','type'=>'step9'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_8_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');
		$fields=new ocp_tempcode();
		$fields->attach(form_input_colour(do_lang_tempcode('SEED_COLOUR'),do_lang_tempcode('DESCRIPTION_SEED_COLOUR'),'seed_hex','#'.find_theme_seed('default'),true));
		$fields->attach(form_input_tick(do_lang_tempcode('DARK_THEME'),do_lang_tempcode('DESCRIPTION_DARK_THEME'),'dark',get_param_integer('dark',0)==1));

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'7ef31eb9712cff98da57a92fc173f7af','PREVIEW'=>true,'SKIP_VALIDATION'=>true,'TITLE'=>$title,'SKIPPABLE'=>'skip_8','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>static_evaluate_tempcode(build_keep_post_fields())));
	}

	/**
	 * UI for a setup wizard step (close-status).
	 *
	 * @return tempcode		The UI
	 */
	function step9()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(9),integer_format(10)));

		$post_url=build_url(array('page'=>'_SELF','type'=>'step10'),'_SELF');
		$text=do_lang_tempcode('SETUP_WIZARD_9_DESCRIBE');
		$submit_name=do_lang_tempcode('PROCEED');

		require_code('form_templates');
		$fields=new ocp_tempcode();
		$fields->attach(form_input_tick(do_lang_tempcode('CLOSED_SITE'),do_lang_tempcode('CONFIG_OPTION_site_closed'),'site_closed',true));
		$fields->attach(form_input_text(do_lang_tempcode('MESSAGE'),do_lang_tempcode('CONFIG_OPTION_closed'),'closed',get_option('closed'),false));

		$javascript="document.getElementById('site_closed').onchange=function() { document.getElementById('closed').disabled=!this.checked; }";

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));

		return do_template('FORM_SCREEN',array('_GUID'=>'c405a64a08328f78ac0e3f22a8365411','SKIP_VALIDATION'=>true,'TITLE'=>$title,'SKIPPABLE'=>'skip_9','FIELDS'=>$fields,'URL'=>$post_url,'TEXT'=>$text,'SUBMIT_NAME'=>$submit_name,'HIDDEN'=>static_evaluate_tempcode(build_keep_post_fields()),'JAVASCRIPT'=>$javascript));
	}

	/**
	 * UI for a setup wizard step (done).
	 *
	 * @return tempcode		The UI
	 */
	function step10()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(10),integer_format(10)));

		$GLOBALS['NO_QUERY_LIMIT']=true;

		require_code('abstract_file_manager');
		force_have_afm_details();

		if (function_exists('set_time_limit')) @set_time_limit(600);

		require_code('config2');
		require_code('menus2');
		require_code('themes2');
		require_lang('zones');

		$header_text=post_param('header_text');
		$name=post_param('site_name');
		$theme=substr(preg_replace('#[^A-Za-z\d]#','_',$name),0,40);
		$installprofile=post_param('installprofile','');

		if ($installprofile!='')
		{
			// Simplify down to a single menu
			foreach (array('main_content','main_website') as $merge_item)
			{
				$GLOBALS['SITE_DB']->query_update('menu_items',array('i_menu'=>'site'),array('i_menu'=>$merge_item));
			}
			$duplicates=$GLOBALS['SITE_DB']->query_select('menu_items',array('id','COUNT(*) AS cnt'),array('i_menu'=>'site'),'GROUP BY i_url');
			foreach ($duplicates as $duplicate)
			{
				if ($duplicate['cnt']>1)
					delete_menu_item($duplicate['id']);
			}
			delete_menu_item_simple('site:');

			// Run any specific code for the profile
			require_code('hooks/modules/admin_setupwizard_installprofiles/'.$installprofile);
			$object=object_factory('Hook_admin_setupwizard_installprofiles_'.$installprofile);
			$object->install_code();
			$installprofileblocks=$object->default_blocks();
		} else $installprofileblocks=array();

		if ((post_param_integer('skip_8',0)==0) && (function_exists('imagecreatefromstring')) && (addon_installed('themewizard')))
		{
			require_code('themewizard');

			// Make theme
			global $IMG_CODES;
			$old_img_codes_site=$GLOBALS['SITE_DB']->query_select('theme_images',array('id','path'),array('theme'=>$GLOBALS['FORUM_DRIVER']->get_theme(),'lang'=>user_lang()));
			if (!file_exists(get_custom_file_base().'/themes/'.$theme))
			{
				make_theme($theme,'default','equations',post_param('seed_hex'),true,post_param_integer('dark',0)==1);
			}
			foreach (array($theme,'default') as $logo_save_theme)
			{
				$logo=generate_logo($name,$header_text,false,$logo_save_theme,'logo_template');
				$path='themes/'.$logo_save_theme.'/images_custom/-logo.png';
				@imagepng($logo,get_custom_file_base().'/'.$path) OR intelligent_write_error($path);
				actual_edit_theme_image('logo/-logo',$logo_save_theme,get_site_default_lang(),'logo/-logo',$path,true);
				if (addon_installed('collaboration_zone'))
					actual_edit_theme_image('logo/collaboration-logo',$logo_save_theme,get_site_default_lang(),'logo/collaboration-logo',$path,true);
				imagedestroy($logo);
				$logo=generate_logo($name,$header_text,false,$logo_save_theme,'trimmed_logo_template');
				$path='themes/'.$logo_save_theme.'/images_custom/trimmed_logo.png';
				@imagepng($logo,get_custom_file_base().'/'.$path) OR intelligent_write_error($path);
				actual_edit_theme_image('logo/trimmed_logo',$logo_save_theme,get_site_default_lang(),'logo/trimmed_logo',$path,true);
				imagedestroy($logo);
			}
			$myfile=fopen(get_custom_file_base().'/themes/'.filter_naughty($theme).'/theme.ini','wt');
			fwrite($myfile,'title='.$name.chr(10));
			fwrite($myfile,'description='.do_lang('NA').chr(10));
			if (fwrite($myfile,'author=ocPortal'.chr(10))==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			fclose($myfile);
			sync_file(get_custom_file_base().'/themes/'.filter_naughty($theme).'/theme.ini');
			$IMG_CODES['site']=$old_img_codes_site; // Just so it renders with the old theme
		}

		// Set options
		if (post_param_integer('skip_3',0)==0)
		{
			set_option('site_name',$name);
			set_option('description',post_param('description'));
			set_option('site_scope',post_param('site_scope'));
			set_option('copyright',post_param('copyright'));
			set_option('staff_address',post_param('staff_address'));
			set_option('keywords',post_param('keywords'));
			set_option('google_analytics',post_param('google_analytics'));
			set_option('fixed_width',post_param('fixed_width','0'));
			$a=$GLOBALS['SITE_DB']->query_select_value('zones','zone_header_text',array('zone_name'=>''));
			lang_remap($a,$header_text);
			$b=$GLOBALS['SITE_DB']->query_select_value_if_there('zones','zone_header_text',array('zone_name'=>'site'));
			if (!is_null($b)) lang_remap($b,$header_text);
		}

		if (post_param_integer('skip_9',0)==0)
		{
			set_option('site_closed',strval(post_param_integer('site_closed',0)));
			set_option('closed',post_param('closed',''));
		}

		// Set addons
		if ((post_param_integer('skip_4',0)==0) && (get_file_base()==get_custom_file_base()))
		{
			require_lang('addons');
			require_code('addons');
			$addons_installed=find_installed_addons();
			$uninstalling=array();
			foreach ($addons_installed as $addon_row)
			{
				if (post_param_integer('addon_'.$addon_row['addon_name'],0)==0)
				{
					$uninstalling[]=$addon_row['addon_name'];
				}
			}
			if (!file_exists(get_file_base().'/.svn')) // Only uninstall if we're not working from a SVN repository
			{
				foreach ($addons_installed as $addon_row)
				{
					if (post_param_integer('addon_'.$addon_row['addon_name'],0)==0)
					{
						$addon_row+=read_addon_info($addon_row['addon_name']);
						$addon_row['addon_author']=''; // Fudge, to stop it dying on warnings for official addons

						// Check dependencies
						$dependencies=$addon_row['addon_dependencies_on_this'];
						foreach ($uninstalling as $d)
						{
							if (in_array($d,$dependencies)) unset($dependencies[array_search($d,$dependencies)]);
						}

						if (count($dependencies)==0)
						{
							// Archive it off to exports/addons
							$file=preg_replace('#^[\_\.\-]#','x',preg_replace('#[^\w\.\-]#','_',$addon_row['addon_name'])).'.tar';
							create_addon($file,explode(chr(10),$addon_row['addon_files']),$addon_row['addon_name'],implode(',',$addon_row['addon_incompatibilities']),implode(',',$addon_row['addon_dependencies']),$addon_row['addon_author'],$addon_row['addon_organisation'],$addon_row['addon_version'],$addon_row['addon_description'],'imports/addons');

							uninstall_addon($addon_row['addon_name']);
						}
					}
				}
			}
		}

		// Set features
		if (post_param_integer('skip_5',0)==0)
		{
			$hooks=find_all_hooks('modules','admin_setupwizard');
			foreach (array_keys($hooks) as $hook)
			{
				if (post_param_integer('addon_'.$hook,0)==1)
				{
					$path=get_file_base().'/sources_custom/modules/systems/admin_setupwizard/'.filter_naughty_harsh($hook).'.php';
					if (!file_exists($path))
						$path=get_file_base().'/sources/hooks/modules/admin_setupwizard/'.filter_naughty_harsh($hook).'.php';
					$_hook_bits=extract_module_functions($path,array('set_fields'));
					if (is_array($_hook_bits[0])) call_user_func_array($_hook_bits[0][0],$_hook_bits[0][1]); else @eval($_hook_bits[0]);
				}
			}
			set_option('show_content_tagging',post_param('show_content_tagging','0'));
			set_option('show_content_tagging_inline',post_param('show_content_tagging_inline','0'));
			set_option('show_screen_actions',post_param('show_screen_actions','0'));
		}

		// Zone structure
		$collapse_zones=post_param_integer('collapse_user_zones',0)==1;
		if (post_param_integer('skip_5',0)==0)
		{
			require_code('config2');
			set_option('collapse_user_zones',strval($collapse_zones));

			if (post_param_integer('guest_zone_access',0)==1)
			{
				$guest_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($GLOBALS['FORUM_DRIVER']->get_guest_id());
				$test=$GLOBALS['SITE_DB']->query_select_value_if_there('group_zone_access','zone_name',array('zone_name'=>'site','group_id'=>$guest_groups[0]));
				if (is_null($test)) $GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>'site','group_id'=>$guest_groups[0]));
			}
		}

		// Rules
		if (post_param_integer('skip_7',0)==0)
		{
			$fullpath=get_custom_file_base().'/pages/comcode_custom/'.get_site_default_lang().'/rules.txt';
			if (file_exists($fullpath))
			{
				@copy($fullpath,$fullpath.'.'.strval(time()));
				fix_permissions($fullpath.'.'.strval(time()));
				sync_file($fullpath.'.'.strval(time()));
			}
			$myfile=@fopen($fullpath,'wt') OR intelligent_write_error(get_custom_file_base().'/pages/comcode_custom/'.get_site_default_lang().'/rules.txt');
			$rf=$this->get_rules_file(post_param('rules'));
			if (fwrite($myfile,$rf)<strlen($rf)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
			fclose($myfile);
			fix_permissions($fullpath);
			sync_file($fullpath);
		}

		$installprofile=post_param('installprofile','');
		if ($installprofile!='')
		{
			require_code('hooks/modules/admin_setupwizard_installprofiles/'.$installprofile);
			$object=object_factory('Hook_admin_setupwizard_installprofiles_'.$installprofile);
			$block_options=$object->block_options();
		} else $block_options=NULL;

		// Blocks
		if (post_param_integer('skip_6',0)==0)
		{
			require_code('setupwizard');
			$page_structure=_get_zone_pages($installprofileblocks,$block_options,$collapse_zones,$installprofile);

			foreach ($page_structure as $zone=>$zone_pages)
			{
				// Start
				$fullpath=get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.get_site_default_lang().'/start.txt';
				if (file_exists($fullpath)) @copy($fullpath,$fullpath.'.'.strval(time()));
				$myfile=@fopen($fullpath,'wt')  OR intelligent_write_error($fullpath);
				if ($myfile!==false)
				{
					if ($zone_pages['start']!='')
						if (fwrite($myfile,$zone_pages['start'])==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
					fclose($myfile);
					fix_permissions($fullpath);
					sync_file($fullpath);
				}

				// Left
				$fullpath=get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.get_site_default_lang().'/panel_left.txt';
				if (file_exists($fullpath)) @copy($fullpath,$fullpath.'.'.strval(time()));
				$myfile=@fopen($fullpath,'wt');
				if ($myfile!==false)
				{
					if ($zone_pages['left']!='')
						if (fwrite($myfile,$zone_pages['left'])==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
					fclose($myfile);
					fix_permissions($fullpath);
					sync_file($fullpath);
				}

				// Right
				$fullpath=get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.get_site_default_lang().'/panel_right.txt';
				if (file_exists($fullpath)) @copy($fullpath,$fullpath.'.'.strval(time()));
				$myfile=fopen($fullpath,'wt');
				if ($myfile!==false)
				{
					if ($zone_pages['right']!='')
						if (fwrite($myfile,$zone_pages['right'])==0) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
					fclose($myfile);
					fix_permissions($fullpath);
					sync_file($fullpath);
				}
			}
		}

		// We're done
		set_value('setup_wizard_completed','1');

		// Clear some cacheing
		require_code('view_modes');
		require_code('zones3');
		erase_comcode_page_cache();
		erase_tempcode_cache();
		//persistent_cache_delete('OPTIONS');  Done by set_option
		persistent_cache_empty();
		erase_cached_templates();

		//breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('START'))));
		//breadcrumb_set_self(do_lang_tempcode('SETUP_WIZARD_STEP',integer_format(10),integer_format(10)));

		$url=build_url(array('page'=>'_SELF','type'=>'step11'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * UI for a setup wizard step (done, message after cache emptied - need lower memory usage to rebuild them).
	 *
	 * @return tempcode		The UI
	 */
	function step11()
	{
		$title=get_screen_title('SETUP_WIZARD_STEP',true,array(integer_format(10),integer_format(10)));

		require_code('templates_donext');

		// Show nice interface to start adding pages
		return do_next_manager($title,do_lang_tempcode('SUCCESS'),
						array(
							/*	 type							  page	 params													 zone	  */
							addon_installed('page_management')?array('pagewizard',array('admin_sitetree',array('type'=>'pagewizard'),get_module_zone('admin_sitetree')),do_lang('PAGE_WIZARD')):NULL,
							array('main_home',array(NULL,array(),'')),
							array('cms_home',array(NULL,array(),'cms')),
							array('admin_home',array(NULL,array(),'adminzone')),
						),
						do_lang('PAGES'),
						NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,
						paragraph(do_lang_tempcode('SETUP_WIZARD_10_DESCRIBE'))
		);
	}
}


