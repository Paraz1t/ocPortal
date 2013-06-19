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
 * @package		core
 */

/**
 * Returns a list of keywords for all databases we might some day support.
 *
 * @return array		List of pairs
 */
function get_db_keywords()
{
	$words=array(
		'ABSOLUTE','ACCESS','ACCESSIBLE','ACTION','ACTIVE','ADA','ADD','ADMIN',
		'AFTER','ALIAS','ALL','ALLOCATE','ALLOW','ALPHANUMERIC','ALTER','ANALYSE',
		'ANALYZE','AND','ANY','APPLICATION','ARE','ARITH_OVERFLOW','ARRAY','AS',
		'ASC','ASCENDING','ASENSITIVE','ASSERTION','ASSISTANT','ASSOCIATE','ASUTIME','ASYMMETRIC',
		'ASYNC','AT','ATOMIC','AUDIT','AUTHORIZATION','AUTO','AUTODDL','AUTOINCREMENT',
		'AUX','AUXILIARY','AVG','BACKUP','BASED','BASENAME','BASE_NAME','BEFORE',
		'BEGIN','BETWEEN','BIGINT','BINARY','BIT','BIT_LENGTH','BLOB','BLOBEDIT',
		'BOOLEAN','BOTH','BOTTOM','BREADTH','BREAK','BROWSE','BUFFER','BUFFERPOOL',
		'BULK','BY','BYTE','CACHE','CALL','CALLED','CAPABILITY','CAPTURE',
		'CASCADE','CASCADED','CASE','CAST','CATALOG','CCSID','CHANGE','CHAR',
		'CHARACTER','CHARACTER_LENGTH','CHAR_CONVERT','CHAR_LENGTH','CHECK','CHECKPOINT','CHECK_POINT_LEN','CHECK_POINT_LENGTH',
		'CLOB','CLOSE','CLUSTER','CLUSTERED','COALESCE','COLLATE','COLLATION','COLLECTION',
		'COLLID','COLUMN','COLUMNS','COMMENT','COMMIT','COMMITTED','COMPACTDATABASE','COMPILETIME',
		'COMPLETION','COMPRESS','COMPUTE','COMPUTED','CONCAT','CONDITION','CONDITIONAL','CONFIRM',
		'CONFLICT','CONNECT','CONNECTION','CONSTRAINT','CONSTRAINTS','CONSTRUCTOR','CONTAINER','CONTAINING',
		'CONTAINS','CONTAINSTABLE','CONTINUE','CONTROLROW','CONVERT','CORRESPONDING','COUNT','COUNTER',
		'CREATE','CREATEDATABASE','CREATEFIELD','CREATEGROUP','CREATEINDEX','CREATEOBJECT','CREATEPROPERTY','CREATERELATION',
		'CREATETABLEDEF','CREATEUSER','CREATEWORKSPACE','CROSS','CSTRING','CUBE','CURRENCY','CURRENT',
		'CURRENTUSER','CURRENT_DATE','CURRENT_DEFAULT_TRANSFORM_GROUP','CURRENT_LC_CTYPE','CURRENT_PATH','CURRENT_ROLE','CURRENT_TIME','CURRENT_TIMESTAMP',
		'CURRENT_TRANSFORM_GROUP_FOR_TYPE','CURRENT_USER','CURSOR','CYCLE','DATA','DATABASE','DATABASES','DATA_PGS',
		'DATE','DATETIME','DAY',/*'DAYS',*/'DAY_HOUR','DAY_MICROSECOND','DAY_MINUTE','DAY_SECOND',
		'DB2SQL','DBCC','DBINFO','DBSPACE','DB_KEY','DEALLOCATE','DEBUG','DEC',
		'DECIMAL','DECLARE','DEFAULT','DEFERRABLE','DEFERRED','DELAYED','DELETE','DELETING',
		'DENY','DEPTH','DEREF','DESC','DESCENDING','DESCRIBE',/*'DESCRIPTION',*/'DESCRIPTOR',
		'DETERMINISTIC','DIAGNOSTICS','DICTIONARY','DISALLOW','DISCONNECT','DISK','DISPLAY','DISTINCT',
		'DISTINCTROW','DISTRIBUTED','DIV','DO','DOCUMENT','DOMAIN','DOUBLE','DROP',
		'DSNHATTR','DSSIZE','DUAL','DUMMY','DUMP','DYNAMIC','EACH','ECHO',
		'EDIT','EDITPROC','ELEMENT','ELSE','ELSEIF','ENCLOSED','ENCODING','ENCRYPTED',
		'ENCRYPTION','END','END-EXEC','ENDIF','ENDING','ENDTRAN','ENTRY_POINT','EQUALS',
		'EQV','ERASE','ERRLVL','ERROR','ERROREXIT','ESCAPE','ESCAPED','EVENT',
		'EXCEPT','EXCEPTION','EXCLUSIVE','EXEC','EXECUTE','EXISTING','EXISTS','EXIT',
		'EXPLAIN','EXTERN','EXTERNAL','EXTERNLOGIN','EXTRACT','FALSE','FENCED','FETCH',
		'FIELD','FIELDPROC','FIELDS','FILE','FILLCACHE','FILLFACTOR','FILTER','FINAL',
		'FIRST','FLOAT','FLOAT4','FLOAT8','FLOPPY','FOR','FORCE','FOREIGN',
		'FORM','FORMS','FORTRAN','FORWARD','FOUND','FREE','FREETEXT','FREETEXTTABLE',
		'FREEZE','FREE_IT','FROM','FULL','FULLTEXT','FUNCTION','GDSCODE','GENERAL',
		'GENERATED','GENERATOR','GEN_ID',/*'GET',*/'GETOBJECT','GETOPTION','GLOB','GLOBAL',
		'GO','GOTO','GOTOPAGE','GRANT','GROUP','GROUPING','GROUP_COMMIT_WAIT','GROUP_COMMIT_WAIT_TIME',
		'GUID','HANDLER','HAVING','HELP','HIGH_PRIORITY','HOLD','HOLDLOCK','HOUR',
		'HOURS','HOUR_MICROSECOND','HOUR_MINUTE','HOUR_SECOND','IDENTIFIED','IDENTITY','IDENTITYCOL','IDENTITY_INSERT',
		'IDLE','IEEEDOUBLE','IEEESINGLE','IF','IGNORE','ILIKE','IMMEDIATE','IMP',
		'IN','INACTIVE','INCLUDE','INCLUSIVE','INCREMENT','INDEX','INDEXES','INDEX_LPAREN',
		'INDICATOR','INFILE','INHERIT','INIT','INITIAL','INITIALLY','INNER','INOUT',
		'INPUT','INPUT_TYPE','INSENSITIVE','INSERT','INSERTING','INSERTTEXT','INSTALL','INSTEAD',
		'INT','INT1','INT2','INT3','INT4','INT8','INTEGER','INTEGER1',
		'INTEGER2','INTEGER4','INTEGRATED','INTERSECT','INTERVAL','INTO','IQ','IS',
		'ISNULL','ISOBID','ISOLATION','ISQL','ITERATE','JAR','JAVA','JOIN',
		'KEY','KEYS','KILL','LABEL',/*'LANGUAGE',*/'LARGE','LAST','LASTMODIFIED',
		'LATERAL','LC_CTYPE','LC_MESSAGES','LC_TYPE','LEADING','LEAVE','LEFT','LENGTH',
		'LESS','LEV','LEVEL','LIKE','LIMIT','LINEAR','LINENO','LINES',
		'LOAD','LOCAL','LOCALE','LOCALTIME','LOCALTIMESTAMP','LOCATOR','LOCATORS','LOCK',
		'LOCKMAX','LOCKSIZE','LOGFILE','LOGICAL','LOGICAL1','LOGIN','LOG_BUFFER_SIZE','LOG_BUF_SIZE',
		'LONG','LONGBINARY','LONGBLOB','LONGTEXT','LOOP','LOWER','LOW_PRIORITY','MACRO',
		'MAINTAINED','MANUAL','MAP','MATCH','MATERIALIZED','MAX','MAXEXTENTS','MAXIMUM',
		'MAXIMUM_SEGMENT','MAX_SEGMENT','MEDIUMBLOB','MEDIUMINT','MEDIUMTEXT','MEMBER','MEMBERSHIP','MEMO',
		'MERGE','MESSAGE','METHOD','MICROSECOND','MICROSECONDS','MIDDLEINT','MIN','MINIMUM',
		'MINUS','MINUTE','MINUTES','MINUTE_MICROSECOND','MINUTE_SECOND','MIRROR','MIRROREXIT','MLSLABEL',
		'MOD','MODE','MODIFIES','MODIFY','MODULE','MODULE_NAME','MONEY','MONTH',
		'MONTHS','MOVE','MULTISET',/*'NAME',*/'NAMES','NATIONAL','NATURAL','NCHAR',
		'NCLOB','NEW','NEWPASSWORD','NEXT','NEXTVAL','NO','NOAUDIT','NOAUTO',
		'NOCHECK','NOCOMPRESS','NOHOLDLOCK','NONCLUSTERED','NONE','NOT','NOTIFY','NOTNULL',
		'NOWAIT','NO_WRITE_TO_BINLOG','NULL','NULLIF','NULLS','NUMBER','NUMERIC','NUMERIC_TRUNCATION',
		'NUMPARTS','NUM_LOG_BUFFERS','NUM_LOG_BUFS','OBID','OBJECT','OCTET_LENGTH','OF','OFF',
		'OFFLINE','OFFSET','OFFSETS','OID','OLD','OLEOBJECT','ON','ONCE',
		'ONLINE','ONLY','OPEN','OPENDATASOURCE','OPENQUERY','OPENRECORDSET','OPENROWSET','OPENXML',
		'OPERATION','OPERATORS','OPTIMIZATION','OPTIMIZE','OPTION','OPTIONALLY','OPTIONS','OR',
		'ORDER','ORDINALITY','OTHERS','OUT','OUTER','OUTFILE','OUTPUT','OUTPUT_TYPE',
		'OVER','OVERFLOW','OVERLAPS','OWNERACCESS','PACKAGE','PAD','PADDED','PAGE',
		'PAGELENGTH',/*'PAGES',*/'PAGE_SIZE','PARAMETER','PARAMETERS','PART','PARTIAL','PARTITION',
		'PARTITIONED','PARTITIONING','PASCAL','PASSTHROUGH','PASSWORD',/*'PATH',*/'PCTFREE','PENDANT',
		'PERCENT','PERM','PERMANENT','PIECESIZE','PIPE','PIVOT','PLACING','PLAN',
		'POSITION','POST_EVENT','PRECISION','PREORDER','PREPARE','PRESERVE','PREVVAL','PRIMARY',
		'PRINT','PRIOR','PRIQTY','PRIVATE','PRIVILEGES','PROC','PROCEDURE','PROCESSEXIT',
		'PROGRAM','PROPERTY','PROTECTED','PSID','PUBLIC','PUBLICATION','PURGE','QUERIES',
		'QUERY','QUERYNO','QUIT','RAID0','RAISERROR','RANGE','RAW','RAW_PARTITIONS',
		'READ','READS','READTEXT','READ_ONLY','READ_WRITE','REAL','RECALC','RECONFIGURE',
		'RECORDSET','RECORD_VERSION','RECURSIVE','REF','REFERENCE','REFERENCES','REFERENCING','REFRESH',
		'REFRESHLINK','REGEXP','REGISTERDATABASE','RELATION','RELATIVE','RELEASE','REMOTE','REMOVE',
		'RENAME','REORGANIZE','REPAINT','REPAIRDATABASE','REPEAT','REPEATABLE','REPLACE','REPLICATION',
		'REPORT','REPORTS','REQUERY','REQUIRE','RESERV','RESERVED_PGS','RESERVING','RESIGNAL',
		'RESOURCE','RESTORE','RESTRICT','RESULT','RESULT_SET_LOCATOR','RETAIN','RETURN','RETURNING_VALUES',
		'RETURNS','REVOKE','RIGHT','RLIKE','ROLE','ROLLBACK','ROLLUP','ROUTINE',
		'ROW','ROWCNT','ROWCOUNT','ROWGUIDCOL','ROWID','ROWLABEL','ROWNUM','ROWS',
		'ROWSET','RULE','RUN','RUNTIME','SAVE','SAVEPOINT','SCHEMA','SCHEMAS',
		'SCOPE','SCRATCHPAD','SCREEN','SCROLL','SEARCH','SECOND','SECONDS','SECOND_MICROSECOND',
		'SECQTY',/*'SECTION',*/'SECURITY','SELECT','SENSITIVE','SEPARATOR','SEQUENCE','SERIALIZABLE',
		'SESSION','SESSION_USER','SET','SETFOCUS','SETOPTION','SETS','SETUSER','SHADOW',
		'SHARE','SHARED','SHELL','SHORT','SHOW','SHUTDOWN','SIGNAL','SIMILAR',
		'SIMPLE','SINGLE','SINGULAR','SIZE','SMALLINT','SNAPSHOT','SOME','SONAME',
		'SORT','SOURCE','SPACE','SPATIAL','SPECIFIC','SPECIFICTYPE','SQL','SQLCA',
		'SQLCODE','SQLERROR','SQLEXCEPTION','SQLSTATE','SQLWARNING','SQL_BIG_RESULT','SQL_CALC_FOUND_ROWS','SQL_SMALL_RESULT',
		'SSL','STABILITY','STANDARD','START','STARTING','STARTS','STATE','STATEMENT',
		'STATIC','STATISTICS','STAY','STDEV','STDEVP','STOGROUP','STOP','STORES',
		'STRAIGHT_JOIN','STRING','STRIPE','STRUCTURE','STYLE','SUBMULTISET','SUBPAGES','SUBSTRING',
		'SUBTRANS','SUBTRANSACTION','SUB_TYPE','SUCCESSFUL','SUM','SUMMARY','SUSPEND','SYB_IDENTITY',
		'SYB_RESTREE','SYMMETRIC','SYNCHRONIZE','SYNONYM','SYNTAX_ERROR','SYSDATE','SYSFUN','SYSIBM',
		'SYSPROC','SYSTEM','SYSTEM_USER','TABLE','TABLEDEF','TABLEDEFS','TABLEID','TABLES',
		'TABLESAMPLE','TABLESPACE','TAPE','TEMP','TEMPORARY','TERMINATED','TERMINATOR','TEST',
		'TEXT','TEXTSIZE','THEN','THERE','TIME','TIMESTAMP','TIMEZONE_HOUR','TIMEZONE_MINUTE',
		'TINYBLOB','TINYINT','TINYTEXT','TO','TOP','TRAILING','TRAN','TRANSACTION',
		'TRANSFORM','TRANSLATE','TRANSLATION','TREAT','TRIGGER','TRIM','TRUE','TRUNCATE',
		'TSEQUAL','TYPE','UID','UNBOUNDED','UNCOMMITTED','UNDER','UNDO','UNION',
		'UNIQUE','UNIQUEIDENTIFIER','UNKNOWN','UNLOCK','UNNEST','UNSIGNED','UNTIL','UPDATE',
		'UPDATETEXT','UPDATING','UPGRADE','UPPER','USAGE','USE','USED_PGS','USER',
		'USER_OPTION','USING','UTC_DATE','UTC_TIME','UTC_TIMESTAMP','VALIDATE','VALIDPROC','VALUE',
		'VALUES','VAR','VARBINARY','VARCHAR','VARCHAR2','VARCHARACTER','VARIABLE','VARIANT',
		'VARP','VARYING','VCAT','VERBOSE','VERSION','VIEW','VIRTUAL','VISIBLE',
		'VOLATILE','VOLUMES','WAIT','WAITFOR','WEEKDAY','WHEN','WHENEVER','WHERE',
		'WHILE','WINDOW','WITH','WITHIN','WITHOUT','WITH_CUBE','WITH_LPAREN','WITH_ROLLUP',
		'WLM','WORK','WORKSPACE','WRITE','WRITETEXT','X509','XMLELEMENT','XOR',
		'YEAR','YEARDAY','YEARS','YEAR_MONTH','YES','YESNO','ZEROFILL','ZONE','GET',
	);
	return $words;
}

