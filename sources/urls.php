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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__urls()
{
	global $HTTPS_PAGES_CACHE;
	$HTTPS_PAGES_CACHE=NULL;

	global $CHR_0;
	$CHR_0=chr(10);

	global $USE_REWRITE_PARAMS_CACHE;
	$USE_REWRITE_PARAMS_CACHE=NULL;

	global $HAS_KEEP_IN_URL_CACHE;
	$HAS_KEEP_IN_URL_CACHE=NULL;

	global $URL_REMAPPINGS;
	$URL_REMAPPINGS=NULL;

	global $CONTENT_OBS,$LOADED_MONIKERS_CACHE;
	$CONTENT_OBS=NULL;
	$LOADED_MONIKERS_CACHE=array();

	global $SELF_URL_CACHED;
	$SELF_URL_CACHED=NULL;

	define('SELF_REDIRECT','!--:)defUNLIKELY');
}

/**
 * Get a well formed URL equivalent to the current URL. Reads direct from the environment and does no clever mapping at all. This function should rarely be used.
 *
 * @return URLPATH		The URL
 */
function get_self_url_easy()
{
	$protocol=((ocp_srv('HTTPS')!='') && (ocp_srv('HTTPS')!='off'))?'https':'http';
	if (!isset($_SERVER['HTTP_HOST']))
	{
		$domain=get_domain();
	} else
	{
		$domain=$_SERVER['HTTP_HOST'];
	}
	$colon_pos=strpos($domain,':');
	if ($colon_pos!==false) $domain=substr($domain,0,$colon_pos);
	$self_url=$protocol.'://'.$domain;
	$port=ocp_srv('SERVER_PORT');
	if (($port!='') && ($port!='80')) $self_url.=':'.$port;
	$s=ocp_srv('PHP_SELF');
	if (substr($s,0,1)!='/') $self_url.='/';
	$self_url.=$s;
	if ((array_key_exists('QUERY_STRING',$_SERVER)) && ($_SERVER['QUERY_STRING']!='')) $self_url.='?'.$_SERVER['QUERY_STRING'];
	return $self_url;
}

/**
 * Get a well formed URL equivalent to the current URL.
 *
 * @param  boolean		Whether to evaluate the URL (so as we don't return tempcode)
 * @param  boolean		Whether to direct to the default page if there was a POST request leading to where we are now (i.e. to avoid missing post fields when we go to this URL)
 * @param  ?array			A map of extra parameters for the URL (NULL: none)
 * @param  boolean		Whether to also keep POSTed data, in the GET request (useful if either_param is used to get the data instead of post_param - of course the POST data must be of the not--persistent-state-changing variety)
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @return mixed			The URL (tempcode or string)
 */
function get_self_url($evaluate=false,$root_if_posted=false,$extra_params=NULL,$posted_too=false,$avoid_remap=false)
{
	global $SELF_URL_CACHED;
	$cacheable=($evaluate) && (!$root_if_posted) && ($extra_params===NULL) && (!$posted_too) && (!$avoid_remap);
	if (($cacheable) && ($SELF_URL_CACHED!==NULL))
	{
		return $SELF_URL_CACHED;
	}

	if ((isset($_SERVER['PHP_SELF'])) || (isset($_ENV['PHP_SELF'])))
	{
		if (running_script('execute_temp'))
		{
			return get_self_url_easy();
		}
	}

	if ($extra_params===NULL) $extra_params=array();
	if ($posted_too)
	{
		$post_array=array();
		foreach ($_POST as $key=>$val)
		{
			if (is_array($val)) continue;
			if (get_magic_quotes_gpc()) $val=stripslashes($val);
			$post_array[$key]=$val;
		}
		$extra_params=array_merge($post_array,$extra_params);
	}
	$page='_SELF';
	if (($root_if_posted) && (count($_POST)!=0)) $page='';
	$params=array('page'=>$page);
	$skip=array();
	foreach ($extra_params as $key=>$val)
	{
		if ($val===NULL)
		{
			$skip[$key]=NULL;
		} else
		{
			$params[$key]=$val;
		}
	}

	$url=build_url($params,'_SELF',$skip,true,$avoid_remap);
	if ($evaluate)
	{
		$ret=$url->evaluate();
		if ($cacheable)
		{
			$SELF_URL_CACHED=$ret;
		}
		return $ret;
	}

	return $url;
}

/**
 * Encode a URL component in such a way that it won't get nuked by Apache %2F blocking security and url encoded '&' screwing. The get_param function will map it back. Hackerish but necessary.
 *
 * @param  URLPATH		The URL to encode
 * @param  ?boolean		Whether we have to consider mod_rewrite (NULL: don't know, look up)
 * @return URLPATH		The encoded result
 */
function ocp_url_encode($url_part,$consider_rewrite=NULL)
{
	// Slipstream for 99.99% of data
	$url_part_encoded=urlencode($url_part);
	if ($url_part_encoded==$url_part) return $url_part_encoded;

	if ($consider_rewrite===NULL) $consider_rewrite=can_try_mod_rewrite();
	if ($consider_rewrite) // These interfere with mod_rewrite processing because they get pre-decoded and make things ambiguous
	{
//		$url_part=str_replace(':','(colon)',$url_part); We'll ignore theoretical problem here- we won't expect there to be a need for encodings within redirect URL paths (params is fine, handles naturally)
//		$url_part=str_replace(urlencode(':'),urlencode('(colon)'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('/'),urlencode(':slash:'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('&'),urlencode(':amp:'),$url_part); // horrible but mod_rewrite does it so we need to
//		$url_part=str_replace(urlencode('#'),urlencode(':uhash:'),$url_part); // horrible but mod_rewrite does it so we need to
		$url_part=str_replace(array('/','&','#'),array(':slash:',':amp:',':uhash:'),$url_part);
	}
	$url_part=urlencode($url_part);
	return $url_part;
}

