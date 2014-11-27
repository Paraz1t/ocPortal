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
 * @package    core_rich_media
 */

/*
Adding attachments.
*/

/**
 * Get an array containing new Comcode, and tempcode. The function wraps the normal comcode_to_tempcode function. The function will do attachment management, including deleting of attachments that have become unused due to editing of some Comcode and removing of the reference.
 *
 * @param  LONG_TEXT                    $comcode The unparsed Comcode that references the attachments
 * @param  ID_TEXT                      $type The type the attachment will be used for (e.g. download)
 * @param  ID_TEXT                      $id The ID the attachment will be used for
 * @param  boolean                      $previewing_only Whether we are only previewing the attachments (i.e. don't store them!)
 * @param  ?object                      $connection The database connection to use (null: standard site connection)
 * @param  ?boolean                     $insert_as_admin Whether to insert it as an admin (any Comcode parsing will be carried out with admin privileges) (null: autodetect)
 * @param  ?MEMBER                      $for_member The member to use for ownership permissions (null: current member)
 * @return array                        A map containing 'Comcode' (after substitution for tying down the new attachments) and 'tempcode'
 */
function do_comcode_attachments($comcode, $type, $id, $previewing_only = false, $connection = null, $insert_as_admin = null, $for_member = null)
{
    require_lang('comcode');
    require_code('comcode_compiler');

    if (function_exists('set_time_limit')) {
        @set_time_limit(600); // Thumbnail generation etc can take some time
    }

    global $COMCODE_ATTACHMENTS;
    unset($COMCODE_ATTACHMENTS[$id]); // In case we have some kind of conflict

    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    if ($for_member !== null) {
        $member = $for_member;
    } else {
        $member = function_exists('get_member') ? get_member() : db_get_first_id();
    }
    if (is_null($insert_as_admin)) {
        $insert_as_admin = false;
    }

    // Handle data URLs for attachment embedding
    _handle_data_url_attachments($comcode, $type, $id, $connection);

    // Find out about attachments already involving this content
    global $ATTACHMENTS_ALREADY_REFERENCED;
    $old_already = $ATTACHMENTS_ALREADY_REFERENCED;
    $ATTACHMENTS_ALREADY_REFERENCED = array();
    $before = $connection->query_select('attachment_refs', array('a_id', 'id'), array('r_referer_type' => $type, 'r_referer_id' => $id));
    foreach ($before as $ref) {
        $ATTACHMENTS_ALREADY_REFERENCED[$ref['a_id']] = 1;
    }

    // Find if we have an attachment(s), and tidy up the Comcode enough to handle the attachment Comcode properly
    $has_one = false;
    $may_have_one = false;
    foreach ($_POST as $key => $value) {
        if (preg_match('#^hidFileID\_#i', $key) != 0) {
            require_code('uploads');
            $may_have_one = is_plupload();
        }
    }
    if ($may_have_one) {
        require_code('uploads');
        is_plupload(true);

        require_code('comcode_from_html');
        $comcode = preg_replace_callback('#<input [^>]*class="ocp_keep_ui_controlled" [^>]*title="([^"]*)" [^>]*type="text" [^>]*value="[^"]*"[^>]*/?' . '>#siU', 'debuttonise', $comcode);
    }

    // Go through all uploaded attachment files
    foreach ($_FILES as $key => $file) {
        $matches = array();
        if ((($may_have_one) && (is_plupload()) || (is_uploaded_file($file['tmp_name']))) && (preg_match('#file(\d+)#', $key, $matches) != 0)) {
            $has_one = true;

            // Handle attachment extraction
            $matches_extract = array();
            if (preg_match('#\[attachment( [^\]]*)type="extract"( [^\]]*)?\]new_' . $matches[1] . '\[/attachment\]#', $comcode, $matches_extract) != 0) {
                _handle_attachment_extraction($comcode, $key, $type, $id, $matches_extract, $connection); // Handle missing attachment markup for uploaded attachments
            } elseif ((strpos($comcode, ']new_' . $matches[1] . '[/attachment]') === false) && (strpos($comcode, ']new_' . $matches[1] . '[/attachment_safe]') === false)) {
                if (preg_match('#\]\d+\[/attachment\]#', $comcode) == 0) { // Attachment could have already been put through (e.g. during a preview). If we have actual ID's referenced, it's almost certainly the case.
                    $comcode .= "\n\n" . '[attachment]new_' . $matches[1] . '[/attachment]';
                }
            }
        }
    }

    // Parse the Comcode to find details of attachments (and add into the database)
    global $LAX_COMCODE;
    $temp = $LAX_COMCODE;
    if ($has_one) {
        $LAX_COMCODE = true; // We don't want a simple syntax error to cause us to lose our attachments
    }
    $tempcode = comcode_to_tempcode($comcode, $member, $insert_as_admin, 60, $id, $connection, false, false, false, false, false, null, $for_member);
    $LAX_COMCODE = $temp;
    $ATTACHMENTS_ALREADY_REFERENCED = $old_already;
    if (!array_key_exists($id, $COMCODE_ATTACHMENTS)) {
        $COMCODE_ATTACHMENTS[$id] = array();
    }

    // Put in our new attachment IDs (replacing the new_* markers)
    $ids_present = array();
    for ($i = 0; $i < count($COMCODE_ATTACHMENTS[$id]); $i++) {
        $attachment = $COMCODE_ATTACHMENTS[$id][$i];

        // If it's a new one, we need to change the comcode to reference the ID we made for it
        if ($attachment['type'] == 'new') {
            $marker_id = intval(substr($attachment['initial_id'], 4)); // After 'new_'

            $comcode = preg_replace('#(\[(attachment|attachment_safe)[^\]]*\])new_' . strval($marker_id) . '(\[/)#', '${1}' . strval($attachment['id']) . '${3}', $comcode);

            if (!is_null($type)) {
                $connection->query_insert('attachment_refs', array('r_referer_type' => $type, 'r_referer_id' => $id, 'a_id' => $attachment['id']));
            }
        } else {
            // (Re-)Reference it
            $connection->query_delete('attachment_refs', array('r_referer_type' => $type, 'r_referer_id' => $id, 'a_id' => $attachment['id']), '', 1);
            $connection->query_insert('attachment_refs', array('r_referer_type' => $type, 'r_referer_id' => $id, 'a_id' => $attachment['id']));
        }

        $ids_present[] = $attachment['id'];
    }
    // Tidy out any attachment references to files that clearly are not here
    $comcode = preg_replace('#\[(attachment|attachment_safe)[^\]]*\]new_\d+\[/(attachment|attachment_safe)\]#', '', $comcode);

    if (!$previewing_only) {
        // Clear any de-referenced attachments
        foreach ($before as $ref) {
            if ((!in_array($ref['a_id'], $ids_present)) && (strpos($comcode, 'attachment.php?id=') === false) && (!multi_lang())) {
                // Delete reference (as it's not actually in the new comcode!)
                $connection->query_delete('attachment_refs', array('id' => $ref['id']), '', 1);

                // Was that the last reference to this attachment? (if so -- delete attachment)
                $test = $connection->query_select_value_if_there('attachment_refs', 'id', array('a_id' => $ref['a_id']));
                if (is_null($test)) {
                    require_code('attachments3');
                    _delete_attachment($ref['a_id'], $connection);
                }
            }
        }
    }

    return array(
        'comcode' => $comcode,
        'tempcode' => $tempcode
    );
}

