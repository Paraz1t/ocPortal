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
 * @package		core_feedback_features
 */

/**
 * Standard code module initialisation function.
 */
function init__feedback()
{
	if (!defined('MAX_LIKES_TO_SHOW'))
	{
		define('MAX_LIKES_TO_SHOW',20);
	}

	if (!defined('RATING_TYPE_star_choice'))
	{
		define('RATING_TYPE_star_choice',0);
		define('RATING_TYPE_like_dislike',1);
	}

	global $RATINGS_STRUCTURE;
	$RATINGS_STRUCTURE=array();
	global $REVIEWS_STRUCTURE;
	$REVIEWS_STRUCTURE=array();

	global $RATING_DETAILS_CACHE;
	$RATING_DETAILS_CACHE=array();
}

/**
 * Find who submitted a piece of feedbackable content.
 *
 * @param  ID_TEXT		Content type
 * @param  ID_TEXT		Content ID
 * @return array			A tuple: Content title (NULL: unknown), Submitter (NULL: unknown), URL (for use within current browser session), URL (for use in emails / sharing), Content meta aware info array
 */
function get_details_behind_feedback_code($content_type,$content_id)
{
	require_code('content');

	$content_type=convert_ocportal_type_codes('feedback_type_code',$content_type,'content_type');
	if ($content_type!='')
	{
		require_code('hooks/systems/content_meta_aware/'.$content_type);
		$cma_ob=object_factory('Hook_content_meta_aware_'.$content_type);
		$info=$cma_ob->info();
		list($content_title,$submitter_id,$cma_info,,$content_url,$content_url_email_safe)=content_get_details($content_type,$content_id);
		return array($content_title,$submitter_id,$content_url,$content_url_email_safe,$cma_info);
	}

	return array(NULL,NULL,NULL,NULL,NULL);
}

/**
 * Given a particular bit of feedback content, check if the user may access it.
 *
 * @param  MEMBER			User to check
 * @param  ID_TEXT		Content type
 * @param  ID_TEXT		Content ID
 * @return boolean		Whether there is permission
 */
function may_view_content_behind_feedback_code($member_id,$content_type,$content_id)
{
	require_code('content');

	$permission_type_code=convert_ocportal_type_codes('feedback_type_code',$content_type,'permissions_type_code');

	$module=convert_ocportal_type_codes('feedback_type_code',$content_type,'module');
	if ($module=='') $module=$content_id;

	$category_id=mixed();
	$content_type=convert_ocportal_type_codes('feedback_type_code',$content_type,'content_type');
	if ($content_type!='')
	{
		require_code('hooks/systems/content_meta_aware/'.$content_type);
		$content_type_ob=object_factory('Hook_content_meta_aware_'.$content_type);
		$info=$content_type_ob->info();
		if (isset($info['category_field']))
		{
			list(,,,$content)=content_get_details($content_type,$content_id);
			if (!is_null($content))
			{
				$category_field=$info['category_field'];
				if (is_array($category_field))
				{
					$category_field=array_pop($category_field);
					$category_id=is_integer($content[$category_field])?strval($content[$category_field]):$content[$category_field];
					if ($award_hook=='catalogue_entry')
					{
						$catalogue_name=$GLOBALS['SITE_DB']->query_select_value('catalogue_categories','c_name',array('id'=>$category_id));
						if (!has_category_access($member_id,'catalogues_catalogue',$catalogue_name))
							return false;
					}
				} else
				{
					$category_id=is_integer($content[$category_field])?strval($content[$category_field]):$content[$category_field];
				}
			}
		}
	}

	return ((has_actual_page_access($member_id,$module)) && (($permission_type_code=='') || (is_null($category_id)) || (has_category_access($member_id,$permission_type_code,$category_id))));
}

/**
 * Main wrapper function to embed miscellaneous feedback systems into a module output.
 *
 * @param  ID_TEXT		The page name
 * @param  ID_TEXT		Content ID
 * @param  BINARY			Whether rating is allowed
 * @param  integer		Whether comments/reviews is allowed (reviews allowed=2)
 * @set 0 1 2
 * @param  BINARY			Whether trackbacks are allowed
 * @param  BINARY			Whether the content is validated
 * @param  ?MEMBER		Content owner (NULL: none)
 * @param  mixed			URL to view the content
 * @param  SHORT_TEXT	Content title
 * @param  ?string		Forum to post comments in (NULL: site-wide default)
 * @return array			Tuple: Rating details, Comment details, Trackback details
 */
