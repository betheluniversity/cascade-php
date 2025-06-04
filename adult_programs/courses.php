<?php
/**
 * Created by PhpStorm.
 * User: ejc84332
 * Date: 4/27/15
 * Time: 8:42 AM
 */

 include_once $_SERVER["DOCUMENT_ROOT"] . "/code/program-search/php/program-search-functions.php";

// Select wsapi.xp for Staging site
if( strpos($_SERVER['SERVER_NAME'],'staging')  !== false ) {
    $wsapi_url = "https://wsapi.xp.bethel.edu";
    //$wsapi_url = "https://ngrok_url"; // Set a temporary testing URL
} else {
    $wsapi_url = "https://wsapi.bethel.edu";
}

// this is the cached version
function course_catalog_call($code, $values){
    global $wsapi_url;
    try {
        $data = array('options' => $values);
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $url = $wsapi_url . "/courses/course-catalog/$code";
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    } catch(Exception $e) {
        return '';
    }
}

// this is the cached version
function individual_courses_call($code){
    global $wsapi_url;
    try {
        $url = $wsapi_url . "/courses/open-enrollment-courses/$code";
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

function program_courses($code) {
    global $wsapi_url;
    try {
        $url = $wsapi_url . "/courses/program-courses/$code";
        return file_get_contents($url, false);
    } catch(Exception $e) {
        return '';
    }
}

function random_courses($path) {
    // Extract the program code from the path
    $path = str_replace("_testing/", "", $path);
    $path = "/" . $path;
    $programs_xml = get_program_xml();
    
    foreach ($programs_xml as $index => $program) {
        foreach ($program["concentrations"] as $L2_index => $concentration) {
            if (strlen($concentration["catalog_url"]) > 0) {
                if (strcmp($path, $concentration["concentration_page"]->{"path"}) == 0 || strcmp($path . 'index', $concentration["concentration_page"]->{"path"}) == 0) {
                    echo '<p>Program Code: ' . $program["program_name"] . '</p>';
                    echo get_random_courses($program["program_code"]);
                    return;
                }
            }
        }
    }
    echo 'Program code not found for path: ' . $path;
    return;
}

function get_random_courses($code) {
    try {
        $content = autoCache('program_courses', array($code, 86400));
        $content = json_decode($content, true);

        if (!empty($content) && is_array($content)) {
            // Extract course numbers into a flat array
            $courseNumbers = array_column($content, 'crs_num');

            // Select up to 5 random courses
            $selectedCourseNums = array_rand(array_flip($courseNumbers), min(5, count($courseNumbers)));
            // Randomize the order of selected course numbers
            shuffle($selectedCourseNums);
            // Get the full course details for the selected course numbers
            $selectedCourses = [];
            foreach ($selectedCourseNums as $courseNum) {
                foreach ($content as $course) {
                    // Convert $courseNum to string for comparison
                    if ($course['crs_num'] === (string)$courseNum) {
                        $selectedCourses[] = $course;
                        break; // Stop searching once we find the course
                    }
                }
            }
            // Render the selected courses using Twig
            $twig = makeTwigEnviron('/code/adult_programs/twig/');
            return $twig->render('random_courses.html', array(
                'courses' => $selectedCourses)
            );
        } else {
            return '';
        }
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
                echo "<h3>Courses</h3>";
                echo $content['data'];
            }
        } else {
            // Data from peso-de sorted by year and semester
            // Displayed in order of course start
            echo "<h3>Courses</h3>";
            foreach($content as $year => $terms) {
                foreach($terms as $semester => $courses) {
                    if ($semester == "Interim") {
                        echo "<h4>".$semester." ".$year."</h4>";
                        echo $courses;
                    }
                }
                foreach($terms as $semester => $courses) {
                    if ($semester == "Spring") {
                        echo "<h4>".$semester." ".$year."</h4>";
                        echo $courses;
                    }
                }
                foreach($terms as $semester => $courses) {
                    if ($semester == "Fall") {
                        echo "<h4>".$semester." ".$year."</h4>";
                        echo $courses;
                    }
                }
            }
        }
    } catch(Exception $e) {
        return '';
    }
}
