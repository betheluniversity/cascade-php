<?php
/**
 * Shared v4 event data and v1/v2 compatibility layer.
 *
 * Set this one flag to false after every legacy Event and Event v2 page has
 * been migrated. Legacy pages and legacy calendar aliases will then be
 * excluded automatically.
 */
if (!defined('EVENT_V4_LEGACY_COMPATIBILITY')) {
    define('EVENT_V4_LEGACY_COMPATIBILITY', true);
}

function event_v4_legacy_compatibility()
{
    return EVENT_V4_LEGACY_COMPATIBILITY === true;
}

function event_v4_metadata_fields()
{
    static $fields = array(
        'general',
        'offices',
        'undergraduate-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program',
        'internal'
    );
    return $fields;
}

/**
 * Old field/value => one or more canonical v4 field/value pairs.
 * Values omitted from this table intentionally remain unchanged.
 */
function event_v4_translation_rules()
{
    static $rules = null;
    if ($rules !== null) {
        return $rules;
    }
    $rules = array(
        'academic-dates' => array(
            'College of Adult & Professional Studies' => array(array('general', 'Academic Dates - Adult Undergraduate')),
            'College of Adult and Professional Studies' => array(array('general', 'Academic Dates - Adult Undergraduate')),
            'College of Arts & Sciences' => array(array('general', 'Academic Dates - Undergraduate')),
            'College of Arts and Sciences' => array(array('general', 'Academic Dates - Undergraduate')),
            'Graduate School' => array(array('general', 'Academic Dates - Graduate')),
            'Seminary St. Paul' => array(array('general', 'Academic Dates - Seminary'))
        ),
        'general' => array(
            'Meetings, Conferences, and Rentals' => array(
                array('general', 'Conferences'),
                array('general', 'Rental')
            ),
            'Music Concerts' => array(array('general', 'Music concerts'))
        ),
        'offices' => array(
            'Admissions - Seminary St. Paul' => array(array('offices', 'Admissions - Seminary')),
            'Christian Formation and Church Relations' => array(array('offices', 'Campus Ministries')),
            'Diversity, Equity, and Inclusion' => array(array('offices', 'Inclusive Excellence')),
            'Human Resources' => array(array('offices', 'People and Culture')),
            'Parents' => array(array('offices', 'Parent and Family Relations'))
        ),
        'cas-departments' => array(
            'Art & Design' => array(array('undergraduate-departments', 'Art and Design')),
            'Biblical & Theological Studies' => array(array('undergraduate-departments', 'Biblical and Theological Studies')),
            'Math & Computer Science' => array(array('undergraduate-departments', 'Math and Computer Science')),
            'Physics & Engineering' => array(array('undergraduate-departments', 'Physics and Engineering'))
        ),
        'adult-undergrad-program' => array(
            'Post-Baccalaureate Nursing (B.S)' => array(array('adult-undergrad-program', 'Post-Baccalaureate Nursing (B.S.N.)'))
        ),
        'graduate-program' => array(
            'Certificate in International Baccalaureate Teaching and Learning' => array(array('graduate-program', 'International Baccalaureate Education Certificate')),
            'Ed.D. in Higher Education Leadership' => array(array('graduate-program', 'Higher Education Leadership (Ed.D)')),
            'Ed.D. in K-12 Administration' => array(array('graduate-program', 'K-12 Administration (Ed.D)')),
            'Social Work (M.S.W.)' => array(array('graduate-program', 'Social Work (MSW) (M.A.)')),
            'Teacher Coordinator of Work-based Learning License' => array(array('graduate-program', 'Teacher Coordinator Work-based Learning License'))
        ),
        'seminary-program' => array(
            "Children's & Family Ministry (M.A.)" => array(array('seminary-program', "Children's, Youth, and Family Ministry (M.A.)")),
            'M.A. (Christian Thought)' => array(array('seminary-program', 'Christian Thought (M.A.)')),
            'Doctor of Ministry' => array(array('seminary-program', 'Doctor of Ministry (D.Min)')),
            'Marital & Family Therapy (M.A.)' => array(array('seminary-program', 'Marriage and Family Therapy (M.A.)')),
            'Marriage & Family Therapy (M.A.)' => array(array('seminary-program', 'Marriage and Family Therapy (M.A.)')),
            'M.A. (Theological Studies)' => array(array('seminary-program', 'Theological Studies (M.A.)'))
        )
    );
    return $rules;
}

