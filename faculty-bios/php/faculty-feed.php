<?php

require_once __DIR__ . '/faculty-bio-listing-v2.php';

/**
 * Render one faculty Swiper. Null selections use metadata; an array of selections
 * uses the supplied bios in order, without metadata filtering, exclusion or limits.
 * Metadata feeds use a random subset when a positive maximum is supplied; otherwise
 * they include every match in the existing leadership/status and last-name order.
 */
function create_faculty_feed($metadata, $excludeAdjunct = false, $selectedBios = null, $maxEntries = null) {
    if ($selectedBios !== null) {
        $bios = $selectedBios;
    } else {
        $bios = filter_faculty_feed_bios(get_faculty_bio_xml(), $metadata, $excludeAdjunct);
        $maxEntries = filter_var($maxEntries, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        if ($maxEntries !== false) {
            shuffle($bios);
            $bios = array_slice($bios, 0, $maxEntries);
        } elseif (count($bios) > 1) {
            $bios = sort_bios_by_lead_and_last_name($bios, true);
        }
    }

    $slides = '';
    foreach ($bios as $bio) {
        $bio['path'] = '/' . ltrim($bio['path'], '/');
        $bio['image-path'] = '/' . ltrim($bio['image-path'], '/');
        $slides .= create_bio_html($bio, true);
    }
    if (trim($slides) === '') {
        return '';
    }

    return '<div class="swiper swiper--cards">'
        . '<div class="swiper-wrapper">' . $slides . '</div>'
        . '<div class="swiper-navigation">'
        . '<div class="swiper-button-prev"></div>'
        . '<div class="swiper-button-next"></div>'
        . '</div></div>';
}

/** Remove empty metadata choices; None and Select are placeholders, not filters. */
function faculty_feed_values($values) {
    $result = array();
    foreach ((array) $values as $value) {
        $value = trim($value);
        if ($value !== '' && !in_array(strtolower($value), array('none', 'select'), true)) {
            $result[] = $value;
        }
    }
    return array_values(array_unique($result));
}

function faculty_feed_school_key($school) {
    return str_replace('&', 'and', $school);
}

/** Match school and program on the same job entry, never on unrelated roles. */
function faculty_feed_job_matches($job, $filters) {
    $programFields = array(
        'College of Arts and Sciences' => 'department',
        'College of Adult and Professional Studies' => 'adult-undergrad-program',
        'Graduate School' => 'graduate-program',
        'Bethel Seminary' => 'seminary-program'
    );
    if ($filters['school']) {
        $jobSchools = array_map('faculty_feed_school_key', faculty_feed_values($job['school']));
        foreach ($filters['school'] as $school) {
            $school = faculty_feed_school_key($school);
            if (!in_array($school, $jobSchools, true)) {
                continue;
            }
            if (!isset($programFields[$school])) {
                return true;
            }
            $field = $programFields[$school];
            if (!$filters[$field] || array_intersect($filters[$field], faculty_feed_values($job[$field]))) {
                return true;
            }
        }
        return false;
    }

    // Program-only feeds search across schools. Empty lists are not wildcards here.
    foreach (array_values($programFields) as $field) {
        if (array_intersect($filters[$field], faculty_feed_values($job[$field]))) {
            return true;
        }
    }
    return false;
}

/** Preserve the listing's existing inclusion exception for Diane Dahl in nursing. */
function faculty_feed_nursing_match($bio, $filters) {
    if ($bio['id'] !== 'aab255628c5865131315e7c4685d543b') {
        return false;
    }
    $nursingFields = array(
        'College of Arts and Sciences' => 'department',
        'College of Adult and Professional Studies' => 'adult-undergrad-program',
        'Graduate School' => 'graduate-program'
    );
    $schools = array_map('faculty_feed_school_key', $filters['school']);
    foreach ($nursingFields as $school => $field) {
        if ($schools && !in_array($school, $schools, true)) {
            continue;
        }
        if (($schools && !$filters[$field]) || check_substring_in_array('nurs', $filters[$field])) {
            return true;
        }
    }
    return false;
}

/** Filter roles first, then combine their titles and sort flags into one bio. */
function filter_faculty_feed_bios($bios, $metadata, $excludeAdjunct = false) {
    $filters = array();
    foreach (array('school', 'department', 'adult-undergrad-program', 'graduate-program', 'seminary-program') as $field) {
        $filters[$field] = faculty_feed_values(isset($metadata[$field]) ? $metadata[$field] : array());
    }
    if (!array_filter($filters)) {
        return array();
    }
    $excludeAdjunct = ($excludeAdjunct === true || $excludeAdjunct === 'yes');
    $result = array();
    foreach ($bios as $bio) {
        $titles = array();
        $bio['is_lead'] = false;
        $bio['top_lead'] = false;
        $bio['dean_of_nursing'] = false;
        $bio['adjunct'] = false;
        $bio['fulltime'] = true;
        $bio['emeritus'] = 'Neither';
        $nursingMatch = faculty_feed_nursing_match($bio, $filters);
        foreach ($bio['job-titles'] as $job) {
            // Legacy job entries may omit role flags or store metadata as arrays.
            $job = array_merge(array(
                'school' => '', 'department' => '', 'adult-undergrad-program' => '',
                'graduate-program' => '', 'seminary-program' => '', 'job_title' => '',
                'department-chair' => 'No', 'program-director' => 'No',
                'lead-faculty' => '', 'dean-of-nursing' => 'No',
                'adjunct' => 'No', 'fulltime' => 'Yes', 'emeritus' => 'Neither'
            ), $job);
            if ($excludeAdjunct && strtolower(trim($job['adjunct'])) === 'yes') {
                continue;
            }
            if (!$nursingMatch && !faculty_feed_job_matches($job, $filters)) {
                continue;
            }
            $title = get_job_title($job, $bio['id']);
            $bio['is_lead'] = $bio['is_lead'] || !empty($title['is_lead']);
            $bio['top_lead'] = $bio['top_lead'] || !empty($title['top_lead']);
            $bio['dean_of_nursing'] = $bio['dean_of_nursing'] || !empty($title['dean_of_nursing']);
            $bio['adjunct'] = $bio['adjunct'] || $job['adjunct'] === 'Yes';
            if ($job['fulltime'] === 'No') {
                $bio['fulltime'] = false;
            }
            if ($job['emeritus'] !== '' && $job['emeritus'] !== 'Neither') {
                $bio['emeritus'] = $job['emeritus'];
            }
            $titles[] = $title['title'];
        }
        if (!$titles) {
            continue;
        }
        $bio['array_of_job_titles'] = array_values(array_unique($titles));
        // XML normally contains one record per bio; also merge repeated records.
        $key = $bio['id'] !== '' ? $bio['id'] : $bio['path'];
        if (isset($result[$key])) {
            $existing = $result[$key];
            $bio['array_of_job_titles'] = array_values(array_unique(array_merge(
                $existing['array_of_job_titles'], $bio['array_of_job_titles']
            )));
            foreach (array('is_lead', 'top_lead', 'dean_of_nursing', 'adjunct') as $flag) {
                $bio[$flag] = $bio[$flag] || $existing[$flag];
            }
            $bio['fulltime'] = $bio['fulltime'] && $existing['fulltime'];
            if ($existing['emeritus'] !== 'Neither') {
                $bio['emeritus'] = $existing['emeritus'];
            }
        }
        $result[$key] = $bio;
    }
    return array_values($result);
}
