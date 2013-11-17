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
 * @package		calendar
 */

class Hook_content_meta_aware_event
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

			'content_type_label'=>'calendar:EVENT',

			'connection'=>$GLOBALS['SITE_DB'],
			'table'=>'calendar_events',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'e_type',
			'parent_category_meta_aware_type'=>'calendar_type',
			'is_category'=>false,
			'is_entry'=>true,
			'category_field'=>'e_type', // For category permissions
			'category_type'=>'calendar', // For category permissions
			'parent_spec__table_name'=>'calendar_types',
			'parent_spec__parent_name'=>NULL,
			'parent_spec__field_name'=>'id',
			'category_is_string'=>false,

			'title_field'=>'e_title',
			'title_field_dereference'=>true,
			'description_field'=>'e_content',
			'thumb_field'=>NULL,

			'view_page_link_pattern'=>'_SEARCH:calendar:view:_WILD',
			'edit_page_link_pattern'=>'_SEARCH:cms_calendar:_ed:_WILD',
			'view_category_page_link_pattern'=>'_SEARCH:calendar:misc:_WILD',
			'add_url'=>(function_exists('has_submit_permission') && has_submit_permission('mid',get_member(),get_ip_address(),'cms_calendar'))?(get_module_zone('cms_calendar').':cms_calendar:ad'):NULL,
			'archive_url'=>((!is_null($zone))?$zone:get_module_zone('calendar')).':calendar',

			'support_url_monikers'=>true,

			'views_field'=>'e_views',
			'submitter_field'=>'e_submitter',
			'add_time_field'=>'e_add_date',
			'edit_time_field'=>'e_edit_date',
			'date_field'=>'e_add_date',
			'validated_field'=>'validated',

			'seo_type_code'=>'event',

			'feedback_type_code'=>'events',

			'permissions_type_code'=>NULL, // NULL if has no permissions

			'search_hook'=>'calendar',

			'addon_name'=>'calendar',

			'cms_page'=>'cms_calendar',
			'module'=>'calendar',

			'occle_filesystem_hook'=>'calendar',
			'occle_filesystem__is_folder'=>false,

			'rss_hook'=>'calendar',

			'actionlog_regexp'=>'\w+_EVENT',

			'supports_privacy'=>true,
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
		require_code('calendar');

		return render_event_box($row,$zone,$give_context,$guid);
	}

}