function embed_feedback_systems($page_name,$content_id,$allow_rating,$allow_comments,$allow_trackbacks,$validated,$submitter,$content_url,$content_title,$forum)
{
	// Sign up original poster for notifications
	if (get_forum_type()=='ocf')
	{
		$auto_monitor_contrib_content=$GLOBALS['OCF_DRIVER']->get_member_row_field($submitter,'m_auto_monitor_contrib_content');
		if ($auto_monitor_contrib_content==1)
		{
			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('notifications_enabled','l_setting',array(
				'l_member_id'=>$submitter,
				'l_notification_code'=>'comment_posted',
				'l_code_category'=>$page_name.'_'.$content_id,
			));
			if (is_null($test))
			{
				require_code('notifications');
				enable_notifications('comment_posted',$page_name.'_'.$content_id,$submitter);
			}
		}
	}

	actualise_rating($allow_rating==1,$page_name,$content_id,$content_url,$content_title);
	if ((!is_null(post_param('title',NULL))) || ($validated==1))
		actualise_post_comment($allow_comments>=1,$page_name,$content_id,$content_url,$content_title,$forum);
	$rating_details=get_rating_box($content_url,$content_title,$page_name,$content_id,$allow_rating==1,$submitter);
	$comment_details=get_comments($page_name,$allow_comments==1,$content_id,false,$forum,NULL,NULL,false,true,$submitter,$allow_comments==2);
	$trackback_details=get_trackbacks($page_name,$content_id,$allow_trackbacks==1);

	if (is_object($content_url)) $content_url=$content_url->evaluate();

	$serialized_options=serialize(array($page_name,$content_id,$allow_comments,$submitter,$content_url,$content_title,$forum));
	$hash=best_hash($serialized_options,get_site_salt()); // A little security, to ensure $serialized_options is not tampered with

	// AJAX support
	$comment_details->attach(do_template('COMMENT_AJAX_HANDLER',array(
		'_GUID'=>'da533e0f637e4c90ca7ef5a9a23f3203',
		'OPTIONS'=>$serialized_options,
		'HASH'=>$hash,
	)));

	return array($rating_details,$comment_details,$trackback_details);
}

/**
 * Do an AJAX comment post
 */
function post_comment_script()
{
	prepare_for_known_ajax_response();

	// Read in context of what we're doing
	$options=post_param('options');
	list($page_name,$content_id,$allow_comments,$submitter,$content_url,$content_title,$forum)=unserialize($options);

	// Check security
	$hash=post_param('hash');
	if (best_hash($options,get_site_salt())!=$hash)
	{
		header('Content-Type: text/plain; charset='.get_charset());
		exit();
	}

	// Post comment
	actualise_post_comment($allow_comments>=1,$page_name,$content_id,$content_url,$content_title,$forum);

	// Get new comments state
	$comment_details=get_comments($page_name,$allow_comments==1,$content_id,false,$forum,NULL,NULL,false,true,$submitter,$allow_comments==2);

	// And output as text
	header('Content-Type: text/plain; charset='.get_charset());
	$comment_details->evaluate_echo();
}

/**
 * Get tempcode for doing ratings (sits above get_rating_simple_array)
 *
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 * @param  ID_TEXT		The type (download, etc) that this rating is for
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @param  boolean		Whether this resource allows rating (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ?MEMBER		Content owner (NULL: none)
 * @return tempcode		Tempcode for complete rating box
 */
function get_rating_box($content_url,$content_title,$content_type,$content_id,$allow_rating,$submitter=NULL)
{
	if ($allow_rating)
	{
		return display_rating($content_url,$content_title,$content_type,$content_id,'RATING_BOX',$submitter);
	}

	return new ocp_tempcode();
}

/**
 * Display rating using images
 * 
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 * @param  ID_TEXT		The type (download, etc) that this rating is for
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @param  ID_TEXT		The template to use to display the rating box
 * @param  ?MEMBER		Content owner (NULL: none)
 * @return tempcode		Tempcode for complete trackback box
 */
function display_rating($content_url,$content_title,$content_type,$content_id,$display_tpl='RATING_INLINE_STATIC',$submitter=NULL)
{
	$rating_data=get_rating_simple_array($content_url,$content_title,$content_type,$content_id,'RATING_FORM',$submitter);

	if (is_null($rating_data))
		return new ocp_tempcode();

	return do_template($display_tpl,$rating_data);
}

/**
 * Get rating information for the specified resource.
 *
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 * @param  ID_TEXT		The type (download, etc) that this rating is for
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @param  ID_TEXT		The template to use to display the rating box
 * @param  ?MEMBER		Content owner (NULL: none)
 * @return ?array			Current rating information (ready to be passed into a template). RATING is the rating (out of 10), NUM_RATINGS is the number of ratings so far, RATING_FORM is the tempcode of the rating box (NULL: rating disabled)
 */
