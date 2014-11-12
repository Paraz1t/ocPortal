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

/**
 * Standard code module initialisation function.
 */
function init__media_renderer()
{
    define('MEDIA_RECOG_PRECEDENCE_SUPER', 50);
    define('MEDIA_RECOG_PRECEDENCE_HIGH', 40);
    define('MEDIA_RECOG_PRECEDENCE_MEDIUM', 30);
    define('MEDIA_RECOG_PRECEDENCE_LOW', 20);
    define('MEDIA_RECOG_PRECEDENCE_TRIVIAL', 10);
    define('MEDIA_RECOG_PRECEDENCE_NONE', 0);

    define('MEDIA_TYPE_IMAGE', 1);
    define('MEDIA_TYPE_VIDEO', 2);
    define('MEDIA_TYPE_AUDIO', 4);
    define('MEDIA_TYPE_OTHER', 8);
    define('MEDIA_TYPE_ALL', 15);

    define('MEDIA_LOWFI', 1);

    /** Options for media rendering.
     *
     * @global boolean $MEDIA_MODE
     */
    global $MEDIA_MODE;
    $MEDIA_MODE = array(0);
}

/**
 * Set the media mode.
 *
 * @param  integer                      The current media mode
 */
function push_media_mode($m)
{
    global $MEDIA_MODE;
    array_push($MEDIA_MODE, $m);
}

/**
 * Restore the media mode.
 */
function pop_media_mode()
{
    global $MEDIA_MODE;
    array_pop($MEDIA_MODE);
}

/**
 * Return the current media mode.
 *
 * @return integer                      The current media mode
 */
function peek_media_mode()
{
    global $MEDIA_MODE;
    return $MEDIA_MODE[count($MEDIA_MODE) - 1];
}

/**
 * Find a media renderer hook for a URL.
 *
 * @param  URLPATH                      The URL
 * @param  array                        Attributes (e.g. width, height, length)
 * @param  boolean                      Whether there are admin privileges, to render dangerous media types
 * @param  ?MEMBER                      Member to run as (NULL: current member)
 * @param  integer                      Bitmask of media that we will support
 * @param  ?ID_TEXT                     Limit to a media rendering hook (NULL: no limit)
 * @return ?array                       The hooks (NULL: cannot find one)
 */
function find_media_renderers($url, $attributes, $as_admin, $source_member, $acceptable_media = 15, $limit_to = null)
{
    if (is_null($source_member)) {
        $source_member = get_member();
    }
    if (has_privilege($source_member, 'comcode_dangerous')) {
        $as_admin = true;
    }

    $hooks = is_null($limit_to) ? array_keys(find_all_hooks('systems', 'media_rendering')) : array($limit_to);
    $obs = array();
    foreach ($hooks as $hook) {
        if (($limit_to !== null) && ($limit_to != $hook)) {
            continue;
        }

        require_code('hooks/systems/media_rendering/' . $hook);
        $obs[$hook] = object_factory('Hook_media_rendering_' . $hook);
    }

    if (($as_admin) && ($limit_to !== null)) {// Don't check mime-types etc if admin and forced type
        return array($limit_to);
    }

    $found = array();
    $matches = array();
    if ((strpos($url, '/') === false) || (url_is_local($url))) {// Just a local file
        // Unfortunately, just not reliable enough to use always (e.g. http://commons.wikimedia.org/wiki/File:Valmiki_Ramayana.jpg)
        //if (preg_match('#\.(\w+)$#',preg_replace('#\#.*#','',$url)/*trim off hash component*/,$matches)!=0)
        {
            // Find via extension
            require_code('mime_types');
        }
        //$mime_type=get_mime_type($matches[1],$as_admin);
        $mime_type = get_mime_type(get_file_extension($url), $as_admin);
        if ($mime_type != 'application/octet-stream') {
            foreach ($hooks as $hook) {
                if ((method_exists($obs[$hook], 'recognises_mime_type')) && (($acceptable_media & $obs[$hook]->get_media_type()) != 0)) {
                    $result = $obs[$hook]->recognises_mime_type($mime_type);
                    if ($result != 0) {
                        $found[$hook] = $result;
                    }
                }
            }
        }
    }

    // Find via URL recognition
    foreach ($hooks as $hook) {
        if ((method_exists($obs[$hook], 'recognises_url')) && (($acceptable_media & $obs[$hook]->get_media_type()) != 0)) {
            $result = $obs[$hook]->recognises_url($url);
            if ($result != 0) {
                $found[$hook] = $result;
            }
        }
    }
    if (count($found) != 0) {
        arsort($found);
        if (reset($found) >= MEDIA_RECOG_PRECEDENCE_HIGH) {
            return array_keys($found);
        }
    }

    // Find via download (oEmbed / mime-type) - last resort, as it is more 'costly' to do
    require_code('files2');
    $meta_details = get_webpage_meta_details($url);
    if ((array_key_exists('mime_type', $attributes)) && ($attributes['mime_type'] != '')) {
        $mime_type = $attributes['mime_type'];
    } else {
        $mime_type = $meta_details['t_mime_type'];
    }
    if ($meta_details['t_mime_type'] != '') {
        foreach ($hooks as $hook) {
            if ((method_exists($obs[$hook], 'recognises_mime_type')) && (($acceptable_media & $obs[$hook]->get_media_type()) != 0)) {
                $result = $obs[$hook]->recognises_mime_type($mime_type, $meta_details);
                if ($result != 0) {
                    $found[$hook] = $result;
                }
            }
        }
        if (count($found) != 0) {
            arsort($found);
            return array_keys($found);
        }
    }

    return null;
}

