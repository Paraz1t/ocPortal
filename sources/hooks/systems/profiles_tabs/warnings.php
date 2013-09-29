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
 * @package		ocf_warnings
 */

class Hook_Profiles_Tabs_warnings
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return (($member_id_of==$member_id_viewing) || (has_privilege($member_id_viewing,'assume_any_member'))) && ($GLOBALS['FORUM_DB']->query_select_value('f_warnings','COUNT(*)',array('w_member_id'=>$member_id_of,'w_is_warning'=>1))>0);
	}

	/**
	 * Standard modular render function for profile tab hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @param  boolean		Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
	 * @return array			A triple: The tab title, the tab contents, the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing,$leave_to_ajax_if_possible=false)
	{
		require_lang('ocf_warnings');
		$title=do_lang_tempcode('MODULE_TRANS_NAME_warnings');

		$order=80;

		if ($leave_to_ajax_if_possible) return array($title,NULL,$order);

		require_lang('ocf');
		require_css('ocf');

		$warnings=new ocp_tempcode();
		$rows=$GLOBALS['FORUM_DB']->query_select('f_warnings',array('*'),array('w_member_id'=>$member_id_of,'w_is_warning'=>1));
		foreach ($rows as $row)
		{
			$warning_by=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['w_by']);
			$date=get_timezoned_date($row['w_time']);
			if ($row['w_explanation']=='') $row['w_explanation']='?'; else $row['w_explanation']=str_replace("\n",' ',$row['w_explanation']);
			$row['w_explanation_orig']=$row['w_explanation'];
			if (strlen($row['w_explanation'])>30) $row['w_explanation']=substr($row['w_explanation'],0,27).'...';
			$explanation=hyperlink(build_url(array('page'=>'warnings','type'=>'_ed','id'=>$row['id'],'redirect'=>get_self_url(true)),get_module_zone('warnings')),$row['w_explanation'],false,true,$row['w_explanation_orig']);
			$warnings->attach(paragraph(do_lang_tempcode('MEMBER_WARNING',$explanation,$warning_by,array(make_string_tempcode(escape_html($date)))),'treyerhy34y'));
		}

		$content=do_template('OCF_MEMBER_PROFILE_WARNINGS',array('_GUID'=>'fea98858f6bf89f1d9dc3ec995785a39','MEMBER_ID'=>strval($member_id_of),'WARNINGS'=>$warnings));

		return array($title,$content,$order);
	}

}


