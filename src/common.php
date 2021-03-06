<?php

function phphoto_text($db, $category, $name) {
    $language = GALLERY_LANGUAGE;

    $argv = func_get_args();

    array_shift($argv); // $db
    array_shift($argv); // $category
    array_shift($argv); // $name

    $sql = sprintf("SELECT text, parameters FROM texts WHERE language_id = '%s' AND category = '%s' AND name = '%s';",
            mysql_real_escape_string($language),
            mysql_real_escape_string($category),
            mysql_real_escape_string($name));
    $result = phphoto_db_query($db, $sql);

    if (count($result) != 1)
        return "@$category:$name@";
    
    $text = $result[0];
    if ($text['parameters'] != count($argv))
        return "@$category:$name:$text[parameters]@";

    return call_user_func_array('sprintf', array_merge((array) $result[0]['text'], $argv));
}

/*
 * Strips HTML tags and makes sure the string is not too long
 */
function format_string($string, $max_length = 0) {
    if ($max_length && strlen($string) > $max_length) {
        $string = substr($string, 0, $max_length).'..';
    }
    $string = strip_tags($string, '<br>');
    return (strlen($string) > 0) ? $string : '&nbsp;';
}

function format_bool($bool) {
    return ($bool) ? "<img src='./icons/check.png'>" : '&nbsp;';
}

/*
 * Nice printout for the given bytes
 */
function format_byte($bytes) {
    $bounds = array(
        array('TB', pow(1024, 4)),
        array('GB', pow(1024, 3)),
        array('MB', pow(1024, 2)),
        array('kB', pow(1024, 1)),
        array('bytes', pow(1024, 0))
    );

    for ($i = count($bounds) - 1; $i > 0; $i--) {
        if ($bytes <= $bounds[$i-1][1]) {
            return sprintf('%.0f %s (%.2f %s)', $bytes/$bounds[$i][1], $bounds[$i][0], $bytes/$bounds[$i-1][1], $bounds[$i-1][0]);
        }
    }
    return sprintf('%.0f %s', $bytes/$bounds[$i][1], $bounds[$i][0]);
}

/*
 * Nice printout for the given date-time
 */
function format_date_time($string) {
    return date(DATE_FORMAT, strtotime($string));
}

/*
 * Nice printout of the camera model based on the given exif data
 */
function format_camera_model($exif) {
    if (!is_array($exif))
        return 'Invalid exif array';

    $summary = array();
    if (isset($exif['Make'])) {
        array_push($summary, sprintf('%s,', $exif['Make']));
    }
    if (isset($exif['Model'])) {
        array_push($summary, sprintf('%s', $exif['Model']));
    }
    //~ if (isset($exif['FirmwareVersion'])) {
        //~ array_push($summary, sprintf(': %s', $exif['FirmwareVersion']));
    //~ }
    if (isset($exif['CropFactor'])) {
        array_push($summary, sprintf(' (%.1f crop factor)', $exif['CropFactor']));
    }

    return (count($summary) > 0) ? implode('&nbsp;', $summary) : null;
}

/*
 * Nice printout of the camera settings based on the given exif data
 */
function format_camera_settings($exif) {
    if (!is_array($exif))
        return 'Invalid exif array';
/*
EXIF flash values (http://www.colorpilot.com/exif_tags.html)
0 	No Flash
1 	Fired
5 	Fired, Return not detected
7 	Fired, Return detected
9 	On
13 	On, Return not detected
15 	On, Return detected
16 	Off
24 	Auto, Did not fire
25 	Auto, Fired
29 	Auto, Fired, Return not detected
31 	Auto, Fired, Return detected
32 	No flash function
65 	Fired, Red-eye reduction
69 	Fired, Red-eye reduction, Return not detected
71 	Fired, Red-eye reduction, Return detected
73 	On, Red-eye reduction
77 	On, Red-eye reduction, Return not detected
79 	On, Red-eye reduction, Return detected
89 	Auto, Fired, Red-eye reduction
93 	Auto, Fired, Red-eye reduction, Return not detected
95 	Auto, Fired, Red-eye reduction, Return detected
*/

    $summary = array();
    if (isset($exif['ExposureTime'])) {
        array_push($summary, sprintf('%ss', $exif['ExposureTime']));
    }
    if (isset($exif['FNumber'])) {
        eval('$aperture = ' . $exif['FNumber'] . ';');
        array_push($summary, sprintf('f/%.1f', $aperture));
    }
    if (isset($exif['FocalLength'])) {
        eval('$focal_length = ' . $exif['FocalLength'] . ';');
        if (isset($exif['CropFactor'])) {
            // calculate real focal length (with the field of view factor)
            $focal_length *= $exif['CropFactor'];
        }
        array_push($summary, sprintf('%.0fmm%s', $focal_length, (isset($exif['CropFactor'])) ? '*' : ''));
    }
    if (isset($exif['ISOSpeedRatings'])) {
        array_push($summary, sprintf('%s', $exif['ISOSpeedRatings']));
    }
    if (isset($exif['Flash'])) {
        if (((int)$exif['Flash'] % 2) == 1) {
            array_push($summary, sprintf('%s', "<img src='./icons/flash.png'>"));
        }
    }

    return (count($summary) > 0) ? implode('&nbsp;&nbsp;&nbsp;', $summary) : null;
}

