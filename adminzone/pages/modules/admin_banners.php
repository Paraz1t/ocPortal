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
 * @package		banners
 */

/**
 * Module page class.
 */
class Module_admin_banners
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
		return array(
			'misc'=>array('BANNER_STATISTICS','menu/cms/banners'),
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

		require_lang('banners');

		if ($type=='misc')
		{
			$also_url=build_url(array('page'=>'cms_banners'),get_module_zone('cms_banners'));
			attach_message(do_lang_tempcode('menus:ALSO_SEE_ADMIN',escape_html($also_url->evaluate())),'inform');

			$this->title=get_screen_title('BANNER_STATISTICS');
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
		require_lang('banners');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->banner_statistics();

		return new ocp_tempcode();
	}

	/**
	 * The UI to show a results table of banner details/statistics.
	 *
	 * @return tempcode		The UI
	 */
	function banner_statistics()
	{
		check_privilege('view_anyones_banner_stats');

		$id=get_param_integer('id',-1);
		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('name'=>do_lang_tempcode('NAME'),'add_date'=>do_lang_tempcode('DATE_TIME'));
		$test=explode(' ',get_param('sort','name ASC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');

		$_sum=$GLOBALS['SITE_DB']->query_select_value('banners','SUM(views_from)');
		$has_banner_network=$_sum!=0.0;

		require_code('templates_results_table');
		$field_titles_arr=array(do_lang_tempcode('NAME'),do_lang_tempcode('TYPE'),do_lang_tempcode('_BANNER_TYPE'));
		if ($has_banner_network)
			$field_titles_arr=array_merge($field_titles_arr,array(do_lang_tempcode('BANNER_HITSFROM'),do_lang_tempcode('BANNER_VIEWSFROM')));
		$field_titles_arr=array_merge($field_titles_arr,array(do_lang_tempcode('BANNER_HITSTO'),do_lang_tempcode('BANNER_VIEWSTO'),do_lang_tempcode('BANNER_CLICKTHROUGH'),do_lang_tempcode('IMPORTANCE_MODULUS'),do_lang_tempcode('SUBMITTER'),do_lang_tempcode('ADDED')));
		if (addon_installed('unvalidated')) $field_titles_arr[]=do_lang_tempcode('VALIDATED');
		$fields_title=results_field_title($field_titles_arr,$sortables,'sort',$sortable.' '.$sort_order);

		$rows=$GLOBALS['SITE_DB']->query_select('banners',array('*'),NULL,'',$max,$start);
		$max_rows=$GLOBALS['SITE_DB']->query_select_value('banners','COUNT(*)');
		$fields=new ocp_tempcode();
		foreach ($rows as $myrow)
		{
			$name=hyperlink(build_url(array('page'=>'banners','type'=>'view','source'=>$myrow['name']),get_module_zone('banners')),$myrow['name'],false,true);

			switch ($myrow['the_type'])
			{
				case 0:
					$type=do_lang_tempcode('BANNER_PERMANENT');
					break;
				case 1:
					$type=do_lang_tempcode('_BANNER_HITS_LEFT',do_lang_tempcode('BANNER_CAMPAIGN'),make_string_tempcode(integer_format($myrow['campaign_remaining'])));
					break;
				case 2:
					$type=do_lang_tempcode('BANNER_DEFAULT');
					break;
			}

			$banner_type=$myrow['b_type'];
			if ($banner_type=='') $banner_type=do_lang('GENERAL');

			$date_and_time=get_timezoned_date($myrow['add_date']);

			$hits_from=integer_format($myrow['hits_from']);
			$views_from=integer_format($myrow['views_from']);
			$hits_to=($myrow['site_url']=='')?do_lang_tempcode('CANT_TRACK'):protect_from_escaping(escape_html(integer_format($myrow['hits_to'])));
			$views_to=($myrow['site_url']=='')?do_lang_tempcode('CANT_TRACK'):protect_from_escaping(escape_html(integer_format($myrow['views_to'])));

			if ($myrow['views_to']!=0)
				$click_through=protect_from_escaping(escape_html(integer_format(intval(round(100.0*($myrow['hits_to']/$myrow['views_to']))))));
			else $click_through=do_lang_tempcode('NA_EM');

			$username=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($myrow['submitter']);

			$importance_modulus=$myrow['importance_modulus'];

			$validated=($myrow['validated']==1)?do_lang('YES'):do_lang('NO');
			if ((!is_null($myrow['expiry_date'])) && ($myrow['expiry_date']<time()))
				$validated.=do_lang('BUT_EXPIRED');

			$result=array(escape_html($name),escape_html($type),escape_html($banner_type));
			if ($has_banner_network)
				$result=array_merge($result,array(escape_html($hits_from),escape_html($views_from)));
			$result=array_merge($result,array(escape_html($hits_to),escape_html($views_to),escape_html($click_through),escape_html(strval($importance_modulus)),$username,escape_html($date_and_time)));
			if (addon_installed('unvalidated'))
				$result[]=escape_html($validated);

			$fields->attach(results_entry($result,true));
		}

		$table=results_table(do_lang_tempcode('BANNERS'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort');

		$tpl=do_template('RESULTS_TABLE_SCREEN',array('_GUID'=>'c9270fd515e76918a37edf3f573c6da2','RESULTS_TABLE'=>$table,'TITLE'=>$this->title));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl);
	}

}

