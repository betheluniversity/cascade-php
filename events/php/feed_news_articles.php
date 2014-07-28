<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 9:35 AM
 */
    $newsArticleFeedCategories;

    $NumArticles;
    $Heading;
    $HideWhenNone;
    $StartDate;
    $EndDate;

    $AddButton;
    $MoreArticlesLink;
    $ButtonText;


    // returns an array of html elements.
    function create_news_article_feed(){
      // Feed

        global $newsArticleFeedCategories;
        $categories = $newsArticleFeedCategories;
        $arrayOfEvents = get_xml("/var/www/cms.pub/_shared-content/xml/articles.xml", $categories);
        $sortedEvents = sort_events($arrayOfEvents);

        // Only grab the first X number of events.
        global $NumEvents;
        $numEventsToFind = $NumEvents;
        $sortedEvents = array_slice($sortedEvents, 0, $numEventsToFind, true);

        $articleArray = array();
        foreach( $sortedEvents as $event){
            array_push($articleArray, $event['html']);
        }

      // HEADING
        global $Heading;
        $heading = array("<h2>".$Heading."</h2>");

      // BUTTON
        global $AddButton;
        global $MoreArticlesLink;
        global $ButtonText;
        $buttonHTML = array("");

        if( $AddButton == "Yes")
        {
            array_push( $buttonHTML, '<a id="news-article-button" class="btn center" href="http://www.bethel.edu/' . $MoreArticlesLink . '">' . $ButtonText . '</a>');
        }

        // Hide if None
        global $HideWhenNone;
        if( sizeOf( $articleArray) == 0){
            if( $HideWhenNone == "Yes"){
                $heading = array();
                $articleArray = array();
            }
            else{
                $articleArray = array("<p>No news articles at this time.</p>");
            }
        }

        $combinedArray = array_merge($heading, $articleArray, $buttonHTML);

        return $combinedArray;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // Returns the information of the page.
    ////////////////////////////////////////////////////////////////////////////////
    // Make sure to set the 'html' to what you want to display.
    // Make sure to set the 'date-for-sorting' to sort the dates. This is a timestamp.
    // Set 'display-on-feed' = 'Yes' if you want to display the event. Else, set to 'No'
    ////////////////////////////////////////////////////////////////////////////////
    function inspect_news_article_page($xml, $categories){
        $page_info = array(
            "title" => $xml->title,
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "date-for-sorting" => "",       //timestamp.
            "md" => array(),
            "html" => "",
            "display-on-feed" => "No",
        );

        $ds = $xml->{'system-data-structure'};
        $page_info['display-on-feed'] = match_metadata($xml, $categories);
        $page_info['date-for-sorting'] = time();

        // To get the correct definition path.
        $dataDefinition = $ds['definition-path'];
        /////////////////// Write Code Here //////////////////////


        if( $dataDefinition == "News Article")
        {
            $page_info['html'] = get_news_article_html($page_info, $xml);

            $page_info['display-on-feed'] = display_on_feed($page_info);

        }
        //////////////////////////////////////////////////////////

        return $page_info;
    }

    function display_on_feed(){
        global $StartDate;
        global $EndDate;
        $modifiedStartDate = $StartDate / 1000;
        $modifiedEndDate = $EndDate / 1000;

        // Check if it falls between the given range.
        if( $StartDate != "" && $EndDate != "" ){
//                $date =
//                if( $StartDate < $date && $date < $EndDate){
            return "Yes";
//                }
        }
        elseif( $StartDate != ""){
//                $date =
//                if( $StartDate < $date && $date < $EndDate){
            return "Yes";
//                }
        }
        elseif( $EndDate != ""){
//                    $date =
//                if( $StartDate < $date && $date < $EndDate){
            return "Yes";
//                }
        }

        return "Yes";
    }

    function get_news_article_html( $article, $xml ){
        $article['html'] = '<div class="media-box pb1">';

        $article['html'] .= '<a href="http://www.bethel.edu/news/articles/2014/july/spitfire-musical">';
        $article['html'] .= '<img class="media-box-img" src="'.$xml->{'system-data-structure'}->{'image'}->path.'" alt="'.$article['description'].'" title="'.$article['title'].'">';
        $article['html'] .= '</a>';

        $article['html'] .= '<div class="media-box-body">';
        $article['html'] .= '<h2 class="h5"><a href="http://www.bethel.edu'.$article['path'].'">'.$article['title'].'</a></h2>';
        $article['html'] .= '<p>'.$article['description'].'</p>';
        $article['html'] .= '</div>';

        $article['html'] .= '</div>';

        return $article['html'];
    }

    function match_metadata($xml, $categories){
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            $options = array('general', 'offices', 'academic-dates', 'cas-departments', 'internal');

            foreach($md->value as $value ){
                if($value == "None" || $value == "none"){
                    continue;
                }
                if (in_array($name,$options)){
                    //Is this a calendar category?
                    if (in_array($value, $categories)){
                        return "Metadata Matches";
                    }
                }

            }
        }
        return "No";
    }

?>