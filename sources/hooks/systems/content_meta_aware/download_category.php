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
 * @package		downloads
 */

class Hook_content_meta_aware_download_category
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

			'content_type_label'=>'downloads:DOWNLOAD_CATEGORY',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'download_categories',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'parent_id',
			'parent_category_meta_aware_type'=>'download_category',
			'is_category'=>true,
			'is_entry'=>false,
			'category_field'=>'parent_id', // For category permissions
			'category_type'=>'downloads', // For category permissions
			'parent_spec__table_name'=>'download_categories',
			'parent_spec__parent_name'=>'parent_id',
			'parent_spec__field_name'=>'id',
			'category_is_string'=>false,

			'title_field'=>'category',
			'title_field_dereference'=>true,
			'description_field'=>'description',
			'thumb_field'=>'rep_image',

			'view_page_link_pattern'=>'_SEARCH:downloads:misc:_WILD',
			'edit_page_link_pattern'=>'_SEARCH:cms_downloads:_ec:_WILD',
			'view_category_page_link_pattern'=>'_SEARCH:downloads:misc:_WILD',
			'add_url'=>(function_exists('has_submit_permission') && has_submit_permission('mid',get_member(),get_ip_address(),'cms_downloads'))?(get_module_zone('cms_downloads').':cms_downloads:ac:parent_id=!'):NULL,
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('downloads')).':downloads',

			'support_url_monikers'=>true,

			'views_field'=>NULL,
			'submitter_field'=>NULL,
			'add_time_field'=>'add_date',
			'edit_time_field'=>NULL,
			'date_field'=>'add_date',
			'validated_field'=>NULL,

			'seo_type_code'=>'downloads_category',

			'feedback_type_code'=>NULL,

			'permissions_type_code'=>'downloads', // NULL if has no permissions

			'search_hook'=>'download_categories',

			'addon_name'=>'downloads',

			'cms_page'=>'cms_downloads',
			'module'=>'downloads',

			'occle_filesystem_hook'=>'downloads',
			'occle_filesystem__is_folder'=>true,

			'rss_hook'=>NULL,

			'actionlog_regexp'=>'\w+_DOWNLOAD_CATEGORY',
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
		require_code('downloads');

		return render_download_category_box($row,$zone,$give_context,$include_breadcrumbs,is_null($root)?NULL:intval($root),$attach_to_url_filter,$guid);
	}

}