/**
 * Convert attachments embedded as data URLs (usually the result of pasting in) to real attachment Comcode.
 *
 * @param  string                       $comcode Our Comcode
 * @param  ID_TEXT                      $type The type the attachment will be used for (e.g. download)
 * @param  ID_TEXT                      $id The ID the attachment will be used for
 * @param  object                       $connection The database connection to use
 */
function _handle_data_url_attachments(&$comcode, $type, $id, $connection)
{
    if (function_exists('imagepng')) {
        $matches = array();
        $matches2 = array();
        $num_matches = preg_match_all('#<img (alt="" )?src="data:image/\w+;base64,([^"]*)" (title="" )?/?' . '>#', $comcode, $matches);
        $num_matches2 = preg_match_all('#\[img param=""\]data:image/\w+;base64,([^"]*)\[/img\]#', $comcode, $matches2);
        for ($i = 0; $i < $num_matches2; $i++) {
            $matches[0][$num_matches] = $matches2[0][$i];
            $matches[1][$num_matches] = $matches2[1][$i];
            $num_matches++;
        }
        for ($i = 0; $i < $num_matches; $i++) {
            if (strpos($comcode, $matches[0][$i]) !== false) { // Check still here (if we have same image in multiple places, may have already been attachment-ified)
                $data = @base64_decode($matches[1][$i]);
                if ($data !== false) {
                    $image = @imagecreatefromstring($data);
                    if ($image !== false) {
                        do {
                            $new_filename = uniqid('', true) . '.png';
                            $new_path = get_custom_file_base() . '/uploads/attachments/' . $new_filename;
                        }
                        while (file_exists($new_path));
                        imagepng($image, $new_path, 9);
                        imagedestroy($image);
                        require_code('images_png');
                        png_compress($new_path);

                        $attachment_id = $GLOBALS['SITE_DB']->query_insert('attachments', array(
                            'a_member_id' => get_member(),
                            'a_file_size' => strlen($data),
                            'a_url' => 'uploads/attachments/' . rawurlencode($new_filename),
                            'a_thumb_url' => '',
                            'a_original_filename' => basename($new_filename),
                            'a_num_downloads' => 0,
                            'a_last_downloaded_time' => time(),
                            'a_description' => '',
                            'a_add_time' => time()
                        ), true);
                        $GLOBALS['SITE_DB']->query_insert('attachment_refs', array('r_referer_type' => $type, 'r_referer_id' => $id, 'a_id' => $attachment_id));

                        $comcode = str_replace($comcode, $matches[0][$i], '[attachment framed="0" thumb="0"]' . strval($attachment_id) . '[/attachment]');
                    }
                }
            }
        }
    }
}

