<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/code/vendor/autoload.php';

/** Decode the Cascade payload; a missing individual selection is an empty feed. */
function create_faculty_feed_from_context($payload) {
    parse_str(rawurldecode($payload), $context);
    $selectedBios = null;
    if (isset($context['mode']) && $context['mode'] === 'individual') {
        $selectedBios = isset($context['bios']) ? $context['bios'] : array();
        foreach ($selectedBios as &$bio) {
            if (!isset($bio['array_of_job_titles'])) {
                $bio['array_of_job_titles'] = array();
            }
        }
        unset($bio);
    }
    return create_faculty_feed(
        isset($context['metadata']) ? $context['metadata'] : array(),
        isset($context['excludeAdjunct']) && $context['excludeAdjunct'] === 'true',
        $selectedBios,
        isset($context['number']) ? $context['number'] : null
    );
}

/**
 * Render one faculty Swiper. Null selections use metadata; an array of selections
 * uses the supplied bios in order, without metadata filtering, exclusion or limits.
 * A positive metadata maximum excludes emeritus/emerita roles and default-avatar
 * images before random selection. Without a maximum, include those entries and use
 * the existing leadership/status and last-name sorting. Adjunct exclusion applies
 * in both modes when requested.
 */
function create_faculty_feed($metadata, $excludeAdjunct = false, $selectedBios = null, $maxEntries = null) {
    if ($selectedBios !== null) {
        $bios = $selectedBios;
    } else {
        $maxEntries = filter_var($maxEntries, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        $bios = filter_faculty_feed_bios(faculty_feed_load_bios(), $metadata, $excludeAdjunct, $maxEntries !== false);
        if ($maxEntries !== false) {
            shuffle($bios);
            $bios = array_slice($bios, 0, $maxEntries);
        } elseif (count($bios) > 1) {
            $bios = faculty_feed_sort_bios($bios);
        }
    }

    if (!$bios) {
        return '';
    }
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(array(
        $_SERVER['DOCUMENT_ROOT'] . '/code/faculty-bios/twig',
        $_SERVER['DOCUMENT_ROOT'] . '/code/general-cascade/twig'
    )));
    $slides = '';
    foreach ($bios as $bio) {
        $bio['path'] = '/' . ltrim($bio['path'], '/');
        $bio['image-path'] = '/' . ltrim($bio['image-path'], '/');
        $slides .= faculty_feed_render_card($bio, $twig);
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
        if (($schools && !$filters[$field]) || preg_grep('/nurs/i', $filters[$field])) {
            return true;
        }
    }
    return false;
}

/** Missing images use the default avatar when rendered, as do explicit avatar paths. */
function faculty_feed_uses_default_avatar($bio) {
    $imagePath = isset($bio['image-path']) ? trim($bio['image-path']) : '';
    if (trim($imagePath, '/') === '') {
        return true;
    }
    $imagePath = parse_url($imagePath, PHP_URL_PATH);
    return is_string($imagePath)
        && strcasecmp(basename(rawurldecode($imagePath)), 'default-avatar.svg') === 0;
}

