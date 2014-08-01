<?php
/**
 * Created by PhpStorm.
 * User: ces55739
 * Date: 7/25/14
 * Time: 9:35 AM
 */
    // GLOBALS

    // Metadata of feed
    $School;
    $Department;
    $UniqueNews;

    $NumArticles;
    $Heading;
    //$HideWhenNone;
    $AddFeaturedArticle;
    $StartDate;
    $EndDate;

    $featuredArticleOptions;

    $AddButton;
    $MoreArticlesLink;
    $ButtonText;


    // returns an array of html elements.
    function create_news_article_feed(){
      // Feed
        global $newsArticleFeedCategories;
        $categories = $newsArticleFeedCategories;

        if( strstr(getcwd(), "staging/public") ){
            $arrayOfArticles = get_xml("/var/www/staging/public/_shared-content/xml/articles.xml", $categories);
        }
        else{ //if( strstr(getcwd(), "cms.pub") )
            $arrayOfArticles = get_xml("/var/www/cms.pub/_shared-content/xml/articles.xml", $categories);
        }

        $sortedArticles = sort_news_articles($arrayOfArticles);

        // Only grab the first X number of articles.
        global $NumArticles;
        $sortedArticles = array_slice($sortedArticles, 0, $NumArticles, true);

        $articleArray = array();
        foreach( $sortedArticles as $article){
            array_push($articleArray, $article['html']);
        }

      // HEADING
        global $Heading;
        $heading = array("<h2>".$Heading."</h2>");

      // FEATURED ARTICLES
        $featuredArticles = create_featured_articles_array();

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

        $combinedArray = array_merge($featuredArticles, $heading, $articleArray, $buttonHTML);

        return $combinedArray;
    }

    ////////////////////////////////////////////////////////////////////////////////
    // Gathers the info/html of the news article
    ////////////////////////////////////////////////////////////////////////////////
    function inspect_news_article_page($xml, $categories){
        $page_info = array(
            "title" => $xml->title,
            "display-name" => $xml->{'display-name'},
            "published" => $xml->{'last-published-on'},
            "description" => $xml->{'description'},
            "path" => $xml->path,
            "date" => $xml->{'system-data-structure'}->{'publish-date'},       //timestamp.
            "md" => array(),
            "html" => "",
            "display-on-feed" => "No",
        );

        $ds = $xml->{'system-data-structure'};
        $page_info['display-on-feed'] = match_metadata_news_articles($xml, $categories);
        $page_info['date-for-sorting'] = time();

        // To get the correct definition path.
        $dataDefinition = $ds['definition-path'];

        if( $dataDefinition == "News Article")
        {

            $page_info['html'] = get_news_article_html($page_info, $xml);

            $page_info['display-on-feed'] = display_on_feed_news_articles($page_info, $ds);

            // Featured Articles
            global $featuredArticleOptions;
            global $AddFeaturedArticle;
            // Check if it is a featured Article.
            // If so, get the featured article html.
            if ( $AddFeaturedArticle == "Yes"){
                foreach( $featuredArticleOptions as $key=>$options)
                {
                    // Check if the url of the article = the url of the desired feature article.
                    if( $page_info['path'] == $options[0]){
                        $featuredArticleOptions[$key][3] = get_featured_article_html( $page_info, $xml, $options);
                    }
                }
            }
        }

        return $page_info;
    }

    // Determine if the news article falls within the given range to be displayed
    function display_on_feed_news_articles($page_info, $ds){
        $date = $ds->{'publish-date'};
        global $StartDate;
        global $EndDate;

        if( $page_info['display-on-feed'] == "Metadata Matches")
        {
            // Check if it falls between the given range.
            if( $StartDate != "" && $EndDate != "" ){
                if( $StartDate < $date && $date < $EndDate){
                    return "Yes";
                }
            }
            elseif( $StartDate != ""){
                if( $StartDate < $date){
                    return "Yes";
                }
            }
            elseif( $EndDate != ""){
                if( $date < $EndDate){
                    return "Yes";
                }
            }
            else
            {
                return "Yes";
            }
        }

        return "No";
    }

    // Returns the html of the news article
    function get_news_article_html( $article, $xml ){
        $ds = $xml->{'system-data-structure'};
        $image = $ds->{'media'}->{'image'}->{'path'};
        $date = $ds->{'publish-date'};

        $article['html'] = '<div class="media-box pb1">';

        $article['html'] .= '<a href="http://www.bethel.edu'.$article['path'].'">';
        $article['html'] .= '<img class="media-box-img" src="http://www.bethel.edu'.$image.'" alt="'.$article['description'].'" title="'.$article['title'].'">';
        $article['html'] .= '</a>';

        $article['html'] .= '<div class="media-box-body">';
        $article['html'] .= '<h2 class="h5"><a href="http://www.bethel.edu'.$article['path'].'">'.$article['title'].'</a></h2>';

        if( $date != "" && $date != "null" )
        {
            $formattedDate = format_featured_date_news_article($date);
            $article['html'] .= "<p>".$formattedDate."</p>";
        }

        $article['html'] .= '<p>'.$article['description'].'</p>';
        $article['html'] .= '</div>';

        $article['html'] .= '</div>';

        return $article['html'];
    }

    // Checks the metadata of the page against the metadata of the news articles.
    // if it matches, return "Metadata Matches"
    // else, return "No"
    function match_metadata_news_articles($xml, $categories){
        global $School;
        global $Department;
        global $UniqueNews;
        foreach ($xml->{'dynamic-metadata'} as $md){

            $name = $md->name;

            foreach($md->value as $value ){
                if($value == "Select" || $value == "select"){
                    continue;
                }
                if( $name == "school")
                {
                    if (in_array($value, $School)){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "department")
                {
                    if (in_array($value, $Department)){
                        return "Metadata Matches";
                    }
                }
                elseif( $name == "unique-news")
                {
                    if (in_array($value, $UniqueNews)){
                        return "Metadata Matches";
                    }
                }
            }
        }
        return "No";
    }


    // Create the Featured Articles.
    function create_featured_articles_array(){
        $featuredArticles = array();

        global $featuredArticleOptions;

        foreach( $featuredArticleOptions as $key=>$options ){
            if( $options[3] != "null" && $options[3] != ""){
                array_push($featuredArticles, $options[3]);
            }
        }
        return $featuredArticles;
    }

    // Returns the featured Article html.
    function get_featured_article_html($page_info, $xml, $options){
        $ds = $xml->{'system-data-structure'};
        $image = $ds->{'media'}->{'image'}->{'path'};
        $date = $ds->{'publish-date'};

        // Only display it if it has an image.
        if( $image != "" && $image != "/")
        {
            $html = '<div class="mt1 mb2 pa1" style="background: #f4f4f4">';
            $html .= '<div class="grid left false">';
            $html .= '<div class="grid-cell  u-medium-1-2">';
            $html .= '<div class="medium-grid-pad-1x">';
            $html .= '<img src="//cdn1.bethel.edu/resize/unsafe/400x0/smart/http://staging.bethel.edu'.$image.'" class="image-replace" alt="'.$page_info['title'].'" data-src="//cdn1.bethel.edu/resize/unsafe/{width}x0/smart/http://staging.bethel.edu'.$image.'" width="400">';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="grid-cell  u-medium-1-2">';
            $html .= '<div class="medium-grid-pad-1x">';
            if( $page_info['title'] != "")
                $html .= '<h2 class="h5"><a href="staging.bethel.edu'.$xml->path.'">'.$page_info['title'].'</a></h2>';

            if( $date != "" && $date != "null" )
            {
                $formattedDate = format_featured_date_news_article($date);
                $html .= "<p>".$formattedDate."</p>";
            }

            if( $options[1] != "" )
                $html .= '<p>'.$options[1].'</p>';
            elseif( $page_info['description'] != "")
                $html .= '<p>'.$page_info['description'].'</p>';

            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

        }
        else
            return "null";

        return $html;
    }

    // Returns a formatted version of the date.
    function format_featured_date_news_article( $date)
    {
        $date = $date/1000;
        $formattedDate = date("F d, Y | g:i a", $date);

        // Change am/pm to a.m./p.m.
        $formattedDate = str_replace("am", "a.m.", $formattedDate);
        $formattedDate = str_replace("pm", "p.m.", $formattedDate);

        // format 7:00 to 7
        $formattedDate = str_replace(":00", "", $formattedDate);
        return $formattedDate;
    }

    // Sort the array of articles, newest first.
    function sort_news_articles( $articles ){
        function cmpi($a, $b)
        {
            return strcmp($b["date"], $a["date"]);
        }
        usort($articles, 'cmpi');

        return $articles;
    }
?>