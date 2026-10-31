<?php
/**
 * Classic widget: Today's date in Nepali.
 *
 * @package Nepali_Date_Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class NDUTodayDateWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'ndu_today_date_widget',
            __('Today Date in Nepali', 'nepali-date-utilities'),
            array(
                'description'                 => __('Shows today\'s date in Nepali (BS).', 'nepali-date-utilities'),
                'customize_selective_refresh' => true,
            )
        );
    }

    public function form($instance)
    {
        $title  = isset($instance['title']) ? $instance['title'] : '';
        $format = isset($instance['format']) ? $instance['format'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Widget Title', 'nepali-date-utilities'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('format')); ?>"><?php esc_html_e('Date format', 'nepali-date-utilities'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('format')); ?>" name="<?php echo esc_attr($this->get_field_name('format')); ?>" type="text" value="<?php echo esc_attr($format); ?>" placeholder="<?php echo esc_attr(get_option('ndu_today_date_format', 'y m d')); ?>" />
            <small><?php esc_html_e('Leave empty to use plugin setting. Tokens: y m d l H/h i s a', 'nepali-date-utilities'); ?></small>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title']  = isset($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['format'] = isset($new_instance['format']) ? sanitize_text_field($new_instance['format']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        $title  = !empty($instance['title']) ? apply_filters('widget_title', $instance['title'], $instance, $this->id_base) : '';
        $format = !empty($instance['format']) ? $instance['format'] : null;

        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        echo esc_html(ndu_today_date($format));

        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

/**
 * Register the classic widget.
 */
function ndu_register_today_date_widget()
{
    register_widget('NDUTodayDateWidget');
}
add_action('widgets_init', 'ndu_register_today_date_widget');
