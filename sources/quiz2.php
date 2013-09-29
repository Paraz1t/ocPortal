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
 * @package		quizzes
 */

/**
 * Load the questions for a quiz into a single string.
 *
 * @param  AUTO_LINK		The quiz ID
 * @return string			The text string
 */
function load_quiz_questions_to_string($id)
{
	$text='';
	$question_rows=$GLOBALS['SITE_DB']->query_select('quiz_questions',array('*'),array('q_quiz'=>$id),'ORDER BY q_order');
	foreach ($question_rows as $q)
	{
		$answer_rows=$GLOBALS['SITE_DB']->query_select('quiz_question_answers',array('*'),array('q_question'=>$q['id']),'ORDER BY q_order');
		$text.=get_translated_text($q['q_question_text']).(($q['q_long_input_field']==1)?' [LONG]':'').(($q['q_required']==1)?' [REQUIRED]':'').((($q['q_num_choosable_answers']==count($answer_rows)) && ($q['q_num_choosable_answers']!=0))?' [*]':'')."\n";
		foreach ($answer_rows as $a)
		{
			$text.=get_translated_text($a['q_answer_text']).(($a['q_is_correct']==1)?' [*]':'')."\n";
			$explanation=get_translated_text($a['q_explanation']);
			if ($explanation!='')
			{
				$text.=':'.$explanation."\n";
			}
		}
		$text.="\n";
	}
	return $text;
}

/**
 * Add the answers for a quiz.
 *
 * @param  AUTO_LINK		The quiz ID
 * @param  string			Text for questions
 * @param  ID_TEXT		The type
 * @set COMPETITION TEST SURVEY
 */
