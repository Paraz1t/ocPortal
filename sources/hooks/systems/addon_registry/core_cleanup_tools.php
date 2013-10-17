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
 * @package		core_cleanup_tools
 */

class Hook_addon_registry_core_cleanup_tools
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Behind-the-scenes maintenance tasks.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
			'tut_cleanup',
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array()
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources/hooks/systems/config/is_on_block_cache.php',
			'sources/hooks/systems/config/is_on_comcode_page_cache.php',
			'sources/hooks/systems/config/is_on_lang_cache.php',
			'sources/hooks/systems/config/is_on_template_cache.php',
			'data/modules/admin_cleanup/.htaccess',
			'data/modules/admin_cleanup/index.html',
			'sources/hooks/systems/addon_registry/core_cleanup_tools.php',
			'CLEANUP_ORPHANED_UPLOADS.tpl',
			'CLEANUP_COMPLETED_SCREEN.tpl',
			'CLEANUP_PAGE_STATS.tpl',
			'adminzone/pages/modules/admin_cleanup.php',
			'themes/default/images/bigicons/cleanup.png',
			'sources/hooks/systems/cleanup/comcode.php',
			'lang/EN/cleanup.ini',
			'themes/default/images/pagepics/cleanup.png',
			'sources/hooks/systems/cleanup/.htaccess',
			'sources/hooks/systems/cleanup/admin_theme_images.php',
			'sources/hooks/systems/cleanup/blocks.php',
			'sources/hooks/systems/cleanup/broken_urls.php',
			'sources/hooks/systems/cleanup/image_thumbs.php',
			'sources/hooks/systems/cleanup/index.html',
			'sources/hooks/systems/cleanup/language.php',
			'sources/hooks/systems/cleanup/mysql.php',
			'sources/hooks/systems/cleanup/orphaned_lang_strings.php',
			'sources/hooks/systems/cleanup/orphaned_uploads.php',
			'sources/hooks/systems/cleanup/templates.php',
			'sources/hooks/systems/cleanup/criticise_mysql_fields.php',
			'sources/hooks/systems/cleanup/page_backups.php',
			'sources/hooks/systems/tasks/find_broken_urls.php',
			'sources/hooks/systems/tasks/find_orphaned_lang_strings.php',
			'sources/hooks/systems/tasks/find_orphaned_uploads.php',
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'CLEANUP_COMPLETED_SCREEN.tpl'=>'administrative__cleanup_completed_screen',
			'CLEANUP_ORPHANED_UPLOADS.tpl'=>'administrative__cleanup_completed_screen',
			'CLEANUP_PAGE_STATS.tpl'=>'administrative__cleanup_completed_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__cleanup_completed_screen()
	{
		require_lang('stats');
		$url=array();
		foreach (placeholder_array() as $v)
		{
			$url[]=array(
				'URL'=>placeholder_url()
			);
		}

		$message=do_lorem_template('CLEANUP_ORPHANED_UPLOADS', array(
			'FOUND'=>$url
		));
		$message->attach(do_lorem_template('CLEANUP_PAGE_STATS', array(
			'STATS_BACKUP_URL'=>placeholder_url()
		)));
		return array(
			lorem_globalise(do_lorem_template('CLEANUP_COMPLETED_SCREEN', array(
				'TITLE'=>lorem_title(),
				'MESSAGES'=>$message
			)), NULL, '', true)
		);
	}
}
