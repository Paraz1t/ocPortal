<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		code_quality
 */

/*
Parse PHPdoc in all scripts under project directory
*/

global $OCPORTAL_PATH;

require(dirname(__FILE__).'/lib.php');

if (isset($_SERVER['argv']))
{
	$extra=array();
	foreach ($_SERVER['argv'] as $index=>$argv)
	{
		$argv=str_replace('\\\\','\\',$argv);
		$_SERVER['argv'][$index]=$argv;
		$explode=explode('=',$argv,2);
		if (count($explode)==2)
		{
			$extra[$explode[0]]=trim($explode[1],'"');
			unset($_SERVER['argv'][$index]);
		}
	}
	$_SERVER['argv']=array_merge($_SERVER['argv'],$extra);
	if (array_key_exists('path',$_SERVER['argv'])) $GLOBALS['OCPORTAL_PATH']=$_SERVER['argv']['path'];
}

require_code('php');

$no_custom=true;
if ((isset($_GET['allow_custom'])) && ($_GET['allow_custom']=='1'))
	$no_custom=false;
$files=do_dir($OCPORTAL_PATH,$no_custom,true);

$classes=array();
$global=array();
global $TO_USE;
//$files=array($OCPORTAL_PATH.'/sources/global2.php'); For debugging
foreach ($files as $filename)
{
	if (basename($filename,'.php')=='tempcode__runtime') continue;
	if (basename($filename,'.php')=='tempcode_compiler__runtime') continue;

	$TO_USE=$filename;

	$_filename=($OCPORTAL_PATH=='')?$filename:substr($filename,strlen($OCPORTAL_PATH)+1);
	if ($_filename=='sources'.DIRECTORY_SEPARATOR.'minikernel.php') continue;
	//echo 'SIGNATURES-DOING '.$_filename.cnl();
	$result=get_php_file_api($_filename,false);

	foreach ($result as $i=>$r)
	{
		if ($r['name']=='__global')
		{
			if (($_filename!='sources'.DIRECTORY_SEPARATOR.'global.php') && ($_filename!='phpstub.php') && ($_filename!='tempcode_compiler__runtime') && ($_filename!='tempcode_compiler'))
			{
				foreach (array_keys($r['functions']) as $f)
				{
					if ((isset($global[$f])) && (!in_array($f,array('file_get_contents','ftp_chmod','html_entity_decode','str_ireplace','str_word_count','do_lang','mixed','qualify_url','http_download_file','get_forum_type','ocp_srv','mailto_obfuscated','get_custom_file_base'))))
						echo 'DUPLICATE-FUNCTION '.$f.' (in '.$filename.')'.cnl();
				}
			}
			$global=array_merge($global,$r['functions']);
		}
	}
	foreach ($result as $in)
	{
		if ($in['name']!='__global')
		{
			$class=$in['name'];
			if (isset($classes[$class])) echo 'DUPLICATE_CLASS'.' '.$class.cnl();
			$classes[$class]=$in;
		}
	}
	//echo 'SIGNATURES-DONE '.$_filename.cnl();
}

$classes['__global']=array('functions'=>$global);
if (file_exists($OCPORTAL_PATH.'/data_custom'))
{
	$myfile=fopen($OCPORTAL_PATH.'/data_custom/functions.dat','wb');
} else
{
	$myfile=fopen('functions.dat','wb');
}
fwrite($myfile,serialize($classes));
fclose($myfile);

echo 'DONE Compiled signatures';

function require_code($codename)
{
	global $OCPORTAL_PATH;
	require_once($OCPORTAL_PATH.'/sources/'.$codename.'.php');
}

function filter_naughty($in)
{
	return $in;
}

function do_lang_tempcode($x,$a=NULL,$b=NULL,$c=NULL,$d=NULL)
{
	global $PARSED;
	if (!isset($PARSED))
	{
		$temp=file_get_contents('lang/php.ini');
		$temp_2=explode("\n",$temp);
		$PARSED=array();
		foreach ($temp_2 as $p)
		{
			$pos=strpos($p,'=');
			if ($pos!==false)
			{
				$PARSED[substr($p,0,$pos)]=substr($p,$pos+1);
			}
		}
	}
	$out=strip_tags(str_replace('{1}',$a,str_replace('{2}',$b,$PARSED[$x])));
	if (is_string($c))
	{
		$out=str_replace('{3}',$c,$out);
	} else
	{
		$out=@str_replace('{3}',$c[0],$out);
		$out=@str_replace('{4}',$c[1],$out);
		$out=@str_replace('{5}',$c[2],$out);
		$out=@str_replace('{6}',$c[3],$out);
	}
	return rtrim($out);
}

function escape_html($in)
{
	return $in;
}

function fatal_exit($message)
{
	global $TO_USE,$LINE;
	echo('ISSUE "'.$TO_USE.'" '.strval($LINE).' 0 '.$message.cnl());
}

