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
 * @package		errorlog
 */

/**
 * Module page class.
 */
class Module_admin_errorlog
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
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('!'=>'ERROR_LOG');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		set_helper_panel_pic('pagepics/errorlog');
		set_helper_panel_tutorial('tut_disaster');

		require_lang('errorlog');
		require_css('errorlog');

		$title=get_screen_title('ERROR_LOG');

		require_code('templates_internalise_screen');
		$test_tpl=internalise_own_screen($title);
		if (is_object($test_tpl)) return $test_tpl;

		// Read in errors
		if (is_readable(get_custom_file_base().'/data_custom/errorlog.php'))
		{
			if (filesize(get_custom_file_base().'/data_custom/errorlog.php')>1024*1024)
			{
				$myfile=fopen(get_custom_file_base().'/data_custom/errorlog.php','rt');
				fseek($myfile,-1024*500,SEEK_END);
				$lines=explode(chr(10),fread($myfile,1024*500));
				fclose($myfile);
				unset($lines[0]);
				$lines[]='...';
			} else
			{
				$lines=file(get_custom_file_base().'/data_custom/errorlog.php');
			}
		}
		else $lines=array();
		$stuff=array();
		foreach ($lines as $line)
		{
			$_line=trim($line);

			if (($_line!='') && (strpos($_line,'<?php')===false))
			{
				$matches=array();
				if (preg_match('#\[(.+?) (.+?)\] (.+?):  ?(.*)#',$_line,$matches)!=0) $stuff[]=$matches;
			}
		}

		// Put errors into table
		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('date_and_time'=>do_lang_tempcode('DATE_TIME'));
		$test=explode(' ',get_param('sort','date_and_time DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		inform_non_canonical_parameter('sort');
		if ($sort_order=='DESC') $stuff=array_reverse($stuff);
		require_code('templates_results_table');
		$fields_title=results_field_title(array(do_lang_tempcode('DATE_TIME'),do_lang_tempcode('TYPE'),do_lang_tempcode('MESSAGE')),$sortables,'sort',$sortable.' '.$sort_order);
		$fields=new ocp_tempcode();
		for ($i=$start;$i<$start+$max;$i++)
		{
			if (!array_key_exists($i,$stuff)) break;

			$message=str_replace(get_file_base(),'',$stuff[$i][4]);
			$fields->attach(results_entry(array(escape_html($stuff[$i][1].' '.$stuff[$i][2]),escape_html($stuff[$i][3]),escape_html($message))));
		}
		$error=results_table(do_lang_tempcode('ERROR_LOG'),$start,'start',$max,'max',$i,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort',new ocp_tempcode());

		// Read in end of permissions file
		require_all_lang();
		if (is_readable(get_custom_file_base().'/data_custom/permissioncheckslog.php'))
		{
			$myfile=@fopen(get_custom_file_base().'/data_custom/permissioncheckslog.php','rt');
			if ($myfile!==false)
			{
				fseek($myfile,-40000,SEEK_END);
				$data='';
				while (!feof($myfile)) $data.=fread($myfile,8192);
				fclose($myfile);
				$lines=explode(chr(10),$data);
				if (count($lines)!=0)
				{
					if (strpos($lines[0],'<'.'?php')!==false)
					{
						array_shift($lines);
					} else
					{
						if (strlen($data)==40000) $lines[0]='...';
					}
				}
				foreach ($lines as $i=>$line)
				{
					$matches=array();
					if (preg_match('#^\s+has\_specific\_permission: (\w+)#',$line,$matches)!=0)
					{
						$looked_up=do_lang('PRIVILEGE_'.$matches[1],NULL,NULL,NULL,NULL,false);
						if (!is_null($looked_up))
						{
							$line=str_replace($matches[1],$looked_up,$line);
							$lines[$i]=$line;
						}
					}
				}
			}
		}

		// Put permssions into table
		$permission=implode(chr(10),$lines);

		return do_template('ERRORLOG_SCREEN',array('_GUID'=>'9186c7beb6b722a52f39e2cbe16aded6','TITLE'=>$title,'ERROR'=>$error,'PERMISSION'=>$permission));
	}

}


