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
 * @package		core_comcode_pages
 */

require_code('hooks/systems/sitemap/page');

class Hook_sitemap_comcode_page extends Hook_sitemap_page
{
	protected $content_type='comcode_page';
	protected $screen_type='';

	// If we have a different content type of entries, under this content type
	protected $entry_content_type=NULL;
	protected $entry_sitetree_hook=NULL;

	/**
	 * Find if a page-link will be covered by this node.
	 *
	 * @param  ID_TEXT		The page-link.
	 * @return integer		A SITEMAP_NODE_* constant.
	 */
	function handles_pagelink($pagelink)
	{
		$matches=array();
		if (preg_match('#^([^:]*):([^:]+)$#',$pagelink,$matches)!=0)
		{
			$zone=$matches[1];
			$page=$matches[2];

			$details=$this->_request_page_details($page,$zone);
			if ($details!==false)
			{
				if (strpos($details[0],'COMCODE')!==false)
				{
					return SITEMAP_NODE_HANDLED;
				}
			}
		}
		return SITEMAP_NODE_NOT_HANDLED;
	}

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
	 * Find details of a position in the Sitemap.
	 *
	 * @param  ID_TEXT  		The page-link we are finding.
	 * @param  ?string  		Callback function to send discovered page-links to (NULL: return).
	 * @param  ?array			List of node types we will return/recurse-through (NULL: no limit)
	 * @param  ?integer		How deep to go from the Sitemap root (NULL: no limit).
	 * @param  integer		Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
	 * @param  boolean		Only go so deep as needed to find nodes with permission-support (typically, stopping prior to the entry-level).
	 * @param  ID_TEXT		The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
	 * @param  boolean		Whether to make use of page groupings, to organise stuff with the hook schema, supplementing the default zone organisation.
	 * @param  boolean		Whether to filter out non-validated content.
	 * @param  boolean		Whether to consider secondary categorisations for content that primarily exists elsewhere.
	 * @param  integer		A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
	 * @param  ?array			Database row (NULL: lookup).
	 * @param  boolean		Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
	 * @return ?array			Node structure (NULL: working via callback / error).
	 */
	function get_node($pagelink,$callback=NULL,$valid_node_types=NULL,$child_cutoff=NULL,$max_recurse_depth=NULL,$recurse_level=0,$require_permission_support=false,$zone='_SEARCH',$use_page_groupings=false,$consider_secondary_categories=false,$consider_validation=false,$meta_gather=0,$row=NULL,$return_anyway=false)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*)#',$pagelink,$matches);
		$page=$matches[2];

		$this->_make_zone_concrete($zone,$pagelink);

		$zone_default_page=get_zone_default_page($zone);

		$details=$this->_request_page_details($page,$zone);

		$path=end($details);

		$row=$this->_load_row($row,$zone,$page);

		$struct=array(
			'title'=>make_string_tempcode(escape_html(titleify($page))),
			'content_type'=>'comcode_page',
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
			'has_possible_children'=>true,

			// These are likely to be changed in individual hooks
			'sitemap_priority'=>($zone_default_page==$page)?SITEMAP_IMPORTANCE_ULTRA:SITEMAP_IMPORTANCE_HIGH,
			'sitemap_refreshfreq'=>($zone_default_page==$page)?'daily':'weekly',

			'permission_page'=>$this->get_permission_page($pagelink),
		);

		$this->_ameliorate_with_row($struct,$row);

		// In the DB?
		$got_title=false;
		$db_row=$GLOBALS['SITE_DB']->query_select('cached_comcode_pages a LEFT JOIN '.get_table_prefix().'comcode_pages b ON a.the_zone=b.the_zone AND a.the_page=b.the_page',array('*'),array('a.the_zone'=>$zone,'a.the_page'=>$page),'',1);
		if (isset($db_row[0]))
		{
			if (isset($db_row[0]['cc_page_title']))
			{
				$_title=get_translated_text($db_row[0]['cc_page_title']);
				if ($_title!='')
				{
					$struct['title']=make_string_tempcode(escape_html($_title));
					$got_title=true;
				}
			}
			if (isset($db_row[0]['p_add_date']))
			{
				$struct['extra_meta']['add_date']=$db_row[0]['p_add_date'];
			}
			if (isset($db_row[0]['p_edit_date']))
			{
				$struct['extra_meta']['edit_date']=$db_row[0]['p_edit_date'];
			}
			if (isset($db_row[0]['p_submitter']))
			{
				$struct['extra_meta']['submitter']=$db_row[0]['p_submitter'];
			}
			if (($meta_gather & SITEMAP_GATHER_DB_ROW)!=0)
			{
				$struct['extra_meta']['db_row']=$db_row[0]+(($row===NULL)?array():$struct['extra_meta']['db_row']);
			}
		}
		if (!$got_title)
		{
			$page_contents=file_get_contents(get_file_base().'/'.$path);
			$matches=array();
			if (preg_match('#\[title[^\]]*\]#',$page_contents,$matches)!=0)
			{
				$start=strpos($page_contents,$matches[0])+strlen($matches[0]);
				$end=strpos($page_contents,'[/title]',$start);
				$_title=substr($page_contents,$start,$end-$start);
				if ($_title!='')
					$struct['title']=comcode_to_tempcode($_title,NULL,true);
			}
		}

		if (!$this->_check_node_permissions($struct)) return NULL;

		if ($callback!==NULL)
			call_user_func($callback,$struct);

		// Categories done after node callback, to ensure sensible ordering
		if (($max_recurse_depth===NULL) || ($recurse_level<$max_recurse_depth))
		{
			$children=array();
			if (($valid_node_types===NULL) || (in_array('comcode_page',$valid_node_types)))
			{
				$where=array('p_parent_page'=>$page,'the_zone'=>$zone);
				if ($consider_validation) $where['p_validated']=1;

				$skip_children=false;
				if ($child_cutoff!==NULL)
				{
					$count=$GLOBALS['SITE_DB']->query_select_value('comcode_pages','COUNT(*)',$where);
					if ($count>$child_cutoff) $skip_children=true;
				}

				if (!$skip_children)
				{
					$start=0;
					do
					{
						$child_rows=$GLOBALS['SITE_DB']->query_select('comcode_pages',array('the_page'),$where,'ORDER BY the_page',SITEMAP_MAX_ROWS_PER_LOOP,$start);
						foreach ($child_rows as $child_row)
						{
							$child_pagelink=$zone.':'.$child_row['the_page'];
							$child_node=$this->get_node($child_pagelink,$callback,$valid_node_types,$child_cutoff,$max_recurse_depth,$recurse_level+1,$require_permission_support,$zone,$use_page_groupings,$consider_secondary_categories,$consider_validation,$meta_gather,$child_row);
							if ($child_node!==NULL)
								$children[]=$child_node;
						}
						$start+=SITEMAP_MAX_ROWS_PER_LOOP;
					}
					while (count($child_rows)>0);
				}
			}
			$struct['children']=$children;
		}

		return ($callback===NULL || $return_anyway)?$struct:NULL;
	}
}