function get_rating_simple_array($content_url,$content_title,$content_type,$content_id,$form_tpl='RATING_FORM',$submitter=NULL)
{
	if (get_option('is_on_rating')=='1')
	{
		global $RATING_DETAILS_CACHE;
		if (isset($RATING_DETAILS_CACHE[$content_type][$content_id][$form_tpl]))
			return $RATING_DETAILS_CACHE[$content_type][$content_id][$form_tpl];

		$liked_by=mixed();

		// Work out structure first
		global $RATINGS_STRUCTURE;
		$all_rating_criteria=array();
		if (array_key_exists($content_type,$RATINGS_STRUCTURE))
		{
			$likes=($RATINGS_STRUCTURE[$content_type][0]==RATING_TYPE_like_dislike);
			foreach ($RATINGS_STRUCTURE[$content_type][1] as $r=>$t)
			{
				$rating_for_type=$content_type;
				if ($r!='') $rating_for_type.='_'.$r;
				$all_rating_criteria[$rating_for_type]=array('TITLE'=>$t,'TYPE'=>$r,'RATING'=>'0');
			}
		} else
		{
			$likes=(get_option('likes')=='1');
			$all_rating_criteria[$content_type]=array('TITLE'=>'','TYPE'=>'','NUM_RATINGS'=>'0','RATING'=>'0');
		}

		// Fill in structure
		$has_ratings=false;
		$overall_num_ratings=0;
		$overall_rating=0.0;
		foreach ($all_rating_criteria as $i=>$rating_criteria)
		{
			$rating_for_type=$content_type;
			if ($rating_criteria['TYPE']!='') $rating_for_type.='_'.$rating_criteria['TYPE'];

			$_num_ratings=$GLOBALS['SITE_DB']->query_select('rating',array('COUNT(*) AS cnt','SUM(rating) AS compound_rating'),array('rating_for_type'=>$rating_for_type,'rating_for_id'=>$content_id),'',1);
			$num_ratings=$_num_ratings[0]['cnt'];
			if ($num_ratings>0)
			{
				$rating=$_num_ratings[0]['compound_rating'];
				$overall_num_ratings=max($overall_num_ratings,$num_ratings);

				if (($num_ratings<MAX_LIKES_TO_SHOW) && ($likes)) // Show likes
				{
					if (is_null($liked_by)) $liked_by=array();
					if (count($liked_by)<MAX_LIKES_TO_SHOW)
					{
						$_liked_by=$GLOBALS['SITE_DB']->query_select('rating',array('rating_member'),array('rating_for_type'=>$rating_for_type,'rating_for_id'=>$content_id,'rating'=>10));
						foreach ($_liked_by as $l)
						{
							$username=$GLOBALS['FORUM_DRIVER']->get_username($l['rating_member']);
							if (!is_null($username))
							{
								$liked_by[]=array(
									'MEMBER_ID'=>strval($l['rating_member']),
									'USERNAME'=>$username,
								);
								if (count($liked_by)==MAX_LIKES_TO_SHOW) break;
							}
						}
					}
				}

				$calculated_rating=intval(round($rating/floatval($num_ratings)));
				$overall_rating+=$calculated_rating;

				$all_rating_criteria[$i]=array('NUM_RATINGS'=>integer_format($num_ratings),'RATING'=>make_string_tempcode(strval($calculated_rating)))+$all_rating_criteria[$i];

				$extra_meta_data=array();
				$extra_meta_data['rating'.(($rating_criteria['TYPE']=='')?'':('_'.$rating_criteria['TYPE']))]=strval($calculated_rating);
				set_extra_request_metadata($extra_meta_data);

				$has_ratings=true;
			}
		}

		// Work out possible errors that mighr prevent rating being allowed
		$error=new ocp_tempcode();
		$rate_url=new ocp_tempcode();
		if (($submitter===get_member()) && (!is_guest()))
		{
			$error=do_lang_tempcode('RATE_DENIED_OWN');
		}
		elseif (!has_privilege(get_member(),'rate',get_page_name()))
		{
			$error=do_lang_tempcode('RATE_DENIED');
		}
		elseif (already_rated(array_keys($all_rating_criteria),$content_id))
		{
			$error=do_lang_tempcode('NORATE');
		} else
		{
			$rate_url=get_self_url();
		}

		// Templating
		$tpl_params=array(
			'CONTENT_URL'=>$content_url,
			'CONTENT_TITLE'=>$content_title,
			'ERROR'=>$error,
			'CONTENT_TYPE'=>$content_type,
			'ID'=>$content_id,
			'URL'=>$rate_url,
			'ALL_RATING_CRITERIA'=>$all_rating_criteria,
			'OVERALL_NUM_RATINGS'=>integer_format($overall_num_ratings),
			'OVERALL_RATING'=>make_string_tempcode(strval(intval($overall_rating/floatval(count($all_rating_criteria))))),
			'HAS_RATINGS'=>$has_ratings,
			'SIMPLISTIC'=>(count($all_rating_criteria)==1),
			'LIKES'=>$likes,
			'LIKED_BY'=>$liked_by,
		);
		$rating_form=do_template($form_tpl,$tpl_params);
		$ret=$tpl_params+array(
			'RATING_FORM'=>$rating_form,
		);
		$RATING_DETAILS_CACHE[$content_type][$content_id][$form_tpl]=$ret;
		return $ret;
	}
	return NULL;
}

/**
 * Find whether you have rated the specified resource before.
 *
 * @param  array			List of types (download, etc) that this rating is for. All need to be rated for it to return true.
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @return boolean		Whether the resource has already been rated
 */
