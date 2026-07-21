<?php

/**
 * Report the current event's assigned metadata while Related Events is tested.
 *
 * @param array|null $currentEvent Metadata context supplied by the page template.
 * @return string
 */
function create_related_events($currentEvent = null)
{
    if (
        !is_array($currentEvent) ||
        !isset($currentEvent['metadata']) ||
        !is_array($currentEvent['metadata'])
    ) {
        return '';
    }

    $fieldNames = array(
        'general',
        'offices',
        'cas-departments',
        'adult-undergrad-program',
        'graduate-program',
        'seminary-program'
    );
    $metadata = array();

    foreach ($fieldNames as $fieldName) {
        $metadata[$fieldName] = related_events_metadata_values(
            $currentEvent['metadata'],
            $fieldName
        );
    }

    return '<pre class="related-events-debug">' .
        htmlspecialchars(json_encode($metadata, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') .
        '</pre>';
}

function related_events_metadata_values($metadata, $fieldName)
{
    if (!isset($metadata[$fieldName])) {
        return array();
    }

    $values = is_array($metadata[$fieldName])
        ? $metadata[$fieldName]
        : array($metadata[$fieldName]);
    $assigned = array();

    foreach ($values as $value) {
        if (is_array($value) || is_object($value)) {
            continue;
        }

        $value = trim((string)$value);
        if ($value === '' || in_array(strtolower($value), array('none', 'select'), true)) {
            continue;
        }

        if (!in_array($value, $assigned, true)) {
            $assigned[] = $value;
        }
    }

    return $assigned;
}
