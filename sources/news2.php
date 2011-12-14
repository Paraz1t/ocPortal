<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		news
 */

/**
 * Update news to facebook wall
 *
 * @param  AUTO_LINK		The ID of the news 
 * @param  SHORT_TEXT	News title
 * @param  SHORT_TEXT	Message
 * @param  BINARY			Whether the news has been validated
 * @param  AUTO_LINK		Main news category's id	
 * @param  boolean		Current process indication. If it is update, need to check it's old "validated" state
 * @return boolean		Returns the success status of function
 */
function facebook_wall_news_update($id,$title,$message,$validated,$main_news_category_id,$update=true)
{
	if (!addon_installed('facebook')) return false;

	if (!($validated==1) || !(has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news',strval($main_news_category_id))))
	{	
		return false;
	}
	
	$appapikey=get_option('facebook_api');
	$appsecret=get_option('facebook_secret_code');
	$uid=get_option('facebook_uid');
	if(($appapikey=='') || ($appsecret=='') || ($uid=='')) return false;
	if (version_compare(PHP_VERSION, '5.0.0', '<')) return false;

	if($update)
	{
		$validated_old=$GLOBALS['SITE_DB']->query_value('news','validated',array('id'=>$id));

		if($validated_old==1) return false; // skip facebook publish if the news was already validated.
	}
	
	require_code('facebook_publish');

	//$message=comcode_to_clean_text($message);
	
	$categorisation=do_lang('FACEBOOK_WALL_HEADING_NEWS',get_option('site_name'));

	$view_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);

	if (function_exists('publish_to_FB'))
		publish_to_FB($title,$categorisation,$view_url->evaluate());
}

/**
 * Update news to twitter.
 *
 * @param  AUTO_LINK		The ID of the news 
 * @param  SHORT_TEXT	Message
 * @param  BINARY			Whether the news has been validated
 * @param  AUTO_LINK		Main news category's id	
 * @param  boolean		Current process indication. If it is update, need to check it's old "validated" state
 * @return boolean		Returns the success status of function
 */
function twitter_news_update($id,$message,$validated,$main_news_category_id,$update=true)
{
	if (!addon_installed('twitter')) return false;

	if (!($validated==1) || !(has_category_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'news',strval($main_news_category_id))))
	{
		return false;
	}
	
	if($update)
	{
		$validated_old=$GLOBALS['SITE_DB']->query_value('news','validated',array('id'=>$id));

		if($validated_old==1) return false; // skip twitter add if the news was already validated.
	}

	// Username and password
	$username=get_option('twitter_login');
	$password=get_option('twitter_password');


	// Checking twitter requirements
	
	if(is_null($username)) return false;
	if($username=='') return false;

	$url = 'http://twitter.com/statuses/update.xml';

	if(strlen($message)>249)
	{
		$_more_url=build_url(array('page'=>'news','type'=>'view','id'=>$id),get_module_zone('news'),NULL,false,false,true);
		$more_url=$_more_url->evaluate();
		$url_length=strlen($more_url);
		$message=substr($message,0,245-$url_length);
		$message.="...".urlencode($more_url);
	}

	require_code('files');
	$ret=http_download_file($url,NULL,false,false,'ocPortal',array('status'=>$message),NULL,NULL,NULL,NULL,NULL,NULL,array($username,$password));
	return !is_null($ret);
}

/**
 * Import wordpress db
 */