/**
 * Encode a URL component, as per ocp_url_encode but without slashes being encoded.
 *
 * @param  URLPATH		The URL to encode
 * @param  ?boolean		Whether we have to consider mod_rewrite (NULL: don't know, look up)
 * @return URLPATH		The encoded result
 */
function ocp_url_encode_mini($url_part,$consider_rewrite=NULL)
{
	// Slipstream for 99.99% of data
	$url_part_encoded=urlencode($url_part);
	if ($url_part_encoded==$url_part) return $url_part_encoded;

	return str_replace('%3Aslash%3A','/',ocp_url_encode($url_part,$consider_rewrite));
}

/**
 * Decode a URL component that was encoded with hackerish_url_encode
 *
 * @param  URLPATH		The URL to encode
 * @return URLPATH		The encoded result
 */
function ocp_url_decode_post_process($url_part)
{
	if ((strpos($url_part,':')!==false) && (can_try_mod_rewrite()))
	{
		$url_part=str_replace(':uhash:','#',$url_part);
		$url_part=str_replace(':amp:','&',$url_part);
		$url_part=str_replace(':slash:','/',$url_part);
//		$url_part=str_replace('(colon)',':',$url_part);
	}
	return $url_part;
}

/**
 * Map spaces to %20 and put http:// in front of URLs starting www.
 *
 * @param  URLPATH		The URL to fix
 * @return URLPATH		The fixed result
 */
function remove_url_mistakes($url)
{
	if (substr($url,0,4)=='www.') $url='http://'.$url;
	$url=@html_entity_decode($url,ENT_NOQUOTES);
	$url=str_replace(' ','%20',$url);
	$url=preg_replace('#keep_session=\d*#','filtered=1',$url);
	return $url;
}

/**
 * Find whether we can skip the normal preservation of a keep value, for whatever reason.
 *
 * @param  string			Parameter name
 * @param  string			Parameter value
 * @return boolean		Whether we can skip it
 */
function skippable_keep($key,$val)
{
	global $BOT_TYPE_CACHE;
	if ($BOT_TYPE_CACHE===false) get_bot_type();
	if ($BOT_TYPE_CACHE!==NULL)
	{
		return true;
	}

	return ((($key=='keep_session') && (isset($_COOKIE['has_cookies']))) || (($key=='keep_has_js') && ($val=='1'))) && ((isset($_COOKIE['js_on'])) || (get_option('detect_javascript')=='0'));
}

/**
 * Are we currently running HTTPS.
 *
 * @return boolean		If we are
 */
function tacit_https()
{
	return ((ocp_srv('HTTPS')!='') && (ocp_srv('HTTPS')!='off'));
}

/**
 * Find whether the specified page is to use HTTPS (if not -- it will use HTTP).
 * All images (etc) on a HTTPS page should use HTTPS to avoid mixed-content browser notices.
 *
 * @param  ID_TEXT		The zone the page is in
 * @param  ID_TEXT		The page codename
 * @return boolean		Whether the page is to run across an HTTPS connection
 */
function is_page_https($zone,$page)
{
	static $off=NULL;
	if ($off===NULL)
	{
		$off=(!addon_installed('ssl')) || (in_safe_mode()) || (!function_exists('persistent_cache_get'));
	}
	if ($off) return false;

	if (($page=='login') && (get_page_name()=='login')) // Because how login can be called from any arbitrary page, which may or may not be on HTTPS. We want to maintain HTTPS if it is there to avoid warning on form submission
	{
		if (tacit_https()) return true;
	}

	global $HTTPS_PAGES_CACHE;
	if (($HTTPS_PAGES_CACHE===NULL) && (function_exists('persistent_cache_get')))
		$HTTPS_PAGES_CACHE=persistent_cache_get('HTTPS_PAGES_CACHE');
	if ($HTTPS_PAGES_CACHE===NULL)
	{
		$results=$GLOBALS['SITE_DB']->query_select('https_pages',array('*'),NULL,'',NULL,NULL,true);
		if (($results===false) || ($results===NULL)) // No HTTPS support (probably not upgraded yet)
		{
			$HTTPS_PAGES_CACHE=array();
			return false;
		}
		$HTTPS_PAGES_CACHE=collapse_1d_complexity('https_page_name',$results);
		if (function_exists('persistent_cache_set'))
			persistent_cache_set('HTTPS_PAGES_CACHE',$HTTPS_PAGES_CACHE);
	}
	return in_array($zone.':'.$page,$HTTPS_PAGES_CACHE);
}

/**
 * Find if mod_rewrite is in use
 *
 * @param  boolean		Whether to explicitly avoid using mod_rewrite. Whilst it might seem weird to put this in as a function parameter, it removes duplicated logic checks in the code.
 * @return boolean		Whether mod_rewrite is in use
 */
