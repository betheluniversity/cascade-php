<?php

if (isset($_GET['related_events_debug']) && $_GET['related_events_debug'] === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    register_shutdown_function('related_events_debug_shutdown');
}

function related_events_debug($message)
{
    if (!isset($_GET['related_events_debug']) || $_GET['related_events_debug'] !== '1') {
        return;
    }

    echo '<pre class="related-events-debug" style="white-space:pre-wrap">';
    echo htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
    echo '</pre>';
}

function related_events_debug_shutdown()
{
    $error = error_get_last();
    if (!$error || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        return;
    }

    related_events_debug(
        'Related Events fatal error: ' . $error['message'] .
        ' in ' . $error['file'] . ':' . $error['line']
    );
}

/**
 * Render two events related to the current event.
 *
 * Pass a context array containing the current page's live metadata. The
 * function returns an empty string when no related events are found.
 *
 * @param array|null $currentEvent
 * @return string
 */
function create_related_events($currentEvent = null)
{
    $eventXml = related_events_load_xml();
    if (!$eventXml) {
        related_events_debug('Related Events: the v2 events feed could not be loaded.');
        return '';
    }

    if (
        !is_array($currentEvent) ||
        !isset($currentEvent['metadata']) ||
        !is_array($currentEvent['metadata'])
    ) {
        related_events_debug(
            'Related Events: current metadata context was not received. Context type: ' .
            gettype($currentEvent)
        );
        return '';
    }

    $currentPath = related_events_resolve_request_path($eventXml);
    $currentMetadata = related_events_metadata_input($currentEvent['metadata']);

    if ($currentPath === '') {
        related_events_debug('Related Events: the current request path could not be resolved.');
        return '';
    }

    $eventTypes = related_events_values($currentMetadata, 'general');
    $organizationalNames = array(
        'offices',
        'departments-programs'
    );
    $matchingGroups = array();

    foreach ($organizationalNames as $name) {
        if (sizeof(related_events_values($currentMetadata, $name)) > 0) {
            $matchingGroups = $organizationalNames;
            break;
        }
    }

    // Every event has an Event Type. Office and Department/Program selections
    // are optional and take precedence when present. "Other" is a valid type.
    if (sizeof($matchingGroups) === 0 && sizeof($eventTypes) > 0) {
        $matchingGroups = array('general');
    }

    if (sizeof($matchingGroups) === 0) {
        related_events_debug(
            'Related Events: no usable matching metadata was received for ' .
            $currentPath . '. Metadata: ' . json_encode($currentMetadata)
        );
        return '';
    }

    $pages = $eventXml->event;
    $matches = array();

    foreach ($pages as $pageXml) {
        $path = related_events_normalize_path((string)$pageXml->path);

        if (
            $path === '' ||
            $path === $currentPath ||
            strpos($path, '/_testing/') === 0 ||
            strtolower(trim((string)$pageXml->hidden)) === 'yes'
        ) {
            continue;
        }

        if (!related_events_matches(
            $currentMetadata,
            related_events_metadata($pageXml),
            $matchingGroups
        )) {
            continue;
        }

        $occurrence = related_events_earliest_occurrence($pageXml);
        if (!$occurrence) {
            continue;
        }

        $matches[] = array(
            'path' => $path,
            'xml' => $pageXml,
            'occurrence' => $occurrence
        );
    }

    if (sizeof($matches) === 0) {
        related_events_debug(
            'Related Events: no active events matched ' . $currentPath .
            '. Matching group: ' . implode(', ', $matchingGroups) .
            '. Metadata: ' . json_encode($currentMetadata)
        );
        return '';
    }

    usort($matches, 'related_events_sort');
    $selected = array_slice($matches, 0, 2);

    $html = '<section class="related-events">';
    $html .= '<h2>Related Events</h2>';

    foreach ($selected as $event) {
        $html .= related_events_render($event['xml'], $event['occurrence']);
    }

    related_events_debug(
        'Related Events: rendered ' . sizeof($selected) . ' event(s) for ' . $currentPath . '.'
    );

    return $html . '</section>';
}

function related_events_load_xml()
{
    $root = $_SERVER['DOCUMENT_ROOT'];
    $files = array(
        $root . '/_testing/jake/events/events-feed-v2-page.xml',
        $root . '/_shared-content/xml/events-v2.xml',
        $root . '/_shared-content/xml/events-short.xml',
        $root . '/_shared-content/xml/events.xml'
    );

    foreach ($files as $file) {
        if (!is_readable($file)) {
            continue;
        }

        $xml = function_exists('autoCache')
            ? autoCache('simplexml_load_file', array($file))
            : simplexml_load_file($file);

        if ($xml && $xml->getName() === 'events' && isset($xml->event[0])) {
            return $xml;
        }
    }

    return false;
}

/**
 * Resolve the current request to the corresponding path in the v2 feed.
 *
 * Test pages may be published under /_testing/ while retaining the same
 * filename as their production source event. A unique filename match lets
 * that source event be excluded without borrowing its metadata.
 */
