<?php
/**
 * Bikram Sambat post calendar renderer.
 *
 * @package Nepali_Date_Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Build and render a monthly BS calendar with optional post links.
 */
class NDU_Post_Calendar
{
    /**
     * Default accent color.
     */
    const DEFAULT_ACCENT = '#c62828';

    /**
     * Romanized BS month names (1-indexed).
     *
     * @var string[]
     */
    private static $month_names_en = array(
        1  => 'Baisakh',
        2  => 'Jestha',
        3  => 'Ashadh',
        4  => 'Shrawan',
        5  => 'Bhadra',
        6  => 'Ashwin',
        7  => 'Kartik',
        8  => 'Mangsir',
        9  => 'Poush',
        10 => 'Magh',
        11 => 'Falgun',
        12 => 'Chaitra',
    );

    /**
     * Short weekday labels.
     *
     * @param string $lang nepali|english.
     * @return string[]
     */
    public static function weekday_labels($lang = 'nepali')
    {
        if ($lang === 'english') {
            return array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        }
        return array('आइत', 'सोम', 'मंगल', 'बुध', 'बिही', 'शुक्र', 'शनि');
    }

    /**
     * Current BS year/month/day from site time.
     *
     * @return array{year:int,month:int,date:int}|false
     */
    public static function current_bs()
    {
        $converter = new Nepali_Date();
        $y = (int) wp_date('Y');
        $m = (int) wp_date('n');
        $d = (int) wp_date('j');
        $nep = $converter->eng_to_nep($y, $m, $d);
        if ($nep === false) {
            return false;
        }
        return array(
            'year'  => (int) $nep['year'],
            'month' => (int) $nep['month'],
            'date'  => (int) $nep['date'],
        );
    }

    /**
     * Normalize attributes.
     *
     * @param array $atts Raw attributes.
     * @return array
     */
    public static function normalize_atts($atts)
    {
        $current = self::current_bs();
        $defaults = array(
            'year'            => $current ? $current['year'] : 2081,
            'month'           => $current ? $current['month'] : 1,
            'lang'            => 'nepali',
            'show_post_links' => true,
            'accent'          => self::DEFAULT_ACCENT,
        );

        $atts = wp_parse_args($atts, $defaults);

        $atts['year']  = max(2000, min(2099, (int) $atts['year']));
        $atts['month'] = max(1, min(12, (int) $atts['month']));
        $atts['lang']  = ($atts['lang'] === 'english') ? 'english' : 'nepali';

        $show = $atts['show_post_links'];
        if (is_string($show)) {
            $show = !in_array(strtolower($show), array('0', 'false', 'no', 'off'), true);
        }
        $atts['show_post_links'] = (bool) $show;

        $accent = sanitize_hex_color($atts['accent']);
        $atts['accent'] = $accent ? $accent : self::DEFAULT_ACCENT;

        return $atts;
    }

    /**
     * Shift BS month by delta (-1 / +1).
     *
     * @param int $year  BS year.
     * @param int $month BS month.
     * @param int $delta Offset.
     * @return array{year:int,month:int}
     */
    public static function shift_month($year, $month, $delta)
    {
        $month = (int) $month + (int) $delta;
        $year  = (int) $year;
        while ($month < 1) {
            $month += 12;
            $year--;
        }
        while ($month > 12) {
            $month -= 12;
            $year++;
        }
        $year = max(2000, min(2099, $year));
        return array('year' => $year, 'month' => $month);
    }