/**
 * Returns a list of pairs, for which permissions are false by default for ordinary usergroups.
 *
 * @return array		List of pairs
 */
function get_false_permissions()
{
	return array(  array('GENERAL_SETTINGS','bypass_flood_control'),
						array('_COMCODE','allow_html'),
						array('GENERAL_SETTINGS','remove_page_split'),
						array('STAFF_ACTIONS','access_closed_site'),
						array('STAFF_ACTIONS','bypass_bandwidth_restriction'),
						array('_COMCODE','comcode_dangerous'),
						array('_COMCODE','comcode_nuisance'),
						array('STAFF_ACTIONS','see_php_errors'),
						array('STAFF_ACTIONS','see_stack_dump'),
						array('GENERAL_SETTINGS','bypass_word_filter'),
						array('STAFF_ACTIONS','view_profiling_modes'),
						array('STAFF_ACTIONS','access_overrun_site'),
						array('SUBMISSION','bypass_validation_highrange_content'),
						array('SUBMISSION','bypass_validation_midrange_content'),
						array('SUBMISSION','edit_highrange_content'),
						array('SUBMISSION','edit_midrange_content'),
						array('SUBMISSION','edit_lowrange_content'),
						array('SUBMISSION','edit_own_highrange_content'),
						array('SUBMISSION','edit_own_midrange_content'),
						array('SUBMISSION','delete_highrange_content'),
						array('SUBMISSION','delete_midrange_content'),
						array('SUBMISSION','delete_lowrange_content'),
						array('SUBMISSION','delete_own_highrange_content'),
						array('SUBMISSION','delete_own_midrange_content'),
						array('SUBMISSION','delete_own_lowrange_content'),
						array('SUBMISSION','can_submit_to_others_categories'),
						array('SUBMISSION','search_engine_links'),
						array('STAFF_ACTIONS','view_content_history'),
						array('STAFF_ACTIONS','restore_content_history'),
						array('STAFF_ACTIONS','delete_content_history'),
						array('SUBMISSION','submit_cat_highrange_content'),
						array('SUBMISSION','submit_cat_midrange_content'),
						array('SUBMISSION','submit_cat_lowrange_content'),
						array('SUBMISSION','edit_cat_highrange_content'),
						array('SUBMISSION','edit_cat_midrange_content'),
						array('SUBMISSION','edit_cat_lowrange_content'),
						array('SUBMISSION','delete_cat_highrange_content'),
						array('SUBMISSION','delete_cat_midrange_content'),
						array('SUBMISSION','delete_cat_lowrange_content'),
						array('SUBMISSION','edit_own_cat_highrange_content'),
						array('SUBMISSION','edit_own_cat_midrange_content'),
						array('SUBMISSION','edit_own_cat_lowrange_content'),
						array('SUBMISSION','delete_own_cat_highrange_content'),
						array('SUBMISSION','delete_own_cat_midrange_content'),
						array('SUBMISSION','delete_own_cat_lowrange_content'),
						array('SUBMISSION','mass_import'),

					);
}

