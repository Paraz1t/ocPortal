<?php

/**
 * Standard code module initialisation function.
 */
function init__upload_syndication()
{
	require_code('uploads');

	define('UPLOAD_PRECEDENCE_NO',0);
	define('UPLOAD_PRECEDENCE_LOW',1);
	define('UPLOAD_PRECEDENCE_MEDIUM',5);
	define('UPLOAD_PRECEDENCE_HIGH',10);
	define('UPLOAD_PRECEDENCE_REGARDLESS',1000);
}

/**
 * Get details for what upload syndication we can do for particular filtered upload types.
 *
 * @param  integer		The kind of files we are going to be handling.
 * @return array			A pair: JSON data describing what upload syndication we can do (may be NULL), a filetype filter.
 */
function get_upload_syndication_json($file_handling_types)
{
	$struct=array();

	$all_hook_file_handling_types=0;

	$hooks=find_all_hooks('systems','upload_syndication');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upload_syndication/'.filter_naughty($hook));
		$ob=object_factory('Hook_upload_syndication_'.$hook);
		if ($ob->is_enabled())
		{
			$hook_file_handling_types=$ob->get_file_handling_types();
			if (($hook_file_handling_types & $file_handling_types)!=0)
			{
				$all_hook_file_handling_types|=$hook_file_handling_types;
				if (!$ob->happens_always())
				{
					$struct[$hook]=array('label'=>$ob->get_label(),'authorised'=>$ob->is_authorised());
				}
			}
		}
	}

	$all_hook_file_handling_types=$all_hook_file_handling_types & $file_handling_types;
	$syndicatable_filetypes='';
	if (($all_hook_file_handling_types & OCP_UPLOAD_ANYTHING)==0)
	{
		require_code('images');
		if (($all_hook_file_handling_types & OCP_UPLOAD_IMAGE)!=0)
		{
			if ($syndicatable_filetypes!='') $syndicatable_filetypes.=',';
			$syndicatable_filetypes.=get_allowed_image_file_types();
		}
		if (($all_hook_file_handling_types & OCP_UPLOAD_VIDEO)!=0)
		{
			if ($syndicatable_filetypes!='') $syndicatable_filetypes.=',';
			$syndicatable_filetypes.=get_allowed_video_file_types();
		}
		if (($all_hook_file_handling_types & OCP_UPLOAD_AUDIO)!=0)
		{
			if ($syndicatable_filetypes!='') $syndicatable_filetypes.=',';
			$syndicatable_filetypes.=get_allowed_audio_file_types();
		}
		if (($all_hook_file_handling_types & OCP_UPLOAD_SWF)!=0)
		{
			if ($syndicatable_filetypes!='') $syndicatable_filetypes.=',';
			$syndicatable_filetypes.='swf';
		}
	}

	if ((function_exists('json_encode')) && (count($struct)>0))
	{
		return array(json_encode($struct),$syndicatable_filetypes);
	}
	return array(NULL,$syndicatable_filetypes);
}

/**
 * Save syndication to a web service (typically via oAuth, but abstracted within the upload_syndication hooks).
 */
function upload_syndication_auth_script()
{
	$hook=get_param('hook');
	$name=get_param('name');

	require_code('hooks/systems/upload_syndication/'.filter_naughty($hook));
	$ob=object_factory('Hook_upload_syndication_'.$hook);
	$success=$ob->receive_authorisation();

	require_lang('upload_syndication');

	$label=$ob->get_label();

	if (!$success)
	{
		warn_exit(do_lang_tempcode('FAILURE_UPLOAD_SYNDICATION_AUTH',escape_html($label)));
	}

	$tpl=do_template('UPLOAD_SYNDICATION_SETUP_SCREEN',array(
		'LABEL'=>$label,
		'HOOK'=>$hook,
		'NAME'=>$name,
	));
	$echo=do_template('STANDALONE_HTML_WRAP',array('TITLE'=>do_lang_tempcode('UPLOAD_SYNDICATION_AUTH'),'CONTENT'=>$tpl,'POPUP'=>true));
	$echo->handle_symbol_preprocessing();
	$echo->evaluate_echo();
}

/**
 * Do upload syndication (after an upload has been received, in-context).
 *
 * @param  ID_TEXT		Upload field name.
 * @param  string			Title associated with the upload.
 * @param  string			Description associated with the upload.
 * @param  URLPATH		URL to the upload (should be local, if it isn't we'll return quickly without doing anything).
 * @param  ID_TEXT		Filename.
 * @param  boolean		Whether to delete the local copy, if the current user has no upload quota. If no syndication was set, an error will be given.
 * @return URLPATH		New URL (if we deleted the local copy, it will be a remote URL).
 */
function handle_upload_syndication($name,$title,$description,$url,$filename,$remove_locally_if_no_quota)
{
	if (!url_is_local($url)) return $url; // Not an upload

	$new_url=$url;

	if (url_is_local($url)) $url=get_custom_base_url().'/'.$url;

	$remote_urls=array();
	$hooks=find_all_hooks('systems','upload_syndication');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/upload_syndication/'.filter_naughty($hook));
		$ob=object_factory('Hook_upload_syndication_'.$hook);
		if ((post_param_integer('upload_syndicate__'.$hook.'__'.$name,0)==1) || ($ob->happens_always()))
		{
			if (($ob->is_enabled()) && ($ob->is_authorised()))
			{
				$hook_file_handling_types=$ob->get_file_handling_types();
				if (_check_enforcement_of_type(get_member(),$filename,$hook_file_handling_types,true)) // Check the upload API agrees this file matches the filetype bitmask
				{
					$remote_url=$ob->syndicate($url,$filename,$title,$description);
					if (!is_null($remote_url))
					{
						$remote_urls[$hook]=array($remote_url,$ob->get_reference_precedence());
						if ($ob->get_reference_precedence()==UPLOAD_PRECEDENCE_REGARDLESS) // Cloud-filesystem use-case
							$remove_locally_if_no_quota=true;
					}
				}
			}
		}
	}

	if ($remove_locally_if_no_quota)
	{
		require_code('files2');
		$max_attach_size=get_max_file_size(get_member(),$GLOBALS['SITE_DB']);
		$no_quota=(($max_attach_size==0) && (ocf_get_member_best_group_property(get_member(),'max_daily_upload_mb')==0));
		if ($no_quota)
		{
			if (url_is_local($url))
			{
				@unlink(get_custom_file_base().'/'.rawurldecode($url));
			}

			if (count($remote_urls)==0)
			{
				require_lang('upload_syndication');
				warn_exit(do_lang_tempcode('UPLOAD_MUST_SYNDICATE',escape_html(get_site_name())));
			}

			sort_maps_by($remote_urls,1);
			$new_url=end($remote_urls);
		}
	}

	return $new_url;
}
