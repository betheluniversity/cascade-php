<?php

/**
 * Render two events related to the current event.
 *
 * The current page is resolved from REQUEST_URI and its metadata is read
 * directly from events.xml, just like each candidate event.
 *
 * @param mixed $currentEvent Kept for template-call compatibility.
 * @return string
 */
function create_related_events($currentEvent = null)
{
    $eventXml = related_events_load_xml();
    if (!$eventXml) {
        return '';
    }

    $pages = related_events_pages($eventXml);
    $currentPath = related_events_resolve_request_path($pages);

    if ($currentPath === '') {
        return '';
    }

    $currentPage = related_events_find_page($pages, $currentPath);

    if (!$currentPage) {
        return '';
    }

    $currentMetadata = related_events_metadata($currentPage);

    // Prefer office or department/program matches. Academic dates remain in
    // the fallback tier until those values are folded into general.
    $matchingTiers = array(
        array('offices', 'departments-programs'),
        array('academic-dates', 'general')
    );

    $selected = array();
    $nextMatches = array();
    $selectedTier = array();

    foreach ($matchingTiers as $matchingTier) {
        $matches = array();

        foreach ($pages as $pageXml) {
            $path = related_events_normalize_path((string)$pageXml->path);

            if (
                $path === '' ||
                $path === $currentPath ||
                strpos($path, '/_testing/') === 0 ||
                related_events_is_hidden($pageXml)
            ) {
                continue;
            }

            $candidateMetadata = related_events_metadata($pageXml);
            if (!related_events_matches($currentMetadata, $candidateMetadata, $matchingTier)) {
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

        usort($matches, 'related_events_sort');

        if (sizeof($matches) > 0) {
            $selected = array_slice($matches, 0, 2);
            $nextMatches = array_slice($matches, 2, 4);
            $selectedTier = $matchingTier;
            break;
        }
    }

    if (sizeof($selected) === 0) {
        return related_events_debug_output($currentMetadata, $selected, $nextMatches, $selectedTier);
    }

    $html = '<section class="related-events">';
    $html .= '<h2>Related Events</h2>';

    foreach ($selected as $event) {
        $html .= related_events_render($event['xml'], $event['occurrence']);
    }

    $html .= '</section>';
    $html .= related_events_debug_output(
        $currentMetadata,
        $selected,
        $nextMatches,
        $selectedTier
    );

    return $html;
}

function related_events_debug_output($currentMetadata, $selected, $nextMatches, $matchingTier)
{
    if (!isset($_GET['related_events_debug']) || $_GET['related_events_debug'] !== '1') {
        return '';
    }

    $report = array(
        'matching_tier' => $matchingTier,
        'current_page_metadata' => $currentMetadata,
        'selected_events' => related_events_debug_events($selected),
        'next_four_matches' => related_events_debug_events($nextMatches)
    );

    return '<pre class="related-events-debug">' .
        related_events_escape(json_encode($report, JSON_PRETTY_PRINT)) .
        '</pre>';
}

function related_events_debug_events($events)
{
    $output = array();

    foreach ($events as $event) {
        $output[] = array(
            'title' => trim((string)$event['xml']->title),
            'path' => $event['path'],
            'end' => date('c', $event['occurrence']['end']),
            'metadata' => related_events_metadata($event['xml'])
        );
    }

    return $output;
}

function related_events_load_xml()
{
    $file = $_SERVER['DOCUMENT_ROOT'] . '/_shared-content/xml/events.xml';

    if (!is_readable($file)) {
        return false;
    }

    return function_exists('autoCache')
        ? autoCache('simplexml_load_file', array($file))
        : simplexml_load_file($file);
}

function related_events_pages($eventXml)
{
    $pages = $eventXml->xpath('//system-page');

    if (!is_array($pages)) {
        return array();
    }

    return array_filter($pages, 'related_events_is_event_page');
}

function related_events_is_event_page($xml)
{
    $definition = trim((string)$xml->{'system-data-structure'}['definition-path']);

    return in_array($definition, array('Event', 'Event v2', 'Event v4'), true);
}

function related_events_is_hidden($xml)
{
    foreach ($xml->{'dynamic-metadata'} as $node) {
        if (trim((string)$node->name) !== 'hide-from-calendar') {
            continue;
        }

        foreach ($node->value as $value) {
            if (strtolower(trim((string)$value)) === 'yes') {
                return true;
            }
        }
    }

    return false;
}

/**
 * Resolve the current request to the corresponding path in events.xml.
 *
 * Test pages may be published under /_testing/ while retaining the same
 * filename as their production source event. A unique filename match lets
 * that source event be excluded without borrowing its metadata.
 */
function related_events_resolve_request_path($pages)
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

function related_events_find_page($pages, $path)
{
    foreach ($pages as $pageXml) {
        if (related_events_normalize_path((string)$pageXml->path) === $path) {
            return $pageXml;
        }
    }

    return false;
}

function related_events_metadata($xml)
{
    $metadata = array();

    foreach ($xml->{'dynamic-metadata'} as $node) {
        $name = related_events_metadata_name((string)$node->name);
        if ($name === '') {
            continue;
        }

        foreach ($node->value as $value) {
            $value = related_events_normalize((string)$value);

            if ($value !== '' && !in_array($value, array('none', 'select'), true)) {
                if (!isset($metadata[$name])) {
                    $metadata[$name] = array();
                }
                $metadata[$name][$value] = true;
            }
        }
    }

    if (isset($metadata['general'])) {
        $location = related_events_location($xml->{'system-data-structure'});
        $location = related_events_normalize($location);

        if ($location !== '' && isset($metadata['general'][$location])) {
            unset($metadata['general'][$location]);
        }
    }

    return $metadata;
}

/**
 * Map the source metadata fields to the v2 matching groups. The option
 * values are read from each event; only the field names are defined here.
 */
function related_events_metadata_name($name)
{
    $name = trim((string)$name);

    if ($name === 'general' || $name === 'offices' || $name === 'academic-dates') {
        return $name;
    }

    if ($name === 'departments-programs' || in_array($name, array(
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
    $data = $xml->{'system-data-structure'};

    if (trim((string)$data['definition-path']) === 'Event') {
        foreach ($data->{'event-dates'} as $date) {
            $occurrences[] = array(
                'start' => related_events_date($date, 'start-date'),
                'end' => related_events_date($date, 'end-date'),
                'all-day' => related_events_value($date, 'all-day')
            );
        }
    } elseif ($data->date) {
        $date = $data->date;
        $occurrences[] = array(
            'start' => related_events_date($date, 'eventStart'),
            'end' => related_events_date($date, 'eventEnd'),
            'all-day' => related_events_value($date, 'hideTime')
        );
    }

    $active = array();
    foreach ($occurrences as $occurrence) {
        if (
            $occurrence['start'] === false ||
            $occurrence['end'] === false ||
            $occurrence['end'] < time()
        ) {
            continue;
        }

        $active[] = $occurrence;
    }

    if (sizeof($active) === 0) {
        return false;
    }

    usort($active, function ($a, $b) {
        if ($a['end'] == $b['end']) {
            return 0;
        }

        return ($a['end'] < $b['end']) ? -1 : 1;
    });

    return $active[0];
}

function related_events_date($date, $name)
{
    $value = related_events_value($date, $name);
    if ($value === '') {
        return false;
    }

    return is_numeric($value) ? (float)$value / 1000 : false;
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
    $path = trim((string)$xml->path);
    $link = trim((string)$data->link);
    if ($link === '') {
        $link = 'https://www.bethel.edu/' . ltrim($path, '/');
    }
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
    if ($description !== '') {
        $html .= '<p class="events__description"><span itemprop="description">';
        $html .= related_events_escape($description) . '</span></p>';
    }
    $html .= '</div></div>';

    return $html;
}

function related_events_location($data)
{
    $definition = trim((string)$data['definition-path']);

    if ($definition === 'Event') {
        $locationType = trim((string)$data->location);
        $location = ($locationType === 'On campus' || $locationType === 'On Campus')
            ? trim((string)$data->{'on-campus-location'})
            : trim((string)$data->{'off-campus-location'});
        $other = trim((string)$data->{'other-on-campus'});

        if ($other !== '') {
            $location = $other;
        }

        return strtolower($location) === 'none' ? '' : $location;
    }

    $location = $data->location;
    $locationType = strtolower(preg_replace(
        '/[^a-z]/',
        '',
        trim((string)$location->locationSelect)
    ));

    if ($locationType === 'oncampus') {
        return trim((string)$location->onCampusLocation->location);
    }

    if ($locationType === 'offcampus') {
        return trim((string)$location->offCampusLocation->name);
    }

    if ($locationType === 'online') {
        return 'Online';
    }

    return '';
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