function _save_available_quiz_answers($id,$text,$type)
{
	$_existing=$GLOBALS['SITE_DB']->query_select('quiz_questions',array('*'),array('q_quiz'=>$id),'ORDER BY q_order');

	$_qs=explode("\n\n",$text);
	$qs=array();
	foreach ($_qs as $q)
	{
		$q=trim($q);
		if ($q!='') $qs[]=$q;
	}
	$num_q=0;

	$qs2=array();
	$existing=array();
	foreach ($qs as $i=>$q)
	{
		$_as=explode("\n",$q);
		$as=array();
		foreach ($_as as $a)
		{
			if ($a!='') $as[]=$a;
		}
		$q=array_shift($as);
		$matches=array();
		if (preg_match('#^(.*)#',$q,$matches)===false) continue;
		if (count($matches)==0) continue;

		$implicit_question_number=$i;//$matches[1];

		$qs2[$implicit_question_number]=$q."\n".implode("\n",$as);

		$question=trim($matches[count($matches)-1]);
		$question=str_replace(array(' [LONG]',' [*]',' [REQUIRED]'),array('','',''),$question);

		foreach ($_existing as $_i=>$q_row) // Try and match to an existing question, by the question name
		{
			if (get_translated_text($q_row['q_question_text'])==$question)
			{
				$existing[$implicit_question_number]=$q_row;
				unset($_existing[$_i]);
				continue 2;
			}
		}
		$existing[$implicit_question_number]=NULL;
	}

	ksort($qs2);
	ksort($existing);
	foreach ($existing as $i=>$e)
	{
		if (is_null($e)) $existing[$i]=array_shift($_existing);
	}
	foreach ($_existing as $e) $existing[]=$e;

	foreach ($qs2 as $i=>$q)
	{
		$_as=explode("\n",$q);
		$as=array();
		foreach ($_as as $a)
		{
			if ($a!='')
			{
				if (substr($a,0,1)==':') // Is an explanation
				{
					if (count($as)!=0)
						$as[count($as)-1][1]=substr($a,1);
				} else
				{
					$as[]=array($a,'');
				}
			}
		}
		$q=array_shift($as);
		$q=$q[0];
		$matches=array();
		if (preg_match('#^(.*)#',$q,$matches)===false) continue;
		if (count($matches)==0) continue;

		$question=trim($matches[count($matches)-1]);
		$long_input_field=(strpos($question,' [LONG]')!==false)?1:0;
		$question=str_replace(' [LONG]','',$question);
		$num_choosable_answers=(strpos($question,' [*]')!==false)?count($as):((count($as)>0)?1:0);
		$question=str_replace(' [*]','',$question);
		$required=(strpos($question,' [REQUIRED]')!==false)?1:0;
		$question=str_replace(' [REQUIRED]','',$question);

		if (is_null($existing[$i])) // We're adding a new question on the end
		{
			$q_id=$GLOBALS['SITE_DB']->query_insert('quiz_questions',array(
				'q_long_input_field'=>$long_input_field,
				'q_num_choosable_answers'=>$num_choosable_answers,
				'q_quiz'=>$id,
				'q_question_text'=>insert_lang($question,2),
				'q_order'=>$i,
				'q_required'=>$required,
			),true);

			// Now we add the answers
			foreach ($as as $x=>$_a)
			{
				list($a,$explanation)=$_a;

				$is_correct=((($x==0) && (strpos($qs2[$i],' [*]')===false) && ($type!='SURVEY')) || (strpos($a,' [*]')!==false))?1:0;
				$a=str_replace(' [*]','',$a);

				$GLOBALS['SITE_DB']->query_insert('quiz_question_answers',array(
					'q_question'=>$q_id,
					'q_answer_text'=>insert_lang($a,2),
					'q_is_correct'=>$is_correct,
					'q_order'=>$x,
					'q_explanation'=>insert_lang($explanation,2),
				));
			}
		} else // We're replacing an existing question
		{
			$GLOBALS['SITE_DB']->query_update('quiz_questions',array(
				'q_long_input_field'=>$long_input_field,
				'q_num_choosable_answers'=>$num_choosable_answers,
				'q_quiz'=>$id,
				'q_question_text'=>lang_remap($existing[$i]['q_question_text'],$question),
				'q_order'=>$i,
				'q_required'=>$required,
			),array('id'=>$existing[$i]['id']));

			// Now we add the answers
			$_existing_a=$GLOBALS['SITE_DB']->query_select('quiz_question_answers',array('*'),array('q_question'=>$existing[$i]['id']),'ORDER BY q_order');
			$existing_a=array();
			foreach ($as as $x=>$_a) // Try and match to an existing answer
			{
				list($a,$explanation)=$_a;

				$a=str_replace(' [*]','',$a);

				foreach ($_existing_a as $_x=>$a_row)
				{
					if (get_translated_text($a_row['q_answer_text'])==$a)
					{
						$existing_a[]=$a_row;
						unset($_existing_a[$_x]);
						continue 2;
					}
				}
				$existing_a[]=NULL;
			}
			foreach ($existing_a as $_x=>$e)
			{
				if (is_null($e)) $existing_a[$_x]=array_shift($_existing_a);
			}
			foreach ($_existing_a as $e) $existing_a[]=$e;
			foreach ($as as $x=>$_a)
			{
				list($a,$explanation)=$_a;

				$is_correct=((($x==0) && (strpos($qs2[$i],' [*]')===false)) || (strpos($a,' [*]')!==false))?1:0;
				$a=str_replace(' [*]','',$a);

				if (!is_null($existing_a[$x]))
				{
					$GLOBALS['SITE_DB']->query_update('quiz_question_answers',array(
						'q_answer_text'=>lang_remap($existing_a[$x]['q_answer_text'],$a),
						'q_is_correct'=>$is_correct,
						'q_order'=>$x,
						'q_explanation'=>insert_lang($explanation,2),
					),array('id'=>$existing_a[$x]['id']),'',1);
				} else
				{
					$GLOBALS['SITE_DB']->query_insert('quiz_question_answers',array(
						'q_question'=>$existing[$i]['id'],
						'q_answer_text'=>insert_lang($a,2),
						'q_is_correct'=>$is_correct,
						'q_order'=>$x,
						'q_explanation'=>insert_lang($explanation,2),
					));
				}
			}
			// If there were more answers before, deleting extra ones
			if (count($existing_a)>count($as))
			{
				for ($x=count($as);$x<count($existing_a);$x++)
				{
					$GLOBALS['SITE_DB']->query_delete('quiz_question_answers',array('id'=>$existing_a[$x]['id']),'',1);
				}
			}
		}

		$num_q++;
	}

	// If there were more answers questions, deleting extra ones
	if (count($existing)>$num_q)
	{
		for ($x=$num_q;$x<count($existing);$x++)
		{
			$GLOBALS['SITE_DB']->query_delete('quiz_questions',array('id'=>$existing[$x]['id']),'',1);
		}
	}
}