    /**
     * Build calendar day cells for a BS month.
     *
     * @param int  $year  BS year.
     * @param int  $month BS month.
     * @param bool $with_posts Load posts for days.
     * @return array|false
     */
    public static function build_month($year, $month, $with_posts = true)
    {
        $converter = new Nepali_Date();
        $days_in_month = $converter->days_in_month($year, $month);
        if ($days_in_month === false) {
            return false;
        }

        $first = $converter->nep_to_eng($year, $month, 1);
        if ($first === false) {
            return false;
        }

        // num_day: 1 = Sunday … 7 = Saturday
        $start_weekday = (int) $first['num_day'] - 1; // 0-based Sunday
        if ($start_weekday < 0) {
            $start_weekday = 0;
        }

        $last = $converter->nep_to_eng($year, $month, $days_in_month);
        if ($last === false) {
            return false;
        }

        $posts_by_day = array();
        if ($with_posts) {
            $posts_by_day = self::posts_for_range(
                (int) $first['year'],
                (int) $first['month'],
                (int) $first['date'],
                (int) $last['year'],
                (int) $last['month'],
                (int) $last['date'],
                $year,
                $month
            );
        }

        $today_bs = self::current_bs();
        $cells = array();

        // Leading padding from previous month.
        $prev = self::shift_month($year, $month, -1);
        $prev_days = $converter->days_in_month($prev['year'], $prev['month']);
        for ($i = 0; $i < $start_weekday; $i++) {
            $bs_day = $prev_days - $start_weekday + $i + 1;
            $eng = $converter->nep_to_eng($prev['year'], $prev['month'], $bs_day);
            $cells[] = self::make_cell($bs_day, $eng, false, false, array());
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $eng = $converter->nep_to_eng($year, $month, $day);
            $is_today = $today_bs
                && (int) $today_bs['year'] === (int) $year
                && (int) $today_bs['month'] === (int) $month
                && (int) $today_bs['date'] === $day;
            $day_posts = isset($posts_by_day[$day]) ? $posts_by_day[$day] : array();
            $cells[] = self::make_cell($day, $eng, true, $is_today, $day_posts);
        }

        // Trailing padding to complete weeks.
        $remainder = count($cells) % 7;
        if ($remainder > 0) {
            $next = self::shift_month($year, $month, 1);
            $pad = 7 - $remainder;
            for ($i = 1; $i <= $pad; $i++) {
                $eng = $converter->nep_to_eng($next['year'], $next['month'], $i);
                $cells[] = self::make_cell($i, $eng, false, false, array());
            }
        }

        $month_name_np = $converter->get_nepali_month_name($month);
        $month_name_en = isset(self::$month_names_en[$month]) ? self::$month_names_en[$month] : '';

        return array(
            'year'           => $year,
            'month'          => $month,
            'days_in_month'  => $days_in_month,
            'month_name_np'  => $month_name_np,
            'month_name_en'  => $month_name_en,
            'cells'          => $cells,
            'prev'           => self::shift_month($year, $month, -1),
            'next'           => self::shift_month($year, $month, 1),
        );
    }

    /**
     * @param int         $bs_day BS day number.
     * @param array|false $eng    Eng conversion result.
     * @param bool        $current_month In current month.
     * @param bool        $is_today Today.
     * @param array       $posts  Post links.
     * @return array
     */
    private static function make_cell($bs_day, $eng, $current_month, $is_today, $posts)
    {
        $eng_label = '';
        if (is_array($eng)) {
            $eng_label = strtoupper(gmdate('j M', mktime(12, 0, 0, (int) $eng['month'], (int) $eng['date'], (int) $eng['year'])));
            // Prefer site timezone formatting via DateTime if available.
            try {
                $dt = new DateTime(sprintf('%04d-%02d-%02d', (int) $eng['year'], (int) $eng['month'], (int) $eng['date']));
                $eng_label = strtoupper($dt->format('j M'));
            } catch (Exception $e) {
                // keep gmdate fallback
            }
        }

        return array(
            'bs_day'         => (int) $bs_day,
            'eng_label'      => $eng_label,
            'current_month'  => (bool) $current_month,
            'is_today'       => (bool) $is_today,
            'posts'          => $posts,
        );
    }

    /**
     * Posts keyed by BS day within the target BS month.
     *
     * @param int $ey1 Start eng year.
     * @param int $em1 Start eng month.
     * @param int $ed1 Start eng day.
     * @param int $ey2 End eng year.
     * @param int $em2 End eng month.
     * @param int $ed2 End eng day.
     * @param int $bs_year Target BS year.
     * @param int $bs_month Target BS month.
     * @return array<int,array>
     */
    private static function posts_for_range($ey1, $em1, $ed1, $ey2, $em2, $ed2, $bs_year, $bs_month)
    {
        $after  = sprintf('%04d-%02d-%02d 00:00:00', $ey1, $em1, $ed1);
        $before = sprintf('%04d-%02d-%02d 23:59:59', $ey2, $em2, $ed2);

        $query = new WP_Query(array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 200,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'date_query'             => array(
                array(
                    'after'     => $after,
                    'before'    => $before,
                    'inclusive' => true,
                ),
            ),
            'orderby'                => 'date',
            'order'                  => 'ASC',
        ));

        $converter = new Nepali_Date();
        $by_day = array();

        foreach ($query->posts as $post) {
            $ts = get_post_time('U', true, $post);
            if (!$ts) {
                continue;
            }
            $y = (int) wp_date('Y', $ts);
            $m = (int) wp_date('n', $ts);
            $d = (int) wp_date('j', $ts);
            $nep = $converter->eng_to_nep($y, $m, $d);
            if ($nep === false) {
                continue;
            }
            if ((int) $nep['year'] !== (int) $bs_year || (int) $nep['month'] !== (int) $bs_month) {
                continue;
            }
            $day = (int) $nep['date'];
            if (!isset($by_day[$day])) {
                $by_day[$day] = array();
            }
            $by_day[$day][] = array(
                'id'    => (int) $post->ID,
                'title' => get_the_title($post),
                'url'   => get_permalink($post),
            );
        }

