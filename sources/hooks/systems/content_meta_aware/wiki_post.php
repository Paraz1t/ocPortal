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
 * @package		wiki
 */

class Hook_content_meta_aware_wiki_post
{

	/**
	 * Standard modular info function for content hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		return array(
			'supports_custom_fields'=>true,

			'content_type_label'=>'wiki:WIKI_POST',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'wiki_posts',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'page_id',
			'parent_category_meta_aware_type'=>'wiki_page',
			'is_category'=>false,
			'is_entry'=>true,
			'category_field'=>'page_id', // For category permissions
			'category_type'=>'wiki_page', // For category permissions
			'parent_spec__table_name'=>'wiki_children',
			'parent_spec__parent_name'=>'parent_id',
			'parent_spec__field_name'=>'child_id',
			'category_is_string'=>false,

			'title_field'=>'the_message',
			'title_field_dereference'=>true,
			'description_field'=>'the_message',
			'thumb_field'=>NULL,

			'view_page_link_pattern'=>'_SEARCH:wiki:find_post:_WILD',
			'edit_page_link_pattern'=>'_SEARCH:wiki:post:post_id=_WILD',
			'view_category_page_link_pattern'=>'_SEARCH:wiki:misc:_WILD',
			'add_url'=>(function_exists('has_submit_permission') && has_submit_permission('low',get_member(),get_ip_address(),'wiki'))?(get_module_zone('wiki').':wiki:add_post'):NULL,
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('wiki')).':wiki',

			'support_url_monikers'=>false,

			'views_field'=>'wiki_views',
			'submitter_field'=>'member_id',
			'add_time_field'=>'date_and_time',
			'edit_time_field'=>'edit_date',
			'date_field'=>'date_and_time',
			'validated_field'=>'validated',

			'seo_type_code'=>NULL,

			'feedback_type_code'=>NULL,

			'permissions_type_code'=>NULL, // NULL if has no permissions

			'search_hook'=>'wiki_posts',

			'addon_name'=>'wiki',

			'cms_page'=>'wiki',
			'module'=>'wiki',

			'occle_filesystem_hook'=>'wiki',
			'occle_filesystem__is_folder'=>false,

			'rss_hook'=>NULL,

			'actionlog_regexp'=>'\w+_WIKI_POST',
		);

	}

	/**
	 * Standard modular run function for content hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @param  ?ID_TEXT	Virtual root to use (NULL: none)
	 * @param  boolean	Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
	 * @param  ID_TEXT	Overridden GUID to send to templates (blank: none)
	 * @return tempcode	Results
	 */
	function run($row,$zone,$give_context=true,$include_breadcrumbs=true,$root=NULL,$attach_to_url_filter=false,$guid='')
	{
		require_code('wiki');

		return render_wiki_post_box($row,$zone,$give_context,$include_breadcrumbs,is_null($root)?NULL:intval($root),$guid);
	}

}
