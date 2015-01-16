<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 8/28/14
 * Time: 10:21 AM
 */
    header('Content-type: application/xml');
    require_once 'events_helper.php';

    $month = date('n');
    $year = date('Y');
    $day = date('j');

    // get events
    $xml = get_event_xml();

    //event array has each date as a key in m-d-y format
    $date = new DateTime($year . '-' . $month . "-" . $day);
    $key = $key = $date->format('Y-m-d');

    $todays_events = $xml[$key];
    $display_date = $date->format('F j, Y');
?>
<div class="channel-section">
<div class="uportal-cms-block">
<div class="uportal-channel-text" style="text-align: right">
<span style="float:left"><?php echo $display_date ?> </span>
<a href="http://tinker.bethel.edu/event/" title="Submit Events">Submit</a>
|
<a href="https://www.bethel.edu/events/calendar/" title="Full Calendar of Events">Full Calendar</a>
</div>
<div id="todayseventscont">
<ul id="todayseventslist" class="alternating uportal-channel-text">
<?php
foreach($todays_events as $event){

        echo "<li>";
        echo '<h4>';
        if ($event['external-link'][0]){
            $link = $event['external-link'][0];
        }else{
            $link = 'http://www.bethel.edu'. $event['path'][0];
        }
        //move all this to event_helper.php
        $title = $event['title'][0];
        $start = $event['specific_start'][0];
        $end = $event['specific_end'][0];
        $all_day = $event['specific_all_day'];
        $location = $event['location'][0];
        $description = $event['description'][0];
        $start_date = $date = new DateTime('now', new DateTimeZone('America/Chicago'));
        $start_date->setTimestamp($start / 1000);
        $start = $start_date->format("g:i a");

        $title = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $title);
        $location = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $location);
        $description = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $description);


    if (substr($start, -6, 3) == ':00'){
            $start = $start_date->format("g a");
        }
        $end_date = $date = new DateTime('now', new DateTimeZone('America/Chicago'));
        $end_date->setTimestamp($end / 1000);
        $end = $end_date->format("g:i a");
        if (substr($end, -6, 3) == ':00'){
            $end = $end_date->format("g a");
        }
        if ($start && $end && !$all_day){
            if ($start == $end)
                $event_date = $start;
            else
                $event_date = $start . '-' . $end;
        }
        echo "<a href='$link'>$title</a>";
        echo '</h4>';
        echo '<p>';

        $info = "";

        if ($location){
            $info .=  $location;
        }
        if ($event_date){
            if ($info != ""){
                $info .= " | ";
            }
            $info .= $event_date;

        }
        if ($description){
            if ($info != ""){
                $info .= " | ";
            }
            $info .= $description;
        }
        echo $info;
        echo '</p>';
        echo '</li>';
    }
?>
</ul>
</div>
</div>
</div>