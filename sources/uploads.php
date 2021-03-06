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

/**
 * Standard code module initialisation function.
 */
function init__uploads()
{
	if (function_exists('set_time_limit')) @set_time_limit(0); // On some server setups, slow uploads can trigger the time-out

	if (!defined('OCP_UPLOAD_ANYTHING'))
	{
		define('OCP_UPLOAD_ANYTHING',0);
		define('OCP_UPLOAD_IMAGE',1);
		define('OCP_UPLOAD_VIDEO',2);
		define('OCP_UPLOAD_MP3',3);
		define('OCP_UPLOAD_IMAGE_OR_SWF',4);
	}
}

/**
 * Find whether an swfupload upload has just happened, and optionally simulate as if it were a normal upload (although 'is_uploaded_file'/'move_uploaded_file' would not work).
 *
 * @param  boolean		Simulate population of the $_FILES array.
 * @return boolean		Whether an swfupload upload has just happened.
 */
function is_swf_upload($fake_prepopulation=false)
{
	//check whatever is used the swfuploader
	$swfupload=false;
	foreach($_POST as $key=>$value)
	{
		if (!is_string($value)) continue;	
		if (!is_string($key)) $key=strval($key);

		if ((preg_match('#^hidFileID\_#i',$key)!=0) && ($value!='-1'))
		{
			// Get the incoming uploads appropiate database table row
			if (substr($value,-4)=='.dat') // By .dat name
			{
				$filename=post_param(str_replace('hidFileID','hidFileName',$key),'');
				if ($filename=='') continue; // Was cancelled during plupload, but plupload can't cancel so was allowed to finish. So we have hidFileID but not hidFileName.

				$path='uploads/incoming/'.filter_naughty($value);
				if (file_exists(get_custom_file_base().'/'.$path))
				{
					$swfupload=true;
					if ($fake_prepopulation)
					{
						$_FILES[substr($key,10)]=array(
							'type'=>'swfupload',
							'name'=>$filename,
							'tmp_name'=>get_custom_file_base().'/'.$path,
							'size'=>filesize(get_custom_file_base().'/'.$path)
						);
					}
				}
			} else // By incoming upload ID
			{
				foreach (array_map('intval',explode(':',$value)) as $i=>$incoming_uploads_id)
				{
					$incoming_uploads_row=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'incoming_uploads WHERE (i_submitter='.strval(get_member()).' OR i_submitter='.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).') AND id='.strval($incoming_uploads_id),1);
					if (array_key_exists(0,$incoming_uploads_row))
					{
						if (file_exists(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']))
						{
							$swfupload=true;
							if ($fake_prepopulation)
							{
								$_FILES[preg_replace('#(\_)?1$#','${1}'.strval($i+1),substr($key,10))]=array(
									'type'=>'swfupload',
									'name'=>$incoming_uploads_row[0]['i_orig_filename'],
									'tmp_name'=>get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'],
									'size'=>filesize(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'])
								);
							}
						}
					}
				}
			}
		}
	}

	return $swfupload;
}

/**
 * Get URLs generated according to the specified information. It can also generate a thumbnail if required. It first tries attached upload, then URL, then fails.
 *
 * @param  ID_TEXT		The name of the POST parameter storing the URL (if '', then no POST parameter). Parameter value may be blank.
 * @param  ID_TEXT		The name of the HTTP file parameter storing the upload (if '', then no HTTP file parameter). No file necessarily is uploaded under this.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		Whether to obfuscate file names so the URLs can not be guessed/derived (0=do not, 1=do, 2=make extension .dat as well, 3=only obfuscate if we need to)
 * @set    0 1 2 3
 * @param  integer		The type of upload it is (from an OCP_UPLOAD_* constant)
 * @param  boolean		Make a thumbnail (this only makes sense, if it is an image)
 * @param  ID_TEXT		The name of the POST parameter storing the thumb URL. As before
 * @param  ID_TEXT		The name of the HTTP file parameter storing the thumb upload. As before
 * @param  boolean		Whether to copy a URL (if a URL) to the server, and return a local reference
 * @param  boolean		Whether to accept upload errors
 * @param  boolean		Whether to give a (deferred?) error if no file was given at all
 * @return array			An array of 4 URL bits (URL, thumb URL, URL original filename, thumb original filename)
 */
function get_url($specify_name,$attach_name,$upload_folder,$obfuscate=0,$enforce_type=0,$make_thumbnail=false,$thumb_specify_name='',$thumb_attach_name='',$copy_to_server=false,$accept_errors=false,$should_get_something=false)
{
	require_code('files2');

	$upload_folder=filter_naughty($upload_folder);
	$out=array();
	$thumb=NULL;

	$swf_uploaded=false;
	$swf_uploaded_thumb=false;
	foreach (array($attach_name,$thumb_attach_name) as $i=>$_attach_name)
	{
		if ($_attach_name=='') continue;

		//check whatever it is an incoming upload
		$row_id_file='hidFileID_'.$_attach_name;
		$row_id_file_value=post_param($row_id_file,NULL);
		if ($row_id_file_value=='-1') $row_id_file_value=NULL;

		//id of the upload from the incoming uploads database table
		if (!is_null($row_id_file_value)) //SwfUploader used
		{
			//get the incoming uploads appropiate db table row
			if ((substr($row_id_file_value,-4)=='.dat') && (strpos($row_id_file_value,':')===false))
			{
				$path='uploads/incoming/'.filter_naughty($row_id_file_value);
				if (file_exists(get_custom_file_base().'/'.$path))
				{
					$_FILES[$_attach_name]=array('type'=>'swfupload', 'name'=>post_param(str_replace('hidFileID','hidFileName',$row_id_file)), 'tmp_name'=>get_custom_file_base().'/'.$path, 'size'=>filesize(get_custom_file_base().'/'.$path));
					if ($i==0)
					{
						$swf_uploaded=true;
					} else
					{
						$swf_uploaded_thumb=true;
					}
				}
			} else
			{
				$incoming_uploads_id=intval(preg_replace('#:.*$#','',$row_id_file_value));
				$incoming_uploads_row=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'incoming_uploads WHERE (i_submitter='.strval(get_member()).' OR i_submitter='.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).') AND id='.strval($incoming_uploads_id),1);
				//if there is a db record proceed
				if (array_key_exists(0,$incoming_uploads_row))
				{
					if (file_exists(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']))
					{
						$_FILES[$_attach_name]=array('type'=>'swfupload', 'name'=>$incoming_uploads_row[0]['i_orig_filename'], 'tmp_name'=>get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url'], 'size'=>filesize(get_custom_file_base().'/'.$incoming_uploads_row[0]['i_save_url']));
						if ($i==0)
						{
							$swf_uploaded=true;
						} else
						{
							$swf_uploaded_thumb=true;
						}
					}
				}
			}
		}
	}

	if ($obfuscate==3) $accept_errors=true;

	$thumb_folder=(strpos($upload_folder,'uploads/galleries')!==false)?str_replace('uploads/galleries','uploads/galleries_thumbs',$upload_folder):($upload_folder.'_thumbs');

	if (!file_exists(get_custom_file_base().'/'.$upload_folder))
	{
		$success=@mkdir(get_custom_file_base().'/'.$upload_folder,0777);
		if ($success===false) warn_exit(@strval($php_errormsg));
		fix_permissions(get_custom_file_base().'/'.$upload_folder,0777);
		sync_file($upload_folder);
	}
	if ((!file_exists(get_custom_file_base().'/'.$thumb_folder)) && ($make_thumbnail))
	{
		$success=@mkdir(get_custom_file_base().'/'.$thumb_folder,0777);
		if ($success===false) warn_exit(@strval($php_errormsg));
		fix_permissions(get_custom_file_base().'/'.$thumb_folder,0777);
		sync_file($thumb_folder);
	}

	// Find URL
	require_code('images');
	if (($enforce_type==OCP_UPLOAD_IMAGE) || ($enforce_type==OCP_UPLOAD_IMAGE_OR_SWF))
	{
		$max_size=get_max_image_size();
	} else
	{
		require_code('files2');
		$max_size=get_max_file_size();
	}
	if (($attach_name!='') && (array_key_exists($attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$attach_name]['tmp_name'])) || ($swf_uploaded))) // If we uploaded
	{
		if (!has_specific_permission(get_member(),'exceed_filesize_limit'))
		{
			if ($_FILES[$attach_name]['size']>$max_size)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
				}
			}
		}

		$url=_get_upload_url($attach_name,$upload_folder,$enforce_type,$obfuscate,$accept_errors);
		if ($url==array('','')) return array('','','','');

		$is_image=is_image($_FILES[$attach_name]['name']);
	}
	elseif (post_param($specify_name,'')!='') // If we specified
	{
		$is_image=is_image($_POST[$specify_name]);

		$url=_get_specify_url($specify_name,$upload_folder,$enforce_type,$accept_errors);
		if ($url==array('','')) return array('','','','');
		if (($copy_to_server) && (!url_is_local($url[0])))
		{
			$path2=ocp_tempnam('ocpfc');
			$tmpfile=fopen($path2,'wb');

			$file=http_download_file($url[0],$max_size,true,false,'ocPortal',NULL,NULL,NULL,NULL,NULL,$tmpfile);
			fclose($tmpfile);
			if (is_null($file))
			{
				@unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('CANNOT_COPY_TO_SERVER'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('CANNOT_COPY_TO_SERVER'));
				}
			}
			global $HTTP_FILENAME;
			if (is_null($HTTP_FILENAME)) $HTTP_FILENAME=$url[1];

			if (!check_extension($HTTP_FILENAME,$obfuscate==2,$path2,$accept_errors))
			{
				if ($obfuscate==3) // We'll try again, with obfuscation to see if this would get through
				{
					$obfuscate=2;
					if (!check_extension($HTTP_FILENAME,$obfuscate==2,$path2,$accept_errors))
					{
						return array('','','','');
					}
				} else
				{
					return array('','','','');
				}
			}

			if (url_is_local($url[0]))
			{
				unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('CANNOT_COPY_TO_SERVER'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('CANNOT_COPY_TO_SERVER'));
				}
			}
			if (($obfuscate!=0) && ($obfuscate!=3))
			{
				$ext=(($obfuscate==2) && (!is_image($HTTP_FILENAME)))?'dat':get_file_extension($HTTP_FILENAME);

				$_file=preg_replace('#\..*\.#','.',$HTTP_FILENAME).((substr($HTTP_FILENAME,-strlen($ext)-1)=='.'.$ext)?'':('.'.$ext));
				$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
				while (file_exists($place))
				{
					$_file=uniqid('',true).'.'.$ext;
					$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
				}
			} else
			{
				$_file=$HTTP_FILENAME;
				$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
			}
			if (!has_specific_permission(get_member(),'exceed_filesize_limit'))
			{
				$max_size=intval(get_option('max_download_size'))*1024;
				if (strlen($file)>$max_size)
				{
					if ($accept_errors)
					{
						attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
						return array('','','','');
					} else
					{
						warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
					}
				}
			}
			$result=@rename($path2,$place);
			if (!$result)
			{
				unlink($path2);
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('WRITE_ERROR',escape_html($upload_folder)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('WRITE_ERROR',escape_html($upload_folder)));
				}
			}
			fix_permissions($place);
			sync_file($place);

			$url[0]=$upload_folder.'/'.$_file;
			if (strpos($HTTP_FILENAME,'/')===false) $url[1]=$HTTP_FILENAME;
		}
	} else // Uh oh
	{
		if (/*($attach_name!='') && */(array_key_exists($attach_name,$_FILES)) && (array_key_exists('error',$_FILES[$attach_name])) && (($_FILES[$attach_name]['error']!=4) || ($should_get_something)) && ($_FILES[$attach_name]['error']!=0)) // If we uploaded
		{
			if ($_FILES[$attach_name]['error']==1)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
				}
			}
			elseif ($_FILES[$attach_name]['error']==2)
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG_QUOTA',integer_format($max_size)),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG_QUOTA',integer_format($max_size)));
				}
			}
			elseif (($_FILES[$attach_name]['error']==3) || ($_FILES[$attach_name]['error']==4) || ($_FILES[$attach_name]['error']==6) || ($_FILES[$attach_name]['error']==7))
			{
				attach_message(do_lang_tempcode('ERROR_UPLOADING_'.strval($_FILES[$attach_name]['error'])),'warn');
				return array('','','','');
			} else
			{
				warn_exit(do_lang_tempcode('ERROR_UPLOADING_'.strval($_FILES[$attach_name]['error'])));
			}
		}

		$url[0]='';
		$url[1]='';
		$is_image=false;
	}

	$out[0]=$url[0];
	$out[2]=$url[1];

	// Generate thumbnail if needed
	if (($make_thumbnail) && ($url[0]!='') && ($is_image))
	{
		if ((array_key_exists($thumb_attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$thumb_attach_name]['tmp_name'])) || ($swf_uploaded_thumb))) // If we uploaded
		{
			if ($_FILES[$thumb_attach_name]['size']>get_max_image_size())
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())));
				}
			}

			$_thumb=_get_upload_url($thumb_attach_name,$thumb_folder,OCP_UPLOAD_IMAGE,0,$accept_errors);
			$thumb=$_thumb[0];
		}
		elseif (array_key_exists($thumb_specify_name,$_POST)) // If we specified
		{
			$_thumb=_get_specify_url($thumb_specify_name,$thumb_folder,OCP_UPLOAD_IMAGE,$accept_errors);
			$thumb=$_thumb[0];
		} else
		{
			$gd=((get_option('is_on_gd')=='1') && (function_exists('imagetypes')));

			if ($gd)
			{
				if (!is_saveable_image($url[0])) $ext='.png'; else $ext='';
				$file=basename($url[0]);
				$_file=$file;
				$place=get_custom_file_base().'/'.$thumb_folder.'/'.$_file.$ext;
				$i=2;
				while (file_exists($place))
				{
					$_file=strval($i).$file;
					$place=get_custom_file_base().'/'.$thumb_folder.'/'.$_file.$ext;
					$i++;
				}
				$url_full=url_is_local($url[0])?get_custom_base_url().'/'.$url[0]:$url[0];
				convert_image($url_full,$place,-1,-1,intval(get_option('thumb_width')));

				$thumb=$thumb_folder.'/'.rawurlencode($_file).$ext;
			} else
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('GD_THUMB_ERROR'),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('GD_THUMB_ERROR'));
				}
			}
		}

		$out[1]=$thumb;
	}
	elseif ($make_thumbnail)
	{
		if ((array_key_exists($thumb_attach_name,$_FILES)) && ((is_uploaded_file($_FILES[$thumb_attach_name]['tmp_name'])) || ($swf_uploaded_thumb))) // If we uploaded
		{
			if ($_FILES[$thumb_attach_name]['size']>get_max_image_size())
			{
				if ($accept_errors)
				{
					attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())),'warn');
					return array('','','','');
				} else
				{
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format(get_max_image_size())));
				}
			}

			$_thumb=_get_upload_url($thumb_attach_name,$thumb_folder,OCP_UPLOAD_IMAGE,0,$accept_errors);
			$thumb=$_thumb[0];
		}
		elseif (array_key_exists($thumb_specify_name,$_POST))
		{
			$_thumb=_get_specify_url($thumb_specify_name,$thumb_folder,OCP_UPLOAD_IMAGE,$accept_errors);
			$thumb=$_thumb[0];
		}
		if (!is_null($thumb))
			$out[1]=$thumb;
		else $out[1]='';
	}

	// For reentrance of previews
	if ($specify_name!='') $_POST[$specify_name]=array_key_exists(0,$out)?$out[0]:'';
	if ($thumb_specify_name!='') $_POST[$thumb_specify_name]=array_key_exists(1,$out)?$out[1]:'';

	return $out;
}

