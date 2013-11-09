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

class Hook_sitemap_page extends Hook_sitemap_base
{
	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		$matches=array();
		if (preg_match('#^([^:]*):([^:]*)(:misc|$)#',$pagelink,$matches)!=0)
		{
			$zone=$matches[1];
			$page=$matches[2];

			require_code('site');
			$details=_request_page($page,$zone);
			if (strpos($details[0],'COMCODE')===false) // We don't handle Comcode pages here, comcode_page handles those
			{
				return SITEMAP_NODE_HANDLED;
			}
		}
		return SITEMAP_NODE_NOT_HANDLED;
	}

	/**
	 * Find details for this node.
	 *
	 * @param  ?array			Faked database row (NULL: derive).
	 * @param  ID_TEXT		The zone.
	 * @param  ID_TEXT		The page.
	 * @return ?array			Faked database row (NULL: derive).
	 */
	protected function _load_row($row,$zone,$page)
	{
		if ($row===NULL) // Find from page grouping
		{
			$hooks=find_all_hooks('systems','page_groupings');
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/systems/page_groupings/'.$hook);

				$ob=object_factory('Hook_page_groupings_'.$hook);
				$links=$ob->run();
				foreach ($links as $link)
				{
					if ($link[2][2]==$zone && $link[2][0]==$page)
					{
						$title=$link[3];
						$icon=$link[1];
						$row=array($title,$icon,NULL);
						break 2;
					}
				}
			}

			if ($row===NULL) // Get from stored menus?
			{
				$test=$GLOBALS['SITE_DB']->query_select('menu_items',array('i_caption','i_theme_img_code','i_caption_long'),array('i_url'=>$zone.':'.$page),'',1);
				if (array_key_exists(0,$test))
				{
					$title=get_translated_text($test[0]['i_caption']);
					$icon=$test[0]['i_theme_img_code'];
					$description=get_translated_text($test[0]['i_caption_long']);
					$row=array($title,$icon,$description);
				}
			}
		}
		return $row;
	}

	/**
	 * Extend the node structure with added details from our row data (if we have it).
	 *
	 * @param  ?array			Structure.
	 * @param  ?array			Faked database row (NULL: we don't have row data).
	 */
	protected function _ameliorate_with_row(&$struct,&$row)
	{
		if ($row!==NULL)
		{
			$title=$row[0];
			$icon=$row[1];
			$description=$row[2];

			$struct['title']=$title;

			$struct['extra_meta']['description']=($description===NULL)?NULL:$description;

			$struct['extra_meta']['image']=($icon===NULL)?NULL:find_theme_image('icons/24x24/'.$icon);
			$struct['extra_meta']['image_2x']=($icon===NULL)?NULL:find_theme_image('icons/48x48/'.$icon);
		}
	}

	/**
	 * Find details of a position in the Sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the Sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			Node structure (NULL: working via callback / error).
	 */
	function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL,$return_anyway=false)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*)#',$pagelink,$matches);
		if ($matches[1]!=$zone)
		{
			if ($zone=='_SEARCH')
			{
				$zone=$matches[1];
			} else
			{
				warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
			}
		}
		$page=$matches[2];

		$zone_default_page=$GLOBALS['SITE_DB']->query_select_value('zones','zone_default_page',array('zone_name'=>$zone));

		require_code('site');
		$details=_request_page($page,$zone);

		$path=end($details);

		$row=$this->_load_row($row,$zone,$page);

		$struct=array(
			'title'=>titleify($page),
			'content_type'=>'page',
			'content_id'=>$zone.':'.$page,
			'pagelink'=>$pagelink,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>NULL,
				'image_2x'=>NULL,
				'add_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filectime(get_file_base().'/'.$path):NULL,
				'edit_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filemtime(get_file_base().'/'.$path):NULL,
				'submitter'=>NULL,
				'views'=>NULL,
				'rating'=>NULL,
				'meta_keywords'=>NULL,
				'meta_description'=>NULL,
				'categories'=>NULL,
				'validated'=>NULL,
				'db_row'=>(($meta_gather & SITEMAP_GATHER_DB_ROW)!=0)?$row:NULL,
			),
			'permissions'=>array(
				array(
					'type'=>'zone',
					'zone_name'=>$zone,
					'is_owned_at_this_level'=>false,
				),
				array(
					'type'=>'page',
					'zone_name'=>$zone,
					'page_name'=>$page,
					'is_owned_at_this_level'=>true,
				),
			),
			'has_possible_children'=>false,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>($zone_default_page==$page)?SITEMAP_IMPORTANCE_ULTRA:SITEMAP_IMPORTANCE_HIGH,
			'sitemap_refreshfreq'=>($zone_default_page==$page)?'daily':'weekly',

			'permission_page'=>NULL,
		);

		switch ($details[0])
		{
			case 'HTML':
			case 'HTML_CUSTOM':
				$page_contents=file_get_contents(get_file_base().'/'.$path);
				$matches=array();
				if (preg_match('#\<title[^\>]*\>#',$page_contents,$matches)!=0)
				{
					$start=strpos($page_contents,$matches[0])+strlen($matches[0]);
					$end=strpos($page_contents,'</title>',$start);
					$struct['title']=make_string_tempcode(substr($page_contents,$start,$end-$start));
				}
				break;

			case 'MODULES':
			case 'MODULES_CUSTOM':
				require_all_lang();
				$test=do_lang('MODULE_TRANS_NAME_'.$page,NULL,NULL,NULL,NULL,false);
				if ($test!==NULL)
					$struct['title']=do_lang_tempcode('MODULE_TRANS_NAME_'.$page);
				break;
		}

		$this->_ameliorate_with_row($struct,$row);

		if (!$this->_check_node_permissions($struct)) return NULL;

		$call_struct=true;

		$children=array();

		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
		{
			// Look for virtual nodes to put under this
			$child_sitemap_hook=mixed();
			$hooks=find_all_hooks('systems','sitemap');
			foreach (array_keys($hooks) as $_hook)
			{
				require_code('hooks/systems/sitemap/'.$_hook);
				$ob=object_factory('Hook_sitemap_'.$_hook);
				if ($ob->is_active())
				{
					$is_handled=$ob->handles_pagelink($pagelink);
					if ($is_handled==SITEMAP_NODE_HANDLED_VIRTUALLY)
					{
						$is_virtual=($is_handled==SITEMAP_NODE_HANDLED_VIRTUALLY);
						$child_sitemap_hook=$ob;
						$struct['permission_page']=$child_sitemap_hook->get_permission_page($pagelink);
						$struct['has_possible_children']=true;

						$virtual_child_nodes=$ob->get_virtual_nodes($pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,true);
						if (is_null($virtual_child_nodes)) $virtual_child_nodes=array();
						foreach ($virtual_child_nodes as $child_node)
						{
							if ((preg_match('#^'.preg_quote($pagelink,'#').':misc(:[^:=]*$|$)#',$child_node['pagelink'])!=0) && (!$require_permission_support))
							{
								//$struct=$child_node; // Put as container instead		Actually this breaks the re-entryable requirement
								$call_struct=false; // Already been called in get_virtual_nodes
							} else
							{
								if ($callback!==NULL)
									$children[$child_node['pagelink']]=$child_node;
							}
						}
					}
				}
			}

			// Look for entry points to put under this
			if (($details[0]=='MODULES' || $details[0]=='MODULES_CUSTOM') && (!$require_permission_support))
			{
				$functions=extract_module_functions($path,array('get_entry_points'),array(/*$check_perms=*/true,/*$member_id=*/NULL,/*$support_crosslinks=*/true));
				if (!is_null($functions[0]))
				{
					$entry_points=is_array($functions[0])?call_user_func_array($functions[0][0],$functions[0][1]):eval($functions[0]);

					if (!is_null($entry_points))
					{
						$struct['has_possible_children']=true;

						$entry_point_sitemap_ob=$this->_get_sitemap_object('entry_point');

						if ((isset($entry_points['misc'])) || (isset($entry_points['!'])))
						{
							unset($entry_points['misc']);
						} else
						{
							array_shift($entry_points);
						}

						foreach (array_keys($entry_points) as $entry_point)
						{
							if (strpos($entry_point,':')===false)
							{
								$child_pagelink=$zone.':'.$page.':'.$entry_point;
							} else
							{
								$child_pagelink=preg_replace('#^_SEARCH:#',$zone.':',$entry_point);
							}

							$child_node=$entry_point_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather);
							if ($child_node!==NULL)
								$children[$child_node['pagelink']]=$child_node;
						}
					}
				} else
				{
					$call_struct=true; // Module is disabled
				}
			}
		}

		if ($callback!==NULL && $call_struct)
			call_user_func($callback,$struct);

		// Finalise children
		if ($callback!==NULL)
		{
			foreach ($children as $child_struct)
			{
				call_user_func($callback,$child_struct);
			}
			$children=array();
		}
		$struct['children']=array_values($children);

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}