/**
 * Check if a config option exists.
 *
 * @param  ID_TEXT		The name of the option
 * @return boolean		Whether it exists
 */
function config_option_exists($name)
{
	$test=get_option($name,true);
	return !is_null($test);
}

/**
 * Check if a privilege exists.
 *
 * @param  ID_TEXT		The name of the option
 * @return boolean		Whether it exists
 */
function permission_exists($name)
{
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('sp_list','the_name',array('the_name'=>$name));
	return !is_null($test);
}

/**
 * Add a configuration option into the database, and initialise it with a specified value.
 *
 * @param  ID_TEXT		The language code to the human name of the config option
 * @param  ID_TEXT		The codename for the config option
 * @param  ID_TEXT		The type of the config option
 * @set    float integer tick line text transline transtext list date forum category usergroup colour
 * @param  SHORT_TEXT	The PHP code to execute to get the default value for this option. Be careful not to make a get_option loop.
 * @param  ID_TEXT		The language code for the option category to store the option in
 * @param  ID_TEXT		The language code for the option group to store the option in
 * @param  BINARY			Whether the option is not settable when on a shared ocportal-hosting environment
 * @param  SHORT_TEXT	Extra data for the option
 */
function add_config_option($human_name,$name,$type,$eval,$category,$group,$shared_hosting_restricted=0,$data='')
{
	if (!in_array($type,array('float','integer','tick','line','text','transline','transtext','list','date','?forum','forum','category','usergroup','colour')))
		fatal_exit('Invalid config option type');

	$map=array('c_set'=>0,'config_value'=>'','the_name'=>$name,'human_name'=>$human_name,'the_type'=>$type,'eval'=>$eval,'the_page'=>$category,'section'=>$group,'explanation'=>'CONFIG_OPTION_'.$name,'shared_hosting_restricted'=>$shared_hosting_restricted,'c_data'=>$data);
	if ($GLOBALS['IN_MINIKERNEL_VERSION']==0)
	{
		$GLOBALS['SITE_DB']->query_insert('config',$map,false,true); // Allow failure in case the config option got auto-installed through searching (can happen if the option is referenced efore the module installs right)
	} else
	{
		$GLOBALS['SITE_DB']->query_insert('config',$map); // From installer we want to know if there are errors in our install cycle
	}
	if (function_exists('persistent_cache_delete')) persistent_cache_delete('OPTIONS');
	global $OPTIONS;
	if ($OPTIONS==array()) // Installer might not have loaded any yet
	{
		load_options();
	} else
	{
		$OPTIONS[$name]=$map;
		if (multi_lang())
		{
			unset($OPTIONS[$name]['config_value_translated']);
		}
	}
}

