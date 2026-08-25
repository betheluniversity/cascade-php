/**
 * Dependency-free public calendar client.
 *
 * Load this file with `defer`.
 * The calendar_rest_v4 endpoint and Cascade-rendered filter markup
 * remain the data and configuration sources.
 */
(function (globalObject, factory) {
    'use strict';

    var api = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }

    if (globalObject && globalObject.document) {
        globalObject.BethelCalendarV4 = api;
        api.boot(globalObject.document, globalObject);
    }
})(typeof window !== 'undefined' ? window : null, function () {
    'use strict';

    var CONFIG = Object.freeze({
        endpoint: '/code/events/php/calendar_rest_v4',
        filterStorageKey: 'bethel.calendar.filters.v4',
        filterStorageVersion: 1,
        viewStorageKey: 'bethel.calendar.view.v4',
        desktopMediaQuery: '(min-width: 800px)'
    });

    var ACADEMIC_FILTER_RULES = Object.freeze({
        'adult undergraduate-academic-dates': Object.freeze({
            suffixes: Object.freeze(['-adult-undergrad-program']),
            exact: Object.freeze([
                'college of adult & professional studies-academic-dates',
                'college of adult and professional studies-academic-dates'
            ])
        }),
        'graduate-academic-dates': Object.freeze({
            suffixes: Object.freeze(['-graduate-program']),
            exact: Object.freeze(['graduate school-academic-dates'])
        }),
        'seminary-academic-dates': Object.freeze({
            suffixes: Object.freeze(['-seminary-program']),
            exact: Object.freeze(['seminary st. paul-academic-dates'])
        }),
        'undergraduate-academic-dates': Object.freeze({
            suffixes: Object.freeze(['-undergraduate-departments', '-cas-departments']),
            exact: Object.freeze([
                'college of arts & sciences-academic-dates',
                'college of arts and sciences-academic-dates'
            ])
        })
    });

    function normalizeCategory(value) {
        return String(value == null ? '' : value).trim().toLowerCase();
    }

    function eventHoverPosition(headingRect, hoverRect, boundaryRect, viewportHeight) {
        var boundaryWidth = boundaryRect.right - boundaryRect.left;
        var left = headingRect.right - boundaryRect.left - 20;
        var top = headingRect.top - boundaryRect.top;

        if (left + hoverRect.width > boundaryWidth) {
            left = headingRect.left - boundaryRect.left - hoverRect.width;
        }
        if (left < 0) {
            left = 0;
        }

        if (viewportHeight
            && headingRect.top + hoverRect.height > viewportHeight
            && headingRect.bottom - hoverRect.height >= boundaryRect.top) {
            top = headingRect.bottom - boundaryRect.top - hoverRect.height;
        }

        return {
            left: left,
            top: Math.max(0, top)
        };
    }

    function isOtherCategory(value) {
        var normalized = normalizeCategory(value);
        return normalized === 'other' || normalized === 'other-general';
    }

    function categoryMatchesFilter(category, filter) {
        var normalizedCategory = normalizeCategory(category);
        var normalizedFilter = normalizeCategory(filter);

        if (normalizedCategory === normalizedFilter) {
            return true;
        }

        var academicRule = ACADEMIC_FILTER_RULES[normalizedFilter];
        if (!academicRule) {
            return false;
        }

        if (academicRule.exact.indexOf(normalizedCategory) > -1) {
            return true;
        }

        return academicRule.suffixes.some(function (suffix) {
            return normalizedCategory.slice(-suffix.length) === suffix;
        });
    }

    function eventMatchesOtherFilter(categories, eventTypeFilters) {
        var normalizedCategories = categories.map(normalizeCategory);

        if (normalizedCategories.indexOf('other-general') > -1) {
            return true;
        }

        // Without the Event Type list, fail closed instead of allowing a
        // generic legacy "other" token to match every event again.
        if (!eventTypeFilters.length) {
            return false;
        }

        var normalizedEventTypes = eventTypeFilters.map(normalizeCategory);
        return !normalizedCategories.some(function (category) {
            return normalizedEventTypes.indexOf(category) > -1;
        });
    }

    function eventMatchesExternalFilters(categories, selectedFilters, eventTypeFilters) {
        var otherSelected = false;

        for (var filterIndex = 0; filterIndex < selectedFilters.length; filterIndex += 1) {
            var filter = selectedFilters[filterIndex];
            if (isOtherCategory(filter)) {
                otherSelected = true;
                continue;
            }

            for (var categoryIndex = 0; categoryIndex < categories.length; categoryIndex += 1) {
                if (categoryMatchesFilter(categories[categoryIndex], filter)) {
                    return true;
                }
            }
        }

        return otherSelected && eventMatchesOtherFilter(categories, eventTypeFilters);
    }

    function eventMatchesInternalFilters(categories, selectedFilters) {
        return selectedFilters.some(function (filter) {
            return categories.some(function (category) {
                return normalizeCategory(category) === normalizeCategory(filter);
            });
        });
    }

    function eventHasInternalCategory(categories) {
        return categories.some(function (category) {
            return /-internal$/i.test(normalizeCategory(category));
        });
    }

    function eventIsVisible(
        categories,
        selectedExternal,
        eventTypeFilters,
        selectedInternal,
        remoteUser
    ) {
        if (eventHasInternalCategory(categories)) {
            return Boolean(remoteUser)
                && eventMatchesInternalFilters(categories, selectedInternal);
        }

        return eventMatchesExternalFilters(categories, selectedExternal, eventTypeFilters);
    }

    function validInteger(value, minimum, maximum) {
        var parsed = Number.parseInt(value, 10);
        return Number.isInteger(parsed) && parsed >= minimum && parsed <= maximum
            ? parsed
            : null;
    }

    function parseCalendarState(locationLike, now) {
        var currentDate = now instanceof Date ? now : new Date();
        var locationHref = typeof locationLike === 'string'
            ? locationLike
            : locationLike && locationLike.href;
        var url = new URL(locationHref || 'https://calendar.invalid/');
        var hashQuery = url.hash.replace(/^#\??/, '');
        var params = hashQuery.indexOf('=') > -1
            ? new URLSearchParams(hashQuery)
            : url.searchParams;
        var month = validInteger(params.get('month'), 1, 12);
        var year = validInteger(params.get('year'), 1970, 9999);
        var day = validInteger(params.get('day'), 1, 31);
        var mode = normalizeCategory(params.get('mode')) === 'list' ? 'list' : null;

        return {
            month: month || currentDate.getMonth() + 1,
            year: year || currentDate.getFullYear(),
            day: day,
            mode: mode
        };
    }

    function calendarHash(calendarState) {
        var params = new URLSearchParams();
        params.set('month', String(calendarState.month));
        params.set('year', String(calendarState.year));
        if (calendarState.day) {
            params.set('day', String(calendarState.day));
        }
        if (calendarState.mode === 'list') {
            params.set('mode', 'list');
        }
        return '#' + params.toString();
    }

    function endpointUrl(calendarState) {
        var params = new URLSearchParams();
        params.set('month', String(calendarState.month));
        params.set('year', String(calendarState.year));
        return CONFIG.endpoint + '?' + params.toString();
    }

    function reconcileFilterSelection(currentValues, savedState) {
        if (!savedState || savedState.version !== CONFIG.filterStorageVersion) {
            return currentValues.slice();
        }

        var selected = new Set(Array.isArray(savedState.selected) ? savedState.selected : []);
        var known = new Set(Array.isArray(savedState.known) ? savedState.known : []);

        return currentValues.filter(function (value) {
            // Newly added Cascade options should start checked. Existing options
            // retain the user's saved choice.
            return selected.has(value) || !known.has(value);
        });
    }

    function safeReadJson(storage, key) {
        if (!storage) {
            return null;
        }
        try {
            var value = storage.getItem(key);
            return value ? JSON.parse(value) : null;
        } catch (error) {
            return null;
        }
    }

    function safeWriteJson(storage, key, value) {
        if (!storage) {
            return;
        }
        try {
            storage.setItem(key, JSON.stringify(value));
        } catch (error) {
            // Filtering still works when storage is unavailable.
        }
    }

    function safeReadText(storage, key) {
        if (!storage) {
            return null;
        }
        try {
            return storage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function safeWriteText(storage, key, value) {
        if (!storage) {
            return;
        }
        try {
            storage.setItem(key, value);
        } catch (error) {
            // View switching still works when storage is unavailable.
        }
    }

    function boot(documentObject, windowObject) {
        function initialize() {
            var calendarMode = documentObject.querySelector('#calendar-mode');
            var calendarMain = documentObject.querySelector('#calendar-main');

            if (!calendarMode || !calendarMain || calendarMode.dataset.calendarV4Initialized === 'true') {
                return null;
            }

            calendarMode.dataset.calendarV4Initialized = 'true';

            var elements = {
                calendarMode: calendarMode,
                calendarMain: calendarMain,
                monthTitle: documentObject.querySelector('.calendar-title__month'),
                previousMonth: documentObject.querySelector('a.previous-month'),
                nextMonth: documentObject.querySelector('a.next-month'),
                today: documentObject.querySelector('.calendar-toolbar .today'),
                gridButton: documentObject.querySelector('.view-mode--grid > a'),
                listButton: documentObject.querySelector('.view-mode--list > a'),
                filterDropdown: documentObject.querySelector('.filter-dropdown'),
                filterForm: documentObject.querySelector('.filter-content'),
                filterClose: documentObject.querySelector('#filter-close'),
                filterActions: documentObject.querySelector('.filter-content .filter-actions'),
                welcome: documentObject.querySelector('.bu-topbar-welcome')
            };

            var toolbarLinks = Array.from(documentObject.querySelectorAll('.calendar-toolbar > a'));
            elements.filterToggle = documentObject.querySelector('[data-calendar-filter-toggle]')
                || toolbarLinks.find(function (link) {
                    return !link.classList.contains('today');
                });

            var mediaQuery = windowObject.matchMedia(CONFIG.desktopMediaQuery);
            var storage = null;
            try {
                storage = windowObject.localStorage;
            } catch (error) {
                storage = null;
            }

            var initialCalendarState = parseCalendarState(windowObject.location, new Date());
            var savedView = safeReadText(storage, CONFIG.viewStorageKey);
            var hasViewControls = Boolean(elements.gridButton && elements.listButton);
            var state = {
                abortController: null,
                effectiveView: 'grid',
                preferredView: hasViewControls
                    ? initialCalendarState.mode || (savedView === 'list' ? 'list' : 'grid')
                    : 'grid',
                internalAccess: 'guest',
                remoteUser: null,
                requestId: 0,
                scrollAfterLoad: initialCalendarState.day !== null && initialCalendarState.mode === 'list'
            };
            var eventHover = documentObject.createElement('div');
            var activeEventHeading = null;

            eventHover.id = 'event-hover';
            eventHover.hidden = true;
            eventHover.style.display = 'none';
            eventHover.style.fontSize = '0.75rem';
            eventHover.style.lineHeight = '1.4';
            eventHover.style.zIndex = '1000';
            eventHover.setAttribute('role', 'dialog');
            eventHover.setAttribute('aria-label', 'Event details');

            if (!calendarMain.style.position) {
                calendarMain.style.position = 'relative';
            }

            function hideEventHover() {
                if (activeEventHeading) {
                    var activeLink = activeEventHeading.querySelector('a');
                    if (activeLink) {
                        activeLink.removeAttribute('aria-expanded');
                    }
                }

                eventHover.hidden = true;
                eventHover.style.display = 'none';
                eventHover.style.visibility = '';
                activeEventHeading = null;
            }

            function eventHeadingFromTarget(target) {
                if (!target || target.nodeType !== 1 || typeof target.closest !== 'function') {
                    return null;
                }

                var heading = target.closest('.vevent > dt');
                return heading && calendarMain.contains(heading) ? heading : null;
            }

            function showEventHover(heading) {
                if (state.effectiveView !== 'grid' || !heading || !calendarMain.contains(heading)) {
                    return false;
                }

                var eventElement = heading.closest('.vevent');
                var details = heading.nextElementSibling;
                if (!eventElement || eventElement.hidden || !details || details.tagName !== 'DD') {
                    return false;
                }

                if (activeEventHeading && activeEventHeading !== heading) {
                    hideEventHover();
                }

                eventHover.replaceChildren();

                var titleLink = heading.querySelector('a[href]');
                if (titleLink) {
                    titleLink.setAttribute('aria-expanded', 'true');
                }

                Array.from(details.children).forEach(function (child) {
                    eventHover.appendChild(child.cloneNode(true));
                });

                var eventUrl = eventElement.getAttribute('data-event-url');
                if (eventUrl) {
                    var action = documentObject.createElement('p');
                    var viewLink = documentObject.createElement('a');
                    action.className = 'event-hover__action';
                    action.style.margin = '0.75rem 0 0';
                    action.style.fontSize = 'inherit';
                    action.style.lineHeight = 'inherit';
                    viewLink.href = eventUrl;
                    viewLink.style.fontSize = 'inherit';
                    viewLink.style.lineHeight = 'inherit';
                    viewLink.target = '_blank';
                    viewLink.rel = 'noopener';
                    viewLink.textContent = 'View event';
                    action.appendChild(viewLink);
                    eventHover.appendChild(action);
                }

                calendarMain.appendChild(eventHover);
                eventHover.hidden = false;
                eventHover.style.display = 'block';
                eventHover.style.visibility = 'hidden';
                eventHover.style.left = '0px';
                eventHover.style.top = '0px';

                var position = eventHoverPosition(
                    heading.getBoundingClientRect(),
                    eventHover.getBoundingClientRect(),
                    calendarMain.getBoundingClientRect(),
                    windowObject.innerHeight
                );
                eventHover.style.left = position.left + 'px';
                eventHover.style.top = position.top + 'px';
                eventHover.style.visibility = '';
                activeEventHeading = heading;
                return true;
            }

            function filterInputs() {
                return elements.filterForm
                    ? Array.from(elements.filterForm.querySelectorAll('input.subject[name="subjects"]'))
                    : [];
            }

            function externalFilterInputs() {
                return filterInputs().filter(function (input) {
                    return input.classList.contains('subject-external');
                });
            }

            function internalFilterInputs() {
                return filterInputs().filter(function (input) {
                    return input.classList.contains('subject-internal');
                });
            }

            function selectedValues(inputs) {
                return inputs.filter(function (input) {
                    return input.checked;
                }).map(function (input) {
                    return input.value;
                });
            }

            function eventTypeFilters() {
                return externalFilterInputs().map(function (input) {
                    return input.value;
                }).filter(function (value) {
                    var normalized = normalizeCategory(value);
                    return normalized.slice(-8) === '-general' && !isOtherCategory(normalized);
                });
            }

            function restoreFilters() {
                var inputs = filterInputs();
                var currentValues = inputs.map(function (input) {
                    return input.value;
                });
                var savedState = safeReadJson(storage, CONFIG.filterStorageKey);
                var restoredSelection = new Set(reconcileFilterSelection(currentValues, savedState));

                inputs.forEach(function (input) {
                    input.checked = restoredSelection.has(input.value);
                });
            }

            function saveFilters() {
                var inputs = filterInputs();
                safeWriteJson(storage, CONFIG.filterStorageKey, {
                    version: CONFIG.filterStorageVersion,
                    known: inputs.map(function (input) {
                        return input.value;
                    }),
                    selected: selectedValues(inputs)
                });
            }

            function eventCategories(eventElement) {
                return Array.from(eventElement.querySelectorAll('.categories [data-category]')).map(function (category) {
                    return category.dataset.category || category.textContent || '';
                });
            }

            function applyFilters() {
                var selectedExternal = selectedValues(externalFilterInputs());
                var selectedInternal = selectedValues(internalFilterInputs());
                var availableEventTypes = eventTypeFilters();
                var visibleCount = 0;

                calendarMain.querySelectorAll('.vevent').forEach(function (eventElement) {
                    var categories = eventCategories(eventElement);
                    var visible = eventIsVisible(
                        categories,
                        selectedExternal,
                        availableEventTypes,
                        selectedInternal,
                        state.remoteUser
                    );

                    // The legacy calendar stylesheet sets list events to
                    // `display: flex`, which overrides the browser's default
                    // `[hidden] { display: none }` rule. Keep the semantic
                    // attribute and explicitly set display so filtering works
                    // in both grid and list views.
                    eventElement.hidden = !visible;
                    eventElement.style.display = visible ? '' : 'none';
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                if (activeEventHeading) {
                    var activeEvent = activeEventHeading.closest('.vevent');
                    if (!activeEvent || activeEvent.hidden) {
                        hideEventHover();
                    }
                }

                calendarMode.dataset.visibleEvents = String(visibleCount);
                return visibleCount;
            }

            function setAllFilters(checked) {
                filterInputs().forEach(function (input) {
                    input.checked = checked;
                });
                saveFilters();
                applyFilters();
            }

            function setFilterOpen(open) {
                if (!elements.filterDropdown || !elements.filterToggle) {
                    return;
                }

                elements.filterDropdown.hidden = !open;
                elements.filterDropdown.style.display = open ? 'block' : 'none';
                elements.filterToggle.classList.toggle('active', open);
                elements.filterToggle.setAttribute('aria-expanded', String(open));
            }

            function filterIsOpen() {
                return Boolean(elements.filterDropdown && !elements.filterDropdown.hidden);
            }

            function setEffectiveView(view) {
                var effectiveView = view === 'list' ? 'list' : 'grid';
                state.effectiveView = effectiveView;
                calendarMode.classList.toggle('calendar-grid', effectiveView === 'grid');
                calendarMode.classList.toggle('calendar-list', effectiveView === 'list');

                if (effectiveView !== 'grid') {
                    hideEventHover();
                }

                if (elements.gridButton) {
                    elements.gridButton.classList.toggle('active', effectiveView === 'grid');
                    elements.gridButton.setAttribute('aria-pressed', String(effectiveView === 'grid'));
                }
                if (elements.listButton) {
                    elements.listButton.classList.toggle('active', effectiveView === 'list');
                    elements.listButton.setAttribute('aria-pressed', String(effectiveView === 'list'));
                }
            }

            function applyResponsiveView() {
                setEffectiveView(mediaQuery.matches ? state.preferredView : 'list');
            }

            function chooseView(view) {
                state.preferredView = view === 'list' ? 'list' : 'grid';
                safeWriteText(storage, CONFIG.viewStorageKey, state.preferredView);
                applyResponsiveView();
            }

            function updateWelcomeBar(remoteUser) {
                if (!elements.welcome) {
                    return;
                }

                var user = remoteUser ? String(remoteUser) : 'guest';
                var link = documentObject.createElement('a');
                link.href = remoteUser
                    ? '/code/general-cascade/logout'
                    : '/code/general-cascade/login';
                link.textContent = remoteUser ? 'Logout' : 'Login';
                elements.welcome.replaceChildren(
                    documentObject.createTextNode('Welcome ' + user + ': '),
                    link
                );
            }

            function updateInternalFilterVisibility() {
                documentObject.querySelectorAll('.filter-list-internal').forEach(function (container) {
                    container.hidden = state.internalAccess === 'guest';
                });

                internalFilterInputs().forEach(function (input) {
                    var isStaffFilter = normalizeCategory(input.value) === 'staff-internal';
                    var hideForStudent = state.internalAccess === 'student' && isStaffFilter;
                    input.closest('li').hidden = hideForStudent;
                    if (hideForStudent) {
                        input.checked = false;
                    }
                });
            }

            function updateMonthLink(link, queryString, label) {
                if (!link) {
                    return;
                }
                if (!queryString) {
                    link.hidden = true;
                    link.setAttribute('href', '#');
                    return;
                }
                link.hidden = false;
                link.setAttribute('href', '#' + String(queryString).replace(/^\??/, ''));
                link.setAttribute('aria-label', label);
            }

            function highlightCurrentDay(calendarState) {
                calendarMain.querySelectorAll('.event.is-current-day').forEach(function (dayElement) {
                    dayElement.classList.remove('is-current-day');
                });

                var today = new Date();
                if (calendarState.month !== today.getMonth() + 1
                    || calendarState.year !== today.getFullYear()) {
                    return null;
                }

                var currentSpan = Array.from(calendarMain.querySelectorAll('.event > span[name]')).find(function (span) {
                    return Number.parseInt(span.getAttribute('name'), 10) === today.getDate();
                });
                var currentDay = currentSpan ? currentSpan.closest('.event') : null;
                if (currentDay) {
                    currentDay.classList.add('is-current-day');
                    currentDay.setAttribute('aria-current', 'date');
                }
                return currentDay;
            }

            function showLoadError() {
                hideEventHover();
                var errorMessage = documentObject.createElement('p');
                errorMessage.className = 'calendar-error';
                errorMessage.setAttribute('role', 'alert');
                errorMessage.appendChild(
                    documentObject.createTextNode('The calendar could not be loaded. ')
                );
                var loginLink = documentObject.createElement('a');
                loginLink.href = '/code/general-cascade/login';
                loginLink.textContent = 'Login and try again.';
                errorMessage.appendChild(loginLink);
                calendarMain.replaceChildren(errorMessage);
            }

            function renderCalendar(data, calendarState) {
                if (!data || typeof data.grid !== 'string') {
                    throw new Error('The calendar endpoint returned an invalid response.');
                }

                if (elements.monthTitle) {
                    elements.monthTitle.textContent = data.month_title || '';
                    elements.monthTitle.style.display = 'block';
                }

                updateMonthLink(elements.previousMonth, data.previous_month_qs, 'Previous month');
                updateMonthLink(elements.nextMonth, data.next_month_qs, 'Next month');
                hideEventHover();
                calendarMain.innerHTML = data.grid;

                state.remoteUser = data.remote_user || null;
                state.internalAccess = data.internal_access || 'guest';
                updateWelcomeBar(state.remoteUser);
                updateInternalFilterVisibility();
                applyFilters();
                applyResponsiveView();

                var currentDay = highlightCurrentDay(calendarState);
                if (currentDay && state.scrollAfterLoad && state.effectiveView === 'list') {
                    windowObject.requestAnimationFrame(function () {
                        currentDay.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
                state.scrollAfterLoad = false;

                calendarMain.dispatchEvent(new windowObject.CustomEvent('calendar:updated', {
                    bubbles: true,
                    detail: { data: data, state: calendarState }
                }));
            }

            function loadCalendar() {
                var calendarState = parseCalendarState(windowObject.location, new Date());
                var requestId = state.requestId + 1;
                state.requestId = requestId;
                hideEventHover();

                if (state.abortController) {
                    state.abortController.abort();
                }
                state.abortController = typeof windowObject.AbortController === 'function'
                    ? new windowObject.AbortController()
                    : null;

                calendarMain.setAttribute('aria-busy', 'true');

                var requestOptions = {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                };
                if (state.abortController) {
                    requestOptions.signal = state.abortController.signal;
                }

                return windowObject.fetch(endpointUrl(calendarState), requestOptions)
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Calendar request failed with status ' + response.status + '.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (requestId === state.requestId) {
                            renderCalendar(data, calendarState);
                        }
                    })
                    .catch(function (error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }
                        if (requestId === state.requestId) {
                            showLoadError();
                        }
                        if (windowObject.console && typeof windowObject.console.error === 'function') {
                            windowObject.console.error(error);
                        }
                    })
                    .finally(function () {
                        if (requestId === state.requestId) {
                            calendarMain.removeAttribute('aria-busy');
                        }
                    });
            }

            function navigateTo(calendarState, scrollAfterLoad) {
                var hash = calendarHash(calendarState);
                state.scrollAfterLoad = Boolean(scrollAfterLoad);
                if (windowObject.location.hash === hash) {
                    loadCalendar();
                } else {
                    windowObject.location.hash = hash;
                }
            }

            restoreFilters();
            setFilterOpen(false);
            applyResponsiveView();
            // Staging protects calendar data behind authentication. Render the
            // guest login link before the first request so authentication does
            // not depend on a successful calendar response.
            updateWelcomeBar(null);

            if (elements.monthTitle) {
                elements.monthTitle.setAttribute('aria-live', 'polite');
            }
            calendarMain.setAttribute('aria-live', 'polite');

            if (elements.filterToggle && elements.filterDropdown) {
                if (!elements.filterDropdown.id) {
                    elements.filterDropdown.id = 'calendar-filter-dropdown';
                }
                elements.filterToggle.setAttribute('aria-controls', elements.filterDropdown.id);
                elements.filterToggle.setAttribute('aria-expanded', 'false');
                elements.filterToggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    setFilterOpen(!filterIsOpen());
                });
            }

            if (elements.filterClose) {
                elements.filterClose.setAttribute('type', 'button');
                elements.filterClose.addEventListener('click', function (event) {
                    event.preventDefault();
                    setFilterOpen(false);
                    if (elements.filterToggle) {
                        elements.filterToggle.focus();
                    }
                });
            }

            if (elements.filterForm) {
                elements.filterForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                });
                elements.filterForm.addEventListener('change', function (event) {
                    if (event.target && event.target.matches('input.subject[name="subjects"]')) {
                        saveFilters();
                        applyFilters();
                    }
                });
            }

            if (elements.filterActions) {
                elements.filterActions.addEventListener('click', function (event) {
                    var action = event.target.closest('a[name]');
                    if (!action) {
                        return;
                    }
                    event.preventDefault();
                    if (action.getAttribute('name') === 'all') {
                        setAllFilters(true);
                    } else if (action.getAttribute('name') === 'none') {
                        setAllFilters(false);
                    }
                });
            }

            calendarMain.addEventListener('mouseover', function (event) {
                var heading = eventHeadingFromTarget(event.target);
                if (!heading || (event.relatedTarget && heading.contains(event.relatedTarget))) {
                    return;
                }
                showEventHover(heading);
            });

            calendarMain.addEventListener('mouseout', function (event) {
                if (!activeEventHeading) {
                    return;
                }

                var relatedTarget = event.relatedTarget;
                var leftHeading = activeEventHeading.contains(event.target);
                var leftHover = eventHover.contains(event.target);
                if (!leftHeading && !leftHover) {
                    return;
                }
                if (relatedTarget
                    && (activeEventHeading.contains(relatedTarget) || eventHover.contains(relatedTarget))) {
                    return;
                }
                hideEventHover();
            });

            calendarMain.addEventListener('focusin', function (event) {
                var heading = eventHeadingFromTarget(event.target);
                if (heading) {
                    showEventHover(heading);
                }
            });

            calendarMain.addEventListener('focusout', function (event) {
                if (!activeEventHeading) {
                    return;
                }

                var relatedTarget = event.relatedTarget;
                if (relatedTarget
                    && (activeEventHeading.contains(relatedTarget) || eventHover.contains(relatedTarget))) {
                    return;
                }
                hideEventHover();
            });

            calendarMain.addEventListener('click', function (event) {
                if (eventHover.contains(event.target)) {
                    return;
                }

                var heading = eventHeadingFromTarget(event.target);
                if (!heading || state.effectiveView !== 'grid') {
                    hideEventHover();
                    return;
                }

                showEventHover(heading);
            });

            documentObject.addEventListener('click', function (event) {
                if (activeEventHeading
                    && !activeEventHeading.contains(event.target)
                    && !eventHover.contains(event.target)) {
                    hideEventHover();
                }

                if (!filterIsOpen() || !elements.filterDropdown || !elements.filterToggle) {
                    return;
                }
                if (!elements.filterDropdown.contains(event.target)
                    && !elements.filterToggle.contains(event.target)) {
                    setFilterOpen(false);
                }
            });

            documentObject.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideEventHover();
                    if (filterIsOpen()) {
                        setFilterOpen(false);
                        if (elements.filterToggle) {
                            elements.filterToggle.focus();
                        }
                    }
                }
            });

            if (elements.gridButton) {
                elements.gridButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    chooseView('grid');
                });
            }
            if (elements.listButton) {
                elements.listButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    chooseView('list');
                });
            }

            if (elements.today) {
                elements.today.addEventListener('click', function (event) {
                    event.preventDefault();
                    var today = new Date();
                    navigateTo({
                        month: today.getMonth() + 1,
                        year: today.getFullYear(),
                        day: today.getDate(),
                        mode: state.effectiveView === 'list' ? 'list' : null
                    }, state.effectiveView === 'list');
                });
            }

            windowObject.addEventListener('hashchange', loadCalendar);
            windowObject.addEventListener('popstate', loadCalendar);
            windowObject.addEventListener('storage', function (event) {
                if (event.key === CONFIG.filterStorageKey) {
                    restoreFilters();
                    applyFilters();
                }
                if (event.key === CONFIG.viewStorageKey) {
                    if (hasViewControls) {
                        var updatedView = safeReadText(storage, CONFIG.viewStorageKey);
                        state.preferredView = updatedView === 'list' ? 'list' : 'grid';
                        applyResponsiveView();
                    }
                }
            });

            var mediaQueryListener = function () {
                applyResponsiveView();
            };
            if (typeof mediaQuery.addEventListener === 'function') {
                mediaQuery.addEventListener('change', mediaQueryListener);
            } else if (typeof mediaQuery.addListener === 'function') {
                mediaQuery.addListener(mediaQueryListener);
            }

            loadCalendar();

            return {
                applyFilters: applyFilters,
                loadCalendar: loadCalendar,
                state: state
            };
        }

        if (documentObject.readyState === 'loading') {
            documentObject.addEventListener('DOMContentLoaded', initialize, { once: true });
            return null;
        }
        return initialize();
    }

    return Object.freeze({
        boot: boot,
        calendarHash: calendarHash,
        categoryMatchesFilter: categoryMatchesFilter,
        endpointUrl: endpointUrl,
        eventHoverPosition: eventHoverPosition,
        eventMatchesExternalFilters: eventMatchesExternalFilters,
        eventMatchesOtherFilter: eventMatchesOtherFilter,
        eventIsVisible: eventIsVisible,
        normalizeCategory: normalizeCategory,
        parseCalendarState: parseCalendarState,
        reconcileFilterSelection: reconcileFilterSelection
    });
});
