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

class Hook_sitemap_zone extends Hook_sitemap_base
{
	/**
	 * Get the permission page that nodes matching $pagelink in this hook are tied to.
	 * The permission page is where privileges may be overridden against.
	 *
	 * @param  string			The page-link
	 * @return ?ID_TEXT		The permission page (NULL: none)
	 */
	function get_permission_page($pagelink)
	{
		return 'cms_comcode_pages';
	}

	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		if (preg_match('#^([^:]*):$#',$pagelink)!=0)
		{
			return SITEMAP_NODE_HANDLED;
		}
		return SITEMAP_NODE_NOT_HANDLED;
	}

	/**
	 * Convert a page-link to a category ID and category permission module type.
	 *
	 * @param  ID_TEXT		The page-link
	 * @return ?array			The pair (NULL: permission modules not handled)
	 */
	function extract_child_pagelink_permission_pair($pagelink)
	{
		$matches=array();
		preg_match('#^([^:]*):$#',$pagelink,$matches);
		$zone=$matches[1];

		return array($zone,'zone_page');
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
		preg_match('#^([^:]*):#',$pagelink,$matches);
		$zone=$matches[1]; // overrides $zone which we must replace

		if (!isset($row))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('zones',array('zone_title','zone_default_page'),array('zone_name'=>$zone),'',1);
			$row=array($zone,get_translated_text($rows[0]['zone_title']),false,$rows[0]['zone_default_page']);
		}
		$title=$row[1];
		$default_page=$row[3];

		$path=get_custom_file_base().'/'.$zone.'/index.php';
		if (!is_file($path)) $path=get_file_base().'/'.$zone.'/index.php';

		$struct=array(
			'title'=>$title,
			'content_type'=>'zone',
			'content_id'=>$zone,
			'pagelink'=>$pagelink,
			'extra_meta'=>array(
				'description'=>NULL,
				'image'=>NULL,
				'image_2x'=>NULL,
				'add_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filectime($path):NULL,
				'edit_date'=>(($meta_gather & SITEMAP_GATHER_TIMES)!=0)?filemtime($path):NULL,
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
					'is_owned_at_this_level'=>true,
				),
			),
			'has_possible_children'=>true,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>SITEMAP_IMPORTANCE_ULTRA,
			'sitemap_refreshfreq'=>'daily',

			'permission_page'=>$this->get_permission_page($pagelink),
		);

		if (!$this->_check_node_permissions($struct)) return NULL;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		// What page groupings may apply in what zones?
		switch ($zone)
		{
			case 'adminzone':
				$applicable_page_groupings=array(
					'structure',
					'audit',
					'style',
					'setup',
					'tools',
					'security',
				);
				break;

			case '':
				if (get_option('collapse_user_zones')=='0')
				{
					$applicable_page_groupings=array();
				} // else flow on...

			case 'site':
				$applicable_page_groupings=array(
					'pages',
					'rich_content',
					'site_meta',
					'social',
				);
				break;

			case 'cms':
				$applicable_page_groupings=array(
					'cms',
				);
				break;
		}

		// Categories done after node callback, to ensure sensible ordering
		$children=array();
		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
		{
			$root_comcode_pages=collapse_2d_complexity('the_page','p_validated',$GLOBALS['SITE_DB']->query_select('comcode_pages',array('the_page','p_validated'),array('the_zone'=>$zone,'p_parent_page'=>'')));

			// Locate all page groupings and pages in them
			$page_groupings=array();
			$pages_found=array();
			$hooks=find_all_hooks('systems','page_groupings');
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/systems/page_groupings/'.$hook);

				$ob=object_factory('Hook_page_groupings_'.$hook);
				$links=$ob->run();
				foreach ($links as $link)
				{
					list($page_grouping)=$link;
					if (($page_grouping!='') && (in_array($page_grouping,$applicable_page_groupings)))
					{
						if (!isset($page_groupings[$page_grouping]))
							$page_groupings[$page_grouping]=array();
						$page_groupings[$page_grouping][]=$link;
						$pages_found[$link[2][0]]=true;
					}
				}
			}
			ksort($page_groupings);

			// Any left-behind pages?
			$orphaned_pages=array();
			$pages=find_all_pages_wrap($zone,false,/*$consider_redirects=*/true);
			foreach ($pages as $page=>$page_type)
			{
				if (is_integer($page)) $page=strval($page);

				if ((!isset($pages_found[$page])) && ((strpos($page_type,'comcode_page')===false) || (isset($root_comcode_pages[$page]))))
				{
					if ($this->_is_page_omitted_from_sitemap($zone,$page)) continue;

					$orphaned_pages[$page]=$page_type;
				}
			}

			// Do page-groupings
			if (count($page_groupings)>1)
			{
				$comcode_page_sitemap_ob=$this->_get_sitemap_object('comcode_page');
				$page_sitemap_ob=$this->_get_sitemap_object('page');
				$page_grouping_sitemap_ob=$this->_get_sitemap_object('page_grouping');

				foreach (array_keys($page_groupings) as $page_grouping)
				{
					if ($zone=='cms')
					{
						$child_pagelink='cms:cms:'.$page_grouping;
					} else
					{
						$child_pagelink='adminzone:admin:'.$page_grouping; // We don't actually link to this, unless it's one of the ones held in the Admin Zone
					}
					$row=array(); // We may put extra nodes in here, beyond what the page_group knows
					if ($page_grouping=='pages' || $page_grouping=='tools' || $page_grouping=='cms')
					{
						$row=$orphaned_pages;
						$orphaned_pages=array();
					}

					if (($valid_node_types!==NULL) && (!in_array('page_grouping',$valid_node_types)))
					{
						continue;
					}

					$child_node=$page_grouping_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$row);
					if ($child_node!==NULL)
						$children[]=$child_node;
				}

				// Any remaining orphaned pages (we have to tag these on as there was no catch-all page grouping in this zone)
				if (count($orphaned_pages)>0)
				{
					$page_sitemap_ob=$this->_get_sitemap_object('page');
					foreach ($orphaned_pages as $page=>$page_type)
					{
						if (is_integer($page)) $page=strval($page);

						$child_pagelink=$pagelink.':'.$page;

						if (strpos($page_type,'comcode')!==false)
						{
							if (($valid_node_types!==NULL) && (!in_array('comcode_page',$valid_node_types)))
							{
								continue;
							}

							if (($consider_validation) && (isset($root_comcode_pages[$page])) && ($root_comcode_pages[$page]==0))
							{
								continue;
							}

							$child_node=$comcode_page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather);
						} else
						{
							if (($valid_node_types!==NULL) && (!in_array('page',$valid_node_types)))
							{
								continue;
							}

							$child_node=$page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather);
						}
						if ($child_node!==NULL)
							$children[]=$child_node;
					}
				}
			} elseif (count($page_groupings)==1)
			{
				// Show contents of group directly...

				$comcode_page_sitemap_ob=$this->_get_sitemap_object('comcode_page');
				$page_sitemap_ob=$this->_get_sitemap_object('page');

				foreach ($page_groupings[$page_grouping] as $links) // Will only be 1 loop iteration, but this finds us that one easily
				{
					$child_links=array();

					foreach ($links as $link)
					{
						$title=do_lang($link[3]);
						$icon=$link[1];

						$_zone=$link[2][2];
						$page=$link[2][0];
						$child_pagelink=$_zone.':'.$page;
						foreach ($link[2][1] as $key=>$val)
						{
							$child_pagelink.=':'.urlencode($key).'='.urlencode($val);
						}

						$child_links[]=array($title,$child_pagelink,$icon,NULL/*unknown/irrelevant $page_type*/,isset($link[4])?comcode_lang_string($link[4]):NULL);
					}

					foreach ($orphaned_pages as $page=>$page_type)
					{
						if (is_integer($page)) $page=strval($page);

						$child_pagelink=$zone.':'.$page;

						$child_links[]=array(titleify($page),$child_pagelink,NULL,$page_type,NULL);
					}

					// Render children, in title order
					sort_maps_by($child_links,0);
					foreach ($child_links as $child_link)
					{
						$title=$child_link[0];
						$description=$child_link[4];
						$icon=$child_link[2];
						$child_pagelink=$child_link[1];
						$page_type=$child_link[3];

						$child_row=($icon===NULL)?NULL/*we know nothing of relevance*/:array($title,$icon,$description);

						if (strpos($page_type,'comcode')!==false)
						{
							if (($valid_node_types!==NULL) && (!in_array('comcode_page',$valid_node_types)))
							{
								continue;
							}

							if (($consider_validation) && (isset($root_comcode_pages[$page])) && ($root_comcode_pages[$page]==0))
							{
								continue;
							}

							$child_node=$comcode_page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
						} else
						{
							if (($valid_node_types!==NULL) && (!in_array('page',$valid_node_types)))
							{
								continue;
							}

							$child_node=$page_sitemap_ob->get_node($child_pagelink,$callback,$valid_node_types,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
						}
						if ($child_node!==NULL)
							$children[]=$child_node;
					}
				}
			}
		}
		$struct['children']=$children;

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}