/**
 * Add a quiz.
 *
 * @param  SHORT_TEXT	The name of the quiz
 * @param  ?integer		The number of minutes allowed for completion (NULL: NA)
 * @param  LONG_TEXT		The text shown at the start of the quiz
 * @param  LONG_TEXT		The text shown at the end of the quiz
 * @param  LONG_TEXT		The text shown at the end of the quiz on failure
 * @param  LONG_TEXT		Notes
 * @param  integer		Percentage correctness required for competition
 * @param  ?TIME			The time the quiz is opened (NULL: now)
 * @param  ?TIME			The time the quiz is closed (NULL: never)
 * @param  integer		The number of winners for this if it is a competition
 * @param  integer		The minimum number of hours between attempts
 * @param  ID_TEXT		The type
 * @set    SURVEY COMPETITION TEST
 * @param  BINARY			Whether this is validated
 * @param  string			Text for questions
 * @param  ?MEMBER		The member adding it (NULL: current member)
 * @param  integer		The number of points awarded for completing/passing the quiz/test
 * @param  ?AUTO_LINK	Newsletter for which a member must be on to enter (NULL: none)
 * @param  ?TIME			The add time (NULL: now)
 * @param  ?SHORT_TEXT	Meta keywords for this resource (NULL: do not edit) (blank: implicit)
 * @param  ?LONG_TEXT	Meta description for this resource (NULL: do not edit) (blank: implicit)
 * @return AUTO_LINK		The ID
 */