/** Limit-specific exclusions precede title aggregation, deduplication and sampling. */
function filter_faculty_feed_bios($bios, $metadata, $excludeAdjunct = false, $limitedSelection = false) {
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
        if ($limitedSelection && faculty_feed_uses_default_avatar($bio)) {
            continue;
        }
        $titles = array();
        $bio['is_lead'] = false;
        $bio['top_lead'] = false;
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
            if ($limitedSelection && in_array(strtolower(trim($job['emeritus'])), array('emeritus', 'emerita'), true)) {
                continue;
            }
            if (!$nursingMatch && !faculty_feed_job_matches($job, $filters)) {
                continue;
            }
            $flags = faculty_feed_job_flags($job, $bio['id']);
            $bio['is_lead'] = $bio['is_lead'] || $flags['is_lead'];
            $bio['top_lead'] = $bio['top_lead'] || $flags['top_lead'];
            $bio['adjunct'] = $bio['adjunct'] || $job['adjunct'] === 'Yes';
            if ($job['fulltime'] === 'No') {
                $bio['fulltime'] = false;
            }
            if ($job['emeritus'] !== '' && $job['emeritus'] !== 'Neither') {
                $bio['emeritus'] = $job['emeritus'];
            }
            $titles[] = $job['job_title'];
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
            foreach (array('is_lead', 'top_lead', 'adjunct') as $flag) {
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

/** Read only card fields and job data; listing details and highlights are not used. */
function faculty_feed_load_bios() {
    $xml = simplexml_load_file($_SERVER['DOCUMENT_ROOT'] . '/_shared-content/xml/faculty-bios-internal.xml');
    $bios = array();
    foreach ($xml->children() as $page) {
        if ($page->getName() !== 'system-page') {
            continue;
        }
        $data = $page->{'system-data-structure'};
        $path = strval($page->path);
        if (strval($data['definition-path']) !== 'Faculty Bio'
            || strval($data->deactivate) === 'Yes'
            || strpos($path, '_testing') !== false
            || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path . '.php')) {
            continue;
        }
        $jobs = array();
        $job = array();
        foreach ($data->{'job-titles'} as $node) {
            $job = array();
            foreach (array('school', 'department', 'adult-undergrad-program', 'graduate-program',
                'seminary-program', 'department-chair', 'dean-of-nursing', 'program-director',
                'lead-faculty', 'job_title', 'adjunct', 'fulltime', 'emeritus') as $field) {
                $job[$field] = strval($node->{$field});
            }
            $jobs[] = $job;
        }
        // Legacy titles take their school/program selection from page metadata.
        if (!$jobs || $jobs[0]['school'] === '') {
            $metadata = array_fill_keys(array('school', 'department', 'adult-undergrad-program',
                'graduate-program', 'seminary-program'), array());
            foreach ($page->{'dynamic-metadata'} as $field) {
                $name = strval($field->name);
                if (array_key_exists($name, $metadata)) {
                    $metadata[$name] = array();
                    foreach ($field->value as $value) {
                        $metadata[$name][] = strval($value);
                    }
                    $metadata[$name] = faculty_feed_values($metadata[$name]);
                }
            }
            foreach ($data->{'job-title'} as $title) {
                $job = array_merge($job, $metadata, array('job_title' => strval($title),
                    'department-chair' => '', 'program-director' => '', 'lead-faculty' => ''));
                $jobs[] = $job;
            }
        }
        $bios[] = array('id' => strval($page['id']), 'path' => $path,
            'first' => strval($data->first), 'last' => strval($data->last),
            'image-path' => strval($data->image->path), 'job-titles' => $jobs);
    }
    return $bios;
}

/** Keep the existing leadership priorities, including the nursing exception. */
function faculty_feed_job_flags($job, $id) {
    $nursingLead = $id === 'aab255628c5865131315e7c4685d543b';
    $dean = $job['dean-of-nursing'] === 'Yes';
    return array(
        'top_lead' => $dean || $nursingLead,
        'is_lead' => !$dean && ($nursingLead || $job['department-chair'] === 'Yes'
            || $job['program-director'] === 'Yes'
            || in_array($job['lead-faculty'], array('Program Director', 'Lead Faculty'), true)
            || $job['job_title'] === 'Director of Online Programs')
    );
}

/** Unlimited feeds retain the original leadership/status and last-name ordering. */
function faculty_feed_sort_bios($bios) {
    $topLead = array_column($bios, 'top_lead');
    $lead = array_column($bios, 'is_lead');
    $adjunct = array_column($bios, 'adjunct');
    $fulltime = array_column($bios, 'fulltime');
    $last = array_column($bios, 'last');
    $emeritus = array();
    foreach ($bios as $bio) {
        $emeritus[] = $bio['emeritus'] !== 'Neither' ? 'Emeritus' : 'Neither';
    }
    array_multisort($topLead, SORT_DESC, $lead, SORT_DESC, $emeritus, SORT_DESC,
        $adjunct, SORT_ASC, $fulltime, SORT_DESC, $last, SORT_ASC, $bios);
    return $bios;
}

function faculty_feed_format_titles($titles) {
    $titles = array_values(array_unique($titles));
    return join(' and ', array_filter(array_merge(
        array(join(', ', array_slice($titles, 0, -1))), array_slice($titles, -1)
    ), 'strlen'));
}

function faculty_feed_render_card($bio, $twig) {
    $image = $bio['image-path'] !== '/'
        ? $twig->render('srcset.html', array('end_path' => $bio['image-path'], 'lazy' => true,
            'classes' => '', 'alt_text' => $bio['first'] . ' ' . $bio['last']))
        : "<img src='https://bethel-university.imgix.net/cdn/images/default-avatar.svg' alt='A default silhouette for faculty without images.' />";
    return $twig->render('faculty-bio-swiper.html', array('bio' => $bio,
        'bio_image' => $image, 'job_titles' => faculty_feed_format_titles($bio['array_of_job_titles'])));
}
