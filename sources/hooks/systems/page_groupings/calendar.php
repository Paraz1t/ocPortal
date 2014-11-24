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
 * @package    calendar
 */

/**
 * Hook class.
 */
class Hook_page_groupings_calendar
{
    /**
     * Run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
     *
     * @param  ?MEMBER                  Member ID to run as (null: current member)
     * @param  boolean                  Whether to use extensive documentation tooltips, rather than short summaries
     * @return array                    List of tuple of links (page grouping, icon, do-next-style linking data), label, help (optional) and/or nulls
     */
    public function run($member_id = null, $extensive_docs = false)
    {
        if (!addon_installed('calendar')) {
            return array();
        }

        return array(
            array('cms', 'menu/rich_content/calendar', array('cms_calendar', array('type' => 'browse'), get_module_zone('cms_calendar')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('calendar:CALENDAR'), make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('calendar_events', 'COUNT(*)', null, '', true))))), 'calendar:DOC_CALENDAR'),
            array('social', 'menu/rich_content/calendar', array('calendar', array(), get_module_zone('calendar')), do_lang_tempcode('calendar:CALENDAR')),
        );
    }
}
