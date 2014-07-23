/**
 * Created by ces55739 on 7/18/14.
 */

Element.prototype.remove = function() {
    this.parentElement.removeChild(this);
}

$(document).ready(function() {

    function createEventFeed() {
        loc = '/code/events/php/event_feed_rest';

        // Event Feed attributes from Cascade
        numEvents = document.getElementById("event-feed").getAttribute('show-num-events');
        hideFeed = document.getElementById("event-feed").getAttribute('hide-feed-if-none');
        addFeaturedEvent = document.getElementById("event-feed").getAttribute('add-featured-event');  // This might not be needed.

        // Featured Event attributes from Cascade
        featuredEventOne = [];
        featuredEventTwo = [];
        featuredEventOne[0] = document.getElementById("featured-event-one").getAttribute('url');
        featuredEventOne[1] = document.getElementById("featured-event-one").getAttribute('description');
        featuredEventOne[2] = document.getElementById("featured-event-one").getAttribute('hide-date');
        featuredEventTwo[0] = document.getElementById("featured-event-two").getAttribute('url');
        featuredEventTwo[1] = document.getElementById("featured-event-two").getAttribute('description');
        featuredEventTwo[2] = document.getElementById("featured-event-two").getAttribute('hide-date');


        $.getJSON(loc, {numEvents: numEvents, featuredEventOneOptions: featuredEventOne, featuredEventTwoOptions: featuredEventTwo}, function(data)
        {
            // Split the 'data' array into 2 arrays. The first is the featured events, the second is the event feed.
            featuredEventArray = data[0];
            eventFeedArray = data[1];

            // Check to see if there are any events.
            if( eventFeedArray.length == 0){
                // If there aren't any, either display a message, or remove the feed.
                if(hideFeed == "No")
                    document.getElementById("event-feed").innerHTML = "<p>There are no events to display.</p>";
                else{
                    // Remove the elements since we do not need them.
                    document.getElementById("event-feed-header").remove();
                    document.getElementById("event-feed-button").remove();
                    document.getElementById("event-feed").remove();
                }
            }
            else
            {
                // Only attempt to display the events if this is checked. I think this is a worthless check.
                //if( addFeaturedEvent == "Yes"){
                    // Display Featured Events
                    $.each(featuredEventArray, function(index, value){
                        if( value != "null"){
                            if( index == 0){
                                document.getElementById("featured-event-one").innerHTML = value;
                            }
                            else{
                                document.getElementById("featured-event-two").innerHTML = value;
                            }
                        }
                    });
                //}

                // Display Event Feed
                $.each(eventFeedArray, function(index, value){
                    document.getElementById("event-feed").innerHTML = document.getElementById("event-feed").innerHTML + value;
                });
            }
        });
    };

    createEventFeed();

});