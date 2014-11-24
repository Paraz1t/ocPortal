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
 * @package    awards
 */

/**
 * Module page class.
 */
class Module_awards
{
    /**
     * Find details of the module.
     *
     * @return ?array                   Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
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
        if ($GLOBALS['SITE_DB']->query_select_value('award_types', 'COUNT(*)') == 0) {
            return array();
        }
        return array(
            'browse' => array('AWARDS', 'menu/adminzone/setup/awards'),
            'overview' => array('AWARD_OVERVIEW', 'menu/_generic_admin/view_archive'),
        );
    }

    public $title;
    public $id;
    public $award_type_row;
    public $ob;
    public $info;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?tempcode                Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param('type', 'browse');

        require_lang('awards');

        if ($type == 'browse') {
            $this->title = get_screen_title('AWARDS');
        }

        if ($type == 'award') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('AWARDS'))));

            $id = get_param_integer('id');
            $_award_type_row = $GLOBALS['SITE_DB']->query_select('award_types', array('*'), array('id' => $id), '', 1);
            if (!array_key_exists(0, $_award_type_row)) {
                warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
            }
            $award_type_row = $_award_type_row[0];
            require_code('content');
            $ob = get_content_object($award_type_row['a_content_type']);
            $info = $ob->info();
            if (is_null($info)) {
                fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
            }

            $this->title = get_screen_title('_AWARD', true, array(escape_html(get_translated_text($award_type_row['a_title']))));

            $this->id = $id;
            $this->award_type_row = $award_type_row;
            $this->ob = $ob;
            $this->info = $info;
        }

        if ($type == 'overview') {
            $this->title = get_screen_title('AWARD_OVERVIEW');
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
        require_code('awards');

        // What are we doing?
        $type = get_param('type', 'browse');

        if ($type == 'browse') {
            return $this->choose_award();
        }
        if ($type == 'award') {
            return $this->award();
        }
        if ($type == 'overview') {
            return $this->award_overview();
        }

        return new Tempcode();
    }

    /**
     * The UI to choose an award type to view.
     *
     * @return tempcode                 The UI
     */
    public function choose_award()
    {
        $rows = $GLOBALS['SITE_DB']->query_select('award_types', array('*'));
        $out = new Tempcode();
        foreach ($rows as $myrow) {
            if ((!file_exists(get_file_base() . '/sources/hooks/systems/content_meta_aware/' . filter_naughty_harsh($myrow['a_content_type']) . '.php')) && (!file_exists(get_file_base() . '/sources_custom/hooks/systems/content_meta_aware/' . filter_naughty_harsh($myrow['a_content_type']) . '.php'))) {
                continue;
            }

            require_code('content');
            $ob = get_content_object($myrow['a_content_type']);
            $info = $ob->info();
            if (!is_null($info)) {
                $url = build_url(array('page' => '_SELF', 'type' => 'award', 'id' => $myrow['id']), '_SELF');
                $_title = get_translated_text($myrow['a_title']);
                $description = get_translated_tempcode('award_types', $myrow, 'a_description');

                $out->attach(do_template('INDEX_SCREEN_FANCIER_ENTRY', array('_GUID' => '0974df260d7521edebf33f5397cab7f4', 'NAME' => $_title, 'URL' => $url, 'DESCRIPTION' => $description, 'TITLE' => '')));
            }
        }

        $add_url = new Tempcode();
        if (has_actual_page_access(get_member(), 'admin_awards')) {
            $add_url = build_url(array('page' => 'admin_awards', 'type' => 'add'), get_module_zone('admin_awards'));
        }

        return do_template('INDEX_SCREEN_FANCIER_SCREEN', array('_GUID' => 'c8351f627333434d426db3b9ffe09d1c', 'ADD_URL' => $add_url, 'PRE' => '', 'POST' => '', 'TITLE' => $this->title, 'CONTENT' => $out));
    }