function event_v4_canonical_field($field)
{
    if ($field === 'cas-departments') {
        return 'undergraduate-departments';
    }
    return $field;
}

function event_v4_translate_legacy_pair($field, $value)
{
    $rules = event_v4_translation_rules();
    if (isset($rules[$field])) {
        if (isset($rules[$field][$value])) {
            return $rules[$field][$value];
        }
        foreach ($rules[$field] as $oldValue => $pairs) {
            if (strcasecmp($oldValue, $value) === 0) {
                return $pairs;
            }
        }
    }

    return array(array(event_v4_canonical_field($field), $value));
}

function event_v4_translate_filter_value($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return array();
    }

    $translated = array();
    foreach (event_v4_translation_rules() as $fieldRules) {
        foreach ($fieldRules as $oldValue => $pairs) {
            if (strcasecmp($oldValue, $value) === 0) {
                foreach ($pairs as $pair) {
                    $translated[] = $pair[1];
                }
            }
        }
    }

    if (!$translated) {
        $translated[] = $value;
    }
    return event_v4_unique_strings($translated);
}

function event_v4_normalize_filter_values($categories)
{
    $flat = array();
    event_v4_flatten_values($categories, $flat);

    $normalized = array();
    foreach ($flat as $value) {
        $translations = event_v4_legacy_compatibility()
            ? event_v4_translate_filter_value($value)
            : array($value);
        foreach ($translations as $translated) {
            $normalized[strtolower($translated)] = true;
        }
    }
    return $normalized;
}

function event_v4_flatten_values($value, &$flat)
{
    if (is_array($value)) {
        foreach ($value as $child) {
            event_v4_flatten_values($child, $flat);
        }
        return;
    }
    if (is_scalar($value)) {
        $text = trim((string)$value);
        if ($text !== '') {
            $flat[] = $text;
        }
    }
}

function event_v4_get_events($sourceFile = '')
{
    if ($sourceFile === '') {
        $sourceFile = $_SERVER['DOCUMENT_ROOT'] . '/_shared-content/xml/events.xml';
    }

    $compatibility = event_v4_legacy_compatibility();
    $requestKey = $sourceFile . '|' . ($compatibility ? '1' : '0');
    static $requestCache = array();
    if (isset($requestCache[$requestKey])) {
        return $requestCache[$requestKey];
    }
    if (function_exists('autoCache')) {
        $requestCache[$requestKey] = autoCache(
            'event_v4_build_normalized_events_from_file',
            array($sourceFile, $compatibility)
        );
        return $requestCache[$requestKey];
    }

    $requestCache[$requestKey] = event_v4_build_normalized_events_from_file($sourceFile, $compatibility);
    return $requestCache[$requestKey];
}

function event_v4_build_normalized_events_from_file($sourceFile, $compatibility)
{
    if (!is_file($sourceFile)) {
        return array();
    }

    $xml = simplexml_load_file($sourceFile);
    if (!$xml) {
        return array();
    }
    return event_v4_build_normalized_events($xml, $compatibility);
}

function event_v4_get_calendar_category_values($sourceFile = '')
{
    if ($sourceFile === '') {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        if ($documentRoot === '') {
            return array();
        }
        $sourceFile = rtrim($documentRoot, '/') . '/_shared-content/xml/calendar-categories.xml';
    }

    static $requestCache = array();
    if (isset($requestCache[$sourceFile])) {
        return $requestCache[$sourceFile];
    }

    $requestCache[$sourceFile] = event_v4_build_calendar_category_values_from_file($sourceFile);
    return $requestCache[$sourceFile];
}