/**
 * Deletes a specified config option permanently from the database.
 *
 * @param  ID_TEXT		The codename of the config option
 */
function delete_config_option($name)
{
	$rows=$GLOBALS['SITE_DB']->query_select('config',array('*'),array('the_name'=>$name),'',1);
	if (array_key_exists(0,$rows))
	{
		$myrow=$rows[0];
		if ((($myrow['the_type']=='transline') || ($myrow['the_type']=='transtext')) && (is_numeric($myrow['config_value'])))
		{
			delete_lang($myrow['config_value']);
		}
		$GLOBALS['SITE_DB']->query_delete('config',array('the_name'=>$name),'',1);
		/*global $OPTIONS;  Don't do this, it will cause problems in some parts of the code
		unset($OPTIONS[$name]);*/
	}
	if (function_exists('persistent_cache_delete')) persistent_cache_delete('OPTIONS');
}

/**
 * Add a privilege, and apply it to every usergroup.
 *
 * @param  ID_TEXT		The section the privilege is filled under
 * @param  ID_TEXT		The codename for the privilege
 * @param  boolean		Whether this permission is granted to all usergroups by default
 * @param  boolean		Whether this permission is not granted to supermoderators by default (something very sensitive)
 */
function add_specific_permission($section,$name,$default=false,$not_even_mods=false)
{
	if (!$not_even_mods) // NB: Don't actually need to explicitly give admins privileges
	{
		$usergroups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);
		$admin_groups=array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),$GLOBALS['FORUM_DRIVER']->get_moderator_groups());
		foreach (array_keys($usergroups) as $id)
		{
			if (($default) || (in_array($id,$admin_groups)))
			{
				$GLOBALS['SITE_DB']->query_insert('gsp',array('specific_permission'=>$name,'group_id'=>$id,'the_page'=>'','module_the_name'=>'','category_name'=>'','the_value'=>1));
			}
		}
	}

	$GLOBALS['SITE_DB']->query_insert('sp_list',array('p_section'=>$section,'the_name'=>$name,'the_default'=>($default?1:0)));
}

