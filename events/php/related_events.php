<?php
/**
 * Related Events module.
 *
 * This file intentionally does not modify or include the existing event feed
 * file. events_helper.php and general-cascade/feed_helper.php both define the
 * global traverse_folder() function, so including event_feed.php here can
 * cause a fatal redeclaration error depending on template include order.
 */

if (isset($_GET['related_events_debug']) && $_GET['related_events_debug'] === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    register_shutdown_function('related_events_debug_shutdown');
}

include_once $_SERVER["DOCUMENT_ROOT"] . "/code/php_helper_for_cascade.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/general-cascade/macros.php";
include_once $_SERVER["DOCUMENT_ROOT"] . "/code/vendor/autoload.php";

/**
 * Display fatal PHP errors when the explicit debug query parameter is used.
 * This is intentionally disabled for normal requests.
 *
 * @return void
 */
function related_events_debug_shutdown()
{
    $error = error_get_last();

    if (!$error || !in_array($error['type'], array(
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR
    ))) {
        return;
    }

    echo '<pre style="white-space: pre-wrap;">';
    echo "Related Events fatal error\n";
    echo htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8');
    echo "\nFile: " . htmlspecialchars($error['file'], ENT_QUOTES, 'UTF-8');
    echo "\nLine: " . (int)$error['line'];
    echo '</pre>';
}

/**
 * Report a nonfatal related-events diagnostic only when explicitly enabled.
 *
 * @param string $message
 * @return void
 */
function related_events_debug_message($message)
{
    if (!isset($_GET['related_events_debug']) || $_GET['related_events_debug'] !== '1') {
        return;
    }

    echo '<pre class="related-events-debug" style="white-space: pre-wrap;">';
    echo htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
    echo '</pre>';
}

/**
 * Render up to two events related to the supplied current event.
 *
 * The preferred argument is the system-page SimpleXML object for the event
 * detail page. When omitted, the current request path is used to find it in
 * events.xml. Its path and dynamic metadata are used for matching and to
 * exclude the current page from the results.
 *
 * @param SimpleXMLElement|array|null $currentEventXml
 * @param int $limit
 * @return string
 */