function event_v4_build_calendar_category_values_from_file($sourceFile)
{
    $categories = array();
    if (!is_file($sourceFile)) {
        return $categories;
    }

    $xml = simplexml_load_file($sourceFile);
    if (!$xml) {
        return $categories;
    }

    $metadataNodes = $xml->xpath('//system-page/dynamic-metadata');
    if (!is_array($metadataNodes)) {
        return $categories;
    }

    foreach ($metadataNodes as $metadata) {
        $field = trim((string)$metadata->name);
        if ($field === '') {
            continue;
        }
        if (!isset($categories[$field])) {
            $categories[$field] = array();
        }

        foreach ($metadata->value as $valueNode) {
            $value = trim((string)$valueNode);
            if (!event_v4_empty_metadata_value($value)) {
                $categories[$field][] = $value;
            }
        }
        $categories[$field] = event_v4_unique_strings($categories[$field]);
    }

    return $categories;
}

function event_v4_build_normalized_events($xml, $compatibility)
{
    $events = array();
    $pathIndexes = array();
    $calendarCategories = event_v4_get_calendar_category_values();
    $pages = $xml->xpath('//system-page');
    if (!is_array($pages)) {
        return $events;
    }

    foreach ($pages as $page) {
        $event = event_v4_normalize_page($page, $compatibility, $calendarCategories);
        if (is_array($event)) {
            $path = $event['path'];
            if (!isset($pathIndexes[$path])) {
                $pathIndexes[$path] = count($events);
                $events[] = $event;
                continue;
            }

            $index = $pathIndexes[$path];
            $existing = $events[$index];
            $preferCanonical = $existing['legacy'] && !$event['legacy']
                && ($event['published'] !== '' || $existing['published'] === '');
            $preferNewer = $existing['legacy'] === $event['legacy']
                && (float)$event['published'] > (float)$existing['published'];
            if ($preferCanonical || $preferNewer) {
                $events[$index] = $event;
            }
        }
    }
    return $events;
}

function event_v4_normalize_page($page, $compatibility, $calendarCategories = null)
{
    $data = $page->{'system-data-structure'};
    $definition = basename(trim((string)$data['definition-path']));
    if (!in_array($definition, array('Event', 'Event v2', 'Event v4'), true)) {
        return null;
    }

    $legacy = $definition !== 'Event v4';
    if ($legacy && !$compatibility) {
        return null;
    }

    $path = trim((string)$page->path);
    if ($path === '' || strpos($path, '_testing') !== false) {
        return null;
    }

    if ($calendarCategories === null) {
        $calendarCategories = event_v4_get_calendar_category_values();
    }
    $metadataResult = event_v4_normalize_metadata($page, $legacy, $compatibility, $calendarCategories);
    $isPublished = strtolower(trim((string)$page->{'is-published'}));
    $published = trim((string)$page->{'last-published-on'});
    if ($isPublished === 'false' || $isPublished === 'no') {
        $published = '';
    }

    $record = array(
        'definition' => $definition,
        'legacy' => $legacy,
        'title' => trim((string)$page->title),
        'published' => $published,
        'description' => $definition === 'Event v4'
            ? trim((string)$page->teaser)
            : trim((string)$page->description),
        'path' => $path,
        'external-link' => event_v4_external_link($data),
        'location' => event_v4_location($data, $definition),
        'dates' => event_v4_dates($data, $definition),
        'metadata' => $metadataResult['canonical'],
        'md' => $metadataResult['calendar'],
        'hide-from-calendar' => $metadataResult['hidden']
    );

    return $record;
}

