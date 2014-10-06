<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 10/2/14
 * Time: 11:01 AM
 */
    $ch = curl_init($feed_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
    $xml = curl_exec($ch);
    curl_close($ch);
    $xml = new SimpleXmlElement($xml);

    $limit = 3;
    $count = 0;

    echo '<div class="gridbox">';
    echo '<div class="facebook-updates">';
    echo '<h3>Facebook</h3>';

    $logo = $xml->{'logo'};

    foreach($xml->entry as $entry) {

        $message = $entry->{'message'};
        $link = $entry->{'link'}['href'];
        $title = $entry->title;
        $date = $entry->{'published'};
        $author = $entry->author->name;

        if($count == $limit){
            break;
        }else{
            $count++;
        }
        echo '<div class="media">';
            echo "<a class='img' href='$link'><img src='$logo'></a>";
            echo '<div class="bd">';
                echo '<p>';
                    echo "<a href='$link'>";
                    echo $author;
                    echo "</a>";
                echo '</p>';
                echo '<p class="date">';
                    $datetime = new DateTime($date);
                    $datetime->modify('-1 hour');
                    echo $datetime->format('F j, Y | g:i a');
                echo '</p>';
                echo '<p>';
                echo $title;
                echo '</p>';
            echo '</div>';
        echo '</div>';
    }


?>


