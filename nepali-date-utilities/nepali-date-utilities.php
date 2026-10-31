<?php
/*
Plugin Name: Nepali Date Utilities
Description: Convert English dates to Nepali (BS), display post/comment dates, today’s date shortcode/block, and Eng↔Nep conversion tools.
Version: 1.1.0
Author: Ashok Basnet
Author URI: http://ashokbasnet.com.np
Text Domain: nepali-date-utilities
Requires at least: 5.0
Requires PHP: 7.4
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('NPD_BASENAME')) {
    define('NPD_BASENAME', plugin_basename(__FILE__));
}
if (!defined('NPD_PLUGIN_URL')) {
    define('NPD_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('NPD_PLUGIN_DIR')) {
    define('NPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('NDU_VERSION')) {
    define('NDU_VERSION', '1.1.0');
}

/**
 * Load plugin classes and textdomain.
 */
function ndu_init()
{
    load_plugin_textdomain('nepali-date-utilities', false, dirname(NPD_BASENAME) . '/languages');

    require_once NPD_PLUGIN_DIR . 'class.nepali.date.php';
    require_once NPD_PLUGIN_DIR . 'class.todaydate.php';
    require_once NPD_PLUGIN_DIR . 'includes/class-post-calendar.php';

    if (is_admin()) {
        require_once NPD_PLUGIN_DIR . 'includes/admin-settings.php';
    }
}
add_action('plugins_loaded', 'ndu_init', 15);

/**
 * Register front-end date filters once; gate conversion at runtime.
 */
function ndu_register_date_filters()
{
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    add_filter('get_the_date', 'ndu_convert_to_nepali_date_time', 10, 3);
    add_filter('the_date', 'ndu_convert_to_nepali_date_time', 10, 3);
    add_filter('get_the_time', 'ndu_convert_to_nepali_date_time', 10, 3);
    add_filter('the_time', 'ndu_convert_to_nepali_date_time', 10, 3);

    add_filter('the_modified_time', 'ndu_convert_to_nepali_date_time_modified', 10, 3);
    add_filter('get_the_modified_time', 'ndu_convert_to_nepali_date_time_modified', 10, 3);
    add_filter('the_modified_date', 'ndu_convert_to_nepali_date_time_modified', 10, 3);
    add_filter('get_the_modified_date', 'ndu_convert_to_nepali_date_time_modified', 10, 3);

    add_filter('get_comment_date', 'ndu_convert_comment_date', 10, 3);
    add_filter('get_comment_time', 'ndu_convert_comment_time', 10, 5);
}
add_action('init', 'ndu_register_date_filters');

/**
 * Whether post/comment date conversion is enabled.
 *
 * @return bool
 */
function ndu_is_conversion_enabled()
{
    return get_option('ndu_convert_post_date', '1') === '1';
}

/**
 * Convert ASCII digits to Devanagari Unicode when numerals option is nepali.
 *
 * @param string $value Value containing digits.
 * @param string $numerals 'nepali' or 'arabic'.
 * @return string
 */
function ndu_convert_unicode_number($value = '', $numerals = null)
{
    if ($numerals === null) {
        $numerals = get_option('ndu_numerals', 'nepali');
    }
    if ($numerals !== 'nepali') {
        return (string) $value;
    }

    $map = array(
        '0' => '०',
        '1' => '१',
        '2' => '२',
        '3' => '३',
        '4' => '४',
        '5' => '५',
        '6' => '६',
        '7' => '७',
        '8' => '८',
        '9' => '९',
    );
    return strtr((string) $value, $map);
}

/**
 * Nepali period-of-day label for hour (0-23).
 *
 * @param int $hour Hour of day.
 * @return string
 */
function ndu_period_of_day($hour)
{
    $hour = (int) $hour;
    if ($hour >= 4 && $hour < 12) {
        return 'बिहान';
    }
    if ($hour >= 12 && $hour < 17) {
        return 'दिउँसो';
    }
    if ($hour >= 17 && $hour < 20) {
        return 'साँझ';
    }
    return 'राति';
}

