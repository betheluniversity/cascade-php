<?php

/**
 * Render two events related to the current event.
 *
 * Pass either the current event's system-page SimpleXML object or a context
 * array containing the current page's live metadata. The function returns an
 * empty string when no related events are found.
 *
 * @param SimpleXMLElement|array|null $currentEvent
 * @return string
 */
function create_related_events($currentEvent = null)
{
    $eventXml = related_events_load_xml();
    if (!$eventXml) {
        return '';
    }

    if (is_object($currentEvent)) {
        $currentPath = related_events_normalize_path((string)$currentEvent->path);
        $currentMetadata = related_events_metadata($currentEvent);
    } elseif (
        is_array($currentEvent) &&
        isset($currentEvent['metadata']) &&
        is_array($currentEvent['metadata'])
    ) {
        $currentPath = related_events_resolve_request_path($eventXml);
        $currentMetadata = related_events_metadata_input($currentEvent['metadata']);
    } else {
        return '';
    }

    if ($currentPath === '') {
        return '';
    }

    $tiers = array();
    $eventTypes = related_events_values($currentMetadata, 'general');
    $organizationalNames = array(
        'offices',
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    );
    $hasOrganizationalMetadata = false;

    foreach ($organizationalNames as $name) {
        if (sizeof(related_events_values($currentMetadata, $name)) > 0) {
            $hasOrganizationalMetadata = true;
            break;
        }
    }

    // Office and Department/Program selections take precedence over Event
    // Type. Match any exact shared organizational value, including multiple
    // selected programs. Use Event Type (including "Other") only when no
    // organizational metadata is assigned.
    if ($hasOrganizationalMetadata) {
        $tiers[] = $organizationalNames;
    } elseif (sizeof($eventTypes) > 0) {
        $tiers[] = array('general');
    }

    $pages = $eventXml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
    $selected = array();
    $selectedPaths = array();

    foreach ($tiers as $tier) {
        $matches = array();

        foreach ($pages as $pageXml) {
            $path = related_events_normalize_path((string)$pageXml->path);

            if ($path === '' || $path === $currentPath || strpos($path, '_testing') !== false) {
                continue;
            }

            if (isset($selectedPaths[$path])) {
                continue;
            }

            $metadata = related_events_metadata($pageXml);
            if (!related_events_matches($currentMetadata, $metadata, $tier)) {
                continue;
            }

            $occurrence = related_events_earliest_occurrence($pageXml);
            if (!$occurrence) {
                continue;
            }

            $matches[] = array(
                'path' => $path,
                'xml' => $pageXml,
                'metadata' => $metadata,
                'occurrence' => $occurrence
            );
        }

        usort($matches, 'related_events_sort');

        foreach ($matches as $match) {
            $selected[] = $match;
            $selectedPaths[$match['path']] = true;

            if (sizeof($selected) === 2) {
                break 2;
            }
        }
    }

    if (sizeof($selected) === 0) {
        return '';
    }

    $html = '<section class="related-events">';
    $html .= '<h2>Related Events</h2>';

    foreach ($selected as $event) {
        $html .= related_events_render($event['xml'], $event['occurrence']);
    }

    return $html . '</section>';
}

function related_events_load_xml()
{
    $file = $_SERVER['DOCUMENT_ROOT'] . '/_shared-content/xml/events.xml';

    if (function_exists('autoCache')) {
        return autoCache('simplexml_load_file', array($file));
    }

    return simplexml_load_file($file);
}

/**
 * Resolve the current request to the corresponding path in events.xml.
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

    $pages = $eventXml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
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

    foreach ($xml->{'dynamic-metadata'} as $node) {
        $name = trim((string)$node->name);
        if ($name === '') {
            continue;
        }

        if (!isset($metadata[$name])) {
            $metadata[$name] = array();
        }

        foreach ($node->value as $value) {
            $value = related_events_normalize((string)$value);

            if ($value !== '' && !in_array($value, array('none', 'select'), true)) {
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
        $name = trim((string)$name);
        if ($name === '') {
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

            if (!isset($metadata[$name])) {
                $metadata[$name] = array();
            }

            $metadata[$name][$value] = true;
        }
    }

    return $metadata;
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
    $dates = $xml->{'system-data-structure'}->{'event-dates'};

    foreach ($dates as $date) {
        $start = related_events_date($date, 'start-date');
        $end = related_events_date($date, 'end-date');

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
    $data = $xml->{'system-data-structure'};
    $externalLink = trim((string)$data->link);
    $path = trim((string)$xml->path);
    $link = $externalLink !== '' ? $externalLink : 'https://www.bethel.edu' . $path;
    $title = trim((string)$xml->title);
    $description = trim(strip_tags((string)$xml->description));
    $location = related_events_location($data);
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
    $html .= '<p class="events__description"><span itemprop="description">';
    $html .= related_events_escape($description) . '</span></p>';
    $html .= '</div></div>';

    return $html;
}

function related_events_location($data)
{
    $locationType = trim((string)$data->location);

    if ($locationType === 'On campus' || $locationType === 'On Campus') {
        $location = trim((string)$data->{'on-campus-location'});
    } else {
        $location = trim((string)$data->{'off-campus-location'});
    }

    $other = trim((string)$data->{'other-on-campus'});
    if ($other !== '') {
        $location = $other;
    }

    return $location === 'none' ? '' : $location;
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
    return preg_replace('/\.(?:php|html?|xml)$/i', '', $path);
}

function related_events_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

?>
