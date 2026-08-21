<?php

/**
 * Render related events using metadata supplied by the current page template.
 *
 * Office and same-field department/program matches are selected first. Event
 * Type matches fill any remaining slots.
 *
 * @param array|null $currentEvent Current page metadata context.
 * @param int $limit Maximum number of events to render.
 * @return string
 */
function create_related_events($currentEvent = null, $limit = 2)
{
    $limit = (int)$limit;
    if (
        $limit < 1 ||
        !is_array($currentEvent) ||
        !isset($currentEvent['metadata']) ||
        !is_array($currentEvent['metadata'])
    ) {
        return '';
    }

    $currentMetadata = related_events_current_metadata($currentEvent['metadata']);
    $eventXml = related_events_load_xml();
    if (!$eventXml) {
        return related_events_debug_output($currentMetadata, array(), array(), 'events.xml could not be loaded');
    }

    $pages = $eventXml->xpath('//system-page');
    if (!is_array($pages)) {
        return related_events_debug_output($currentMetadata, array(), array(), 'No event pages were found');
    }

    $currentPath = related_events_request_path();
    $organizationFields = array(
        'offices',
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    );

    $organizationMatches = related_events_candidates(
        $pages,
        $currentMetadata,
        $organizationFields,
        $currentPath,
        array(),
        'office-or-department-program'
    );

    $organizationPaths = array();
    foreach ($organizationMatches as $event) {
        $organizationPaths[$event['path']] = true;
    }

    $eventTypeMatches = array();
    if (
        sizeof($organizationMatches) < $limit ||
        (isset($_GET['related_events_debug']) && $_GET['related_events_debug'] === '1')
    ) {
        $eventTypeMatches = related_events_candidates(
            $pages,
            $currentMetadata,
            array('general'),
            $currentPath,
            $organizationPaths,
            'event-type'
        );
    }

    $orderedMatches = array_merge($organizationMatches, $eventTypeMatches);
    $selected = array_slice($orderedMatches, 0, $limit);
    $nextMatches = array_slice($orderedMatches, $limit, 4);

    if (sizeof($selected) === 0) {
        return related_events_debug_output($currentMetadata, $selected, $nextMatches);
    }

    $html = '<section class="pt2 pb4"><div class="inner--content pt3" style="border-top: 1px solid #ddd">';
    $html .= '<section class="related-events"><h4 class="mt0">Related Events</h2>';
    $html .= '<div class="grid grid-cols-2--large">';
    foreach ($selected as $event) {
        $html .= related_events_render($event);
    }
    $html .= '</div></section></div></section>';

    return $html . related_events_debug_output($currentMetadata, $selected, $nextMatches);
}

function related_events_current_metadata($input)
{
    $fields = array(
        'general',
        'offices',
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    );
    $metadata = array();

    foreach ($fields as $field) {
        $metadata[$field] = array();
        if (!isset($input[$field])) {
            continue;
        }

        $values = is_array($input[$field]) ? $input[$field] : array($input[$field]);
        foreach ($values as $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $value = trim((string)$value);
            if (
                $value !== '' &&
                !in_array(strtolower($value), array('none', 'select'), true) &&
                !in_array($value, $metadata[$field], true)
            ) {
                $metadata[$field][] = $value;
            }
        }
    }

    return $metadata;
}

function related_events_load_xml()
{
    if (!isset($_SERVER['DOCUMENT_ROOT'])) {
        return false;
    }

    $file = $_SERVER['DOCUMENT_ROOT'] . '/_shared-content/xml/events.xml';
    if (!is_readable($file)) {
        return false;
    }

    return function_exists('autoCache')
        ? autoCache('simplexml_load_file', array($file))
        : simplexml_load_file($file);
}