function add_quiz($name,$timeout,$start_text,$end_text,$end_text_fail,$notes,$percentage,$open_time,$close_time,$num_winners,$redo_time,$type,$validated,$text,$submitter=NULL,$points_for_passing=0,$tied_newsletter=NULL,$add_time=NULL,$meta_keywords='',$meta_description='')
{
	if (is_null($submitter)) $submitter=get_member();
	if (is_null($add_time)) $add_time=time();

	if (!addon_installed('unvalidated')) $validated=1;
	$id=$GLOBALS['SITE_DB']->query_insert('quizzes',array(
		'q_name'=>insert_lang($name,2),
		'q_timeout'=>$timeout,
		'q_start_text'=>insert_lang($start_text,2),
		'q_end_text'=>insert_lang($end_text,2),
		'q_end_text_fail'=>insert_lang($end_text_fail,2),
		'q_notes'=>$notes,
		'q_percentage'=>$percentage,
		'q_open_time'=>$open_time,
		'q_close_time'=>$close_time,
		'q_num_winners'=>$num_winners,
		'q_redo_time'=>$redo_time,
		'q_type'=>$type,
		'q_validated'=>$validated,
		'q_submitter'=>$submitter,
		'q_add_date'=>$add_time,
		'q_points_for_passing'=>$points_for_passing,
		'q_tied_newsletter'=>$tied_newsletter,
	),true);

	_save_available_quiz_answers($id,$text,$type);

	require_code('seo2');
	if (($meta_keywords=='') && ($meta_description==''))
	{
		seo_meta_set_for_implicit('quiz',strval($id),array($name,$start_text),$start_text);
	} else
	{
		seo_meta_set_for_explicit('quiz',strval($id),$meta_keywords,$meta_description);
	}

	log_it('ADD_QUIZ',strval($id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('quiz',strval($id),NULL,NULL,true);
	}

	return $id;
}

/**
 * Edit a quiz.
 *
 * @param  AUTO_LINK		The ID
 * @param  SHORT_TEXT	The name of the quiz
 * @param  ?integer		The number of minutes allowed for completion (NULL: NA)
 * @param  LONG_TEXT		The text shown at the start of the quiz
 * @param  LONG_TEXT		The text shown at the end of the quiz
 * @param  LONG_TEXT		The text shown at the end of the quiz on failure
 * @param  LONG_TEXT		Notes
 * @param  integer		Percentage correctness required for competition
 * @param  ?TIME			The time the quiz is opened (NULL: now)
 * @param  ?TIME			The time the quiz is closed (NULL: never)
 * @param  integer		The number of winners for this if it is a competition
 * @param  integer		The minimum number of hours between attempts
 * @param  ID_TEXT		The type
 * @set    SURVEY COMPETITION TEST
 * @param  BINARY			Whether this is validated
 * @param  string			Text for questions
 * @param  SHORT_TEXT	Meta keywords
 * @param  LONG_TEXT		Meta description
 * @param  integer		The number of points awarded for completing/passing the quiz/test
 * @param  ?TIME			Edit time (NULL: either means current time, or if $null_is_literal, means reset to to NULL)
 * @param  ?TIME			Add time (NULL: do not change)
 * @param  ?MEMBER		Submitter (NULL: do not change)
 * @param  boolean		Determines whether some NULLs passed mean 'use a default' or literally mean 'set to NULL'
 */
function edit_quiz($id,$name,$timeout,$start_text,$end_text,$end_text_fail,$notes,$percentage,$open_time,$close_time,$num_winners,$redo_time,$type,$validated,$text,$meta_keywords,$meta_description,$points_for_passing=0,$tied_newsletter=NULL,$add_time=NULL,$submitter=NULL,$null_is_literal=false)
{
	$rows=$GLOBALS['SITE_DB']->query_select('quizzes',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows))
	{
		warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	}
	$_name=$rows[0]['q_name'];
	$_start_text=$rows[0]['q_start_text'];
	$_end_text=$rows[0]['q_end_text'];
	$_end_text_fail=$rows[0]['q_end_text_fail'];

	if (!addon_installed('unvalidated')) $validated=1;

	require_code('submit');
	$just_validated=(!content_validated('quiz',strval($id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('quiz',strval($id));
	}

	$update_map=array(
		'q_name'=>lang_remap($_name,$name),
		'q_timeout'=>$timeout,
		'q_start_text'=>lang_remap($_start_text,$start_text),
		'q_end_text'=>lang_remap($_end_text,$end_text),
		'q_end_text_fail'=>lang_remap($_end_text_fail,$end_text_fail),
		'q_notes'=>$notes,
		'q_percentage'=>$percentage,
		'q_open_time'=>$open_time,
		'q_close_time'=>$close_time,
		'q_num_winners'=>$num_winners,
		'q_redo_time'=>$redo_time,
		'q_type'=>$type,
		'q_validated'=>$validated,
		'q_points_for_passing'=>$points_for_passing,
		'q_tied_newsletter'=>$tied_newsletter,
	);

	if (!is_null($add_time))
		$update_map['q_add_date']=$add_time;
	if (!is_null($submitter))
		$update_map['q_submitter']=$submitter;

	$GLOBALS['SITE_DB']->query_update('quizzes',$update_map,array('id'=>$id));

	if (!fractional_edit())
		_save_available_quiz_answers($id,$text,$type);

	require_code('urls2');
	suggest_new_idmoniker_for('quiz','do',strval($id),'',$name);

	require_code('seo2');
	seo_meta_set_for_explicit('quiz',strval($id),$meta_keywords,$meta_description);

	log_it('EDIT_QUIZ',strval($id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		generate_resourcefs_moniker('quiz',strval($id));
	}
}

/**
 * Delete a quiz.
 *
 * @param  AUTO_LINK		The ID
 */
function delete_quiz($id)
{
	$rows=$GLOBALS['SITE_DB']->query_select('quizzes',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows))
	{
		warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	}
	$_name=$rows[0]['q_name'];
	$_start_text=$rows[0]['q_start_text'];
	$_end_text=$rows[0]['q_end_text'];
	$name=get_translated_text($_name);

	delete_lang($_name);
	delete_lang($_start_text);
	delete_lang($_end_text);

	require_code('seo2');
	seo_meta_erase_storage('quiz',strval($id));

	$GLOBALS['SITE_DB']->query_delete('quizzes',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('quiz_winner',array('q_quiz'=>$id));
	$entries=$GLOBALS['SITE_DB']->query_select('quiz_questions',array('*'),array('q_quiz'=>$id));
	foreach ($entries as $entry)
	{
		delete_lang($entry['q_question_text']);
		$answers=$GLOBALS['SITE_DB']->query_select('quiz_question_answers',array('*'),array('q_question'=>$entry['id']));
		foreach ($answers as $answer)
		{
			delete_lang($answer['q_answer_text']);
		}
		$GLOBALS['SITE_DB']->query_delete('quiz_entry_answer',array('q_question'=>$entry['id']));
		$GLOBALS['SITE_DB']->query_delete('quiz_question_answers',array('q_question'=>$entry['id']));
	}
	$GLOBALS['SITE_DB']->query_delete('quiz_questions',array('q_quiz'=>$id));
	$GLOBALS['SITE_DB']->query_delete('quiz_entries',array('q_quiz'=>$id));

	log_it('DELETE_QUIZ',strval($id),$name);

	if ((addon_installed('occle')) && (!running_script('install')))
	{
		require_code('resource_fs');
		expunge_resourcefs_moniker('quiz',strval($id));
	}
}