function event_v4_normalize_metadata($page, $legacy, $compatibility, $calendarCategories = null)
{
    if ($calendarCategories === null) {
        $calendarCategories = event_v4_get_calendar_category_values();
    }

    $canonical = array();
    foreach (event_v4_metadata_fields() as $field) {
        $canonical[$field] = array();
    }
    $raw = array();
    $hidden = false;
    $allowedFields = array_merge(
        event_v4_metadata_fields(),
        array('academic-dates', 'cas-departments', 'hide-from-calendar')
    );

    foreach ($page->{'dynamic-metadata'} as $metadata) {
        $field = trim((string)$metadata->name);
        if ($field === '' || !in_array($field, $allowedFields, true)) {
            continue;
        }
        if (!isset($raw[$field])) {
            $raw[$field] = array();
        }

        foreach ($metadata->value as $valueNode) {
            $value = trim((string)$valueNode);
            if (event_v4_empty_metadata_value($value)) {
                continue;
            }
            $raw[$field][] = $value;

            if ($field === 'hide-from-calendar') {
                if (strcasecmp($value, 'Yes') === 0) {
                    $hidden = true;
                }
                continue;
            }

            $pairs = $legacy
                ? event_v4_translate_legacy_pair($field, $value)
                : array(array(event_v4_canonical_field($field), $value));

            foreach ($pairs as $pair) {
                if (isset($canonical[$pair[0]])) {
                    $canonical[$pair[0]][] = $pair[1];
                }
            }
        }
    }

    foreach ($canonical as $field => $values) {
        $canonical[$field] = event_v4_unique_strings($values);
    }
    foreach ($raw as $field => $values) {
        $raw[$field] = event_v4_unique_strings($values);
    }

    return array(
        'canonical' => $canonical,
        'raw' => $raw,
        'calendar' => event_v4_calendar_tokens($canonical, $raw, $compatibility, $calendarCategories),
        'hidden' => $hidden
    );
}

function event_v4_empty_metadata_value($value)
{
    return $value === '' || strcasecmp($value, 'None') === 0 || strcasecmp($value, 'Select') === 0;
}