/**
 * Filters specified URLs to make sure we're really allowed to access them.
 *
 * @param  ID_TEXT		The name of the POST parameter storing the URL (if '', then no POST parameter). Parameter value may be blank.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		The type of upload it is (from an OCP_UPLOAD_* constant)
 * @param  boolean		Whether to accept upload errors
 * @return array			A pair: the URL and the filename
 */
function _get_specify_url($specify_name,$upload_folder,$enforce_type=0,$accept_errors=false)
{
	// Security check against naughty url's
	$url=array();
	$url[0]=/*filter_naughty*/(post_param($specify_name));
	$url[1]=rawurldecode(basename($url[0]));

	// If this is a relative URL then it may be downloaded through a PHP script.
	//  So lets check we are allowed to download it!
	if (($url[0]!='') && (url_is_local($url[0])))
	{
		$missing_ok=false;

		// Its not in the upload folder, so maybe we aren't allowed to download it
		if ((substr($url[0],0,strlen($upload_folder)+1)!=$upload_folder.'/') || (strpos($url[0],'..')!==false))
		{
			$myfile=@fopen(get_custom_file_base().'/'.rawurldecode($url[0]),'rb');
			if ($myfile!==false)
			{
				$shouldbe=fread($myfile,8000);
				fclose($myfile);
			} else $shouldbe=NULL;
			global $HTTP_MESSAGE;
			$actuallyis=http_download_file(get_custom_base_url().'/'.$url[0],8000,false);

			if (($HTTP_MESSAGE=='200') && (is_null($shouldbe)))
			{
				// No error downloading, but error using file system - therefore file exists and we'll use URL to download. Hence no security check.
				$missing_ok=true;
			} else
			{
				if (@strcmp(substr($shouldbe,0,8000),substr($actuallyis,0,8000))!=0)
				{
					log_hack_attack_and_exit('TRY_TO_DOWNLOAD_SCRIPT');
				}
			}
		}

		// Check the file exists
		if ((!file_exists(get_custom_file_base().'/'.rawurldecode($url[0]))) && (!$missing_ok))
		{
			if ($accept_errors)
			{
				attach_message(do_lang_tempcode('MISSING_FILE'),'warn');
				return array('','');
			} else
			{
				warn_exit(do_lang_tempcode('MISSING_FILE'));
			}
		}
	}

	if ($url[0]!='') _check_enforcement_of_type($url[0],$enforce_type,$accept_errors);

	return $url;
}