function can_try_mod_rewrite($avoid_remap=false)
{
	if (!function_exists('get_option')) return false;
	$url_scheme=get_option('url_scheme',false);
	return (($url_scheme!==NULL) && ($url_scheme!=='RAW') && ((!array_key_exists('block_mod_rewrite',$GLOBALS['SITE_INFO'])) || ($GLOBALS['SITE_INFO']['block_mod_rewrite']=='0')) && (!$avoid_remap) && ((get_value('ionic_on','0')=='1') || (ocp_srv('SERVER_SOFTWARE')=='') || (substr(ocp_srv('SERVER_SOFTWARE'),0,6)=='Apache') || (substr(ocp_srv('SERVER_SOFTWARE'),0,5)=='IIS7/') || (substr(ocp_srv('SERVER_SOFTWARE'),0,5)=='IIS8/') || (substr(ocp_srv('SERVER_SOFTWARE'),0,10)=='LiteSpeed'))); // If we don't have the option on or are not using apache, return
}

/**
 * Build and return a proper URL, from the $vars array.
 * Note: URL parameters should always be in lower case (one of the coding standards)
 *
 * @param  array			A map of parameter names to parameter values. E.g. array('page'=>'example','type'=>'foo','id'=>2). Values may be strings or integers, or Tempcode, or NULL. NULL indicates "skip this". 'page' cannot be NULL.
 * @param  ID_TEXT		The zone the URL is pointing to. YOU SHOULD NEVER HARD CODE THIS- USE '_SEARCH', '_SELF' (if you're self-referencing your own page) or the output of get_module_zone.
 * @param  ?array			Variables to explicitly not put in the URL (perhaps because we have $keep_all set, or we are blocking certain keep_ values). The format is of a map where the keys are the names, and the values are 1. (NULL: don't skip any)
 * @param  boolean		Whether to keep all non-skipped parameters that were in the current URL, in this URL
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @param  boolean		Whether to skip actually putting on keep_ parameters (rarely will this skipping be desirable)
 * @param  string			Hash portion of the URL (blank: none). May or may not start '#' - code will put it on if needed
 * @return tempcode		The URL in tempcode format.
 */
function build_url($vars,$zone_name='',$skip=NULL,$keep_all=false,$avoid_remap=false,$skip_keep=false,$hash='')
{
	if (empty($vars['page'])) // For SEO purposes we need to make sure we get the right URL
	{
		$vars['page']=get_zone_default_page($zone_name);
		if ($vars['page']===NULL) $vars['page']='start';
	}

	$id=isset($vars['id'])?$vars['id']:NULL;

	$page_link=make_string_tempcode($zone_name.':'./*urlencode not needed in reality, performance*/($vars['page']));
	if ((isset($vars['type'])) || (array_key_exists('type',$vars)))
	{
		if (is_object($vars['type']))
		{
			$page_link->attach(':');
			$page_link->attach($vars['type']);
		} else
		{
			$page_link->attach(':'.(($vars['type']===NULL)?'<null>':urlencode($vars['type'])));
		}
		unset($vars['type']);
		if ((isset($id)) || (array_key_exists('id',$vars)))
		{
			if (is_integer($id))
			{
				$page_link->attach(':'.strval($id));
			} elseif (is_object($id))
			{
				$page_link->attach(':');
				$page_link->attach($id);
			} else
			{
				$page_link->attach(':'.(($id===NULL)?'<null>':urlencode($id)));
			}
			unset($vars['id']);
		}
	}

	foreach ($vars as $key=>$val)
	{
		if (is_integer($val)) $val=strval($val);
		if ($val===NULL) $val='<null>';

		if ($key!='page')
		{
			if (is_object($val))
			{
				$page_link->attach(':'.$key.'=');
				$page_link->attach($val);
			} else
			{
				$page_link->attach(':'.$key.'='.(($val===NULL)?'<null>':urlencode($val)));
			}
		}
	}

	if (($hash!='') && (substr($hash,0,1)!='#')) $hash='#'.$hash;

	$page_link->attach($hash);

	$arr=array(
		$page_link,
		$avoid_remap?'1':'0',
		$skip_keep?'1':'0',
		$keep_all?'1':'0'
	);
	if ($skip!==NULL) $arr[]=implode(',',array_keys($skip));

	$ret=symbol_tempcode('PAGE_LINK',$arr);
	global $SITE_INFO;
	if ((isset($SITE_INFO['no_keep_params'])) && ($SITE_INFO['no_keep_params']=='1') && (!is_numeric($id)/*i.e. not going to trigger a URL moniker query*/) && (strpos($id,'/')!==false))
	{
		$ret=make_string_tempcode($ret->evaluate());
	}
	return $ret;
}

/**
 * Find whether URL monikers are enabled.
 *
 * @return boolean		Whether URL monikers are enabled.
 */
function url_monikers_enabled()
{
	if (!function_exists('get_option')) return false;
	if (get_param_integer('keep_simpleurls',0)==1) return false;
	if (get_option('url_monikers_enabled',true)!=='1') return false;
	return true;
}

/**
 * Build and return a proper URL, from the $vars array.
 * Note: URL parameters should always be in lower case (one of the coding standards)
 *
 * @param  array			A map of parameter names to parameter values. Values may be strings or integers, or NULL. NULL indicates "skip this". 'page' cannot be NULL.
 * @param  ID_TEXT		The zone the URL is pointing to. YOU SHOULD NEVER HARD CODE THIS- USE '_SEARCH', '_SELF' (if you're self-referencing your own page) or the output of get_module_zone.
 * @param  ?array			Variables to explicitly not put in the URL (perhaps because we have $keep_all set, or we are blocking certain keep_ values). The format is of a map where the keys are the names, and the values are 1. (NULL: don't skip any)
 * @param  boolean		Whether to keep all non-skipped parameters that were in the current URL, in this URL
 * @param  boolean		Whether to avoid mod_rewrite (sometimes essential so we can assume the standard URL parameter addition scheme in templates)
 * @param  boolean		Whether to skip actually putting on keep_ parameters (rarely will this skipping be desirable)
 * @param  string			Hash portion of the URL (blank: none).
 * @return string			The URL in string format.
 */