/**
 * Convert attachments marked for 'extraction' to real attachment Comcode.
 *
 * @param  string                       $comcode Our Comcode
 * @param  string                       $key The attachment file key
 * @param  ID_TEXT                      $type The type the attachment will be used for (e.g. download)
 * @param  ID_TEXT                      $id The ID the attachment will be used for
 * @param  array                        $matches_extract Reg-exp grabbed parameters from the extract marker attachment (we will re-use them for each individual attachment)
 * @param  object                       $connection The database connection to use
 */
function _handle_attachment_extraction(&$comcode, $key, $type, $id, $matches_extract, $connection)
{
    require_code('uploads');
    require_code('files');
    require_code('files2');

    $myfile = mixed();

    $added_comcode = '';

    $file = $_FILES[$key];

    $arcext = get_file_extension($file['name']);
    if (($arcext == 'tar') || ($arcext == 'zip')) {
        if ($arcext == 'tar') {
            require_code('tar');
            $myfile = tar_open($file['tmp_name'], 'rb');
            $dir = tar_get_directory($myfile, true);
        } elseif ($arcext == 'zip') {
            if ((!function_exists('zip_open')) && (get_option('unzip_cmd') == '')) {
                warn_exit(do_lang_tempcode('ZIP_NOT_ENABLED'));
            }
            if (!function_exists('zip_open')) {
                require_code('m_zip');
                $mzip = true;
            } else {
                $mzip = false;
            }

            $myfile = zip_open($file['tmp_name']);
            if (is_integer($myfile)) {
                require_code('failure');
                warn_exit(zip_error($myfile, $mzip));
            }
            $dir = array();
            while (($zip_entry = zip_read($myfile)) !== false) {
                $dir[] = array(
                    'zip_entry' => $zip_entry,
                    'path' => zip_entry_name($zip_entry),
                    'size' => zip_entry_filesize($zip_entry),
                );
            }
        }
        if (count($dir) > 100) {
            require_code('site');
            attach_message(do_lang_tempcode('TOO_MANY_FILES_TO_EXTRACT'), 'warn');
        } else {
            require_code('files');

            foreach ($dir as $entry) {
                if (substr($entry['path'], -1) == '/') {
                    continue; // Ignore folders
                }

                $_file = preg_replace('#\..*\.#', '.', basename($entry['path']));

                if (!check_extension($_file, false, null, true)) {
                    continue;
                }
                if (should_ignore_file($entry['path'], IGNORE_ACCESS_CONTROLLERS | IGNORE_HIDDEN_FILES)) {
                    continue;
                }

                $place = get_custom_file_base() . '/uploads/attachments/' . $_file;
                $i = 2;
                // Hunt with sensible names until we don't get a conflict
                while (file_exists($place)) {
                    $_file = strval($i) . basename($entry['path']);
                    $place = get_custom_file_base() . '/uploads/attachments/' . $_file;
                    $i++;
                }
                file_put_contents($place, ''); // Lock it in ASAP, to stop race conditions

                $i = 2;
                $_file_thumb = basename($entry['path']);
                $place_thumb = get_custom_file_base() . '/uploads/attachments_thumbs/' . $_file_thumb;
                // Hunt with sensible names until we don't get a conflict
                while (file_exists($place_thumb)) {
                    $_file_thumb = strval($i) . basename($entry['path']);
                    $place_thumb = get_custom_file_base() . '/uploads/attachments_thumbs/' . $_file_thumb;
                    $i++;
                }
                file_put_contents($place_thumb, ''); // Lock it in ASAP, to stop race conditions

                if ($arcext == 'tar') {
                    $file_details = tar_get_file($myfile, $entry['path'], false, $place);
                } elseif ($arcext == 'zip') {
                    zip_entry_open($myfile, $entry['zip_entry']);
                    $file_details = array(
                        'size' => $entry['size'],
                    );

                    $out_file = @fopen($place, 'wb') or intelligent_write_error($place);
                    $more = mixed();
                    do {
                        $more = zip_entry_read($entry['zip_entry']);
                        if ($more !== false) {
                            if (fwrite($out_file, $more) < strlen($more)) {
                                warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
                            }
                        }
                    }
                    while (($more !== false) && ($more != ''));
                    fclose($out_file);

                    zip_entry_close($entry['zip_entry']);
                }

                $description = do_lang('EXTRACTED_FILE');
                if (strpos($entry['path'], '/') !== false) {
                    $description = do_lang('EXTRACTED_FILE_PATH', dirname($entry['path']));
                }

                // Thumbnail
                $thumb_url = '';
                require_code('images');
                if (is_image($_file)) {
                    if (function_exists('imagetypes')) {
                        require_code('images');
                        if (!is_saveable_image($_file)) {
                            $ext = '.png';
                        } else {
                            $ext = '.' . get_file_extension($_file);
                        }
                        $thumb_url = 'uploads/attachments_thumbs/' . $_file_thumb;
                        convert_image(get_custom_base_url() . '/uploads/attachments/' . $_file, $place_thumb, -1, -1, intval(get_option('thumb_width')), true, null, false, true);

                        if ($connection->connection_write != $GLOBALS['SITE_DB']->connection_write) {
                            $thumb_url = get_custom_base_url() . '/' . $thumb_url;
                        }
                    } else {
                        $thumb_url = 'uploads/attachments/' . $_file;
                    }
                }

                // Create new attachment from extracted file
                $url = 'uploads/attachments/' . rawurlencode($_file);
                $attachment_id = $connection->query_insert('attachments', array(
                    'a_member_id' => get_member(),
                    'a_file_size' => $file_details['size'],
                    'a_url' => $url,
                    'a_thumb_url' => $thumb_url,
                    'a_original_filename' => basename($entry['path']),
                    'a_num_downloads' => 0,
                    'a_last_downloaded_time' => time(),
                    'a_description' => $description,
                    'a_add_time' => time()
                ), true);
                $connection->query_insert('attachment_refs', array('r_referer_type' => $type, 'r_referer_id' => $id, 'a_id' => $attachment_id));
                if (addon_installed('galleries')) {
                    require_code('images');
                    if ((is_video($url, true, true)) && ($connection->connection_read == $GLOBALS['SITE_DB']->connection_read)) {
                        require_code('transcoding');
                        transcode_video($url, 'attachments', $attachment_id, 'id', 'a_url', 'a_original_filename', null, null);
                    }
                }

                // Append Comcode for this new attachment
                $added_comcode .= "\n\n" . '[attachment' . $matches_extract[1] . $matches_extract[2] . ' type="" description="' . comcode_escape($description) . '"]' . strval($attachment_id) . '[/attachment]';
            }
        }
        if ($arcext == 'tar') {
            tar_close($myfile);
        } elseif ($arcext == 'zip') {
            zip_close($myfile);
        }

        // Remove extract marker and put new Comcode in place
        $comcode = str_replace($matches_extract[0], trim($added_comcode), $comcode);
    }
}