/*
 * Returns formatted aspect ratio for the width and height
 */
function phphoto_image_aspect_ratio($width, $height) {
    $lcd = 1;
    for ($i = 2; $i < ($width/2) && $i < ($height/2); $i++) {
        if ($width % $i == 0 && $height % $i == 0)
            $lcd = $i;
    }
    return $width/$lcd.':'.$height/$lcd;
}

/*
 * Filters the given exif data based upon the given keys in config.php
 */
function phphoto_filter_exif_data($exif) {
    $parsed_exif = array();
    foreach (explode(',', IMAGE_EXIF_KEYS) as $key) {
        $key = trim($key);
        if (isset($exif[$key])) {
            $parsed_exif[$key] = trim($exif[$key]);
        }
        elseif (isset($exif['COMPUTED'][$key])) {
            $parsed_exif[$key] = trim($exif['COMPUTED'][$key]);
        }
    }
    return $parsed_exif;
}

////////////////////////////////////////////////////////////////////////////////
//   HTML GENERATORS
////////////////////////////////////////////////////////////////////////////////

/*
 * HTML table generator
 */
function phphoto_to_html_table($body, $header = array(), $footer = array()) {
    echo "\n<table>";
    if (count($header) > 0) {
        echo "\n    <thead>";
        echo "\n        <tr><th>".implode('</th><th>', $header)."</th></tr>";
        echo "\n    </thead>";
    }
    if (count($footer) > 0 && count($footer) <= count($header)) {
        echo "\n    <tfoot>";
        echo "\n        <tr><td colspan='".(count($header)-count($footer)+1)."'>".implode('</td><td>', $footer)."</td></tr>";
        echo "\n    </tfoot>";
    }
    echo "\n    <tbody>";
    for ($i = 0; $i < count($body); $i++) {
        echo "\n        <tr class='row".($i % 2)."'><td>".implode('</td><td>', $body[$i])."</td></tr>";
    }
    echo "\n    <tbody>";
    echo "\n</table>";
}

/*
 * Popup generator, returns a div-tag
 */
function phphoto_popup_message($message, $type) {
    switch ($type) {
        case 'error':
            echo "\n<div class='message' id='error'><img src='./icons/dialog-error.png'>$message</div>";
            break;
        case 'warning':
            echo "\n<div class='message' id='warning'><img src='./icons/dialog-warning.png'>$message</div>";
            break;
        case 'info':
            echo "\n<div class='message' id='info'><img src='./icons/dialog-information.png'>$message</div>";
            break;
        default:
            echo "\n<div class='message'>Unknown message type ($type): $message</div>";
            break;
    }
}

/*
 * Returns a string with previous/next links depending on current page
 */
function phphoto_page_numbering($db, $page_number, $pages, $url_previous, $url_next) {
    $string = '';
    if ($page_number > 0)
        $string .= "<a href='$url_previous'><img src='./icons/go-previous.png' /></a>";
    else
        $string .= "<img src='./icons/go-previous-inactive.png' />";
    $string .= '&nbsp;'.phphoto_text($db, 'common', 'page_number', $page_number + 1, $pages).'&nbsp;';
    if ($page_number < ($pages - 1))
        $string .= "<a href='$url_next'><img src='./icons/go-next.png' /></a>";
    else
        $string .= "<img src='./icons/go-next-inactive.png' />";
    return $string;
}

////////////////////////////////////////////////////////////////////////////////
//   SQL GENERATORS
////////////////////////////////////////////////////////////////////////////////

/*
 * TBD
 */
function phphoto_sql_exif_values($key) {
    return "
        SELECT
            SUBSTRING(
                exif, 
                LOCATE('$key', exif)+".strlen($key)."+6, 
                LOCATE('\'', SUBSTRING(exif, LOCATE('$key', exif)+".strlen($key)."+6))-1
            ) AS ExifValue
        FROM
            images
        GROUP BY 1
    ";
}

/*
 * TBD
 */
function phphoto_sql_exif_images($key, $value = null) {
    if ($value == null) {
        // Return all images where $key does not exist in EXIF data
        return "
            SELECT
                id
            FROM
                images
            WHERE
                LOCATE('\'$key\' => \'', exif) = 0
        ";
    }
    else {
        return "
            SELECT
                id
            FROM
                images
            WHERE
                LOCATE('\'$key\' => \'$value\'', exif) > 0
        ";
    }
}

?>