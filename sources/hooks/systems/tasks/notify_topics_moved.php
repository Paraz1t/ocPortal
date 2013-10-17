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
 * @package		ocf_forum
 */

class Hook_task_notify_topics_moved
{
	/**
	 * Run the task hook.
	 *
	 * @param  string			An SQL segment of what topics are being moved
	 * @param  string			The name of the target forum
	 * @return ?array			A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (NULL: show standard success message)
	 */
	function run($or_list,$forum_name)
	{
		require_code('notifications');
		require_lang('ocf');

		$start=0;
		do
		{
			$topics2=$GLOBALS['FORUM_DB']->query('SELECT id,t_cache_first_title,t_cache_last_time FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE '.$or_list,100,$start,false,true);
			require_code('urls2');
			foreach ($topics2 as $_topic)
			{
				if ($_topic['t_cache_last_time']<time()-60*60*24*14) continue;

				$topic_id=$_topic['id'];
				$topic_title=$_topic['t_cache_first_title'];

				suggest_new_idmoniker_for('topicview','misc',strval($topic_id),'',$topic_title);

				// Now lets inform people tracking the topic that it has moved
				$subject=do_lang('TOPIC_MOVE_MAIL_SUBJECT',get_site_name(),$topic_title);
				$mail=do_lang('TOPIC_MOVE_MAIL',comcode_escape(get_site_name()),comcode_escape($topic_title),array(comcode_escape($forum_name)));
				dispatch_notification('ocf_topic',strval($topic_id),$subject,$mail);
			}
		}
		while (count($topics2)==100);

		return NULL;
	}
}