/**
 * Sets the privilege of a usergroup
 *
 * @param  GROUP			The usergroup having the permission set
 * @param  ID_TEXT		The codename of the permission
 * @param  boolean		Whether the usergroup has the permission
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?ID_TEXT		The category-type for the permission (NULL: none required)
 * @param  ?ID_TEXT		The category-name/value for the permission (NULL: none required)
 */
function set_specific_permission($group_id,$permission,$value,$page=NULL,$category_type=NULL,$category_name=NULL)
{
	if (is_null($page)) $page='';
	if (is_null($category_type)) $category_type='';
	if (is_null($category_name)) $category_name='';

	$GLOBALS['SITE_DB']->query_delete('gsp',array('specific_permission'=>$permission,'group_id'=>$group_id,'the_page'=>$page,'module_the_name'=>$category_type,'category_name'=>$category_name),'',1);
	$GLOBALS['SITE_DB']->query_insert('gsp',array('specific_permission'=>$permission,'group_id'=>$group_id,'the_page'=>$page,'module_the_name'=>$category_type,'category_name'=>$category_name,'the_value'=>$value?1:0));
}

/**
 * Delete a privilege, and every usergroup is then relaxed from the restrictions of this permission.
 *
 * @param  ID_TEXT		The codename of the permission
 */