/**
 * Ensures a given filename is of the right file extension for the desired file type.
 *
 * @param  string			The filename.
 * @param  integer		The type of upload it is (from an OCP_UPLOAD_* constant)
 * @param  boolean		Whether to accept upload errors
 */
function _check_enforcement_of_type($file,$enforce_type,$accept_errors=false)
{
	require_code('images');
	if (($enforce_type==OCP_UPLOAD_IMAGE_OR_SWF) && (!is_image($file)) && (get_file_extension($file)!='swf'))
	{
		warn_exit(do_lang_tempcode('NOT_IMAGE'));
	}
	if (($enforce_type==OCP_UPLOAD_IMAGE) && (!is_image($file)))
	{
//		if ($accept_errors)
//			attach_message(do_lang_tempcode('NOT_IMAGE'),'warn');
//		else
			warn_exit(do_lang_tempcode('NOT_IMAGE'));
	}
	if (($enforce_type==OCP_UPLOAD_VIDEO) && (!is_video($file)))
	{
//		if ($accept_errors)
		require_lang('galleries');
//			attach_message(do_lang_tempcode('NOT_VIDEO'),'warn');
//		else
			warn_exit(do_lang_tempcode('NOT_VIDEO'));
	}
	if (($enforce_type==OCP_UPLOAD_MP3) && (get_file_extension($file)!='mp3') && (get_file_extension($file)!='mp4') && (get_file_extension($file)!='3gp'))
	{
		warn_exit(do_lang_tempcode('NOT_FILE_TYPE','.mp3'));
	}
}

