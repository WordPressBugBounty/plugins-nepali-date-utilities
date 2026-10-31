<?php
/**
 * Admin settings for Nepali Date Utilities (Settings API).
 *
 * @package Nepali_Date_Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register settings, sections, and fields.
 */
function ndu_register_settings()
{
    register_setting('ndu_settings_group', 'ndu_convert_post_date', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_checkbox',
        'default'           => '1',
    ));
    register_setting('ndu_settings_group', 'ndu_date_format', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_format',
        'default'           => 'y m d, l H:i',
    ));
    register_setting('ndu_settings_group', 'ndu_today_date_format', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_format',
        'default'           => 'y m d',
    ));
    register_setting('ndu_settings_group', 'ndu_date_format_for_home', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_home_mode',
        'default'           => '0',
    ));
    register_setting('ndu_settings_group', 'ndu_display_mode', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_display_mode',
        'default'           => 'nepali',
    ));
    register_setting('ndu_settings_group', 'ndu_numerals', array(
        'type'              => 'string',
        'sanitize_callback' => 'ndu_sanitize_numerals',
        'default'           => 'nepali',
    ));

    add_settings_section(
        'ndu_display_section',
        __('Display settings', 'nepali-date-utilities'),
        'ndu_display_section_cb',
        'ndu_display'
    );

    add_settings_field('ndu_convert_post_date', __('Convert dates to Nepali', 'nepali-date-utilities'), 'ndu_field_convert_post_date', 'ndu_display', 'ndu_display_section');
    add_settings_field('ndu_date_format', __('Post date format', 'nepali-date-utilities'), 'ndu_field_date_format', 'ndu_display', 'ndu_display_section');
    add_settings_field('ndu_today_date_format', __('Today\'s date format', 'nepali-date-utilities'), 'ndu_field_today_date_format', 'ndu_display', 'ndu_display_section');
    add_settings_field('ndu_date_format_for_home', __('Home / front page', 'nepali-date-utilities'), 'ndu_field_home_format', 'ndu_display', 'ndu_display_section');
    add_settings_field('ndu_display_mode', __('Display mode', 'nepali-date-utilities'), 'ndu_field_display_mode', 'ndu_display', 'ndu_display_section');
    add_settings_field('ndu_numerals', __('Numerals', 'nepali-date-utilities'), 'ndu_field_numerals', 'ndu_display', 'ndu_display_section');
}
add_action('admin_init', 'ndu_register_settings');

/**
 * @param mixed $value Raw value.
 * @return string
 */
function ndu_sanitize_checkbox($value)
{
    return ($value === '1' || $value === 1 || $value === true || $value === 'on') ? '1' : '0';
}

/**
 * @param mixed $value Raw format.
 * @return string
 */
function ndu_sanitize_format($value)
{
    $value = is_string($value) ? wp_strip_all_tags($value) : '';
    $value = trim($value);
    return $value !== '' ? $value : 'y m d';
}

/**
 * @param mixed $value Raw value.
 * @return string
 */
function ndu_sanitize_home_mode($value)
{
    $value = (string) $value;
    return in_array($value, array('0', '1', '2'), true) ? $value : '0';
}

/**
 * @param mixed $value Raw value.
 * @return string
 */
function ndu_sanitize_display_mode($value)
{
    return ($value === 'bilingual') ? 'bilingual' : 'nepali';
}

/**
 * @param mixed $value Raw value.
 * @return string
 */
function ndu_sanitize_numerals($value)
{
    return ($value === 'arabic') ? 'arabic' : 'nepali';
}

/**
 * Section intro.
 */
function ndu_display_section_cb()
{
    echo '<p>' . esc_html__('Control how Nepali dates appear on your site.', 'nepali-date-utilities') . '</p>';
}

/**
 * Convert toggle field.
 */
