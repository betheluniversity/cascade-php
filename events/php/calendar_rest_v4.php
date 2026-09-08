<?php
/**
 * V4 calendar endpoint.
 *
 * Event v4 and legacy Event pages use the shared normalized event-data layer.
 */
header('Content-Type: application/json; charset=utf-8');
// This response varies by the authenticated user's role. Prevent browser,
// proxy, and CDN caches from replaying a guest grid to a logged-in user (or
// an internal grid to a different user).
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Vary: Cookie');
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/macros.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/code/events/php/event_data_v4.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/msal.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/code/vendor/autoload.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}
if ($year < 1970 || $year > 9999) {
    $year = (int)date('Y');
}

$access = calendar_data_internal_access();

// The rendered calendar is user-dependent: staff, students, and guests must
// receive different event sets. Keep this payload request-scoped so a guest
// grid cannot be served to a logged-in user (or expose internal events to a
// guest). event_data_get_events() still caches the shared normalized event
// collection.
$data = calendar_data_build_payload($month, $year, $access['level']);
$data['remote_user'] = calendar_data_authenticated_user();
$data['internal_access'] = $access['level'];
echo json_encode($data);

function calendar_data_internal_access()
{
    if (!phpMSAL::checkAuthentication()) {
        return array('level' => 'guest');
    }

    $groups = phpMSAL::getUserGroups();
    $groups = is_array($groups) ? $groups : array();
    $isStaffOrFaculty = false;
    $isStudent = false;

    foreach ($groups as $group) {
        $group = strtoupper(trim((string)$group));
        if (strpos($group, 'STAFF') !== false || strpos($group, 'FACULTY') !== false) {
            $isStaffOrFaculty = true;
        }
        if (strpos($group, 'STUDENT') !== false) {
            $isStudent = true;
        }
    }

    if ($isStaffOrFaculty) {
        return array('level' => 'staff');
    }
    if ($isStudent) {
        return array('level' => 'student');
    }

    return array('level' => 'guest');
}

function calendar_data_authenticated_user()
{
    if (phpMSAL::checkAuthentication()) {
        return phpMSAL::getDisplayName();
    }

    if (isset($_SERVER['REMOTE_USER']) && $_SERVER['REMOTE_USER'] !== '') {
        return $_SERVER['REMOTE_USER'];
    }

    return null;
}

function calendar_data_build_payload($month, $year, $internalAccess = 'guest')
{
    $next = calendar_data_adjacent_month($month, $year, 1);
    $previous = calendar_data_adjacent_month($month, $year, -1);

    $data = array(
        'previous_title' => 'Previous Month',
        'next_title' => 'Next Month',
        'next_month_qs' => 'month=' . $next->format('n') . '&year=' . $next->format('Y'),
        'previous_month_qs' => 'month=' . $previous->format('n') . '&year=' . $previous->format('Y'),
        'current_month_qs' => 'month=' . $month . '&year=' . $year,
        'grid' => calendar_data_draw($month, $year, $internalAccess),
        'month_title' => calendar_data_month_name($month) . ' ' . $year
    );

    return $data;
}

function calendar_data_adjacent_month($month, $year, $direction)
{
    $date = new DateTime();
    $date->setDate($year, $month, 1);
    return $date->modify($direction > 0 ? '+1 month' : '-1 month');
}

function calendar_data_month_name($month)
{
    $date = DateTime::createFromFormat('!m', $month);
    return $date->format('F');
}

function calendar_data_draw($month, $year, $internalAccess = 'guest')
{
    $monthStart = mktime(0, 0, 0, $month, 1, $year);
    $monthEnd = strtotime('+1 month', $monthStart) - 1;
    $calendarEvents = array();
    foreach (event_data_get_events() as $event) {
        // Match the populations used by the previous hybrid endpoint: the old
        // parser selected Event pages and the normalized path selected Event v4.
        if (in_array($event['definition'], array('Event', 'Event v4'), true)) {
            $calendarEvents[] = $event;
        }
    }
    $eventsByDate = event_data_calendar_date_map(
        $calendarEvents,
        $monthStart,
        $monthEnd,
        $internalAccess
    );
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