function create_related_events($currentEventXml = null, $limit = 2)
{
    $limit = (int)$limit;
    if ($limit < 1) {
        related_events_debug_message('Related Events: limit is less than 1.');
        return '';
    }

    $currentEventXml = related_events_resolve_current_event($currentEventXml);
    if (!is_object($currentEventXml)) {
        $requestPath = isset($_SERVER['REQUEST_URI'])
            ? (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
            : '';
        related_events_debug_message(
            'Related Events: current event was not found in events.xml. Request path: ' . $requestPath
        );
        return '';
    }

    $currentPath = related_events_get_path($currentEventXml);
    if ($currentPath === '') {
        related_events_debug_message('Related Events: the resolved event has no path.');
        return '';
    }

    $currentMetadata = related_events_get_metadata($currentEventXml);
    $candidates = related_events_get_candidates($currentPath);
    if (sizeof($candidates) === 0) {
        related_events_debug_message(
            'Related Events: no active candidate events were found for ' . $currentPath . '.'
        );
        return '';
    }

    $tiers = array();

    $organizationalMetadataNames = array(
        'offices',
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    );

    $hasOrganizationalMetadata = false;
    foreach ($organizationalMetadataNames as $metadataName) {
        if (sizeof(related_events_get_metadata_values($currentMetadata, $metadataName)) > 0) {
            $hasOrganizationalMetadata = true;
            break;
        }
    }

    // Office and Department/Program selections are more specific than Event
    // Type. If any are assigned, match candidates sharing any selected value
    // across those fields. This also supports multiple selected programs.
    $eventTypes = related_events_get_metadata_values($currentMetadata, 'general');
    if ($hasOrganizationalMetadata) {
        $tiers[] = $organizationalMetadataNames;
    } elseif (sizeof($eventTypes) > 0) {
        // Event Type is used only when no Office or Department/Program has
        // been assigned.
        $tiers[] = array('general');
    }

    $selected = array();
    $selectedPaths = array();

    foreach ($tiers as $tier) {
        $matches = array();

        foreach ($candidates as $candidate) {
            $candidatePath = $candidate['path'];

            if (isset($selectedPaths[$candidatePath])) {
                continue;
            }

            if (related_events_matches_tier($currentMetadata, $candidate['metadata'], $tier)) {
                $matches[] = $candidate;
            }
        }

        usort($matches, 'related_events_sort_by_expiration');

        foreach ($matches as $match) {
            $selected[] = $match;
            $selectedPaths[$match['path']] = true;

            if (sizeof($selected) >= $limit) {
                break 2;
            }
        }
    }

    if (sizeof($selected) === 0) {
        related_events_debug_message(
            'Related Events: ' . sizeof($candidates) .
            ' active candidates were found, but none shared configured metadata with ' .
            $currentPath . '. Current metadata groups: ' .
            implode(', ', array_keys($currentMetadata))
        );
        return '';
    }

    $html = '<section class="related-events">';
    $html .= '<h2>Related Events</h2>';

    foreach ($selected as $event) {
        $html .= related_events_render_event_html($event['event']);
    }

    $html .= '</section>';

    related_events_debug_message(
        'Related Events: rendered ' . sizeof($selected) . ' event(s) for ' . $currentPath . '.'
    );

    return $html;
}

/**
 * Return the unique metadata values currently assigned to Event pages.
 *
 * This is intentionally a read-only diagnostic helper. A Cascade template
 * can call it temporarily to inspect the live metadata names and options
 * without maintaining a hardcoded list in this module.
 *
 * @return array
 */
function get_related_event_metadata_options()
{
    $xml = related_events_load_xml();
    $options = array();

    if (!$xml) {
        return $options;
    }

    $eventPages = $xml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
    if (!is_array($eventPages)) {
        return $options;
    }

    foreach ($eventPages as $eventXml) {
        foreach ($eventXml->{'dynamic-metadata'} as $metadata) {
            $name = trim((string)$metadata->name);
            if ($name === '') {
                continue;
            }

            if (!isset($options[$name])) {
                $options[$name] = array();
            }

            foreach ($metadata->value as $value) {
                $displayValue = trim((string)$value);
                $normalizedValue = related_events_normalize_value($displayValue);

                if ($normalizedValue === '' || in_array($normalizedValue, array('none', 'select'), true)) {
                    continue;
                }

                $options[$name][$normalizedValue] = $displayValue;
            }
        }
    }

    foreach ($options as $name => $values) {
        natcasesort($values);
        $options[$name] = array_values($values);
    }

    ksort($options, SORT_NATURAL | SORT_FLAG_CASE);

    return $options;
}

/**
 * Load the event XML using the same cache helper used by the existing event
 * modules.
 *
 * @return SimpleXMLElement|false
 */
function related_events_load_xml()
{
    if (!function_exists('autoCache')) {
        return false;
    }

    return autoCache(
        'simplexml_load_file',
        array($_SERVER["DOCUMENT_ROOT"] . "/_shared-content/xml/events.xml")
    );
}

/**
 * Resolve the current event from a supplied XML object, an inspected event
 * array, or the current request path.
 *
 * @param SimpleXMLElement|array|null $currentEvent
 * @return SimpleXMLElement|false
 */
function related_events_resolve_current_event($currentEvent)
{
    if (is_object($currentEvent)) {
        return $currentEvent;
    }

    $requestPath = '';

    if (is_array($currentEvent) && isset($currentEvent['xml']) && is_object($currentEvent['xml'])) {
        return $currentEvent['xml'];
    }

    if (is_array($currentEvent) && isset($currentEvent['path'])) {
        $requestPath = (string)$currentEvent['path'];
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $requestPath = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    $requestPath = related_events_normalize_path($requestPath);
    if ($requestPath === '') {
        return false;
    }

    $xml = related_events_load_xml();
    if (!$xml) {
        return false;
    }

    $eventPages = $xml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
    if (!is_array($eventPages)) {
        return false;
    }

    foreach ($eventPages as $eventXml) {
        if (related_events_get_path($eventXml) === $requestPath) {
            return $eventXml;
        }
    }

    // Test pages are commonly published beneath /_testing/events-tests while
    // their source event remains at its production path in events.xml. When
    // the exact path cannot match, resolve a unique event by its filename.
    if (strpos($requestPath, '/_testing/') === 0) {
        $requestName = basename($requestPath);
        $filenameMatches = array();

        foreach ($eventPages as $eventXml) {
            $eventPath = related_events_get_path($eventXml);
            if ($eventPath !== '' && basename($eventPath) === $requestName) {
                $filenameMatches[] = $eventXml;
            }
        }

        if (sizeof($filenameMatches) === 1) {
            return $filenameMatches[0];
        }
    }

    return false;
}

/**
 * Build one candidate per event page, using its earliest active/upcoming
 * occurrence for expiration and display.
 *
 * @param string $currentPath
 * @return array
 */
function related_events_get_candidates($currentPath)
{
    $xml = related_events_load_xml();
    $candidates = array();

    if (!$xml) {
        return $candidates;
    }

    $eventPages = $xml->xpath("//system-page[system-data-structure[@definition-path='Event']]");
    if (!is_array($eventPages)) {
        return $candidates;
    }

    foreach ($eventPages as $eventXml) {
        $path = related_events_get_path($eventXml);

        if ($path === '' || $path === $currentPath || strpos($path, '_testing') !== false) {
            continue;
        }

        // Match the existing event-helper behavior and do not create links
        // for event pages that are not present on disk.
        if (!file_exists($_SERVER["DOCUMENT_ROOT"] . '/' . $path . '.php')) {
            continue;
        }

        // inspect_event_page() contains the existing event-page extraction
        // and location/link/image behavior. It also has optional featured-
        // event side effects, so keep those disabled for this module.
        $event = related_events_inspect_event_page($eventXml);
        if (!is_array($event) || !isset($event['dates'])) {
            continue;
        }

        $occurrence = related_events_get_earliest_occurrence($event);
        if (!$occurrence) {
            continue;
        }

        $candidateEvent = $event;
        $candidateEvent['date'] = $occurrence['date'];
        $candidateEvent['start-date'] = $occurrence['start-date'];
        $candidateEvent['end-date'] = $occurrence['end-date'];
        $candidateEvent['date-for-sorting'] = $occurrence['start-date'];

        $candidates[] = array(
            'path' => $path,
            'metadata' => related_events_get_metadata($eventXml),
            'expiration' => $occurrence['end-date'],
            'event' => $candidateEvent
        );
    }

    return $candidates;
}

/**
 * Extract the event fields needed by the existing event-card renderer.
 *
 * @param SimpleXMLElement $eventXml
 * @return array|string
 */
function related_events_inspect_event_page($eventXml)
{
    $pageInfo = array(
        'title' => $eventXml->title,
        'display-name' => $eventXml->{'display-name'},
        'published' => $eventXml->{'last-published-on'},
        'description' => $eventXml->{'description'},
        'path' => $eventXml->path,
        'date' => '',
        'date-for-sorting' => '',
        'dates' => array(),
        'md' => array(),
        'html' => '',
        'display-on-feed' => false,
        'external-link' => '',
        'image' => '',
        'xml' => $eventXml
    );

    if (strpos($pageInfo['path'], '_testing') !== false) {
        return '';
    }

    $dataStructure = $eventXml->{'system-data-structure'};
    if ((string)$dataStructure['definition-path'] !== 'Event') {
        return '';
    }

    $pageInfo['external-link'] = $dataStructure->{'link'};
    $pageInfo['dates'] = $dataStructure->{'event-dates'};

    $location = '';
    $locationType = (string)$dataStructure->location;

    if ($locationType === 'On campus' || $locationType === 'On Campus') {
        $location = $dataStructure->{'on-campus-location'};
    } else {
        $location = $dataStructure->{'off-campus-location'};
    }

    $otherLocation = $dataStructure->{'other-on-campus'};
    if ($otherLocation[0]) {
        $location = $otherLocation;
    }

    if ((string)$location === 'none') {
        $location = '';
    }

    $pageInfo['location'] = $location;
    $pageInfo['image'] = $dataStructure->{'image'}->path;

    return $pageInfo;
}

/**
 * Render an event with the existing event-card template when the event feed
 * has already been loaded. If events_helper.php prevented that include, use
 * the same template with local, prefixed filter functions instead.
 *
 * @param array $event
 * @return string
 */
function related_events_render_event_html($event)
{
    if (function_exists('get_event_html')) {
        return get_event_html($event);
    }

    if (!function_exists('makeTwigEnviron') || !class_exists('Twig_SimpleFilter')) {
        return '';
    }

    $twig = makeTwigEnviron('/code/events/twig');
    $twig->addFilter(new Twig_SimpleFilter(
        'convert_path_to_link',
        'related_events_convert_path_to_link'
    ));
    $twig->addFilter(new Twig_SimpleFilter(
        'format_fancy_event_date',
        'related_events_format_fancy_event_date'
    ));
    $twig->addFilter(new Twig_SimpleFilter(
        'get_month_shorthand_name',
        'related_events_get_month_shorthand_name'
    ));
    $twig->addFilter(new Twig_SimpleFilter(
        'get_timezone_shorthand',
        'related_events_get_timezone_shorthand'
    ));

    return $twig->render('get_event_html.html', array(
        'title' => $event['title'],
        'event' => $event,
        'start' => $event['date']['start-date'],
        'end' => $event['date']['end-date']
    ));
}

/**
 * Preserve the existing event-feed rule for art galleries and theatre.
 *
 * @param array $event
 * @return bool
 */
function related_events_is_art_or_theatre($event)
{
    if (!isset($event['xml'])) {
        return false;
    }

    foreach ($event['xml']->{'dynamic-metadata'} as $metadata) {
        if ((string)$metadata->name !== 'general') {
            continue;
        }

        foreach ($metadata->value as $value) {
            $value = related_events_normalize_value((string)$value);

            if (in_array($value, array(
                'art galleries',
                'johnson gallery',
                'olson gallery',
                'theatre'
            ), true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Return the correct event link.
 *
 * @param array $event
 * @return string
 */
function related_events_convert_path_to_link($event)
{
    if (!empty($event['external-link'])) {
        return (string)$event['external-link'];
    }

    return 'https://www.bethel.edu' . $event['path'];
}

/**
 * Return a formatted event time.
 *
 * @param array $date
 * @return string
 */
function related_events_format_fancy_event_date($date)
{
    $startDate = $date['start-date'];
    $allDay = $date['all-day'];

    if ($startDate === '') {
        return '';
    }

    $formattedDate = date('g:i a', $startDate);

    if ($allDay === 'Yes') {
        return '';
    }

    if ($formattedDate === '12:00 pm') {
        return 'Noon';
    }

    $formattedDate = str_replace('am', 'a.m.', $formattedDate);
    $formattedDate = str_replace('pm', 'p.m.', $formattedDate);

    return str_replace(':00', '', $formattedDate);
}

/**
 * Return the existing month shorthand used by event cards.
 *
 * @param string $month
 * @return string
 */
function related_events_get_month_shorthand_name($month)
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

/**
 * Return the existing timezone shorthand used by event cards.
 *
 * @param array $date
 * @return string
 */
function related_events_get_timezone_shorthand($date)
{
    if ($date['outside-of-minnesota'] !== 'Yes') {
        return '';
    }

    $timeZones = array(
        'Hawaii-Aleutian Time' => 'HT',
        'Alaska Time' => 'AT',
        'Pacific Time' => 'PT',
        'Mountain Time' => 'MT',
        'Central Time' => 'CT',
        'Eastern Time' => 'ET'
    );

    return isset($timeZones[$date['time-zone']]) ? $timeZones[$date['time-zone']] : '';
}

/**
 * Select the active/upcoming occurrence that expires soonest.
 *
 * @param array $event
 * @return array|false
 */
function related_events_get_earliest_occurrence($event)
{
    $now = time();
    $occurrences = array();
    $isArtOrTheatre = related_events_is_art_or_theatre($event);

    foreach ($event['dates'] as $date) {
        $start = related_events_date_timestamp($date, 'start-date');
        $end = related_events_date_timestamp($date, 'end-date');

        if ($start === false || $end === false || $end < $now) {
            continue;
        }

        // Preserve the existing event-feed rule for art and theatre events.
        if ($isArtOrTheatre && $now > ($start + (3 * 24 * 60 * 60))) {
            continue;
        }

        $occurrences[] = array(
            'start-date' => $start,
            'end-date' => $end,
            'date' => array(
                'start-date' => $start,
                'end-date' => $end,
                'all-day' => related_events_date_value($date, 'all-day'),
                'outside-of-minnesota' => related_events_date_value($date, 'outside-of-minnesota'),
                'time-zone' => related_events_date_value($date, 'time-zone')
            )
        );
    }

    if (sizeof($occurrences) === 0) {
        return false;
    }

    usort($occurrences, 'related_events_sort_occurrences');

    return $occurrences[0];
}

/**
 * Extract metadata values grouped by metadata name.
 *
 * @param SimpleXMLElement $xml
 * @return array
 */
function related_events_get_metadata($xml)
{
    $metadata = array();

    foreach ($xml->{'dynamic-metadata'} as $metadataNode) {
        $name = trim((string)$metadataNode->name);
        if ($name === '') {
            continue;
        }

        if (!isset($metadata[$name])) {
            $metadata[$name] = array();
        }

        foreach ($metadataNode->value as $value) {
            $normalizedValue = related_events_normalize_value((string)$value);

            if ($normalizedValue === '' || in_array($normalizedValue, array('none', 'select'), true)) {
                continue;
            }

            $metadata[$name][$normalizedValue] = true;
        }
    }

    return $metadata;
}

/**
 * Get normalized values for one metadata name.
 *
 * @param array $metadata
 * @param string $name
 * @return array
 */
function related_events_get_metadata_values($metadata, $name)
{
    if (!isset($metadata[$name]) || !is_array($metadata[$name])) {
        return array();
    }

    return array_keys($metadata[$name]);
}

/**
 * Determine whether the current event and candidate share a value within
 * one of the exact metadata names represented by the supplied tier.
 *
 * @param array $currentMetadata
 * @param array $candidateMetadata
 * @param array $metadataNames
 * @return bool
 */
function related_events_matches_tier($currentMetadata, $candidateMetadata, $metadataNames)
{
    foreach ($metadataNames as $name) {
        $currentValues = related_events_get_metadata_values($currentMetadata, $name);
        $candidateValues = related_events_get_metadata_values($candidateMetadata, $name);

        if (sizeof($currentValues) === 0 || sizeof($candidateValues) === 0) {
            continue;
        }

        if (sizeof(array_intersect($currentValues, $candidateValues)) > 0) {
            return true;
        }
    }

    return false;
}

/**
 * Normalize metadata values for comparison while retaining the original
 * values for diagnostic output.
 *
 * @param string $value
 * @return string
 */
function related_events_normalize_value($value)
{
    $value = html_entity_decode(trim((string)$value), ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);

    return strtolower(trim($value));
}

/**
 * Return a page path as a string.
 *
 * @param SimpleXMLElement $xml
 * @return string
 */
function related_events_get_path($xml)
{
    return related_events_normalize_path((string)$xml->path);
}

/**
 * Normalize paths for current-page matching without changing the event URL
 * stored in the event data used for rendering.
 *
 * @param string $path
 * @return string
 */
function related_events_normalize_path($path)
{
    $path = trim((string)$path);

    if ($path === '' || $path === '/') {
        return $path;
    }

    $path = rtrim($path, '/');
    $path = preg_replace('/\.(?:php|html?|xml)$/i', '', $path);

    return $path;
}

/**
 * Read an event date field as a Unix timestamp. Cascade stores these values
 * in milliseconds.
 *
 * @param SimpleXMLElement $date
 * @param string $name
 * @return float|false
 */
function related_events_date_timestamp($date, $name)
{
    $value = related_events_date_value($date, $name);

    if ($value === '') {
        return false;
    }

    return ((float)$value) / 1000;
}

/**
 * Read a date value while supporting both the nested Cascade value shape and
 * the direct value shape used by related event/calendar data.
 *
 * @param SimpleXMLElement $date
 * @param string $name
 * @return string
 */
function related_events_date_value($date, $name)
{
    if (isset($date->{$name}->value)) {
        return (string)$date->{$name}->value;
    }

    return trim((string)$date->{$name});
}

/**
 * Sort candidates by soonest expiration, then by start date, then path for a
 * stable result when dates are equal.
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function related_events_sort_by_expiration($a, $b)
{
    if ($a['expiration'] != $b['expiration']) {
        return ($a['expiration'] < $b['expiration']) ? -1 : 1;
    }

    $aStart = $a['event']['start-date'];
    $bStart = $b['event']['start-date'];

    if ($aStart != $bStart) {
        return ($aStart < $bStart) ? -1 : 1;
    }

    return strcmp($a['path'], $b['path']);
}

/**
 * Sort occurrences by soonest expiration, then by start date.
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function related_events_sort_occurrences($a, $b)
{
    if ($a['end-date'] != $b['end-date']) {
        return ($a['end-date'] < $b['end-date']) ? -1 : 1;
    }

    if ($a['start-date'] == $b['start-date']) {
        return 0;
    }

    return ($a['start-date'] < $b['start-date']) ? -1 : 1;
}

?>
