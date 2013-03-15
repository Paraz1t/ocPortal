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
 * @package		newsletter
 */

class Block_main_newsletter_signup
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
		$info['parameters']=array('subject','path','to','param');
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('newsletter');
		require_lang('javascript');

		$newsletter_id=array_key_exists('param',$map)?intval($map['param']):db_get_first_id();

		$_newsletter_title=$GLOBALS['SITE_DB']->query_value_null_ok('newsletters','title',array('id'=>$newsletter_id));
		if (is_null($_newsletter_title)) return paragraph(do_lang_tempcode('MISSING_RESOURCE'));
		$newsletter_title=get_translated_text($_newsletter_title);

		$address=post_param('address'.strval($newsletter_id),'');
		if ($address!='')
		{
			require_code('newsletter');

			require_code('type_validation');
			if (!is_valid_email_address($address))
			{
				$msg=do_template('INLINE_WIP_MESSAGE',array('MESSAGE'=>do_lang_tempcode('INVALID_EMAIL_ADDRESS')));
				return do_template('BLOCK_MAIN_NEWSLETTER_SIGNUP',array('URL'=>get_self_url(),'MSG'=>$msg));
			}

			if (!array_key_exists('path',$map)) $map['path']='uploads/website_specific/signup.txt';

			require_code('character_sets');
			$password=basic_newsletter_join($address,4,NULL,!file_exists(get_custom_file_base().'/'.$map['path']),$newsletter_id,post_param('firstname'.strval($newsletter_id),''),post_param('lastname'.strval($newsletter_id),''));
			if ($password=='')
			{
				return do_template('INLINE_WIP_MESSAGE',array('MESSAGE'=>do_lang_tempcode('NEWSLETTER_THIS_ALSO')));
			}
			if ($password==do_lang('NA'))
			{
				$manage_url=build_url(array('page'=>'newsletter','email'=>$address),get_module_zone('newsletter'));
				return do_template('INLINE_WIP_MESSAGE',array('MESSAGE'=>do_lang_tempcode('ALREADY_EMAIL_ADDRESS',escape_html($manage_url->evaluate()))));
			}

			require_code('mail');
			if (file_exists(get_custom_file_base().'/'.$map['path']))
			{
				$url=(url_is_local($map['path'])?(get_custom_base_url().'/'):'').$map['path'];
				mail_wrap(array_key_exists('subject',$map)?$map['subject']:do_lang('WELCOME'),convert_to_internal_encoding(http_download_file($url)),array($address),array_key_exists('to',$map)?$map['to']:'','','',3,NULL,false,NULL,true);
			}

			return do_template('BLOCK_MAIN_NEWSLETTER_SIGNUP_DONE',array('_GUID'=>'9953c83685df4970de8f23fcd5dd15bb','NEWSLETTER_TITLE'=>$newsletter_title,'NID'=>strval($newsletter_id),'PASSWORD'=>$password));
		} else
		{
			return do_template('BLOCK_MAIN_NEWSLETTER_SIGNUP',array('_GUID'=>'c0e6f9cdab3d624bf3d27b745e3de38f','NEWSLETTER_TITLE'=>$newsletter_title,'NID'=>strval($newsletter_id),'URL'=>get_self_url()));
		}
	}

}