        wp_reset_postdata();
        return $by_day;
    }

    /**
     * Render calendar HTML.
     *
     * @param array $atts Normalized or raw attributes.
     * @return string
     */
    public static function render($atts = array())
    {
        $atts = self::normalize_atts($atts);
        $data = self::build_month($atts['year'], $atts['month'], true);
        if ($data === false) {
            return '<p class="ndu-calendar-error">' . esc_html__('Unable to build calendar for this date.', 'nepali-date-utilities') . '</p>';
        }

        $lang = $atts['lang'];
        $use_nepali_nums = ($lang === 'nepali');
        $converter = new Nepali_Date();

        $title_month = ($lang === 'english') ? $data['month_name_en'] : $data['month_name_np'];
        $title_year  = $use_nepali_nums
            ? $converter->convert_to_nepali_number($data['year'])
            : (string) $data['year'];

        $weekdays = self::weekday_labels($lang);
        $uid = 'ndu-cal-' . wp_unique_id();

        ob_start();
        ?>
        <div
            id="<?php echo esc_attr($uid); ?>"
            class="ndu-post-calendar"
            style="--ndu-cal-accent: <?php echo esc_attr($atts['accent']); ?>;"
            data-year="<?php echo esc_attr((string) $data['year']); ?>"
            data-month="<?php echo esc_attr((string) $data['month']); ?>"
            data-lang="<?php echo esc_attr($lang); ?>"
            data-show-post-links="<?php echo $atts['show_post_links'] ? '1' : '0'; ?>"
            data-accent="<?php echo esc_attr($atts['accent']); ?>"
        >
            <div class="ndu-cal-header">
                <button type="button" class="ndu-cal-nav ndu-cal-prev" aria-label="<?php esc_attr_e('Previous month', 'nepali-date-utilities'); ?>">
                    <span aria-hidden="true">&#8249;</span>
                </button>
                <div class="ndu-cal-title">
                    <span class="ndu-cal-month"><?php echo esc_html($title_month); ?></span>
                    <span class="ndu-cal-year"><?php echo esc_html($title_year); ?></span>
                </div>
                <button type="button" class="ndu-cal-nav ndu-cal-next" aria-label="<?php esc_attr_e('Next month', 'nepali-date-utilities'); ?>">
                    <span aria-hidden="true">&#8250;</span>
                </button>
            </div>

            <div class="ndu-cal-weekdays" role="row">
                <?php foreach ($weekdays as $label) : ?>
                    <div class="ndu-cal-weekday" role="columnheader"><?php echo esc_html($label); ?></div>
                <?php endforeach; ?>
            </div>

            <div class="ndu-cal-grid" role="grid">
                <?php foreach ($data['cells'] as $cell) :
                    $classes = array('ndu-cal-day');
                    if (!$cell['current_month']) {
                        $classes[] = 'is-outside';
                    }
                    if ($cell['is_today']) {
                        $classes[] = 'is-today';
                    }
                    if (!empty($cell['posts']) && $atts['show_post_links']) {
                        $classes[] = 'has-posts';
                    }
                    $bs_label = $use_nepali_nums
                        ? $converter->convert_to_nepali_number($cell['bs_day'])
                        : (string) $cell['bs_day'];
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>" role="gridcell">
                        <span class="ndu-cal-bs"><?php echo esc_html($bs_label); ?></span>
                        <span class="ndu-cal-eng"><?php echo esc_html($cell['eng_label']); ?></span>
                        <?php if ($atts['show_post_links'] && !empty($cell['posts']) && $cell['current_month']) : ?>
                            <ul class="ndu-cal-posts">
                                <?php foreach ($cell['posts'] as $post_item) : ?>
                                    <li>
                                        <a href="<?php echo esc_url($post_item['url']); ?>" title="<?php echo esc_attr($post_item['title']); ?>">
                                            <?php echo esc_html(wp_html_excerpt($post_item['title'], 28, '…')); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php elseif (!empty($cell['posts']) && $cell['current_month']) : ?>
                            <span class="ndu-cal-dot" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

/**
 * Shortcode: [ndu_post_calendar year="" month="" lang="nepali" show_post_links="1" accent="#c62828"]
 *
 * @param array|string $atts Attributes.
 * @return string
 */
function ndu_post_calendar_shortcode($atts = array())
{
    ndu_enqueue_calendar_assets();
    $atts = shortcode_atts(
        array(
            'year'            => '',
            'month'           => '',
            'lang'            => 'nepali',
            'show_post_links' => '1',
            'accent'          => NDU_Post_Calendar::DEFAULT_ACCENT,
        ),
        $atts,
        'ndu_post_calendar'
    );

    $current = NDU_Post_Calendar::current_bs();
    if ($atts['year'] === '' || $atts['year'] === null) {
        $atts['year'] = $current ? $current['year'] : 2081;
    }
    if ($atts['month'] === '' || $atts['month'] === null) {
        $atts['month'] = $current ? $current['month'] : 1;
    }

    return NDU_Post_Calendar::render($atts);
}
add_shortcode('ndu_post_calendar', 'ndu_post_calendar_shortcode');

/**
 * Enqueue calendar CSS/JS when needed.
 */
function ndu_enqueue_calendar_assets()
{
    if (wp_style_is('ndu-calendar', 'enqueued')) {
        return;
    }

    wp_enqueue_style(
        'ndu-calendar',
        NPD_PLUGIN_URL . 'assets/calendar.css',
        array(),
        NDU_VERSION
    );

    wp_enqueue_script(
        'ndu-calendar',
        NPD_PLUGIN_URL . 'assets/calendar.js',
        array(),
        NDU_VERSION,
        true
    );

    wp_localize_script('ndu-calendar', 'nduCalendar', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('ndu_calendar'),
    ));
}