function delete_specific_permission($name)
{
	$GLOBALS['SITE_DB']->query_delete('sp_list',array('the_name'=>$name),'',1);
	$GLOBALS['SITE_DB']->query('DELETE FROM '.get_table_prefix().'gsp WHERE '.db_string_not_equal_to('module_the_name','forums').' AND '.db_string_equal_to('specific_permission',$name));
}

/**
 * Delete attachments solely used by the specified hook.
 *
 * @param  ID_TEXT		The hook
 * @param  ?object		The database connection to use (NULL: standard site connection)
 */
function delete_attachments($type,$connection=NULL)
{
	if (is_null($connection)) $connection=$GLOBALS['SITE_DB'];

	require_code('attachments2');
	require_code('attachments3');

	// Clear any de-referenced attachments
	$before=$connection->query_select('attachment_refs',array('a_id','id'),array('r_referer_type'=>$type));
	foreach ($before as $ref)
	{
		// Delete reference (as it's not actually in the new comcode!)
		$connection->query_delete('attachment_refs',array('id'=>$ref['id']),'',1);

		// Was that the last reference to this attachment? (if so -- delete attachment)
		$test=$connection->query_value_null_ok('attachment_refs','id',array('a_id'=>$ref['a_id']));
		if (is_null($test))
		{
			_delete_attachment($ref['a_id'],$connection);
		}
	}
}

/**
 * Deletes all language codes linked to by the specified table and attribute identifiers, if they exist.
 *
 * @param  ID_TEXT		The table
 * @param  array			The attributes
 * @param  ?object		The database connection to use (NULL: standard site connection)
 */
function mass_delete_lang($table,$attrs,$connection)
{
	if (count($attrs)==0) return;

	if (is_null($connection)) $connection=$GLOBALS['SITE_DB'];

	$rows=$connection->query_select($table,$attrs,NULL,'',NULL,NULL,true);
	if (!is_null($rows))
	{
		foreach ($rows as $row)
		{
			foreach ($attrs as $attr)
			{
				delete_lang($row[$attr],$connection);
			}
		}
	}
}