function related_events_candidates(
    $pages,
    $currentMetadata,
    $fields,
    $currentPath,
    $excludedPaths,
    $tier
)
{
    $matches = array();
    $seenPaths = array();

    foreach ($pages as $page) {
        if (!related_events_is_event_page($page) || related_events_is_hidden($page)) {
            continue;
        }

        $path = related_events_normalize_path((string)$page->path);
        if (
            $path === '' ||
            isset($seenPaths[$path]) ||
            isset($excludedPaths[$path]) ||
            strpos($path, '/_testing/') !== false ||
            $path === $currentPath ||
            (
                strpos($currentPath, '/_testing/') !== false &&
                basename($path) === basename($currentPath)
            )
        ) {
            continue;
        }
        $seenPaths[$path] = true;

        $candidateMetadata = related_events_page_metadata($page);
        $matchedFields = related_events_matching_fields(
            $currentMetadata,
            $candidateMetadata,
            $fields
        );
        if (sizeof($matchedFields) === 0) {
            continue;
        }

        $occurrence = related_events_earliest_occurrence($page);
        if (!$occurrence) {
            continue;
        }

        $matches[] = array(
            'path' => $path,
            'page' => $page,
            'metadata' => $candidateMetadata,
            'occurrence' => $occurrence,
            'matched_fields' => $matchedFields,
            'tier' => $tier
        );
    }

    usort($matches, 'related_events_sort_candidates');
    return $matches;
}

function related_events_is_event_page($page)
{
    $definition = trim((string)$page->{'system-data-structure'}['definition-path']);
    return in_array($definition, array('Event', 'Event v4'), true);
}

function related_events_is_hidden($page)
{
    foreach ($page->{'dynamic-metadata'} as $field) {
        if (trim((string)$field->name) !== 'hide-from-calendar') {
            continue;
        }

        foreach ($field->value as $value) {
            if (strtolower(trim((string)$value)) === 'yes') {
                return true;
            }
        }
    }

    return false;
}

function related_events_page_metadata($page)
{
    $metadata = related_events_current_metadata(array());

    foreach ($page->{'dynamic-metadata'} as $field) {
        $name = trim((string)$field->name);
        if (!isset($metadata[$name])) {
            continue;
        }

        foreach ($field->value as $value) {
            $value = trim((string)$value);
            if (
                $value !== '' &&
                !in_array(strtolower($value), array('none', 'select'), true) &&
                !in_array($value, $metadata[$name], true)
            ) {
                $metadata[$name][] = $value;
            }
        }
    }

    return $metadata;
}

function related_events_matching_fields($current, $candidate, $fields)
{
    $matched = array();

    foreach ($fields as $field) {
        if (!isset($current[$field]) || !isset($candidate[$field])) {
            continue;
        }

        $currentValues = array_map('related_events_normalize', $current[$field]);
        $candidateValues = array_map('related_events_normalize', $candidate[$field]);

        if (sizeof(array_intersect($currentValues, $candidateValues)) > 0) {
            $matched[] = $field;
        }
    }

    return $matched;
}

function related_events_earliest_occurrence($page)
{
    $data = $page->{'system-data-structure'};
    $eventDates = $data->{'event-dates'};
    $usesEventDates = count($eventDates) > 0;
    $dates = $usesEventDates ? $eventDates : $data->date;
    $startNames = $usesEventDates ? array('start-date') : array('eventStart', 'start');
    $endNames = $usesEventDates ? array('end-date') : array('eventEnd', 'end');
    $allDayNames = $usesEventDates ? array('all-day') : array('hideTime', 'all-day');
    $earliest = false;

    foreach ($dates as $date) {
        $start = related_events_timestamp(related_events_child_value($date, $startNames));
        $end = related_events_timestamp(related_events_child_value($date, $endNames));
        if ($start === false) {
            continue;
        }
        if ($end === false) {
            $end = $start;
        }
        if ($end < time() || ($earliest && $end >= $earliest['end'])) {
            continue;
        }

        $earliest = array(
            'start' => $start,
            'end' => $end,
            'all-day' => related_events_child_value($date, $allDayNames)
        );
    }

    return $earliest;
}

function related_events_child_value($node, $names)
{
    foreach ($names as $name) {
        if (!isset($node->{$name})) {
            continue;
        }

        if (isset($node->{$name}->value)) {
            return trim((string)$node->{$name}->value);
        }

        return trim((string)$node->{$name});
    }

    return '';
}

function related_events_timestamp($value)
{
    if (!is_numeric($value)) {
        return false;
    }

    $timestamp = (float)$value;
    return $timestamp > 9999999999 ? $timestamp / 1000 : $timestamp;
}

