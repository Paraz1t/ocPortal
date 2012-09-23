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
 * @package		users_online_block
 */

class Block_side_users_online
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
		$info['version']=3;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		$info['parameters']=array();
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		delete_config_option('usersonline_show_newest_member');
		delete_config_option('usersonline_show_birthdays');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		if (is_null($upgrade_from))
		{
			add_config_option('SHOW_NEWEST_MEMBER','usersonline_show_newest_member','tick','return ((has_no_forum()) || (get_forum_type()!=\'ocf\'))?NULL:\'0\';','BLOCKS','USERS_ONLINE_BLOCK');
			add_config_option('BIRTHDAYS','usersonline_show_birthdays','tick','return ((has_no_forum()) || (get_forum_type()!=\'ocf\'))?NULL:\'0\';','BLOCKS','USERS_ONLINE_BLOCK');
		}
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(get_member())';
		$info['ttl']=3;
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
		$count=0;
		$members=get_online_members(false,NULL,$count);
		if (is_null($members)) return new ocp_tempcode(); // Too many to show

		if (get_forum_type()=='ocf')
		{
			require_code('ocf_general');
			require_code('ocf_members');
			require_css('ocf');
		}

		$out=new ocp_tempcode();
		$guests=0;
		$_members=0;
		$done_members=array();
		$done_ips=array();
		foreach ($members as $_member)
		{
			$member=$_member['member_id'];
			$name=$_member['cache_username'];
			$ip=$_member['ip'];

			if ((is_guest($member)) || (is_null($name)))
			{
				if (!array_key_exists($ip,$done_ips))
				{
					$done_ips[$ip]=1;
					$guests++;
				}
			} else
			{
				if (!array_key_exists($member,$done_members))
				{
					$colour=(get_forum_type()=='ocf')?get_group_colour(ocf_get_member_primary_group($member)):NULL;
					$done_members[$member]=1;
					$url=$GLOBALS['FORUM_DRIVER']->member_profile_url($member,true,true);
					$out->attach(do_template('BLOCK_SIDE_USERS_ONLINE_USER',array(
						'_GUID'=>'a0b55810fe2f306c2886ec0c4cd8e8fd',
						'URL'=>$url,
						'NAME'=>$name,
						'COLOUR'=>$colour,
					)));
					$_members++;
				}
			}
		}

		$newest=new ocp_tempcode();
		$birthdays=new ocp_tempcode();
		if (get_forum_type()=='ocf')
		{
			require_lang('ocf');

			// Show newest member
			if (get_option('usersonline_show_newest_member',true)=='1')
			{
				$newest_member=$GLOBALS['FORUM_DB']->query_select('f_members',array('m_username','id'),array('m_validated'=>1),'ORDER BY id DESC',1);
				$username_link=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($newest_member[0]['id'],false,$newest_member[0]['m_username']);
				$newest->attach(paragraph(do_lang_tempcode('NEWEST_MEMBER_WELCOME',$username_link),'gdgdfhrug'));
			}

			// Birthdays
			if (get_option('usersonline_show_birthdays',true)=='1')
			{
				require_code('ocf_members');
				$_birthdays=ocf_find_birthdays();

				foreach ($_birthdays as $_birthday)
				{
					$colour=get_group_colour(ocf_get_member_primary_group($_birthday['id']));
					$birthday=do_template('OCF_USER_MEMBER',array(
						'_GUID'=>'b2d355ff45f4b4170b937ef0753e6a78',
						'FIRST'=>$birthdays->is_empty(),
						'COLOUR'=>$colour,
						'AGE'=>array_key_exists('age',$_birthday)?integer_format($_birthday['age']):NULL,
						'PROFILE_URL'=>$GLOBALS['FORUM_DRIVER']->member_profile_url($_birthday['id'],false,true),
						'USERNAME'=>$_birthday['username'],
					));
					$birthdays->attach($birthday);
				}
				if (!$birthdays->is_empty()) $birthdays=do_template('OCF_BIRTHDAYS',array('_GUID'=>'080ed2e74efd6410bd6b83ec01962c04','BIRTHDAYS'=>$birthdays));
			}
		}

		return do_template('BLOCK_SIDE_USERS_ONLINE',array(
			'_GUID'=>'fdfa68dff479b4ea7d517585297ea6af',
			'CONTENT'=>$out,
			'GUESTS'=>integer_format($guests),
			'MEMBERS'=>integer_format($_members),
			'_GUESTS'=>strval($guests),
			'_MEMBERS'=>strval($_members),
			'BIRTHDAYS'=>$birthdays,
			'NEWEST'=>$newest,
		));
	}

}


