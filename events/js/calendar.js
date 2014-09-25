/* Modernizr 2.0.6 (Custom Build) | MIT & BSD
 * Build: http://www.modernizr.com/download/#-canvas-canvastext-mq-teststyles
 */
;window.Modernizr=function(a,b,c){function y(a,b){return!!~(""+a).indexOf(b)}function x(a,b){return typeof a===b}function w(a,b){return v(prefixes.join(a+";")+(b||""))}function v(a){j.cssText=a}var d="2.0.6",e={},f=b.documentElement,g=b.head||b.getElementsByTagName("head")[0],h="modernizr",i=b.createElement(h),j=i.style,k,l=Object.prototype.toString,m={},n={},o={},p=[],q=function(a,c,d,e){var g,i,j,k=b.createElement("div");if(parseInt(d,10))while(d--)j=b.createElement("div"),j.id=e?e[d]:h+(d+1),k.appendChild(j);g=["&shy;","<style>",a,"</style>"].join(""),k.id=h,k.innerHTML+=g,f.appendChild(k),i=c(k,a),k.parentNode.removeChild(k);return!!i},r=function(b){if(a.matchMedia)return matchMedia(b).matches;var c;q("@media "+b+" { #"+h+" { position: absolute; } }",function(b){c=(a.getComputedStyle?getComputedStyle(b,null):b.currentStyle).position=="absolute"});return c},s,t={}.hasOwnProperty,u;!x(t,c)&&!x(t.call,c)?u=function(a,b){return t.call(a,b)}:u=function(a,b){return b in a&&x(a.constructor.prototype[b],c)},m.canvas=function(){var a=b.createElement("canvas");return!!a.getContext&&!!a.getContext("2d")},m.canvastext=function(){return!!e.canvas&&!!x(b.createElement("canvas").getContext("2d").fillText,"function")};for(var z in m)u(m,z)&&(s=z.toLowerCase(),e[s]=m[z](),p.push((e[s]?"":"no-")+s));v(""),i=k=null,e._version=d,e.mq=r,e.testStyles=q;return e}(this,this.document);

