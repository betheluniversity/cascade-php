<?php
/**
 * V5 calendar endpoint.
 *
 * Event v4 pages use the shared normalized event-data layer. Legacy Event
 * pages retain the established calendar_rest.php parsing behavior.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/events/php/event_data_v4.php';
define('CALENDAR_REST_LIBRARY_ONLY', true);
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/events/php/calendar_rest.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/code/vendor/autoload.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}
if ($year < 1970 || $year > 9999) {
    $year = (int)date('Y');
}

$cachedData = autoCache(
    'build_calendar_data_v5',
    array($month, $year)
);
$data = json_decode($cachedData, true);
if (!is_array($data)) {
    $data = array();
}
$data['remote_user'] = isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : null;
echo json_encode($data);

function build_calendar_data_v5($month, $year)
{
    $next = calendar_v5_adjacent_month($month, $year, 1);
    $previous = calendar_v5_adjacent_month($month, $year, -1);

    $data = array(
        'previous_title' => 'Previous Month',
        'next_title' => 'Next Month',
        'next_month_qs' => 'month=' . $next->format('n') . '&year=' . $next->format('Y'),
        'previous_month_qs' => 'month=' . $previous->format('n') . '&year=' . $previous->format('Y'),
        'current_month_qs' => 'month=' . $month . '&year=' . $year,
        'grid' => draw_calendar_v5($month, $year),
        'month_title' => calendar_v5_month_name($month) . ' ' . $year
    );

    return json_encode($data);
}

function calendar_v5_adjacent_month($month, $year, $direction)
{
    $date = new DateTime();
    $date->setDate($year, $month, 1);
    return $date->modify($direction > 0 ? '+1 month' : '-1 month');
}

function calendar_v5_month_name($month)
{
    $date = DateTime::createFromFormat('!m', $month);
    return $date->format('F');
}

function draw_calendar_v5($month, $year)
{
    $monthStart = mktime(0, 0, 0, $month, 1, $year);
    $monthEnd = strtotime('+1 month', $monthStart) - 1;
    $v4Events = array();
    foreach (event_v4_get_events() as $event) {
        if ($event['definition'] === 'Event v4') {
            $v4Events[] = $event;
        }
    }
    $eventsByDate = event_v4_calendar_date_map($v4Events, $monthStart, $monthEnd);
    if (event_v4_legacy_compatibility()) {
        $eventsByDate = calendar_v5_merge_legacy_events(
            $eventsByDate,
            get_event_xml(),
            $monthStart,
            $monthEnd
        );
    }
    $classes = array(
        1 => 'sun',
        2 => 'mon',
        3 => 'tue',
        4 => 'wed',
        5 => 'thu',
        6 => 'fri',
        7 => 'sat'
    );
    $runningDay = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
    $daysInThisWeek = 1;
    $calendar = '<ul class="calendar-row">';

    for ($index = 0; $index < $runningDay; $index++) {
        $back = '-' . ($runningDay - $index) . ' days';
        $lastMonthDate = date('j', strtotime($back, strtotime($year . '-' . $month . '-01')));
        $calendar .= '<li class="' . $classes[$daysInThisWeek] . ' event not-current"><span>'
            . $lastMonthDate
            . '</span></li>';
        $daysInThisWeek++;
    }

    $twig = makeTwigEnviron('/code/events/twig');
    $twig->getExtension('Twig_Extension_Core')->setTimezone('America/Chicago');
    $calendar .= $twig->render('calendar_rest.html', array(
        'running_day' => $runningDay,
        'days_in_month' => $daysInMonth,
        'days_in_this_week' => $daysInThisWeek,
        'day_counter' => 0,
        'classes' => $classes,
        'xml' => $eventsByDate,
        'year' => $year,
        'month' => $month
    ));

    return $calendar;
}

function calendar_v5_merge_legacy_events($eventsByDate, $legacyEvents, $rangeStart, $rangeEnd)
{
    foreach ($legacyEvents as $date => $events) {
        $dateTimestamp = strtotime($date . ' 00:00:00');
        if ($dateTimestamp < $rangeStart || $dateTimestamp > $rangeEnd) {
            continue;
        }
        if (!isset($eventsByDate[$date])) {
            $eventsByDate[$date] = array();
        }
        foreach ($events as $event) {
            $eventsByDate[$date][] = $event;
        }
    }

    foreach ($eventsByDate as $date => $events) {
        usort($events, 'event_v4_sort_calendar_records');
        $eventsByDate[$date] = $events;
    }
    return $eventsByDate;
}

?>
