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
 * @package		ssl
 */

/**
 * Module page class.
 */
class Module_admin_ssl
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
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'SSL_CONFIGURATION');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		$GLOBALS['HELPER_PANEL_PIC']='pagepics/ssl';
		$GLOBALS['HELPER_PANEL_TUTORIAL']='tut_security';

		if (get_file_base()!=get_custom_file_base()) warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));

		require_lang('security');

		if (get_option('enable_https')=='0')
		{
			$_config_url=build_url(array('page'=>'admin_config','type'=>'category','id'=>'SECURITY'),get_module_zone('admin_config'));
			$config_url=$_config_url->evaluate();
			inform_exit(do_lang_tempcode('HTTPS_DISABLED',escape_html($config_url.'#group_GENERAL')));
		}

		$type=get_param('type','misc');

		if ($type=='set') return $this->set();
		if ($type=='misc') return $this->ssl_interface();

		return new ocp_tempcode();
	}

	/**
	 * The UI for selecting HTTPS pages.
	 *
	 * @return tempcode		The UI
	 */
	function ssl_interface()
	{
		$title=get_page_title('SSL_CONFIGURATION');

		$content=new ocp_tempcode();
		$zones=find_all_zones();
		foreach ($zones as $zone)
		{
			$pages=find_all_pages_wrap($zone);
			$pk=array_keys($pages);
			@sort($pk); // @'d for inconsitency (some integer keys like 404) (annoying PHP quirk)
			foreach ($pk as $page)
			{
				if (!is_string($page)) $page=strval($page); // strval($page) as $page could have become numeric due to array imprecision
				$ticked=is_page_https($zone,$page);
				$content->attach(do_template('SSL_CONFIGURATION_ENTRY',array('_GUID'=>'a08c339d93834f968c8936b099c677a3','TICKED'=>$ticked,'PAGE'=>$page,'ZONE'=>$zone)));
			}
		}

		$url=build_url(array('page'=>'_SELF','type'=>'set'),'_SELF');
		return do_template('SSL_CONFIGURATION_SCREEN',array('_GUID'=>'823f395205f0c018861847e80c622710','URL'=>$url,'TITLE'=>$title,'CONTENT'=>$content));
	}

	/**
	 * The actualiser for selecting HTTPS pages.
	 *
	 * @return tempcode		The UI
	 */
	function set()
	{
		$zones=find_all_zones();
		foreach ($zones as $zone)
		{
			$pages=find_all_pages_wrap($zone);
			foreach (array_keys($pages) as $page)
			{
				if (!is_string($page)) $page=strval($page); // strval($page) as $page could have become numeric due to array imprecision
				$id=$zone.':'.$page;
				$value=post_param_integer('ssl_'.$zone.'__'.$page,0);
				$GLOBALS['SITE_DB']->query_delete('https_pages',array('https_page_name'=>$id),'',1);
				if ($value==1) $GLOBALS['SITE_DB']->query_insert('https_pages',array('https_page_name'=>$id));
			}
		}

		$title=get_page_title('SSL_CONFIGURATION');

		persistant_cache_empty();

		// Show it worked / Refresh
		$url=build_url(array('page'=>'_SELF','type'=>'misc'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

}


