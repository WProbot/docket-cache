<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

/*
 * Reference:
 *  plugins/wp-crontrol/src/event-list-table.php
 *	plugins/wp-crontrol/src/event.php
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

if (!class_exists('\\WP_List_Table', false)) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class EventList extends \WP_List_Table
{
    private $pt;

    public function __construct(Plugin $pt)
    {
        parent::__construct(
            [
                'singular' => 'eventlist-event',
                'plural' => 'eventlist-events',
                'ajax' => false,
                'screen' => 'eventlist-events',
            ]
        );

        $this->pt = $pt;
    }

    public function get_schedules()
    {
        $schedules = wp_get_schedules();
        uasort(
            $schedules,
            function (array $a, array $b) {
                return  $a['interval'] - $b['interval'];
            }
        );

        array_walk(
            $schedules,
            function (array &$schedule, $name) {
                $schedule['name'] = $name;
            }
        );

        return $schedules;
    }

    public function get_crons()
    {
        $is_switch = $this->pt->switch_cron_site();

        $crons = $this->pt->get_crons(true);
        $events = [];

        if (empty($crons)) {
            if ($is_switch) {
                restore_current_blog();
            }

            return [];
        }

        foreach ($crons as $time => $cron) {
            foreach ($cron as $hook => $dings) {
                if (!has_action($hook)) {
                    wp_clear_scheduled_hook($hook);
                    continue;
                }
                foreach ($dings as $sig => $data) {
                    $events[$hook.'-'.$sig.'-'.$time] = (object) [
                        'hook' => $hook,
                        'time' => $time,
                        'sig' => $sig,
                        'args' => $data['args'],
                        'schedule' => $data['schedule'],
                        'interval' => isset($data['interval']) ? $data['interval'] : null,
                    ];
                }
            }
        }

        uasort(
            $events,
            function ($a, $b) {
                if ($a->time === $b->time) {
                    return 0;
                }

                return ($a->time > $b->time) ? 1 : -1;
            }
        );

        if ($is_switch) {
            restore_current_blog();
        }

        return $events;
    }

    public function get_utc_offset()
    {
        $offset = get_option('gmt_offset', 0);

        if (empty($offset)) {
            return 'UTC';
        }

        if (0 <= $offset) {
            $formatted_offset = '+'.(string) $offset;
        } else {
            $formatted_offset = (string) $offset;
        }
        $formatted_offset = str_replace(
            ['.25', '.5', '.75'],
            [':15', ':30', ':45'],
            $formatted_offset
        );

        return 'UTC'.$formatted_offset;
    }

    public function populate_callback($callback)
    {
        $callback = (array) $callback;

        if (method_exists('\QM_Util', 'populate_callback')) {
            return \QM_Util::populate_callback($callback);
        }

        if (\is_string($callback['function']) && (false !== strpos($callback['function'], '::'))) {
            $callback['function'] = explode('::', $callback['function']);
        }

        if (\is_array($callback['function'])) {
            if (\is_object($callback['function'][0])) {
                $class = \get_class($callback['function'][0]);
                $access = '->';
            } else {
                $class = $callback['function'][0];
                $access = '::';
            }

            $callback['name'] = $class.$access.$callback['function'][1].'()';
        } elseif (\is_object($callback['function'])) {
            if (is_a($callback['function'], 'Closure')) {
                $callback['name'] = 'Closure';
            } else {
                $class = \get_class($callback['function']);

                $callback['name'] = $class.'->__invoke()';
            }
        } else {
            $callback['name'] = $callback['function'].'()';
        }

        return $callback;
    }

    public function json_output($input)
    {
        $json_options = 0;

        if (\defined('JSON_UNESCAPED_SLASHES')) {
            $json_options |= JSON_UNESCAPED_SLASHES;
        }
        if (\defined('JSON_PRETTY_PRINT')) {
            $json_options |= JSON_PRETTY_PRINT;
        }

        return wp_json_encode($input, $json_options);
    }

    public function get_hook_callbacks($name)
    {
        global $wp_filter;

        $actions = [];

        if (isset($wp_filter[$name])) {
            $action = $wp_filter[$name];

            foreach ($action as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $callback = $this->populate_callback($callback);

                    $actions[] = [
                        'priority' => $priority,
                        'callback' => $callback,
                    ];
                }
            }
        }

        return $actions;
    }

    public function get_schedule_name(\stdClass $event)
    {
        $schedules = $this->get_schedules();

        if (isset($event->schedule) && isset($schedules[$event->schedule])) {
            return $schedules[$event->schedule]['display'];
        }

        /* translators: %s: Schedule name */
        $error_text = sprintf(__('Unknown (%s)', 'docket-cache'), $event->schedule);

        return new \WP_Error('unknown_schedule', $error_text);
    }

    public function interval($since)
    {
        // Array of time period chunks.
        $chunks = [
            /* translators: 1: The number of years in an interval of time. */
            [60 * 60 * 24 * 365, _n_noop('%s year', '%s years', 'docket-cache')],
            /* translators: 1: The number of months in an interval of time. */
            [60 * 60 * 24 * 30, _n_noop('%s month', '%s months', 'docket-cache')],
            /* translators: 1: The number of weeks in an interval of time. */
            [60 * 60 * 24 * 7, _n_noop('%s week', '%s weeks', 'docket-cache')],
            /* translators: 1: The number of days in an interval of time. */
            [60 * 60 * 24, _n_noop('%s day', '%s days', 'docket-cache')],
            /* translators: 1: The number of hours in an interval of time. */
            [60 * 60, _n_noop('%s hour', '%s hours', 'docket-cache')],
            /* translators: 1: The number of minutes in an interval of time. */
            [60, _n_noop('%s minute', '%s minutes', 'docket-cache')],
            /* translators: 1: The number of seconds in an interval of time. */
            [1, _n_noop('%s second', '%s seconds', 'docket-cache')],
        ];

        if ($since <= 0) {
            return __('now', 'docket-cache');
        }

        $j = \count($chunks);

        for ($i = 0; $i < $j; ++$i) {
            $seconds = $chunks[$i][0];
            $name = $chunks[$i][1];

            $count = floor($since / $seconds);
            if ($count) {
                break;
            }
        }

        $output = sprintf(translate_nooped_plural($name, $count, 'docket-cache'), $count);

        if ($i + 1 < $j) {
            $seconds2 = $chunks[$i + 1][0];
            $name2 = $chunks[$i + 1][1];
            $count2 = floor(($since - ($seconds * $count)) / $seconds2);
            if ($count2) {
                $output .= ' '.sprintf(translate_nooped_plural($name2, $count2, 'docket-cache'), $count2);
            }
        }

        return $output;
    }

    public function is_late($event)
    {
        $event = (object) $event;
        $until = $event->time - time();

        return  $until < (0 - (10 * MINUTE_IN_SECONDS));
    }

    public function get_timezone_name()
    {
        $timezone_string = get_option('timezone_string', '');
        $gmt_offset = get_option('gmt_offset', 0);

        if ('UTC' === $timezone_string || (empty($gmt_offset) && empty($timezone_string))) {
            return 'UTC';
        }

        if ('' === $timezone_string) {
            return $this->get_utc_offset();
        }

        return sprintf(
            '%s, %s',
            str_replace('_', ' ', $timezone_string),
            $this->get_utc_offset()
        );
    }

    public function prepare_items()
    {
        $events = $this->get_crons();

        if (!empty($_GET['s'])) {
            $s = sanitize_text_field(wp_unslash($_GET['s']));

            $events = array_filter(
                $events,
                function ($event) use ($s) {
                    return  false !== strpos($event->hook, $s);
                }
            );
        }

        $count = \count($events);
        $per_page = 50;
        $offset = ($this->get_pagenum() - 1) * $per_page;

        $this->items = \array_slice($events, $offset, $per_page);

        $this->set_pagination_args(
            [
                'total_items' => $count,
                'per_page' => $per_page,
                'total_pages' => ceil($count / $per_page),
            ]
        );
    }

    public function get_columns()
    {
        /* translators: %s: UTC offset */
        $next_run_text = sprintf(__('Next Schedule (%s)', 'docket-cache'), $this->get_utc_offset());

        return [
            'eventlist_hook' => __('Hook', 'docket-cache'),
            'eventlist_args' => __('Arguments', 'docket-cache'),
            'eventlist_next' => $next_run_text,
            'eventlist_actions' => __('Action', 'docket-cache'),
            'eventlist_recurrence' => __('Recurrence', 'docket-cache'),
         ];
    }

    public function get_table_classes()
    {
        return ['widefat', 'striped', $this->_args['plural']];
    }

    public function column_eventlist_hook($event)
    {
        return esc_html($event->hook);
    }

    public function column_eventlist_args($event)
    {
        if (!empty($event->args)) {
            $args = $this->json_output($event->args);
        }

        if (empty($event->args)) {
            return sprintf(
                    '<em>%s</em>',
                    esc_html__('None', 'docket-cache')
                );
        }

        return sprintf(
                    '<pre>%s</pre>',
                    esc_html($args)
                );
    }

    public function column_eventlist_actions($event)
    {
        $hook_callbacks = $this->get_hook_callbacks($event->hook);

        if (!empty($hook_callbacks)) {
            $callbacks = [];

            foreach ($hook_callbacks as $callback) {
                $callbacks[] = '<code>'.$callback['callback']['name'].'</code>';
            }

            return implode('<br>', $callbacks);
        }

        return sprintf(
            '<span class="status-eventlist-warning">%s</span>',
            esc_html__('None', 'docket-cache')
        );
    }

    public function column_eventlist_next($event)
    {
        $date_utc = gmdate('Y-m-d\TH:i:s+00:00', $event->time);
        $date_local = get_date_from_gmt(date('Y-m-d H:i:s', $event->time), 'Y-m-d H:i:s');

        $time = sprintf(
            '<time datetime="%1$s">%2$s</time>',
            esc_attr($date_utc),
            esc_html($date_local)
        );

        $until = $event->time - time();
        $late = $this->is_late($event);

        if ($late) {
            /* translators: %s: Time period, for example "8 minutes" */
            $ago = sprintf(__('%s ago', 'docket-cache'), $this->interval(abs($until)));

            return sprintf(
                '%s<br><span class="status-eventlist-warning">%s</span>',
                $time,
                esc_html($ago)
            );
        }

        return sprintf(
            '%s<br>%s',
            $time,
            esc_html($this->interval($until))
        );
    }

    public function column_eventlist_recurrence($event)
    {
        if ($event->schedule) {
            $schedule_name = $this->get_schedule_name($event);
            if (is_wp_error($schedule_name)) {
                return sprintf(
                    '<span class="status-eventlist-error"><span class="dashicons dashicons-warning" aria-hidden="true"></span> %s</span>',
                    esc_html($schedule_name->get_error_message())
                );
            }

            return esc_html($schedule_name);
        }

        return esc_html__('Non-repeating', 'docket-cache');
    }

    public function no_items()
    {
        esc_html_e('There are currently no scheduled cron events.', 'docket-cache');
    }
}
