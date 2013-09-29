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
 * @package		core
 */

/**
 * Standard code module initialisation function.
 */
function init__tempcode_compiler()
{
	define('PARSE_NO_MANS_LAND',0);
	define('PARSE_DIRECTIVE',1);
	define('PARSE_SYMBOL',2);
	define('PARSE_LANGUAGE_REFERENCE',3);
	define('PARSE_PARAMETER',4);
	define('PARSE_DIRECTIVE_INNER',5);

	global $DIRECTIVES_NEEDING_VARS;
	$DIRECTIVES_NEEDING_VARS=array('IF_PASSED_AND_TRUE'=>1,'IF_NON_PASSED_OR_FALSE'=>1,'PARAM_INFO'=>1,'IF_NOT_IN_ARRAY'=>1,'IF_IN_ARRAY'=>1,'IMPLODE'=>1,'COUNT'=>1,'IF_ARRAY_EMPTY'=>1,'IF_ARRAY_NON_EMPTY'=>1,'OF'=>1,'INCLUDE'=>1,'LOOP'=>1);

	// These are templates often used multiple times on a single screen. They are loaded as functions, rather than eval'd each time
	global $FUNC_STYLE_TPL;
	$FUNC_STYLE_TPL=defined('HIPHOP_PHP')?array():array('CSS_NEED','JAVASCRIPT_NEED','OCF_AUTO_TIME_ZONE_ENTRY','FORM_SCREEN_INPUT_LIST_ENTRY','FORM_SCREEN_INPUT_LINE','FORM_SCREEN_INPUT_PERMISSION','FORM_SCREEN_INPUT_PERMISSION_OVERRIDE','FORM_SCREEN_INPUT_RADIO_LIST_ENTRY','FORM_SCREEN_INPUT_HIDDEN','FORM_SCREEN_INPUT_TICK','FORM_SCREEN_INPUT_TEXT','FORM_SCREEN_FIELD','MENU_BRANCH','MENU_NODE','HYPERLINK','BREADCRUMB_SEPARATOR','RESULTS_TABLE_ENTRY','OCF_TOPIC_POST','POSTER','OCF_FORUM_TOPIC_ROW');

	// Work out what symbols may be compiled out
	global $COMPILABLE_SYMBOLS;
	$_compilable_symbols=array(
		'LANG',
		'THEME',
		'VERSION_NUMBER',
		'VERSION',
		'SITE_NAME',
		'CHARSET',
		'ADDON_INSTALLED',
		'CONFIG_OPTION',
		'VALUE_OPTION',
		'MOBILE',
		'COPYRIGHT',
		'BASE_URL',
		'BASE_URL_NOHTTP',
		'CUSTOM_BASE_URL',
		'CUSTOM_BASE_URL_NOHTTP',
		'BRAND_NAME',
		'BRAND_BASE_URL',
		'OCF',
		'VALID_FILE_TYPES',
		'COOKIE_PATH',
		'COOKIE_DOMAIN',
		'SESSION_COOKIE_NAME',
		'INLINE_STATS',
		'CURRENCY_SYMBOL',
		'DOMAIN',
		'STAFF_ADDRESS',
		'STAFF_ADDRESS_PURE',
		'SHOW_DOCS',
		'SITE_SCOPE',
		'IMG_WIDTH',
		'IMG_HEIGHT',
		'IMG_INLINE',
		'SSW',
		'MAILTO',
	);
	global $SITE_INFO;
	if ((isset($SITE_INFO['no_keep_params'])) && ($SITE_INFO['no_keep_params']=='1'))
	{
		$_compilable_symbols[]='KEEP';
		$_compilable_symbols[]='PAGE_LINK';
		$_compilable_symbols[]='FIND_SCRIPT';
	}
	if (!addon_installed('ssl'))
	{
		$_compilable_symbols[]='IMG';
	}
	if (get_option('detect_javascript')=='0')
	{
		$_compilable_symbols[]='JS_ON';
	}
	$COMPILABLE_SYMBOLS=array();
	foreach ($_compilable_symbols as $s)
	{
		$COMPILABLE_SYMBOLS['"'.$s.'"']=true;
	}
}

/**
 * Helper function or use getting line numbers.
 *
 * @param  array			Compiler tokens
 * @param  integer		How far we are through the token list
 * @return integer		The sum length of tokens passed
 */
function _length_so_far($bits,$i)
{
	$len=0;
	foreach ($bits as $_i=>$x)
	{
		if ($_i==$i) break;
		$len+=strlen($x);
	}
	return $len;
}

/**
 * Compile a template into a list of appendable outputs, for the closure-style Tempcode implementation.
 *
 * @param  string			The template file contents
 * @param  ID_TEXT		The name of the template
 * @param  ID_TEXT		The name of the theme
 * @param  ID_TEXT		The language it is for
 * @param  boolean		Whether to tolerate errors
 * @return array			A pair: array Compiled result structure, array preprocessable bits (special stuff needing attention that is referenced within the template)
 */
