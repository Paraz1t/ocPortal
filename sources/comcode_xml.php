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
 * @package		core_rich_media
 */

/**
 * Standard code module initialisation function.
 */
function init__comcode_xml()
{
	global $COMCODE_XML_PARAM_RENAMING,$COMCODE_XML_SWITCH_AROUND;

	$COMCODE_XML_PARAM_RENAMING=array(
		'list'=>'type',
		'flash'=>'dimensions',
		'indent'=>'offset',
		'menu'=>'codename',
		'title'=>'level',
		'box'=>'title',
		'img'=>'url',
		'tab'=>'title',
		'thumb'=>'storeAs',
		'email'=>'address',
		'reference'=>'refId',
		'upload'=>'filename',
		'page'=>'pageLink',
		'code'=>'language',
		'quote'=>'cite',
		'if_in_group'=>'groupList',
		'exp_ref'=>'caption',
		'align'=>'type',
		'post'=>'caption',
		'topic'=>'caption',
		'include'=>'zone',
		'ticker'=>'width',
		'section'=>'name',
		'section_controller'=>'children',
		'big_tab_controller'=>'children',
		'big_tab'=>'title',
	);

	$COMCODE_XML_SWITCH_AROUND=array(
		'img',
		'reference',
		'upload'
	);
}

/**
 * Converts Comcode XML to Tempcode.
 * @package		core_rich_media
 */
class comcode_xml_to_tempcode
{
	// Used during parsing
	var $namespace_stack,$tag_stack,$attribute_stack,$special_child_elements_stack;
	var $marker,$wml,$comcode,$source_member,$wrap_pos,$pass_id,$connection,$semiparse_mode,$is_all_semihtml,$structure_sweep,$check_only,$on_behalf_of_member;

	// The output
	var $tempcode;

	/**
	 * Convert the specified Comcode (text format) into a tempcode tree. You shouldn't output the tempcode tree to the browser, as it looks really horrible. If you are in a rare case where you need to output directly (not through templates), you should call the evaluate method on the tempcode object, to convert it into a string.
	 *
	 * @param  LONG_TEXT		The Comcode to convert
	 * @param  MEMBER			The member the evaluation is running as. This is a security issue, and you should only run as an administrator if you have considered where the Comcode came from carefully
	 * @param  ?integer		The position to conduct wordwrapping at (NULL: do not conduct word-wrapping)
	 * @param  ?string		A special identifier that can identify this resource in a sea of our resources of this class; usually this can be ignored, but may be used to provide a binding between Javascript in evaluated Comcode, and the surrounding environment (NULL: no explicit binding)
	 * @param  object			The database connection to use
	 * @param  boolean		Whether to parse so as to create something that would fit inside a semihtml tag. It means we generate HTML, with Comcode written into it where the tag could never be reverse-converted (e.g. a block).
	 * @param  boolean		Whether this is being pre-parsed, to pick up errors before row insertion.
	 * @param  boolean		Whether to treat this whole thing as being wrapped in semihtml, but apply normal security otherwise.
	 * @param  boolean		Whether we are only doing this parse to find the title structure
	 * @param  boolean		Whether to only check the Comcode. It's best to use the check_comcode function which will in turn use this parameter.
	 * @param  ?MEMBER		The member we are running on behalf of, with respect to how attachments are handled; we may use this members attachments that are already within this post, and our new attachments will be handed to this member (NULL: member evaluating)
	 * @return tempcode		The tempcode tree.
	 */
	function comcode_xml_to_tempcode($comcode,$source_member,$wrap_pos,$pass_id,$connection,$semiparse_mode,$preparse_mode,$is_all_semihtml,$structure_sweep,$check_only,$on_behalf_of_member=NULL)
	{
		if (is_null($pass_id)) $pass_id=strval(mt_rand(0,32000)); // This is a unique ID that refers to this specific piece of comcode

		$this->wml=false; // removed feature from ocPortal now
		$this->comcode=$comcode;
		$this->source_member=$source_member;
		$this->wrap_pos=$wrap_pos;
		$this->pass_id=$pass_id;
		$this->connection=$connection;
		$this->semiparse_mode=$semiparse_mode;
		$this->preparse_mode=$preparse_mode;
		$this->is_all_semihtml=$is_all_semihtml;
		$this->structure_sweep=$structure_sweep;
		$this->check_only=$check_only;
		$this->on_behalf_of_member=$on_behalf_of_member;

		global $VALID_COMCODE_TAGS,$IMPORTED_CUSTOM_COMCODE;
		if (!$IMPORTED_CUSTOM_COMCODE)
			_custom_comcode_import($connection);

		$this->namespace_stack=array();
		$this->tag_stack=array();
		$this->attribute_stack=array();
		$this->tempcode_stack=array(new ocp_tempcode());
		$this->special_child_elements_stack=array();

		// Create and setup our parser
		$xml_parser=function_exists('xml_parser_create_ns')?xml_parser_create_ns():xml_parser_create();
		if ($xml_parser===false)
		{
			return do_lang_tempcode('XML_PARSING_NOT_SUPPORTED'); // PHP5 default build on windows comes with this function disabled, so we need to be able to escape on error
		}
		xml_set_object($xml_parser,$this);
		@xml_parser_set_option($xml_parser,XML_OPTION_TARGET_ENCODING,get_charset());
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0);
		xml_set_element_handler($xml_parser,'startElement','endElement');
		xml_set_character_data_handler($xml_parser,'startText');
		if (function_exists('xml_set_start_namespace_decl_handler'))
		{
			xml_set_start_namespace_decl_handler($xml_parser,'startNamespace');
		}
		if (function_exists('xml_set_end_namespace_decl_handler'))
		{
			xml_set_end_namespace_decl_handler($xml_parser,'startNamespace');
		}