function ndu_field_convert_post_date()
{
    $value = get_option('ndu_convert_post_date', '1');
    ?>
    <input type="hidden" name="ndu_convert_post_date" value="0" />
    <label for="ndu_convert_post_date">
        <input type="checkbox" name="ndu_convert_post_date" id="ndu_convert_post_date" value="1" <?php checked($value, '1'); ?> />
        <?php esc_html_e('Convert post, modified, and comment dates to Nepali', 'nepali-date-utilities'); ?>
    </label>
    <?php
}

/**
 * Post format field with presets.
 */
function ndu_field_date_format()
{
    $value = get_option('ndu_date_format', 'y m d, l H:i');
    ?>
    <input type="text" name="ndu_date_format" id="ndu_date_format" class="regular-text ndu-format-input" data-preview="ndu-preview-post" value="<?php echo esc_attr($value); ?>" />
    <p class="description"><?php esc_html_e('Tokens: y m d l H/h i s a', 'nepali-date-utilities'); ?></p>
    <?php ndu_render_format_presets('ndu_date_format'); ?>
    <?php
}

/**
 * Today format field.
 */
function ndu_field_today_date_format()
{
    $value = get_option('ndu_today_date_format', 'y m d');
    ?>
    <input type="text" name="ndu_today_date_format" id="ndu_today_date_format" class="regular-text ndu-format-input" data-preview="ndu-preview-today" value="<?php echo esc_attr($value); ?>" />
    <p class="description"><?php esc_html_e('Used by shortcode, widget, and Today Date block.', 'nepali-date-utilities'); ?></p>
    <?php ndu_render_format_presets('ndu_today_date_format'); ?>
    <?php
}

/**
 * Home page behavior.
 */
function ndu_field_home_format()
{
    $value = get_option('ndu_date_format_for_home', '0');
    ?>
    <select name="ndu_date_format_for_home" id="ndu_date_format_for_home">
        <option value="0" <?php selected($value, '0'); ?>><?php esc_html_e('Use post date format', 'nepali-date-utilities'); ?></option>
        <option value="1" <?php selected($value, '1'); ?>><?php esc_html_e('Relative time (time ago)', 'nepali-date-utilities'); ?></option>
        <option value="2" <?php selected($value, '2'); ?>><?php esc_html_e('Hide date', 'nepali-date-utilities'); ?></option>
    </select>
    <?php
}

/**
 * Display mode field.
 */
function ndu_field_display_mode()
{
    $value = get_option('ndu_display_mode', 'nepali');
    ?>
    <select name="ndu_display_mode" id="ndu_display_mode">
        <option value="nepali" <?php selected($value, 'nepali'); ?>><?php esc_html_e('Nepali only', 'nepali-date-utilities'); ?></option>
        <option value="bilingual" <?php selected($value, 'bilingual'); ?>><?php esc_html_e('Bilingual (BS + AD)', 'nepali-date-utilities'); ?></option>
    </select>
    <?php
}

/**
 * Numerals field.
 */
function ndu_field_numerals()
{
    $value = get_option('ndu_numerals', 'nepali');
    ?>
    <select name="ndu_numerals" id="ndu_numerals">
        <option value="nepali" <?php selected($value, 'nepali'); ?>><?php esc_html_e('Devanagari (०१२…)', 'nepali-date-utilities'); ?></option>
        <option value="arabic" <?php selected($value, 'arabic'); ?>><?php esc_html_e('Arabic (012…)', 'nepali-date-utilities'); ?></option>
    </select>
    <?php
}

/**
 * Format preset buttons.
 *
 * @param string $target_id Input element id.
 */
function ndu_render_format_presets($target_id)
{
    $presets = array(
        'y m d',
        'y m d, l',
        'y m d, l H:i',
        'y m d गते',
        'd m y',
    );
    echo '<div class="ndu-presets" data-target="' . esc_attr($target_id) . '">';
    foreach ($presets as $preset) {
        printf(
            '<button type="button" class="button button-small ndu-preset" data-format="%1$s">%1$s</button> ',
            esc_attr($preset)
        );
    }
    echo '</div>';
}

