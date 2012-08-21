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
 * @package		downloads
 */

class Hook_admin_stats_downloads
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		if (!addon_installed('downloads')) return NULL;

		require_lang('downloads');

		return array(
			array('downloads'=>'SECTION_DOWNLOADS',),
			array('downloads',array('_SELF',array('type'=>'downloads'),'_SELF'),do_lang('SECTION_DOWNLOADS'),('DESCRIPTION_DOWNLOADS_STATISTICS')),
		);
	}


	/**
	 * The UI to show download statistics.
	 *
	 * @param  object			The stats module object
	 * @param  string			The screen type
	 * @return tempcode		The UI
	 */
	function downloads($ob,$type)
	{
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('SITE_STATISTICS'))));

		require_lang('downloads');

		//This will show a plain bar chart with all the downloads listed
		$title=get_screen_title('SECTION_DOWNLOADS');

		// Handle time range
		if (get_param_integer('dated',0)==0)
		{
			$title=get_screen_title('SECTION_DOWNLOADS');

			return $ob->get_between($title,false,NULL,do_lang_tempcode('DOWNLOAD_STATS_RANGE'));
		}
		$time_start=get_input_date('time_start',true);
		$time_end=get_input_date('time_end',true);
		if (!is_null($time_end)) $time_end+=60*60*24-1; // So it is end of day not start

		if ((is_null($time_start)) && (is_null($time_end)))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('id','num_downloads','name'));
		} else
		{
			if (is_null($time_start)) $time_start=0;
			if (is_null($time_end)) $time_end=time();

			$title=get_screen_title('SECTION_DOWNLOADS_RANGE',true,array(escape_html(get_timezoned_date($time_start,false)),escape_html(get_timezoned_date($time_end,false))));

			$rows=$GLOBALS['SITE_DB']->query('SELECT id,num_downloads,name FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'download_downloads WHERE add_date>'.strval($time_start).' AND add_date<'.strval($time_end));
		}

		if (count($rows)<1) return warn_screen($title,do_lang_tempcode('NO_DATA'));

		$downloads=array();
		foreach ($rows as $i=>$row)
		{
			if (!array_key_exists('num_downloads',$row))
			{
				$row['num_downloads']=$GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'download_logging WHERE id='.strval($row['id']));
				$rows[$i]=$row;
			}
			$downloads[get_translated_text($row['name']).' (#'.strval($row['id']).')']=$row['num_downloads'];
		}

		$start=get_param_integer('start',0);
		$max=get_param_integer('max',30);
		$csv=get_param_integer('csv',0)==1;
		if ($csv)
		{
			if (function_exists('set_time_limit')) @set_time_limit(0);
			$start=0; $max=10000;
		}
		$sortables=array('num_downloads'=>do_lang_tempcode('COUNT_DOWNLOADS'));
		$test=explode(' ',get_param('sort','num_downloads DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		inform_non_canonical_parameter('sort');

		if ($sort_order=='ASC') asort($downloads);
		else arsort($downloads);

		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('TITLE'),do_lang_tempcode('COUNT_DOWNLOADS')),$sortables,'sort',$sortable.' '.$sort_order);
		$fields=new ocp_tempcode();
		$real_data=array();
		$i=0;
		foreach ($downloads as $download_name=>$value)
		{
			if ($i<$start)
			{
				$i++; continue;
			} elseif ($i>=$start+$max) break;
			$fields->attach(results_entry(array(escape_html($download_name),integer_format($value))));

			$real_data[]=array(
				'Download name'=>$download_name,
				'Tally'=>$value,
			);

			$i++;
		}
		$list=results_table(do_lang_tempcode('SECTION_DOWNLOADS'),$start,'start',$max,'max',count($downloads),$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',new ocp_tempcode());
		if ($csv) make_csv($real_data,'download_stats.csv');

		$output=create_bar_chart(array_slice($downloads,$start,$max),do_lang('TITLE'),do_lang('COUNT_DOWNLOADS'),'','');
		$ob->save_graph('Global-Downloads',$output);

		$graph=do_template('STATS_GRAPH',array('GRAPH'=>get_custom_base_url().'/data_custom/modules/admin_stats/Global-Downloads.xml','TITLE'=>do_lang_tempcode('SECTION_DOWNLOADS'),'TEXT'=>do_lang_tempcode('DESCRIPTION_DOWNLOADS_STATISTICS')));

		return do_template('STATS_SCREEN',array('_GUID'=>'4b8e0478231473d690e947ffc4580840','TITLE'=>$title,'GRAPH'=>$graph,'STATS'=>$list));
	}

}


