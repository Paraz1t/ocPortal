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
 * @package		ocf_warnings
 */

class Hook_addon_registry_ocf_warnings
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
		return 'Member warnings and punishment.';
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
			'sources/hooks/systems/addon_registry/ocf_warnings.php',
			'site/pages/modules/warnings.php',
			'OCF_SAVED_WARNING.tpl',
			'OCF_WARNING_HISTORY_SCREEN.tpl',
			'lang/EN/ocf_warnings.ini',
			'site/warnings.php',
			'sources/hooks/systems/profiles_tabs/warnings.php',
			'OCF_MEMBER_PROFILE_WARNINGS.tpl'
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
			'OCF_SAVED_WARNING.tpl'=>'ocf_saved_warning',
			'OCF_WARNING_HISTORY_SCREEN.tpl'=>'administrative__ocf_warning_history_screen',
			'OCF_MEMBER_PROFILE_WARNINGS.tpl'=>'ocf_member_profile_warnings'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ocf_member_profile_warnings()
	{
		$tab_content=do_lorem_template('OCF_MEMBER_PROFILE_WARNINGS', array(
			'MEMBER_ID'=>placeholder_id(),
			'WARNINGS'=>lorem_phrase()
		));
		return array(
			lorem_globalise($tab_content, NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ocf_saved_warning()
	{
		require_css('ocf');
		return array(
			lorem_globalise(do_lorem_template('OCF_SAVED_WARNING', array(
				'MESSAGE'=>lorem_phrase(),
				'EXPLANATION'=>lorem_phrase(),
				'TITLE'=>lorem_word(),
				'DELETE_LINK'=>placeholder_link()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__ocf_warning_history_screen()
	{
		require_lang('ocf');
		require_css('ocf');
		return array(
			lorem_globalise(do_lorem_template('OCF_WARNING_HISTORY_SCREEN', array(
				'TITLE'=>lorem_title(),
				'MEMBER_ID'=>placeholder_id(),
				'EDIT_PROFILE_URL'=>placeholder_url(),
				'VIEW_PROFILE_URL'=>placeholder_url(),
				'ADD_WARNING_URL'=>placeholder_url(),
				'RESULTS_TABLE'=>placeholder_table()
			)), NULL, '', true)
		);
	}

}