function already_rated($rating_for_types,$content_id)
{
	if (($GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (get_param_integer('keep_rating_test',0)==1))
		return false;

	$more=(!is_guest())?' OR rating_member='.strval((integer)get_member()):'';
	$for_types='';
	foreach ($rating_for_types as $rating_for_type)
	{
		if ($for_types!='') $for_types.=' OR ';
		$for_types.=db_string_equal_to('rating_for_type',$rating_for_type);
	}
	$query='SELECT COUNT(*) FROM '.get_table_prefix().'rating WHERE ('.$for_types.') AND '.db_string_equal_to('rating_for_id',$content_id);
	$query.=' AND (';
	if (!$GLOBALS['IS_ACTUALLY_ADMIN'])
	{
		$query.='rating_ip=\''.get_ip_address().'\'';
	} else
	{
		$query.='1=0';
	}
	$query.=$more.')';
	$has_rated=$GLOBALS['SITE_DB']->query_value_if_there($query);

	return ($has_rated>=count($rating_for_types));
}

/**
 * Actually adds a rating to the specified resource.
 * It performs full checking of inputs, and will log a hackattack if the rating is not between 1 and 10.
 *
 * @param  boolean		Whether this resource allows rating (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The type (download, etc) that this rating is for
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 */
function actualise_rating($allow_rating,$content_type,$content_id,$content_url,$content_title)
{
	if ((get_option('is_on_rating')=='0') || (!$allow_rating)) return;

	global $RATINGS_STRUCTURE;
	$all_rating_criteria=array();
	if (array_key_exists($content_type,$RATINGS_STRUCTURE))
	{
		$all_rating_criteria=array_keys($RATINGS_STRUCTURE[$content_type][1]);
	} else
	{
		$all_rating_criteria[]='';
	}

	foreach ($all_rating_criteria as $type)
	{
		// Has there actually been any rating?
		$rating=post_param_integer('rating__'.$content_type.'__'.$type.'__'.$content_id,NULL);
		if (is_null($rating)) return;

		actualise_specific_rating($rating,get_page_name(),get_member(),$content_type,$type,$content_id,$content_url,$content_title);
	}

	actualise_give_rating_points();

	// Ok, so just thank 'em
	attach_message(do_lang_tempcode('THANKYOU_FOR_RATING'),'inform');
}

/**
 * Assign points to the current member for rating.
 */
function actualise_give_rating_points()
{
	if ((!is_guest()) && (addon_installed('points')))
	{
		require_code('points');
		$_count=point_info(get_member());
		$count=array_key_exists('points_gained_rating',$_count)?$_count['points_gained_rating']:0;
		$GLOBALS['FORUM_DRIVER']->set_custom_field(get_member(),'points_gained_rating',$count+1);
	}
}

/**
 * Implement a rating at the quantum level.
 *
 * @param  ?integer		Rating given (NULL: unrate)
 * @range 1 10
 * @param  ID_TEXT		The page name the rating is on
 * @param  MEMBER			The member doing the rating
 * @param  ID_TEXT		The type (download, etc) that this rating is for
 * @param  ID_TEXT		The second level type (probably blank)
 * @param  ID_TEXT		The ID of the type that this rating is for
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know)
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 */
function actualise_specific_rating($rating,$page_name,$member_id,$content_type,$type,$content_id,$content_url,$content_title)
{
	if (!is_null($rating))
	{
		if (($rating>10) || ($rating<1)) log_hack_attack_and_exit('VOTE_CHEAT');
	}

	$rating_for_type=$content_type.(($type=='')?'':('_'.$type));

	if (!has_privilege($member_id,'rate',$page_name)) return;
	$already_rated=already_rated(array($rating_for_type),$content_id);
	if (!is_null($rating))
	{
		if ($already_rated)
		{
			// Delete, in preparation for re-rating
			$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>$rating_for_type,'rating_for_id'=>$content_id,'rating_member'=>$member_id,'rating_ip'=>get_ip_address()));
		}
	}

	list($_content_title,$submitter,,$safe_content_url,$cma_info)=get_details_behind_feedback_code($content_type,$content_id);
	if (is_null($content_title)) $content_title=$_content_title;
	if (($member_id===$submitter) && (!is_guest($member_id))) return;

	if (!is_null($rating))
	{
		$GLOBALS['SITE_DB']->query_insert('rating',array('rating_for_type'=>$rating_for_type,'rating_for_id'=>$content_id,'rating_member'=>$member_id,'rating_ip'=>get_ip_address(),'rating_time'=>time(),'rating'=>$rating));
	} else
	{
		$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>$rating_for_type,'rating_for_id'=>$content_id,'rating_member'=>$member_id,'rating_ip'=>get_ip_address()));
	}

	// Top rating / liked
	if (($rating===10) && ($type==''))
	{
		$content_type_title=$content_type;
		if ((!is_null($cma_info)) && (isset($cma_info['content_type_label'])))
		{
			$content_type_title=do_lang($cma_info['content_type_label']);
		}

		// Special case. Would prefer not to hard-code, but important for usability
		if (($content_type=='post') && ($content_title=='') && (get_forum_type()=='ocf'))
		{
			$content_title=do_lang('POST_IN',$GLOBALS['FORUM_DB']->query_select_value('f_topics','t_cache_first_title',array('id'=>$GLOBALS['FORUM_DB']->query_select_value('f_posts','p_topic_id',array('id'=>intval($content_id))))));
		}

		if ((!is_null($submitter)) && (!is_guest($submitter)))
		{
			// Give points
			if ($member_id!=$submitter)
			{
				if ((addon_installed('points')) && (!$already_rated))
				{
					require_code('points2');
					require_lang('points');
					system_gift_transfer(do_lang('CONTENT_LIKED'),intval(get_option('points_if_liked')),$submitter);
				}
			}

			// Notification
			require_code('notifications');
			$subject=do_lang('CONTENT_LIKED_NOTIFICATION_MAIL_SUBJECT',get_site_name(),($content_title=='')?ocp_mb_strtolower($content_type_title):$content_title);
			$rendered='';
			$content_type=convert_ocportal_type_codes('feedback_type_code',$content_type,'content_type');
			if ($content_type!='')
			{
				require_code('hooks/systems/content_meta_aware/'.$content_type);
				$cma_ob=object_factory('Hook_content_meta_aware_'.$content_type);
				$cma_content_row=content_get_row($content_id,$cma_ob->info());
				if (!is_null($cma_content_row))
				{
					$rendered=preg_replace('#&amp;keep_\w+=[^&]*#','',static_evaluate_tempcode($cma_ob->run($cma_content_row,'_SEARCH',true,true)));
				}
			}
			$mail=do_lang('CONTENT_LIKED_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape(($content_title=='')?ocp_mb_strtolower($content_type_title):$content_title),array(comcode_escape(is_object($safe_content_url)?$safe_content_url->evaluate():$safe_content_url),$rendered,comcode_escape($GLOBALS['FORUM_DRIVER']->get_username(get_member()))));
			dispatch_notification('like',NULL,$subject,$mail,array($submitter));
		}

		// Put on activity wall / whatever
		if (may_view_content_behind_feedback_code($GLOBALS['FORUM_DRIVER']->get_guest_id(),$content_type,$content_id))
		{
			if (is_null($submitter)) $submitter=$GLOBALS['FORUM_DRIVER']->get_guest_id();

			$activity_type=((is_null($submitter)) || (is_guest($submitter)))?'_ACTIVITY_LIKES':'ACTIVITY_LIKES';
			if ($content_title=='')
			{
				syndicate_described_activity($activity_type.'_UNTITLED',ocp_mb_strtolower($content_type_title),$content_type_title,'',url_to_pagelink(is_object($safe_content_url)?$safe_content_url->evaluate():$safe_content_url),'','',convert_ocportal_type_codes('feedback_type_code',$content_type,'addon_name'),1,NULL,false,$submitter);
			} else
			{
				syndicate_described_activity($activity_type,$content_title,ocp_mb_strtolower($content_type_title),$content_type_title,url_to_pagelink(is_object($safe_content_url)?$safe_content_url->evaluate():$safe_content_url),'','',convert_ocportal_type_codes('feedback_type_code',$content_type,'addon_name'),1,NULL,false,$submitter);
			}
		}
	}

	// Enter them for a prize draw to win a free jet
	// NOT IMPLEMENTED- Anyone want to donate the jet?
}