/**
 * Render a media URL in the best way we can.
 *
 * @param  mixed                        The URL
 * @param  mixed                        URL to render (no sessions etc)
 * @param  array                        Attributes (e.g. width, height, length). IMPORTANT NOTE: Only pass in 'mime_type' from user data if you have verified privileges to do so, no verification is done within the media API.
 * @param  boolean                      Whether there are admin privileges, to render dangerous media types
 * @param  ?MEMBER                      Member to run as (NULL: current member)
 * @param  integer                      Bitmask of media that we will support
 * @param  ?ID_TEXT                     Limit to a media rendering hook (NULL: no limit)
 * @param  ?URLPATH                     The URL to do media detection against (NULL: use $url)
 * @return ?tempcode                    The rendered version (NULL: cannot render)
 */
function render_media_url($url, $url_safe, $attributes, $as_admin = false, $source_member = null, $acceptable_media = 15, $limit_to = null, $url_to_scan_against = null)
{
    $hooks = find_media_renderers(
        is_null($url_to_scan_against) ? (is_object($url) ? $url->evaluate() : $url) : $url_to_scan_against,
        $attributes,
        $as_admin,
        $source_member,
        $acceptable_media,
        $limit_to
    );
    if (is_null($hooks)) {
        return null;
    }
    $hook = reset($hooks);

    $ob = object_factory('Hook_media_rendering_' . $hook);
    $ret = $ob->render($url, $url, $attributes, $as_admin, $source_member, $url_to_scan_against);

    if (array_key_exists('float', $attributes)) {
        $ret = do_template('FLOATER', array('_GUID' => '26410f89305c16ae9cb17dd02a4a7999', 'FLOAT' => $attributes['float'], 'CONTENT' => $ret));
    }

    return $ret;
}

/**
 * Turn standardised media parameters into standardised media template parameters.
 *
 * @param  mixed                        The URL
 * @param  array                        Attributes (Any combination of: thumb_url, width, height, length, filename, mime_type, description, filesize, framed, wysiwyg_editable, num_downloads, click_url, thumb)
 * @param  boolean                      Whether there are admin privileges, to render dangerous media types
 * @param  ?MEMBER                      Member to run as (NULL: current member)
 * @return array                        Template-ready parameters
 */
