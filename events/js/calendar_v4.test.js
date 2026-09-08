'use strict';

var test = require('node:test');
var assert = require('node:assert/strict');
var calendar = require('./calendar_v4.js');

test('normalizes category tokens', function () {
    assert.equal(calendar.normalizeCategory('  Other-General '), 'other-general');
    assert.equal(calendar.normalizeCategory(null), '');
});

test('positions the event hover inside the calendar and above the viewport edge', function () {
    assert.deepEqual(
        calendar.eventHoverPosition(
            { left: 700, right: 780, top: 100, bottom: 120 },
            { width: 200, height: 150 },
            { left: 100, right: 800, top: 50 },
            900
        ),
        { left: 400, top: 50 }
    );

    assert.deepEqual(
        calendar.eventHoverPosition(
            { left: 300, right: 380, top: 700, bottom: 720 },
            { width: 200, height: 250 },
            { left: 100, right: 800, top: 50 },
            800
        ),
        { left: 260, top: 420 }
    );
});

test('matches exact and aggregate academic filters', function () {
    assert.equal(
        calendar.categoryMatchesFilter('Athletics-general', 'Athletics-general'),
        true
    );
    assert.equal(
        calendar.categoryMatchesFilter('Physics and Engineering-undergraduate-departments', 'Undergraduate-academic-dates'),
        true
    );
    assert.equal(
        calendar.categoryMatchesFilter('Social Work-graduate-program', 'Graduate-academic-dates'),
        true
    );
    assert.equal(
        calendar.categoryMatchesFilter('Ministry-seminary-program', 'Seminary-academic-dates'),
        true
    );
    assert.equal(
        calendar.categoryMatchesFilter('Nursing-adult-undergrad-program', 'Adult Undergraduate-academic-dates'),
        true
    );
    assert.equal(
        calendar.categoryMatchesFilter('Athletics-general', 'Graduate-academic-dates'),
        false
    );
});

test('Other ignores a generic legacy token when a recognized Event Type exists', function () {
    var eventTypes = [
        'Athletics-general',
        'Music concerts-general',
        'Academic Dates - Undergraduate-general'
    ];

    assert.equal(
        calendar.eventMatchesOtherFilter(
            ['Athletics-general', 'other', 'Social Work-cas-departments'],
            eventTypes
        ),
        false
    );
    assert.equal(
        calendar.eventMatchesOtherFilter(
            ['Academic Dates - Undergraduate-general', 'other'],
            eventTypes
        ),
        false
    );
});

test('Other matches explicit v4 Other and untyped legacy events', function () {
    var eventTypes = ['Athletics-general', 'Music concerts-general'];

    assert.equal(
        calendar.eventMatchesOtherFilter(['Other-general', 'Athletics-general'], eventTypes),
        true
    );
    assert.equal(
        calendar.eventMatchesOtherFilter(['other', 'Social Work-cas-departments'], eventTypes),
        true
    );
    assert.equal(
        calendar.eventMatchesOtherFilter(['other'], []),
        false
    );
});

test('external filtering combines exact, aggregate, and Other behavior', function () {
    var eventTypes = ['Athletics-general', 'Music concerts-general'];

    assert.equal(
        calendar.eventMatchesExternalFilters(
            ['Athletics-general', 'other'],
            ['Other-general'],
            eventTypes
        ),
        false
    );
    assert.equal(
        calendar.eventMatchesExternalFilters(
            ['other', 'Social Work-cas-departments'],
            ['Other-general'],
            eventTypes
        ),
        true
    );
    assert.equal(
        calendar.eventMatchesExternalFilters(
            ['Social Work-graduate-program'],
            ['Graduate-academic-dates'],
            eventTypes
        ),
        true
    );
});

test('internal metadata requires an authenticated matching internal filter', function () {
    var categories = [
        'Physician Assistant (M.S.)-graduate-program',
        'Students-internal'
    ];
    var eventTypes = ['Athletics-general', 'Other-general'];

    assert.equal(
        calendar.eventIsVisible(
            categories,
            ['Graduate-academic-dates'],
            eventTypes,
            [],
            'mip79358'
        ),
        false
    );
    assert.equal(
        calendar.eventIsVisible(['Students-internal'], [], eventTypes, ['Students-internal'], null),
        false
    );
    assert.equal(
        calendar.eventIsVisible(
            ['Students-internal'],
            [],
            eventTypes,
            ['Students-internal'],
            'mip79358'
        ),
        true
    );
    assert.equal(
        calendar.eventIsVisible(
            ['Faculty/staff-internal'],
            [],
            eventTypes,
            ['Faculty/staff-internal'],
            null,
            'staff'
        ),
        true
    );
    assert.equal(
        calendar.eventIsVisible(
            ['Faculty/staff-internal'],
            [],
            eventTypes,
            ['Faculty/staff-internal'],
            'mip79358',
            'guest'
        ),
        false
    );
});

test('parses query and hash calendar state with hash taking precedence', function () {
    var now = new Date(2026, 7, 7);

    assert.deepEqual(
        calendar.parseCalendarState('https://example.edu/events/calendar/?month=9&year=2027&day=3', now),
        { month: 9, year: 2027, day: 3, mode: null }
    );
    assert.deepEqual(
        calendar.parseCalendarState(
            'https://example.edu/events/calendar/?month=9&year=2027#month=10&year=2028&mode=list',
            now
        ),
        { month: 10, year: 2028, day: null, mode: 'list' }
    );
    assert.deepEqual(
        calendar.parseCalendarState('https://example.edu/events/calendar/?month=99&year=nope', now),
        { month: 8, year: 2026, day: null, mode: null }
    );
});

test('builds compatible endpoint and hash URLs', function () {
    var state = { month: 10, year: 2028, day: 12, mode: 'list' };

    assert.equal(
        calendar.endpointUrl(state),
        '/code/events/php/calendar_rest_v4?month=10&year=2028'
    );
    assert.equal(
        calendar.calendarHash(state),
        '#month=10&year=2028&day=12&mode=list'
    );
});

test('restores saved choices while selecting newly added Cascade options', function () {
    var current = ['Athletics-general', 'Other-general', 'New-general'];
    var saved = {
        version: 1,
        known: ['Athletics-general', 'Other-general'],
        selected: ['Athletics-general']
    };

    assert.deepEqual(
        calendar.reconcileFilterSelection(current, saved),
        ['Athletics-general', 'New-general']
    );
    assert.deepEqual(
        calendar.reconcileFilterSelection(current, null),
        current
    );
});