/**
 * Check that not too many attachments have been uploaded for the member submitting.
 */
function _check_attachment_count()
{
    if ((get_forum_type() == 'ocf') && (function_exists('get_member'))) {
        require_code('ocf_groups');
        require_lang('ocf');
        require_lang('comcode');
        $max_attachments_per_post = ocf_get_member_best_group_property(get_member(), 'max_attachments_per_post');

        $may_have_one = false;
        foreach ($_POST as $key => $value) {
            if (preg_match('#^hidFileID\_#i', $key) != 0) {
                require_code('uploads');
                $may_have_one = is_plupload();
            }
        }
        if ($may_have_one) {
            require_code('uploads');
            is_plupload(true);
        }
        foreach (array_keys($_FILES) as $name) {
            if ((substr($name, 0, 4) == 'file') && (is_numeric(substr($name, 4)) && ($_FILES[$name]['tmp_name'] != ''))) {
                $max_attachments_per_post--;
            }
        }

        if ($max_attachments_per_post < 0) {
            warn_exit(do_lang_tempcode('TOO_MANY_ATTACHMENTS'));
        }
    }
}

/**
 * Insert some Comcode content that may contain attachments, and return the language ID.
 *
 * @param  ID_TEXT                      $field_name The field name
 * @param  integer                      $level The level of importance this language string holds
 * @set    1 2 3 4
 * @param  LONG_TEXT                    $text The Comcode content
 * @param  ID_TEXT                      $type The arbitrary type that the attached is for (e.g. download)
 * @param  ID_TEXT                      $id The ID in the set of the arbitrary types that the attached is for
 * @param  ?object                      $connection The database connection to use (null: standard site connection)
 * @param  boolean                      $insert_as_admin Whether to insert it as an admin (any Comcode parsing will be carried out with admin privileges)
 * @param  ?MEMBER                      $for_member The member to use for ownership permissions (null: current member)
 * @return array                        The language ID save fields
 */
