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
 * @package		chat
 */

class Hook_symbol_CHAT_IM
{

	/**
	 * Standard modular run function for symbol hooks. Searches for tasks to perform.
    *
    * @param  array		Symbol parameters
    * @return string		Result
	 */
	function run($param)
	{
		$value='';

		if ((get_option('sitewide_im',true)==='1') && (!is_guest()) && ((!array_key_exists(get_session_id(),$GLOBALS['SESSION_CACHE'])) || ($GLOBALS['SESSION_CACHE'][get_session_id()]['session_invisible']==0)))
		{
			require_code('chat');
			require_lang('chat');

			$messages_php=find_script('messages');

			$im_area_template=do_template('CHAT_LOBBY_IM_AREA',array('_GUID'=>'38de4f030d5980790d6d1db1a7e2ff39','MESSAGES_PHP'=>$messages_php,'ROOM_ID'=>'__room_id__'));
			$im_area_template=do_template('CHAT_SITEWIDE_IM_POPUP',array('_GUID'=>'e520e557f86d0dd4e32d25a208d8f154','CONTENT'=>$im_area_template));
			$im_area_template=do_template('STANDALONE_HTML_WRAP',array('_GUID'=>'5032bfa802af3fe14e610d09078ef849','CSS'=>'sitewide_im_popup_body','TITLE'=>'__room_name__','TARGET'=>'_site_opener','CONTENT'=>$im_area_template,'POPUP'=>true));

			$make_friend_url=build_url(array('page'=>'_SELF','type'=>'friend_add','member_id'=>'__id__'),'_SELF');

			$block_member_url=build_url(array('page'=>'_SELF','type'=>'blocking_add','member_id'=>'__id__'),'_SELF');

			$profile_url=$GLOBALS['FORUM_DRIVER']->member_profile_url(-100,false,true);
			if (is_object($profile_url)) $profile_url=$profile_url->evaluate();
			$profile_url=str_replace('-100','__id__',$profile_url);

			$im_participant_template=do_template('CHAT_LOBBY_IM_PARTICIPANT',array('_GUID'=>'0c5e080d0afb29814a6e3059f0204ad1','PROFILE_URL'=>$profile_url,'ID'=>'__id__','ROOM_ID'=>'__room_id__','USERNAME'=>'__username__','ONLINE'=>'__online__','AVATAR_URL'=>'__avatar_url__','MAKE_FRIEND_URL'=>$make_friend_url,'BLOCK_MEMBER_URL'=>$block_member_url));

			$_value=do_template('CHAT_SITEWIDE_IM',array('_GUID'=>'5ab0404b3dac4578e8b4be699bd43c95','IM_AREA_TEMPLATE'=>$im_area_template,'IM_PARTICIPANT_TEMPLATE'=>$im_participant_template,'CHAT_SOUND'=>get_chat_sound_tpl()));
			$value=$_value->evaluate();
		}

		return $value;
	}

}