		$extra_data="<?xml version=\"1.0\" encoding=\"".escape_html(get_charset())."\" ?".">
<!DOCTYPE comcode_xml [
<!ENTITY nbsp \"&nbsp;\" >
]>
";
		if (xml_parse($xml_parser,$extra_data.$comcode,true)==0)
		{
			$error_str=xml_error_string(xml_get_error_code($xml_parser));
			warn_exit(escape_html($error_str)); // Parsing error
		} else
		{
			$this->tempcode=$this->tempcode_stack[0];
		}
		@xml_parser_free($xml_parser);
		return $this->tempcode;
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			N/A
	 * @param  ?URLPATH		The URI of the name space we are entering (NULL: not given)
	 */
	function startNameSpace($parser,$prefix,$uri=NULL)
	{
		array_push($this->namespace_stack,strtolower($uri));
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 */
	function endNameSpace($parser)
	{
		array_pop($this->namespace_stack);
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The name of the element found
	 * @param  array			Array of attributes of the element
	 */
	function startElement($parser,$name,$attributes)
	{
		array_push($this->tag_stack,$name);
		array_push($this->attribute_stack,$attributes);
		array_push($this->tempcode_stack,new ocp_tempcode());
		array_push($this->special_child_elements_stack,array());
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 */
	function endElement($parser)
	{
		$this_element=array_peek($this->tag_stack);
		if (strpos($this_element,':')!==false)
		{
			$bits=explode(':',$this_element);
			$this_element=$bits[1];
		}

		$child_tempcode=array_pop($this->tempcode_stack);
		$parent_special_child_elements=array_pop($this->special_child_elements_stack);
		list($tempcode,$aggregate)=$this->convertFinalisedElement($parser,$child_tempcode,$parent_special_child_elements);

		array_pop($this->attribute_stack);
		array_pop($this->tag_stack);

		if ($aggregate)
		{
			$parent_tempcode=array_pop($this->tempcode_stack);
			$parent_tempcode->attach($tempcode);
			array_push($this->tempcode_stack,$parent_tempcode);
		}

		if (!array_key_exists($this_element,$parent_special_child_elements)) $parent_special_child_elements[$this_element]=array();
		$parent_special_child_elements[$this_element][]=$tempcode;
		array_push($this->special_child_elements_stack,$parent_special_child_elements);
	}

	/**
	 * Standard PHP XML parser function.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  string			The text
	 */
	function startText($parser,$data)
	{
		$parent_tempcode=array_pop($this->tempcode_stack);
		$parent_tempcode->attach($data);
		array_push($this->tempcode_stack,$parent_tempcode);
	}

	/**
	 * Parse the complete text of the inside of the tag.
	 *
	 * @param  object			The parser object (same as 'this')
	 * @param  tempcode		Tempcode from child elements
	 * @param  array			A map containing arrays of tempcode from child elements indexed under element name
	 * @return array			A pair: The resultant tempcode. Whether the resultant tempcode is aggregated with neighbours.
	 */
	function convertFinalisedElement($parser,$child_tempcode,$special_child_elements)
	{
		$this->marker=xml_get_current_byte_index($parser);

		global $VALID_COMCODE_TAGS,$COMCODE_XML_PARAM_RENAMING,$COMCODE_XML_SWITCH_AROUND;
		$conflict_tags=array('br','hr','table','tr','th','td');
		$aux_tags=array('html_wrap','comcode','br','hr','table','tr','th','td','float','fh','fd','emoticon','member','wiki','list','list_element','concepts','show_concept','block','block_param','random','random_target','jumping','jumping_target','shocker','shocker_left','shocker_right','directive','language','symbol','directive_param','language_param','symbol_param','attachment','attachment_description','hide','hide_title','tooltip','tooltip_message');

		// Tidy up tag name
		$namespace=array_peek($this->namespace_stack);
		if (is_null($namespace)) $namespace='';
		$tag=array_peek($this->tag_stack);
		$colon_pos=strrpos($tag,':');
		if ($colon_pos!==false)
		{
			$namespace=substr($tag,0,$colon_pos);
			$tag=substr($tag,$colon_pos+1);
		}
		$tag=from_camelCase($tag);

		// Tidy up attributes
		$attributes=array_peek($this->attribute_stack);
		foreach ($COMCODE_XML_PARAM_RENAMING as $_tag=>$comcode_xml_name)
		{
			if (($tag==$_tag) && (isset($attributes[$comcode_xml_name])))
			{
				$attributes['param']=$attributes[$comcode_xml_name];
				unset($attributes[$comcode_xml_name]);
			}
		}
		foreach ($attributes as $key=>$val)
		{
			unset($attributes[$key]);
			$attributes[from_camelCase($key)]=$val;
		}

		// Do any switching around (Comcode has different embed vs attribute semantics to XML)
		foreach (array_merge($COMCODE_XML_SWITCH_AROUND,array('email')) as $_tag)
		{
			if ($tag==$_tag)
			{
				$x='param';
				if ($tag=='reference') $x='title';
				$temp=array_key_exists($x,$attributes)?$attributes[$x]:'';
				$attributes[$x]=$child_tempcode->evaluate();
				$child_tempcode=make_string_tempcode($temp);
			}
		}

		$tempcode=new ocp_tempcode();
		$aggregate=true;

		$is_html=false;
		if (in_array($tag,$conflict_tags)) // These could be either XHTML or Comcode, so we need to check the namespace
		{
			if (strpos($namespace,'html')!==false) // HTML
			{
				$is_html=true;
			}
		} elseif (strpos($namespace,'html')!==false)
		{
			if ((!isset($VALID_COMCODE_TAGS[$tag])) && (!in_array($tag,$aux_tags)))
			{
				$is_html=true;
			}
		}

		if ($is_html)
		{
			$tempcode->attach('<'.$tag);
			foreach ($attributes as $key=>$val)
			{
				$tempcode->attach(' '.$key.'="'.escape_html($val).'"');
			}
			$tempcode->attach('>');
			$tempcode->attach($child_tempcode);
			$tempcode->attach('</'.$tag.'>');
		} else
		{
			if (in_array($tag,$aux_tags))
			{
				switch ($tag)
				{
					case 'comcode':
						$tempcode=$child_tempcode;
						break;
					case 'html_wrap':
						$tempcode=$child_tempcode;
						break;
					case 'br':
						$tempcode=make_string_tempcode('<br />');
						break;
					case 'hr':
						$tempcode=do_template('COMCODE_TEXTCODE_LINE');
						break;
					case 'table':
						$tempcode=new ocp_tempcode();
						if (isset($attributes['summary']))
						{
							$tempcode->attach((($attributes['summary']=='')?('<p class="accessibility_hidden">'.escape_html($attributes['summary']).'</p>'):'').'<table">');
						} else
						{
							$tempcode->attach('<table>');
						}
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</table>');
						break;
					case 'tr':
						$tempcode->attach('<tr>');
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</tr>');
						break;
					case 'th':
						$tempcode->attach('<th style="vertical-align: top">');
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</th>');
						break;
					case 'td':
						$tempcode->attach('<td style="vertical-align: top">');
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</td>');
						break;
					case 'float':
						$tempcode->attach($child_tempcode);
						$tempcode->attach('<br style="clear: both" />');
						break;
					case 'fh':
						// Limited due to limitation of XML
						$i_dir_1='left';
						$i_dir_2='right';

						$tempcode->attach('<div style="padding-'.$i_dir_2.': 30px; float: '.$i_dir_1.'">');
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</th>');
						break;
					case 'fd':
						$tempcode->attach('<div class="inline">');
						$tempcode->attach($child_tempcode);
						$tempcode->attach('</div>');
						break;
					case 'emoticon':
						$smilies=$GLOBALS['FORUM_DRIVER']->find_emoticons(); // Sorted in descending length order

						require_code('comcode_text');

						$_child_tempcode=$child_tempcode->evaluate();
						foreach ($smilies as $code=>$imgcode)
						{
							if ($_child_tempcode==$code)
							{
								$eval=do_emoticon($imgcode);
								$tempcode=$eval;
								break;
							}
						}
						break;
					case 'directive':
						if (!isset($special_child_elements['directiveParam'])) $special_child_elements['directiveParam']=array();
						$tempcode=directive_tempcode($attributes['type'],$child_tempcode,$special_child_elements['directiveParam']);
						break;
					case 'language':
						if (!isset($special_child_elements['languageParam'])) $special_child_elements['languageParam']=array();
						$a=array_shift($special_child_elements['languageParam']);
						$b=array_shift($special_child_elements['languageParam']);
						$symbol_params=array();
						foreach ($special_child_elements['languageParam'] as $val)
							$symbol_params[]=$val->evaluate();
						$tempcode=do_lang_tempcode($child_tempcode->evaluate(),$a,$b,$symbol_params);
						break;
					case 'symbol':
						if (!isset($special_child_elements['symbolParam'])) $special_child_elements['symbolParam']=array();
						$symbol_params=array();
						foreach ($special_child_elements['symbolParam'] as $val)
							$symbol_params[]=$val->evaluate();
						$tempcode=symbol_tempcode($child_tempcode->evaluate(),$symbol_params);
						break;
					case 'hide_title':
					case 'attachment_description':
					case 'tooltip_message':
					case 'list_element':
					case 'show_concept':
					case 'block_param':
					case 'random_target':
					case 'jumping_target':
					case 'shocker_left':
					case 'shocker_right':
					case 'directive_param':
					case 'language_param':
					case 'symbol_param':
						$tempcode=make_string_tempcode(serialize(array($attributes,$child_tempcode)));
						$aggregate=false;
						break;
					case 'member':
						$username=$child_tempcode->evaluate();
						$username_info=((isset($attributes['boxed'])) && ($attributes['boxed']=='1'));
						$this_member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
						if (!is_null($this_member_id))
						{
							$poster_url=$GLOBALS['FORUM_DRIVER']->member_profile_url($this_member_id,false,true);
							if ((get_forum_type()=='ocf') && ($username_info))
							{
								require_lang('ocf');
								require_code('ocf_members2');
								$details=render_member_box($this_member_id,false,NULL,NULL,true,NULL,false);
								$tempcode=do_template('HYPERLINK_TOOLTIP',array('_GUID'=>'f7b65418616787b0f732c32486b63f4e','TOOLTIP'=>$details,'CAPTION'=>$username,'URL'=>$poster_url,'NEW_WINDOW'=>false));
							} else
							{
								$tempcode=hyperlink($poster_url,$username);
							}
						}
						break;
					case 'wiki':
						$wiki_page_name=$child_tempcode->evaluate();
						if (isset($attributes['anchor']))
						{
							$jump_to=$attributes['anchor'];
						} else $jump_to='';
						$wiki_page_url=build_url(array('page'=>'wiki','type'=>'misc','find'=>$wiki_page_name),get_module_zone('wiki'));
						if ($jump_to!='')
						{
							$wiki_page_url->attach('#'.$jump_to);
						}
						$tempcode=do_template('COMCODE_WIKI_LINK',array('_GUID'=>'770ac8741e9b0fc2697d1ee3d7ec3b38','URL'=>$wiki_page_url,'TEXT'=>$wiki_page_name));
						break;
					case 'list':
						if (!isset($special_child_elements['listElement'])) $special_child_elements['listElement']=array();
						$my_list=array();
						foreach ($special_child_elements['listElement'] as $val)
						{
							$bits=unserialize($val->evaluate());
							$my_list[]=$bits[1]->evaluate();
						}
						$tempcode=_do_tags_comcode($tag,$attributes,$my_list,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'concepts':
						if (!isset($special_child_elements['showConcept'])) $special_child_elements['showConcept']=array();
						$new_attributes=array();
						foreach ($special_child_elements['showConcept'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes['key_'.strval($i)]=isset($bits[0]['key'])?$bits[0]['key']:'';
							$new_attributes['val_'.strval($i)]=isset($bits[0]['key'])?$bits[0]['value']:'';
						}
						$tempcode=_do_tags_comcode($tag,$new_attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'block':
						if (!isset($special_child_elements['blockParam'])) $special_child_elements['blockParam']=array();
						$new_attributes=array();
						foreach ($special_child_elements['blockParam'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes[isset($bits[0]['key'])?$bits[0]['key']:'param']=isset($bits[0]['value'])?$bits[0]['value']:'';
						}
						$tempcode=_do_tags_comcode($tag,$new_attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'random':
						if (!isset($special_child_elements['randomTarget'])) $special_child_elements['randomTarget']=array();
						$new_attributes=array();
						foreach ($special_child_elements['randomTarget'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes[isset($bits[0]['pickIfAbove'])?$bits[0]['pickIfAbove']:'0']=$bits[1];
						}
						$tempcode=_do_tags_comcode($tag,$new_attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'jumping':
						if (!isset($special_child_elements['jumpingTarget'])) $special_child_elements['jumpingTarget']=array();
						$new_attributes=array();
						foreach ($special_child_elements['jumpingTarget'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes[strval($i)]=$bits[1];
							if (is_object($new_attributes[strval($i)])) $new_attributes[strval($i)]=$new_attributes[strval($i)]->evaluate();
						}
						$tempcode=_do_tags_comcode($tag,$new_attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'shocker':
						if (!isset($special_child_elements['shockerLeft'])) $special_child_elements['shockerLeft']=array();
						$new_attributes=array();
						foreach ($special_child_elements['shockerLeft'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes['left_'.strval($i)]=$bits[1];
							if (is_object($new_attributes['left_'.strval($i)])) $new_attributes['left_'.strval($i)]=$new_attributes['left_'.strval($i)]->evaluate();
						}
						foreach ($special_child_elements['shockerRight'] as $i=>$val)
						{
							$bits=unserialize($val->evaluate());
							$new_attributes['right_'.strval($i)]=$bits[1];
							if (is_object($new_attributes['right_'.strval($i)])) $new_attributes['right_'.strval($i)]=$new_attributes['right_'.strval($i)]->evaluate();
						}
						$tempcode=_do_tags_comcode($tag,$new_attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'attachment':
						$description='';
						if (isset($special_child_elements['attachmentDescription']))
						{
							$bits=unserialize($special_child_elements['attachmentDescription'][0]->evaluate());
							$title=is_object($bits[1])?$bits[1]->evaluate():$bits[1];
						}
						$tempcode=_do_tags_comcode($tag,array_merge($attributes,array('description'=>$description)),$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'hide':
						$title='';
						if (isset($special_child_elements['hideTitle']))
						{
							$bits=unserialize($special_child_elements['hideTitle'][0]->evaluate());
							$title=is_object($bits[1])?$bits[1]->evaluate():$bits[1];
						}
						$tempcode=_do_tags_comcode($tag,array_merge($attributes,array('param'=>$title)),$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
					case 'tooltip':
						$title='';
						if (isset($special_child_elements['tooltipMessage']))
						{
							$bits=unserialize($special_child_elements['tooltipMessage'][0]->evaluate());
							$title=is_object($bits[0])?$bits[0]->evaluate():$bits[0];
						}
						$tempcode=_do_tags_comcode($tag,array_merge($attributes,array('param'=>$title)),$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
						break;
				}
			}
			elseif (isset($VALID_COMCODE_TAGS[$tag]))
			{
				$tempcode=_do_tags_comcode($tag,$attributes,$child_tempcode,true,$this->pass_id,$this->marker,$this->source_member,true,$this->connection,$this->comcode,$this->wml,$this->structure_sweep,$this->semiparse_mode,NULL,$this->on_behalf_of_member);
			}
			// Else, it is simply unknown and hence skipped
		}

		return array($tempcode,$aggregate);
	}

}

/**
 * Convert a string from camelCase to underlined_case.
 *
 * @param  string	Input string
 * @return string	Output string
 */
function from_camelCase($value)
{
	$out='';
	$len=strlen($value);
	for ($i=0;$i<$len;$i++)
	{
		$char=$value[$i];
		if (strtolower($char)!=$char) $out.='_';
		$out.=strtolower($char);
	}
	return $out;
}

/**
 * Convert a string from underlined_case to camelCase.
 *
 * @param  string	Input string
 * @return string	Output string
 */
function to_camelCase($value)
{
	$under_pos=mixed();
	do
	{
		$under_pos=strpos($value,'_');
		if ($under_pos!==false)
		{
			$value=substr($value,0,$under_pos).ucfirst(substr($value,$under_pos+1));
		}
	}
	while ($under_pos!==false);
	return $value;
}


