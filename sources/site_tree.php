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
 * @package		core
 */

/**
 * AJAX script for dynamically extended sitetree.
 */
function site_tree_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	$root_perms=array('submit_cat_highrange_content'=>0,'edit_cat_highrange_content'=>0,'edit_own_cat_highrange_content'=>0,'delete_cat_highrange_content'=>0,'delete_own_cat_highrange_content'=>0,'submit_highrange_content'=>1,'bypass_validation_highrange_content'=>1,'edit_own_highrange_content'=>1,'edit_highrange_content'=>1,'delete_own_highrange_content'=>1,'delete_highrange_content'=>1,'submit_cat_midrange_content'=>0,'edit_cat_midrange_content'=>0,'edit_own_cat_midrange_content'=>0,'delete_cat_midrange_content'=>0,'delete_own_cat_midrange_content'=>0,'submit_midrange_content'=>1,'bypass_validation_midrange_content'=>1,'edit_own_midrange_content'=>1,'edit_midrange_content'=>1,'delete_own_midrange_content'=>1,'delete_midrange_content'=>1,'submit_cat_lowrange_content'=>0,'edit_cat_lowrange_content'=>0,'edit_own_cat_lowrange_content'=>0,'delete_cat_lowrange_content'=>0,'delete_own_cat_lowrange_content'=>0,'submit_lowrange_content'=>1,'bypass_validation_lowrange_content'=>1,'edit_own_lowrange_content'=>1,'edit_lowrange_content'=>1,'delete_own_lowrange_content'=>1,'delete_lowrange_content'=>1);

	require_code('zones2');
	require_code('zones3');

	// Usergroups we have
	$admin_groups=$GLOBALS['FORUM_DRIVER']->get_super_admin_groups();
	$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

	if (!has_actual_page_access(get_member(),'admin_site_tree','adminzone')) exit();

	if (function_exists('set_time_limit')) @set_time_limit(30);

	disable_php_memory_limit(); // Needed for loading large amount of permissions (potentially)

	// ======
	// Saving
	// ======

	if (get_param_integer('set_perms',0)==1)
	{
		if (!has_actual_page_access(get_member(),'admin_permissions','adminzone')) exit();

		// Build a map of every page link we are setting permissions for
		$map=array();
		foreach (array_merge($_GET,$_POST) as $i=>$page_link)
		{
			if (get_magic_quotes_gpc()) $page_link=stripslashes($page_link);

			if (substr($i,0,4)=='map_')
			{
				$map[intval(substr($i,4))]=$page_link;
			}
		}

		// Read it all in
		foreach ($map as $i=>$page_link) // For everything we're setting at once
		{
			// Decode page link
			$matches=array();
			$type='';
			if ($page_link=='_root') $type='root';
			elseif (preg_match('#^([^:]*):([^:]+):.+$#',$page_link,$matches)!=0) $type='cat';
			elseif (preg_match('#^([^:]*):([^:]+)$#',$page_link,$matches)!=0) $type='page';
			elseif (preg_match('#^([^:]*):?$#',$page_link,$matches)!=0) $type='zone';
			else $type='root';

			// Working out what we're doing with privilege overrides
			if (($type=='page') || ($type=='cat'))
			{
				$zone=$matches[1];
				$page=$matches[2];

				list($overridables,$sp_page)=get_module_overridables($zone,$page);
			}

			if ($type=='root')
			{
				// Insertion
				foreach ($groups as $group=>$group_name) // For all usergroups
				{
					if (!in_array($group,$admin_groups))
					{
						// SP's
						foreach (array_keys($root_perms) as $overide) // For all SP's supported here (some will be passed that aren't - so we can't work back from GET params)
						{
							$val=post_param_integer(strval($i).'gsp_'.$overide.'_'.strval($group),-2);
							if ($val!=-2)
							{
								$GLOBALS['SITE_DB']->query_delete('gsp',array('specific_permission'=>$overide,'group_id'=>$group,'the_page'=>'','module_the_name'=>'','category_name'=>''));
								if ($val!=-1)
								{
									$GLOBALS['SITE_DB']->query_insert('gsp',array('specific_permission'=>$overide,'group_id'=>$group,'module_the_name'=>'','category_name'=>'','the_page'=>'','the_value'=>$val));
								}
							}
						}
					}
				}
			}
			elseif ($type=='zone')
			{
				$zone=$matches[1];

				// Insertion
				foreach ($groups as $group=>$group_name) // For all usergroups
				{
					if (!in_array($group,$admin_groups))
					{
						// View access
						$view=post_param_integer(strval($i).'g_view_'.strval($group),-1);
						if ($view!=-1) // -1 means unchanged
						{
							$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('zone_name'=>$zone,'group_id'=>$group));
							if ($view==1)
								$GLOBALS['SITE_DB']->query_insert('group_zone_access',array('zone_name'=>$zone,'group_id'=>$group));
						}
					}
				}
			}
			elseif ($type=='page')
			{
				// Insertion
				foreach ($groups as $group=>$group_name) // For all usergroups
				{
					if (!in_array($group,$admin_groups))
					{
						// View access
						$view=post_param_integer(strval($i).'g_view_'.strval($group),-1);
						if ($view!=-1) // -1 means unchanged
						{
							$GLOBALS['SITE_DB']->query_delete('group_page_access',array('zone_name'=>$zone,'page_name'=>$page,'group_id'=>$group));
							if ($view==0) // Pages have access by row non-presence, for good reason
								$GLOBALS['SITE_DB']->query_insert('group_page_access',array('zone_name'=>$zone,'page_name'=>$page,'group_id'=>$group));
						}

						// SP's
						foreach (array_keys($overridables) as $overide) // For all SP's supported here (some will be passed that aren't - so we can't work back from GET params)
						{
							$val=post_param_integer(strval($i).'gsp_'.$overide.'_'.strval($group),-2);
							if ($val!=-2)
							{
								$GLOBALS['SITE_DB']->query_delete('gsp',array('specific_permission'=>$overide,'group_id'=>$group,'the_page'=>$sp_page));
								if ($val!=-1)
								{
									$GLOBALS['SITE_DB']->query_insert('gsp',array('specific_permission'=>$overide,'group_id'=>$group,'module_the_name'=>'','category_name'=>'','the_page'=>$sp_page,'the_value'=>$val));
								}
							}
						}
					}
				}
			}
			elseif ($type=='cat')
			{
				$_pagelinks=extract_module_functions_page($zone,$page,array('extract_page_link_permissions'),array($page_link));
				list($category,$module)=is_array($_pagelinks[0])?call_user_func_array($_pagelinks[0][0],$_pagelinks[0][1]):eval($_pagelinks[0]); // If $_pagelinks[0] is NULL then it's an error: extract_page_link_permissions is always there when there are cat permissions

				// Insertion
				foreach ($groups as $group=>$group_name) // For all usergroups
				{
					if (!in_array($group,$admin_groups))
					{
						// View access
						$view=post_param_integer(strval($i).'g_view_'.strval($group),-1);
						if ($view!=-1) // -1 means unchanged
						{
							$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>$module,'category_name'=>$category,'group_id'=>$group));
							if ($view==1)
								$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>$module,'category_name'=>$category,'group_id'=>$group));
						}

						// SP's
						foreach ($overridables as $overide=>$cat_support) // For all SP's supported here (some will be passed that aren't - so we can't work back from GET params)
						{
							if (is_array($cat_support)) $cat_support=$cat_support[0];
							if ($cat_support==0) continue;

							$val=post_param_integer(strval($i).'gsp_'.$overide.'_'.strval($group),-2);
							if ($val!=-2)
							{
								$GLOBALS['SITE_DB']->query_delete('gsp',array('specific_permission'=>$overide,'group_id'=>$group,'module_the_name'=>$module,'category_name'=>$category,'the_page'=>''));
								if ($val!=-1)
								{
									$new_settings=array('specific_permission'=>$overide,'group_id'=>$group,'module_the_name'=>$module,'category_name'=>$category,'the_page'=>'','the_value'=>$val);
									$GLOBALS['SITE_DB']->query_insert('gsp',$new_settings);
								}
							}
						}
					}
				}
			}
		}

		decache('main_sitemap');
		$GLOBALS['SITE_DB']->query_delete('cache');
		if (function_exists('persistant_cache_empty')) persistant_cache_empty();

		// Tra la la tada
		return;
	}

	// =======
	// Loading
	// =======

	$default=get_param('default',NULL);

	header('Content-Type: text/xml');
	$permissions_needed=(get_param_integer('get_perms',0)==1); // Whether we are limiting our tree to permission-supporting
	@ini_set('ocproducts.xss_detect','0');

	echo '<'.'?xml version="1.0" encoding="'.get_charset().'"?'.'>';
	echo '<request><result>';
	require_lang('permissions');
	require_lang('zones');
	$page_link=get_param('id',NULL,true);
	$_sp_access=$GLOBALS['SITE_DB']->query_select('gsp',array('*'));
	$sp_access=array();
	foreach ($_sp_access as $a)
	{
		if (!isset($sp_access[$a['group_id']])) $sp_access[$a['group_id']]=array();
		$sp_access[$a['group_id']][]=$a;
	}

	if ((!is_null($page_link)) && ($page_link!='') && ((strpos($page_link,':')===false) || (strpos($page_link,':')===strlen($page_link)-1))) // Expanding a zone
	{
		if (strpos($page_link,':')===strlen($page_link)-1) $page_link=substr($page_link,0,strlen($page_link)-1);

		// Pages in the zone
		$zone=$page_link;
		$page_type=get_param('page_type',NULL);
		$pages=find_all_pages_wrap($zone,false,true,FIND_ALL_PAGES__NEWEST,$page_type);
		ksort($pages);
		if ($permissions_needed)
		{
			$zone_access=$GLOBALS['SITE_DB']->query_select('group_zone_access',array('*'),array('zone_name'=>$zone));
			$page_access=$GLOBALS['SITE_DB']->query_select('group_page_access',array('*'),array('zone_name'=>$zone));
		}
		foreach ($pages as $page=>$page_type)
		{
			if (!is_string($page)) $page=strval($page);

			$full_page_type=$page_type;
			$description='';
			if (strpos($full_page_type,'/')!==false) $full_page_type=substr($full_page_type,0,strpos($full_page_type,'/'));
			if (strpos($full_page_type,':')!==false) $full_page_type=substr($full_page_type,0,strpos($full_page_type,':'));
			switch ($full_page_type)
			{
				case 'redirect':
					list(,$redir_zone,$redir_page)=explode(':',$page_type);
					$page_title=html_entity_decode(strip_tags(str_replace(array('<kbd>','</kbd>'),array('"','"'),do_lang('REDIRECT_PAGE_TO',xmlentities($redir_zone),xmlentities($redir_page)))),ENT_QUOTES).': '.(is_string($page)?$page:strval($page));
					break;
				case 'comcode':
				case 'comcode_custom':
					$page_title=do_lang('COMCODE_PAGE').': '.(is_string($page)?$page:strval($page));
					break;
				case 'html':
				case 'html_custom':
					$page_title='HTML: '.$page;
					break;
				case 'modules':
				case 'modules_custom':
					$page_title=do_lang('MODULE').': '.$page;

					$matches=array();
					if (preg_match('#@package\s+(\w+)#',file_get_contents(zone_black_magic_filterer(get_file_base().'/'.$zone.'/pages/'.$page_type.'/'.$page.'.php')),$matches)!=0)
					{
						$package=$matches[1];
						$path=get_file_base().'/sources/hooks/systems/addon_registry/'.$package.'.php';
						if (!file_exists($path))
							$path=get_file_base().'/sources_custom/hooks/systems/addon_registry/'.$package.'.php';
						if (file_exists($path))
						{
							require_lang('zones');
							require_code('zones2');
							$functions=extract_module_functions($path,array('get_description'));
							$description=is_array($functions[0])?call_user_func_array($functions[0][0],$functions[0][1]):eval($functions[0]);
							$description=do_lang('FROM_ADDON',$package,$description);
						}
					}

					break;
				case 'minimodules':
				case 'minimodules_custom':
					$page_title=do_lang('MINIMODULE').': '.$page;
					break;
				default:
					$page_title=do_lang('PAGE').': '.$page;
					break;
			}
			if ($permissions_needed)
			{
				$view_perms='';
				foreach ($groups as $group=>$group_name)
				{
					if (!in_array($group,$admin_groups))
						$view_perms.='g_view_'.strval($group).'="'.(!in_array(array('zone_name'=>$zone,'page_name'=>is_string($page)?$page:strval($page),'group_id'=>$group),$page_access)?'true':'false').'" ';
				}
				$pagelinks=NULL;
				if (substr($page_type,0,7)!='modules')
				{
					$overridables=array();
				} else
				{
					list($overridables,$sp_page)=get_module_overridables($zone,$page);
				}
				$sp_perms='';
				foreach ($overridables as $overridable=>$cat_support)
				{
					$lang_string=do_lang('PT_'.$overridable);
					if (is_array($cat_support)) $lang_string=do_lang($cat_support[1]);
					if ((strlen($lang_string)>20) && (strpos($lang_string,'(')!==false))
						$lang_string=preg_replace('# \([^\)]*\)#','',$lang_string);
					$sp_perms.='sp_'.$overridable.'="'.xmlentities($lang_string).'" ';
					foreach ($groups as $group=>$group_name)
					{
						if (!in_array($group,$admin_groups))
						{
							$override_value=-1;
							foreach ($sp_access[$group] as $test)
							{
								if (($test['specific_permission']==$overridable) && ($test['the_page']==$sp_page))
									$override_value=$test['the_value'];
							}
							if ($override_value!=-1) $sp_perms.='gsp_'.$overridable.'_'.strval($group).'="'.strval($override_value).'" ';
						}
					}
				}
				if (count($overridables)==0) $sp_perms='no_sps="1" ';

				$has_children=($sp_perms!='');

				if (count(array_diff(array_keys($overridables),array('add_highrange_content','add_midrange_content','add_lowrange_content')))!=0) $sp_perms.='inherits_something="1" ';
				$serverid=$zone.':'.(is_string($page)?$page:strval($page));
				echo '<category '.(($serverid==$default)?'selected="yes" ':'').'description="'.xmlentities($description).'" img_func_1="permissions_img_func_1" img_func_2="permissions_img_func_2" highlighted="true" '.$view_perms.$sp_perms.' id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities($page_title).'" has_children="'.($has_children?'true':'false').'" selectable="true">';
			} else
			{
				$extra='';

				if (strpos($page_type,'modules')===0)
				{
					$info=extract_module_info(zone_black_magic_filterer(get_file_base().'/'.$zone.(($zone=='')?'':'/').'pages/'.$page_type.'/'.$page.'.php'));

					if ((!is_null($info)) && (array_key_exists('author',$info)))
						$extra='author="'.xmlentities($info['author']).'" organisation="'.xmlentities($info['organisation']).'" version="'.xmlentities(integer_format($info['version'])).'" ';
				}

				$has_children=false; // For a normal tree, we have children if we have entry points. We have children if we have categories also - but where there are categories there are also entry points
				if (strpos($page_type,'modules')===0)
				{
					$_entrypoints=extract_module_functions_page($zone,$page,array('get_entry_points'));
					if (!is_null($_entrypoints[0]))
					{
						$entrypoints=((is_string($_entrypoints[0])) && (strpos($_entrypoints[0],'::')!==false))?array('whatever'=>1):(is_array($_entrypoints[0])?call_user_func_array($_entrypoints[0][0],$_entrypoints[0][1]):eval($_entrypoints[0])); // The strpos thing is a little hack that allows it to work for base-class derived modules
						if (!is_array($entrypoints)) $entrypoints=array('whatever'=>1);
						$has_children=(array_keys($entrypoints)!=array('!'));
					}
				}

				global $MODULES_ZONES;
				$not_draggable=((array_key_exists($page,$MODULES_ZONES)) || (($zone=='adminzone') && (substr($page,0,6)=='admin_') && (substr($page_type,0,6)=='module')));
				$serverid=$zone.':'.$page;
				echo '<category '.(($serverid==$default)?'selected="yes" ':'').''.$extra.'type="'.xmlentities($page_type).'" description="'.xmlentities($description).'" draggable="'.($not_draggable?'false':'page').'" droppable="'.(($page_type=='zone')?'page':'false').'" id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities($page_title).'" has_children="'.($has_children?'true':'false').'" selectable="true">';
			}
			echo '</category>';
		}
	}
	elseif ((!is_null($page_link)) && ($page_link!='')) // Expanding a module/category
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*)#',$page_link,$matches);
		$zone=$matches[1];
		$page=$matches[2];

		if ($permissions_needed)
		{
			$category_access=$GLOBALS['SITE_DB']->query_select('group_category_access',array('*'));
		}

		$_pagelinks=extract_module_functions_page($zone,$page,array('get_page_links'),array(1,true,$page_link));
		if (!is_null($_pagelinks[0])) // If it's a CMS-supporting module (e.g. downloads)
		{
			$pagelinks=is_array($_pagelinks[0])?call_user_func_array($_pagelinks[0][0],$_pagelinks[0][1]):eval($_pagelinks[0]);
			if ((!is_null($pagelinks[0])) && (!is_null($pagelinks[1]))) // If it's not disabled and does have permissions
			{
				$_overridables=extract_module_functions_page(get_module_zone($pagelinks[1]),$pagelinks[1],array('get_sp_overrides'));
				if (!is_null($_overridables[0])) // If it's a CMS-supporting module with SP overrides
				{
					$overridables=is_array($_overridables[0])?call_user_func_array($_overridables[0][0],$_overridables[0][1]):eval($_overridables[0]);
				} else $overridables=array();
			} else $overridables=array();
		} else $pagelinks=NULL;

		$_pagelinks=extract_module_functions_page($zone,$page,array('extract_page_link_permissions'),array($page_link));
		list($category,$module)=((is_null($_pagelinks[0])) || (strlen($matches[0])==strlen($page_link)))?array('!',''):(is_array($_pagelinks[0])?call_user_func_array($_pagelinks[0][0],$_pagelinks[0][1]):eval($_pagelinks[0])); // If $_pagelinks[0] is NULL then it's an error: extract_page_link_permissions is always there when there are cat permissions

		// Entry points under here
		if ((!$permissions_needed) && ($zone.':'.$page==$page_link))
		{
			$path=zone_black_magic_filterer(filter_naughty($zone).(($zone=='')?'':'/').'pages/modules_custom/'.filter_naughty($page).'.php',true);
			if (!file_exists(get_file_base().'/'.$path)) $path=zone_black_magic_filterer(filter_naughty($zone).'/pages/modules/'.filter_naughty($page).'.php',true);
			require_code($path);
			if (class_exists('Mx_'.filter_naughty_harsh($page)))
			{
				$object=object_factory('Mx_'.filter_naughty_harsh($page));
			} else
			{
				$object=object_factory('Module_'.filter_naughty_harsh($page));
			}
			require_all_lang();
			$entrypoints=$object->get_entry_points();
			foreach ($entrypoints as $entry_point=>$lang_string)
			{
				$serverid=$zone.':'.$page;
				echo '<category '.(($serverid==$default)?'selected="yes" ':'').'type="entry_point" id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).':type='.$entry_point.'" title="'.xmlentities(do_lang('ENTRY_POINT').': '.do_lang($lang_string)).'" has_children="false" selectable="true">';
				echo '</category>';
			}
		}

		// Categories under here
		if (!is_null($pagelinks))
		{
			foreach ($pagelinks[0] as $pagelink)
			{
				$keys=array_keys($pagelink);
				if (is_string($keys[0])) // Map style
				{
					$module_the_name=array_key_exists(3,$pagelinks)?$pagelinks[3]:NULL;
					$category_name=is_string($pagelink['id'])?$pagelink['id']:strval($pagelink['id']);
					$actual_page_link=str_replace('!',$category_name,$pagelinks[2]);
					$title=$pagelink['title'];
					$has_children=$pagelink['child_count']!=0;
				} else // Explicit list style
				{
					$cms_module_name=NULL;
					$module_the_name=$pagelink[1];
					$category_name=is_null($pagelink[2])?'':(is_string($pagelink[2])?$pagelink[2]:strval($pagelink[2]));
					$actual_page_link=$pagelink[0];
					$title=$pagelink[3];
					$has_children=array_key_exists(7,$pagelink)?$pagelink[7]:NULL;
				}
				$cms_module_name=$pagelinks[1];

				if ($category_name==$category) continue;
				if (($module_the_name=='catalogues_category') && ($category_name=='')) continue;

				if (!is_null($cms_module_name))
				{
					$edit_type='_ec';
					if ($module_the_name=='catalogues_catalogue') $edit_type='_ev';
					$actual_edit_link=preg_replace('#^[\w\_]+:[\w\_]+:type=[\w\_]+:(id|catalogue\_name)=#',get_module_zone($cms_module_name).':'.$cms_module_name.':'.$edit_type.':',$actual_page_link);
				} else $actual_edit_link='';
				$actual_page_link=str_replace('_SELF:_SELF',$zone.':'.$page,$actual_page_link); // Support for lazy notation

				if ($permissions_needed)
				{
					$highlight=($module_the_name=='catalogues_catalogue')?'true':'false';

					$view_perms='';
					$sp_perms='';
					if (!is_null($module_the_name))
					{
						foreach ($groups as $group=>$group_name)
						{
							if (!in_array($group,$admin_groups))
								$view_perms.='g_view_'.strval($group).'="'.(in_array(array('module_the_name'=>$module_the_name,'category_name'=>$category_name,'group_id'=>$group),$category_access)?'true':'false').'" ';
						}
//						if (count($pagelinks[0])<40)
						{
							foreach ($overridables as $overridable=>$cat_support)
							{
								$lang_string=do_lang('PT_'.$overridable);
								if (is_array($cat_support)) $lang_string=do_lang($cat_support[1]);
								if ((strlen($lang_string)>20) && (strpos($lang_string,'(')!==false))
									$lang_string=preg_replace('# \([^\)]*\)#','',$lang_string);
								if (is_array($cat_support)) $cat_support=$cat_support[0];
								if ($cat_support==0) continue;

								$sp_perms.='sp_'.$overridable.'="'.xmlentities($lang_string).'" ';
								foreach ($groups as $group=>$group_name)
								{
									if (!in_array($group,$admin_groups))
									{
										$override_value=-1;
										foreach ($sp_access[$group] as $test)
										{
											if (($test['specific_permission']==$overridable) && ($test['the_page']=='') && ($test['category_name']==$category_name) && ($test['module_the_name']==$module_the_name))
												$override_value=$test['the_value'];
										}
										if ($override_value!=-1) $sp_perms.='gsp_'.$overridable.'_'.strval($group).'="'.strval($override_value).'" ';
									}
								}
							}
						}
					}

					if (count(array_diff(array_keys($overridables),array('add_highrange_content','add_midrange_content','add_lowrange_content')))!=0) $sp_perms.='inherits_something="1" ';
					$serverid=$actual_page_link;
					echo '<category '.(($serverid==$default)?'selected="yes" ':'').'img_func_1="permissions_img_func_1" img_func_2="permissions_img_func_2" highlighted="'.$highlight.'" '.$view_perms.$sp_perms.' id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="'.(!is_null($module_the_name)?'true':'false').'">';
				} else
				{
					$serverid=$actual_page_link;
					echo '<category '.(($serverid==$default)?'selected="yes" ':'').'type="category" id="'.uniqid('',true).'" edit="'.xmlentities($actual_edit_link).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities($title).'" has_children="'.($has_children?'true':'false').'" selectable="true">';
				}
				echo '</category>';
			}
		}
	} else

	// EXPANDING THE TREE
	{
		// Start of tree
		if ($permissions_needed)
		{
			$view_perms='';
			foreach ($groups as $group=>$group_name)
			{
				if (!in_array($group,$admin_groups))
					$view_perms.='g_view_'.strval($group).'="true" '; // This isn't actually displayed in the editor
			}
			$sp_perms='';
			$sp_perms_opera_hack='';
			foreach (array_keys($root_perms) as $overridable)
			{
				$sp_perms.='sp_'.$overridable.'="'.xmlentities(do_lang('PT_'.$overridable)).'" ';
				$sp_perms_opera_hack.='<attribute key="'.'sp_'.$overridable.'" value="'.xmlentities(do_lang('PT_'.$overridable)).'" />';
				foreach ($groups as $group=>$group_name)
				{
					if (!in_array($group,$admin_groups))
					{
						$override_value=0;
						foreach ($sp_access[$group] as $test)
						{
							if (($test['specific_permission']==$overridable) && ($test['the_page']=='') && ($test['module_the_name']=='') && ($test['category_name']==''))
								$override_value=$test['the_value'];
						}
						$sp_perms.='gsp_'.$overridable.'_'.strval($group).'="'.strval($override_value).'" ';
						$sp_perms_opera_hack.='<attribute key="'.'gsp_'.$overridable.'_'.strval($group).'" value="'.strval($override_value).'" />';
					}
				}
			}
			echo '<category serverid="_root" expanded="true" title="'.do_lang('ROOT').'" has_children="true" selectable="true" img_func_1="permissions_img_func_1" img_func_2="permissions_img_func_2" id="'.uniqid('',true).'" '.$view_perms.'>';
			echo $sp_perms_opera_hack;
		} else
		{
			echo '<category serverid="_root" expanded="true" title="'.do_lang('ROOT').'" has_children="true" selectable="false" type="root" id="'.uniqid('',true).'">';
		}

		// Zones
		$zones=$GLOBALS['SITE_DB']->query_select('zones',array('zone_title','zone_name','zone_default_page'),NULL,'ORDER BY zone_title',50/*reasonable limit; zone_title is sequential for default zones*/);
		if ($permissions_needed)
		{
			$zone_access=$GLOBALS['SITE_DB']->query_select('group_zone_access',array('*'));
			$page_access=$GLOBALS['SITE_DB']->query_select('group_page_access',array('*'));
		}
		$start_links=(get_param_integer('start_links',0)==1);
		foreach ($zones as $_zone)
		{
			if ((get_option('collapse_user_zones')=='1') && ($_zone['zone_name']=='site')) continue;

			$_zone['text_original']=get_translated_text($_zone['zone_title']);

			$zone=$_zone['zone_name'];
			$zone_title=$_zone['text_original'];

			$serverid=$zone;
			if ($start_links)
			{
				$serverid=$zone.':'/*.$_zone['zone_default_page']*/;
			}
			$pages=find_all_pages_wrap($zone,false,true,FIND_ALL_PAGES__NEWEST);
			if ($permissions_needed)
			{
				$view_perms='';
				foreach ($groups as $group=>$group_name)
				{
					if (!in_array($group,$admin_groups))
						$view_perms.='g_view_'.strval($group).'="'.(in_array(array('zone_name'=>$zone,'group_id'=>$group),$zone_access)?'true':'false').'" ';
				}

				echo '<category '.(($serverid==$default)?'selected="yes" ':'').'img_func_1="permissions_img_func_1" img_func_2="permissions_img_func_2" no_sps="1" highlighted="true" '.$view_perms.' id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities(do_lang('ZONE').': '.$zone_title).'" has_children="'.((count($pages)!=0)?'true':'false').'" selectable="true">';
			} else
			{
				echo '<category '.(($serverid==$default)?'selected="yes" ':'').'type="zone" droppable="page" id="'.uniqid('',true).'" serverid="'.xmlentities($serverid).'" title="'.xmlentities(do_lang('ZONE').': '.$zone_title).'" has_children="'.((count($pages)!=0)?'true':'false').'" selectable="true">';
			}
			echo '</category>';
		}

		echo '</category>';
	}

	// Mark parent cats for pre-expansion
	if ((!is_null($default)) && ($default!='') && (strpos($default,':')!==false))
	{
		list($zone,$page)=explode(':',$default,2);
		echo "\n".'<expand>'.$zone.'</expand>';
		echo "\n".'<expand>'.$zone.':</expand>';
		echo "\n".'<expand>'.$zone.':'.$page.'</expand>';
	}

	echo '</result></request>';
}


