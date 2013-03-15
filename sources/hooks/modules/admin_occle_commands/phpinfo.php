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
 * @package		occle
 */

class Hook_phpinfo
{
	/**
	* Standard modular run function for OcCLE hooks.
	*
	* @param  array	The options with which the command was called
	* @param  array	The parameters with which the command was called
	* @param  array	A reference to the OcCLE filesystem object
	* @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	*/
	function run($options,$parameters,&$occle_fs)
	{
		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('phpinfo',array('h'),array()),'','');
		else
		{
			ob_start();
			phpinfo();
			$out=ob_get_contents();
			ob_end_clean();
			require_code('xhtml');

			$out=preg_replace('#<!DOCTYPE[^>]*>#s','',preg_replace('#</body[^>]*>#','',preg_replace('#<body[^>]*>#','',preg_replace('#</html[^>]*>#','',preg_replace('#<html[^>]*>#','',$out)))));
			$matches=array();
			if (preg_match('#<style[^>]*>#',$out,$matches)!=0)
			{
				$offset=strpos($out,$matches[0])+strlen($matches[0]);
				$end=strpos($out,'</style>',$offset);
				if ($end!==false)
				{
					$style=substr($out,$offset-strlen($matches[0]),$end-$offset+strlen('</style>')+strlen($matches[0]));
					//$GLOBALS['EXTRA_HEAD']=make_string_tempcode($style);

					$out=substr($out,0,$offset).substr($out,$end);
				}
			}
			$out=preg_replace('#<head[^>]*>.*</head[^>]*>#s','',$out);

			$out=str_replace(' width="600"',' width="100%"',$out);
			$out=preg_replace('#([^\s<>"\']{65}&[^;]+;)#','${1}<br />',$out);
			$out=preg_replace('#([^\s<>"\']{95})#','${1}<br />',$out);
			$url_parts=parse_url(get_base_url());
			$out=str_replace('<img border="0" src="/','<img border="0" style="padding-top: 20px" src="http://'.escape_html($url_parts['host']).'/',$out);

			return array('',xhtmlise_html($out,true),'','');
		}
	}

}

