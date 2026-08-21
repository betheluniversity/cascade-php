<?php
/**
 * Drop-in v4-compatible replacement for event_feed.php.
 * Do not include both entrypoints in the same request.
 */
$NumEvents;
$PriorToToday;
$StartDate;
$EndDate;

include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/events/php/event_data_v4.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/code/vendor/autoload.php';

function create_event_feed($categories, $heading = '')
{
    return autoCache(
        'create_event_feed_v4_logic',
        array($categories)
    );
}

function create_event_feed_v4_logic($categories)
{
    global $NumEvents;

    $allEvents = event_v4_get_events();
    $filters = event_v4_normalize_filter_values($categories);
    $events = array();
    foreach ($allEvents as $event) {
        if ($event['published'] === ''
            || event_feed_v4_event_is_other_only($event)
            || !event_v4_event_matches_filters($event, $filters)) {
            continue;
        }
        foreach ($event['dates'] as $date) {
            foreach (event_feed_v4_occurrences($event, $date) as $occurrence) {
                $events[] = $occurrence;
            }
        }
    }

    usort($events, 'event_feed_v4_sort_events');

    $limit = is_numeric($NumEvents) ? (int)$NumEvents : count($events);
    if ($limit >= 0) {
        $events = array_slice($events, 0, $limit, true);
    }

    $eventHtml = array();
    foreach ($events as $event) {
        $eventHtml[] = get_event_html($event);
    }

    return array(array(), $eventHtml, count($eventHtml));
}

function event_feed_v4_event_is_other_only($event)
{
    $hasOtherEventType = false;
    $hasAdditionalSelection = false;

    foreach ($event['metadata'] as $field => $values) {
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value === '' || strcasecmp($value, 'None') === 0 || strcasecmp($value, 'Select') === 0) {
                continue;
            }

            if ($field === 'general' && strcasecmp($value, 'Other') === 0) {
                $hasOtherEventType = true;
                continue;
            }

            $hasAdditionalSelection = true;
        }
    }

    return $hasOtherEventType && !$hasAdditionalSelection;
}

function event_feed_v4_occurrences($event, $date)
{
    $occurrences = array();
    $start = (int)$date['start-date'] / 1000;
    $end = (int)$date['end-date'] / 1000;
    if (!$start || $end < $start) {
        $end = $start;
    }

    $dayStart = $start;
    while ($dayStart <= $end) {
        $dailyDate = $date;
        $dailyDate['start-date'] = $dayStart * 1000;
        $occurrence = event_feed_v4_occurrence($event, $dailyDate);
        if ($occurrence !== null) {
            $occurrences[] = $occurrence;
        }

        // Match the legacy feed: ranges lasting at least 24 hours produce
        // one item per day, while shorter overnight events remain one item.
        if ($end - $start < 86400) {
            break;
        }
        $nextDayStart = strtotime(date('Y-m-d H:i:s', $dayStart) . ' +1 day');
        if ($nextDayStart <= $dayStart) {
            break;
        }
        $dayStart = $nextDayStart;
    }

    return $occurrences;
}

function event_feed_v4_occurrence($event, $date)
{
    global $PriorToToday;

    $start = (int)$date['start-date'] / 1000;
    $end = (int)$date['end-date'] / 1000;
    if (!$start) {
        return null;
    }
    if ($end < $start) {
        $end = $start;
    }
    $now = time();
    if ($now > $end && $PriorToToday !== 'Show') {
        return null;
    }

    $displayStart = $start;
    if (date('Y-m-d', $start) !== date('Y-m-d', $end) && $start <= $now && $end >= $now) {
        $displayStart = $now;
    }

    $item = $event;
    $item['date'] = array(
        'start-date' => $displayStart,
        'time-start-date' => $start,
        'end-date' => $end,
        'all-day' => $date['all-day'],
        'outside-of-minnesota' => $date['outside-of-minnesota'],
        'time-zone' => $date['time-zone']
    );
    $item['start-date'] = $displayStart;
    $item['end-date'] = $end;
    $item['date-for-sorting'] = $displayStart;
    return display_on_feed_events($item) ? $item : null;
}

function event_feed_v4_sort_events($a, $b)
{
    if ($a['date-for-sorting'] == $b['date-for-sorting']) {
        return strcasecmp($a['title'], $b['title']);
    }
    return $a['date-for-sorting'] < $b['date-for-sorting'] ? -1 : 1;
}

function display_on_feed_events($event)
{
    global $StartDate;
    global $EndDate;

    $start = $event['date']['start-date'];
    $end = $event['date']['end-date'];
    $rangeStart = $StartDate !== '' && $StartDate !== null ? (int)$StartDate / 1000 : null;
    $rangeEnd = $EndDate !== '' && $EndDate !== null ? (int)$EndDate / 1000 : null;

    if ($rangeStart !== null && $rangeEnd !== null) {
        return $rangeStart < $end && $start < $rangeEnd;
    }
    if ($rangeStart !== null) {
        return $rangeStart < $end;
    }
    if ($rangeEnd !== null) {
        return $start < $rangeEnd;
    }
    return true;
}

function get_event_html($event)
{
    $twig = makeTwigEnviron('/code/events/twig');
    $twig->addFilter(new Twig_SimpleFilter('convert_path_to_link', 'convert_path_to_link'));
    $twig->addFilter(new Twig_SimpleFilter('format_fancy_event_date', 'format_fancy_event_date'));
    $twig->addFilter(new Twig_SimpleFilter('get_month_shorthand_name', 'get_month_shorthand_name'));
    $twig->addFilter(new Twig_SimpleFilter('get_timezone_shorthand', 'get_timezone_shorthand'));

    return $twig->render('get_event_html_v4.html', array(
        'event' => $event,
        'start' => $event['date']['start-date']
    ));
}

function format_fancy_event_date($date)
{
    if (!isset($date['start-date']) || $date['start-date'] === '') {
        return '';
    }
    if (event_v4_is_yes($date['all-day'])) {
        return '';
    }
    $start = isset($date['time-start-date']) ? $date['time-start-date'] : $date['start-date'];
    $formatted = date('g:i a', $start);
    if ($formatted === '12:00 pm') {
        return 'Noon';
    }
    $formatted = str_replace(array('am', 'pm'), array('a.m.', 'p.m.'), $formatted);
    return str_replace(':00', '', $formatted);
}

function convert_path_to_link($event)
{
    return $event['external-link'] !== ''
        ? $event['external-link']
        : 'https://www.bethel.edu' . $event['path'];
}

function get_month_shorthand_name($month)
{
    $month = strtoupper($month);
    if ($month === 'JULY' || $month === 'JUNE') {
        return $month;
    }
    if ($month === 'SEPTEMBER') {
        return 'SEPT';
    }
    return substr($month, 0, 3);
}

function get_timezone_shorthand($date)
{
    if (isset($date['outside-of-minnesota']) && event_v4_is_yes($date['outside-of-minnesota'])) {
        return event_v4_timezone_abbreviation($date['time-zone']);
    }
    return '';
}

?>