/**
 * Shared Nepali date formatter.
 *
 * @param int   $timestamp Unix timestamp.
 * @param string $format   Token format (y m d l H/h i s a).
 * @param array  $args {
 *     @type string $numerals      'nepali'|'arabic'
 *     @type string $display_mode  'nepali'|'bilingual'
 *     @type bool   $apply_home    Whether to apply home page behavior.
 * }
 * @return string
 */
function ndu_format_nepali_date($timestamp, $format = '', $args = array())
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    $defaults = array(
        'numerals'     => get_option('ndu_numerals', 'nepali'),
        'display_mode' => get_option('ndu_display_mode', 'nepali'),
        'apply_home'   => false,
    );
    $args = wp_parse_args($args, $defaults);

    if ($args['apply_home'] && (is_home() || is_front_page())) {
        $home_mode = get_option('ndu_date_format_for_home', '0');
        if ($home_mode === '1') {
            return ndu_convert_unicode_number(
                ndu_human_time_diff_nepali($timestamp, current_time('timestamp')) . ' अघि',
                $args['numerals']
            );
        }
        if ($home_mode === '2') {
            return '';
        }
    }

    if ($format === '') {
        $format = get_option('ndu_date_format', 'y m d, l H:i');
    }

    $converter = new Nepali_Date();
    // Site-local components via wp_date (WP 5.3+).
    $y = (int) wp_date('Y', $timestamp);
    $m = (int) wp_date('n', $timestamp);
    $d = (int) wp_date('j', $timestamp);
    $H = (int) wp_date('G', $timestamp);
    $h = (int) wp_date('g', $timestamp);
    $i = wp_date('i', $timestamp);
    $s = wp_date('s', $timestamp);
    $eng_ymd = wp_date('Y-m-d', $timestamp);

    $nepali = $converter->eng_to_nep($y, $m, $d);
    if ($nepali === false) {
        return $eng_ymd;
    }

    $use_nepali_nums = ($args['numerals'] === 'nepali');
    $num = static function ($value) use ($use_nepali_nums, $converter) {
        return $use_nepali_nums ? $converter->convert_to_nepali_number($value) : (string) $value;
    };

    $nepali_year   = $num($nepali['year']);
    $nepali_month  = $nepali['nmonth'];
    $nepali_day    = $nepali['day'];
    $nepali_date   = $num($nepali['date']);
    $nepali_minute = $num($i);
    $nepali_second = $num($s);
    $nepali_ampm   = ndu_period_of_day($H);

    if (strpos($format, 'H') !== false) {
        $converted = str_replace(
            array('l', 'd', 'm', 'y', 'H', 'i', 's', 'a'),
            array($nepali_day, $nepali_date, $nepali_month, $nepali_year, $num($H), $nepali_minute, $nepali_second, $nepali_ampm),
            $format
        );
    } else {
        $converted = str_replace(
            array('l', 'd', 'm', 'y', 'h', 'i', 's', 'a'),
            array($nepali_day, $nepali_date, $nepali_month, $nepali_year, $num($h), $nepali_minute, $nepali_second, $nepali_ampm),
            $format
        );
    }

    if ($args['display_mode'] === 'bilingual') {
        $converted .= ' (' . $eng_ymd . ')';
    }

    return $converted;
}

/**
 * Human time difference in Nepali units.
 *
 * @param int $from From timestamp.
 * @param int $to   To timestamp.
 * @return string
 */
