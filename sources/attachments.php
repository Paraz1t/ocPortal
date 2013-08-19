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
 * @package		core_rich_media
 */

/**
 * Show the image of an attachment/thumbnail.
 */
function attachments_script()
{
	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_privilege(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain');
		@exit(get_option('closed'));
	}

	$id=get_param_integer('id',0);
	$connection=$GLOBALS[(get_param_integer('forum_db',0)==1)?'FORUM_DB':'SITE_DB'];
	$has_no_restricts=!is_null($connection->query_select_value_if_there('attachment_refs','id',array('r_referer_type'=>'null','a_id'=>$id)));

	if (!$has_no_restricts)
	{
		global $SITE_INFO;
		if ((!is_guest()) || (!isset($SITE_INFO['any_guest_cached_too'])) || ($SITE_INFO['any_guest_cached_too']=='0'))
		{
			if ((get_param('for_session','-1')!=md5(strval(get_session_id()))) && (get_option('anti_leech')=='1') && (ocp_srv('HTTP_REFERER')!=''))
				warn_exit(do_lang_tempcode('LEECH_BLOCK'));
		}
	}

	require_lang('comcode');

	// Lookup
	$rows=$connection->query_select('attachments',array('*'),array('id'=>$id),'ORDER BY a_add_time DESC');
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];
	header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T',$myrow['a_add_time']));
	if ($myrow['a_url']=='') warn_exit(do_lang_tempcode('INTERNAL_ERROR'));

	if (!$has_no_restricts)
	{
		// Permission
		if (substr($myrow['a_url'],0,20)=='uploads/attachments/')
		{
			if (!has_attachment_access(get_member(),$id,$connection))
				access_denied('ATTACHMENT_ACCESS');
		}
	}

	$thumb=get_param_integer('thumb',0);

	if ($thumb==1)
	{
		$full=$myrow['a_thumb_url'];
		require_code('images');
		$myrow['a_thumb_url']=ensure_thumbnail($myrow['a_url'],$myrow['a_thumb_url'],'attachments','attachments',intval($myrow['id']),'a_thumb_url',NULL,true);
	}
	else
	{
		$full=$myrow['a_url'];

		if (get_param_integer('no_count',0)==0)
		{
			// Update download count
			if (ocp_srv('HTTP_RANGE')=='')
				$connection->query_update('attachments',array('a_num_downloads'=>$myrow['a_num_downloads']+1,'a_last_downloaded_time'=>time()),array('id'=>$id),'',1,NULL,false,true);
		}
	}

	// Is it non-local? If so, redirect
	if (!url_is_local($full))
	{
		if ((strpos($full,chr(10))!==false) || (strpos($full,chr(13))!==false))
			log_hack_attack_and_exit('HEADER_SPLIT_HACK');
		header('Location: '.$full);
		return;
	}

	$_full=get_custom_file_base().'/'.rawurldecode($full);
	if (!file_exists($_full)) warn_exit(do_lang_tempcode('_MISSING_RESOURCE','url:'.escape_html($full))); // File is missing, we can't do anything
	$size=filesize($_full);
	$original_filename=$myrow['a_original_filename'];
	$extension=get_file_extension($original_filename);

	require_code('files2');
	check_shared_bandwidth_usage($size);

	require_code('mime_types');
	$mime_type=get_mime_type($extension);

	// Send header
	if ((strpos($original_filename,chr(10))!==false) || (strpos($original_filename,chr(13))!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');
	header('Content-Type: '.$mime_type.'; authoritative=true;');
	header('Content-Disposition: inline; filename="'.$original_filename.'"');
	header('Accept-Ranges: bytes');

	// Caching
	header('Pragma: private');
	header('Cache-Control: private');
	header('Expires: '.gmdate('D, d M Y H:i:s',time()+60*60*24*365).' GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$myrow['a_add_time']).' GMT');

	// Default to no resume
	$from=0;
	$new_length=$size;

	@ini_set('zlib.output_compression','Off');

	// They're trying to resume (so update our range)
	$httprange=ocp_srv('HTTP_RANGE');
	if (strlen($httprange)>0)
	{
		$_range=explode('=',ocp_srv('HTTP_RANGE'));
		if (count($_range)==2)
		{
			if (strpos($_range[0],'-')===false) $_range=array_reverse($_range);
			$range=$_range[0];
			if (substr($range,0,1)=='-') $range=strval($size-intval(substr($range,1))-1).$range;
			if (substr($range,-1,1)=='-') $range.=strval($size-1);
			$bits=explode('-',$range);
			if (count($bits)==2)
			{
				list($from,$to)=array_map('intval',$bits);
				if (($to-$from!=0) || ($from==0)) // Workaround to weird behaviour on Chrome
				{
					$new_length=$to-$from+1;

					header('HTTP/1.1 206 Partial Content');
					header('Content-Range: bytes '.$range.'/'.strval($size));
				} else
				{
					$from=0;
				}
			}
		}
	}
	header('Content-Length: '.strval($new_length));
	if (function_exists('set_time_limit')) @set_time_limit(0);
	error_reporting(0);

	if ($from==0)
		$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'values SET the_value=(the_value+'.strval($size).') WHERE the_name=\'download_bandwidth\'',1);

	@ini_set('ocproducts.xss_detect','0');

	// Send actual data
	$myfile=fopen($_full,'rb');
	fseek($myfile,$from);
	/*if ($size==$new_length)		Uses a lot of memory :S
	{
		fpassthru($myfile);
	} else*/
	{
		$i=0;
		flush(); // Works around weird PHP bug that sends data before headers, on some PHP versions
		while ($i<$new_length)
		{
			$content=fread($myfile,min($new_length-$i,1048576));
			echo $content;
			$len=strlen($content);
			if ($len==0) break;
			$i+=$len;
		}
		fclose($myfile);
	}
}

/**
 * Find if the specified member has access to view the specified attachment.
 *
 * @param  MEMBER			The member being checked whether to have the access
 * @param  AUTO_LINK		The ID code for the attachment being checked
 * @param  ?object		The database connection to use (NULL: site DB)
 * @return boolean		Whether the member has attachment access
 */
function has_attachment_access($member,$id,$connection=NULL)
{
	if (is_null($connection)) $connection=$GLOBALS['SITE_DB'];

	if ($GLOBALS['FORUM_DRIVER']->is_super_admin($member)) return true;

	$refs=$connection->query_select('attachment_refs',array('r_referer_type','r_referer_id'),array('a_id'=>$id));

	foreach ($refs as $ref)
	{
		$type=$ref['r_referer_type'];
		$ref_id=$ref['r_referer_id'];
		if ((file_exists(get_file_base().'/sources/hooks/systems/attachments/'.filter_naughty_harsh($type).'.php')) || (file_exists(get_file_base().'/sources_custom/hooks/systems/attachments/'.filter_naughty_harsh($type).'.php')))
		{
			require_code('hooks/systems/attachments/'.filter_naughty_harsh($type));
			$object=object_factory('Hook_attachments_'.filter_naughty_harsh($type));

			if ($object->run($ref_id,$connection)) return true;
		}
	}

	return false;
}

/**
 * Shows an HTML page of all attachments we can access with selection buttons.
 */
function attachment_popup_script()
{
	require_lang('comcode');
	require_javascript('javascript_editing');

	$connection=(get_page_name()=='topics')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	$members=array();
	if (!is_guest())
		$members[get_member()]=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
	if (has_privilege(get_member(),'reuse_others_attachments'))
	{
		$_members=$connection->query_select('attachments',array('DISTINCT a_member_id'));
		foreach ($_members as $_member)
		{
			$members[$_member['a_member_id']]=$GLOBALS['FORUM_DRIVER']->get_username($_member['a_member_id']);
		}
	}
	asort($members);

	$member_now=post_param_integer('member_id',get_member());
	if (!array_key_exists($member_now,$members)) access_denied('REUSE_ATTACHMENT');

	$list=new ocp_tempcode();
	foreach ($members as $member_id=>$username)
	{
		$list->attach(form_input_list_entry(strval($member_id),$member_id==$member_now,$username));
	}

	$field_name=get_param('field_name','post');
	$post_url=get_self_url();

	$rows=$connection->query_select('attachments',array('*'),array('a_member_id'=>$member_now));
	$content=new ocp_tempcode();
	foreach ($rows as $myrow)
	{
		$may_delete=(get_member()==$myrow['a_member_id']) && ($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member()));

		if ((post_param_integer('delete_'.strval($myrow['id']),0)==1) && ($may_delete))
		{
			require_code('attachments3');
			_delete_attachment($myrow['id'],$connection);
			continue;
		}

		$myrow['description']=$myrow['a_description'];
		$tpl=render_attachment('attachment',array(),$myrow,uniqid('',true),get_member(),false,$connection,NULL,get_member());
		$content->attach(do_template('ATTACHMENTS_BROWSER_ATTACHMENT',array(
			'_GUID'=>'64356d30905c99325231d3bbee92128c',
			'FIELD_NAME'=>$field_name,
			'TPL'=>$tpl,
			'DESCRIPTION'=>$myrow['a_description'],
			'ID'=>strval($myrow['id']),
			'MAY_DELETE'=>$may_delete,
		)));
	}

	$content=do_template('ATTACHMENTS_BROWSER',array('_GUID'=>'7773aad46fb0bfe563a142030beb1a36','LIST'=>$list,'CONTENT'=>$content,'URL'=>$post_url));

	require_code('site');
	attach_to_screen_header('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	$echo=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'954617cc747b5cece4cc406d8c110150','TITLE'=>do_lang_tempcode('ATTACHMENT_POPUP'),'POPUP'=>true,'CONTENT'=>$content));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Get tempcode for a Comcode rich-media attachment.
 *
 * @param  ID_TEXT		The attachment tag
 * @set attachment attachment_safe
 * @param  array			A map of the attributes (name=>val) for the tag
 * @param  array			A map of the attachment properties (name=>val) for the attachment
 * @param  string			A special identifier to mark where the resultant tempcode is going to end up (e.g. the ID of a post)
 * @param  MEMBER			The member who is responsible for this Comcode
 * @param  boolean		Whether to check as arbitrary admin
 * @param  object			The database connection to use
 * @param  ?array			A list of words to highlight (NULL: none)
 * @param  ?MEMBER		The member we are running on behalf of, with respect to how attachments are handled; we may use this members attachments that are already within this post, and our new attachments will be handed to this member (NULL: member evaluating)
 * @param  boolean		Whether to parse so as to create something that would fit inside a semihtml tag. It means we generate HTML, with Comcode written into it where the tag could never be reverse-converted (e.g. a block).
 * @return tempcode		The tempcode for the attachment
 */
function render_attachment($tag,$attributes,$attachment,$pass_id,$source_member,$as_admin,$connection,$highlight_bits=NULL,$on_behalf_of_member=NULL,$semiparse_mode=false)
{
	require_code('comcode_renderer');

	$extension=get_file_extension($attachment['a_original_filename']);
	require_code('mime_types');
	$mime_type=get_mime_type($extension);

	require_code('files');
	$attachment['CLEAN_SIZE']=clean_file_size($attachment['a_file_size']);
	$attachment['MIME_TYPE']=$mime_type;
	$attachment['PASS_ID']=(intval($pass_id)<0)?strval(mt_rand(0,10000)):$pass_id;
	$attachment['SCRIPT']=find_script('attachment');
	if ($connection->connection_write!=$GLOBALS['SITE_DB']->connection_write)
	{
		$attachment['SUP_PARAMS']='&forum_db=1';
		$attachment['FORUM_DB_BIN']='1';
	} else
	{
		$attachment['SUP_PARAMS']='';
		$attachment['FORUM_DB_BIN']='';
	}

	$type=trim(array_key_exists('type',$attributes)?$attributes['type']:'auto');

	$attachment['id']=strval($attachment['id']);
	$attachment['a_member_id']=strval($attachment['a_member_id']);
	$attachment['a_file_size']=strval($attachment['a_file_size']);
	$attachment['a_last_downloaded_time']=is_null($attachment['a_last_downloaded_time'])?'':strval($attachment['a_last_downloaded_time']);
	$attachment['a_add_time']=strval($attachment['a_add_time']);
	$attachment['a_num_downloads']=integer_format($attachment['a_num_downloads']);

	require_code('images');

	$attachment['a_width']=array_key_exists('width',$attributes)?strval(intval($attributes['width'])):'';
	$attachment['a_height']=array_key_exists('height',$attributes)?strval(intval($attributes['height'])):'';
	if (($attachment['a_width']=='') || ($attachment['a_height']==''))
	{
		if ((addon_installed('galleries')) && (is_video($attachment['a_original_filename'])) && (url_is_local($attachment['a_url'])))
		{
			require_code('galleries2');
			$vid_details=get_video_details(get_custom_file_base().'/'.rawurldecode($attachment['a_url']),$attachment['a_original_filename'],true);
			if ($vid_details!==false)
			{
				list($_width,$_height,)=$vid_details;
				if ($attachment['a_width']=='') $attachment['a_width']=strval($_width);
				if ($attachment['a_height']=='') $attachment['a_height']=strval($_height);
			}
		}
		if ((($attachment['a_width']=='') || ($attachment['a_height']=='')) && (is_video($attachment['a_original_filename'])))
		{
			if ($attachment['a_width']=='') $attachment['a_width']=get_option('attachment_default_width');
			if ($attachment['a_height']=='') $attachment['a_height']=get_option('attachment_default_height');
		}
	}
	$attachment['a_align']=array_key_exists('align',$attributes)?$attributes['align']:'left';

	if (!array_key_exists('a_description',$attachment)) // All quite messy, because descriptions might source from attachments table (for existing attachments with no overridden Comcode description), from Comcode parameter (for attachments with description), or from POST environment (for new attachments)
	{
		if (array_key_exists('description',$attributes)) $attachment['description']=$attributes['description'];
		if (!array_key_exists('description',$attachment)) $attachment['description']='';
		$attachment['a_description']=is_object($attachment['description'])?$attachment['description']:comcode_to_tempcode($attachment['description'],$source_member,$as_admin,60,NULL,$connection,false,false,false,false,false,NULL,$on_behalf_of_member);
	}

	$attachment['a_type']=$type;
	$attachment['a_thumb']=array_key_exists('thumb',$attributes)?$attributes['thumb']:'1';
	if ($attachment['a_thumb']!='0') $attachment['a_thumb']='1';
	$attachment['a_thumb_url']=array_key_exists('thumb_url',$attributes)?$attributes['thumb_url']:$attachment['a_thumb_url'];

	switch ($type)
	{
		case 'email':
			require_code('mail');
			global $EMAIL_ATTACHMENTS;
			if (url_is_local($attachment['a_url'])) $attachment['a_url']=get_custom_base_url().'/'.$attachment['a_url'];
			$EMAIL_ATTACHMENTS[$attachment['a_url']]=$attachment['a_original_filename'];
			$temp_tpl=new ocp_tempcode();
			break;

		case 'code':
			$url=$attachment['a_url'];
			if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;
			$file_contents=http_download_file($url,1024*1024*20/*reasonable limit*/);
			list($_embed,$title)=do_code_box($extension,make_string_tempcode($file_contents));
			if ($attachment['a_original_filename']!='') $title=escape_html($attachment['a_original_filename']);
			$temp_tpl=do_template('COMCODE_CODE',array('_GUID'=>'b76f3383d31ad823f50124d59db6a8c3','WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true,'STYLE'=>'','TYPE'=>$extension,'CONTENT'=>$_embed,'TITLE'=>$title));
			break;

		case 'hyperlink':
			if ($tag=='attachment')
			{
				$keep=symbol_tempcode('KEEP');
				$_url=new ocp_tempcode();
				$_url->attach(find_script('attachment').'?id='.urlencode($attachment['id']).$keep->evaluate());
				if (get_option('anti_leech')=='1')
				{
					$_url->attach('&for_session=');
					$_url->attach(symbol_tempcode('SESSION_HASHED'));
				}
			} else
			{
				$url=$attachment['a_url'];
				if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;
				$_url=make_string_tempcode($url);
			}

			$temp_tpl=hyperlink($_url,($attachment['a_description']!='')?$attachment['a_description']:$attachment['a_original_filename'],true);
			break;

		default:
			if (is_image($attachment['a_original_filename']))
			{
				if (($type=='inline') || ($type=='left_inline') || ($type=='right_inline')) $attachment['mini']='1';
				require_code('images');
				ensure_thumbnail($attachment['a_url'],$attachment['a_thumb_url'],'attachments','attachments',intval($attachment['id']),'a_thumb_url',NULL,true);

				$temp_tpl=do_template('ATTACHMENT_IMG'.(((array_key_exists('mini',$attachment)) && ($attachment['mini']=='1'))?'_MINI':''),map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				if (($type=='left') || ($type=='left_inline'))
				{
					$temp_tpl=do_template('ATTACHMENT_LEFT',array('_GUID'=>'aee2a6842d369c8dae212c3478a3a3e9','WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true,'CONTENT'=>$temp_tpl));
				}
				if (($type=='right') || ($type=='right_inline'))
				{
					$temp_tpl=do_template('ATTACHMENT_RIGHT',array('_GUID'=>'1a7209d67d91db740c86e7a331720195','WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true,'CONTENT'=>$temp_tpl));
				}

				break;
			}
			elseif ($extension=='swf')
			{
				$temp_tpl=do_template('ATTACHMENT_SWF',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				break;
			}
			elseif ((addon_installed('jwplayer')) && (($mime_type=='video/x-flv') || ($mime_type=='video/mp4') || ($mime_type=='video/webm')))
			{
				$temp_tpl=do_template('ATTACHMENT_FLV',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				break;
			}
			elseif ($mime_type=='video/quicktime')
			{
				$temp_tpl=do_template('ATTACHMENT_QT',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				break;
			}
			elseif ($mime_type=='audio/x-pn-realaudio')
			{
				$temp_tpl=do_template('ATTACHMENT_RM',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				break;
			}
			elseif ((substr($mime_type,0,5)=='video') || (substr($mime_type,0,5)=='audio'))
			{
				$temp_tpl=do_template('ATTACHMENT_MEDIA',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
				break;
			}
			// Continues on, as it's not a media type...

		case 'download':
			if (is_null($attachment['a_file_size']))
			{
				$temp_tpl=do_template('ATTACHMENT_DOWNLOAD_REMOTE',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
			} else
			{
				$temp_tpl=do_template('ATTACHMENT_DOWNLOAD',map_keys_to_upper($attachment)+array('WYSIWYG_SAFE'=>($tag=='attachment')?NULL:true));
			}
			break;
	}

	return $temp_tpl;
}