function _build_url($vars,$zone_name='',$skip=NULL,$keep_all=false,$avoid_remap=false,$skip_keep=false,$hash='')
{
	global $HAS_KEEP_IN_URL_CACHE,$USE_REWRITE_PARAMS_CACHE,$BOT_TYPE_CACHE,$WHAT_IS_RUNNING_CACHE,$KNOWN_AJAX;

	// Build up our URL base
	$stub=get_base_url(is_page_https($zone_name,isset($vars['page'])?$vars['page']:''),$zone_name);
	$stub.='/';

	if (($BOT_TYPE_CACHE!==NULL) && (get_bot_type()!==NULL))
	{
		foreach ($vars as $key=>$val)
		{
			if ($key=='redirect') unset($vars[$key]);
			if ((substr($key,0,5)=='keep_') && (skippable_keep($key,$val))) unset($vars[$key]);
		}
	}

	// Things we need to keep in the url
	$keep_actual=array();
	if (($HAS_KEEP_IN_URL_CACHE===NULL) || ($HAS_KEEP_IN_URL_CACHE) || ($keep_all))
	{
		$mc=get_magic_quotes_gpc();

		$keep_cant_use=array();
		$HAS_KEEP_IN_URL_CACHE=false;
		foreach ($_GET as $key=>$val)
		{
			if (is_array($val))
			{
				if (is_null($val)) continue;

				if ($keep_all)
				{
					if ((!array_key_exists($key,$vars)) && (!isset($skip[$key])))
					{
						_handle_array_var_append($key,$val,$vars);
					}
				}
				continue;
			}

			$is_keep=false;
			$appears_keep=(($key[0]=='k') && (substr($key,0,5)=='keep_'));
			if ($appears_keep)
			{
				if ((!$skip_keep) && (!skippable_keep($key,$val)))
					$is_keep=true;
				$HAS_KEEP_IN_URL_CACHE=true;
			}
			if (((($keep_all) && (!$appears_keep)) || ($is_keep)) && (!array_key_exists($key,$vars)) && (!isset($skip[$key])))
			{
				if ($mc) $val=stripslashes($val);
				if ($is_keep) $keep_actual[$key]=$val;
				else $vars[$key]=$val;
			} elseif ($is_keep)
			{
				if ($mc) $val=stripslashes($val);
				$keep_cant_use[$key]=$val;
			}
		}

		$vars+=$keep_actual;
	}

	global $URL_MONIKERS_ENABLED_CACHE;
	if ($URL_MONIKERS_ENABLED_CACHE===NULL) $URL_MONIKERS_ENABLED_CACHE=url_monikers_enabled();
	if ($URL_MONIKERS_ENABLED_CACHE)
	{
		$test=find_id_moniker($vars,$zone_name);
		if ($test!==NULL)
		{
			if (substr($test,0,1)=='/') // relative to zone root
			{
				$parts=explode('/',substr($test,1),3);
				$vars['page']=$parts[0];
				if (isset($parts[1])) $vars['type']=$parts[1]; else unset($vars['type']);
				if (isset($parts[2])) $vars['id']=$parts[2]; else unset($vars['id']);
			} else // relative to content module
			{
				if (array_key_exists('id',$vars))
				{
					$vars['id']=$test;
				} else
				{
					$vars['page']=$test;
				}
			}
		}
	}

	// We either use mod_rewrite, or return a standard parameterisation
	if (($USE_REWRITE_PARAMS_CACHE===NULL) || ($avoid_remap))
	{
		$use_rewrite_params=can_try_mod_rewrite($avoid_remap);
		if (!$avoid_remap) $USE_REWRITE_PARAMS_CACHE=$use_rewrite_params;
	} else $use_rewrite_params=$USE_REWRITE_PARAMS_CACHE;
	$test_rewrite=NULL;
	$self_page=((!isset($vars['page'])) || ((function_exists('get_zone_name')) && (get_zone_name()==$zone_name) && (($vars['page']=='_SELF') || ($vars['page']==get_param('page',''))))) && ((!isset($vars['type'])) || ($vars['type']==get_param('type',''))) && ($hash!='_top') && (!$KNOWN_AJAX);
	if ($use_rewrite_params)
	{
		if ((!$self_page) || ($WHAT_IS_RUNNING_CACHE==='index'))
		{
			$test_rewrite=_url_rewrite_params($zone_name,$vars,count($keep_actual)>0);
		}
	}
	if ($test_rewrite===NULL)
	{
		$url=(($self_page) && ($WHAT_IS_RUNNING_CACHE!=='index'))?find_script($WHAT_IS_RUNNING_CACHE):($stub.'index.php');

		// Fix sort order
		if (isset($vars['id']))
		{
			$_vars=$vars;
			unset($_vars['id']);
			$vars=array('id'=>$vars['id'])+$_vars;
		}
		if (isset($vars['type']))
		{
			$_vars=$vars;
			unset($_vars['type']);
			$vars=array('type'=>$vars['type'])+$_vars;
		}
		if (isset($vars['page']))
		{
			$_vars=$vars;
			unset($_vars['page']);
			$vars=array('page'=>$vars['page'])+$_vars;
		}

		// Build up the URL string
		$symbol='?';
		foreach ($vars as $key=>$val)
		{
			if ($val===NULL) continue; // NULL means skip

			if ($val===SELF_REDIRECT) $val=get_self_url(true,true);

			// Add in
			$url.=$symbol.$key.'='.(is_integer($val)?strval($val):/*ocp_*/urlencode($val/*,false*/));
			$symbol='&';
		}
	} else
	{
		$url=$stub.$test_rewrite;
	}

	// Done
	return $url.$hash;
}