function ndu_human_time_diff_nepali($from, $to = '')
{
    if ($to === '' || $to === null) {
        $to = time();
    }

    $diff = (int) abs($to - $from);

    if ($diff < HOUR_IN_SECONDS) {
        $mins = round($diff / MINUTE_IN_SECONDS);
        if ($mins <= 1) {
            $mins = 1;
        }
        $since = sprintf(_n('%s मिनेट', '%s मिनेट', $mins, 'nepali-date-utilities'), $mins);
    } elseif ($diff < DAY_IN_SECONDS) {
        $hours = round($diff / HOUR_IN_SECONDS);
        if ($hours <= 1) {
            $hours = 1;
        }
        $since = sprintf(_n('%s घण्टा', '%s घण्टा', $hours, 'nepali-date-utilities'), $hours);
    } elseif ($diff < WEEK_IN_SECONDS) {
        $days = round($diff / DAY_IN_SECONDS);
        if ($days <= 1) {
            $days = 1;
        }
        $since = sprintf(_n('%s दिन', '%s दिन', $days, 'nepali-date-utilities'), $days);
    } elseif ($diff < MONTH_IN_SECONDS) {
        $weeks = round($diff / WEEK_IN_SECONDS);
        if ($weeks <= 1) {
            $weeks = 1;
        }
        $since = sprintf(_n('%s हप्ता', '%s हप्ता', $weeks, 'nepali-date-utilities'), $weeks);
    } elseif ($diff < YEAR_IN_SECONDS) {
        $months = round($diff / MONTH_IN_SECONDS);
        if ($months <= 1) {
            $months = 1;
        }
        $since = sprintf(_n('%s महिना', '%s महिना', $months, 'nepali-date-utilities'), $months);
    } else {
        $years = round($diff / YEAR_IN_SECONDS);
        if ($years <= 1) {
            $years = 1;
        }
        $since = sprintf(_n('%s वर्ष', '%s वर्ष', $years, 'nepali-date-utilities'), $years);
    }

    return apply_filters('ndu_human_time_diff_nepali', $since, $diff, $from, $to);
}

/**
 * Echo relative time for the current post (legacy helper).
 */
function ndu_date_ago()
{
    echo esc_html(ndu_get_date_ago());
}

/**
 * Return relative time for the current post.
 *
 * @param int|null $timestamp Optional timestamp.
 * @return string
 */
function ndu_get_date_ago($timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = get_the_time('U');
    }
    return ndu_convert_unicode_number(
        ndu_human_time_diff_nepali((int) $timestamp, current_time('timestamp')) . ' अघि'
    );
}

/**
 * Today's Nepali date string.
 *
 * @param string|null $format Optional format override.
 * @return string
 */
function ndu_today_date($format = null)
{
    if ($format === null || $format === '') {
        $format = get_option('ndu_today_date_format', 'y m d');
    }

    return ndu_format_nepali_date(
        current_time('timestamp'),
        $format,
        array(
            'apply_home' => false,
        )
    );
}

/**
 * Shortcode: today's date. Supports format attribute.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function ndu_today_date_shortcode($atts = array())
{
    $atts = shortcode_atts(
        array(
            'format' => '',
        ),
        $atts,
        'ndu_today_date'
    );

    $format = $atts['format'] !== '' ? $atts['format'] : null;
    return esc_html(ndu_today_date($format));
}
add_shortcode('ndu_today_date', 'ndu_today_date_shortcode');

/**
 * Shortcode: relative time ago for current post.
 *
 * @param array|string $atts Unused.
 * @return string
 */
function ndu_date_ago_shortcode($atts = array())
{
    return esc_html(ndu_get_date_ago());
}
add_shortcode('ndu_date_ago', 'ndu_date_ago_shortcode');

/**
 * Shortcode: convert a specific date Eng↔Nep.
 *
 * Usage: [ndu_convert date="2024-10-31" from="eng" format="y m d"]
 *        [ndu_convert date="2081-07-15" from="nep" format="Y-m-d"]
 *
 * @param array|string $atts Attributes.
 * @return string
 */
