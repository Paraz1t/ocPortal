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

class Hook_task_import_member_csv
{
	/**
	 * Run the task hook.
	 *
	 * @param  string			The default password to use
	 * @param  boolean		Whether to assign temporary passwords
	 * @param  PATH			The path of the file to import
	 * @return ?array			A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
	 */
	function run($default_password,$use_temporary_passwords,$path)
	{
		require_lang('ocf');
		require_lang('import');
		require_code('ocf_members_action');

		$num_added=0;
		$num_edited=0;
		$done=0;

		$outputted_messages=new ocp_tempcode();

		require_code('ocf_members_actions2');
		$headings=member_get_csv_headings();
		$all_cpfs=$GLOBALS['FORUM_DB']->query_select('f_custom_fields',array('id','cf_default','cf_type','cf_name'),NULL,'ORDER BY cf_order');
		foreach ($all_cpfs as $i=>$c) // CPFs take precedence over normal fields of the same name
		{
			$c['text_original']=get_translated_text($c['cf_name'],$GLOBALS['FORUM_DB']);
			$all_cpfs[$i]=$c;
			$headings[$c['text_original']]=NULL;
		}
		$_all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,false,true);
		$all_groups=array_flip($_all_groups);
		$all_members=collapse_2d_complexity('id','m_username',$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_username')));
		$all_members_flipped=array_flip($all_members);

		$_csv_data=array();

		$myfile=fopen($path,'rb');
		$del=',';
		$csv_header=fgetcsv($myfile,102400,$del);
		if ($csv_header===false)
		{
			@unlink($path);
			return array(NULL,do_lang_tempcode('NO_DATA_IMPORTED'));
		}
		if ((count($csv_header)==1) && (strpos($csv_header[0],';')!==false))
		{
			$del=';';
			rewind($myfile);
			$csv_header=fgetcsv($myfile,102400,$del);
		}
		while (($csv_line=fgetcsv($myfile,102400,$del))!==false)
		{
			$line=array();
			foreach ($csv_header as $i=>$h)
			{
				$extracted_value=trim(unixify_line_format(array_key_exists($i,$csv_line)?$csv_line[$i]:''));

				if (strpos($h,':')!==false) // Special syntax to allow merging of columns together into a single multi-line field
				{
					$parts=explode(':',$h,2);
					$h=trim($parts[0]);
					if ($extracted_value!='') $extracted_value=$parts[1].': '.$extracted_value;
				}

				if (array_key_exists($h,$line))
				{
					if ($extracted_value!='') $line[$h].=(($line[$h]!='')?"\n":'').$extracted_value;
				} else
				{
					$line[$h]=$extracted_value;
				}
			}

			if ((!array_key_exists('Username',$line)) || ($line['Username']==''))
			{
				// Can we auto-generate it
				$forename=NULL;
				if (array_key_exists('Forenames',$line)) $forename=$line['Forenames'];
				if (array_key_exists('Forename',$line)) $forename=$line['Forename'];
				if (array_key_exists('First name',$line)) $forename=$line['First name'];
				if (array_key_exists('First Name',$line)) $forename=$line['First Name'];
				$surname=NULL;
				if (array_key_exists('Surname',$line)) $surname=$line['Surname'];
				if (array_key_exists('Last name',$line)) $surname=$line['Last name'];
				if (array_key_exists('Last Name',$line)) $surname=$line['Last Name'];
				if ((!is_null($forename)) || (!is_null($surname)))
				{
					// Can we get a year too?
					$year='';
					foreach ($line as $tl_key=>$tl_val)
					{
						if (substr($tl_key,0,4)=='Year')
						{
							$year=$tl_val;
							break;
						}
					}
					if ((strlen($year)==4) && ((substr($year,0,2)=='19') || (substr($year,0,2)=='20'))) $year=substr($year,2);

					// Tidy up forename
					$_forename=preg_replace('#[^\w]#','',preg_replace('#[\s\.].*#','',$forename));

					// Tidy up surname (last bit strips like 'OBE')
					$_surname=preg_replace('#[^\w]#','',trim(preg_replace('#\s*[A-Z\d][A-Z\d]+#','',$surname)));

					// Put it together
					$line['Username']=ucfirst($_forename).ucfirst($_surname).$year;
				} else
				{
					continue; // This field is needed
				}
			}

			$username=$line['Username'];

			$linked_id=NULL;
			if (array_key_exists('ID',$line)) $linked_id=(($line['ID']!='')&&(array_key_exists(intval($line['ID']),$all_members)))?intval($line['ID']):NULL;
			if (is_null($linked_id)) $linked_id=array_key_exists($username,$all_members_flipped)?$all_members_flipped[$username]:NULL;
			$new_member=is_null($linked_id);

			$email_address_key='E-mail address';
			if (array_key_exists('Email address',$line)) $email_address_key='Email address';
			if (array_key_exists('E-mail Address',$line)) $email_address_key='E-mail Address';
			if (array_key_exists('Email Address',$line)) $email_address_key='Email Address';
			if (array_key_exists('E-mail',$line)) $email_address_key='E-mail';
			if (array_key_exists('Email',$line)) $email_address_key='Email';

			$dob_key='Date of birth';
			if (array_key_exists('Date Of Birth',$line)) $dob_key='Date Of Birth';
			if (array_key_exists('DOB',$line)) $dob_key='DOB';

			// If it's an edited member, add in their existing CSV details, so that if it's a partial merge it'll still work without deleting anything!
			if (!$new_member)
			{
				$member_groups=$GLOBALS['FORUM_DB']->query_select('f_group_members',array('gm_member_id','gm_group_id'),array('gm_validated'=>1,'gm_member_id'=>$linked_id));
				$member_cpfs=list_to_map('mf_member_id',$GLOBALS['FORUM_DB']->query_select('f_member_custom_fields',array('*'),array('mf_member_id'=>$linked_id),'',1));
				$this_record=$this->_get_csv_member_record($member_cpfs,$GLOBALS['FORUM_DRIVER']->get_member_row($linked_id),$_all_groups,$headings,$all_cpfs,$member_groups); // Remember "+" in PHP won't overwrite existing keys
				if (!array_key_exists($email_address_key,$line)) unset($this_record['E-mail address']);
				if (!array_key_exists($dob_key,$line)) unset($this_record['Date of birth']);
				$line+=$this_record;
			}

			// Set up member row
			if ((array_key_exists('Password',$line)) && ($line['Password']!=''))
			{
				$parts=explode('/',$line['Password']);
				$password=$parts[0];
				$salt=array_key_exists(1,$parts)?$parts[1]:NULL;
				$password_compatibility_scheme=array_key_exists(2,$parts)?$parts[2]:NULL;
			} else
			{
				$password=NULL;
				$salt=NULL;
				$password_compatibility_scheme=NULL;
			}
			$matches=array();
			if (array_key_exists($email_address_key,$line)) $email_address=$line[$email_address_key]; else $email_address=NULL;
			if (preg_match('#^([^\s]*)\s+\(.*\)$#',$email_address,$matches)!=0) $email_address=$matches[1];
			if (preg_match('#^.*\s+<(.*)>$#',$email_address,$matches)!=0) $email_address=$matches[1];
			if (array_key_exists($dob_key,$line))
			{
				$parts=explode('/',$line[$dob_key]);
				$dob_day=array_key_exists(2,$parts)?intval($parts[2]):NULL;
				$dob_month=array_key_exists(1,$parts)?intval($parts[1]):NULL;
				$dob_year=array_key_exists(0,$parts)?intval($parts[0]):NULL;
			} else
			{
				$dob_day=NULL;
				$dob_month=NULL;
				$dob_year=NULL;
			}
			$validated=array_key_exists('Validated',$line)?((strtoupper($line['Validated'])=='YES' || $line['Validated']=='1' || strtoupper($line['Validated'])=='Y' || strtoupper($line['Validated'])=='ON')?1:0):1;
			if (array_key_exists('Join time',$line))
			{
				if (strpos($line['Join time'],'-')!==false)
				{
					$parts=explode('-',$line['Join time']);
				} else
				{
					$parts=explode('/',$line['Join time']);
				}
				if (!array_key_exists(1,$parts)) $parts[1]='1';
				if (!array_key_exists(2,$parts)) $parts[2]='1';
				if (strlen($parts[2])!=4) // Would be nice to be smarter but unfortunately Open Office saves as yy not yyyy
				{
					$join_time=mktime(0,0,0,intval($parts[1]),intval($parts[2]),intval($parts[0])); // yy(yy)-mm-dd
				} else
				{
					$join_time=mktime(0,0,0,intval($parts[1]),intval($parts[0]),intval($parts[2])); // dd-mm-yyyy
				}
			} else
			{
				$join_time=NULL;
			}

			$avatar_url=array_key_exists('Avatar',$line)?$line['Avatar']:NULL;
			if (substr($avatar_url,0,strlen(get_base_url()))==get_base_url()) $avatar_url=substr($avatar_url,strlen(get_base_url()));
			$signature=array_key_exists('Signature',$line)?$line['Signature']:'';
			$is_perm_banned=array_key_exists('Banned',$line)?((strtoupper($line['Banned'])=='YES' || $line['Banned']=='1' || strtoupper($line['Banned'])=='Y' || strtoupper($line['Banned'])=='ON')?1:0):0;
			$reveal_age=array_key_exists('Reveal age',$line)?((strtoupper($line['Reveal age'])=='YES' || $line['Reveal age']=='1' || strtoupper($line['Reveal age'])=='Y' || strtoupper($line['Reveal age'])=='ON')?1:0):0;
			$language=array_key_exists('Language',$line)?$line['Language']:'';
			$allow_emails=array_key_exists('Accept member e-mails',$line)?((strtoupper($line['Accept member e-mails'])=='YES' || $line['Accept member e-mails']=='1' || strtoupper($line['Accept member e-mails'])=='Y' || strtoupper($line['Accept member e-mails'])=='ON')?1:0):0;
			$allow_emails_from_staff=array_key_exists('Opt-in',$line)?((strtoupper($line['Opt-in'])=='YES' || $line['Opt-in']=='1' || strtoupper($line['Opt-in'])=='Y' || strtoupper($line['Opt-in'])=='ON')?1:0):0;
			$primary_group=NULL;
			$groups=NULL;
			if (array_key_exists('Usergroup',$line))
			{
				$parts=explode('/',$line['Usergroup']);
				foreach ($parts as $p)
				{
					if (!array_key_exists($p,$all_groups))
					{
						require_code('ocf_groups_action');
						$g_id=ocf_make_group($p,0,0,0,'');
						$all_groups[$p]=$g_id;
						$_group_edit_url=build_url(array('page'=>'admin_ocf_groups','type'=>'_ed','id'=>$g_id),get_module_zone('admin_ocf_groups'));
						$group_edit_url=$_group_edit_url->evaluate();
						$outputted_messages->attach(do_lang_tempcode('MEMBER_IMPORT_GROUP_ADDED',escape_html($p),escape_html($group_edit_url)));
					}
				}
				$primary_group=$all_groups[$parts[0]];
				unset($parts[0]);
				$groups=array();
				foreach ($parts as $p)
				{
					$groups[]=$all_groups[$p];
				}
			}
			$photo_url=array_key_exists('Photo',$line)?$line['Photo']:'';
			if ($photo_url!='')
			{
				if ((!$new_member) && ($photo_url==$GLOBALS['FORUM_DRIVER']->get_member_row_field($linked_id,'m_photo_url')))
				{
					$photo_thumb_url=$GLOBALS['FORUM_DRIVER']->get_member_row_field($linked_id,'m_photo_url');
				} else
				{
					require_code('images');
					$photo_thumb_url='uploads/ocf_photos_thumbs/'.uniqid('',true).'.png';
					convert_image($photo_url,$photo_thumb_url,-1,-1,intval(get_option('thumb_width')),false);
				}
			} else $photo_thumb_url='';
			$custom_fields=array();
			foreach ($all_cpfs as $cpf)
			{
				$custom_fields[$cpf['id']]=array_key_exists($cpf['text_original'],$line)?$line[$cpf['text_original']]:$cpf['cf_default'];
				if ((!array_key_exists($cpf['text_original'],$line)) && ($cpf['cf_type']=='list'))
				{
					$parts=explode($custom_fields[$cpf['id']],'|');
					$custom_fields[$cpf['id']]=$parts[0];
				}
				if ($cpf['cf_type']=='integer')
				{
					$custom_fields[$cpf['id']]=intval($custom_fields[$cpf['id']]);
				} elseif ($cpf['cf_type']=='tick')
				{
					$custom_fields[$cpf['id']]=((strtoupper($custom_fields[$cpf['id']])=='YES' || strtoupper($custom_fields[$cpf['id']])=='Y' || strtoupper($custom_fields[$cpf['id']])=='ON' || $custom_fields[$cpf['id']]=='1')?1:0);
				} elseif (($cpf['cf_type']=='short_text') || ($cpf['cf_type']=='short_trans'))
				{
					$custom_fields[$cpf['id']]=substr(str_replace("\n",', ',str_replace(','."\n","\n",$custom_fields[$cpf['id']])),0,255);
				} elseif (($cpf['cf_type']=='long_text') || ($cpf['cf_type']=='long_trans'))
				{
					//$custom_fields[$cpf['id']]=$custom_fields[$cpf['id']];	Duh, no transform required
				} elseif ($cpf['cf_type']=='float')
				{
					if (preg_match('#^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\n(\d\d\d\d)$#',$custom_fields[$cpf['id']])!=0) // Convert to float based date
					{
						$parts=explode("\n",$custom_fields[$cpf['id']],2);
						$month_lookup=array(
							'Jan'=>0.1,
							'Feb'=>0.2,
							'Mar'=>0.3,
							'Apr'=>0.4,
							'May'=>0.5,
							'Jun'=>0.6,
							'Jul'=>0.7,
							'Aug'=>0.8,
							'Sep'=>0.9,
							'Oct'=>0.10,
							'Nov'=>0.11,
							'Dec'=>0.12,
						);
						$custom_fields[$cpf['id']]=floatval($parts[1])+$month_lookup[$parts[0]];
					} else
					{
						$custom_fields[$cpf['id']]=floatval($custom_fields[$cpf['id']]);
					}
				}
				unset($line[$cpf['text_original']]);
			}
			foreach (array_keys($headings) as $h) unset($line[$h]);
			unset($line[$email_address_key]);
			unset($line[$dob_key]);
			foreach ($line as $h=>$f) // New CPFs
			{
				$cf_id=ocf_make_custom_field($h,0,'','',0,0,0,0,'long_text');
				$_cpf_edit_url=build_url(array('page'=>'admin_ocf_customprofilefields','type'=>'_ed','id'=>$cf_id),get_module_zone('admin_ocf_customprofilefields'));
				$cpf_edit_url=$_cpf_edit_url->evaluate();
				$outputted_messages->attach(do_lang_tempcode('MEMBER_IMPORT_CPF_ADDED',escape_html($h),escape_html($cpf_edit_url)));
				$custom_fields[$cf_id]=$f;
				$all_cpfs[]=array('id'=>$cf_id,'cf_default'=>'','text_original'=>$h,'cf_type'=>'short_line');
			}
			if ($new_member)
			{
				if (is_null($password)) $password=$default_password;
				if (is_null($salt)) $salt='';
				if (is_null($password_compatibility_scheme))
					$password_compatibility_scheme=($use_temporary_passwords?'temporary':'');

				$linked_id=ocf_make_member($username,$password,is_null($email_address)?'':$email_address,$groups,$dob_day,$dob_month,$dob_year,$custom_fields,NULL,$primary_group,$validated,$join_time,NULL,'',$avatar_url,$signature,$is_perm_banned,(get_option('default_preview_guests')=='1')?1:0,$reveal_age,'',$photo_url,$photo_thumb_url,1,1,$language,$allow_emails,$allow_emails_from_staff,NULL,'',false,$password_compatibility_scheme,$salt,1,NULL,NULL,0,'*','');
				$num_added++;
			} else
			{
				$old_username=$GLOBALS['OCF_DRIVER']->get_member_row_field($linked_id,'m_username');
				if ($old_username==$username) $username=NULL;

				ocf_edit_member($linked_id,$email_address,NULL,$dob_day,$dob_month,$dob_year,NULL,$primary_group,$custom_fields,NULL,$reveal_age,NULL,NULL,$language,$allow_emails,$allow_emails_from_staff,$validated,$username,$password,NULL,NULL,NULL,NULL,NULL,$join_time,$avatar_url,$signature,$is_perm_banned,$photo_url,$photo_thumb_url,$salt,$password_compatibility_scheme,true);
				if (!is_null($groups))
				{
					foreach ($groups as $g_id)
					{
						$GLOBALS['FORUM_DB']->query_delete('f_group_members',array('gm_member_id'=>$linked_id,'gm_group_id'=>$g_id),'',1);
						$GLOBALS['FORUM_DB']->query_insert('f_group_members',array(
							'gm_group_id'=>$g_id,
							'gm_member_id'=>$linked_id,
							'gm_validated'=>1
						),false,true);
					}
				}
				$num_edited++;
			}

			$done++;
		}
		fclose($myfile);

		if ($done==0)
		{
			@unlink($path);
			return array(NULL,do_lang_tempcode('NO_DATA_IMPORTED'));
		}

		$outputted_messages->attach(do_lang_tempcode('NUM_MEMBERS_IMPORTED',escape_html(integer_format($num_added)),escape_html(integer_format($num_edited))));

		@unlink($path);
		return array('text/html',$outputted_messages);
	}
}
