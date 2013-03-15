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
 * @package		core
 */

class Hook_block
{

	/**
	 * Standard modular run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
	function run()
	{
		$sup=get_param('block_map_sup','',true);
		$_map=get_param('block_map',false,true);
		if ($sup!='') $_map.=','.$sup;

		require_code('blocks');

		$map=block_params_str_to_arr($_map);

		if (!array_key_exists('block',$map)) return new ocp_tempcode();

		$auth_key=get_param_integer('auth_key');

		// Check permissions
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('temp_block_permissions','p_block_constraints',array('p_session_id'=>get_session_id(),'id'=>$auth_key));
		if ((is_null($test)) || (!block_signature_check(block_params_str_to_arr($test),$map)))
		{
			require_lang('permissions');
			return paragraph(do_lang_tempcode('ACCESS_DENIED__ACCESS_DENIED',escape_html($map['block'])));
		}

		// Cleanup
		if (!$GLOBALS['SITE_DB']->table_is_locked('temp_block_permissions'))
			$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'temp_block_permissions WHERE p_time<'.strval(60*60*intval(get_option('session_expiry_time'))));

		// Return block snippet
		global $CSSS,$JAVASCRIPTS;
		$CSSS=array();
		$JAVASCRIPTS=array();
		$out=new ocp_tempcode();
		$out->attach(symbol_tempcode('CSS_TEMPCODE'));
		$out->attach(symbol_tempcode('JS_TEMPCODE'));
		$out->attach(do_block($map['block'],$map));
		return $out;
	}

}

