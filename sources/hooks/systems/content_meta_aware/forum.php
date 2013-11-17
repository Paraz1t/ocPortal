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
 * @package		ocf_forum
 */

class Hook_content_meta_aware_forum
{

	/**
	 * Standard modular info function for content hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		if (get_forum_type()!='ocf') return NULL;

		return array(
			'supports_custom_fields'=>true,

			'content_type_label'=>'ocf:FORUM',

			'connection'=>$GLOBALS['FORUM_DB'],
			'table'=>'f_forums',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'f_parent_forum',
			'parent_category_meta_aware_type'=>'forum',
			'is_category'=>true,
			'is_entry'=>false,
			'category_field'=>'id', // For category permissions
			'category_type'=>'forums', // For category permissions
			'parent_spec__table_name'=>'f_forums',
			'parent_spec__parent_name'=>'f_parent_forum',
			'parent_spec__field_name'=>'id',
			'category_is_string'=>false,

			'title_field'=>'f_name',
			'title_field_dereference'=>false,
			'description_field'=>'f_description',
			'thumb_field'=>NULL,

			'view_page_link_pattern'=>'_SEARCH:forumview:misc:_WILD',
			'edit_page_link_pattern'=>'_SEARCH:admin_ocf_forums:_ec:_WILD',
			'view_category_page_link_pattern'=>'_SEARCH:forumview:misc:_WILD',
			'add_url'=>'',
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('forumview')).':forumview',

			'support_url_monikers'=>true,

			'views_field'=>NULL,
			'submitter_field'=>NULL,
			'add_time_field'=>NULL,
			'edit_time_field'=>NULL,
			'date_field'=>NULL,
			'validated_field'=>NULL,

			'seo_type_code'=>NULL,

			'feedback_type_code'=>NULL,

			'permissions_type_code'=>'forums', // NULL if has no permissions

			'search_hook'=>NULL,

			'addon_name'=>'ocf_forum',

			'cms_page'=>'topics',
			'module'=>'forumview',

			'occle_filesystem_hook'=>'forums',
			'occle_filesystem__is_folder'=>true,

			'rss_hook'=>NULL,

			'actionlog_regexp'=>'\w+_FORUM',
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
		require_code('ocf_forums');

		return render_forum_box($row,$zone,$give_context,$include_breadcrumbs,is_null($root)?NULL:intval($root),$guid);
	}

}