function compile_template($data,$template_name,$theme,$lang,$tolerate_errors=false)
{
	if (strpos($data,'{$,Parser hint: pure}')!==false)
	{
		return array(array('"'.php_addslashes(preg_replace('#\{\$,.*\}#U','/*no minify*/',$data)).'"'),array());
	}

	$data=preg_replace('#<\?php(.*)\?'.'>#sU','{+START,PHP}${1}{+END}',$data);

	global $COMPILABLE_SYMBOLS;

	require_code('lang');
	require_code('urls');
	$cl=fallback_lang();
	$bits=array_values(preg_split('#(?<!\\\\)(\{(?=[\dA-Z\$\+\!\_]+[\.`%\*=\;\#\-~\^\|\'&/@+]*))|((?<!\\\\)\,)|((?<!\\\\)\})#',$data,-1,PREG_SPLIT_DELIM_CAPTURE));  // One error mail showed on a server it had weird indexes, somehow. Hence the array_values call to reindex it
	$count=count($bits);
	$stack=array();
	$current_level_mode=PARSE_NO_MANS_LAND;
	$current_level_data=array();
	$current_level_params=array();
	$preprocessable_bits=array();
	$num_preprocessable_bits=0;
	$preprocessable_bits_stack=array();
	for ($i=0;$i<$count;$i++)
	{
		$next_token=$bits[$i];
		if ($next_token=='') continue;
		if (($i!=$count-1) && ($next_token=='{') && (preg_match('#^[\dA-Z\$\+\!\_]#',$bits[$i+1])==0))
		{
			$current_level_data[]='"{}"';
			continue;
		}

		switch ($next_token)
		{
			case '{':
				// Open a new level
				$stack[]=array($current_level_mode,$current_level_data,$current_level_params,NULL,NULL,NULL,count($preprocessable_bits));
				++$i;
				$next_token=isset($bits[$i])?$bits[$i]:NULL;
				if ($next_token===NULL)
				{
					if ($tolerate_errors) continue;
					warn_exit(do_lang_tempcode('ABRUPTED_DIRECTIVE_OR_BRACE',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
				}
				$current_level_data=array();
				switch (isset($next_token[0])?$next_token[0]:'')
				{
					case '$':
						$current_level_mode=PARSE_SYMBOL;
						$current_level_data[]='"'.php_addslashes(substr($next_token,1)).'"';
						break;
					case '+':
						$current_level_mode=PARSE_DIRECTIVE;
						$current_level_data[]='"'.php_addslashes(substr($next_token,1)).'"';
						break;
					case '!':
						$current_level_mode=PARSE_LANGUAGE_REFERENCE;
						$current_level_data[]='"'.php_addslashes(substr($next_token,1)).'"';
						break;
					default:
						$current_level_mode=PARSE_PARAMETER;
						$current_level_data[]='"'.php_addslashes($next_token).'"';
						break;
				}
				$current_level_params=array();
				break;

			case '}':
				if (($stack==array()) || ($current_level_mode==PARSE_DIRECTIVE_INNER))
				{
					$literal=php_addslashes($next_token);

					if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($literal);

					$current_level_data[]='"'.$literal.'"';
					break;
				}

				$opener_params=array_merge($current_level_params,array($current_level_data));
				$__first_param=array_shift($opener_params);
				if (count($__first_param)!=1)
				{
					warn_exit(do_lang_tempcode('COMPLEX_FIRST_PARAMETER'));
				}
				$_first_param=$__first_param[0];

				if (($bits[$i-1]=='') && (count($current_level_data)==0)) $current_level_data[]='""';

				// Return to the previous level
				$past_level_data=$current_level_data;
				$past_level_params=$current_level_params;
				$past_level_mode=$current_level_mode;
				if ($stack==array())
				{
					if (!$tolerate_errors)
					{
						warn_exit(do_lang_tempcode('TEMPCODE_TOO_MANY_CLOSES',escape_html($template_name),integer_format(1+_length_so_far($bits,$i))));
					}
				} else
				{
					list($current_level_mode,$current_level_data,$current_level_params,,,,$num_preprocessable_bits)=array_pop($stack);
				}

				// Handle the level we just closed
				$_escaped=str_split(preg_replace('#[^:\.`%\*=\;\#\-~\^\|\'&/@+]:?#','',$_first_param)); // :? is so that the ":" in lang strings does not get considered an escape
				$escaped=array();
				foreach ($_escaped as $e)
				{
					switch ($e)
					{
						case '`':
							$escaped[]=NULL_ESCAPED;
							break;
						case '%':
							$escaped[]=NAUGHTY_ESCAPED;
							break;
						case '*':
							$escaped[]=ENTITY_ESCAPED;
							break;
						case '=':
							$escaped[]=FORCIBLY_ENTITY_ESCAPED;
							break;
						case ';':
							$escaped[]=SQ_ESCAPED;
							break;
						case '#':
							$escaped[]=DQ_ESCAPED;
							break;
						case '~': // New lines disappear
							$escaped[]=NL_ESCAPED;
							break;
						case '^':
							$escaped[]=NL2_ESCAPED; // New lines go to \n
							break;
						case '|':
							$escaped[]=ID_ESCAPED;
							break;
						case '\'':
							$escaped[]=CSS_ESCAPED;
							break;
						case '&':
							$escaped[]=UL_ESCAPED;
							break;
						case '.':
							$escaped[]=UL2_ESCAPED;
							break;
						case '/':
							$escaped[]=JSHTML_ESCAPED;
							break;
						case '@':
							$escaped[]=CC_ESCAPED;
							break;
						case '+':
							$escaped[]=PURE_STRING; // A performance marker
							break;
						// This is used as a hint to not preprocess
						case '-':
						// NB: we're out of ASCII symbols now. We want to avoid []()<>" brackets, whitespace characters, and control codes, and others are used for Tempcode grammar or are valid identifier characters.
						//  Actually +/$/! can be used at the end (+ is already taken)
					}
				}
				$_opener_params='';
				foreach ($opener_params as $oi=>&$oparam)
				{
					if ($oparam==array())
					{
						$oparam=array('""');
						if (!isset($opener_params[$oi+1]))
						{
							unset($opener_params[$oi]);
							break;
						}
					}

					if ($_opener_params!='') $_opener_params.=',';
					$_opener_params.=implode('.',$oparam);
				}

				$escaping_symbols_from=array('`','%','*','=',';','#','-','~','^','|','\'','&','.','/','@','+');
				$escaping_symbols_to=array('','','','','','','','','','','','','','','','');
				$first_param=str_replace($escaping_symbols_from,$escaping_symbols_to,$_first_param);
				switch ($past_level_mode)
				{
					case PARSE_SYMBOL:
						$no_preprocess=in_array('-',$_escaped);
						if (!$no_preprocess)
						{
							switch ($first_param) // These need preprocessing
							{
								case '""':
									array_splice($preprocessable_bits,$num_preprocessable_bits); // Remove anything preprocessable marked inside the comment
									break;

								case '"REQUIRE_CSS"':
								case '"REQUIRE_JAVASCRIPT"':
								case '"JS_TEMPCODE"':
								case '"CSS_TEMPCODE"':
								case '"SET"':
								case '"BLOCK"':
								case '"LOAD_PAGE"':
								case '"LOAD_PANEL"':
									foreach ($stack as $level_test) // Make sure if it's a LOOP then we evaluate the parameters early, as these have extra bindings we don't know about
									{
										if (($level_test[3]==PARSE_DIRECTIVE) && (isset($level_test[5][1])) && (isset($level_test[5][1][0])) && ($level_test[5][1][0]=='"LOOP"')) // For a loop, we need to do full evaluation of symbol parameters as it may be bound to a loop variable
										{
											$eval=@eval('return array('.$_opener_params.');');
											if (is_array($eval))
											{
												$pp_bit=array(array(),TC_SYMBOL,str_replace('"','',$first_param),$eval);
												$preprocessable_bits[]=$pp_bit;
											}
											break 2;
										}
									}

									$symbol_params=array();
									foreach ($opener_params as $param)
									{
										$myfunc='tcpfunc_'.fast_uniqid();
										$funcdef=build_closure_function($myfunc,$param);
										$symbol_params[]=new ocp_tempcode(array(array($myfunc=>$funcdef),array(array(array($myfunc,array(/* Is currently unbound */),TC_KNOWN,'',''))))); // Parameters will be bound in later.
									}

									$pp_bit=array(array(),TC_SYMBOL,str_replace('"','',$first_param),$symbol_params);

									$preprocessable_bits[]=$pp_bit;
									break;
							}
						}

						if (($first_param=='"IMG"') && (strpos($_opener_params,',')===false)) // Needed to ensure correct binding
						{
							$_opener_params.=',"0","'.php_addslashes($theme).'"';
						}

						if ($first_param=='"?"') // Optimise out static ternary
						{
							if (implode('.',$opener_params[0])=='"1"')
							{
								if (isset($opener_params[1]))
									$current_level_data[]=implode('.',$opener_params[1]);
								break;
							}
							if (implode('.',$opener_params[0])=='"0"')
							{
								if (isset($opener_params[2]))
									$current_level_data[]=implode('.',$opener_params[2]);
								break;
							}
						}

						if ($first_param=='""') // Optimise out comments
						{
							break;
						}

						// Optimise simple PHP-compatible operators
						foreach (array('EQ'=>'==','NEQ'=>'!=') as $symbol_op=>$php_op)
						{
							if (($first_param=='"'.$symbol_op.'"') && (count($opener_params)==2))
							{
								$current_level_data[]='((('.implode('.',$opener_params[0]).')'.$php_op.'('.implode('.',$opener_params[1]).'))?"1":"0")';
								break 2;
							}
						}
						foreach (array('AND'=>'&&','OR'=>'||') as $symbol_op=>$php_op)
						{
							if (($first_param=='"'.$symbol_op.'"') && (count($opener_params)==2))
							{
								$current_level_data[]='((('.implode('.',$opener_params[0]).')=="1")'.$php_op.'('.implode('.',$opener_params[1]).'=="1")?"1":"0")';
								break 2;
							}
						}
						if (($first_param=='"?"') && (count($opener_params)==3))
						{
							$current_level_data[]='((('.implode('.',$opener_params[0]).')=="1")?('.implode('.',$opener_params[1]).'):('.implode('.',$opener_params[2]).'))';
							break 2;
						}

						// Okay, a fully dynamic symbol
						$name=preg_replace('#(^")|("$)#','',$first_param);
						if ($name=='?') $name='TERNARY';
						if (function_exists('ecv_'.$name))
						{
							$new_line='ecv_'.$name.'($cl,array('.implode(',',$escaped).'),array('.$_opener_params.'))';
						} else
						{
							$new_line='ecv($cl,array('.implode(',',$escaped).'),'.strval(TC_SYMBOL).','.$first_param.',array('.$_opener_params.'))';
						}
						if ((isset($COMPILABLE_SYMBOLS[$first_param])) && (preg_match('#^[^\(\)]*$#',$_opener_params)!=0)) // Can optimise out?
						{
							$new_line='"'.php_addslashes(eval('return '.$new_line.';')).'"';
						}
						$current_level_data[]=$new_line;
						break;

					case PARSE_LANGUAGE_REFERENCE:
						$new_line='ecv($cl,array('.implode(',',$escaped).'),'.strval(TC_LANGUAGE_REFERENCE).','.$first_param.',array('.$_opener_params.'))';
						if (($_opener_params=='') && ($escaped==array())) // Optimise it out for simple case?
						{
							$looked_up=do_lang(eval('return '.$first_param.';'),NULL,NULL,NULL,$lang,false);
							if ($looked_up!==NULL)
							{
								if (apply_tempcode_escaping($escaped,$looked_up)==$looked_up)
									$new_line='"'.php_addslashes($looked_up).'"';
							}
						}
						$current_level_data[]=$new_line;
						break;
	
					case PARSE_PARAMETER:
						$parameter=str_replace('"','',str_replace("'",'',$first_param));
						$parameter=preg_replace('#[^\w\_\d]#','',$parameter); // security to stop PHP injection
						if ($escaped==array(PURE_STRING))
						{
							$current_level_data[]='$bound_'.php_addslashes($parameter);
						} else
						{
							$temp='otp(isset($bound_'.php_addslashes($parameter).')?$bound_'.php_addslashes($parameter).':NULL';
							if (get_value('shortened_tempcode')!=='1')
								$temp.=',"'.php_addslashes($parameter.'/'.$template_name).'"';
							$temp.=')';

							if ($escaped==array())
							{
								$current_level_data[]=$temp;
							} else
							{
								$s_escaped='';
								foreach ($escaped as $esc)
								{
									if ($s_escaped!='') $s_escaped.=',';
									$s_escaped.=strval($esc);
								}
								if (($s_escaped==strval(ENTITY_ESCAPED)) && (!$GLOBALS['XSS_DETECT']))
								{
									$current_level_data[]='(empty($bound_'.$parameter.'->pure_lang)?str_replace($GLOBALS[\'HTML_ESCAPE_1_STRREP\'],$GLOBALS[\'HTML_ESCAPE_2\'],'.$temp.'):'.$temp.')';
								} else
								{
									if ($s_escaped==strval(ENTITY_ESCAPED))
									{
										$current_level_data[]='(empty($bound_'.$parameter.'->pure_lang)?apply_tempcode_escaping_inline(array('.$s_escaped.'),'.$temp.'):'.$temp.')';
									} else
									{
										$current_level_data[]='apply_tempcode_escaping_inline(array('.$s_escaped.'),'.$temp.')';
									}
								}
							}
						}
						break;
				}

				// Handle directive nesting
				if ($past_level_mode==PARSE_DIRECTIVE)
				{
					$eval=@eval('return '.$first_param.';');
					if (!is_string($eval)) $eval='';
					if ($eval=='START') // START
					{
						// Open a new directive level
						$stack[]=array($current_level_mode,$current_level_data,$current_level_params,$past_level_mode,$past_level_data,$past_level_params,count($preprocessable_bits));
						$current_level_data=array();
						$current_level_params=array();
						$current_level_mode=PARSE_DIRECTIVE_INNER;
						if ($opener_params==array(array('"NO_PREPROCESSING"')))
						{
							array_push($preprocessable_bits_stack,$preprocessable_bits);
						}
					} elseif ($eval=='END') // END
					{
						// Test that the top stack does represent a started directive, and close directive level
						$past_level_data=$current_level_data;
						if ($past_level_data==array()) $past_level_data=array('""');
						$past_level_params=$current_level_params;
						$past_level_mode=$current_level_mode;
						if ($stack==array())
						{
							if ($tolerate_errors) continue;
							warn_exit(do_lang_tempcode('TEMPCODE_TOO_MANY_CLOSES',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
						}
						list($current_level_mode,$current_level_data,$current_level_params,$directive_level_mode,$directive_level_data,$directive_level_params,$num_preprocessable_bits)=array_pop($stack);
						if (!is_array($directive_level_params))
						{
							if ($tolerate_errors) continue;
							warn_exit(do_lang_tempcode('UNCLOSED_DIRECTIVE_OR_BRACE',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
						}
						$directive_opener_params=array_merge($directive_level_params,array($directive_level_data));
						if (($directive_level_mode!=PARSE_DIRECTIVE) || ($directive_opener_params[0][0]!='"START"'))
						{
							if ($tolerate_errors) continue;
							warn_exit(do_lang_tempcode('TEMPCODE_TOO_MANY_CLOSES',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
						}

						// Handle directive
						if (count($directive_opener_params)==1)
						{
							if ($tolerate_errors) continue;
							warn_exit(do_lang_tempcode('NO_DIRECTIVE_TYPE',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
						}
						$directive_params='';
						$first_directive_param='""';
						if ($directive_opener_params[1]==array()) $directive_opener_params[1]=array('""');
						$count_directive_opener_params=count($directive_opener_params);
						for ($j=2;$j<$count_directive_opener_params;$j++)
						{
							if ($directive_opener_params[$j]==array()) $directive_opener_params[$j]=array('""');

							if ($directive_params!='') $directive_params.=',';
							$directive_params.=implode('.',$directive_opener_params[$j]);

							if ($j==2) $first_directive_param=implode('.',$directive_opener_params[$j]);
						}
						$eval=@eval('return '.implode('.',$directive_opener_params[1]).';');
						if (!is_string($eval)) $eval='';
						$directive_name=$eval;
						switch ($directive_name)
						{
							case 'INCLUDE':
							case 'FRACTIONAL_EDITABLE':
								$eval=@eval('return array('.$directive_params.');');
								if (is_array($eval))
								{
									$pp_bit=array(array(),TC_DIRECTIVE,str_replace('"','',$directive_name),$eval);
									$preprocessable_bits[]=$pp_bit;
								}
								break;
						}
						switch ($directive_name)
						{
							case 'COMMENT':
								break;

							case 'NO_PREPROCESSING':
								$current_level_data[]=implode('.',$past_level_data);
								$preprocessable_bits=array_pop($preprocessable_bits_stack);
								$num_preprocessable_bits=count($preprocessable_bits);
								break;

							case 'IF':
								// Optimise out static NOT expressions
								if (preg_match('#^ecv\(\$cl,array\(\),0,"NOT",array\("1"\)\)$#',$first_directive_param)!=0)
									$first_directive_param='"0"';
								if (preg_match('#^ecv\(\$cl,array\(\),0,"NOT",array\("0"\)\)$#',$first_directive_param)!=0)
									$first_directive_param='"1"';

								// Optimise out static boolean expressions
								if (($first_directive_param=='((("1")==("1"))?"1":"0")') || ($first_directive_param=='(("0"=="0")?"1":"0")'))
									$first_directive_param='"1"';
								elseif (($first_directive_param=='((("1")==("0"))?"1":"0")') || ($first_directive_param=='(("0"=="1")?"1":"0")'))
									$first_directive_param='"0"';

								// Optimise simple expressions to PHP
								$matches=array();
								if (preg_match('#^\((\(\([^()]+\)(==|!=)\([^()]+\))\)\?"1":"0"\)\)$#',$first_directive_param,$matches)!=0)
								{
									$current_level_data[]='('.$matches[1].'?('.implode('.',$past_level_data).'):\'\')';
									break;
								}

								// Optimise out static IFs
								if ($first_directive_param=='"0"')
								{
									break;
								}
								if ($first_directive_param=='"1"')
								{
									$current_level_data[]='('.implode('.',$past_level_data).')';
									break;
								}

								// Normal IF then (actually it's implemented as ternary un PHP)
								$current_level_data[]='(('.$first_directive_param.'=="1")?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_EMPTY':
								$current_level_data[]='(('.$first_directive_param.'==\'\')?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_NON_EMPTY':
								$current_level_data[]='(('.$first_directive_param.'!=\'\')?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_PASSED':
								$eval=@eval('return '.$first_directive_param.';');
								if (!is_string($eval)) $eval='';
								$current_level_data[]='(isset($bound_'.preg_replace('#[^\w\d\_]#','',$eval).')?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_NON_PASSED':
								$eval=@eval('return '.$first_directive_param.';');
								if (!is_string($eval)) $eval='';
								$current_level_data[]='(!isset($bound_'.preg_replace('#[^\w\d\_]#','',$eval).')?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_PASSED_AND_TRUE':
								$eval=@eval('return '.$first_directive_param.';');
								if (!is_string($eval)) $eval='';
								$current_level_data[]='((isset($bound_'.preg_replace('#[^\w\d\_]#','',$eval).') && (otp($bound_'.preg_replace('#[^\w\d\_]#','',$eval).')=="1"))?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'IF_NON_PASSED_OR_FALSE':
								$eval=@eval('return '.$first_directive_param.';');
								if (!is_string($eval)) $eval='';
								$current_level_data[]='((!isset($bound_'.preg_replace('#[^\w\d\_]#','',$eval).') || (otp($bound_'.preg_replace('#[^\w\d\_]#','',$eval).')=="0"))?('.implode('.',$past_level_data).'):\'\')';
								break;

							case 'WHILE':
								$current_level_data[]='closure_while_loop(array($parameters,$cl),'."\n".'recall_named_function(\''.uniqid('',true).'\',\'$parameters,$cl\',"extract(\$parameters,EXTR_PREFIX_ALL,\'bound\'); return ('.php_addslashes($first_directive_param).')==\"1\";"),'."\n".'recall_named_function(\''.uniqid('',true).'\',\'$parameters,$cl\',"extract(\$parameters,EXTR_PREFIX_ALL,\'bound\'); return '.php_addslashes(implode('.',$past_level_data)).';"))';
								break;

							case 'LOOP':
								$current_level_data[]='closure_loop(array('.$directive_params.',\'vars\'=>$parameters),array($parameters,$cl),'."\n".'recall_named_function(\''.uniqid('',true).'\',\'$parameters,$cl\',"extract(\$parameters,EXTR_PREFIX_ALL,\'bound\'); return '.php_addslashes(implode('.',$past_level_data)).';"))';
								break;

							case 'PHP':
								$current_level_data[]='closure_eval('.implode('.',$past_level_data).',$parameters)';
								break;

							case 'INCLUDE':
								global $FILE_ARRAY;
								$eval=@eval('return '.$first_directive_param.';');
								if (!is_string($eval)) $eval='';
								if (($template_name==$eval) || ((!$GLOBALS['SEMI_DEV_MODE']) && (get_param('special_page_type','')=='')) && ($count_directive_opener_params==3) && ($past_level_data==array('""')) && (!isset($FILE_ARRAY))) // Simple case
								{
									$found=find_template_place($eval,'',$theme,'.tpl','templates',$template_name==$eval);
									$_theme=$found[0];
									if ($found[1]!==NULL)
									{
										$fullpath=get_custom_file_base().'/themes/'.$_theme.$found[1].$eval.'.tpl';
										if (!is_file($fullpath))
											$fullpath=get_file_base().'/themes/'.$_theme.$found[1].$eval.'.tpl';
										if (is_file($fullpath))
										{
											$tmp=fopen($fullpath,'rb');
											flock($tmp,LOCK_SH);
											$filecontents=file_get_contents($fullpath);
											flock($tmp,LOCK_UN);
											fclose($tmp);
										} else
										{
											$filecontents='';
										}
										list($_current_level_data,$_preprocessable_bits)=compile_template($filecontents,$eval,$theme,$lang);
										$current_level_data=array_merge($current_level_data,$_current_level_data);
										$preprocessable_bits=array_merge($preprocessable_bits,$_preprocessable_bits);
										break;
									}
								}

							default:
								if ($directive_params!='') $directive_params.=',';
								$directive_params.=implode('.',$past_level_data);
								if (isset($GLOBALS['DIRECTIVES_NEEDING_VARS'][$directive_name]))
								{
									$current_level_data[]='ecv($cl,array(),'.strval(TC_DIRECTIVE).','.implode('.',$directive_opener_params[1]).',array('.$directive_params.',\'vars\'=>$parameters))';
								} else
								{
									$current_level_data[]='ecv($cl,array(),'.strval(TC_DIRECTIVE).','.implode('.',$directive_opener_params[1]).',array('.$directive_params.'))';
								}
								break;
						}
					} else
					{
						$eval=@eval('return '.$first_param.';');
						if (!is_string($eval)) $eval='';
						$directive_name=$eval;
						if (isset($GLOBALS['DIRECTIVES_NEEDING_VARS'][$directive_name]))
						{
							$current_level_data[]='ecv($cl,array('.implode(',',$escaped).'),'.strval(TC_DIRECTIVE).','.$first_param.',array('.$_opener_params.',\'vars\'=>$parameters))';
						} else
						{
							$current_level_data[]='ecv($cl,array('.implode(',',$escaped).'),'.strval(TC_DIRECTIVE).','.$first_param.',array('.$_opener_params.'))';
						}
					}
				}
				break;

			case ',': // NB: Escaping via "\," was handled in our regexp split
				switch($current_level_mode)
				{
					case PARSE_NO_MANS_LAND:
					case PARSE_DIRECTIVE_INNER:
						$current_level_data[]='\',\'';
						break;
					default:
						$current_level_params[]=$current_level_data;
						$current_level_data=array();
						break;
				}
				break;

			default:
				$literal=php_addslashes(str_replace(array('\,','\}','\{'),array(',','}','{'),$next_token));
				if ($GLOBALS['XSS_DETECT']) ocp_mark_as_escaped($literal);

				$current_level_data[]='"'.$literal.'"';
				break;
		}
	}
	if ((!array_key_exists('LAX_COMCODE',$GLOBALS)) || ($GLOBALS['LAX_COMCODE']===false))
	{
		if ($stack!=array())
		{
			if (!$tolerate_errors)
				warn_exit(do_lang_tempcode('UNCLOSED_DIRECTIVE_OR_BRACE',escape_html($template_name),integer_format(1+substr_count(substr($data,0,_length_so_far($bits,$i)),"\n"))));
		}
	}

	if ($current_level_data==array('')) $current_level_data=array('""');

	return array($current_level_data,$preprocessable_bits);
}

/**
 * A template has not been structurally cached, so compile it and store in the cache.
 *
 * @param  ID_TEXT			The theme the template is in the context of
 * @param  PATH				The path to the template file
 * @param  ID_TEXT			The codename of the template (e.g. foo)
 * @param  ID_TEXT			The actual codename to use for the template (e.g. foo_mobile)
 * @param  LANGUAGE_NAME	The language the template is in the context of
 * @param  string				File type suffix of template file (e.g. .tpl)
 * @param  ?ID_TEXT			The theme to cache in (NULL: main theme)
 * @return tempcode			The compiled tempcode
 */
function _do_template($theme,$path,$codename,$_codename,$lang,$suffix,$theme_orig=NULL)
{
	if (is_null($theme_orig)) $theme_orig=$theme;

	if (is_null($GLOBALS['CURRENT_SHARE_USER']))
	{
		$base_dir=((($theme=='default') && (($suffix!='.css') || (strpos($path,'/css_custom')===false)))?get_file_base():get_custom_file_base()).'/themes/';
	} else
	{
		$base_dir=get_custom_file_base().'/themes/';
		if (!is_file($base_dir.$theme.$path.$codename.$suffix))
			$base_dir=get_file_base().'/themes/';
	}

	global $CACHE_TEMPLATES,$FILE_ARRAY,$IS_TEMPLATE_PREVIEW_OP_CACHE,$MEM_CACHE;

	if (isset($FILE_ARRAY))
	{
		$html=unixify_line_format(file_array_get('themes/'.$theme.$path.$codename.$suffix));
	} else
	{
		$_path=$base_dir.filter_naughty($theme.$path.$codename).$suffix;
		$tmp=fopen($_path,'rb');
		flock($tmp,LOCK_SH);
		$html=unixify_line_format(file_get_contents($_path));
		flock($tmp,LOCK_UN);
		fclose($tmp);
	}

	if (($GLOBALS['SEMI_DEV_MODE']) && (strpos($html,'.innerHTML')!==false) && (!running_script('install')) && (strpos($html,'Parser hint: .innerHTML okay')===false))
	{
		attach_message('Do not use the .innerHTML property in your Javascript because it will not work in true XHTML (when the browsers real XML parser is in action). Use ocPortal\'s global set_inner_html/get_inner_html functions.','warn');
	}

	// Strip off trailing final lines from single lines templates. Editors often put these in, and it causes annoying "visible space" issues
	if ((substr($html,-1,1)=="\n") && (substr_count($html,"\n")==1))
	{
		$html=substr($html,0,strlen($html)-1);
	}

	if ($IS_TEMPLATE_PREVIEW_OP_CACHE)
	{
		$test=post_param($codename,NULL);
		if (!is_null($test)) $html=post_param($test.'_new');
	}

	$result=template_to_tempcode($html,0,false,($suffix!='.tpl')?'':$codename,$theme_orig,$lang);
	if (($CACHE_TEMPLATES) && (!$IS_TEMPLATE_PREVIEW_OP_CACHE) && (($suffix=='.tpl') || ($codename=='no_cache')))
	{
		$path2=get_custom_file_base().'/themes/'.$theme_orig.'/templates_cached/'.filter_naughty($lang).'/';
		$myfile=@fopen($path2.filter_naughty($_codename).$suffix.'.tcp','ab');
		if ($myfile===false)
		{
			@mkdir(dirname($path2),0777);
			fix_permissions(dirname($path2),0777);
			sync_file(dirname($path2));
			if (@mkdir($path2,0777))
			{
				fix_permissions($path2,0777);
				sync_file($path2);
			} else
			{
				if ($codename=='SCREEN_TITLE') critical_error('PASSON',do_lang('WRITE_ERROR',escape_html($path2.filter_naughty($_codename).$suffix.'.tcp'))); // Bail out hard if would cause a loop
				intelligent_write_error($path2.filter_naughty($_codename).$suffix.'.tcp');
			}
		} else
		{
			flock($myfile,LOCK_EX);
			ftruncate($myfile,0);
			$data_to_write='<'.'?php'."\n".$result->to_assembly($lang)."\n".'?'.'>';
			if (fwrite($myfile,$data_to_write)>=strlen($data_to_write))
			{
				// Success
				flock($myfile,LOCK_UN);
				fclose($myfile);
				require_code('files');
				fix_permissions($path2.filter_naughty($_codename).$suffix.'.tcp');
			} else
			{
				// Failure
				flock($myfile,LOCK_UN);
				fclose($myfile);
				@unlink($path2.filter_naughty($_codename).$suffix.'.tcp'); // Can't leave this around, would cause problems
			}
		}
	}

	return $result;
}

/**
 * Convert template text into tempcode format.
 *
 * @param  string			The template text
 * @param  integer		The position we are looking at in the text
 * @param  boolean		Whether this text is infact a directive, about to be put in the context of a wider template
 * @param  ID_TEXT		The codename of the template (e.g. foo)
 * @param  ?ID_TEXT		The theme it is for (NULL: current theme)
 * @param  ?ID_TEXT		The language it is for (NULL: current language)
 * @param  boolean		Whether to tolerate errors
 * @return mixed			The converted/compiled template as tempcode, OR if a directive, encoded directive information
 */
function template_to_tempcode(/*&*/$text,$symbol_pos=0,$inside_directive=false,$codename='',$theme=NULL,$lang=NULL,$tolerate_errors=false)
{
	if (is_null($theme)) $theme=isset($GLOBALS['FORUM_DRIVER'])?$GLOBALS['FORUM_DRIVER']->get_theme():'default';
	if (is_null($lang)) $lang=user_lang();

	list($parts,$preprocessable_bits)=compile_template(substr($text,$symbol_pos),$codename,$theme,$lang,$tolerate_errors);

	if (count($parts)==0) return new ocp_tempcode();

	$myfunc='tcpfunc_'.(($codename=='')?fast_uniqid():$codename);

	$funcdef=build_closure_function($myfunc,$parts);

	$ret=new ocp_tempcode(array(array($myfunc=>$funcdef),array(array(array($myfunc,array(/* Is currently unbound */),TC_KNOWN,'',''))))); // Parameters will be bound in later.
	$ret->preprocessable_bits=array_merge($ret->preprocessable_bits,$preprocessable_bits);
	$ret->codename=$codename;
	return $ret;
}

/**
 * Build a closure function for a compiled template.
 *
 * @param  string			The function name
 * @param  array			An array of lines to be output, each one in PHP format
 * @return string			Finished PHP code
 */
function build_closure_function($myfunc,$parts)
{
	static $chr_10=NULL;
	if ($chr_10===NULL) $chr_10="\n";

	if ($parts==array()) $parts=array('""');
	$code='';
	foreach ($parts as $i=>$part)
	{
		if ($i!=0) $code.=','.$chr_10."\t";
		$code.=$part;
	}

	global $FUNC_STYLE_TPL;
	$func_style=false;
	foreach ($FUNC_STYLE_TPL as $s)
	{
		if (strpos($myfunc,$s)!==false) $func_style=true;
	}
	if ($func_style)
	{
		if (strpos($code,'$bound')===false)
		{
			$funcdef="\$tpl_funcs['$myfunc']=\$KEEP_TPL_FUNCS['$myfunc']=recall_named_function('".uniqid('',true)."','\$parameters,\$cl',\"echo ".php_addslashes($code).";\");\n";
		} else
		{
			$funcdef="\$tpl_funcs['$myfunc']=\$KEEP_TPL_FUNCS['$myfunc']=recall_named_function('".uniqid('',true)."','\$parameters,\$cl',\"extract(\\\$parameters,EXTR_PREFIX_ALL,'bound'); echo ".php_addslashes($code).";\");\n";
		}
	} else
	{
		$unset_code='';
		if (strpos($code,'isset($bound')!==false) // Horrible but efficient code needed to allow IF_PASSED/IF_NON_PASSED to keep working when templates are put adjacent to each other, where some have it, and don't. This is needed as eval does not set a scope block.
			$reset_code="eval(\\\$FULL_RESET_VAR_CODE);";
		elseif (strpos($code,'$bound')!==false)
			$reset_code="eval(\\\$RESET_VAR_CODE);";
		else
			$reset_code='';
		$funcdef="\$tpl_funcs['$myfunc']=\"$reset_code echo ".php_addslashes($code).";\";\n";
	}

	return $funcdef;
}