function related_events_sort_candidates($a, $b)
{
    if ($a['occurrence']['end'] == $b['occurrence']['end']) {
        return strcmp($a['path'], $b['path']);
    }

    return $a['occurrence']['end'] < $b['occurrence']['end'] ? -1 : 1;
}

function related_events_render($event)
{
    $page = $event['page'];
    $data = $page->{'system-data-structure'};
    $occurrence = $event['occurrence'];
    $title = trim((string)$page->title);
    $description = trim(strip_tags((string)$page->description));
    $location = related_events_location($data);
    $time = related_events_time_text($occurrence);
    $displayDate = $occurrence['start'];
    if (
        date('Y-m-d', $occurrence['start']) !== date('Y-m-d', $occurrence['end']) &&
        $occurrence['start'] <= time() &&
        $occurrence['end'] >= time()
    ) {
        $displayDate = time();
    }
    $link = trim((string)$data->link);

    if (strpos($link, 'http://') !== 0 && strpos($link, 'https://') !== 0) {
        $link = 'https://www.bethel.edu' . $event['path'];
    }

    $twig = makeTwigEnviron('/code/events/twig');
    return $twig->render('related_event_html.html', array(
        'title' => $title,
        'link' => $link,
        'month' => strtoupper(date('M', $displayDate)),
        'day' => date('j', $displayDate),
        'year' => date('Y', $displayDate),
        'time' => $time,
        'location' => $location,
        'description' => $description
    ));
}

function related_events_location($data)
{
    $definition = trim((string)$data['definition-path']);

    if ($definition === 'Event') {
        $type = strtolower(trim((string)$data->location));
        $location = strpos($type, 'on campus') !== false
            ? trim((string)$data->{'on-campus-location'})
            : trim((string)$data->{'off-campus-location'});
        $other = trim((string)$data->{'other-on-campus'});

        if ($other !== '') {
            $location = $other;
        }

        return strtolower($location) === 'none' ? '' : $location;
    }

    $location = $data->location;
    $type = strtolower(preg_replace('/[^a-z]/i', '', trim((string)$location->locationSelect)));

    if ($type === 'oncampus') {
        return trim((string)$location->onCampusLocation->location);
    }
    if ($type === 'offcampus') {
        return trim((string)$location->offCampusLocation->name);
    }
    if ($type === 'online') {
        return 'Online';
    }

    return '';
}

function related_events_time_text($occurrence)
{
    if (strtolower($occurrence['all-day']) === 'yes') {
        return '';
    }

    return str_replace(
        array(':00', 'am', 'pm'),
        array('', 'a.m.', 'p.m.'),
        date('g:i a', $occurrence['start'])
    );
}

function related_events_request_path()
{
    if (!isset($_SERVER['REQUEST_URI'])) {
        return '';
    }

    return related_events_normalize_path(
        (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
}

function related_events_normalize_path($path)
{
    $path = trim((string)$path);
    if ($path === '' || $path === '/') {
        return $path;
    }

    $path = preg_replace('/\.(?:php|html?)$/i', '', rtrim($path, '/'));
    return '/' . ltrim($path, '/');
}

function related_events_normalize($value)
{
    $value = html_entity_decode(trim((string)$value), ENT_QUOTES, 'UTF-8');
    return strtolower(preg_replace('/\s+/', ' ', $value));
}

function related_events_debug_output($currentMetadata, $selected, $nextMatches, $error = '')
{
    if (!isset($_GET['related_events_debug']) || $_GET['related_events_debug'] !== '1') {
        return '';
    }

    $report = array(
        'current_page_metadata' => $currentMetadata,
        'selected_events' => related_events_debug_events($selected),
        'next_four_matches' => related_events_debug_events($nextMatches)
    );
    if ($error !== '') {
        $report['error'] = $error;
    }

    return '<pre class="related-events-debug">' .
        related_events_escape(json_encode($report, JSON_PRETTY_PRINT)) .
        '</pre>';
}

function related_events_debug_events($events)
{
    $output = array();

    foreach ($events as $event) {
        $output[] = array(
            'title' => trim((string)$event['page']->title),
            'path' => $event['path'],
            'match_tier' => $event['tier'],
            'matched_fields' => $event['matched_fields'],
            'end' => date('c', $event['occurrence']['end']),
            'metadata' => $event['metadata']
        );
    }

    return $output;
}

function related_events_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
