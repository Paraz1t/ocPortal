<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__content2()
{
    define('META_DATA_HEADER_NO', 0);
    define('META_DATA_HEADER_YES', 1);
    define('META_DATA_HEADER_FORCE', 2);
}

/**
 * Get template fields to insert into a form page, for manipulation of meta data.
 *
 * @param  ID_TEXT                      $content_type The type of resource (e.g. download)
 * @param  ?ID_TEXT                     $content_id The ID of the resource (null: adding)
 * @param  boolean                      $allow_no_owner Whether to allow owner to be left blank (meaning no owner)
 * @param  ?array                       $fields_to_skip List of fields to NOT take in (null: empty list)
 * @param  integer                      $show_header Whether to show a header (a META_DATA_HEADER_* constant)
 * @return tempcode                     Form page tempcode fragment
 */
function meta_data_get_fields($content_type, $content_id, $allow_no_owner = false, $fields_to_skip = null, $show_header = 1)
{
    require_lang('meta_data');

    $fields = new Tempcode();

    if (has_privilege(get_member(), 'edit_meta_fields')) {
        if (is_null($fields_to_skip)) {
            $fields_to_skip = array();
        }

        require_code('content');
        $ob = get_content_object($content_type);
        $info = $ob->info();

        require_code('content');
        $content_row = mixed();
        if (!is_null($content_id)) {
            list(, , , $content_row) = content_get_details($content_type, $content_id);
        }

        $views_field = in_array('views', $fields_to_skip) ? null : $info['views_field'];
        if (!is_null($views_field)) {
            $views = is_null($content_row) ? 0 : $content_row[$views_field];
            $fields->attach(form_input_integer(do_lang_tempcode('_VIEWS'), do_lang_tempcode('DESCRIPTION_META_VIEWS'), 'meta_views', null, false));
        }

        $submitter_field = in_array('submitter', $fields_to_skip) ? null : $info['submitter_field'];
        if (!is_null($submitter_field)) {
            $submitter = is_null($content_row) ? get_member() : $content_row[$submitter_field];
            $username = $GLOBALS['FORUM_DRIVER']->get_username($submitter);
            if (is_null($username)) {
                $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
            }
            $fields->attach(form_input_username(do_lang_tempcode('OWNER'), do_lang_tempcode('DESCRIPTION_OWNER'), 'meta_submitter', $username, !$allow_no_owner));
        }

        $add_time_field = in_array('add_time', $fields_to_skip) ? null : $info['add_time_field'];
        if (!is_null($add_time_field)) {
            $add_time = is_null($content_row) ? time() : $content_row[$add_time_field];
            $fields->attach(form_input_date(do_lang_tempcode('ADD_TIME'), do_lang_tempcode('DESCRIPTION_META_ADD_TIME'), 'meta_add_time', true, false, true, $add_time, 40, intval(date('Y')) - 20, null));
        }

        if (!is_null($content_id)) {
            $edit_time_field = in_array('edit_time', $fields_to_skip) ? null : $info['edit_time_field'];
            if (!is_null($edit_time_field)) {
                $edit_time = is_null($content_row) ? null : (is_null($content_row[$edit_time_field]) ? time() : max(time(), $content_row[$edit_time_field]));
                $fields->attach(form_input_date(do_lang_tempcode('EDIT_TIME'), do_lang_tempcode('DESCRIPTION_META_EDIT_TIME'), 'meta_edit_time', false, is_null($edit_time), true, $edit_time, 10, null, null));
            }
        }

        if (($info['support_url_monikers']) && (!in_array('url_moniker', $fields_to_skip))) {
            $url_moniker = mixed();
            if (!is_null($content_id)) {
                if ($content_type == 'comcode_page') {
                    list($zone, $_content_id) = explode(':', $content_id);
                    $attributes = array();
                    $url_moniker = find_id_moniker(array('page' => $_content_id) + $attributes, $zone);
                } else {
                    $_content_id = $content_id;
                    list($zone, $attributes,) = page_link_decode($info['view_page_link_pattern']);
                    $url_moniker = find_id_moniker(array('id' => $_content_id) + $attributes, $zone);
                }

                if (is_null($url_moniker)) {
                    $url_moniker = '';
                }

                $moniker_where = array('m_manually_chosen' => 1, 'm_resource_page' => ($content_type == 'comcode_page') ? $_content_id : $attributes['page'], 'm_resource_type' => isset($attributes['type']) ? $attributes['type'] : '', 'm_resource_id' => $_content_id);
                $manually_chosen = !is_null($GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers', 'm_moniker', $moniker_where));
            } else {
                $url_moniker = '';
                $manually_chosen = false;
            }
            $fields->attach(form_input_codename(do_lang_tempcode('URL_MONIKER'), do_lang_tempcode('DESCRIPTION_META_URL_MONIKER', escape_html($url_moniker)), 'meta_url_moniker', $manually_chosen ? $url_moniker : '', false, null, null, array('/')));
        }
    } else {
        if ($show_header != META_DATA_HEADER_FORCE) {
            return new Tempcode();
        }
    }

    if ((!$fields->is_empty()) && ($show_header != META_DATA_HEADER_NO)) {
        $_fields = new Tempcode();
        $_fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => 'adf2a2cda231619243763ddbd0cc9d4e', 'SECTION_HIDDEN' => true,
            'TITLE' => do_lang_tempcode('META_DATA'),
            'HELP' => do_lang_tempcode('DESCRIPTION_META_DATA', is_null($content_id) ? do_lang_tempcode('RESOURCE_NEW') : $content_id),
        )));
        $_fields->attach($fields);
        return $_fields;
    }

    return $fields;
}