function ndu_convert_shortcode($atts = array())
{
    $atts = shortcode_atts(
        array(
            'date'   => '',
            'from'   => 'eng',
            'format' => '',
        ),
        $atts,
        'ndu_convert'
    );

    $date_str = trim($atts['date']);
    if ($date_str === '') {
        return '';
    }

    $from = strtolower($atts['from']) === 'nep' ? 'nep' : 'eng';
    $parts = preg_split('/[-\/.\s]+/', $date_str);
    if (count($parts) < 3) {
        return '';
    }

    $y = (int) $parts[0];
    $m = (int) $parts[1];
    $d = (int) $parts[2];
    $converter = new Nepali_Date();

    if ($from === 'eng') {
        $format = $atts['format'] !== '' ? $atts['format'] : get_option('ndu_today_date_format', 'y m d');
        $ts = mktime(12, 0, 0, $m, $d, $y);
        if ($ts === false) {
            return '';
        }
        return esc_html(ndu_format_nepali_date($ts, $format, array('apply_home' => false)));
    }

    $eng = $converter->nep_to_eng($y, $m, $d);
    if ($eng === false) {
        return '';
    }

    $format = $atts['format'] !== '' ? $atts['format'] : 'Y-m-d';
    $ts = mktime(12, 0, 0, (int) $eng['month'], (int) $eng['date'], (int) $eng['year']);
    if ($ts === false) {
        return '';
    }

    // For nep→eng with a Nepali token format, format as Nepali; otherwise use PHP date.
    if (preg_match('/[ymdl]/', $format) && !preg_match('/[YFj]/', $format)) {
        return esc_html(ndu_format_nepali_date($ts, $format, array('apply_home' => false, 'display_mode' => 'nepali')));
    }

    return esc_html(date($format, $ts));
}
add_shortcode('ndu_convert', 'ndu_convert_shortcode');

/**
 * Resolve a post from filter args (get_* pass post; the_* may pass $before).
 *
 * @param mixed $post Candidate post, ID, or unrelated value.
 * @return WP_Post|null
 */
function ndu_resolve_post($post = null)
{
    if ($post instanceof WP_Post) {
        return $post;
    }
    if (is_numeric($post)) {
        $resolved = get_post((int) $post);
        return $resolved instanceof WP_Post ? $resolved : null;
    }
    $resolved = get_post();
    return $resolved instanceof WP_Post ? $resolved : null;
}

/**
 * Convert post published date/time.
 *
 * @param string      $orig_time Original formatted date.
 * @param string      $format    Requested format (plugin uses its own option).
 * @param mixed       $post      Post object/ID, or unrelated arg from the_* filters.
 * @return string
 */
function ndu_convert_to_nepali_date_time($orig_time, $format = '', $post = null)
{
    if (!ndu_is_conversion_enabled()) {
        return $orig_time;
    }

    $post = ndu_resolve_post($post);
    if (!$post) {
        return $orig_time;
    }

    $post_date = get_post_time('U', true, $post);
    if (!$post_date) {
        return $orig_time;
    }

    return ndu_format_nepali_date(
        (int) $post_date,
        get_option('ndu_date_format', 'y m d, l H:i'),
        array('apply_home' => true)
    );
}

/**
 * Convert post modified date/time.
 *
 * @param string $orig_time Original value.
 * @param string $format    Format.
 * @param mixed  $post      Post or unrelated arg.
 * @return string
 */
function ndu_convert_to_nepali_date_time_modified($orig_time, $format = '', $post = null)
{
    if (!ndu_is_conversion_enabled()) {
        return $orig_time;
    }

    $post = ndu_resolve_post($post);
    if (!$post) {
        return $orig_time;
    }

    if ($post->post_date === $post->post_modified) {
        return '';
    }

    $post_date = get_post_modified_time('U', true, $post);
    if (!$post_date) {
        return $orig_time;
    }

    return ndu_format_nepali_date(
        (int) $post_date,
        get_option('ndu_date_format', 'y m d, l H:i'),
        array('apply_home' => true)
    );
}

/**
 * Convert comment date.
 *
 * @param string     $date    Formatted date.
 * @param string     $format  Format.
 * @param WP_Comment $comment Comment object.
 * @return string
 */
function ndu_convert_comment_date($date, $format, $comment)
{
    if (!ndu_is_conversion_enabled()) {
        return $date;
    }

    if (!($comment instanceof WP_Comment) || empty($comment->comment_date)) {
        return $date;
    }

    $ts = strtotime($comment->comment_date);
    if ($ts === false) {
        return $date;
    }

    return ndu_format_nepali_date(
        $ts,
        get_option('ndu_date_format', 'y m d, l H:i'),
        array('apply_home' => false)
    );
}