/**
 * Get the tempcode containing all the comments posted, and the comments posting form for the specified resource.
 *
 * @param  ID_TEXT		The type (download, etc) that this commenting is for
 * @param  boolean		Whether this resource allows comments (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The ID of the type that this commenting is for
 * @param  boolean		Whether the comment box will be invisible if there are not yet any comments (and you're not staff)
 * @param  ?string		The name of the forum to use (NULL: default comment forum)
 * @param  ?string		The default post to use (NULL: standard courtesy warning)
 * @param  ?mixed			The raw comment array (NULL: lookup). This is useful if we want to pass it through a filter
 * @param  boolean		Whether to skip permission checks
 * @param  boolean		Whether to reverse the posts
 * @param  ?MEMBER		User to highlight the posts of (NULL: none)
 * @param  boolean		Whether to allow ratings along with the comment (like reviews)
 * @param  ?integer		Maximum to load (NULL: default)
 * @return tempcode		The tempcode for the comment topic
 */
function get_comments($content_type,$allow_comments,$content_id,$invisible_if_no_comments=false,$forum=NULL,$post_warning=NULL,$_comments=NULL,$explicit_allow=false,$reverse=true,$highlight_by_user=NULL,$allow_reviews=false,$num_to_show_limit=NULL)
{
	if (((get_option('is_on_comments')=='1') && (get_forum_type()!='none') && ((get_forum_type()!='ocf') || (addon_installed('ocf_forum'))) && (($allow_reviews) || ($allow_comments))) || ($explicit_allow))
	{
		if (is_null($forum)) $forum=get_option('comments_forum_name');

		require_code('topics');
		$renderer=new OCP_Topic();

		return $renderer->render_as_comment_topic($content_type,$content_id,$allow_comments,$invisible_if_no_comments,$forum,$post_warning,$_comments,$explicit_allow,$reverse,$highlight_by_user,$allow_reviews,$num_to_show_limit);
	}

	return new ocp_tempcode(); // No franchise to render comments
}

/**
 * Topic titles/descriptions (depending on forum driver) are encoded for both human readable data, and a special ID code: this will extract just the ID code, or return the whole thing if no specific pattern match
 *
 * @param  string			Potentially complex topic title
 * @return string			Simplified topic title
*/
function extract_topic_identifier($full_text)
{
	$matches=array();
	if (preg_match('#: \#(.*)$#',$full_text,$matches)!=0)
	{
		return $matches[1];
	}
	return $full_text;
}