(function($) {

    function SimpleDict() {
        this.count = function() {
            var count = 0;
            for (key in this) {
                if (key !== undefined && typeof(this[key]) != 'function')
                    count += 1;
            }
            return count;
        };
    };

    function queryToObject(query) {
        var params = query.split("&");
        var queryStringList = new SimpleDict();
        for(var i=0; i < params.length; i++)
        {
            var keyVal = params[i].split("=");
            if (keyVal[0] != '') {
                var key = keyVal[0];
                var value = keyVal[1];
                if (queryStringList[key] !== undefined) {
                    existing_value = queryStringList[key];
                    if (existing_value instanceof Array) {
                        existing_value.push(unescape(value));
                    } else {
                        queryStringList[key] = [existing_value, unescape(value)];
                    }
                } else {
                    queryStringList[key] = unescape(value);
                }
            }
        }
        return queryStringList;
    }

    function extractQueryParameters(url) {
        url.match(/\?(.+)$/);
        var query = RegExp.$1;
        return queryToObject(query);
    }

    function extractHashParameters(url) {
        url.match(/\#(.+)$/);
        var query = RegExp.$1;
        return queryToObject(query);
    }

    function replaceQueryToHash(link) {
        parts = link.attr('href').split('?');
        link.attr('href', '#' + parts[1]);
    }

    function replaceQueryString(link, qs) {
        link.attr('href',
            link.attr('href').replace(/\?.*$/, qs));
    }

    function getExternalCategories(){

        try{
            externalCategories = JSON.parse($.cookie('external-categories'));
        }catch (err){
            var externalCategories = createExternalList();
        }

        return externalCategories;
    }
    function getInternalCategories(){

        try{
            var internalCategories = JSON.parse($.cookie('internal-categories'));
        }catch (err){
            var internalCategories = createInternalList();
        }

        return internalCategories;
    }

    function createInternalList(){
        var internalCategories = [];
        $('.subject-internal').each(function(){
            if (this.checked){
                internalCategories.push(this.value);
            }
        });
        return internalCategories;
    }
    function createExternalList(){
        var externalCategories = [];
        $('.subject-external').each(function(){
            if (this.checked){
                externalCategories.push(this.value);
            }
        });
        return externalCategories;
    }

    function createCategoryCookie(username){
        var externalCategories = createExternalList();
        var internalCategories = createInternalList();
        $.cookie('external-categories', JSON.stringify(externalCategories), { expires: 365 });
        $.cookie('internal-categories', JSON.stringify(internalCategories), { expires: 365 });
    }

    function checkEventCategories(){

        var username = $.cookie('cal-user');

        // Get all categores that are checked
        var externalCategories = getExternalCategories();

        // Unceck any that shouldn't be checked (for example, on page reload)
        $(".subject-external").each(function(){
            if (externalCategories.indexOf(this.value) == -1){
                $(this).attr('checked', false);
            }else{
                $(this).attr('checked', true);
            }
        });
        if (username){
            var internalCategories = getInternalCategories();
            $(".subject-internal").each(function(){
                if (internalCategories.indexOf(this.value) == -1){
                    $(this).attr('checked', false);
                }else{
                    $(this).attr('checked', true);
                }
            });
        }

        $(".vevent").each(function(){
            var categories = $(this).find('.categories').children();
            // Hide by default unless we find a good category
            var username = $.cookie('cal-user');
            var hide = true;
            for (var index = 0; index < categories.length; ++index) {
                var category = $(categories[index]).data()['category'];
                if (internalCategories.indexOf(category) > -1 && username != "null"){
                    hide = false;
                }
                if (externalCategories.indexOf(category) > -1){
                    hide = false;
                }
            }
            if (hide){
                $(this).hide();
            }else{
                //just in case it is currently hidden
                $(this).show();
            }
        });
    }


    function objectToQuery(object) {
        var qs = [];
        for (key in object) {
            var value = object[key];
            if ('function' == typeof(value) || undefined === value) {
                continue;
            }
            if (value instanceof Array) {
                for(var i=0; i < value.length; i++) {
                    qs.push(key + '=' + escape(value[i]));
                }
            } else {
                qs.push(key + '=' + escape(value));
            }
        }
        return qs.join("&");
    }

    function CalendarController(element) {
        this.element = $(element);
        this.next_month_link = $('a.next-month', this.element);
        this.previous_month_link = $('a.previous-month', this.element);
        this.buttons = $('a.button', this.element);
        this.month_grid = $('div#calendar-main', this.element);
        this.title = $('div#calendar-title h3', this.element);
    }

    CalendarController.prototype.update = function(data) {
        var loc = window.location.toString().replace(/#.*/, '');
        this.title.text(data['month_title']);

        if (data['remote_user']){
            $("#bu-topbar-welcome").html("Welcome " + data['remote_user']);
        }else{
            var append = document.URL.replace("#", "?");
            var url = "https://auth.bethel.edu/cas/login?service=" + append;
            //url = url.replace("https", "http");
            $("#bu-topbar-welcome").html('Welcome guest: <a href="' + url + '">Login</a>');
        }

        if (data['next_month_qs'] !== null) {
            this.next_month_link.attr('href', loc + "#" + data['next_month_qs']);
            this.next_month_link.html(data['next_title'] + ' &raquo;');
            this.next_month_link.show()
        } else {
            this.next_month_link.hide();
            this.next_month_link.attr('href', '#');
        }
        if (data['previous_month_qs'] !== null) {
            this.previous_month_link.attr('href',
                loc + "#" + data['previous_month_qs']);
            this.previous_month_link.html('&laquo; ' + data['previous_title']);
            this.previous_month_link.show();
        } else {
            this.previous_month_link.hide();
            this.previous_month_link.attr('href', '#');
        }
        $.each(this.buttons, function(index, button){
            replaceQueryString($(button), '?' + data['current_month_qs']);
        });
        this.month_grid.html(data['grid']);
    }

    CalendarController.prototype.init = function() {
        var loc = window.location.toString().replace(/#.*/, '');
        if (this.previous_month_link.length > 0) {
            var prevHref = this.previous_month_link.attr('href');
            var qs = extractQueryParameters(prevHref);
            this.previous_month_link.attr('href', loc + "#" + objectToQuery(qs));
        }
        if (this.next_month_link.length > 0) {
            var nextHref = this.next_month_link.attr('href');
            var qs = extractQueryParameters(nextHref);
            this.next_month_link.attr('href', loc + "#" + objectToQuery(qs));
        }
    }

    function changeCalendarLocation(loc){
        var controller = new CalendarController('#main');
        $.getJSON(loc, function(data){
            controller.update(data);
            if (!(data['remote_user'])){
                //remove the internal categories so they can't be selected via select-all
                $("#filter-list-internal").remove();
                $.cookie('cal-user', null);

            }else{
                $.cookie('cal-user', data['remote_user']);
            }
            checkEventCategories();
        });
    }

    function updateCalendar() {
        var h = window.location.hash.replace(/^\#/, '?') || '?';
        if (h == "?"){
            //using query params instead of hash
            h = window.location.search.replace(/^\#/, '?') || '?';
        }
        loc = '/events/calendar/code/calendar_rest' + h;
        changeCalendarLocation(loc);
    }

    $("#list-mode").click(function(){
        $.cookie('cal-mode', "LIST");
    });

    $("#grid-mode").click(function(){
        $.cookie('cal-mode', "GRID");
    });

    function checked_subjects() {
        var checkboxes = $('#filter-content input[name=subjects]:checked');
        var values = [];
        $.each(checkboxes, function(index, node){
            values.push($(node).val());
        });
    }

    function set_all_subjects(state) {
        var checkboxes = $('#filter-content input[name=subjects]');
        if (state) {
            checkboxes.attr('checked', 'checked');
        } else {
            checkboxes.removeAttr('checked');
        }
    }

    $("#today").click(function(){
        var today = new Date();
        var month = today.getMonth() +1;
        var year = today.getFullYear();
        var day = today.getDate();

        h = "?month=" + month + "&day=" + day + "&year=" + year;
        var mode = $("#view-mode li .active").text();
        if (mode == "LIST"){
            h += "&mode=list"
        }
        loc = '/events/calendar/code/calendar_rest' + h;

        changeCalendarLocation(loc);

        var search = "[name=" + day + "]";
        if(mode == "LIST"){
            $('html,body').animate({
                scrollTop:  $(search).offset().top
            });
        }

    });

    $(window).bind('jQuery.hashchange', updateCalendar);

    $(document).ready(function(event) {
        var queryParams = extractQueryParameters(window.location.toString());
        var hashParams = extractHashParameters(window.location.toString());
        var controller = new CalendarController('#main');
        controller.init();
        if (hashParams.count() >= 0 || queryParams >= 0) {
            updateCalendar();
        }

        $('.subject').change(function() {
            createCategoryCookie();
            checkEventCategories();
        });

        //hide the dropdown if it's open and a click happens outside it
        $("body").click(function(event) {
            var dd = $('#filter-dropdown'),
                target = $(event.target);
            if (dd.css('display') != 'none') {
                if (target.parents().filter(dd).length == 0) {
                    $('#filter .button').click();
                }
            }
        });

        // Calendar filter big dropdown
        $('#filter .button').click(function() {
            $('#filter-dropdown').toggle(0, function(){
                var holder = $('#filter-holder'),
                    h5s = holder.find('h5'),
                    order = ['Academics', 'General', 'Offices', 'Internal'],
                    el = $(this);
                if (h5s.length == 4) { // if not authenticated, sort alphabetically
                    order.sort();
                }
                if (holder.isotope !== undefined) {
                    //isotope isn't smart enough to know the correct order.  Two categories
                    // are short and should be on top of eachother, the rest are in separate
                    // columns.  Use a special sorting order to do this.
                    holder.isotope({
                        animationEngine: 'css',
                        getSortData: {
                            byTitle: function(elem) {
                                var  h5 = elem.find('h5').html();
                                return jQuery.inArray(h5, order);
                            }
                        },
                        sortBy: 'byTitle',
                        masonry : {columnWidth : 220 }
                    });
                }
                // adjust the height of parent containers as necessary
                // -- the day view has a really short height -- shorter than the filter,
                //    which causes a scrollbar to appear.  Get the height of the
                //    calendar container, and make sure it is at least the height
                //    of the popup + (the difference between the top of the dropdown
                //    and the top of the calendar container)
                var cm = el.parents('#calendar-mode'),
                    height = el.height() + Math.abs(cm.offset().top - el.offset().top);
                if (el.css('display')=='none') {
                    //restore old cm height
                    if (cm.data('container-height')!=null) {
                        cm.height(cm.data('container-height'));
                    }
                } else {
                    if (cm.height() < height) {
                        cm.data('container-height', cm.height());
                        cm.height(height);
                    }
                }
            });
            $(this).toggleClass('active');
            return false;
        });

        $('#filter-close').click(function(event) {
            $('#filter .button').click();
            event.preventDefault();
        });

        $('#filter-content').bind('submit', function(){
            var loc = window.location.toString()
            var hashParams = extractHashParameters(loc);
            var queryParams = extractQueryParameters(loc);
            delete queryParams['subjects'];
            delete hashParams['subjects'];
            if (hashParams.count() > 0) {
                loc = loc.replace(/([?#].*)/, '?' + objectToQuery(hashParams));
            } else {
                loc = loc.replace(/([?#].*)/, '?' + objectToQuery(queryParams));
            }
            $(this).attr('action', loc);
        });

        $('#filter-content .filter-actions').bind('click', function(event) {
            var target = $(event.target);
            $.removeCookie('calendar-categories');
            switch (target.attr('name')) {
                case 'none':
                    set_all_subjects(false);
                    break;
                case 'all':
                    set_all_subjects(true);
                    break;
                default:
                    break;
            }
            createCategoryCookie();
            checkEventCategories();
            return false;
        });

        $('#view-mode a').click(function(event) {
            // switch 'active' class on button group, remove old class from
            // calendar div, add new class on calendar div.
            var $el = $(this),
                $active = $('#view-mode a.active'),
                $cal = $('#calendar-mode');
            $active.removeClass('active');
            $el.addClass('active');
            $cal.removeClass($active.attr('name'));
            $cal.addClass($el.attr('name'));
            return false;
        });

        //datepicker calendars for day and week buttons
        (function() { // for scoping vars
            var d = $('#time-mode a[name=day]'),
                di = $('<input type="hidden" id="day-picker" />'),
                w = $('#time-mode a[name=week]'),
                wi = $('<input type="hidden" id="week-picker" />');
            d.before(di);
            di.datepicker({
                maxDate: 365,
                minDate: -365,
                selectOtherMonths: true,
                showOtherMonths: true,
                onClose: function(dateText, inst) {
                    var button = inst.input.next();
                    if (!button.data('dp-original-state-active')) {
                        button.removeClass('active');
                    }
                },
                onSelect: function(dateText, inst) {
                    var d = new Date(dateText),
                        href = inst.input.next().attr('href');
                    href = href.replace(/month=\d\d?/,'month='+(d.getMonth()+1)).
                        replace(/year=\d\d\d?\d?/, 'year='+d.getFullYear()).
                        replace(/day=\d\d?/, 'day='+d.getDate())
                    window.location.href = href;
                }
            });
            d.data('dp-state', 'closed')
            d.data('dp-original-state-active', d.hasClass('active'));
            d.click(function(event) {
                var el = $(this),
                    p = el.prev(),
                    w = p.datepicker('widget');
                if (el.data('dp-state') == 'closed' || w.css('display') == 'none') {
                    p.datepicker('show');
                    el.data('dp-state', 'open')
                    w.position({
                        my: 'top',
                        at: 'bottom',
                        of: el,
                        collision: 'fit fit'
                    });
                    if (!el.data('dp-original-state-active')) {
                        el.addClass('active')
                    }
                } else {
                    // click has happened outside the calendar, so the calendar has
                    // already hidden the datepicker via a separate event.
                    // here we just need to update the state of the button
                    el.data('dp-state', 'closed')
                }
                return false;
            });

            w.before(wi);
            wi.datepicker({
                maxDate: 365,
                minDate: -365,
                selectOtherMonths: true,
                showOtherMonths: true,
                beforeShow: function(input, inst) {
                    inst.dpDiv.addClass('week-picker');
                },
                onClose: function(dateText, inst) {
                    var button = inst.input.next();
                    inst.dpDiv.removeClass('week-picker');
                    if (!button.data('dp-original-state-active')) {
                        button.removeClass('active');
                    }
                },
                onSelect: function(dateText, inst) {
                    // we want to start on the most recent Sunday.  So substract the
                    // day of the week (in milliseconds) from the milliseconds for the
                    // date, reset window to the new url for the week.
                    var d = new Date(dateText),
                        day = d.getDay(), // day of week
                        dayInMillis = day * 86400000, // day of week in milliseconds
                        sunday = new Date(d.getTime() - dayInMillis),
                        href = inst.input.next().attr('href');
                    href = href.replace(/month=\d\d?/,'month='+(sunday.getMonth()+1)).
                        replace(/year=\d\d\d?\d?/, 'year='+sunday.getFullYear()).
                        replace(/day=\d\d?/, 'day='+sunday.getDate());
                    window.location.href = href;
                }
            });
            wi.datepicker('widget').hide();
            w.data('dp-state','closed');
            w.data('dp-original-state-active', w.hasClass('active'));
            w.click(function(event) {
                var el = $(this),
                    p = el.prev(),
                    w = p.datepicker('widget');
                if (el.data('dp-state') == 'closed' || w.css('display')=='none') {
                    p.datepicker('show');
                    el.data('dp-state', 'open')
                    w.position({
                        my: 'top',
                        at: 'bottom',
                        of: el,
                        collision: 'none'
                    });
                    if (!el.data('dp-original-state-active')) {
                        el.addClass('active')
                    }
                } else {
                    // click has happened outside the calendar, so the calendar has
                    // already hidden the datepicker via a separate event.
                    // here we just need to update the state of the button
                    el.data('dp-state', 'closed')
                }
                return false;
            });
        })(); // end for scoping vars

        //when in grid mode, display a hover for the event details.
        (function() {
            var hover_div = $('<div id="event-hover"></div>'),
                calendar_mode = $('#calendar-mode'),
                calendar_toolbar = $('#calendar-toolbar'),
                calendar_main = $('#calendar-main'),
                active_dt,
                display = function(dt) { // display hover, given a dt jquery object
                    if (!calendar_mode.hasClass('calendar-grid'))
                        return;

                    if (dt.get(0) == active_dt) // do not redisplay if dt is active
                        return;

                    //locally scoped variables, for quicker lookup
                    var next = dt.next(),
                        hd = hover_div,
                        a = dt.find('a'),
                        pos = dt.position(),
                        hover_left = pos.left + dt.width() - 20,
                        hover_top = pos.top,
                        dt_offset = dt.offset().top,
                        next_offset,
                        height;

                    //if the next element is a dd, display it in a hover
                    if (next.size()==1 && next.get(0).tagName == 'DD') {
                        active_dt = dt.get(0);
                        // take the contents of the dd and place it in a hover
                        dt.append(hd);
                        hd.children().remove();
                        if (a.size()) { // if there is a link, add it into the hover
                            hd.append('<a href="'+a.attr('href')+'">Visit Website</a><br />');
                        }
                        hd.append(next.children().clone());



                        // if hover overflows right side of window, display on left
                        // side instead
                        if (hover_left + hd.outerWidth() > $(calendar_main).width() ) {
                            hover_left = pos.left - hd.width();
                        }
                        // get nearest following next sibling of nearest positioned parent
                        // we use this sibling to determine the height of the calendar.
                        var p = dt.offsetParent();
                        while (p.next().size()==0) {
                            p = p.parent();
                        }
                        next_offset = p.next().offset().top,
                            height = hd.outerHeight();
                        // if the positioned hover will overflow the calendar height
                        // (causing a scrollbar or such), reposition the top of the
                        // hover to be the amount of the overflow + 20 (for some extra)
                        if (dt_offset > $(calendar_main).height() + 20) {
                            hover_top = pos.top - height + 20;
                        }
                        hd.css({
                            top: hover_top,
                            left: hover_left
                        });
                        hd.show();
                    }
                },
                hide = function(dt) { // hide hover, given a dt jquery object
                    if (!calendar_mode.hasClass('calendar-grid'))
                        return;
                    var next = dt.next(),
                        hd = hover_div;
                    if (next.size()==1 && next.get(0).tagName == 'DD') {
                        hd.hide();
                        active_dt = null;
                    }

                };

            // capture click events, if target is within a DT (and NOT within the
            // hover), display the hover
            calendar_mode.click(function(event) {
                var target = $(event.target),
                    tagName = target.get(0).tagName,
                    dt = tagName=='DT' ? target : target.parents('dt'),
                    hover = target.attr('id')=='event-hover' ? target : target.parents('#event-over');
                if (hover.size()) // if click is in hover, do nothing
                    return;
                if (dt.size()) {
                    display(dt);
                } else {
                    hover_div.hide();
                }
            });

            // when we upgrade jquery (1.7+), will need to switch to 'on' instead of 'delegate'
            calendar_mode.delegate('dt',
                {
                    mouseenter: function(event) { // mouseenter
                        display($(this));
                    },
                    mouseout: function(event) {  // mouseout
                        var to = $(event.relatedTarget),
                            parents = to.parents('dt');
                        //mouseout events are fired when moving from the 'dt' to an inner
                        // element.  When this happens, DO NOT hide the hover.  According to
                        // "javascript: the definitive guide 5th ed. pg 408, relatedTarget
                        // is used on mouseout events, referring to the node the mouse
                        // entered when leaving the target.  If relatedTarget is a child of
                        // the dt, do not hide.
                        if (to.size() && parents.size() && parents.get(0) == active_dt)
                            return;
                        hide($(this));
                    }
                }
            );
            // suppress clicks on event links (the dt) when in grid mode
            calendar_mode.delegate('dt a', 'click',
                function(event) {
                    if (!calendar_mode.hasClass('grid') || $(this).parents('#event-hover').size()==1)
                        return;
                    event.preventDefault();
                }
            );
        })();

        if ($.cookie('cal-mode') == "LIST"){
            $("#list-mode").addClass("active");
            $("#grid-mode").removeClass("active");
            $('#view-mode a').click();
        }
    });

})(jQuery);