/**
 * Converts an uploaded file into a URL, by moving it to an appropriate place.
 *
 * @param  ID_TEXT		The name of the HTTP file parameter storing the upload (if '', then no HTTP file parameter). No file necessarily is uploaded under this.
 * @param  ID_TEXT		The folder name in uploads/ where we will put this upload
 * @param  integer		The type of upload it is (from an OCP_UPLOAD_* constant)
 * @param  integer		Whether to obfuscate file names so the URLs can not be guessed/derived (0=do not, 1=do, 2=make extension .dat as well)
 * @set    0 1 2
 * @param  boolean		Whether to accept upload errors
 * @return array			A pair: the URL and the filename
 */
function _get_upload_url($attach_name,$upload_folder,$enforce_type=0,$obfuscate=0,$accept_errors=false)
{
	$file=$_FILES[$attach_name]['name'];
	if (get_magic_quotes_gpc()) $file=stripslashes($file);

	if (!check_extension($file,$obfuscate==2,NULL,$accept_errors))
	{
		if ($obfuscate==3) // We'll try again, with obfuscation to see if this would get through
		{
			$obfuscate=2;
			if (!check_extension($file,$obfuscate==2,NULL,$accept_errors))
			{
				return array('','','','');
			}
		} else
		{
			return array('','','','');
		}
	}

	_check_enforcement_of_type($file,$enforce_type,$accept_errors);

	// If we are not obfuscating then we will need to search for an available filename
	if (($obfuscate==0) || ($obfuscate==3))
	{
		$_file=preg_replace('#\..*\.#','.',$file);
		$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		$i=2;
		// Hunt with sensible names until we don't get a conflict
		while (file_exists($place))
		{
			$_file=strval($i).preg_replace('#\..*\.#','.',$file);
			$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
			$i++;
		}
	}
	else // A result of some randomness
	{
		$ext=get_file_extension($file);
		$ext=(($obfuscate==2) && (!is_image($file)))?'dat':get_file_extension($file);

		$_file=uniqid('',true).'.'.$ext;
		$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		while (file_exists($place))
		{
			$_file=uniqid('',true).'.'.$ext;
			$place=get_custom_file_base().'/'.$upload_folder.'/'.$_file;
		}
	}

	check_shared_space_usage($_FILES[$attach_name]['size']);

	// Copy there, and return our URL
	if ($_FILES[$attach_name]['type']!='swfupload')
	{
		$test=@move_uploaded_file($_FILES[$attach_name]['tmp_name'],$place);
	} else
	{
		$test=@copy($_FILES[$attach_name]['tmp_name'],$place); // We could rename, but it would hurt integrity of refreshes
	}
	if ($test===false)
	{
		if ($accept_errors)
		{
			$df=do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html($place));
			attach_message($df,'warn');
			return array('','');
		} else
		{
			warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($file),escape_html($place)));
		}
	}
	fix_permissions($place);
	sync_file($place);

	$url=array();
	$url[0]=$upload_folder.'/'.rawurlencode($_file);
	$url[1]=$file;
	return $url;
}