function _create_media_template_parameters($url, $attributes, $as_admin = false, $source_member = null)
{
    $_url = is_object($url) ? $url->evaluate() : $url;

    if (is_null($source_member)) {
        $source_member = get_member();
    }
    if (has_privilege($source_member, 'comcode_dangerous')) {
        $as_admin = true;
    }

    // Put in defaults
    $no_width = (!array_key_exists('width', $attributes)) || (!is_numeric($attributes['width']));
    $no_height = (!array_key_exists('height', $attributes)) || (!is_numeric($attributes['height']));
    if ($no_width || $no_height) { // Try and work out the best default width/height, from the thumbnail if possible (image_websafe runs it's own code to do the equivalent, as that defaults to thumb_width rather than attachment_default_width&attachment_default_height)
        $_width = get_option('attachment_default_width');
        $_height = get_option('attachment_default_height');
        if ((function_exists('getimagesize')) && (array_key_exists('thumb_url', $attributes)) && (((is_object($attributes['thumb_url'])) && (!$attributes['thumb_url']->is_empty()) || (is_string($attributes['thumb_url'])) && ($attributes['thumb_url'] != '')))) {
            require_code('images');
            list($_width, $_height) = _symbol_image_dims(array(is_object($attributes['thumb_url']) ? $attributes['thumb_url']->evaluate() : $attributes['thumb_url']));
        }

        if ($no_width) {
            $attributes['width'] = $_width;
        }
        if ($no_height) {
            $attributes['height'] = $_height;
        }
    }
    if ((!array_key_exists('length', $attributes)) || (!is_numeric($attributes['length']))) {
        $attributes['length'] = '';
    }
    if (!array_key_exists('thumb_url', $attributes)) {
        $attributes['thumb_url'] = '';
    }
    if ((!array_key_exists('filename', $attributes)) || ($attributes['filename'] == '')) {
        $attributes['filename'] = urldecode(basename(preg_replace('#\?.*#', '', $_url)));
    }
    if ((!array_key_exists('mime_type', $attributes)) || ($attributes['mime_type'] == '')) {
        // As this is not necessarily a local file, we need to get the mime-type in the formal way.
        //  If this was an uploaded file (i.e. new file in the JS security context) with a dangerous mime type, it would have been blocked by now.
        require_code('files2');
        $meta_details = get_webpage_meta_details($_url);
        $attributes['mime_type'] = $meta_details['t_mime_type'];
    }
    if (!array_key_exists('description', $attributes)) {
        $attributes['description'] = '';
    }
    if ((!array_key_exists('filesize', $attributes)) || (!is_numeric($attributes['filesize']))) {
        $attributes['filesize'] = '';
    }

    // Framing. NB: Framed is not used by media types that imply their own framing (e.g. external videos)
    $framed = ((!array_key_exists('framed', $attributes)) || ($attributes['framed'] != '0'));

    $wysiwyg_editable = ((array_key_exists('wysiwyg_editable', $attributes)) && ($attributes['wysiwyg_editable'] != '0'));

    $use_thumb = (!array_key_exists('thumb', $attributes)) || ($attributes['thumb'] == '1');

    if (($_url != '') && (url_is_local($_url))) {
        if (is_file(get_custom_file_base() . '/' . urldecode($_url))) {
            $_url = get_custom_base_url() . '/' . $_url;
        } else {
            $_url = get_base_url() . '/' . $_url;
        }
        $url = $_url;
    }
    if ((is_string($attributes['thumb_url'])) && ($attributes['thumb_url'] != '') && (url_is_local($attributes['thumb_url']))) {
        if (is_file(get_custom_file_base() . '/' . urldecode($_url))) {
            $attributes['thumb_url'] = get_custom_base_url() . '/' . $attributes['thumb_url'];
        } else {
            $attributes['thumb_url'] = get_base_url() . '/' . $attributes['thumb_url'];
        }
    }

    // Put together template parameters
    return array(
        'URL' => $url,
        'REMOTE_ID' => array_key_exists('remote_id', $attributes) ? $attributes['remote_id'] : '',
        'THUMB_URL' => $attributes['thumb_url'],
        'FILENAME' => $attributes['filename'],
        'MIME_TYPE' => $attributes['mime_type'],
        'CLICK_URL' => array_key_exists('click_url', $attributes) ? $attributes['click_url'] : null,

        'WIDTH' => $attributes['width'],
        'HEIGHT' => $attributes['height'],

        'LENGTH' => $attributes['length'],

        'FILESIZE' => $attributes['filesize'],
        'CLEAN_FILESIZE' => is_numeric($attributes['filesize']) ? clean_file_size(intval($attributes['filesize'])) : '',

        'THUMB' => $use_thumb,
        'FRAMED' => $framed,
        'WYSIWYG_EDITABLE' => $wysiwyg_editable,
        'NUM_DOWNLOADS' => array_key_exists('num_downloads', $attributes) ? $attributes['num_downloads'] : null,
        'DESCRIPTION' => comcode_to_tempcode($attributes['description'], $source_member, $as_admin),
    );
}

abstract class Media_renderer_with_fallback
{
    /**
     * If we are rendering in low-fi, result to simple image fall-back.
     *
     * @param  mixed                    URL to render
     * @param  mixed                    URL to render (no sessions etc)
     * @param  array                    Attributes (e.g. width, height, length)
     * @param  boolean                  Whether there are admin privileges, to render dangerous media types
     * @param  ?MEMBER                  Member to run as (NULL: current member)
     * @param  mixed                    URL to route clicks through to
     * @return ?tempcode                Rendered version (NULL: do not render)
     */
    public function fallback_render($url, $url_safe, $attributes, $as_admin, $source_member, $click_url = null)
    {
        if ((peek_media_mode() & MEDIA_LOWFI) != 0) {
            // Work out where to direct links to
            if (!empty($GLOBALS['TEMPCODE_SETGET']['comcode__current_linking_context'])) {
                // Tempcode has specified
                $attributes['click_url'] = $GLOBALS['TEMPCODE_SETGET']['comcode__current_linking_context'];
                if ($attributes['click_url'] == '-') {
                    // Special notation indicating to not use a link, i.e. an explicit "no link"
                    $attributes['click_url'] = '';
                }
            } else {
                // Natural link
                if (!is_null($click_url)) {
                    $attributes['click_url'] = $click_url;
                } // Else: no link
            }

            // Thumbnail?
            if (method_exists($this, 'get_video_thumbnail')) {
                $test = $this->get_video_thumbnail($url);
                if ($test !== null) {
                    $url = $test;
                    $url_safe = $test;
                    $attributes['thumb'] = '0'; // Don't re-thumbnail
                }
            }

            // Render as image
            require_code('hooks/systems/media_rendering/image_websafe');
            $ob = new Hook_media_rendering_image_websafe();
            $attributes['framed'] = '0';
            return $ob->render($url, $url_safe, $attributes, $as_admin, $source_member);
        }

        return null;
    }
}