/**
 * Get field values for meta data.
 *
 * @param  ID_TEXT                      $content_type The type of resource (e.g. download)
 * @param  ?ID_TEXT                     $content_id The old ID of the resource (null: adding)
 * @param  ?array                       $fields_to_skip List of fields to NOT take in (null: empty list)
 * @param  ?ID_TEXT                     $new_content_id The new ID of the resource (null: not being renamed)
 * @return array                        A map of standard meta data fields (name to value). If adding, this map is accurate for adding. If editing, NULLs mean do-not-edit or non-editable.
 */
function actual_meta_data_get_fields($content_type, $content_id, $fields_to_skip = null, $new_content_id = null)
{
    require_lang('meta_data');

    if (is_null($fields_to_skip)) {
        $fields_to_skip = array();
    }

    if (fractional_edit()) {
        return array(
            'views' => INTEGER_MAGIC_NULL,
            'submitter' => INTEGER_MAGIC_NULL,
            'add_time' => INTEGER_MAGIC_NULL,
            'edit_time' => INTEGER_MAGIC_NULL,
            /*'url_moniker'=>NULL,, was handled internally*/
        );
    }

    if (!has_privilege(get_member(), 'edit_meta_fields')) { // Pass through as how an edit would normally function (things left alone except edit time)
        return array(
            'views' => is_null($content_id) ? 0 : INTEGER_MAGIC_NULL,
            'submitter' => is_null($content_id) ? get_member() : INTEGER_MAGIC_NULL,
            'add_time' => is_null($content_id) ? time() : INTEGER_MAGIC_NULL,
            'edit_time' => time(),
            /*'url_moniker'=>NULL,, was handled internally*/
        );
    }

    require_code('content');
    $ob = get_content_object($content_type);
    $info = $ob->info();

    $views = mixed();
    $views_field = in_array('views', $fields_to_skip) ? null : $info['views_field'];
    if (!is_null($views_field)) {
        $views = post_param_integer('meta_views', null);
        if (is_null($views)) {
            if (is_null($content_id)) {
                $views = 0;
            } else {
                $views = INTEGER_MAGIC_NULL;
            }
        }
    }

    $submitter = mixed();
    $submitter_field = in_array('submitter', $fields_to_skip) ? null : $info['submitter_field'];
    if (!is_null($submitter_field)) {
        $_submitter = post_param('meta_submitter', $GLOBALS['FORUM_DRIVER']->get_username(get_member()));
        if ($_submitter != '') {
            $submitter = $GLOBALS['FORUM_DRIVER']->get_member_from_username($_submitter);
            if (is_null($submitter)) {
                $submitter = null; // Leave alone, we did not recognise the user
                attach_message(do_lang_tempcode('_MEMBER_NO_EXIST', escape_html($_submitter)), 'warn'); // ...but attach an error at least
            }
            if (is_null($submitter)) {
                if (is_null($content_id)) {
                    $submitter = get_member();
                }
            }
        } else {
            $submitter = null;
        }
    }

    $add_time = mixed();
    $add_time_field = in_array('add_time', $fields_to_skip) ? null : $info['add_time_field'];
    if (!is_null($add_time_field)) {
        $add_time = get_input_date('meta_add_time');
        if (is_null($add_time)) {
            if (is_null($content_id)) {
                $add_time = time();
            } else {
                $add_time = INTEGER_MAGIC_NULL; // This code branch should actually be impossible to reach
            }
        }
    }

    $edit_time = mixed();
    $edit_time_field = in_array('edit_time', $fields_to_skip) ? null : $info['edit_time_field'];
    if (!is_null($edit_time_field)) {
        $edit_time = get_input_date('meta_edit_time');
        if (is_null($edit_time)) {
            if (is_null($content_id)) {
                $edit_time = null; // No edit time
            } else {
                $edit_time = null; // Edit time explicitly wiped out
            }
        }
    }

    $url_moniker = mixed();
    if (($info['support_url_monikers']) && (!in_array('url_moniker', $fields_to_skip))) {
        $url_moniker = post_param('meta_url_moniker', '');
        if ($url_moniker == '') {
            $url_moniker = null;
        }

        if ($url_moniker !== null) {
            require_code('type_validation');
            if (!is_alphanumeric(str_replace('/', '', $url_moniker))) {
                attach_message(do_lang_tempcode('BAD_CODENAME'), 'warn');
                $url_moniker = null;
            }

            if (!is_null($url_moniker)) {
                if ($content_type == 'comcode_page') {
                    list($zone, $page) = explode(':', $content_id);
                    $type = '';
                    $_content_id = $zone;

                    if (!is_null($new_content_id)) {
                        $GLOBALS['SITE_DB']->query_update('url_id_monikers', array(
                            'm_resource_page' => $new_content_id,
                        ), array('m_resource_page' => $page, 'm_resource_type' => '', 'm_resource_id' => $zone));
                    }
                } else {
                    list($zone, $attributes,) = page_link_decode($info['view_page_link_pattern']);
                    $page = $attributes['page'];
                    $type = $attributes['type'];
                    $_content_id = $content_id;

                    if (!is_null($new_content_id)) {
                        $GLOBALS['SITE_DB']->query_update('url_id_monikers', array(
                            'm_resource_id' => $new_content_id,
                        ), array('m_resource_page' => $page, 'm_resource_type' => $type, 'm_resource_id' => $content_id));
                    }
                }

                $ok = true;

                // Test for conflicts
                $conflict_test_map = array(
                    'm_moniker' => $url_moniker,
                    'm_deprecated' => 0
                );
                if (substr($url_moniker, 0, 1) != '/') { // Can narrow the conflict-check scope if it's relative to a module rather than a zone ('/' prefix)
                    $conflict_test_map += array(
                        'm_resource_page' => $page,
                        'm_resource_type' => $type,
                    );
                }
                $test = $GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers', 'm_resource_id', $conflict_test_map);
                if (($test !== null) && ($test !== $_content_id)) {
                    $test_page = $GLOBALS['SITE_DB']->query_select_value_if_there('url_id_monikers', 'm_resource_page', $conflict_test_map);
                    if ($content_type == 'comcode_page') {
                        if (_request_page($test_page, $test, null, get_site_default_lang(), true) !== false) {
                            $ok = false;
                        } else { // Deleted, so clean up
                            $GLOBALS['SITE_DB']->query_delete('url_id_monikers', 'm_resource_page', $conflict_test_map);
                        }
                    } else {
                        $test2 = content_get_details(convert_ocportal_type_codes('module', $test_page, 'content_type'), $test);
                        if ($test2[0] !== null) {
                            $ok = false;
                        } else { // Deleted, so clean up
                            $GLOBALS['SITE_DB']->query_delete('url_id_monikers', 'm_resource_page', $conflict_test_map);
                        }
                    }
                    if (!$ok) {
                        if ($content_type == 'comcode_page') {
                            $competing_page_link = $test . ':' . $page;
                        } else {
                            $competing_page_link = '_WILD' . ':' . $page;
                            if ($type != '' || $test != '') {
                                $competing_page_link .= ':' . $type;
                            }
                            if ($test != '') {
                                $competing_page_link .= ':' . $test;
                            }
                        }
                        attach_message(do_lang_tempcode('URL_MONIKER_TAKEN', escape_html($competing_page_link), escape_html($url_moniker)), 'warn');
                    }
                }

                if (substr($url_moniker, 0, 1) == '/') { // ah, relative to zones, better run some anti-conflict tests!
                    $parts = explode('/', substr($url_moniker, 1), 3);

                    if ($ok) {
                        // Test there are no zone conflicts
                        if ((file_exists(get_file_base() . '/' . $parts[0])) || (file_exists(get_custom_file_base() . '/' . $parts[0]))) {
                            $ok = false;
                            attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_ZONE'), 'warn');
                        }
                    }

                    if ($ok) {
                        // Test there are no page conflicts, from perspective of welcome zone
                        require_code('site');
                        $test1 = (count($parts) < 2) ? _request_page($parts[0], '') : false;
                        $test2 = false;
                        if (isset($parts[1])) {
                            $test2 = (count($parts) < 3) ? _request_page($parts[1], $parts[0]) : false;
                        }
                        if (($test1 !== false) || ($test2 !== false)) {
                            $ok = false;
                            attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_PAGE'), 'warn');
                        }
                    }

                    if ($ok) {
                        // Test there are no page conflicts, from perspective of deep zones
                        require_code('site');
                        $start = 0;
                        $zones = array();
                        do {
                            $zones = find_all_zones(false, false, false, $start, 50);
                            foreach ($zones as $zone_name) {
                                $test1 = (count($parts) < 2) ? _request_page($parts[0], $zone_name) : false;
                                if ($test1 !== false) {
                                    $ok = false;
                                    attach_message(do_lang_tempcode('URL_MONIKER_CONFLICT_PAGE'), 'warn');
                                    break 2;
                                }
                            }
                            $start += 50;
                        }
                        while (count($zones) != 0);
                    }
                }

                if ($ok) {
                    // Insert
                    $GLOBALS['SITE_DB']->query_delete('url_id_monikers', array(    // It's possible we're re-activating/replacing a deprecated one
                        'm_resource_page' => $page,
                        'm_resource_type' => $type,
                        'm_resource_id' => $_content_id,
                        'm_moniker' => $url_moniker,
                    ), '', 1);
                    $GLOBALS['SITE_DB']->query_update('url_id_monikers', array( // Deprecate old monikers
                        'm_deprecated' => 1,
                    ), array(
                        'm_resource_page' => $page,
                        'm_resource_type' => $type,
                        'm_resource_id' => $_content_id,
                    ));
                    $GLOBALS['SITE_DB']->query_insert('url_id_monikers', array(
                        'm_resource_page' => $page,
                        'm_resource_type' => $type,
                        'm_resource_id' => $_content_id,
                        'm_moniker' => $url_moniker,
                        'm_moniker_reversed' => strrev($url_moniker),
                        'm_deprecated' => 0,
                        'm_manually_chosen' => 1,
                    ));
                }
            }
        }
    }

    return array(
        'views' => $views,
        'submitter' => $submitter,
        'add_time' => $add_time,
        'edit_time' => $edit_time,
        /*'url_moniker'=>$url_moniker, was handled internally*/
    );
}

/**
 * Read in an additional meta data field, specific to a resource type.
 *
 * @param  array                        $meta_data Meta data already collected
 * @param  ID_TEXT                      $key The parameter name
 * @param  mixed                        $default The default if it was not set
 */
function actual_meta_data_get_fields__special(&$meta_data, $key, $default)
{
    $meta_data[$key] = $default;
    if (has_privilege(get_member(), 'edit_meta_fields')) {
        if (is_integer($default)) {
            switch ($default) {
                case 0:
                case INTEGER_MAGIC_null:
                    $meta_data[$key] = post_param_integer('meta_' . $key, $default);
                    break;
            }
        } else {
            switch ($default) {
                case '':
                case STRING_MAGIC_null:
                    $meta_data[$key] = post_param('meta_' . $key, $default);
                    if ($meta_data[$key] == '') {
                        $meta_data[$key] = $default;
                    }
                    break;

                case null:
                    $meta_data[$key] = post_param_integer('meta_' . $key, null);
                    break;
            }
        }
    }
}