/**
 * Convert comment time.
 *
 * @param string     $date     Formatted time.
 * @param string     $format   Format.
 * @param bool       $gmt      GMT flag.
 * @param bool       $translate Translate flag.
 * @param WP_Comment $comment  Comment.
 * @return string
 */
function ndu_convert_comment_time($date, $format, $gmt, $translate, $comment)
{
    if (!ndu_is_conversion_enabled()) {
        return $date;
    }

    if (!($comment instanceof WP_Comment)) {
        return $date;
    }

    $raw = $gmt ? $comment->comment_date_gmt : $comment->comment_date;
    if (empty($raw)) {
        return $date;
    }

    $ts = strtotime($raw);
    if ($ts === false) {
        return $date;
    }

    $time_format = get_option('ndu_date_format', 'y m d, l H:i');
    // Prefer time tokens if the stored format includes them; else use H:i a.
    if (strpos($time_format, 'H') === false && strpos($time_format, 'h') === false) {
        $time_format = 'H:i a';
    }

    return ndu_format_nepali_date(
        $ts,
        $time_format,
        array('apply_home' => false)
    );
}

/**
 * Legacy alias used by older code paths.
 *
 * @param int $post_date Timestamp.
 * @return string
 */
function ndu_convert_post_date_common($post_date)
{
    return ndu_format_nepali_date(
        (int) $post_date,
        get_option('ndu_date_format', 'y m d, l H:i'),
        array('apply_home' => true)
    );
}

/**
 * Register Gutenberg blocks (PHP render, no build step).
 */
function ndu_register_blocks()
{
    $script_deps = array(
        'wp-blocks',
        'wp-element',
        'wp-block-editor',
        'wp-components',
        'wp-i18n',
        'wp-server-side-render',
    );

    $today_dir = NPD_PLUGIN_DIR . 'blocks/today-date';
    if (file_exists($today_dir . '/block.json')) {
        wp_register_script(
            'ndu-today-date-editor',
            NPD_PLUGIN_URL . 'blocks/today-date/index.js',
            $script_deps,
            NDU_VERSION,
            true
        );

        register_block_type($today_dir, array(
            'editor_script'   => 'ndu-today-date-editor',
            'render_callback' => 'ndu_render_today_date_block',
        ));
    }

    $calendar_dir = NPD_PLUGIN_DIR . 'blocks/post-calendar';
    if (file_exists($calendar_dir . '/block.json')) {
        wp_register_script(
            'ndu-post-calendar-editor',
            NPD_PLUGIN_URL . 'blocks/post-calendar/index.js',
            $script_deps,
            NDU_VERSION,
            true
        );

        register_block_type($calendar_dir, array(
            'editor_script'   => 'ndu-post-calendar-editor',
            'style'           => 'ndu-calendar',
            'render_callback' => 'ndu_render_post_calendar_block',
        ));

        // Ensure style handle exists for block editor / front.
        wp_register_style(
            'ndu-calendar',
            NPD_PLUGIN_URL . 'assets/calendar.css',
            array(),
            NDU_VERSION
        );
    }
}
add_action('init', 'ndu_register_blocks');

/**
 * Enqueue calendar script in block editor for interactive preview nav.
 */
function ndu_enqueue_block_editor_assets()
{
    ndu_enqueue_calendar_assets();
}
add_action('enqueue_block_editor_assets', 'ndu_enqueue_block_editor_assets');
/**
 * Render callback for Nepali Today Date block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function ndu_render_today_date_block($attributes)
{
    $format = isset($attributes['format']) && $attributes['format'] !== ''
        ? $attributes['format']
        : get_option('ndu_today_date_format', 'y m d');

    $class = 'ndu-today-date-block';
    if (!empty($attributes['className'])) {
        $class .= ' ' . $attributes['className'];
    }

    return sprintf(
        '<div class="%1$s">%2$s</div>',
        esc_attr($class),
        esc_html(ndu_today_date($format))
    );
}

/**
 * Settings link on plugins list.
 *
 * @param array $links Existing links.
 * @return array
 */
function ndu_add_settings_link($links)
{
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=nepali-date-utilities')) . '">' . esc_html__('Settings', 'nepali-date-utilities') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ndu_add_settings_link');