/**
 * Add comments to the specified resource.
 *
 * @param  boolean		Whether this resource allows comments (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The type (download, etc) that this commenting is for
 * @param  ID_TEXT		The ID of the type that this commenting is for
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 * @param  ?string		The name of the forum to use (NULL: default comment forum)
 * @param  boolean		Whether to not require a captcha
 * @param  ?BINARY		Whether the post is validated (NULL: unknown, find whether it needs to be marked unvalidated initially). This only works with the OCF driver (hence is the last parameter).
 * @param  boolean		Whether to force allowance
 * @param  boolean		Whether to skip a success message
 * @param  boolean		Whether posts made should not be shared
 * @return boolean		Whether a hidden post has been made
 */
function actualise_post_comment($allow_comments,$content_type,$content_id,$content_url,$content_title,$forum=NULL,$avoid_captcha=false,$validated=NULL,$explicit_allow=false,$no_success_message=false,$private=false)
{
	if (!$explicit_allow)
	{
		if ((get_option('is_on_comments')=='0') || (!$allow_comments)) return false;

		if (!has_privilege(get_member(),'comment',get_page_name())) return false;
	}

	if (running_script('preview')) return false;

	$forum_tie=(get_option('is_on_strong_forum_tie')=='1');

	if (addon_installed('captcha'))
	{
		if (((array_key_exists('post',$_POST)) && ($_POST['post']!='')) && (!$avoid_captcha))
		{
			require_code('captcha');
			enforce_captcha();
		}
	}

	$post_title=post_param('title',NULL);
	if ((is_null($post_title)) && (!$forum_tie)) return false;

	$post=post_param('post',NULL);
	if (($post=='') && ($post_title!==''))
	{
		$post=$post_title;
		$post_title='';
	}
	if ($post==='') warn_exit(do_lang_tempcode('NO_PARAMETER_SENT','post'));
	if (is_null($post)) $post='';
	$email=trim(post_param('email',''));
	if ($email!='')
	{
		$body='> '.str_replace(chr(10),chr(10).'> ',$post);
		if (substr($body,-2)=='> ') $body=substr($body,0,strlen($body)-2);
		if (get_page_name()!='tickets') $post.='[staff_note]';
		$post.="\n\n".'[email subject="Re: '.comcode_escape($post_title).' ['.get_site_name().']" body="'.comcode_escape($body).'"]'.$email.'[/email]'."\n\n";
		if (get_page_name()!='tickets') $post.='[/staff_note]';
	}

	$content_title=strip_comcode($content_title);

	if (is_null($forum)) $forum=get_option('comments_forum_name');

	$content_url_flat=(is_object($content_url)?$content_url->evaluate():$content_url);

	$_parent_id=post_param('parent_id','');
	$parent_id=($_parent_id=='')?NULL:intval($_parent_id);

	$poster_name_if_guest=post_param('poster_name_if_guest','');
	list($topic_id,$is_hidden)=$GLOBALS['FORUM_DRIVER']->make_post_forum_topic(
		// Define scope
		$forum,
		$content_type.'_'.$content_id,

		// What is being posted
		get_member(),
		$post_title,
		$post,

		// Define more about scope
		$content_title,
		do_lang('COMMENT'),
		$content_url_flat,

		// Define more about what is being posted,
		NULL,
		NULL,
		$validated,
		$explicit_allow?1:NULL,
		$explicit_allow,
		$poster_name_if_guest,
		$parent_id,
		false,

		// Do not send notifications to someone also getting one defined by the following
		((!$private) && ($post!=''))?'comment_posted':NULL,
		((!$private) && ($post!=''))?($content_type.'_'.$content_id):NULL
	);

	if (!is_null($topic_id))
	{
		if (!is_integer($forum))
		{
			$forum_id=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum);
		}
		else $forum_id=(integer)$forum;

		if ((get_forum_type()=='ocf') && (!is_null($GLOBALS['LAST_POST_ID'])))
		{
			$extra_review_ratings=array();
			global $REVIEWS_STRUCTURE;
			if (array_key_exists($content_type,$REVIEWS_STRUCTURE))
			{
				$reviews_rating_criteria=$REVIEWS_STRUCTURE[$content_type];
			} else
			{
				$reviews_rating_criteria[]='';
			}

			foreach ($reviews_rating_criteria as $rating_type)
			{
				// Has there actually been any rating?
				$rating=post_param_integer('review_rating__'.fix_id($rating_type),NULL);

				if (!is_null($rating))
				{
					if (($rating>10) || ($rating<1)) log_hack_attack_and_exit('VOTE_CHEAT');

					$GLOBALS['SITE_DB']->query_insert('review_supplement',array(
						'r_topic_id'=>$GLOBALS['LAST_TOPIC_ID'],
						'r_post_id'=>$GLOBALS['LAST_POST_ID'],
						'r_rating_type'=>$rating_type,
						'r_rating_for_type'=>$content_type,
						'r_rating_for_id'=>$content_id,
						'r_rating'=>$rating,
					));
				}
			}
		}
	}

	if ((!$private) && ($post!=''))
	{
		list(,$submitter,,$safe_content_url,$cma_info)=get_details_behind_feedback_code($content_type,$content_id);

		$content_type_title=$content_type;
		if ((!is_null($cma_info)) && (isset($cma_info['content_type_label'])))
		{
			$content_type_title=do_lang($cma_info['content_type_label']);
		}

		// Notification
		require_code('notifications');
		$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		$subject=do_lang('NEW_COMMENT_SUBJECT',get_site_name(),($content_title=='')?ocp_mb_strtolower($content_type_title):$content_title,array($post_title,$username),get_site_default_lang());
		$username=$GLOBALS['FORUM_DRIVER']->get_username(get_member());
		$message_raw=do_lang('NEW_COMMENT_BODY',comcode_escape(get_site_name()),comcode_escape(($content_title=='')?ocp_mb_strtolower($content_type_title):$content_title),array($post_title,post_param('post'),comcode_escape($content_url_flat),comcode_escape($username)),get_site_default_lang());
		dispatch_notification('comment_posted',$content_type.'_'.$content_id,$subject,$message_raw);

		// Is the user gonna automatically enable notifications for this?
		if (get_forum_type()=='ocf')
		{
			$auto_monitor_contrib_content=$GLOBALS['OCF_DRIVER']->get_member_row_field(get_member(),'m_auto_monitor_contrib_content');
			if ($auto_monitor_contrib_content==1)
				enable_notifications('comment_posted',$content_type.'_'.$content_id);
		}

		// Activity
		if (may_view_content_behind_feedback_code($GLOBALS['FORUM_DRIVER']->get_guest_id(),$content_type,$content_id))
		{
			if (is_null($submitter)) $submitter=$GLOBALS['FORUM_DRIVER']->get_guest_id();
			$activity_type=((is_null($submitter)) || (is_guest($submitter)))?'_ADDED_COMMENT_ON':'ADDED_COMMENT_ON';
			if ($content_title=='')
			{
				syndicate_described_activity($activity_type.'_UNTITLED',ocp_mb_strtolower($content_type_title),$content_type_title,'',url_to_pagelink(is_object($safe_content_url)?$safe_content_url->evaluate():$safe_content_url),'','',convert_ocportal_type_codes('feedback_type_code',$content_type,'addon_name'),1,NULL,false,$submitter);
			} else
			{
				syndicate_described_activity($activity_type,$content_title,ocp_mb_strtolower($content_type_title),$content_type_title,url_to_pagelink(is_object($safe_content_url)?$safe_content_url->evaluate():$safe_content_url),'','',convert_ocportal_type_codes('feedback_type_code',$content_type,'addon_name'),1,NULL,false,$submitter);
			}
		}
	}

	if (($post!='') && ($forum_tie) && (!$no_success_message))
	{
		require_code('site2');
		assign_refresh($GLOBALS['FORUM_DRIVER']->topic_url($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum,$content_type.'_'.$content_id),$forum),0.0);
	}

	if (($post!='') && (!$no_success_message)) attach_message(do_lang_tempcode('SUCCESS'));

	return $is_hidden;
}

