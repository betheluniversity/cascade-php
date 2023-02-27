<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 4/27/15
 * Time: 8:42 AM
 */


// this is the cached version
function course_catalog_call($code, $values){
    try {
        $data = array('options' => $values);
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $url = "https://wsapi.bethel.edu/courses/course-catalog/$code";
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    } catch(Exception $e) {
        return '';
    }
}

// this is the cached version
function individual_courses_call($code){
    try {
        $url = "https://wsapi.bethel.edu/courses/open-enrollment-courses/$code";
        return file_get_contents($url, false);
    } catch(Exception $e) {
        return '';
    }
}

function individual_courses($code){
    try {
        $content = autoCache('individual_courses_call', array($code, 86400));
        return $content;
    } catch(Exception $e) {
        return '';
    }
}

function load_course_catalog ($values, $code) {
    try {
        $content = autoCache('course_catalog_call', array($code, $values, 86400));
        $content = json_decode($content, true);

        if (array_key_exists('data', $content)) {
            // Data from program code
            if( strpos($content['data'], '<li') !== false ) {
                echo "<h2>Courses</h2>";
                echo $content['data'];
            }
        } else {
            // Data from peso-de sorted by year and semester
            // Displayed in order of course start
            echo "<h2>Courses</h2>";
            foreach($content as $year => $terms) {
                foreach($terms as $semester => $courses) {
                    if ($semester == "Interim") {
                        echo "<h3>".$semester." ".$year."</h3>";
                        echo $courses;
                    }
                }
                foreach($terms as $semester => $courses) {
                    if ($semester == "Spring") {
                        echo "<h3>".$semester." ".$year."</h3>";
                        echo $courses;
                    }
                }
                foreach($terms as $semester => $courses) {
                    if ($semester == "Fall") {
                        echo "<h3>".$semester." ".$year."</h3>";
                        echo $courses;
                    }
                }
            }
        }
    } catch(Exception $e) {
        return '';
    }
}