/**
 * Add settings submenu.
 */
function ndu_admin_menu()
{
    add_submenu_page(
        'options-general.php',
        __('Nepali Date Utilities', 'nepali-date-utilities'),
        __('Nepali Date Utilities', 'nepali-date-utilities'),
        'manage_options',
        'nepali-date-utilities',
        'ndu_render_settings_page',
        111
    );
}
add_action('admin_menu', 'ndu_admin_menu');

/**
 * Enqueue admin assets on settings page only.
 *
 * @param string $hook Hook suffix.
 */
function ndu_admin_enqueue($hook)
{
    if ($hook !== 'settings_page_nepali-date-utilities') {
        return;
    }

    wp_enqueue_style(
        'ndu-admin',
        NPD_PLUGIN_URL . 'assets/admin.css',
        array(),
        NDU_VERSION
    );

    wp_enqueue_script(
        'ndu-admin',
        NPD_PLUGIN_URL . 'assets/admin.js',
        array(),
        NDU_VERSION,
        true
    );

    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'display';
    if ($tab === 'calendar') {
        ndu_enqueue_calendar_assets();
    }

    $now = current_time('timestamp');
    wp_localize_script('ndu-admin', 'nduAdmin', array(
        'previewPost'  => ndu_format_nepali_date($now, get_option('ndu_date_format', 'y m d, l H:i'), array('apply_home' => false)),
        'previewToday' => ndu_today_date(get_option('ndu_today_date_format', 'y m d')),
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('ndu_admin'),
        'i18n'         => array(
            'converting' => __('Converting…', 'nepali-date-utilities'),
            'error'      => __('Could not convert that date.', 'nepali-date-utilities'),
            'copied'     => __('Copied', 'nepali-date-utilities'),
        ),
    ));
}
add_action('admin_enqueue_scripts', 'ndu_admin_enqueue');

/**
 * AJAX: live format preview.
 */
function ndu_ajax_preview_format()
{
    check_ajax_referer('ndu_admin', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Forbidden'), 403);
    }

    $format = isset($_POST['format']) ? ndu_sanitize_format(wp_unslash($_POST['format'])) : 'y m d';
    $mode   = isset($_POST['display_mode']) ? ndu_sanitize_display_mode(wp_unslash($_POST['display_mode'])) : get_option('ndu_display_mode', 'nepali');
    $nums   = isset($_POST['numerals']) ? ndu_sanitize_numerals(wp_unslash($_POST['numerals'])) : get_option('ndu_numerals', 'nepali');

    $preview = ndu_format_nepali_date(
        current_time('timestamp'),
        $format,
        array(
            'apply_home'   => false,
            'display_mode' => $mode,
            'numerals'     => $nums,
        )
    );

    wp_send_json_success(array('preview' => $preview));
}
add_action('wp_ajax_ndu_preview_format', 'ndu_ajax_preview_format');

/**
 * AJAX: Eng↔Nep converter.
 */
function ndu_ajax_convert_date()
{
    check_ajax_referer('ndu_admin', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Forbidden'), 403);
    }

    $direction = isset($_POST['direction']) ? sanitize_key(wp_unslash($_POST['direction'])) : 'eng_to_nep';
    $y = isset($_POST['year']) ? (int) $_POST['year'] : 0;
    $m = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $d = isset($_POST['day']) ? (int) $_POST['day'] : 0;

    $converter = new Nepali_Date();

    if ($direction === 'nep_to_eng') {
        $result = $converter->nep_to_eng($y, $m, $d);
        if ($result === false) {
            wp_send_json_error(array('message' => $converter->last_error ?: __('Invalid date.', 'nepali-date-utilities')));
        }
        $formatted = sprintf(
            '%04d-%02d-%02d (%s)',
            (int) $result['year'],
            (int) $result['month'],
            (int) $result['date'],
            $result['nmonth']
        );
        wp_send_json_success(array(
            'result' => $formatted,
            'parts'  => $result,
        ));
    }

    $result = $converter->eng_to_nep($y, $m, $d);
    if ($result === false) {
        wp_send_json_error(array('message' => $converter->last_error ?: __('Invalid date.', 'nepali-date-utilities')));
    }

    $ts = mktime(12, 0, 0, $m, $d, $y);
    $formatted = ndu_format_nepali_date(
        $ts ?: current_time('timestamp'),
        'y m d, l',
        array('apply_home' => false, 'display_mode' => 'nepali')
    );

    wp_send_json_success(array(
        'result' => $formatted,
        'parts'  => $result,
    ));
}
add_action('wp_ajax_ndu_convert_date', 'ndu_ajax_convert_date');

