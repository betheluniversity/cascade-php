<?php
/**
 * V4-compatible calendar endpoint. calendar_v4.js calls this file directly.
 */
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/events/php/event_data_v4.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/code/vendor/autoload.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}
if ($year < 1970 || $year > 9999) {
    $year = (int)date('Y');
}

echo autoCache(
    'build_calendar_data_v4',
    array($month, $year)
);

function build_calendar_data_v4($month, $year)
{
    $next = calendar_v4_adjacent_month($month, $year, 1);
    $previous = calendar_v4_adjacent_month($month, $year, -1);

    $data = array(
        'previous_title' => 'Previous Month',
        'next_title' => 'Next Month',
        'next_month_qs' => 'month=' . $next->format('n') . '&year=' . $next->format('Y'),
        'previous_month_qs' => 'month=' . $previous->format('n') . '&year=' . $previous->format('Y'),
        'current_month_qs' => 'month=' . $month . '&year=' . $year,
        'grid' => draw_calendar_v4($month, $year),
        'month_title' => calendar_v4_month_name($month) . ' ' . $year,
        'remote_user' => isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : null
    );

    return json_encode($data);
}

function calendar_v4_adjacent_month($month, $year, $direction)
{
    $date = new DateTime();
    $date->setDate($year, $month, 1);
    return $date->modify($direction > 0 ? '+1 month' : '-1 month');
}

function calendar_v4_month_name($month)
{
    $date = DateTime::createFromFormat('!m', $month);
    return $date->format('F');
}

function draw_calendar_v4($month, $year)
{
    $monthStart = mktime(0, 0, 0, $month, 1, $year);
    $monthEnd = strtotime('+1 month', $monthStart) - 1;
    $eventsByDate = event_v4_calendar_date_map(event_v4_get_events(), $monthStart, $monthEnd);
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

?>
