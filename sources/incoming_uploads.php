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
 * Function to clear old uploads, that are older then 2 days
 */
function clear_old_uploads()
{
	//get the unix timestamp corresonding to the two days ago condition
	$two_days_ago=strtotime('-2 days');
	//get the incoming uploads that are older than two days
	$rows=$GLOBALS['SITE_DB']->query('SELECT * FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'incoming_uploads WHERE i_date_and_time<'.strval($two_days_ago));

	//if there are older uploads records found start processing them
	if(count($rows)>0)
	{
		//browse through files
		foreach($rows as $upload)
		{
			if(!empty($upload['i_save_url']))
			{
				if(file_exists($upload['i_save_url']))
				{
					//delete file if it exists
					@unlink($upload['i_save_url']);
				}

				//Note: it is possible some db records to be left without corresponding files. So we need to clean them too.
				$GLOBALS['SITE_DB']->query_delete('incoming_uploads',array('id'=>$upload['id']),'',1);
			}
		}
	}
}

/**
 * Function to process the file upload process
 */
function incoming_uploads_script()
{
	$is_uploaded=false;

	if (!file_exists(get_custom_file_base().'/uploads/incoming'))
	{
		@mkdir(get_custom_file_base().'/uploads/incoming',0777);
		fix_permissions(get_custom_file_base().'/uploads/incoming',0777);
		sync_file(get_custom_file_base().'/uploads/incoming');
	}

	$savename='uploads/incoming/'.uniqid('',true).'.dat';

	if (array_key_exists('file',$_FILES)) // nice multi-part upload
	{
		if (is_uploaded_file($_FILES['file']['tmp_name']))
		{
			$is_uploaded=true;
		} else
		{
			header('HTTP/1.1 500 File Upload Error');

			@error_log('ocPortal: '.do_lang('ERROR_UPLOADING_'.strval($_FILES['file']['error'])),0);

			exit('ocPortal: '.do_lang('ERROR_UPLOADING_'.strval($_FILES['file']['error'])));
		}

		$name=$_FILES['file']['name'];

		if ($is_uploaded)// && (file_exists($_FILES['file']['tmp_name']))) // file_exists check after is_uploaded_file to avoid race conditions. >>> Actually, open_basedir might block it
		{
			@move_uploaded_file($_FILES['file']['tmp_name'],get_custom_file_base().'/'.$savename) OR intelligent_write_error(get_custom_file_base().'/'.$savename);
		}
	} elseif (post_param('name','')!='') // less nice raw post, which most HTML5 browsers have to do
	{
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

		$name=post_param('name');

		// Read binary input stream and append it to temp file
		$in=fopen('php://input','rb');
		if ($in!==false)
		{
			// Open temp file
			$out=fopen($savename,'wb');
			if ($out!==false)
			{
				$is_uploaded=true;

				do
				{
					$buff=fread($in,4096);
					fwrite($out,$buff);
				}
				while (!feof($out));

				fclose($out);
			}

			fclose($in);
		}
	}

	if ($is_uploaded)
	{
		$max_length=255;
		$field_type_test=$GLOBALS['SITE_DB']->query_value('db_meta','m_type',array('m_name'=>'i_orig_filename'));
		if ($field_type_test=='ID_TEXT') $max_length=80; // Legacy
		$name=substr($name,max(0,strlen($name)-$max_length));

		header('Content-type: text/plain; charset='.get_charset());

		require_code('files');

		if (get_param_integer('base64',0)==1)
		{
			$new=base64_decode(file_get_contents(get_custom_file_base().'/'.$savename));
			$myfile=@fopen(get_custom_file_base().'/'.$savename,'wb') OR intelligent_write_error(get_custom_file_base().'/'.$savename);
			fwrite($myfile,$new);
			fclose($myfile);
		}

		fix_permissions(get_custom_file_base().'/'.$savename);
		sync_file(get_custom_file_base().'/'.$savename);

		$member_id=get_member();

		$file_db_id=$GLOBALS['SITE_DB']->query_insert('incoming_uploads',array('i_submitter'=>$member_id,'i_date_and_time'=>time(),'i_orig_filename'=>$name,'i_save_url'=>$savename),true,false);

		// File is valid, and was successfully uploaded. Now see if there is any metadata to surface from the file.
		require_code('images');
		$outa=array();
		if (is_image($name))
		{
			require_code('exif');
			$outa+=get_exif_data(get_custom_file_base().'/'.$savename);
		}
		$outa['upload_id']=strval($file_db_id);
		$outa['upload_name']=$name;
		$outa['upload_savename']=$savename;
		@ini_set('ocproducts.xss_detect','0');
		$outstr='{';
		$done=0;
		foreach ($outa as $key=>$val) // Put out data as JSON
		{
			if (is_float($val)) $val=float_to_raw_string($val);
			elseif (is_integer($val)) $val=strval($val);

			if ((is_string($val)) && ($val!=''))
			{
				$val=str_replace(chr(0),'',$val);

				if ($done!=0) $outstr.=', ';
				$outstr.='"'.str_replace(chr(10),'\n',addcslashes($key,"\\\'\"&\n\r<>")).'": "'.str_replace(chr(10),'\n',addcslashes($val,"\\\'\"&\n\r<>")).'"';
				$done++;
			}
		}
		$outstr.='}';
		echo $outstr;
	} else
	{
		//header('Content-type: text/plain; charset='.get_charset()); @print('No file ('.serialize($_FILES).')');
		header('HTTP/1.1 500 File Upload Error');

		// Test harness
		$title=get_screen_title('UPLOAD');
		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_upload(do_lang_tempcode('FILE'),'','file',true,NULL,NULL,false));
		$hidden=new ocp_tempcode();
		$out2=globalise(do_template('FORM_SCREEN',array('_GUID'=>'632edbf0ca9f6f644cd9ebbd817b90f3','TITLE'=>$title,'SUBMIT_NAME'=>do_lang_tempcode('PROCEED'),'TEXT'=>'','HIDDEN'=>$hidden,'URL'=>find_script('incoming_uploads',true),'FIELDS'=>$fields)),NULL,'',true);
		$out2->evaluate_echo();
	}

	exit();
}