/**
 * Update the spacer post of a comment topic, after an edit.
 *
 * @param  boolean		Whether this resource allows comments (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The type (download, etc) that this commenting is for
 * @param  ID_TEXT		The ID of the type that this commenting is for
 * @param  mixed			The URL to where the commenting will pass back to (to put into the comment topic header) (URLPATH or Tempcode)
 * @param  ?string		The title to where the commenting will pass back to (to put into the comment topic header) (NULL: don't know, but not first post so not important)
 * @param  ?string		The name of the forum to use (NULL: default comment forum)
 * @param  ?AUTO_LINK	ID of spacer post (NULL: unknown)
 */
function update_spacer_post($allow_comments,$content_type,$content_id,$content_url,$content_title,$forum=NULL,$post_id=NULL)
{
	if ((get_option('is_on_comments')=='0') || (!$allow_comments)) return;
	if (get_forum_type()!='ocf') return;

	$home_link=is_null($content_title)?new ocp_tempcode():hyperlink($content_url,escape_html($content_title));

	if (is_null($forum)) $forum=get_option('comments_forum_name');
	if (!is_integer($forum))
	{
		$forum_id=$GLOBALS['FORUM_DRIVER']->forum_id_from_name($forum);
		if (is_null($forum_id)) return;
	}
	else $forum_id=(integer)$forum;

	$content_title=strip_comcode($content_title);

	if (is_null($post_id))
	{
		$topic_id=$GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum_id,$content_type.'_'.$content_id);
		if (is_null($topic_id)) return;
		$post_id=$GLOBALS['FORUM_DB']->query_select_value_if_there('f_posts','MIN(id)',array('p_topic_id'=>$topic_id));
		if (is_null($post_id)) return;
	} else
	{
		$topic_id=$GLOBALS['FORUM_DB']->query_select_value('f_posts','p_topic_id',array('id'=>$post_id));
	}

	$spacer_title=is_null($content_title)?($content_type.'_'.$content_id):($content_title.' (#'.$content_type.'_'.$content_id.')');
	$spacer_post='[semihtml]'.do_lang('SPACER_POST',$home_link->evaluate(),'','',get_site_default_lang()).'[/semihtml]';

	if (get_forum_type()=='ocf')
	{
		require_code('ocf_posts_action3');
		ocf_edit_post($post_id,1,is_null($content_title)?$spacer_title:$content_title,$spacer_post,0,0,NULL,false,false,'',false);
		require_code('ocf_topics_action2');
		ocf_edit_topic($topic_id,do_lang('COMMENT').': #'.$content_type.'_'.$content_id,NULL,NULL,NULL,NULL,NULL,NULL,'',NULL,$home_link->evaluate(),false);
	}
}

