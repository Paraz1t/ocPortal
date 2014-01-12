<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		filedump
 */

/**
 * Module page class.
 */
class Module_filedump
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
		$info['version']=4;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('filedump');

		delete_privilege('delete_anything_filedump');
		delete_privilege('upload_filedump');
		delete_privilege('upload_anything_filedump');

		//require_code('files');
		//deldir_contents(get_custom_file_base().'/uploads/filedump',true);
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
			$GLOBALS['SITE_DB']->create_table('filedump',array(
				'id'=>'*AUTO',
				'name'=>'ID_TEXT',
				'path'=>'URLPATH',
				'description'=>'SHORT_TRANS',
				'the_member'=>'MEMBER'
			));

			add_privilege('FILEDUMP','upload_anything_filedump',false);
			add_privilege('FILEDUMP','upload_filedump',true);
			add_privilege('FILEDUMP','delete_anything_filedump',false);
		}

		if ((!is_null($upgrade_from)) && ($upgrade_from<4))
		{
			if (addon_installed('redirects_editor'))
			{
				$GLOBALS['SITE_DB']->query_delete('redirects',array('r_from_page'=>'filedump','r_from_zone'=>'collaboration','r_to_page'=>'filedump','r_to_zone'=>'cms','r_is_transparent'=>1));
			}

			$GLOBALS['SITE_DB']->query_update('privilege_list',array('p_section'=>'FILEDUMP'),array('p_section'=>'FILE_DUMP'));
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
	function get_entry_points($check_perms=true,$member_id=NULL,$support_crosslinks=true,$be_deferential=false)
	{
		return array(
			'misc'=>array('FILEDUMP','menu/cms/filedump'),
		);
	}

	var $title;
	var $place;

	/**
	 * Standard modular pre-run function, so we know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
	function pre_run()
	{
		require_lang('filedump');

		$type=get_param('type','misc');

		if ($type=='misc')
		{
			$place=filter_naughty(get_param('place','/'));
			if (substr($place,-1,1)!='/') $place.='/';

			set_feed_url('?mode=filedump&filter='.$place);

			// Show breadcrumbs
			$dirs=explode('/',substr($place,0,strlen($place)-1));
			$pre='';
			$breadcrumbs=new ocp_tempcode();
			foreach ($dirs as $i=>$d)
			{
				if ($i==0) $d=do_lang('FILEDUMP');

				if (array_key_exists($i+1,$dirs))
				{
					$breadcrumbs_url=build_url(array('page'=>'_SELF','place'=>$pre.$dirs[$i].'/'),'_SELF');
					if (!$breadcrumbs->is_empty()) $breadcrumbs->attach(do_template('BREADCRUMB_SEPARATOR',array('_GUID'=>'7ee62e230d53344a7d9667dc59be21c6')));
					$breadcrumbs->attach(hyperlink($breadcrumbs_url,$d));
				}
				$pre.=$dirs[$i].'/';
			}
			if (!$breadcrumbs->is_empty())
			{
				breadcrumb_add_segment($breadcrumbs,protect_from_escaping('<span>'.escape_html($d).'</span>'));
			} else
			{
				breadcrumb_set_self(($i==1)?do_lang_tempcode('FILEDUMP'):make_string_tempcode(escape_html($d)));
			}

			$this->place=$place;

			$this->title=get_screen_title('FILEDUMP');
		}

		if ($type=='embed')
		{
			$this->title=get_screen_title('FILEDUMP_EMBED');
		}

		if ($type=='mass')
		{
			$action=post_param('action');
			switch ($action)
			{
				case 'edit':
					$this->title=get_screen_title('FILEDUMP_EDIT');
					break;

				case 'delete':
					$this->title=get_screen_title('FILEDUMP_DELETE');
					break;

				default:
					$this->title=get_screen_title('FILEDUMP_MOVE');
					break;
			}

			breadcrumb_set_parents(array(array('_SELF:_SELF',do_lang_tempcode('FILEDUMP'))));

			if ((post_param_integer('confirmed',0)!=1) && ($action!='edit'/*edit too trivial/specific to need a confirm*/))
			{
				breadcrumb_set_self(do_lang_tempcode('CONFIRM'));
			}
		}

		return NULL;
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_lang('filedump');
		require_css('filedump');
		require_code('files2');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->do_gui();
		if ($type=='embed') return $this->do_embed();
		if ($type=='mass') return $this->do_mass();
		if ($type=='ac') return $this->do_add_folder();
		if ($type=='ad') return $this->do_upload();

		return new ocp_tempcode();
	}

	/**
	 * The main user interface for the file dump.
	 *
	 * @return tempcode	The UI.
	 */
	function do_gui()
	{
		require_code('form_templates');
		require_code('images');

		disable_php_memory_limit();

		$place=$this->place;

		$type_filter=get_param('type_filter','');

		$search=get_param('search','',true);
		if ($search==do_lang('SEARCH')) $search='';

		$sort=get_param('sort','time ASC');
		if (strpos($sort,' ')===false)
		{
			$sort='time ASC';
		}
		list($order,$direction)=explode(' ',$sort,2);
		if ($direction!='ASC' && $direction!='DESC') $direction='ASC';

		$GLOBALS['FEED_URL']=find_script('backend').'?mode=filedump&filter='.$place;

		// Check directory exists
		$fullpath=get_custom_file_base().'/uploads/filedump'.$place;
		if (!file_exists(get_custom_file_base().'/uploads/filedump'.$place))
		{
			if (has_privilege(get_member(),'upload_filedump'))
			{
				@mkdir($fullpath,0777) OR warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY',escape_html($fullpath),escape_html(dirname($fullpath))));
				fix_permissions($fullpath,0777);
				sync_file($fullpath);
			}
		}

		// Find all files in the filedump directory
		$db_rows=list_to_map('name',$GLOBALS['SITE_DB']->query_select('filedump',array('*'),array('path'=>$place)));
		$handle=opendir(get_custom_file_base().'/uploads/filedump'.$place);
		$files=array();
		while (false!==($filename=readdir($handle)))
		{
			if (!should_ignore_file('uploads/filedump'.$place.$filename,IGNORE_ACCESS_CONTROLLERS | IGNORE_HIDDEN_FILES))
			{
				$_full=get_custom_file_base().'/uploads/filedump'.$place.$filename;
				if (!file_exists($_full)) continue; // Broken symlink or (?) permission problem

				$is_directory=!is_file($_full);

				$db_row=isset($db_rows[$filename])?$db_rows[$filename]:NULL;

				$_description=isset($db_row)?get_translated_text($db_row['description']):'';

				if ($is_directory)
				{
					if (!$this->_recursive_search($place.$filename.'/',$_description,$search,$type_filter)) continue;
				} else
				{
					if (!$this->_matches_filter($filename,$_description,$search,$type_filter)) continue;
				}

				if ($_description!='')
				{
					$description=make_string_tempcode($_description);
					$description_2=$description;
				} else
				{
					$description=new ocp_tempcode();
					$description_2=($is_directory)?do_lang_tempcode('FOLDER'):new ocp_tempcode();
				}
				if ($is_directory)
				{
					$filesize=get_directory_size($_full);
					$timestamp=NULL;
				} else
				{
					$filesize=filesize($_full);
					$timestamp=filemtime($_full);
				}
				$choosable=((!is_null($db_row)) && ($db_row['the_member']==get_member())) || (has_privilege(get_member(),'delete_anything_filedump'));

				$width=mixed();
				$height=mixed();
				if (is_image($_full))
				{
					$dims=@getimagesize($_full);
					if ($dims!==false)
					{
						list($width,$height)=$dims;
					}
				}

				$files[]=array(
					'filename'=>$is_directory?($filename.'/'):$filename,
					'description'=>$description,
					'description_2'=>$description_2,
					'width'=>$width,
					'height'=>$height,
					'_size'=>$filesize,
					'size'=>clean_file_size($filesize),
					'_time'=>$timestamp,
					'time'=>is_null($timestamp)?NULL:get_timezoned_date($timestamp,false),
					'is_directory'=>$is_directory,
					'choosable'=>$choosable,
				);
			}
		}
		closedir($handle);

		switch ($order)
		{
			case 'time':
				@sort_maps_by($files,'_time');
				break;
			case 'name':
				@sort_maps_by($files,'filename');
				break;
			case 'size':
				@sort_maps_by($files,'_size');
				break;
		}
		if ($direction=='DESC')
		{
			$files=array_reverse($files);
		}

		$thumbnails=array();

		if (count($files)>0) // If there are some files
		{
			require_code('templates_columned_table');
			$header_row=columned_table_header_row(array(
				do_lang_tempcode('FILENAME'),
				do_lang_tempcode('DESCRIPTION'),
				do_lang_tempcode('SIZE'),
				do_lang_tempcode('DATE_TIME'),
				do_lang_tempcode('ACTIONS'),
				do_lang_tempcode('CHOOSE'),
			));

			$url=mixed();

			$rows=new ocp_tempcode();
			foreach ($files as $i=>$file)
			{
				$filename=$file['filename'];

				if ($file['is_directory']) // Directory
				{
					$url=build_url(array('page'=>'_SELF','place'=>$place.$filename,'sort'=>$sort,'type_filter'=>$type_filter,'search'=>$search),'_SELF');

					$is_image=false;

					$image_url=find_theme_image('icons/48x48/menu/_generic_admin/view_this_category');

					$embed_url=mixed();
				} else // File
				{
					$url=get_custom_base_url().'/uploads/filedump'.str_replace('%2F','/',rawurlencode($place.$filename));

					if (is_image($url))
					{
						$is_image=true;
						$image_url=$url;
					} else
					{
						$is_image=false;
						$image_url=find_theme_image('no_image');
					}

					$embed_url=build_url(array('page'=>'_SELF','type'=>'embed','place'=>$place,'file'=>$filename),'_SELF');
				}

				$actions=new ocp_tempcode();
				if ($file['choosable'])
				{
					$actions->attach(do_template('COLUMNED_TABLE_ROW_CELL_TICK',array(
						'LABEL'=>do_lang_tempcode('CHOOSE'),
						'NAME'=>'select_'.strval($i),
						'VALUE'=>rtrim($filename,'/'),
					)));
				}

				// Thumbnail
				$thumbnail=do_image_thumb($image_url,$file['description_2'],false,false,NULL,NULL,true);
				$thumbnails[]=array(
					'FILENAME'=>$filename,
					'THUMBNAIL'=>$thumbnail,
					'IS_IMAGE'=>$is_image,
					'URL'=>$url,
					'DESCRIPTION'=>$file['description_2'],
					'_SIZE'=>is_null($file['_size'])?'':strval($file['_size']),
					'SIZE'=>$file['size'],
					'_TIME'=>is_null($file['_time'])?'':strval($file['_time']),
					'TIME'=>is_null($file['time'])?'':$file['time'],
					'WIDTH'=>is_null($file['width'])?'':strval($file['width']),
					'HEIGHT'=>is_null($file['height'])?'':strval($file['height']),
					'IS_DIRECTORY'=>$file['is_directory'],
					'CHOOSABLE'=>$file['choosable'],
					'ACTIONS'=>$actions,
					'EMBED_URL'=>$embed_url,
				);

				// Editable description
				$description_field=do_template('COLUMNED_TABLE_ROW_CELL_LINE',array(
					'LABEL'=>do_lang_tempcode('DESCRIPTION'),
					'NAME'=>'description_value_'.strval($i),
					'VALUE'=>$file['description'],
					'HIDDEN_NAME'=>'description_file_'.strval($i),
					'HIDDEN_VALUE'=>rtrim($filename,'/'),
				));

				// Size
				if (!is_null($file['width']))
				{
					$size=do_lang_tempcode('FILEDUMP_SIZE',escape_html($file['size']),escape_html(strval($file['width'])),escape_html(strval($file['height'])));
				} else
				{
					$size=make_string_tempcode(escape_html($file['size']));
				}

				// Listing row
				$rows->attach(columned_table_row(array(
					hyperlink($url,escape_html($filename),!$file['is_directory']/*files go to new window*/),
					$description_field,
					$size,
					is_null($file['time'])?do_lang_tempcode('NA'):make_string_tempcode(escape_html($file['time'])),
					is_null($embed_url)?new ocp_tempcode():hyperlink($embed_url,do_lang_tempcode('FILEDUMP_EMBED')),
					$actions
				)));
			}

			$listing=do_template('COLUMNED_TABLE',array('_GUID'=>'1c0a91d47c5fc8a7c2b35c7d9b36132f','HEADER_ROW'=>$header_row,'ROWS'=>$rows));
		} else
		{
			$listing=new ocp_tempcode();
		}

		// Find directories we could move stuff into / upload to
		require_code('files2');
		$directories=get_directory_contents(get_custom_file_base().'/uploads/filedump','',false,true,false);
		$directories[]='';
		sort($directories);
		$other_directories=$directories;
		foreach ($other_directories as $i=>$directory)
		{
			if ('/'.$directory.(($directory=='')?'':'/')==$place)
			{
				unset($other_directories[$i]);
				break;
			}
		}

		// Do a form so people can upload their own stuff
		if (has_privilege(get_member(),'upload_filedump'))
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>'ad','uploading'=>1),'_SELF');

			$submit_name=do_lang_tempcode('FILEDUMP_UPLOAD');

			$max=floatval(get_max_file_size());
			$text=new ocp_tempcode();
			if ($max<30.0)
			{
				$config_url=get_upload_limit_config_url();
				$text->attach(do_lang_tempcode(is_null($config_url)?'MAXIMUM_UPLOAD':'MAXIMUM_UPLOAD_STAFF',escape_html(($max>10.0)?integer_format(intval($max)):float_format($max/1024.0/1024.0)),escape_html(is_null($config_url)?'':$config_url)));
			}

			$fields=new ocp_tempcode();
			url_default_parameters__enable();
			$fields->attach(form_input_upload_multi(do_lang_tempcode('FILES'),do_lang_tempcode('DESCRIPTION_FILES'),'files',true));
			$fields->attach(form_input_line(do_lang_tempcode('DESCRIPTION'),do_lang_tempcode('DESCRIPTION_DESCRIPTION_FILES'),'description','',false));
			$list=new ocp_tempcode();
			foreach ($directories as $directory)
			{
				$_directory='/'.$directory.(($directory=='')?'':'/');
				$list->attach(form_input_list_entry($_directory,($_directory==$place),'/'.$directory));
			}
			$fields->attach(form_input_list(do_lang_tempcode('FOLDER'),'','place',$list));

			url_default_parameters__disable();

			$hidden=new ocp_tempcode();
			handle_max_file_size($hidden);

			$upload_form=do_template('FORM',array(
				'TABINDEX'=>strval(get_form_field_tabindex()),
				'SKIP_REQUIRED'=>true,
				'HIDDEN'=>$hidden,
				'TEXT'=>$text,
				'FIELDS'=>$fields,
				'SUBMIT_NAME'=>$submit_name,
				'SUBMIT_ICON'=>'menu___generic_admin__add_one',
				'URL'=>$post_url,
			));
		} else
		{
			$upload_form=new ocp_tempcode();
		}

		// Do a form so people can make folders
		if ((get_option('is_on_folder_create')=='1') && (has_privilege(get_member(),'upload_filedump')))
		{
			$post_url=build_url(array('page'=>'_SELF','type'=>'ac'),'_SELF');

			$submit_name=do_lang_tempcode('FILEDUMP_CREATE_FOLDER');

			$fields=new ocp_tempcode();
			$fields->attach(form_input_line(do_lang_tempcode('NAME'),do_lang_tempcode('DESCRIPTION_FOLDER_NAME'),'name','',true));
			$fields->attach(form_input_line(do_lang_tempcode('DESCRIPTION'),new ocp_tempcode(),'description','',false));

			$hidden=form_input_hidden('place',$place);

			$create_folder_form=do_template('FORM',array(
				'_GUID'=>'043f9b595d3699b7d8cd7f2284cdaf98',
				'TABINDEX'=>strval(get_form_field_tabindex()),
				'SKIP_REQUIRED'=>true,
				'SECONDARY_FORM'=>true,
				'HIDDEN'=>$hidden,
				'TEXT'=>'',
				'FIELDS'=>$fields,
				'SUBMIT_NAME'=>$submit_name,
				'SUBMIT_ICON'=>'menu___generic_admin__add_one_category',
				'URL'=>$post_url,
			));
		} else
		{
			$create_folder_form=new ocp_tempcode();
		}

		$post_url=build_url(array('page'=>'_SELF','type'=>'mass','place'=>$place),'_SELF');

		return do_template('FILEDUMP_SCREEN',array(
			'_GUID'=>'3f49a8277a11f543eff6488622949c84',
			'TITLE'=>$this->title,
			'PLACE'=>$place,
			'THUMBNAILS'=>$thumbnails,
			'LISTING'=>$listing,
			'UPLOAD_FORM'=>$upload_form,
			'CREATE_FOLDER_FORM'=>$create_folder_form,
			'TYPE_FILTER'=>$type_filter,
			'SEARCH'=>$search,
			'SORT'=>$sort,
			'POST_URL'=>$post_url,
			'DIRECTORIES'=>$directories,
			'OTHER_DIRECTORIES'=>$other_directories,
		));
	}

	/**
	 * Find whether a file matches the search filter. If there is no filter, anything will match.
	 *
	 * @param  PATH		Folder path.
	 * @param  string		Folder description.
	 * @param  string		Search filter.
	 * @param  string		Type filter.
	 * @set images videos audios others 
	 * @return boolean	Whether it passes the filter.
	 */
	function _recursive_search($place,$description,$search,$type_filter)
	{
		if ($type_filter=='')
		{
			if ($search!='')
			{
				if ((strpos(basename($place),$search)!==false) || (strpos($description,$search)!==false)) // Directory itself matches
					return true;
			} else
			{
				return true;
			}
		}

		$db_rows=list_to_map('name',$GLOBALS['SITE_DB']->query_select('filedump',array('*'),array('path'=>$place)));

		$handle=opendir(get_custom_file_base().'/uploads/filedump'.$place);
		while (false!==($filename=readdir($handle)))
		{
			if (!should_ignore_file('uploads/filedump'.$place.$filename,IGNORE_ACCESS_CONTROLLERS | IGNORE_HIDDEN_FILES))
			{
				$_full=get_custom_file_base().'/uploads/filedump'.$place.$filename;
				if (!file_exists($_full)) continue; // Broken symlink or (?) permission problem

				$is_directory=!is_file($_full);

				$db_row=isset($db_rows[$filename])?$db_rows[$filename]:NULL;

				$_description=isset($db_row)?get_translated_text($db_row['description']):'';

				if ($is_directory)
				{
					if ($this->_recursive_search($place.$filename.'/',$_description,$search,$type_filter)) return true; // Look deeper
				} else
				{
					if ($this->_matches_filter($filename,$_description,$search,$type_filter)) return true; // File under matches
				}
			}
		}
		closedir($handle);

		return false;
	}

	/**
	 * Find whether a file matches the search filter. If there is no filter, anything will match.
	 *
	 * @param  ID_TEXT	Filename.
	 * @param  string		File description.
	 * @param  string		Search filter.
	 * @param  string		Type filter.
	 * @set images videos audios others 
	 * @return boolean	Whether it passes the filter.
	 */
	function _matches_filter($filename,$_description,$search,$type_filter)
	{
		if ($search!='')
		{
			if ((strpos($filename,$search)===false) && (strpos($_description,$search)===false))
				return false;
		}

		switch ($type_filter)
		{
			case 'images':
				if (!is_image($filename)) return false;
				break;

			case 'videos':
				if ((substr(strtolower($filename),-4)=='.mp3') || (substr(strtolower($filename),-4)=='.wav') || (substr(strtolower($filename),-4)=='.ogg'))
					return false;
				if (!is_video($filename,true)) return false;
				break;

			case 'audios':
				if ((substr(strtolower($filename),-4)!='.mp3') && (substr(strtolower($filename),-4)!='.wav') && (substr(strtolower($filename),-4)!='.ogg'))
					return false;
				//if (!is_audio($filename)) return false;		Use official call in v10
				break;

			case 'others':
				if (is_image($filename)) return false;
				if (is_video($filename,true)) return false;
				if ((substr(strtolower($filename),-4)=='.mp3') || (substr(strtolower($filename),-4)=='.wav') || (substr(strtolower($filename),-4)=='.ogg'))
					return false;
				break;
		}

		return true;
	}

	/**
	 * The main user interface for the file dump.
	 *
	 * @return tempcode	The UI.
	 */
	function do_embed()
	{
		require_code('form_templates');
		require_code('images');

		$place=get_param('place');
		$file=get_param('file');

		$url=get_custom_base_url().'/uploads/filedump'.str_replace('%2F','/',rawurlencode($place.$file));
		$path=get_custom_file_base().'/uploads/filedump'.$place.$file;

		$generated=mixed();
		$rendered=mixed();
		if (strtoupper(ocp_srv('REQUEST_METHOD'))=='POST')
		{
			$generated='[media';
			$param=post_param('description','');
			if ($param!='')
				$generated.=' description="'.comcode_escape($param).'"';
			$param=post_param('type','');
			if ($param!='')
				$generated.=' type="'.comcode_escape($param).'"';
			$param=post_param('framed','0');
			if ($param!='')
				$generated.=' framed="'.comcode_escape($param).'"';
			$param=post_param('width','');
			if ($param!='')
				$generated.=' width="'.comcode_escape($param).'"';
			$param=post_param('height','');
			if ($param!='')
				$generated.=' height="'.comcode_escape($param).'"';
			$param=post_param('align','');
			if ($param!='')
				$generated.=' align="'.comcode_escape($param).'"';
			$param=post_param('float','');
			if ($param!='')
				$generated.=' float="'.comcode_escape($param).'"';
			$param=post_param('thumb','0');
			if ($param!='')
				$generated.=' thumb="'.comcode_escape($param).'"';
			$param=post_param('thumb_url','');
			if ($param!='')
				$generated.=' thumb_url="'.comcode_escape($param).'"';
			$generated.=']uploads/filedump'.$place.$file.'[/media]';

			$rendered=comcode_to_tempcode($generated);
		}

		$_description=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','description',array('name'=>$file,'path'=>$place));
		if (is_null($_description))
		{
			$description=post_param('description','');
		} else
		{
			$description=post_param('description',get_translated_text($_description));
		}

		require_lang('comcode');

		$adv=do_lang('BLOCK_IND_ADVANCED');

		$fields=new ocp_tempcode();

		$fields->attach(form_input_line_comcode(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_description'),do_lang('COMCODE_TAG_attachment_PARAM_description'),'description',$description,false));

		$_description=do_lang('COMCODE_TAG_attachment_PARAM_type');
		if (substr($_description,0,strlen($adv)+1)==$adv) $_description=substr($_description,0,strlen($adv)+1);
		$list=new ocp_tempcode();
		$list->attach(form_input_list_entry('',false,do_lang('MEDIA_TYPE_')));
		$hooks=find_all_hooks('systems','media_rendering');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/media_rendering/'.$hook);
			$ob=object_factory('Hook_media_rendering_'.$hook);
			$hooks[$hook]=$ob->get_type_label();
		}
		asort($hooks);
		foreach ($hooks as $option_val=>$option_label)
		{
			$list->attach(form_input_list_entry($option_val,($option_val==post_param('type',is_image($file)?'image_websafe':'')),$option_label));
		}
		$fields->attach(form_input_list(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_type'),$_description,'type',$list,NULL,false,false));

		$fields->attach(form_input_tick(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_framed'),do_lang_tempcode('COMCODE_TAG_attachment_PARAM_framed'),'framed',post_param_integer('framed',0)==1));

		$fields->attach(form_input_integer(do_lang_tempcode('WIDTH'),do_lang_tempcode('COMCODE_TAG_attachment_PARAM_width'),'width',post_param_integer('width',NULL),false));

		$fields->attach(form_input_integer(do_lang_tempcode('HEIGHT'),do_lang_tempcode('COMCODE_TAG_attachment_PARAM_height'),'height',post_param_integer('height',NULL),false));

		/*$_description=do_lang('COMCODE_TAG_attachment_PARAM_align');
		if (substr($_description,0,strlen($adv)+1)==$adv) $_description=substr($_description,0,strlen($adv)+1);
		$list=new ocp_tempcode();
		foreach (explode('|',$_description) as $option)
		{
			list($option_val,$option_label)=explode('=',$option,2);
			$list->attach(form_input_list_entry($option_val,($option_val==post_param('align','')),$option_label));
		}
		$fields->attach(form_input_list(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_align'),'','align',$list,NULL,false,false));*/

		$_description=do_lang('COMCODE_TAG_attachment_PARAM_float');
		if (substr($_description,0,strlen($adv)+1)==$adv) $_description=substr($_description,0,strlen($adv)+1);
		$list=new ocp_tempcode();
		foreach (explode('|',$_description) as $option)
		{
			list($option_val,$option_label)=explode('=',$option,2);
			$list->attach(form_input_list_entry($option_val,($option_val==post_param('float','')),$option_label));
		}
		$fields->attach(form_input_list(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_float'),'','float',$list,NULL,false,false));

		$_description=do_lang('COMCODE_TAG_attachment_PARAM_thumb');
		if (substr($_description,0,strlen($adv)+1)==$adv) $_description=substr($_description,0,strlen($adv)+1);
		$_description=preg_replace('#\s*'.do_lang('BLOCK_IND_DEFAULT').': ["\']([^"]*)["\'](?-U)\.?(?U)#Ui','',$_description);
		$thumb_ticked=true;
		if (strtoupper(ocp_srv('REQUEST_METHOD'))=='POST') $thumb_ticked=(post_param_integer('thumb',0)==1);
		$fields->attach(form_input_tick(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_thumb'),ucfirst(substr($_description,12)),'thumb',$thumb_ticked));

		$_description=do_lang('COMCODE_TAG_attachment_PARAM_thumb_url');
		if (substr($_description,0,strlen($adv)+1)==$adv) $_description=substr($_description,0,strlen($adv)+1);
		$fields->attach(form_input_line_comcode(do_lang_tempcode('COMCODE_TAG_attachment_NAME_OF_PARAM_thumb_url'),$_description,'thumb_url',post_param('thumb_url',NULL),false));

		$form=do_template('FORM',array(
			'FIELDS'=>$fields,
			'HIDDEN'=>'',
			'TEXT'=>'',
			'URL'=>get_self_url(),
			'SUBMIT_NAME'=>do_lang_tempcode('GENERATE_COMCODE'),
			'SUBMIT_ICON'=>'buttons__proceed',
			'TARGET'=>'_self',
		));

		$image_sizes=mixed();
		if (is_image($file))
		{
			$size=@getimagesize($path);
			if ($size!==false)
			{
				$ratio=floatval($size[0])/floatval($size[1]);

				$_image_sizes=array();
				if (intval(get_option('thumb_width'))<$size[0])
					$_image_sizes[intval(get_option('thumb_width'))]='FILEDUMP_IMAGE_URLS_SMALL';
				if (730<$size[0])
					$_image_sizes[730]='FILEDUMP_IMAGE_URLS_MEDIUM';
				$_image_sizes[$size[0]]='FILEDUMP_IMAGE_URLS_LARGE';

				$image_sizes=array();
				foreach ($_image_sizes as $width=>$lng_str)
				{
					$size_url=$url;
					if ($width!=$size[0])
					{
						$size_url=symbol_tempcode('THUMBNAIL',array($url,strval($width)));
					}

					$height=intval(floatval($width)*$ratio);
					$image_sizes[]=array(
						'LABEL'=>do_lang_tempcode($lng_str,escape_html(strval($width)),escape_html(strval($height))),
						'SIZE_URL'=>$size_url,
						'SIZE_WIDTH'=>strval($width),
						'SIZE_HEIGHT'=>strval($height),
					);
				}
			}
		}

		return do_template('FILEDUMP_EMBED_SCREEN',array(
			'TITLE'=>$this->title,
			'FORM'=>$form,
			'GENERATED'=>$generated,
			'RENDERED'=>$rendered,
			'URL'=>$url,
			'IMAGE_SIZES'=>$image_sizes,
		));
	}

	/**
	 * The actualiser for handling mass actions.
	 *
	 * @return tempcode	The UI.
	 */
	function do_mass()
	{
		$action=post_param('action');
		switch ($action)
		{
			default:
				$target=$action;
				if ($target=='') warn_exit(do_lang_tempcode('SELECT_AN_ACTION'));

				$action='move';
				break;
		}

		$place=filter_naughty(get_param('place'));

		if ($action!='edit')
		{
			$files=array();
			foreach (array_keys($_POST) as $key)
			{
				if (preg_match('#^select_\d+$#',$key)!=0)
				{
					$files[]=post_param($key);
				}
			}
		} else
		{
			$files=array();
			$descriptions=array();
			foreach (array_keys($_POST) as $key)
			{
				$matches=array();
				if (preg_match('#^description_file_(\d+)$#',$key,$matches)!=0)
				{
					$file=post_param('description_file_'.$matches[1]);
					$files[]=$file;
					$descriptions[$file]=post_param('description_value_'.$matches[1],'');
				}
			}
		}
		if (count($files)==0) warn_exit(do_lang_tempcode('NOTHING_SELECTED'));

		// Confirm
		if ((post_param_integer('confirmed',0)!=1) && ($action!='edit'/*edit too trivial/specific to need a confirm*/))
		{
			$url=get_self_url();

			switch ($action)
			{
				case 'delete':
					$text=do_lang_tempcode('CONFIRM_DELETE',implode(', ',$files));
					break;

				case 'move':
					$text=do_lang_tempcode('CONFIRM_MOVE',implode(', ',$files),$target);
					break;
			}

			$hidden=build_keep_post_fields();
			$hidden->attach(form_input_hidden('confirmed','1'));

			return do_template('CONFIRM_SCREEN',array('_GUID'=>'19503cf5dc795b9c85d26702b79e3202','TITLE'=>$this->title,'FIELDS'=>$hidden,'PREVIEW'=>$text,'URL'=>$url));
		}

		// Perform action(s)
		foreach ($files as $file)
		{
			$owner=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','the_member',array('name'=>$file,'path'=>$place));
			if (((!is_null($owner)) && ($owner==get_member())) || (has_privilege(get_member(),'delete_anything_filedump')))
			{
				$is_directory=is_dir(get_custom_file_base().'/uploads/filedump'.$place.$file);
				$path=get_custom_file_base().'/uploads/filedump'.$place.$file;

				switch ($action)
				{
					case 'edit':
						$description=$descriptions[$file];
						$test=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','description',array('name'=>$file,'path'=>$place));
						if (!is_null($test))
						{
							lang_remap($test,$description);
						} else
						{
							$GLOBALS['SITE_DB']->query_insert('filedump',array('name'=>$file,'path'=>$place,'the_member'=>get_member(),'description'=>insert_lang_comcode($description,3)));
						}
						break;

					case 'delete':
						$test=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','description',array('name'=>$file,'path'=>$place));
						if (!is_null($test)) delete_lang($test);

						if ($is_directory)
						{
							deldir_contents($path);
							@rmdir($path) OR warn_exit(do_lang_tempcode('FOLDER_DELETE_ERROR'));

							log_it('FILEDUMP_DELETE_FOLDER',$file,$place);
						} else
						{
							@unlink($path) OR intelligent_write_error($path);

							log_it('FILEDUMP_DELETE_FILE',$file,$place);
						}
						sync_file('uploads/filedump'.$place.$file);

						break;

					case 'move':
						$path_target=get_custom_file_base().'/uploads/filedump'.$target.$file;
						rename($path,$path_target) OR intelligent_write_error($path);
						sync_file('uploads/filedump'.$path_target);

						$test=$GLOBALS['SITE_DB']->query_update('filedump',array('path'=>$target),array('name'=>$file,'path'=>$place),'',1);

						log_it('FILEDUMP_MOVE',$place.$file,$target.$file);

						break;
				}
			} else
			{
				access_denied('I_ERROR');
			}
		}

		$return_url=build_url(array('page'=>'_SELF','type'=>'misc','place'=>$place),'_SELF');

		return redirect_screen($this->title,$return_url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser for adding a folder.
	 *
	 * @return tempcode	The UI.
	 */
	function do_add_folder()
	{
		if (!has_privilege(get_member(),'upload_filedump')) access_denied('I_ERROR');

		$name=filter_naughty(post_param('name'));
		$place=filter_naughty(post_param('place'));

		if (file_exists(get_custom_file_base().'/uploads/filedump'.$place.$name))
			warn_exit(do_lang_tempcode('FOLDER_OVERWRITE_ERROR'));

		$path=get_custom_file_base().'/uploads/filedump'.$place.$name;
		@mkdir($path,0777) OR warn_exit(do_lang_tempcode('WRITE_ERROR_DIRECTORY',escape_html($place),escape_html(dirname($place))));
		fix_permissions($path,0777);
		sync_file($path);

		$return_url=build_url(array('page'=>'_SELF','type'=>'misc','place'=>$place),'_SELF');

		// Add description
		$test=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','description',array('name'=>$name,'path'=>$place));
		if (!is_null($test))
		{
			delete_lang($test);
			$GLOBALS['SITE_DB']->query_delete('filedump',array('name'=>$name,'path'=>$place),'',1);
		}
		$description=post_param('description','');
		$GLOBALS['SITE_DB']->query_insert('filedump',array('name'=>$name,'path'=>$place,'the_member'=>get_member(),'description'=>insert_lang_comcode($description,3)));

		log_it('FILEDUMP_CREATE_FOLDER',$name,$place);

		return redirect_screen($this->title,$return_url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * The actualiser for uploading a file.
	 *
	 * @return tempcode	The UI.
	 */
	function do_upload()
	{
		if (!has_privilege(get_member(),'upload_filedump')) access_denied('I_ERROR');

		if (function_exists('set_time_limit')) @set_time_limit(0); // Slowly uploading a file can trigger time limit, on some servers

		$place=filter_naughty(post_param('place'));

		require_code('uploads');
		is_swf_upload(true);

		$new_files=array();

		foreach ($_FILES as $file)
		{
			// Error?
			if ((!is_swf_upload()) && (!is_uploaded_file($file['tmp_name'])))
			{
				$max_size=get_max_file_size();
				if (($file['error']==1) || ($file['error']==2))
					warn_exit(do_lang_tempcode('FILE_TOO_BIG',integer_format($max_size)));
				elseif ((isset($file)) && (($file['error']==3) || ($file['error']==6) || ($file['error']==7)))
					warn_exit(do_lang_tempcode('ERROR_UPLOADING_'.strval($file['error'])));
				else warn_exit(do_lang_tempcode('ERROR_UPLOADING'));
			}

			$filename=$file['name'];
			if (get_magic_quotes_gpc()) $filename=stripslashes($filename);

			// Security
			if ((!has_privilege(get_member(),'upload_anything_filedump')) || (get_file_base()!=get_custom_file_base()/*myocp*/))
				check_extension($filename);
			// Don't allow double file extensions, huge security risk with Apache
			$filename=str_replace('.','-',basename($filename,'.'.get_file_extension($filename))).'.'.get_file_extension($filename);

			// Too big?
			$max_size=get_max_file_size();
			if ($file['size']>$max_size)
			{
				attach_message(do_lang_tempcode('FILE_TOO_BIG',integer_format(intval($max_size))),'warn');
				continue;
			}

			// Conflict?
			if (file_exists(get_custom_file_base().'/uploads/filedump'.$place.$filename))
			{
				attach_message(do_lang_tempcode('OVERWRITE_ERROR'),'warn');
				continue;
			}

			// Save in file
			$full=get_custom_file_base().'/uploads/filedump'.$place.$filename;
			if (is_swf_upload())
			{
				@rename($file['tmp_name'],$full) OR warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($filename),escape_html('uploads/filedump'.$place)));
			} else
			{
				@move_uploaded_file($file['tmp_name'],$full) OR warn_exit(do_lang_tempcode('FILE_MOVE_ERROR',escape_html($filename),escape_html('uploads/filedump'.$place)));
			}
			fix_permissions($full);
			sync_file($full);
			$new_files[]=$filename;

			// Add description
			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('filedump','description',array('name'=>$filename,'path'=>$place));
			if (!is_null($test))
			{
				delete_lang($test);
				$GLOBALS['SITE_DB']->query_delete('filedump',array('name'=>$filename,'path'=>$place),'',1);
			}
			$description=post_param('description','');
			$GLOBALS['SITE_DB']->query_insert('filedump',array('name'=>$filename,'path'=>$place,'the_member'=>get_member(),'description'=>insert_lang_comcode($description,3)));

			// Logging etc
			require_code('notifications');
			$subject=do_lang('FILEDUMP_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$filename,$place);
			$mail=do_lang('FILEDUMP_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($filename),array(comcode_escape($place),comcode_escape($description)));
			dispatch_notification('filedump',$place,$subject,$mail);
			log_it('FILEDUMP_UPLOAD',$filename,$place);
			require_code('users2');
			if (has_actual_page_access(get_modal_user(),get_page_name(),get_zone_name()))
			{
				require_code('activities');
				syndicate_described_activity('filedump:ACTIVITY_FILEDUMP_UPLOAD',$place.'/'.$file,'','','','','','filedump');
			}
		}

		// Done
		$url_map=array('page'=>'_SELF','place'=>$place);
		if (count($new_files)==1)
		{
			$url_map['filename']=$new_files[0];
		}
		$return_url=build_url($url_map,'_SELF');
		return redirect_screen($this->title,$return_url,do_lang_tempcode('SUCCESS'));
	}

}
