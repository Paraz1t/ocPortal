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
 * @package    wiki
 */

/**
 * Hook class.
 */
class Hook_page_groupings_wiki
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
        if (!addon_installed('wiki')) {
            return array();
        }

        return array(
            array('cms', 'menu/rich_content/wiki', array('cms_wiki', array('type' => 'browse'), get_module_zone('cms_wiki')), do_lang_tempcode('ITEMS_HERE', do_lang_tempcode('wiki:WIKI'), make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_select_value_if_there('wiki_pages', 'COUNT(*)', null, '', true))))), 'wiki:DOC_WIKI'),
            array('rich_content', 'menu/rich_content/wiki', array('wiki', array(), get_module_zone('wiki')), do_lang_tempcode('wiki:WIKI')),
        );
    }
}