function insert_lang_comcode_attachments($field_name, $level, $text, $type, $id, $connection = null, $insert_as_admin = false, $for_member = null)
{
    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    require_lang('comcode');

    _check_attachment_count();

    $_info = do_comcode_attachments($text, $type, $id, false, $connection, $insert_as_admin, $for_member);
    $text_parsed = $_info['tempcode']->to_assembly();
    $source_user = (function_exists('get_member')) ? get_member() : $GLOBALS['FORUM_DRIVER']->get_guest_id();

    if (!multi_lang_content()) {
        final_attachments_from_preview($id, $connection);

        $ret = array();
        $ret[$field_name] = $_info['comcode'];
        $ret[$field_name . '__text_parsed'] = $text_parsed;
        $ret[$field_name . '__source_user'] = $source_user;
        return $ret;
    }

    $lang_id = null;

    if (user_lang() == 'Gibb') { // Debug code to help us spot language layer bugs. We expect &keep_lang=EN to show EnglishEnglish content, but otherwise no EnglishEnglish content.
        $lang_id = $connection->query_insert('translate', array('source_user' => $source_user, 'broken' => 0, 'importance_level' => $level, 'text_original' => 'EnglishEnglishWarningWrongLanguageWantGibberishLang', 'text_parsed' => '', 'language' => 'EN'), true);
    }
    if (is_null($lang_id)) {
        $lang_id = $connection->query_insert('translate', array(
            'source_user' => $source_user,
            'broken' => 0,
            'importance_level' => $level,
            'text_original' => $_info['comcode'],
            'text_parsed' => $text_parsed,
            'language' => user_lang(),
        ), true);
    } else {
        $connection->query_insert('translate', array(
            'id' => $lang_id,
            'source_user' => $source_user,
            'broken' => 0,
            'importance_level' => $level,
            'text_original' => $_info['comcode'],
            'text_parsed' => $text_parsed,
            'language' => user_lang(),
        ));
    }

    final_attachments_from_preview($id, $connection);

    return array(
        $field_name => $lang_id,
    );
}

/**
 * Finalise attachments which were created during a preview, so that they have the proper reference IDs.
 *
 * @param  ID_TEXT                      $id The ID in the set of the arbitrary types that the attached is for
 * @param  ?object                      $connection The database connection to use (null: standard site connection)
 */
function final_attachments_from_preview($id, $connection = null)
{
    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    // Clean up the any attachments added at the preview stage
    $posting_ref_id = post_param_integer('posting_ref_id', null);
    if ($posting_ref_id < 0) {
        fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
    }
    if (!is_null($posting_ref_id)) {
        $connection->query_delete('attachment_refs', array('r_referer_type' => 'null', 'r_referer_id' => strval(-$posting_ref_id)), '', 1);
        $connection->query_delete('attachment_refs', array('r_referer_id' => strval(-$posting_ref_id))); // Can trash this, was made during preview but we made a new one in do_comcode_attachments (recalled by insert_lang_comcode_attachments)
    }
}