/**
 * Get the tempcode containing all the trackbacks received, and the trackback posting form for the specified resource.
 *
 * @param  ID_TEXT		The type (download, etc) that this trackback is for
 * @param  ID_TEXT		The ID of the type that this trackback is for
 * @param  boolean		Whether this resource allows trackback (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The type of details being fetched (currently: blank or XML)
 * @return tempcode		Tempcode for complete trackback box
 */
function get_trackbacks($content_type,$content_id,$allow_trackback,$type='')
{
	if (($type!='') && ($type!='xml')) $type='';

	if ((get_option('is_on_trackbacks')=='1') && ($allow_trackback))
	{
		require_lang('trackbacks');

		$trackbacks=$GLOBALS['SITE_DB']->query_select('trackbacks',array('*'),array('trackback_for_type'=>$content_type,'trackback_for_id'=>$content_id),'ORDER BY trackback_time DESC',300);

		$content=new ocp_tempcode();
		$items=new ocp_tempcode();

		global $CURRENT_SCREEN_TITLE;

		if (is_null($CURRENT_SCREEN_TITLE)) $CURRENT_SCREEN_TITLE='';

		foreach ($trackbacks as $value)
		{
			if ($type=='')
			{
				$trackback_rendered=do_template('TRACKBACK',array(
					'_GUID'=>'128e21cdbc38a3037d083f619bb311ae',
					'ID'=>strval($value['id']),
					'TIME_RAW'=>strval($value['trackback_time']),
					'TIME'=>get_timezoned_date($value['trackback_time']),
					'URL'=>$value['trackback_url'],
					'TITLE'=>$value['trackback_title'],
					'EXCERPT'=>$value['trackback_excerpt'],
					'NAME'=>$value['trackback_name'],
				));
				$content->attach($trackback_rendered);
			} else
			{
				$trackback_rendered_xml=do_template('TRACKBACK_XML',array(
					'_GUID'=>'a3fa8ab9f0e58bf2ad88b0980c186245',
					'TITLE'=>$value['trackback_title'],
					'LINK'=>$value['trackback_url'],
					'EXCERPT'=>$value['trackback_excerpt'],
				));
				$items->attach($trackback_rendered_xml);
			}
		}

		if ((count($trackbacks)<1) && ($type=='xml'))
		{
			$trackback_xml_error=do_template('TRACKBACK_XML_ERROR',array(
				'_GUID'=>'945e2fcb510816caf323ba3704209430',
				'TRACKBACK_ERROR'=>do_lang_tempcode('NO_TRACKBACKS'),
			));
			$content->attach($trackback_xml_error);
		}

		if ($type=='')
		{
			$output=do_template('TRACKBACK_WRAPPER',array(
				'_GUID'=>'1bc2c42a54fdf4b0a10e8e1ea45f6e4f',
				'TRACKBACKS'=>$content,
				'TRACKBACK_PAGE'=>$content_type,
				'TRACKBACK_ID'=>$content_id,
				'TRACKBACK_TITLE'=>$CURRENT_SCREEN_TITLE,
			));
		} else
		{
			$trackback_xml=do_template('TRACKBACK_XML_LISTING',array(
				'_GUID'=>'3bff402f15395f4648a2b5af33de8285',
				'ITEMS'=>$items,
				'LINK_PAGE'=>$content_type,
				'LINK_ID'=>$content_id,
			));
			$content->attach($trackback_xml);
			$output=$content;
		}
	} else
	{
		$output=new ocp_tempcode();
	}

	return $output;
}

/**
 * Add trackbacks to the specified resource.
 *
 * @param  boolean		Whether this resource allows trackback (if not, this function does nothing - but it's nice to move out this common logic into the shared function)
 * @param  ID_TEXT		The type (download, etc) that this trackback is for
 * @param  ID_TEXT		The ID of the type that this trackback is for
 * @return boolean		Whether trackbacks are on
 */
function actualise_post_trackback($allow_trackbacks,$content_type,$content_id)
{
	if ((get_option('is_on_trackbacks')=='0') || (!$allow_trackbacks)) return false;

	inject_action_spamcheck();

	$url=either_param('url',NULL);
	if (is_null($url)) return false;
	$title=either_param('title',$url);
	$excerpt=either_param('excerpt','');
	$name=either_param('blog_name',$url);

	$GLOBALS['SITE_DB']->query_insert('trackbacks',array('trackback_for_type'=>$content_type,'trackback_for_id'=>$content_id,'trackback_ip'=>get_ip_address(),'trackback_time'=>time(),'trackback_url'=>$url,'trackback_title'=>$title,'trackback_excerpt'=>$excerpt,'trackback_name'=>$name));

	return true;
}