/**
 * Recursively put array parameters into a flat array for use in a query string.
 *
 * @param  ID_TEXT		Primary field name
 * @param  array			Array
 * @param  array			Flat array to write into
 */
function _handle_array_var_append($key,$val,&$vars)
{
	$val2=mixed();

	foreach ($val as $key2=>$val2)
	{
		if (get_magic_quotes_gpc()) $val2=stripslashes($val2);
		if (!is_string($key2)) $key2=strval($key2);

		if (is_array($val2))
		{
			_handle_array_var_append($key.'['.$key2.']',$val2,$vars);
		} else
		{
			$vars[$key.'['.$key2.']']=$val2;
		}
	}
}

/**
 * Attempt to use mod_rewrite to improve this URL.
 *
 * @param  ID_TEXT		The name of the zone for this
 * @param  array			A map of variables to include in our URL
 * @param  boolean		Force inclusion of the index.php name into a short URL, so something may tack on extra parameters to the result here
 * @return ?URLPATH		The improved URL (NULL: couldn't do anything)
 */
function _url_rewrite_params($zone_name,$vars,$force_index_php=false)
{
	global $URL_REMAPPINGS;
	if ($URL_REMAPPINGS===NULL)
	{
		require_code('url_remappings');
		$URL_REMAPPINGS=get_remappings(get_option('url_scheme'));
		foreach ($URL_REMAPPINGS as $i=>$_remapping)
		{
			$URL_REMAPPINGS[$i][3]=count($_remapping[0]);
		}
	}

	static $url_scheme=NULL;
	if ($url_scheme===NULL) $url_scheme=get_option('url_scheme');

	// Find mapping
	foreach ($URL_REMAPPINGS as $_remapping)
	{
		list($remapping,$target,$require_full_coverage,$last_key_num)=$_remapping;
		$good=true;

		$loop_cnt=0;
		foreach ($remapping as $key=>$val)
		{
			$loop_cnt++;
			$last=($loop_cnt==$last_key_num);

			if ((isset($vars[$key])) && (is_integer($vars[$key]))) $vars[$key]=strval($vars[$key]);

			if (!(((isset($vars[$key])) || (($val===NULL) && ($key=='type') && ((isset($vars['id'])) || (array_key_exists('id',$vars))))) && (($key!='page') || ($vars[$key]!='') || ($val==='')) && ((!isset($vars[$key])&&!array_key_exists($key,$vars)/*NB this is just so the next clause does not error, we have other checks for non-existence*/) || ($vars[$key]!='') || (!$last)) && (($val===NULL) || ($vars[$key]==$val))))
			{
				$good=false;
				break;
			}
		}

		if ($require_full_coverage)
		{
			foreach ($_GET as $key=>$val)
			{
				if (!is_string($val)) continue;

				if ((substr($key,0,5)=='keep_')  && (!skippable_keep($key,$val))) $good=false;
			}
			foreach ($vars as $key=>$val)
			{
				if ((!array_key_exists($key,$remapping)) && ($val!==NULL) && (($key!='page') || ($vars[$key]!='')))
					$good=false;
			}
		}
		if ($good)
		{
			// We've found one, now let's sort out the target
			$makeup=$target;
			if ($GLOBALS['DEV_MODE'])
			{
				foreach ($vars as $key=>$val)
				{
					if (is_integer($val)) $vars[$key]=strval($val);
				}
			}

			$extra_vars=array();
			foreach (array_keys($remapping) as $key)
			{
				if (!isset($vars[$key])) continue;

				$val=$vars[$key];
				unset($vars[$key]);

				$makeup=str_replace(strtoupper($key),ocp_url_encode_mini($val,true),$makeup);
			}
			if (!$require_full_coverage)
			{
				$extra_vars+=$vars;
			}
			$makeup=str_replace('TYPE','misc',$makeup);
			if ($makeup=='')
			{
				switch ($url_scheme)
				{
					case 'HTM':
						$makeup.=get_zone_default_page($zone_name).'.htm';
						break;

					case 'SIMPLE':
						$makeup.=get_zone_default_page($zone_name);
						break;
				}
			}
			if (($extra_vars!=array()) || ($force_index_php))
			{
				if ($url_scheme=='PG') $makeup.='/index.php';

				$first=true;
				foreach ($extra_vars as $key=>$val) // Add these in explicitly
				{
					if ($val===NULL) continue;
					if ($val===SELF_REDIRECT) $val=get_self_url(true,true);
					$makeup.=($first?'?':'&').$key.'='.ocp_url_encode($val,true);
					$first=false;
				}
			}

			return $makeup;
		}
	}

	return NULL;
}