    /**
     * The UI to view the overview of all current award allocations.
     *
     * @return tempcode                 The UI
     */
    public function award_overview()
    {
        $award_types = $GLOBALS['SITE_DB']->query_select('award_types', array('*'));

        $content = new Tempcode();

        require_code('content');

        foreach ($award_types as $award_type_row) {
            require_code('content');
            $ob = get_content_object($award_type_row['a_content_type']);
            $info = $ob->info();
            if (is_null($info)) {
                continue;
            }

            $_title = get_translated_text($award_type_row['a_title']);
            $description = paragraph(get_translated_tempcode('award_types', $award_type_row, 'a_description'), 'grdgdfghdfgodfs');

            $rows = $GLOBALS['SITE_DB']->query_select('award_archive', array('*'), array('a_type_id' => $award_type_row['id']), 'ORDER BY date_and_time DESC', 1);
            foreach ($rows as $myrow) {
                $award_content_row = content_get_row($myrow['content_id'], $info);

                if (!is_null($award_content_row)) {
                    $rendered_content = $ob->run($award_content_row, '_SEARCH', false, true);

                    if (($award_type_row['a_hide_awardee'] == 1) || (is_guest($myrow['member_id']))) {
                        $awardee = '';
                        $awardee_username = '';
                        $awardee_profile_url = '';
                    } else {
                        $awardee = strval($myrow['member_id']);
                        $awardee_username = $GLOBALS['FORUM_DRIVER']->get_username($myrow['member_id']);
                        if (is_null($awardee_username)) {
                            $awardee_username = do_lang('UNKNOWN');
                        }
                        $awardee_profile_url = $GLOBALS['FORUM_DRIVER']->member_profile_url($myrow['member_id'], true, true);
                    }

                    $rendered = do_template('AWARDED_CONTENT', array(
                        '_GUID' => '1a2a5b6e9b53a99e303b7ed17070cea9',
                        'AWARDEE_PROFILE_URL' => $awardee_profile_url,
                        'AWARDEE' => $awardee,
                        'AWARDEE_USERNAME' => $awardee_username,
                        'RAW_AWARD_DATE' => strval($myrow['date_and_time']),
                        'AWARD_DATE' => get_timezoned_date($myrow['date_and_time']),
                        'CONTENT' => $rendered_content,
                    ));
                    $archive_url = build_url(array('page' => '_SELF', 'type' => 'award', 'id' => $award_type_row['id']), '_SELF');
                    $content->attach(do_template('INDEX_SCREEN_FANCIER_ENTRY', array('_GUID' => 'edd7305b3a9e7777951d0cf04a9360a3', 'URL' => $archive_url, 'TITLE' => $_title, 'NAME' => $_title, 'DESCRIPTION' => $rendered)));
                }
            }
        }

        return do_template('INDEX_SCREEN_FANCIER_SCREEN', array('_GUID' => '4d705418b837db3dc992de95c3b93f71', 'TITLE' => $this->title, 'PRE' => do_lang_tempcode('DESCRIPTION_AWARD_OVERVIEW'), 'CONTENT' => $content, 'POST' => ''));
    }

    /**
     * The UI to view the archive for an award type.
     *
     * @return tempcode                 The UI
     */
    public function award()
    {
        $id = $this->id;
        $award_type_row = $this->award_type_row;
        $ob = $this->ob;
        $info = $this->info;

        $start = get_param_integer('award_start', 0);
        $max = get_param_integer('award_max', intval(get_option('awarded_items_per_page')));

        require_css('awards');

        $description = paragraph(get_translated_tempcode('award_types', $award_type_row, 'a_description'), 'grdgdfghdfgodfs');

        $rows = $GLOBALS['SITE_DB']->query_select('award_archive', array('*'), array('a_type_id' => $id), 'ORDER BY date_and_time DESC', $max, $start);
        $max_rows = $GLOBALS['SITE_DB']->query_select_value('award_archive', 'COUNT(*)', array('a_type_id' => $id));
        $content = new Tempcode();
        foreach ($rows as $myrow) {
            require_code('content');
            $award_content_row = content_get_row($myrow['content_id'], $info);

            if (!is_null($award_content_row)) {
                $rendered_content = $ob->run($award_content_row, '_SEARCH', false, true);

                if (($award_type_row['a_hide_awardee'] == 1) || (is_guest($myrow['member_id']))) {
                    $awardee = '';
                    $awardee_username = '';
                    $awardee_profile_url = '';
                } else {
                    $awardee = strval($myrow['member_id']);
                    $awardee_username = $GLOBALS['FORUM_DRIVER']->get_username($myrow['member_id']);
                    if (is_null($awardee_username)) {
                        $awardee_username = do_lang('UNKNOWN');
                    }
                    $awardee_profile_url = $GLOBALS['FORUM_DRIVER']->member_profile_url($myrow['member_id'], false, true);
                }

                $content->attach(do_template('AWARDED_CONTENT', array(
                    '_GUID' => '67678ff081cb5996fd52cb369d946cf2',
                    'AWARDEE_PROFILE_URL' => $awardee_profile_url,
                    'AWARDEE' => $awardee,
                    'AWARDEE_USERNAME' => $awardee_username,
                    'RAW_AWARD_DATE' => strval($myrow['date_and_time']),
                    'AWARD_DATE' => get_timezoned_date($myrow['date_and_time'], false, false, false, true),
                    'CONTENT' => $rendered_content,
                )));
            }
        }
        if ($content->is_empty()) {
            if (has_category_access(get_member(), 'award', strval($id))) {
                inform_exit(do_lang_tempcode('NO_ENTRIES_AWARDS', do_lang_tempcode($info['content_type_label'])));
            }
            inform_exit(do_lang_tempcode('NO_ENTRIES'));
        }

        require_code('templates_pagination');
        $pagination = pagination(do_lang_tempcode('AWARD_HISTORY'), $start, 'award_start', $max, 'award_max', $max_rows);

        $sub_title = do_lang_tempcode('AWARD_HISTORY');

        $tpl = do_template('PAGINATION_SCREEN', array('_GUID' => 'b9cf3a37300aced490003f79d7bb4914', 'TITLE' => $this->title, 'SUB_TITLE' => $sub_title, 'DESCRIPTION' => $description, 'CONTENT' => $content, 'PAGINATION' => $pagination));

        require_code('templates_internalise_screen');
        return internalise_own_screen($tpl);
    }
}
