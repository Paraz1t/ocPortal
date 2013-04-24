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
 * @package		ocf_forum
 */

class Hook_choose_forum
{

	/**
	 * Standard modular run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by Javascript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
	function run($id,$options,$default=NULL)
	{
		require_code('ocf_forums');
		require_code('ocf_forums2');

		$compound_list=array_key_exists('compound_list',$options)?$options['compound_list']:false;
		$addable_filter=array_key_exists('addable_filter',$options)?($options['addable_filter']):false;
		$stripped_id=($compound_list?preg_replace('#,.*$#','',$id):$id);

		$tree=ocf_get_forum_tree_secure(NULL,is_null($id)?NULL:intval($id),false,NULL,'',NULL,NULL,$compound_list,1,true);
		$out='';

		if (!has_actual_page_access(NULL,'forumview')) $tree=$compound_list?array(array(),''):array();

		$categories=collapse_2d_complexity('id','c_title',$GLOBALS['FORUM_DB']->query_select('f_forum_groupings',array('id','c_title')));

		if ($compound_list)
		{
			list($tree,)=$tree;
		}

		foreach ($tree as $t)
		{
			if ($compound_list)
			{
				$_id=$t['compound_list'];
			} else
			{
				$_id=strval($t['id']);
			}

			if ($stripped_id===$_id) continue; // Possible when we look under as a root
			$title=$t['title'];
			$description=array_key_exists($t['group'],$categories)?$categories[$t['group']]:'';
			$has_children=($t['child_count']!=0);
			$selectable=((!$addable_filter) || ocf_may_post_topic($t['id']));

			$tag='category'; // category
			$out.='<'.$tag.' id="'.$_id.'" title="'.xmlentities($title).'" description="'.xmlentities($description).'" has_children="'.($has_children?'true':'false').'" selectable="'.($selectable?'true':'false').'"></'.$tag.'>';
		}

		// Mark parent cats for pre-expansion
		if ((!is_null($default)) && ($default!=''))
		{
			$cat=intval($default);
			while (!is_null($cat))
			{
				$out.='<expand>'.strval($cat).'</expand>';
				$cat=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums','f_parent_forum',array('id'=>$cat));
			}
		}

		$tag='result'; // result
		return '<'.$tag.'>'.$out.'</'.$tag.'>';
	}

	/**
	 * Standard modular simple function for ajax-tree hooks. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return tempcode		The nice list
	 */
	function simple($id,$options,$it=NULL)
	{
		require_code('ocf_forums2');

		$compound_list=array_key_exists('compound_list',$options)?$options['compound_list']:false;
		$addable_filter=array_key_exists('addable_filter',$options)?($options['addable_filter']):false;

		require_code('ocf_forums');
		require_code('ocf_forums2');

		$tree=ocf_get_forum_tree_secure(NULL,NULL,true,is_null($it)?NULL:array(intval($it)),'',NULL,NULL,$compound_list,NULL,false);

		if ($compound_list)
		{
			list($tree,)=$tree;
		}

		return $tree;
	}

}


