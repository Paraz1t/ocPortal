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
 * @package		core_ocf
 */

class Hook_task_download_member_csv
{
	/**
	 * Run the task hook.
	 *
	 * @return ?array			A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
	 */
	function run()
	{
		$filename='members-'.date('Y-m-d').'.csv';

		$headers=array();
		$headers['Content-type']='text/csv';
		$headers['Content-Disposition']='attachment; filename="'.str_replace("\r",'',str_replace("\n",'',addslashes($filename))).'"';

		$ini_set=array();
		$ini_set['ocproducts.xss_detect']='0';

		$outfile_path=ocp_tempnam('csv');
		$outfile=fopen($outfile_path,'w+b');

		$fields=array('id','m_username','m_email_address','m_last_visit_time','m_cache_num_posts','m_pass_hash_salted','m_pass_salt','m_password_compat_scheme','m_signature','m_validated','m_join_time','m_primary_group','m_is_perm_banned','m_dob_day','m_dob_month','m_dob_year','m_reveal_age','m_language','m_allow_emails','m_allow_emails_from_staff');
		if (addon_installed('ocf_member_avatars')) $fields[]='m_avatar_url';
		if (addon_installed('ocf_member_photos')) $fields[]='m_photo_url';
		$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,false,true);
		$member_groups_count=$GLOBALS['FORUM_DB']->query_select_value('f_group_members','COUNT(*)',array('gm_validated'=>1));
		if ($member_groups_count<500)
		{
			$member_groups=$GLOBALS['FORUM_DB']->query_select('f_group_members',array('gm_member_id','gm_group_id'),array('gm_validated'=>1));
		} else $member_groups=array();
		$cpfs=$GLOBALS['FORUM_DB']->query_select('f_custom_fields',array('id','cf_type','cf_name'),NULL,'ORDER BY cf_order');
		$member_count=$GLOBALS['FORUM_DB']->query_select_value('f_members','COUNT(*)');
		if ($member_count<700)
		{
			$member_cpfs=list_to_map('mf_member_id',$GLOBALS['FORUM_DB']->query_select('f_member_custom_fields',array('*')));
		} else
		{
			$member_cpfs=array();
		}

		require_code('ocf_members_action2');
		$headings=member_get_csv_headings();
		foreach ($cpfs as $i=>$c) // CPFs take precedence over normal fields of the same name
		{
			$cpfs[$i]['text_original']=get_translated_text($c['cf_name'],$GLOBALS['FORUM_DB']);
			$headings[$cpfs[$i]['text_original']]=NULL;
		}

		foreach (array_keys($headings) as $i=>$h)
		{
			if ($i!=0) fwrite($outfile,',');
			fwrite($outfile,'"'.str_replace('"','""',$h).'"');
		}
		fwrite($outfile,"\n");

		$at=mixed();

		$start=0;
		do
		{
			$members=$GLOBALS['FORUM_DB']->query_select('f_members',$fields,NULL,'',200,$start);

			if ($member_count>=700)
			{
				$or_list='';
				foreach ($members as $m)
				{
					if ($or_list!='') $or_list.=' OR ';
					$or_list.='mf_member_id='.strval($m['id']);
				}
				$member_cpfs=list_to_map('mf_member_id',$GLOBALS['FORUM_DB']->query('SELECT * FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_member_custom_fields WHERE '.$or_list,NULL,NULL,false,true));
			}

			foreach ($members as $m)
			{
				if (is_guest($m['id'])) continue;

				if ($member_groups_count>=500)
				{
					$member_groups=$GLOBALS['FORUM_DB']->query_select('f_group_members',array('gm_member_id','gm_group_id'),array('gm_validated'=>1,'gm_member_id'=>$m['id']));
				}

				$out=$this->_get_csv_member_record($member_cpfs,$m,$groups,$headings,$cpfs,$member_groups);
				$i=0;
				foreach ($out as $wider)
				{
					if ($i!=0) fwrite($outfile,',');
					fwrite($outfile,'"'.str_replace('"','""',$wider).'"');
					$i++;
				}
				fwrite($outfile,"\n");
			}

			$start+=200;
		}
		while (count($members)==200);

		fclose($outfile);

		return array('text/csv',array($filename,$outfile_path),$headers,$ini_set);
	}

	/**
	 * Get a CSV-outputtable row for a member.
	 *
	 * @param  array			A map of member CPF maps
	 * @param  array			Member row
	 * @param  array			Map of usergroup details
	 * @param  array			List of headings to pull from the member row
	 * @param  array			List of CPFS to pull
	 * @param  array			List of member group membership records
	 * @return array			The row
	 */
	function _get_csv_member_record($member_cpfs,$m,$groups,$headings,$cpfs,$member_groups)
	{
		$at=mixed();
		$out=array();
		$i=0;
		foreach ($headings as $written_heading=>$f)
		{
			if (is_null($f)) continue;
			$parts=explode('/',$f);
			$wider='';
			foreach ($parts as $part)
			{
				switch (substr($part,0,1))
				{
					case '*': // lang string
						$at=get_translated_text($m[substr($part,1)],$GLOBALS['FORUM_DB']);
						break;

					case '!': // binary
						$at=($m[substr($part,1)]==1)?'Yes':'No'; // Hard-coded in English, because we need a multi-language standard
						break;

					case '&': // timestamp
						$at=date('Y-m-d',intval($m[substr($part,1)]));
						break;

					case '#': // url
						$at=$m[substr($part,1)];
						if ((url_is_local($at)) && ($at!='')) $at=get_complex_base_url($at).'/'.$at;
						break;

					case '@': // append other groups
						$at=isset($groups[$m[substr($part,1)]])?$groups[$m[substr($part,1)]]:'';

						foreach ($member_groups as $g)
						{
							if ($g['gm_member_id']==$m['id'])
							{
								if (array_key_exists($g['gm_group_id'],$groups))
									$at.='/'.$groups[$g['gm_group_id']];
							}
						}
						break;

					default: // string
						$at=$m[$part];
						break;
				}
				if ($wider!='') $wider.='/';
				$wider.=is_integer($at)?strval($at):(is_null($at)?'':$at);
			}
			$out[$written_heading]=$wider;

			$i++;
		}
		foreach ($cpfs as $c)
		{
			if (!array_key_exists($m['id'],$member_cpfs))
			{
				$at='';
			} else
			{
				$at=$member_cpfs[$m['id']]['field_'.strval($c['id'])];
				if (is_null($at))
				{
					$at='';
				} else
				{
					if (strpos($c['cf_type'],'_trans')!==false)
					{
						$at=get_translated_text($at);
					} elseif (!is_string($at))
					{
						$at=strval($at);
					}
				}
			}
			$out[$c['text_original']]=$at;
		}
		return $out;
	}
}
