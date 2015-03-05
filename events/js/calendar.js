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
        this.next_month_link = $('a.next-month');
        this.previous_month_link = $('a.previous-month');
        this.buttons = $('a.button');
        this.month_grid = $('div#calendar-main');
        this.title = $('div.calendar-title h3');
    }

    function getRemoteUser(){
        if($.cookie('cal-user') && $.cookie('cal-user') != null && $.cookie('cal-user') != "null"){
            return $.cookie('cal-user');
        }else{
            var url = '/code/general-cascade/get_remote_user';
            $.getJSON(url, function(data){
                var remote_user = data['remote_user'];
                if( remote_user){
                    $.cookie('cal-user', remote_user);
                }
            });
        }

    }

    function updateWelcomeBar(){
        var remote_user = $.cookie('cal-user');
        if (remote_user != null && remote_user != "null"){
            $("#bu-topbar-welcome").html("Welcome " + remote_user);
        }else{
            var append = document.URL.replace("#", "?");
            var url = "https://auth.bethel.edu/cas/login?service=" + append;
            //url = url.replace("https", "http");
            $("#bu-topbar-welcome").html('Welcome guest: <a href="' + url + '">Login</a>');
        }
    }

    CalendarController.prototype.update = function(data) {
        var loc = window.location.toString().replace(/#.*/, '');
        this.title.text(data['month_title']);
        getRemoteUser();
        updateWelcomeBar();

        if (data['next_month_qs'] !== null) {
            this.next_month_link.attr('href', loc + "#" + data['next_month_qs']);
            // Removed the words "Next Month"
            //this.next_month_link.html(data['next_title'] + ' &raquo;');
            this.next_month_link.html('&raquo;');
            this.next_month_link.show()
        } else {
            this.next_month_link.hide();
            this.next_month_link.attr('href', '#');
        }
        if (data['previous_month_qs'] !== null) {
            this.previous_month_link.attr('href',
                loc + "#" + data['previous_month_qs']);
            // Removed the words "Previous Month"
            //this.previous_month_link.html('&laquo; ' + data['previous_title']);
            this.previous_month_link.html('&laquo; ');
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
            getRemoteUser();
            var remote_user = $.cookie('cal-user');
            if (!remote_user){
                //remove the internal categories so they can't be selected via select-all
                $("#filter-list-internal").remove();
                $.cookie('cal-user', null);

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

    $(".view-mode--list").click(function(event){
        var d = document.getElementById("calendar-mode");
        d.className = d.className.replace('calendar-grid','calendar-list');

        var listMode = document.getElementsByClassName("view-mode--list")[0].getElementsByTagName('a')[0];

        if( listMode.className.indexOf(" active") == -1 ) {
            listMode.className = listMode.className + " active";

            var gridMode = document.getElementsByClassName("view-mode--grid")[0].getElementsByTagName('a')[0];
            gridMode.className = gridMode.className.replace(' active', '');
        }
        event.preventDefault();
    });

    $(".view-mode--grid").click(function(event){
        var d = document.getElementById("calendar-mode");
        d.className = d.className.replace('calendar-list','calendar-grid');

        var gridMode = document.getElementsByClassName("view-mode--grid")[0].getElementsByTagName('a')[0];

        if( gridMode.className.indexOf(" active") == -1 ){
            gridMode.className = gridMode.className + " active";

            var listMode = document.getElementsByClassName("view-mode--list")[0].getElementsByTagName('a')[0];
            listMode.className = listMode.className.replace(' active', '');
        }
        event.preventDefault();
    });

    function checked_subjects() {
        var checkboxes = $('.filter-content input[name=subjects]:checked');
        var values = [];
        $.each(checkboxes, function(index, node){
            values.push($(node).val());
        });
    }

    function set_all_subjects(state) {
        var checkboxes = $('.filter-content input[name=subjects]');
        if (state) {
            checkboxes.attr('checked', 'checked');
        } else {
            checkboxes.removeAttr('checked');
        }
    }

    $(".today").click(function(){
        var today = new Date();
        var month = today.getMonth() +1;
        var year = today.getFullYear();
        var day = today.getDate();

        h = "?month=" + month + "&day=" + day + "&year=" + year;

        if (document.getElementsByClassName("view-mode--list")[0].getElementsByTagName('a')[0].className.indexOf(" active") != -1){
            var mode = "LIST";
        }
        else{
            var mode = "GRID";
        }

        if (mode == "LIST"){
            h += "&mode=list";
        }
        loc = '/events/calendar/code/calendar_rest' + h;

        changeCalendarLocation(loc);

        var search = "[name=" + day + "]";
        if(mode == "LIST"){
            $('html,body').animate({
                scrollTop:  $(search).offset().top
            });
        }

        event.preventDefault();
    });

    $(window).bind('jQuery.hashchange', updateCalendar);

    $(document).ready(function(event) {
        var queryParams = extractQueryParameters(window.location.toString());
        var hashParams = extractHashParameters(window.location.toString());
        var controller = new CalendarController('#main');
        controller.init();
        getRemoteUser();
        updateWelcomeBar();
        if (hashParams.count() >= 0 || queryParams >= 0) {
            updateCalendar();
        }

        $('.subject').change(function() {
            createCategoryCookie();
            checkEventCategories();
        });

        //hide the dropdown if it's open and a click happens outside it
        $("body").click(function(event) {
            var dd = $('.filter-dropdown'),
                target = $(event.target);
            if (dd.css('display') != 'none') {
                if (target.parents().filter(dd).length == 0) {
                    $(".filter-by-category").click();
                }
            }
        });

        $('.filter-by-category').click(function() {
            $('.filter-dropdown').toggle(0, function(){
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
            $(".filter-by-category").click();
            event.preventDefault();
        });

        $('.filter-content').bind('submit', function(){
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

        $('.filter-content .filter-actions').bind('click', function(event) {
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

        $('.view-mode a').click(function(event) {
            // switch 'active' class on button group, remove old class from
            // calendar div, add new class on calendar div.
            var $el = $(this),
                $active = $('.view-mode a.active'),
                $cal = $('#calendar-mode');
            $active.removeClass('active');
            $el.addClass('active');
            $cal.removeClass($active.attr('name'));
            $cal.addClass($el.attr('name'));
            return false;
        });

        //when in grid mode, display a hover for the event details.
        (function() {
            var hover_div = $('<div id="event-hover"></div>'),
                calendar_mode = $('#calendar-mode'),
                calendar_toolbar = $('.calendar-toolbar'),
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

        var mq  = matchMedia('(min-width: 800px)');
        mq.addListener(function(mql) {
            if (mql.matches) {
                document.querySelector("#calendar-mode").classList.add('calendar-grid');
                document.querySelector("#calendar-mode").classList.remove('calendar-list');
                document.querySelector(".view-mode--list > a").classList.remove('active');
                document.querySelector(".view-mode--grid > a").classList.add('active');
            }
            else {
                document.querySelector("#calendar-mode").classList.add('calendar-list');
                document.querySelector("#calendar-mode").classList.remove('calendar-grid');
            }
        });
    });

})(jQuery);