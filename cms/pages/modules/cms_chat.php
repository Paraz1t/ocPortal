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
 * @package    chat
 */

/**
 * Module page class.
 */
class Module_cms_chat
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Philip Withnall';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean                  Whether to check permissions.
     * @param  ?MEMBER                  The member to check permissions as (null: current user).
     * @param  boolean                  Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean                  Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array                   A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            'browse' => array('CHAT_MODERATION', 'menu/social/chat/chat'),
        );
    }

    /**
     * Find privileges defined as overridable by this module.
     *
     * @return array                    A map of privileges that are overridable; privilege to 0 or 1. 0 means "not category overridable". 1 means "category overridable".
     */
    public function get_privilege_overrides()
    {
        require_lang('chat');
        return array('edit_lowrange_content' => array(1, 'MODERATE_CHATROOMS'));
    }

    public $title;
    public $myrow;
    public $message_id;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param('type', 'browse');

        require_lang('chat');

        set_helper_panel_tutorial('tut_chat');

        if ($type == 'browse') {
            if (has_actual_page_access(get_member(), 'admin_chat')) {
                $also_url = build_url(array('page' => 'admin_chat'), get_module_zone('admin_chat'));
                attach_message(do_lang_tempcode('menus:ALSO_SEE_CMS', escape_html($also_url->evaluate())), 'inform', true);
            }

            breadcrumb_set_self(do_lang_tempcode('CHATROOMS'));

            $this->title = get_screen_title('CHAT_MODERATION');
        }

        if ($type == 'room') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS'))));

            $this->title = get_screen_title('CHAT_MODERATION');
        }

        if ($type == 'ban') {
            $id = get_param_integer('id');
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS')), array('_SELF:_SELF:room:' . strval($id), do_lang_tempcode('CHAT_MODERATION'))));

            $this->title = get_screen_title('CHAT_BAN');
        }

        if ($type == 'unban') {
            $id = get_param_integer('id');
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS')), array('_SELF:_SELF:room:' . strval($id), do_lang_tempcode('CHAT_MODERATION'))));

            $this->title = get_screen_title('CHAT_UNBAN');
        }

        if ($type == 'edit') {
            $id = get_param_integer('id');

            $rows = $GLOBALS['SITE_DB']->query_select('chat_messages', array('*'), array('id' => $id), '', 1);
            if (!array_key_exists(0, $rows)) {
                return warn_screen($this->title, do_lang_tempcode('MISSING_RESOURCE'));
            }
            $myrow = $rows[0];

            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS')), array('_SELF:_SELF:room:' . strval($myrow['room_id']), do_lang_tempcode('CHAT_MODERATION'))));

            $this->title = get_screen_title('EDIT_MESSAGE');

            $this->myrow = $myrow;
        }

        if ($type == '_edit') {
            breadcrumb_set_self(do_lang_tempcode('DONE'));

            $delete = post_param_integer('delete', 0);
            if ($delete == 1) {
                $message_id = get_param_integer('id');

                $rows = $GLOBALS['SITE_DB']->query_select('chat_messages', array('the_message', 'room_id'), array('id' => $message_id));
                if (!array_key_exists(0, $rows)) {
                    return warn_screen($this->title, do_lang_tempcode('MISSING_RESOURCE'));
                }
                $myrow = $rows[0];

                breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS')), array('_SELF:_SELF:room:' . strval($myrow['room_id']), do_lang_tempcode('CHAT_MODERATION'))));

                $this->title = get_screen_title('DELETE_MESSAGE');

                $this->myrow = $myrow;
                $this->message_id = $message_id;
            } else {
                $this->title = get_screen_title('EDIT_MESSAGE');
            }
        }

        if ($type == 'delete') {
            $id = get_param_integer('id');
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CHATROOMS')), array('_SELF:_SELF:room:' . strval($id), do_lang_tempcode('CHAT_MODERATION'))));

            $this->title = get_screen_title('DELETE_ALL_MESSAGES');
        }

        if ($type == '_delete') {
            breadcrumb_set_self(do_lang_tempcode('DONE'));

            $this->title = get_screen_title('DELETE_ALL_MESSAGES');
        }

        if ($type == 'mass_delete') {
            breadcrumb_set_self(do_lang_tempcode('DONE'));

            $this->title = get_screen_title('DELETE_SOME_MESSAGES');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return tempcode                 The result of execution.
     */
    public function run()
    {
        require_code('chat');
        require_code('chat2');
        require_css('chat');

        $type = get_param('type', 'browse');

        if ($type == 'browse') {
            return $this->chat_choose_room();
        }
        if ($type == 'room') {
            return $this->moderate_chat_room();
        }
        if ($type == 'ban') {
            return $this->chat_ban();
        }
        if ($type == 'unban') {
            return $this->chat_unban();
        }
        if ($type == 'delete') {
            return $this->chat_delete_all_messages();
        }
        if ($type == '_delete') {
            return $this->_chat_delete_all_messages();
        }
        if ($type == 'mass_delete') {
            return $this->_chat_delete_many_messages();
        }
        if ($type == 'edit') {
            return $this->chat_edit_message();
        }
        if ($type == '_edit') {
            return $this->_chat_edit_message();
        }

        return new Tempcode();
    }

    /**
     * The main user interface for choosing a chat room to moderate.
     *
     * @return tempcode                 The UI.
     */
    public function chat_choose_room()
    {
        $introtext = do_lang_tempcode('CHAT_MODERATION_INTRO');

        $start = get_param_integer('start', 0);
        $max = get_param_integer('max', 50);
        $sortables = array('room_name' => do_lang_tempcode('CHATROOM_NAME'), 'messages' => do_lang_tempcode('MESSAGES'));
        $test = explode(' ', either_param('sort', 'room_name DESC'));
        if (count($test) == 1) {
            $test[1] = 'DESC';
        }
        list($sortable, $sort_order) = $test;
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }
        require_code('templates_results_table');
        $fields_title = results_field_title(array(do_lang_tempcode('CHATROOM_NAME'), do_lang_tempcode('CHATROOM_OWNER'), do_lang_tempcode('CHATROOM_LANG'), do_lang_tempcode('MESSAGES')), $sortables, 'sort', $sortable . ' ' . $sort_order);

        $max_rows = $GLOBALS['SITE_DB']->query_select_value('chat_rooms', 'COUNT(*)', array('is_im' => 0));
        $sort_clause = ($sortable == 'room_name') ? ('ORDER BY room_name ' . $sort_order) : '';
        $rows = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('is_im' => 0), $sort_clause, $max, $start);
        if ($sortable == 'messages') {
            usort($rows, array('Module_cms_chat', '_sort_chat_browse_rows'));
            if ($sort_order == 'DESC') {
                $rows = array_reverse($rows);
            }
        }

        require_code('chat_lobby');

        $fields = new Tempcode();
        foreach ($rows as $row) {
            $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $row['id']))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
            if ((!handle_chatroom_pruning($row)) && ($has_mod_access)) {
                $url = build_url(array('page' => '_SELF', 'type' => 'room', 'id' => $row['id']), '_SELF');
                $messages = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'COUNT(*)', array('room_id' => $row['id']));
                $_username = $GLOBALS['FORUM_DRIVER']->get_username($row['room_owner']);
                if (is_null($_username)) {
                    $username = do_lang('NA_EM');
                } else {
                    $username = make_string_tempcode($_username);
                }
                $fields->attach(results_entry(array(hyperlink($url, escape_html($row['room_name'])), $username, escape_html($row['room_language']), escape_html(integer_format($messages)))));
            }
        }
        if ($fields->is_empty()) {
            inform_exit(do_lang_tempcode('NO_CATEGORIES'));
        }

        $results_table = results_table(do_lang_tempcode('CHATROOMS'), $start, 'start', $max, 'max', $max_rows, $fields_title, $fields, $sortables, $sortable, $sort_order, 'sort');

        $tpl = do_template('CHAT_MODERATE_SCREEN', array('_GUID' => 'c59cb6c8409d0e678b05628d92e423db', 'TITLE' => $this->title, 'INTRODUCTION' => $introtext, 'CONTENT' => $results_table, 'LINKS' => array()));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }

    /**
     * Sort chatroom rows (callback).
     *
     * @param  array                    First row.
     * @param  array                    Second row.
     * @return integer                  Sorting code.
     */
    public function _sort_chat_browse_rows($a, $b)
    {
        $messages_a = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'COUNT(*)', array('room_id' => $a['id']));
        $messages_b = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'COUNT(*)', array('room_id' => $b['id']));
        if ($messages_a < $messages_b) {
            return (-1);
        } elseif ($messages_a == $messages_b) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * The main user interface for moderating a chat room.
     *
     * @return tempcode                 The UI.
     */
    public function moderate_chat_room()
    {
        $room_id = get_param_integer('id');
        check_chatroom_access($room_id);
        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }

        $start = get_param_integer('start', 0);
        $max = get_param_integer('max', 50);
        $sortables = array('date_and_time' => do_lang_tempcode('DATE_TIME'), 'member_id' => do_lang_tempcode('MEMBER'));
        $test = explode(' ', get_param('sort', 'date_and_time DESC'), 2);
        if (count($test) == 1) {
            $test[1] = 'DESC';
        }
        list($sortable, $sort_order) = $test;
        if (((strtoupper($sort_order) != 'ASC') && (strtoupper($sort_order) != 'DESC')) || (!array_key_exists($sortable, $sortables))) {
            log_hack_attack_and_exit('ORDERBY_HACK');
        }
        $max_rows = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'COUNT(*)', array('room_id' => $room_id));
        $rows = $GLOBALS['SITE_DB']->query_select('chat_messages', array('*'), array('room_id' => $room_id), 'ORDER BY ' . $sortable . ' ' . $sort_order, $max, $start);
        $fields = new Tempcode();
        require_code('templates_results_table');
        $array = array(do_lang_tempcode('MEMBER'), do_lang_tempcode('DATE_TIME'), do_lang_tempcode('MESSAGE'));
        if (has_js()) {
            $array[] = do_lang_tempcode('DELETE');
        }
        $fields_title = results_field_title($array, $sortables, 'sort', $sortable . ' ' . $sort_order);
        foreach ($rows as $myrow) {
            $url = build_url(array('page' => '_SELF', 'type' => 'edit', 'room_id' => $room_id, 'id' => $myrow['id']), '_SELF');

            $username = $GLOBALS['FORUM_DRIVER']->get_username($myrow['member_id']);
            if (is_null($username)) {
                $username = '';
            }//do_lang('UNKNOWN');

            $message = get_translated_tempcode('chat_messages', $myrow, 'the_message');

            $link_time = hyperlink($url, escape_html(get_timezoned_date($myrow['date_and_time'])));

            $_row = array($GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($GLOBALS['FORUM_DRIVER']->get_member_from_username($username), false, '', false), escape_html($link_time), $message);
            if (has_js()) {
                $deletion_tick = do_template('RESULTS_TABLE_TICK', array('_GUID' => '40c6bd03e455c98589542b704259351d', 'ID' => strval($myrow['id'])));
                $_row[] = $deletion_tick;
            }

            $fields->attach(results_entry($_row));
        }
        if ($fields->is_empty()) {
            if ($start != 0) { // Go back a page, because we might have come here after deleting
                $_GET['start'] = strval(max(0, $start - $max));
                return $this->moderate_chat_room();
            }
            inform_exit(do_lang_tempcode('NO_ENTRIES'));
        }

        $content = results_table(do_lang_tempcode('MESSAGES'), $start, 'start', $max, 'max', $max_rows, $fields_title, $fields, $sortables, $sortable, $sort_order, 'sort');

        $mod_link = hyperlink(build_url(array('page' => '_SELF', 'type' => 'delete', 'stage' => 0, 'id' => $room_id), '_SELF'), do_lang_tempcode('DELETE_ALL_MESSAGES'));
        $view_link = hyperlink(build_url(array('page' => 'chat', 'type' => 'room', 'id' => $room_id), get_module_zone('chat')), do_lang_tempcode('VIEW'));
        $logs_link = hyperlink(build_url(array('page' => 'chat', 'type' => 'download_logs', 'id' => $room_id), get_module_zone('chat')), do_lang_tempcode('CHAT_DOWNLOAD_LOGS'));
        $links = array($mod_link, $view_link, $logs_link);

        $delete_url = build_url(array('page' => '_SELF', 'type' => 'mass_delete', 'room_id' => $room_id, 'start' => $start, 'max' => $max), '_SELF');

        $tpl = do_template('CHAT_MODERATE_SCREEN', array('_GUID' => '940de7e8c9a0ac3c575892887c7ef3c0', 'URL' => $delete_url, 'TITLE' => $this->title, 'INTRODUCTION' => '', 'CONTENT' => $content, 'LINKS' => $links));
        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }

    /**
     * The actualiser for banning a chatter.
     *
     * @return tempcode                 The UI.
     */
    public function chat_ban()
    {
        $id = get_param_integer('id');

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }
        check_privilege('ban_chatters_from_rooms');

        $member_id = post_param_integer('member_id', null);
        if (is_null($member_id)) {
            $member_id = get_param_integer('member_id');
            $confirm_needed = true;
        } else {
            $confirm_needed = false;
        }

        if (is_guest($member_id)) {
            warn_exit(do_lang_tempcode('CHAT_BAN_GUEST'));
        }

        if ($member_id == get_member()) {
            warn_exit(do_lang_tempcode('CHAT_BAN_YOURSELF'));
        }

        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
        if (is_null($username)) {
            $username = do_lang('UNKNOWN');
        }

        if ($confirm_needed) {
            $hidden = form_input_hidden('member_id', strval($member_id));
            return do_template('CONFIRM_SCREEN', array('_GUID' => '7d04bebbac2c49be4458afdbf5619dc7', 'TITLE' => $this->title, 'TEXT' => do_lang_tempcode('Q_SURE_BAN', escape_html($username)), 'URL' => get_self_url(), 'HIDDEN' => $hidden, 'FIELDS' => ''));
        }

        chatroom_ban_to($member_id, $id);

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The actualiser for unbanning a chatter.
     *
     * @return tempcode                 The UI.
     */
    public function chat_unban()
    {
        $id = get_param_integer('id');

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }
        check_privilege('ban_chatters_from_rooms');

        $member_id = post_param_integer('member_id', null);
        if (is_null($member_id)) {
            $member_id = get_param_integer('member_id');
            $confirm_needed = true;
        } else {
            $confirm_needed = false;
        }

        $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
        if (is_null($username)) {
            $username = do_lang('UNKNOWN');
        }

        if ($confirm_needed) {
            $hidden = form_input_hidden('member_id', strval($member_id));
            return do_template('CONFIRM_SCREEN', array('_GUID' => '6e90c87aa46814a8f4b8c5b2fee6c29d', 'TITLE' => $this->title, 'TEXT' => do_lang_tempcode('Q_SURE_UNBAN', escape_html($username)), 'URL' => get_self_url(), 'HIDDEN' => $hidden, 'FIELDS' => ''));
        }

        chatroom_unban_to($member_id, $id);

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The UI for editing a message.
     *
     * @return tempcode                 The UI.
     */
    public function chat_edit_message()
    {
        $myrow = $this->myrow;

        $room_id = $myrow['room_id'];
        check_chatroom_access($room_id);

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }

        $post_url = build_url(array('page' => '_SELF', 'type' => '_edit', 'id' => $myrow['id'], 'room_id' => $room_id), '_SELF');

        $message = get_translated_tempcode('chat_messages', $myrow, 'the_message');

        require_code('form_templates');

        $text_colour = ($myrow['text_colour'] == '') ? get_option('chat_default_post_colour') : $myrow['text_colour'];
        $font_name = ($myrow['font_name'] == '') ? get_option('chat_default_post_font') : $myrow['font_name'];

        $fields = form_input_text_comcode(do_lang_tempcode('MESSAGE'), do_lang_tempcode('DESCRIPTION_MESSAGE'), 'message', $message->evaluate(), true);
        $fields->attach(form_input_line(do_lang_tempcode('CHAT_OPTIONS_COLOUR_NAME'), do_lang_tempcode('CHAT_OPTIONS_COLOUR_DESCRIPTION'), 'text_colour', $text_colour, false));
        $fields->attach(form_input_line(do_lang_tempcode('CHAT_OPTIONS_TEXT_NAME'), do_lang_tempcode('CHAT_OPTIONS_TEXT_DESCRIPTION'), 'fontname', $font_name, false));
        $fields->attach(do_template('FORM_SCREEN_FIELD_SPACER', array('_GUID' => '43ca9d141f23445a018634bdc70f1c7c', 'TITLE' => do_lang_tempcode('ACTIONS'))));
        $fields->attach(form_input_tick(do_lang_tempcode('DELETE'), do_lang_tempcode('DESCRIPTION_DELETE_MESSAGE'), 'delete', false));

        return do_template('FORM_SCREEN', array('_GUID' => 'bf92ecd4d5f923f78bbed4faca6c0cb6', 'HIDDEN' => '', 'TITLE' => $this->title, 'TEXT' => '', 'FIELDS' => $fields, 'URL' => $post_url, 'SUBMIT_ICON' => 'buttons__save', 'SUBMIT_NAME' => do_lang_tempcode('SAVE')));
    }

    /**
     * The actualiser for editing a message.
     *
     * @return tempcode                 The UI.
     */
    public function _chat_edit_message()
    {
        $delete = post_param_integer('delete', 0);
        if ($delete == 1) {
            return $this->_chat_delete_message();
        } else {
            $message_id = get_param_integer('id');

            $room_id = $GLOBALS['SITE_DB']->query_select_value_if_there('chat_messages', 'room_id', array('id' => $message_id));
            if (is_null($room_id)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }
            check_chatroom_access($room_id);

            $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
            if (!array_key_exists(0, $room_details)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }
            $row = $room_details[0];
            $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
            if (!$has_mod_access) {
                access_denied('PRIVILEGE', 'edit_lowrange_content');
            }

            $map = array('text_colour' => preg_replace('#^\##', '', post_param('text_colour')), 'font_name' => post_param('fontname'));
            $map += insert_lang_comcode('the_message', wordfilter_text(post_param('message')), 4);
            $GLOBALS['SITE_DB']->query_update('chat_messages', $map, array('id' => $message_id), '', 1);

            log_it('EDIT_MESSAGE', strval($message_id), post_param('message'));

            decache('side_shoutbox');

            require_code('templates_donext');
            return do_next_manager($this->title, do_lang_tempcode('SUCCESS'),
                null,
                null,
                /* TYPED-ORDERED LIST OF 'LINKS'    */
                null, // Add one
                array('_SELF', array('type' => 'edit', 'id' => $message_id, 'room_id' => $room_id), '_SELF'), // Edit this
                array('_SELF', array('type' => 'room', 'id' => $room_id), '_SELF'), // Edit one
                null, // View this
                array('_SELF', array(), '_SELF'), // View archive
                null, // Add to category
                null, // Add one category
                null, // Edit one category
                null, // Edit this category
                null, // View this category
                /* SPECIALLY TYPED 'LINKS' */
                array(),
                array(),
                array(
                    has_actual_page_access(get_member(), 'admin_chat') ? array('menu/social/chat/chat', array('admin_chat', array('type' => 'browse'), get_module_zone('admin_chat')), do_lang('CHATROOMS')) : null,
                ),
                do_lang('SETUP')
            );
        }
    }

    /**
     * The actualiser for deleting a message.
     *
     * @return tempcode                 The UI.
     */
    public function _chat_delete_message()
    {
        $myrow = $this->myrow;
        $message_id = $this->message_id;

        $room_id = $myrow['room_id'];
        check_chatroom_access($room_id);

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }

        $GLOBALS['SITE_DB']->query_delete('chat_messages', array('id' => $message_id), '', 1);

        decache('side_shoutbox');

        $message2 = get_translated_tempcode('chat_messages', $myrow, 'the_message');
        delete_lang($myrow['the_message']);

        log_it('DELETE_MESSAGE', strval($message_id), $message2->evaluate());

        require_code('templates_donext');
        return do_next_manager($this->title, do_lang_tempcode('SUCCESS'),
            null,
            null,
            /* TYPED-ORDERED LIST OF 'LINKS'  */
            null, // Add one
            null, // Edit this
            array('_SELF', array('type' => 'room', 'id' => $room_id), '_SELF'), // Edit one
            null, // View this
            array('_SELF', array(), '_SELF'), // View archive
            null, // Add to category
            null, // Add one category
            null, // Edit one category
            null, // Edit this category
            null, // View this category
            /* SPECIALLY TYPED 'LINKS' */
            array(
                has_actual_page_access(get_member(), 'admin_chat') ? array('menu/social/chat/chat', array('admin_chat', array('type' => 'browse'), get_module_zone('admin_chat')), do_lang('SETUP')) : null,
            )
        );
    }

    /**
     * The UI for deleting all the messages in a room.
     *
     * @return tempcode                 The UI.
     */
    public function chat_delete_all_messages()
    {
        $id = get_param_integer('id');
        check_chatroom_access($id);

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }

        $fields = new Tempcode();
        require_code('form_templates');
        $fields->attach(form_input_tick(do_lang_tempcode('PROCEED'), do_lang_tempcode('Q_SURE'), 'continue_delete', false));
        $text = paragraph(do_lang_tempcode('CONFIRM_DELETE_ALL_MESSAGES', escape_html(get_chatroom_name($id))));
        $post_url = build_url(array('page' => '_SELF', 'type' => '_delete', 'id' => $id), '_SELF');
        $submit_name = do_lang_tempcode('DELETE');

        return do_template('FORM_SCREEN', array('_GUID' => '31b488e5d4ff52ffd5e097876c0b13c7', 'SKIP_VALIDATION' => true, 'HIDDEN' => '', 'TITLE' => $this->title, 'URL' => $post_url, 'FIELDS' => $fields, 'SUBMIT_ICON' => 'menu___generic_admin__delete', 'SUBMIT_NAME' => $submit_name, 'TEXT' => $text));
    }

    /**
     * The actualiser for deleting all the messages in a room.
     *
     * @return tempcode                 The UI.
     */
    public function _chat_delete_all_messages()
    {
        $delete = post_param_integer('continue_delete', 0);
        if ($delete != 1) {
            $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
            return redirect_screen($this->title, $url, do_lang_tempcode('CANCELLED'));
        } else {
            $room_id = get_param_integer('id');
            check_chatroom_access($room_id);

            $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
            if (!array_key_exists(0, $room_details)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }
            $row = $room_details[0];
            $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
            if (!$has_mod_access) {
                access_denied('PRIVILEGE', 'edit_lowrange_content');
            }

            delete_chat_messages(array('room_id' => $room_id));

            decache('side_shoutbox');

            log_it('DELETE_ALL_MESSAGES', strval($room_id));

            // Redirect
            $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
            return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
        }
    }

    /**
     * The actualiser for deleting all the ticked messages in a room.
     *
     * @return tempcode                 The UI.
     */
    public function _chat_delete_many_messages()
    {
        $room_id = get_param_integer('room_id');
        check_chatroom_access($room_id);

        $room_details = $GLOBALS['SITE_DB']->query_select('chat_rooms', array('*'), array('id' => $room_id), '', 1);
        if (!array_key_exists(0, $room_details)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }
        $row = $room_details[0];
        $has_mod_access = ((has_privilege(get_member(), 'edit_lowrange_content', 'cms_chat', array('chat', $room_id))) || ($row['room_owner'] == get_member()) && (has_privilege(get_member(), 'moderate_my_private_rooms')));
        if (!$has_mod_access) {
            access_denied('PRIVILEGE', 'edit_lowrange_content');
        }

        // Actualiser
        $count = 0;
        foreach (array_keys($_REQUEST) as $key) {
            if (substr($key, 0, 4) == 'del_') {
                delete_chat_messages(array('room_id' => $room_id, 'id' => intval(substr($key, 4))));
                $count++;
            }
        }

        if ($count == 0) {
            warn_exit(do_lang_tempcode('NOTHING_SELECTED'));
        }

        decache('side_shoutbox');

        $num_remaining = $GLOBALS['SITE_DB']->query_select_value('chat_messages', 'COUNT(*)', array('room_id' => $room_id));
        if ($num_remaining == 0) {
            $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        } else {
            $url = build_url(array('page' => '_SELF', 'type' => 'room', 'id' => $room_id, 'start' => get_param_integer('start'), 'max' => get_param_integer('max')), '_SELF');
        }

        // Redirect
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