/**
 * AJAX: navigate calendar month (front + admin).
 */
function ndu_ajax_calendar_month()
{
    check_ajax_referer('ndu_calendar', 'nonce');

    $year  = isset($_POST['year']) ? (int) $_POST['year'] : 0;
    $month = isset($_POST['month']) ? (int) $_POST['month'] : 0;
    $delta = isset($_POST['delta']) ? (int) $_POST['delta'] : 0;
    $lang  = isset($_POST['lang']) ? sanitize_key(wp_unslash($_POST['lang'])) : 'nepali';
    $show  = isset($_POST['show_post_links']) ? sanitize_text_field(wp_unslash($_POST['show_post_links'])) : '1';
    $accent = isset($_POST['accent']) ? sanitize_hex_color(wp_unslash($_POST['accent'])) : NDU_Post_Calendar::DEFAULT_ACCENT;

    if ($delta !== 0) {
        $shifted = NDU_Post_Calendar::shift_month($year, $month, $delta);
        $year  = $shifted['year'];
        $month = $shifted['month'];
    }

    $html = NDU_Post_Calendar::render(array(
        'year'            => $year,
        'month'           => $month,
        'lang'            => $lang,
        'show_post_links' => $show,
        'accent'          => $accent ? $accent : NDU_Post_Calendar::DEFAULT_ACCENT,
    ));

    wp_send_json_success(array(
        'html'  => $html,
        'year'  => $year,
        'month' => $month,
    ));
}
add_action('wp_ajax_ndu_calendar_month', 'ndu_ajax_calendar_month');
add_action('wp_ajax_nopriv_ndu_calendar_month', 'ndu_ajax_calendar_month');

/**
 * Render callback for Post Calendar block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function ndu_render_post_calendar_block($attributes)
{
    ndu_enqueue_calendar_assets();

    $atts = array(
        'year'            => isset($attributes['year']) && $attributes['year'] !== '' && $attributes['year'] !== null
            ? (int) $attributes['year']
            : '',
        'month'           => isset($attributes['month']) && $attributes['month'] !== '' && $attributes['month'] !== null
            ? (int) $attributes['month']
            : '',
        'lang'            => !empty($attributes['lang']) ? $attributes['lang'] : 'nepali',
        'show_post_links' => isset($attributes['showPostLinks']) ? (bool) $attributes['showPostLinks'] : true,
        'accent'          => !empty($attributes['accentColor']) ? $attributes['accentColor'] : NDU_Post_Calendar::DEFAULT_ACCENT,
    );

    $current = NDU_Post_Calendar::current_bs();
    if ($atts['year'] === '') {
        $atts['year'] = $current ? $current['year'] : 2081;
    }
    if ($atts['month'] === '') {
        $atts['month'] = $current ? $current['month'] : 1;
    }

    $html = NDU_Post_Calendar::render($atts);
    $class = 'ndu-post-calendar-block';
    if (!empty($attributes['className'])) {
        $class .= ' ' . $attributes['className'];
    }

    return '<div class="' . esc_attr($class) . '">' . $html . '</div>';
}