/**
 * Find if the specified URL is local or not (actually, if it is relative). This is often used by code that wishes to use file system functions on URLs (ocPortal will store such relative local URLs for uploads, etc)
 *
 * @param  URLPATH		The URL to check
 * @return boolean		Whether the URL is local
 */
function url_is_local($url)
{
	if (preg_match('#^[^:\{%]*$#',$url)!=0) return true;
	return (strpos($url,'://')===false) && (substr($url,0,1)!='{') && (substr($url,0,7)!='mailto:') && (substr($url,0,5)!='data:') && (substr($url,0,1)!='%') && (strpos($url,'{$BASE_URL')===false) && (strpos($url,'{$FIND_SCRIPT')===false);
}

/**
 * Find if a value appears to be some kind of URL (possibly an ocPortalised Comcode one).
 *
 * @param  string			The value to check
 * @param  boolean		Whether to be a bit lax in the check
 * @return boolean		Whether the value appears to be a URL
 */
function looks_like_url($value,$lax=false)
{
	if (($lax) && (strpos($value,'/')!==false)) return true;
	if (($lax) && (substr($value,0,1)=='%')) return true;
	if (($lax) && (substr($value,0,1)=='{')) return true;
	return (((strpos($value,'.php')!==false) || (strpos($value,'.htm')!==false) || (substr($value,0,1)=='#') || (substr($value,0,15)=='{$TUTORIAL_URL') || (substr($value,0,13)=='{$FIND_SCRIPT') || (substr($value,0,17)=='{$BRAND_BASE_URL') || (substr($value,0,10)=='{$BASE_URL') || (substr(strtolower($value),0,11)=='javascript:') || (substr($value,0,4)=='tel:') || (substr($value,0,7)=='mailto:') || (substr($value,0,7)=='http://') || (substr($value,0,8)=='https://') || (substr($value,0,3)=='../') || (substr($value,0,7)=='sftp://') || (substr($value,0,6)=='ftp://'))) && (strpos($value,'<')===false);
}

/**
 * Get hidden fields for a form representing 'keep_x'. If we are having a GET form instead of a POST form, we need to do this. This function also encodes the page name, as we'll always want that.
 *
 * @param  ID_TEXT		The page for the form to go to (blank: don't attach)
 * @param  boolean		Whether to keep all elements of the current URL represented in this form (rather than just the keep_ fields, and page)
 * @param  ?array			A list of parameters to exclude (NULL: don't exclude any)
 * @return tempcode		The builtup hidden form fields
 */
function build_keep_form_fields($page='',$keep_all=false,$exclude=NULL)
{
	require_code('urls2');
	return _build_keep_form_fields($page,$keep_all,$exclude);
}

/**
 * Relay all POST variables for this URL, to the URL embedded in the form.
 *
 * @param  ?array			A list of parameters to exclude (NULL: exclude none)
 * @return tempcode		The builtup hidden form fields
 */
function build_keep_post_fields($exclude=NULL)
{
	require_code('urls2');
	return _build_keep_post_fields($exclude);
}

/**
 * Takes a URL, and converts it into a file system storable filename. This is used to cache URL contents to the servers filesystem.
 *
 * @param  URLPATH		The URL to convert to an encoded filename
 * @return string			A usable filename based on the URL
 */
function url_to_filename($url_full)
{
	require_code('urls2');
	return _url_to_filename($url_full);
}

/**
 * Take a URL and base-URL, and fully qualify the URL according to it.
 *
 * @param  URLPATH		The URL to fully qualified
 * @param  URLPATH		The base-URL
 * @return URLPATH		Fully qualified URL
 */
function qualify_url($url,$url_base)
{
	require_code('urls2');
	return _qualify_url($url,$url_base);
}

/**
 * Take a page link and convert to attributes and zone.
 *
 * @param  SHORT_TEXT	The page link
 * @return array			Triple: zone, attribute-array, hash part of a URL including the hash (or blank)
 */
function page_link_decode($param)
{
	global $CHR_0;

	if (strpos($param,'#')===false)
	{
		$hash='';
	} else
	{
		$hash_pos=strpos($param,'#');
		$hash=substr($param,$hash_pos);
		$param=substr($param,0,$hash_pos);
	}
	if (strpos($param,$CHR_0)===false)
	{
		$bits=explode(':',$param);
	} else // If there's a line break then we ignore any colons after that line-break. It's to allow complex stuff to be put on the end of the page-link
	{
		$term_pos=strpos($param,$CHR_0);
		$bits=explode(':',substr($param,0,$term_pos));
		$bits[count($bits)-1].=substr($param,$term_pos);
	}
	$zone=$bits[0];
	if ($zone=='_SEARCH')
	{
		if (isset($bits[1]))
		{
			$zone=get_page_zone($bits[1],false);
			if ($zone===NULL) $zone='';
		} else $zone='';
	} elseif (($zone=='site') && (get_option('collapse_user_zones')=='1')) $zone='';
	elseif ($zone=='_SELF') $zone=get_zone_name();
	if (isset($bits[1]))
	{
		if ($bits[1]!='')
		{
			if ($bits[1]=='_SELF')
			{
				$attributes=array('page'=>get_page_name());
			} else
			{
				$attributes=array('page'=>$bits[1]);
			}
		} else
		{
			$attributes=array();
		}
		unset($bits[1]);
	} else
	{
		$attributes=array('page'=>get_zone_default_page($zone));
	}
	unset($bits[0]);
	$i=0;
	foreach ($bits as $bit)
	{
		if (($bit!='') || ($i==1))
		{
			if (($i==0) && (strpos($bit,'=')===false))
			{
				$_bit=array('type',$bit);
			}
			elseif (($i==1) && (strpos($bit,'=')===false))
			{
				$_bit=array('id',$bit);
			} else
			{
				$_bit=explode('=',$bit,2);
			}
		} else
		{
			$_bit=array($bit,'');
		}
		if (isset($_bit[1]))
		{
			$decoded=urldecode($_bit[1]);
			if (($decoded!='') && ($decoded[0]=='{') && (strlen($decoded)>2) && (intval($decoded[1])>51)) // If it is in template format (symbols)
			{
				require_code('tempcode_compiler');
				$decoded=template_to_tempcode($decoded);
				$decoded=$decoded->evaluate();
			}
			if ($decoded=='<null>')
			{
				$attributes[$_bit[0]]=NULL;
			} else
			{
				$attributes[$_bit[0]]=$decoded;
			}
		}

		$i++;
	}

	return array($zone,$attributes,$hash);
}

