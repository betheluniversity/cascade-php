<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 11/5/14
 * Time: 3:29 PM
 */


if($program == "CAPS")
    $program = "COPN";
else
    $program = "GOPN";
$url = "http://wsapi.bethel.edu/open-enrollment-courses/$program";
$results = json_decode(file_get_contents($url));
echo $results->data;


?>

<style type="text/css">
    ul.collapseable li p { position: absolute; left: -999em; display: block; }
    ul.collapseable li.open p { position: relative; left: 0; display: none; }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        $("ul.collapseable li").click(function() {
            var $p =  $(this).find("p");
            if ($p.is(':visible')) {
                if ($(this).hasClass('open')) {
                    //Expanded, needs to collapse and go back to the left
                    $p.slideToggle(300, function() { $(this).removeAttr('style'); $(this).parent().removeClass('open'); });
                    _gaq.push(['_trackEvent', 'Clicks', 'Collapsible List', 'Course Catalog Collapse']);
                }
                else {
                    //Off to the left, needs to be brought right and expanded
                    $(this).addClass("open");
                    $p.slideToggle(300);
                    _gaq.push(['_trackEvent', 'Clicks', 'Collapsible List', 'Course Catalog Expand']);
                }
            }
            else {
                //The paragraph is not visible, so it is in a transition state
            }
        });
        $("ul.collapseable li p").click(function() {
            return false;
        });
    });
</script>