function related_events_resolve_request_path($eventXml)
{
    if (!isset($_SERVER['REQUEST_URI'])) {
        return '';
    }

    $requestPath = related_events_normalize_path(
        (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
    if ($requestPath === '') {
        return '';
    }

    $pages = $eventXml->event;
    $filenameMatches = array();

    foreach ($pages as $pageXml) {
        $path = related_events_normalize_path((string)$pageXml->path);

        if ($path === $requestPath) {
            return $path;
        }

        if (
            strpos($requestPath, '/_testing/') === 0 &&
            $path !== '' &&
            basename($path) === basename($requestPath)
        ) {
            $filenameMatches[] = $path;
        }
    }

    if (sizeof($filenameMatches) === 1) {
        return $filenameMatches[0];
    }

    return $requestPath;
}

function related_events_metadata($xml)
{
    $metadata = array();
    $groups = array(
        'general',
        'offices',
        'departments-programs'
    );

    foreach ($groups as $name) {
        foreach ($xml->{$name}->value as $value) {
            $value = related_events_normalize((string)$value);

            if ($value !== '' && !in_array($value, array('none', 'select'), true)) {
                if (!isset($metadata[$name])) {
                    $metadata[$name] = array();
                }
                $metadata[$name][$value] = true;
            }
        }
    }

    return $metadata;
}

/**
 * Normalize the live metadata array supplied by the Cascade template.
 */
function related_events_metadata_input($input)
{
    $metadata = array();

    foreach ($input as $name => $values) {
        $group = related_events_metadata_group($name);
        if ($group === '') {
            continue;
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $value = related_events_normalize($value);
            if ($value === '' || in_array($value, array('none', 'select'), true)) {
                continue;
            }

            if (!isset($metadata[$group])) {
                $metadata[$group] = array();
            }

            $metadata[$group][$value] = true;
        }
    }

    return $metadata;
}

/**
 * Map Cascade's metadata fields into the three related-event categories.
 */
function related_events_metadata_group($name)
{
    $name = trim((string)$name);

    if ($name === 'general' || $name === 'offices') {
        return $name;
    }

    if (in_array($name, array(
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    ), true)) {
        return 'departments-programs';
    }

    return '';
}

function related_events_values($metadata, $name)
{
    return isset($metadata[$name]) ? array_keys($metadata[$name]) : array();
}

function related_events_matches($current, $candidate, $names)
{
    foreach ($names as $name) {
        $currentValues = related_events_values($current, $name);
        $candidateValues = related_events_values($candidate, $name);

        if (sizeof(array_intersect($currentValues, $candidateValues)) > 0) {
            return true;
        }
    }

    return false;
}

function related_events_earliest_occurrence($xml)
{
    $occurrences = array();
    $dates = $xml->date;

    foreach ($dates as $date) {
        $start = related_events_date($date, 'start');
        $end = related_events_date($date, 'end');

        if ($end === false) {
            $end = $start;
        }

        if ($start === false || $end === false || $end < time()) {
            continue;
        }

        $occurrences[] = array(
            'start' => $start,
            'end' => $end,
            'all-day' => related_events_value($date, 'all-day'),
            'outside-of-minnesota' => related_events_value($date, 'outside-of-minnesota'),
            'time-zone' => related_events_value($date, 'time-zone')
        );
    }

    if (sizeof($occurrences) === 0) {
        return false;
    }

    usort($occurrences, function ($a, $b) {
        if ($a['end'] == $b['end']) {
            return 0;
        }

        return ($a['end'] < $b['end']) ? -1 : 1;
    });

    return $occurrences[0];
}

function related_events_date($date, $name)
{
    $value = related_events_value($date, $name);

    if ($value === '') {
        return false;
    }

    return ((float)$value) / 1000;
}

function related_events_value($date, $name)
{
    if (isset($date->{$name}->value)) {
        return trim((string)$date->{$name}->value);
    }

    return trim((string)$date->{$name});
}

function related_events_sort($a, $b)
{
    $aEnd = $a['occurrence']['end'];
    $bEnd = $b['occurrence']['end'];

    if ($aEnd == $bEnd) {
        return strcmp($a['path'], $b['path']);
    }

    return ($aEnd < $bEnd) ? -1 : 1;
}

function related_events_render($xml, $occurrence)
{
    $path = trim((string)$xml->path);
    $link = trim((string)$xml->url);
    if ($link === '') {
        $link = '/' . ltrim($path, '/');
    }
    $title = trim((string)$xml->title);
    $description = trim(strip_tags((string)$xml->description));
    $location = trim((string)$xml->location);
    $dateText = related_events_date_text($occurrence);

    $html = '<div class="events__item" itemscope="itemscope" itemtype="https://schema.org/Event">';
    $html .= '<div class="events__content">';
    $html .= '<p class="events__headline"><a href="' . related_events_escape($link) . '">';
    $html .= '<span itemprop="name">' . related_events_escape($title) . '</span></a></p>';
    $html .= '<p class="events__location">' . related_events_escape($dateText);

    if ($location !== '') {
        $html .= ' <span itemprop="location">' . related_events_escape($location) . '</span>';
    }

    $html .= '</p>';
    if ($description !== '') {
        $html .= '<p class="events__description"><span itemprop="description">';
        $html .= related_events_escape($description) . '</span></p>';
    }
    $html .= '</div></div>';

    return $html;
}

function related_events_date_text($occurrence)
{
    $start = $occurrence['start'];
    $end = $occurrence['end'];

    if (date('Y-m-d', $start) !== date('Y-m-d', $end)) {
        return date('F j, Y', $start) . ' - ' . date('F j, Y', $end);
    }

    if ($occurrence['all-day'] === 'Yes') {
        return date('F j, Y', $start);
    }

    $time = date('g:i a', $start);
    $time = str_replace('am', 'a.m.', $time);
    $time = str_replace('pm', 'p.m.', $time);

    return date('F j, Y', $start) . ' | ' . str_replace(':00', '', $time);
}

function related_events_normalize($value)
{
    $value = html_entity_decode(trim((string)$value), ENT_QUOTES, 'UTF-8');
    return strtolower(preg_replace('/\s+/', ' ', $value));
}

function related_events_normalize_path($path)
{
    $path = trim((string)$path);

    if ($path === '' || $path === '/') {
        return $path;
    }

    $path = rtrim($path, '/');
    $path = preg_replace('/\.(?:php|html?|xml)$/i', '', $path);

    return '/' . ltrim($path, '/');
}

function related_events_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