/**
 * Convert a URL to a local file path.
 *
 * @param  URLPATH		The value to convert
 * @return ?PATH			File path (NULL: is not local)
 */
function convert_url_to_path($url)
{
	require_code('urls2');
	return _convert_url_to_path($url);
}

/**
 * Sometimes users don't enter full URLs but do intend for them to be absolute. This code tries to see what relative URLs are actually absolute ones, via an algorithm. It then fixes the URL.
 *
 * @param  URLPATH		The URL to fix
 * @return URLPATH		The fixed URL (or original one if no fix was needed)
 */
function fixup_protocolless_urls($in)
{
	require_code('urls2');
	return _fixup_protocolless_urls($in);
}

/**
 * Convert a local URL to a page-link.
 *
 * @param  URLPATH		The URL to convert. Note it may not be a short URL, and it must be based on the local base URL (else failure WILL occur).
 * @param  boolean		Whether to only convert absolute URLs. Turn this on if you're not sure what you're passing is a URL not and you want to be extra safe.
 * @param  boolean		Whether to only allow perfect conversions.
 * @return string			The page link (blank: could not convert).
 */
function url_to_pagelink($url,$abs_only=false,$perfect_only=true)
{
	require_code('urls2');
	return _url_to_pagelink($url,$abs_only,$perfect_only);
}

/**
 * Convert a local page file path to a written page-link.
 *
 * @param  string			The path.
 * @return string			The page link (blank: could not convert).
 */
function page_path_to_pagelink($page)
{
	require_code('urls2');
	return _page_path_to_pagelink($page);
}

/**
 * Load up hooks needed to detect how to use monikers.
 */
function load_moniker_hooks()
{
	global $CONTENT_OBS;
	if ($CONTENT_OBS===NULL)
	{
		$CONTENT_OBS=function_exists('persistent_cache_get')?persistent_cache_get('CONTENT_OBS'):NULL;
		if ($CONTENT_OBS!==NULL)
		{
			foreach ($CONTENT_OBS as $ob_info)
			{
				if (($ob_info['title_field']!==NULL) && (strpos($ob_info['title_field'],'CALL:')!==false))
					require_code('hooks/systems/content_meta_aware/'.$ob_info['_hook']);
			}

			return;
		}

		$CONTENT_OBS=array();
		$hooks=find_all_hooks('systems','content_meta_aware');
		foreach ($hooks as $hook=>$sources_dir)
		{
			if ($hook=='banner' || $hook=='banner_type' || $hook=='catalogue' || $hook=='post') continue; // FUDGEFUDGE: Optimisation, not ideal!

			$info_function=extract_module_functions(get_file_base().'/'.$sources_dir.'/hooks/systems/content_meta_aware/'.$hook.'.php',array('info'),NULL,false,'Hook_content_meta_aware_'.$hook);
			if ($info_function[0]!==NULL)
			{
				$ob_info=is_array($info_function[0])?call_user_func_array($info_function[0][0],$info_function[0][1]):eval($info_function[0]);

				if ($ob_info===NULL) continue;
				$ob_info['_hook']=$hook;
				$CONTENT_OBS[$ob_info['view_pagelink_pattern']]=$ob_info;

				if (($ob_info['title_field']!==NULL) && (strpos($ob_info['title_field'],'CALL:')!==false))
					require_code('hooks/systems/content_meta_aware/'.$hook);
			}
		}

		if (function_exists('persistent_cache_set')) persistent_cache_set('CONTENT_OBS',$CONTENT_OBS);
	}
}

/**
 * Find the textual moniker for a typical ocPortal URL path. This will be called from inside build_url, based on details learned from a moniker hook (only if a hook exists to hint how to make the requested link SEO friendly).
 *
 * @param  array			The URL component map (must contain 'page', 'type', and 'id' if this function is to do anything).
 * @param  ID_TEXT		The URL zone name (only used for Comcode Page URL monikers).
 * @return ?string		The moniker ID (NULL: could not find)
 */