function event_v4_calendar_category_is_available($categories, $field, $value)
{
    if (!isset($categories[$field])) {
        return false;
    }

    foreach ($categories[$field] as $categoryValue) {
        if (strcasecmp($categoryValue, $value) === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Preserve the legacy calendar's generic "other" token for metadata values
 * that are not represented by the public calendar's configured filters.
 */
function event_v4_calendar_tokens($canonical, $raw, $compatibility, $calendarCategories = null)
{
    $tokens = array();
    $hasUnmatchedCategory = false;
    $hasMetadata = false;
    foreach ($canonical as $field => $values) {
        foreach ($values as $value) {
            $tokens[] = $value . '-' . $field;
            if ($calendarCategories) {
                $hasMetadata = true;
                if (!event_v4_calendar_category_is_available($calendarCategories, $field, $value)) {
                    $hasUnmatchedCategory = true;
                }
            }
        }
    }

    if (!$compatibility) {
        if ($hasMetadata && $hasUnmatchedCategory) {
            $tokens[] = 'other';
        }
        return event_v4_unique_strings($tokens);
    }

    foreach ($raw as $field => $values) {
        if ($field === 'hide-from-calendar') {
            continue;
        }
        foreach ($values as $value) {
            $tokens[] = $value . '-' . $field;
            if ($calendarCategories) {
                $hasMetadata = true;
                if (!event_v4_calendar_category_is_available($calendarCategories, $field, $value)) {
                    $hasUnmatchedCategory = true;
                }
            }
        }
    }

    foreach ($canonical as $field => $values) {
        foreach ($values as $value) {
            if ($field === 'undergraduate-departments') {
                $tokens[] = $value . '-cas-departments';
            }
            foreach (event_v4_legacy_alias_pairs($field, $value) as $legacyPair) {
                $tokens[] = $legacyPair[1] . '-' . $legacyPair[0];
            }
        }
    }

    if ($hasMetadata && $hasUnmatchedCategory) {
        $tokens[] = 'other';
    }

    return event_v4_unique_strings($tokens);
}

function event_v4_legacy_alias_pairs($canonicalField, $canonicalValue)
{
    static $reverse = null;
    if ($reverse === null) {
        $reverse = array();
        foreach (event_v4_translation_rules() as $oldField => $fieldRules) {
            foreach ($fieldRules as $oldValue => $canonicalPairs) {
                foreach ($canonicalPairs as $pair) {
                    $key = $pair[0] . '|' . strtolower($pair[1]);
                    if (!isset($reverse[$key])) {
                        $reverse[$key] = array();
                    }
                    $reverse[$key][] = array($oldField, $oldValue);
                }
            }
        }
    }
    $key = $canonicalField . '|' . strtolower($canonicalValue);
    return isset($reverse[$key]) ? $reverse[$key] : array();
}

function event_v4_event_matches($event, $categories)
{
    $filters = event_v4_normalize_filter_values($categories);
    return event_v4_event_matches_filters($event, $filters);
}

function event_v4_event_matches_filters($event, $filters)
{
    if (!$filters) {
        return false;
    }

    foreach ($event['metadata'] as $values) {
        foreach ($values as $value) {
            if (isset($filters[strtolower($value)])) {
                return true;
            }
        }
    }
    return false;
}

function event_v4_dates($data, $definition)
{
    $dates = array();
    if ($definition === 'Event v4') {
        foreach ($data->date as $date) {
            $start = event_v4_milliseconds(event_v4_child_text($date, array('eventStart', 'start')));
            $end = event_v4_milliseconds(event_v4_child_text($date, array('eventEnd', 'end')));
            if ($start === '') {
                continue;
            }
            if ($end === '') {
                $end = $start;
            }
            $timeZone = event_v4_timezone(event_v4_child_text($date, array('timeZone', 'time-zone', 'timezone')));
            $allDay = event_v4_yes_no(event_v4_child_text($date, array('hideTime', 'all-day')));
            $dates[] = event_v4_date_record($start, $end, $allDay, $timeZone);
        }
        return $dates;
    }

    foreach ($data->{'event-dates'} as $date) {
        $start = event_v4_milliseconds(event_v4_child_text($date, array('start-date', 'start')));
        $end = event_v4_milliseconds(event_v4_child_text($date, array('end-date', 'end')));
        if ($start === '') {
            continue;
        }
        if ($end === '') {
            $end = $start;
        }
        $allDay = event_v4_yes_no(event_v4_child_text($date, array('all-day')));
        $timeZone = event_v4_timezone(event_v4_child_text($date, array('time-zone', 'timezone')));
        $outside = event_v4_yes_no(event_v4_child_text($date, array('outside-of-minnesota')));
        $dates[] = event_v4_date_record($start, $end, $allDay, $timeZone, $outside);
    }
    return $dates;
}

function event_v4_date_record($start, $end, $allDay, $timeZone, $outside = '')
{
    if ($outside === '') {
        $outside = ($timeZone !== '' && $timeZone !== 'Central Time') ? 'Yes' : 'No';
    }
    return array(
        'start-date' => (string)$start,
        'end-date' => (string)$end,
        'all-day' => $allDay,
        'outside-of-minnesota' => $outside,
        'time-zone' => $timeZone,
        'time-string' => event_v4_time_string($start, $end, $allDay, $outside, $timeZone)
    );
}

function event_v4_milliseconds($value)
{
    $value = trim((string)$value);
    if ($value === '' || !is_numeric($value)) {
        return '';
    }
    $number = (float)$value;
    if ($number < 100000000000) {
        $number *= 1000;
    }
    return sprintf('%.0f', $number);
}

function event_v4_timezone($value)
{
    $key = strtolower(preg_replace('/[^a-z]/i', '', trim((string)$value)));
    $zones = array(
        'hawaiialeutiantime' => 'Hawaii-Aleutian Time',
        'hawaiialeutian' => 'Hawaii-Aleutian Time',
        'alaskatime' => 'Alaska Time',
        'alaska' => 'Alaska Time',
        'pacifictime' => 'Pacific Time',
        'pacific' => 'Pacific Time',
        'mountaintime' => 'Mountain Time',
        'mountain' => 'Mountain Time',
        'centraltime' => 'Central Time',
        'central' => 'Central Time',
        'easterntime' => 'Eastern Time',
        'eastern' => 'Eastern Time'
    );
    return isset($zones[$key]) ? $zones[$key] : trim((string)$value);
}

function event_v4_yes_no($value)
{
    return event_v4_is_yes($value) ? 'Yes' : 'No';
}

function event_v4_is_yes($value)
{
    $value = strtolower(trim((string)$value));
    return $value === 'yes' || $value === 'true' || $value === '1';
}

function event_v4_child_text($node, $names)
{
    if (!is_object($node)) {
        return '';
    }
    foreach ($names as $name) {
        if (!isset($node->{$name})) {
            continue;
        }
        $child = $node->{$name};
        $text = trim((string)$child);
        if ($text !== '') {
            return $text;
        }
        if (isset($child->value)) {
            foreach ($child->value as $value) {
                $text = trim((string)$value);
                if ($text !== '') {
                    return $text;
                }
            }
        }
    }
    return '';
}

function event_v4_external_link($data)
{
    if (isset($data->link)) {
        return trim((string)$data->link);
    }
    return '';
}

function event_v4_location($data, $definition)
{
    if ($definition === 'Event v4') {
        $location = $data->location;
        $mode = event_v4_key((string)$location->locationSelect);
        if ($mode === 'oncampus') {
            $label = event_v4_child_text($location->onCampusLocation, array('location'));
            return $label !== '' ? $label : 'On Campus';
        }
        if ($mode === 'offcampus') {
            return event_v4_off_campus_location($location->offCampusLocation, false);
        }
        if ($mode === 'online') {
            return 'Online';
        }
        return '';
    }

    if ($definition === 'Event v2') {
        $mode = event_v4_key((string)$data->{'location-name'});
        if ($mode === 'oncampuslocation') {
            $other = trim((string)$data->{'other-on-campus'});
            return $other !== '' ? $other : trim((string)$data->{'on-campus-location'});
        }
        if ($mode === 'offcampuslocation') {
            return event_v4_off_campus_location($data->{'off-campus-location'}, true);
        }
        if ($mode === 'onlineurl') {
            return 'Online';
        }
    }

    $mode = event_v4_key((string)$data->location);
    if ($mode === 'oncampus') {
        $other = trim((string)$data->{'other-on-campus'});
        $location = $other !== '' ? $other : trim((string)$data->{'on-campus-location'});
    } else {
        $location = trim((string)$data->{'off-campus-location'});
    }
    return strcasecmp($location, 'none') === 0 ? '' : $location;
}

function event_v4_off_campus_location($node, $legacyV2)
{
    if (!is_object($node)) {
        return '';
    }
    $name = $legacyV2 ? 'off-campus-name' : 'name';
    $address = $legacyV2 ? 'off-campus-address' : 'address';
    $city = $legacyV2 ? 'off-campus-city' : 'city';
    $state = $legacyV2 ? 'off-campus-state' : 'state';
    $zip = $legacyV2 ? 'off-campus-zip' : 'zip';

    $label = trim((string)$node->{$name});
    if ($label !== '') {
        return $label;
    }
    $parts = array(
        trim((string)$node->{$address}),
        trim(trim((string)$node->{$city}) . ', ' . trim((string)$node->{$state}) . ' ' . trim((string)$node->{$zip}), ', ')
    );
    return implode(', ', array_values(array_filter($parts)));
}

function event_v4_key($value)
{
    return strtolower(preg_replace('/[^a-z0-9]/i', '', trim((string)$value)));
}

function event_v4_unique_strings($values)
{
    $unique = array();
    foreach ($values as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $unique[strtolower($value)] = $value;
        }
    }
    return array_values($unique);
}

function event_v4_time_string($start, $end, $allDay, $outside, $timeZone)
{
    if (event_v4_is_yes($allDay)) {
        return '';
    }
    $startText = event_v4_format_clock((int)$start / 1000);
    $endText = event_v4_format_clock((int)$end / 1000);
    $zone = event_v4_is_yes($outside) ? event_v4_timezone_abbreviation($timeZone) : '';
    $suffix = $zone !== '' ? ' (' . $zone . ')' : '';

    if ($startText === $endText) {
        return $startText === 'midnight' ? '' : $startText . $suffix;
    }
    return $startText . '-' . $endText . $suffix;
}

function event_v4_format_clock($timestamp)
{
    $formatted = date('g:i a', $timestamp);
    if ($formatted === '12:00 pm') {
        return 'noon';
    }
    if ($formatted === '12:00 am') {
        return 'midnight';
    }
    $formatted = str_replace(':00', '', $formatted);
    return str_replace(array('am', 'pm'), array('a.m.', 'p.m.'), $formatted);
}

function event_v4_timezone_abbreviation($timeZone)
{
    $zones = array(
        'Hawaii-Aleutian Time' => 'HT',
        'Alaska Time' => 'AT',
        'Pacific Time' => 'PT',
        'Mountain Time' => 'MT',
        'Central Time' => 'CT',
        'Eastern Time' => 'ET'
    );
    return isset($zones[$timeZone]) ? $zones[$timeZone] : '';
}

function event_v4_calendar_date_map($events, $rangeStart = null, $rangeEnd = null)
{
    $dates = array();
    $pathIndexes = array();

    foreach ($events as $event) {
        if ($event['hide-from-calendar'] || $event['published'] === '') {
            continue;
        }
        foreach ($event['dates'] as $date) {
            $startSeconds = (int)$date['start-date'] / 1000;
            $endSeconds = (int)$date['end-date'] / 1000;
            if (!$startSeconds) {
                continue;
            }
            if ($endSeconds < $startSeconds) {
                $endSeconds = $startSeconds;
            }

            if (($rangeStart !== null && $endSeconds < $rangeStart)
                || ($rangeEnd !== null && $startSeconds > $rangeEnd)) {
                continue;
            }

            $eventStartKey = date('Y-m-d', $startSeconds);
            $eventEndKey = date('Y-m-d', $endSeconds);
            $visibleStart = $rangeStart !== null ? max($startSeconds, $rangeStart) : $startSeconds;
            $visibleEnd = $rangeEnd !== null ? min($endSeconds, $rangeEnd) : $endSeconds;
            $startKey = date('Y-m-d', $visibleStart);
            $endKey = date('Y-m-d', $visibleEnd);
            $current = new DateTime($startKey);
            $last = new DateTime($endKey);

            while ($current <= $last) {
                $key = $current->format('Y-m-d');
                $page = event_v4_calendar_record($event, $date, $eventStartKey !== $eventEndKey);
                event_v4_add_calendar_record($dates, $pathIndexes, $key, $page);
                $current->modify('+1 day');
            }
        }
    }

    foreach ($dates as $key => $dayEvents) {
        usort($dayEvents, 'event_v4_sort_calendar_records');
        $dates[$key] = $dayEvents;
    }
    return $dates;
}

function event_v4_calendar_record($event, $date, $multiDay)
{
    return array(
        'title' => $event['title'],
        'published' => $event['published'],
        'description' => $event['description'],
        'path' => $event['path'],
        'externallink' => $event['external-link'],
        'location' => $event['location'],
        'md' => $event['md'],
        'specific_start' => $date['start-date'],
        'specific_end' => $date['end-date'],
        'specific_all_day' => $multiDay || event_v4_is_yes($date['all-day']),
        'time_string' => $multiDay ? '' : $date['time-string']
    );
}

function event_v4_add_calendar_record(&$dates, &$pathIndexes, $key, $page)
{
    if (!isset($dates[$key])) {
        $dates[$key] = array();
        $pathIndexes[$key] = array();
    }

    $path = $page['path'];
    if (!isset($pathIndexes[$key][$path])) {
        $pathIndexes[$key][$path] = count($dates[$key]);
        $dates[$key][] = $page;
        return;
    }

    $index = $pathIndexes[$key][$path];
    $existing = trim($dates[$key][$index]['time_string']);
    $additional = trim($page['time_string']);
    if ($additional !== '' && strpos($existing, $additional) === false) {
        $dates[$key][$index]['time_string'] = $existing === '' ? $additional : $existing . ', ' . $additional;
    }
}

function event_v4_sort_calendar_records($a, $b)
{
    $aStart = (float)$a['specific_start'];
    $bStart = (float)$b['specific_start'];
    if ($aStart == $bStart) {
        return strcasecmp($a['title'], $b['title']);
    }
    return $aStart < $bStart ? -1 : 1;
}

?>
