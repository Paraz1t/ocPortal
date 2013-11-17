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
 * @package		core_feedback_features
 */

/**
 * Module page class.
 */
class Module_admin_trackbacks
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		if ((get_option('is_on_trackbacks')=='0') || ($GLOBALS['SITE_DB']->query_select_value_if_there('trackbacks','COUNT(*)',NULL,'',true)==0)) return NULL;

		return array(
			'misc'=>array('MANAGE_TRACKBACKS','menu/adminzone/audit/trackbacks'),
		);
	}

	var $title;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		$type=get_param('type','misc');

		require_lang('trackbacks');

		set_helper_panel_text(comcode_lang_string('DOC_TRACKBACKS'));

		if ($type=='misc')
		{
			$this->title=get_screen_title('MANAGE_TRACKBACKS');
		}

		if ($type=='delete')
		{
			$this->title=get_screen_title('DELETE_TRACKBACKS');
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$type=get_param('type','misc');

		if ($type=='misc') return $this->choose();
		if ($type=='delete') return $this->delete_trackbacks();

		return new ocp_tempcode();
	}

	/**
	 * The UI to delete trackbacks.
	 *
	 * @return tempcode		The UI
	 */
	function choose()
	{
		$trackback_rows=$GLOBALS['SITE_DB']->query_select('trackbacks',array('*'),NULL,'ORDER BY id DESC',1000);

		$trackbacks='';
		foreach ($trackback_rows as $value)
		{
			$trackbacks.=static_evaluate_tempcode(do_template('TRACKBACK',array(
				'_GUID'=>'eb005ff4cf387e4c18cbc862c38555e3',
				'ID'=>strval($value['id']),
				'TIME_RAW'=>strval($value['trackback_time']),
				'TIME'=>get_timezoned_date($value['trackback_time']),
				'URL'=>$value['trackback_url'],
				'TITLE'=>$value['trackback_title'],
				'EXCERPT'=>$value['trackback_excerpt'],
				'NAME'=>$value['trackback_name'],
			)));
		}

		return do_template('TRACKBACK_DELETE_SCREEN',array('_GUID'=>'51f7e4c1976bcaf120758d2c86771289','TITLE'=>$this->title,'TRACKBACKS'=>$trackbacks,'LOTS'=>count($trackback_rows)==1000));
	}

	/**
	 * The actualiser to delete trackbacks.
	 *
	 * @return tempcode		The UI
	 */
	function delete_trackbacks()
	{
		foreach ($_POST as $key=>$val)
		{
			if (!is_string($val)) continue;

			if (substr($key,0,10)=='trackback_')
			{
				$id=intval(substr($key,10));
				switch ($val)
				{
					case '2':
						if (addon_installed('securitylogging'))
						{
							$trackback_ip=$GLOBALS['SITE_DB']->query_select_value_if_there('trackbacks','trackback_ip',array('id'=>$id));
							if (is_null($trackback_ip)) break;
							require_code('failure');
							add_ip_ban($trackback_ip,do_lang('TRACKBACK_SPAM'));
							syndicate_spammer_report($trackback_ip,'','',do_lang('TRACKBACK_SPAM'),true);
						}
						// Intentionally no 'break' line below
					case '1':
						$GLOBALS['SITE_DB']->query_delete('trackbacks',array('id'=>$id),'',1);
						break;
					// (zero is do nothing)
				}
			}
		}

		// Show it worked / Refresh
		$text=do_lang_tempcode('SUCCESS');
		$url=get_param('redirect',NULL);
		if (is_null($url))
		{
			$_url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
			$url=$_url->evaluate();
		}
		return redirect_screen($this->title,$url,$text);
	}

}