function find_id_moniker($url_parts,$zone)
{
	if (!isset($url_parts['page'])) return NULL;

	// Does this URL arrangement support monikers?
	global $CONTENT_OBS;
	load_moniker_hooks();
	if (!array_key_exists('id',$url_parts))
	{
		$effective_id=$url_parts['page'];

		if (!is_file(get_custom_file_base().'/'.$zone.'/pages/comcode_custom/EN/'.$effective_id.'.txt')) return NULL;

		$url_parts['type']='';
		$url_parts['id']=$zone;

		$looking_for='_WILD:_WILD';
	} else
	{
		if (!isset($url_parts['type'])) $url_parts['type']='misc';
		if ($url_parts['type']===NULL) $url_parts['type']='misc'; // NULL means "do not take from environment"; so we default it to 'misc' (even though it might actually be left out when SEO URLs are off, we know it cannot be for SEO URLs)

		if (array_key_exists('id',$url_parts))
		{
			if ($url_parts['id']===NULL) $url_parts['id']=strval(db_get_first_id());
		}

		$effective_id=$url_parts['id'];

		$looking_for='_SEARCH:'.$url_parts['page'].':'.$url_parts['type'].':_WILD';
	}
	$ob_info=isset($CONTENT_OBS[$looking_for])?$CONTENT_OBS[$looking_for]:NULL;
	if ($ob_info===NULL) return NULL;

	if ($ob_info['id_field_numeric'])
	{
		if (!is_numeric($effective_id)) return NULL;
	} else
	{
		if (strpos($effective_id,'/')!==false) return NULL;
	}

	if ($ob_info['support_url_monikers'])
	{
	   // Has to find existing if already there
		global $LOADED_MONIKERS_CACHE;
		if (isset($LOADED_MONIKERS_CACHE[$url_parts['page']][$url_parts['type']][$effective_id]))
		{
			if (is_bool($LOADED_MONIKERS_CACHE[$url_parts['page']][$url_parts['type']][$effective_id])) // Ok, none pre-loaded yet, so we preload all and replace the boolean values with actual results
			{
				$or_list='';
				foreach ($LOADED_MONIKERS_CACHE as $page=>$types)
				{
					foreach ($types as $type=>$ids)
					{
						foreach ($ids as $id=>$status)
						{
							if (!is_bool($status)) continue;

							if (is_integer($id)) $id=strval($id);

							if ($or_list!='') $or_list.=' OR ';
							$or_list.='('.db_string_equal_to('m_resource_page',$page).' AND '.db_string_equal_to('m_resource_type',$type).' AND '.db_string_equal_to('m_resource_id',$id).')';
						}
					}
				}
				$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
				$GLOBALS['NO_DB_SCOPE_CHECK']=true;
				$query='SELECT m_moniker,m_resource_page,m_resource_type,m_resource_id FROM '.get_table_prefix().'url_id_monikers WHERE m_deprecated=0 AND ('.$or_list.')';
				$results=$GLOBALS['SITE_DB']->query($query,NULL,NULL,false,true);
				$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
				foreach ($results as $result)
				{
					$LOADED_MONIKERS_CACHE[$result['m_resource_page']][$result['m_resource_type']][$result['m_resource_id']]=$result['m_moniker'];
				}
			}
			$test=$LOADED_MONIKERS_CACHE[$url_parts['page']][$url_parts['type']][$effective_id];
		} else
		{
			$bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
			$GLOBALS['NO_DB_SCOPE_CHECK']=true;
			$where=array(
				'm_deprecated'=>0,
				'm_resource_page'=>$url_parts['page'],
				'm_resource_type'=>$url_parts['type'],
				'm_resource_id'=>is_integer($effective_id)?strval($effective_id):$effective_id,
			);
			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers','m_moniker',$where);
			$GLOBALS['NO_DB_SCOPE_CHECK']=$bak;
			$LOADED_MONIKERS_CACHE[$url_parts['page']][$url_parts['type']][$effective_id]=$test;
		}
		if (is_string($test)) return ($test=='')?NULL:$test;

		// Otherwise try to generate a new one
		require_code('urls2');
		$test=autogenerate_new_url_moniker($ob_info,$url_parts,$zone);
		if ($test===NULL) $test='';
		$LOADED_MONIKERS_CACHE[$url_parts['page']][$url_parts['type']][$effective_id]=$test;
		return ($test=='')?NULL:$test;
	}

	return NULL;
}

/**
 * Change whatever global context that is required in order to run from a different context.
 *
 * @sets_input_state
 *
 * @param  array			The URL component map (must contain 'page').
 * @param  ID_TEXT		The zone.
 * @param  ID_TEXT		The running script.
 * @param  boolean		Whether to get rid of keep_ variables in current URL.
 * @return array			A list of parameters that would be required to be passed back to reset the state.
 */
function set_execution_context($new_get,$new_zone='_SEARCH',$new_current_script='index',$erase_keep_also=false)
{
	$old_get=$_GET;
	$old_zone=get_zone_name();
	$old_current_script=current_script();

	foreach ($_GET as $key=>$val)
	{
		if ((substr($key,0,5)!='keep_') || ($erase_keep_also)) unset($_GET[$key]);
	}

	foreach ($new_get as $key=>$val)
	{
		$_GET[$key]=is_integer($val)?strval($val):$val;
	}

	global $RELATIVE_PATH,$ZONE;
	$RELATIVE_PATH=($new_zone=='_SEARCH')?get_page_zone(get_param('page')):$new_zone;
	$ZONE=NULL; // So zone details will have to reload

	global $PAGE_NAME_CACHE;
	$PAGE_NAME_CACHE=NULL;
	global $RUNNING_SCRIPT_CACHE,$WHAT_IS_RUNNING_CACHE;
	$RUNNING_SCRIPT_CACHE=array();
	$WHAT_IS_RUNNING_CACHE=$new_current_script;

	return array($old_get,$old_zone,$old_current_script,true);
}