/**
 * Render the settings page with tabs.
 */
function ndu_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'display';
    if (!in_array($tab, array('display', 'converter', 'calendar', 'help'), true)) {
        $tab = 'display';
    }

    $base_url = admin_url('options-general.php?page=nepali-date-utilities');
    ?>
    <div class="wrap ndu-admin">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <nav class="nav-tab-wrapper ndu-tabs">
            <a href="<?php echo esc_url($base_url . '&tab=display'); ?>" class="nav-tab <?php echo $tab === 'display' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Display', 'nepali-date-utilities'); ?>
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=converter'); ?>" class="nav-tab <?php echo $tab === 'converter' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Converter', 'nepali-date-utilities'); ?>
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=calendar'); ?>" class="nav-tab <?php echo $tab === 'calendar' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Post Calendar', 'nepali-date-utilities'); ?>
            </a>
            <a href="<?php echo esc_url($base_url . '&tab=help'); ?>" class="nav-tab <?php echo $tab === 'help' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Help &amp; shortcodes', 'nepali-date-utilities'); ?>
            </a>
        </nav>

        <?php if ($tab === 'display') : ?>
            <div class="ndu-layout">
                <div class="ndu-main">
                    <div class="ndu-card">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('ndu_settings_group');
                            do_settings_sections('ndu_display');
                            submit_button(__('Save settings', 'nepali-date-utilities'));
                            ?>
                        </form>
                    </div>
                </div>
                <aside class="ndu-sidebar">
                    <div class="ndu-card ndu-preview-card">
                        <h2><?php esc_html_e('Live preview', 'nepali-date-utilities'); ?></h2>
                        <p class="ndu-preview-label"><?php esc_html_e('Post date', 'nepali-date-utilities'); ?></p>
                        <p class="ndu-preview" id="ndu-preview-post"><?php echo esc_html(ndu_format_nepali_date(current_time('timestamp'), get_option('ndu_date_format', 'y m d, l H:i'), array('apply_home' => false))); ?></p>
                        <p class="ndu-preview-label"><?php esc_html_e('Today\'s date', 'nepali-date-utilities'); ?></p>
                        <p class="ndu-preview" id="ndu-preview-today"><?php echo esc_html(ndu_today_date()); ?></p>
                    </div>
                    <div class="ndu-card ndu-about-card">
                        <h2><?php esc_html_e('About', 'nepali-date-utilities'); ?></h2>
                        <p class="ndu-about-version">
                            <?php
                            printf(
                                /* translators: %s: plugin version number */
                                esc_html__('Version %s', 'nepali-date-utilities'),
                                esc_html(NDU_VERSION)
                            );
                            ?>
                        </p>
                        <p><?php esc_html_e('Nepali Date Utilities converts English (AD) dates to Nepali (BS) for posts, comments, shortcodes, blocks, and a monthly post calendar.', 'nepali-date-utilities'); ?></p>
                        <p>
                            <a href="https://wordpress.org/plugins/nepali-date-utilities" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Plugin page', 'nepali-date-utilities'); ?></a>
                            &nbsp;|&nbsp;
                            <a href="https://ashokbasnet.com.np/contact/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Contact developer', 'nepali-date-utilities'); ?></a>
                        </p>
                    </div>
                </aside>
            </div>
        <?php elseif ($tab === 'calendar') : ?>
            <?php ndu_enqueue_calendar_assets(); ?>
            <div class="ndu-layout ndu-layout--calendar">
                <div class="ndu-main">
                    <div class="ndu-card">
                        <h2><?php esc_html_e('Post Calendar', 'nepali-date-utilities'); ?></h2>
                        <p><?php esc_html_e('A monthly Bikram Sambat calendar you can place anywhere. Days show BS numerals with the matching Gregorian date underneath. Use the arrows to move between months.', 'nepali-date-utilities'); ?></p>
                        <p>
                            <code>[ndu_post_calendar]</code>
                        </p>
                        <p>
                            <code>[ndu_post_calendar year="2081" month="7" lang="english" show_post_links="1" accent="#c62828"]</code>
                        </p>
                        <ul class="ndu-token-list">
                            <li><code>year</code> / <code>month</code> — <?php esc_html_e('BS year and month (default: current)', 'nepali-date-utilities'); ?></li>
                            <li><code>lang</code> — <code>nepali</code> <?php esc_html_e('or', 'nepali-date-utilities'); ?> <code>english</code></li>
                            <li><code>show_post_links</code> — <?php esc_html_e('1 or 0 — show titles of posts published that day', 'nepali-date-utilities'); ?></li>
                            <li><code>accent</code> — <?php esc_html_e('hex accent color for headers and today highlight', 'nepali-date-utilities'); ?></li>
                        </ul>
                        <p><?php esc_html_e('Gutenberg: insert the “Nepali Post Calendar” block and configure year, month, language, post links, and accent color in the sidebar.', 'nepali-date-utilities'); ?></p>
                    </div>
                </div>
                <aside class="ndu-sidebar">
                    <div class="ndu-card ndu-calendar-preview-card">
                        <h2><?php esc_html_e('Preview', 'nepali-date-utilities'); ?></h2>
                        <?php
                        echo NDU_Post_Calendar::render(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer
                            'lang'            => 'nepali',
                            'show_post_links' => true,
                            'accent'          => NDU_Post_Calendar::DEFAULT_ACCENT,
                        ));
                        ?>
                    </div>
                </aside>
            </div>
        <?php elseif ($tab === 'converter') : ?>
            <div class="ndu-layout ndu-layout--single">
                <div class="ndu-main">
                    <div class="ndu-card">
                        <h2><?php esc_html_e('English → Nepali', 'nepali-date-utilities'); ?></h2>
                        <p class="description"><?php esc_html_e('Supported AD 1944–2033.', 'nepali-date-utilities'); ?></p>
                        <div class="ndu-converter" data-direction="eng_to_nep">
                            <div class="ndu-converter-row">
                                <label>
                                    <?php esc_html_e('Year', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-year small-text" value="<?php echo esc_attr(wp_date('Y')); ?>" min="1944" max="2033" />
                                </label>
                                <label>
                                    <?php esc_html_e('Month', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-month small-text" value="<?php echo esc_attr(wp_date('n')); ?>" min="1" max="12" />
                                </label>
                                <label>
                                    <?php esc_html_e('Day', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-day small-text" value="<?php echo esc_attr(wp_date('j')); ?>" min="1" max="31" />
                                </label>
                                <button type="button" class="button button-primary ndu-convert-btn"><?php esc_html_e('Convert', 'nepali-date-utilities'); ?></button>
                            </div>
                            <div class="ndu-converter-result">
                                <code class="ndu-result-text"></code>
                                <button type="button" class="button button-small ndu-copy-btn" hidden><?php esc_html_e('Copy', 'nepali-date-utilities'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="ndu-card">
                        <h2><?php esc_html_e('Nepali → English', 'nepali-date-utilities'); ?></h2>
                        <p class="description"><?php esc_html_e('Supported BS 2000–2099.', 'nepali-date-utilities'); ?></p>
                        <div class="ndu-converter" data-direction="nep_to_eng">
                            <div class="ndu-converter-row">
                                <label>
                                    <?php esc_html_e('Year', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-year small-text" value="2081" min="2000" max="2099" />
                                </label>
                                <label>
                                    <?php esc_html_e('Month', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-month small-text" value="7" min="1" max="12" />
                                </label>
                                <label>
                                    <?php esc_html_e('Day', 'nepali-date-utilities'); ?>
                                    <input type="number" class="ndu-day small-text" value="15" min="1" max="32" />
                                </label>
                                <button type="button" class="button button-primary ndu-convert-btn"><?php esc_html_e('Convert', 'nepali-date-utilities'); ?></button>
                            </div>
                            <div class="ndu-converter-result">
                                <code class="ndu-result-text"></code>
                                <button type="button" class="button button-small ndu-copy-btn" hidden><?php esc_html_e('Copy', 'nepali-date-utilities'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <div class="ndu-layout ndu-layout--single">
                <div class="ndu-main">
                    <div class="ndu-card">
                        <h2><?php esc_html_e('Format tokens', 'nepali-date-utilities'); ?></h2>
                        <ul class="ndu-token-list">
                            <li><code>y</code> — <?php esc_html_e('वर्ष (e.g. २०८१)', 'nepali-date-utilities'); ?></li>
                            <li><code>m</code> — <?php esc_html_e('महिना (e.g. कार्तिक)', 'nepali-date-utilities'); ?></li>
                            <li><code>d</code> — <?php esc_html_e('गते (day number)', 'nepali-date-utilities'); ?></li>
                            <li><code>l</code> — <?php esc_html_e('दिन (e.g. आइतबार)', 'nepali-date-utilities'); ?></li>
                            <li><code>H</code> / <code>h</code> — <?php esc_html_e('Hour (24h / 12h)', 'nepali-date-utilities'); ?></li>
                            <li><code>i</code> — <?php esc_html_e('Minute', 'nepali-date-utilities'); ?></li>
                            <li><code>s</code> — <?php esc_html_e('Second', 'nepali-date-utilities'); ?></li>
                            <li><code>a</code> — <?php esc_html_e('Period (बिहान / दिउँसो / साँझ / राति)', 'nepali-date-utilities'); ?></li>
                        </ul>
                    </div>
                    <div class="ndu-card">
                        <h2><?php esc_html_e('Shortcodes &amp; PHP', 'nepali-date-utilities'); ?></h2>
                        <ul class="ndu-snippet-list">
                            <li>
                                <code>[ndu_today_date]</code>
                                <span><?php esc_html_e('Today\'s date', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>[ndu_today_date format="y m d, l"]</code>
                                <span><?php esc_html_e('Today with custom format', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>[ndu_date_ago]</code>
                                <span><?php esc_html_e('Relative time for the current post', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>[ndu_convert date="2024-10-31" from="eng" format="y m d"]</code>
                                <span><?php esc_html_e('Convert AD → BS', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>[ndu_convert date="2081-07-15" from="nep"]</code>
                                <span><?php esc_html_e('Convert BS → AD', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>[ndu_post_calendar]</code>
                                <span><?php esc_html_e('Monthly BS post calendar', 'nepali-date-utilities'); ?></span>
                            </li>
                            <li>
                                <code>&lt;?php echo ndu_today_date(); ?&gt;</code>
                                <span><?php esc_html_e('PHP helper', 'nepali-date-utilities'); ?></span>
                            </li>
                        </ul>
                        <p>
                            <?php esc_html_e('Gutenberg blocks: “Nepali Today Date” and “Nepali Post Calendar”.', 'nepali-date-utilities'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