function import_wordpress_db()
{
	disable_php_memory_limit();
	
	$data=get_wordpress_data();
	$is_validated=post_param_integer('wp_auto_validate',0);
	$to_own_account=post_param_integer('wp_add_to_own',0);	

	//Create members
	require_code('ocf_members_action');
	require_code('ocf_groups');

	$def_grp_id=get_first_default_group();
	$cat_id=array();	

	$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
	
	$NEWS_CATS=list_to_map('id',$NEWS_CATS);

	foreach($data as $values)
	{
		if(get_forum_type()=='ocf')
		{
			$member_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_username'=>$values['user_login']));

			if(is_null($member_id))
			{
				if (post_param_integer('wp_import_wordpress_users',0)==1)
				{
					$member_id=ocf_make_member($values['user_login'],$values['user_pass'],'',NULL,NULL,NULL,NULL,array(),NULL,$def_grp_id,1,time(),time(),'',NULL,'',0,0,1,'','','',1,0,'',1,1,'',NULL,'',false,'wordpress');
				} else
				{
					$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username('admin');	//Set admin as owner
					if (is_null($member_id)) $member_id=$GLOBALS['FORUM_DRIVER']->get_guest_id()+1;
				}
			}
		}
		else
			$member_id=$GLOBALS['FORUM_DRIVER']->get_guest_id(); //Guest user

		//If post should go to own account
		if($to_own_account==1)	$member_id=get_member();			
		
		if(array_key_exists('POSTS',$values))
		{
			//Create posts in blog
			foreach($values['POSTS'] as $post_id=>$post)
			{	
				if(array_key_exists('category',$post))
				{	
					$cat_id=array();
					foreach($post['category'] as $cat_code=>$category)
					{	
						$cat_code=NULL;
						if($category=='Uncategorized')	continue;	//Skip blank category creation
						foreach($NEWS_CATS as $id=>$existing_cat)
						{
							if (get_translated_text($existing_cat['nc_title'])==$category)
							{
								$cat_code=$id;
							}
						}
						if(is_null($cat_code))	//Cound not find existing category, create new
						{
							$cat_code=add_news_category($category,'newscats/community',$category);
							$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'));	
							$NEWS_CATS=list_to_map('id',$NEWS_CATS);
						}
						$cat_id=array_merge($cat_id,array($cat_code));
					}
				}

				$owner_category_id=$GLOBALS['SITE_DB']->query_value_null_ok('news_categories','id',array('nc_owner'=>$member_id));
	
				if($post['post_type']=='post')	//Posts
				{
					$id=add_news($post['post_title'],html_to_comcode($post['post_content']),NULL,$is_validated,1,($post['comment_status']=='closed')?0:1,1,'',html_to_comcode($post['post_content']),$owner_category_id,$cat_id,NULL,$member_id,0,time(),NULL,'');
				}
				elseif($post['post_type']=='page' )	// page/articles
				{
					//If dont have permission to write comcode page, skip the post
					if (!has_submit_permission('high',get_member(),get_ip_address(),NULL,NULL))	continue;
					
					require_code('comcode');
					//Save articles as new comcode pages
					$zone=filter_naughty(post_param('zone','site'));
					$lang=filter_naughty(post_param('lang','EN'));
					$file=preg_replace('/[^A-Za-z0-9]/','_',$post['post_title']);	//Filter non alphanumeric charactors
					$parent_page=post_param('parent_page','');
					$fullpath=zone_black_magic_filterer(get_custom_file_base().'/'.$zone.'/pages/comcode_custom/'.$lang.'/'.$file.'.txt');

					//Check existancy of new page
					$submiter=$GLOBALS['SITE_DB']->query_value_null_ok('comcode_pages','p_submitter',array('the_zone'=>$zone,'the_page'=>$file));
	
					if(!is_null($submiter)) continue; //Skip existing titled articles	- may need change
				
					require_code('submit');
					give_submit_points('COMCODE_PAGE_ADD');
		
					if (!addon_installed('unvalidated')) $is_validated=1;
					$GLOBALS['SITE_DB']->query_insert('comcode_pages',array(
						'the_zone'=>$zone,
						'the_page'=>$file,
						'p_parent_page'=>$parent_page,
						'p_validated'=>$is_validated,
						'p_edit_date'=>NULL,
						'p_add_date'=>strtotime($post['post_date']),
						'p_submitter'=>$member_id,
						'p_show_as_edit'=>0
					));

					if ((!file_exists($fullpath)))
					{
						$_content=html_to_comcode($post['post_content']);
						$myfile=@fopen($fullpath,'wt');
						if ($myfile===false) intelligent_write_error($fullpath);
						if (fwrite($myfile,$_content)<strlen($_content)) warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
						
						fclose($myfile);
						sync_file($fullpath);
					}

					require_code('seo2');
					seo_meta_set_for_explicit('comcode_page',$zone.':'.$file,post_param('meta_keywords',''),post_param('meta_description',''));
		
					require_code('permissions2');
					set_page_permissions_from_environment($zone,$file);
				}
	
				$self_url=get_self_url();
				$self_title=$post['post_title'];
				$home_link=is_null($self_title)?new ocp_tempcode():hyperlink($self_url,escape_html($self_title));
	
				//Add comments
				if(post_param_integer('wp_import_blog_comments',0)==1)
				{
					if(array_key_exists('COMMENTS',$post))
					{
						$submitter=NULL;
						foreach($post['COMMENTS'] as $comment)
						{
							$submitter=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members','id',array('m_username'=>$comment['comment_author']));
		
							if(is_null($submitter))	$submitter=1;	//If comment is done by a nonmember, assign comment to guest account
							
							$forum=(is_null(get_value('comment_forum__news')))?get_option('comments_forum_name'):get_value('comment_forum__news');
							$result=$GLOBALS['FORUM_DRIVER']->make_post_forum_topic($forum,$post['post_title'],$submitter,$comment['comment_content'],'',$home_link,NULL,NULL,1,1,false,array($post['post_title'],do_lang('COMMENT').': #news_'.strval($id),is_object($self_url)?$self_url->evaluate():$self_url));
						}
					}
				}
			}
		}
	}
}


/**
 * Get data from wordpress db
 *
 * @return array		Result array
 */
function get_wordpress_data()
{
	$host_name=post_param('wp_host');
	$db_name=post_param('wp_db');
	$db_user=post_param('wp_db_user');
	$db_passwrod=post_param('wp_db_password');
	$db_table_prefix=post_param('wp_table_prefix');

	//Create db driver
	$db=new database_driver($db_name,$host_name,$db_user,$db_passwrod,$db_table_prefix);

	$row=$db->query('SELECT * FROM '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_users');

	$data=array();
	foreach($row as $users)
	{
		$user_id=$users['ID'];
		$data[$user_id]=$users;
		//Fetch user posts
		$row1=$db->query('SELECT * FROM '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_posts WHERE post_author='.strval($user_id).' AND (post_type=\'post\' OR post_type=\'page\')');	
		foreach($row1 as $posts)
		{
			$post_id=$posts['ID'];
			$data[$user_id]['POSTS'][$post_id]=$posts;

			//get categories
			$row3=$db->query('SELECT t1.slug,t1.name FROM '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_terms t1,'.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_term_relationships t2 WHERE t1.term_id=t2.term_taxonomy_id AND t2.object_id='.strval($post_id));

			foreach($row3 as $categories)
			{
				$data[$user_id]['POSTS'][$post_id]['category'][$categories['slug']]=$categories['name'];
			}
			//Comments
			$row2=$db->query('SELECT * FROM '.db_escape_string($db_name).'.'.db_escape_string($db_table_prefix).'_comments WHERE comment_post_ID='.strval($post_id).' AND comment_approved=1');
			foreach($row2 as $comments)
			{
				$comment_id=$comments['comment_ID'];
				$data[$user_id]['POSTS'][$post_id]['COMMENTS'][$comment_id]=$comments;
			}
		}
	}
	
	return $data;
}


