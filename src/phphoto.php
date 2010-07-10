<?php

session_start();

require_once('./config.php');
require_once('./database.php');
require_once('./admin.php');
require_once('./gallery.php');

$allowed_filetypes = array('jpg','jpeg','png');

function phphoto_main($authorized = false) {
    $db = phphoto_db_connect();
    $admin = (isset($_GET[GET_KEY_ADMIN_QUERY])) ? $_GET[GET_KEY_ADMIN_QUERY] : '';
    if ($authorized && strlen($admin) > 0)
        phphoto_admin($db, $admin);
    else
        phphoto_gallery($db);
    phphoto_db_disconnect($db);
}

function phphoto_gallery($db) {
    $gallery_id = (isset($_GET[GET_KEY_GALLERY_ID])) ? $_GET[GET_KEY_GALLERY_ID] : INVALID_ID;
    if (is_numeric($gallery_id) && $gallery_id != INVALID_ID)
        phphoto_echo_gallery($db, $gallery_id);
    else
        phphoto_echo_galleries($db);
}

function phphoto_admin($db, $admin) {
    switch ($admin) {
        case GET_VALUE_ADMIN_GALLERY:
            $gallery_id = (isset($_GET[GET_KEY_GALLERY_ID])) ? $_GET[GET_KEY_GALLERY_ID] : INVALID_ID;
            if (is_numeric($gallery_id) && $gallery_id != INVALID_ID)
                phphoto_echo_admin_gallery($db, $gallery_id);
            else
                phphoto_echo_admin_galleries($db);
            break;
        case GET_VALUE_ADMIN_TAG:
            $tag_id = (isset($_GET[GET_KEY_TAG_ID])) ? $_GET[GET_KEY_TAG_ID] : INVALID_ID;
            if (is_numeric($tag_id) && $tag_id != INVALID_ID)
                phphoto_echo_admin_tag($db, $tag_id);
            else
                phphoto_echo_admin_tags($db);
            break;
        case GET_VALUE_ADMIN_IMAGE:
            $image_id = (isset($_GET[GET_KEY_IMAGE_ID])) ? $_GET[GET_KEY_IMAGE_ID] : INVALID_ID;
            if (is_numeric($image_id) && $image_id != INVALID_ID)
                phphoto_echo_admin_image($db, $image_id);
            else
                phphoto_echo_admin_images($db);
            break;
        default:
            phphoto_echo_admin_default($db);;
    }
}

function phphoto_admin_links($additional_items = array()) {
    echo "\n<ul>";
    echo "\n    <li><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_DEFAULT."'>Admin</a></li>";
    echo "\n    <li><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY."'>Galleries</a></li>";
    echo "\n    <li><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG."'>Tags</a></li>";
    echo "\n    <li><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE."'>Images</a></li>";
    foreach ($additional_items as $name=>$url)
        echo "\n    <li><a href='$url'>$name</a></li>";
    echo "\n</ul>";
}

////////////////////////////////////////////////////////////////////////////////
//   GENERATORS
////////////////////////////////////////////////////////////////////////////////

function format_byte($bytes) {
    $bounds = array(
        array('TB', pow(1024, 4)),
        array('GB', pow(1024, 3)),
        array('MB', pow(1024, 2)),
        array('kB', pow(1024, 1)),
        array('bytes', pow(1024, 0))
    );

    for ($i = 0; $i < count($bounds); $i++) {
        if ($bytes >= $bounds[$i+1][1]) {
            if ($i == count($bounds)-1)
                return sprintf('%d %s', $bytes/$bounds[$i][1], $bounds[$i][0]);
            else
                return sprintf('%d %s (%.1f %s)', $bytes/$bounds[$i+1][1], $bounds[$i+1][0], $bytes/$bounds[$i][1], $bounds[$i][0]);
        }
    }
    return $bytes; // should not come here
}

// Returns a date-time string with proper formatting
function format_date_time($string) {
    return date(DATE_FORMAT, strtotime($string));
}

// Returns a well formatted string of the given exif array
function format_camera_model($exif) {
    if (!is_array($exif))
        return 'Invalid exif array';

    $summary = array();
    /*if (isset($exif['Make'])) {
        array_push($summary, sprintf('%s', $exif['Make']));
    }*/
    if (isset($exif['Model'])) {
        array_push($summary, sprintf('%s', $exif['Model']));
    }
    if (isset($exif['FirmwareVersion'])) {
        array_push($summary, sprintf(': %s', $exif['FirmwareVersion']));
    }
    if (isset($exif['CCDWidth'])) {
        array_push($summary, sprintf('(CCD %s)', $exif['CCDWidth']));
    }

    return (count($summary) > 0) ? implode('&nbsp;', $summary) : null;
}

// Returns a well formatted string of the given exif array
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
            // calculate real focal length
            $fieldOfViewCrop = $exif['CropFactor'];
            $focal_length *= $fieldOfViewCrop;
        }
        array_push($summary, sprintf('%.0fmm%s', $focal_length, (isset($exif['CCDWidth'])) ? '*':''));
    }
    if (isset($exif['ISOSpeedRatings'])) {
        array_push($summary, sprintf('%s', $exif['ISOSpeedRatings']));
    }
    if (isset($exif['Flash'])) {
        if (((int)$exif['Flash'] % 2) == 1) {
            array_push($summary, sprintf('%s', "<img src='./icons/flash.png'>"));
        }
    }

    return (count($summary) > 0) ? implode('&nbsp;&nbsp;&nbsp;&nbsp;', $summary) : null;
}

// Returns formatted aspect ratio for the width and height
function aspect_ratio($width, $height) {
    $lcd = 1;
    for ($i = 2; $i < ($width/2) && $i < ($height/2); $i++) {
        if ($width % $i == 0 && $height % $i == 0)
            $lcd = $i;
    }
    return $width/$lcd.':'.$height/$lcd;
}

function parse_exif_data($exif) {
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

// Adds the image to the database and returns the image ID
function store_image($db, $uploaded_image, $replace_existing = false){
    // Validate extension and filesiz
    $image_filename = $uploaded_image['name'];
    $image = $uploaded_image['tmp_name'];

    // Get image data
    $image_filesize = filesize($image);
    $image_info = getimagesize($image);
    $image_width =  $image_info[0];
    $image_height =  $image_info[1];
    $image_type =  $image_info[2];
    // Read exif data
    $exif_temp = exif_read_data($image);
    $exif = parse_exif_data($exif_temp);
    $image_exif = addslashes(var_export($exif, true));
    //~ die('<pre>'.$image_exif.'\n\n'.print_r($exif, true).'\n\n'.print_r($exif_temp, true).'</pre>');

    // Generate image data
    $image_data = generate_image_data($image);
    $image_thumbnail = generate_image_data($image, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);

    // Check if exists
    $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM images WHERE filename = '$image_filename';");
    $image_exists = ($result[0]['exist'] == 1);
    if (!$replace_existing && $image_exists) {
        return -2;
    }

    if ($image_exists) {
        // Update existing
        $sql = "
            UPDATE images SET
                data = '$image_data',
                thumbnail = '$image_thumbnail',
                type = $image_type,
                width = $image_width,
                height = $image_height,
                filesize = $image_filesize,
                exif = '$image_exif'
            WHERE
                filename = '$image_filename'
        ";
    }
    else {
        // Insert new
        $sql = "
            INSERT INTO images (
                data,
                thumbnail,
                type,
                width,
                height,
                filesize,
                filename,
                exif,
                title,
                description,
                created
            )
            VALUES (
                '$image_data',
                '$image_thumbnail',
                $image_type,
                $image_width,
                $image_height,
                $image_filesize,
                '$image_filename',
                '$image_exif',
                '',
                '',
                NOW()
            )
        ";
    }

    $result = phphoto_db_query($db, $sql);

    return ($result) ? mysql_insert_id($db) : INVALID_ID;
}

function regenerate_gallery_thumbnail($db, $gallery_id) {
    $sql = "
            SELECT
                data,
                width,
                height
            FROM
                images
            WHERE
                id IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)
            ORDER BY
                RAND()
            LIMIT ".GALLERY_THUMBNAIL_MAXIMUM_IMAGES."
    ";
    $images = phphoto_db_query($db, $sql);
    $thumbnail = generate_gallery_data($images);
    $sql = "UPDATE galleries SET thumbnail = '$thumbnail' WHERE id = $gallery_id";
    return (phphoto_db_query($db, $sql) == 1);
}

// Generates gallery thumbnail data as a byte[]
function generate_gallery_data($images) {
    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor(GALLERY_THUMBNAIL_WIDTH, GALLERY_THUMBNAIL_HEIGHT))
        die('Failed to create destination image');
    
    // set canvas background color
    $panel_color = str_replace('#', '', GALLERY_THUMBNAIL_PANEL_COLOR);
    if (strlen($panel_color) != 6)
        die("Panel color is not properly formatted: #$panel_color");
    $canvas_r = hexdec(substr($panel_color, 0, 2));
    $canvas_g = hexdec(substr($panel_color, 2, 2));
    $canvas_b = hexdec(substr($panel_color, 4, 2));
    $canvas_bg = imagecolorallocate($canvas_resource, $canvas_r, $canvas_g, $canvas_b);
    imagefill($canvas_resource, 0, 0, $canvas_bg);

    // draw image thumbnails on canvas
    if (count($images) < GALLERY_THUMBNAIL_MINIMUM_IMAGES)
        $size = ceil(sqrt(GALLERY_THUMBNAIL_MINIMUM_IMAGES));
    elseif (count($images) > GALLERY_THUMBNAIL_MAXIMUM_IMAGES)
        $size = ceil(sqrt(GALLERY_THUMBNAIL_MAXIMUM_IMAGES));
    else
        $size = floor(sqrt(count($images)));

    $image_width = GALLERY_THUMBNAIL_WIDTH / $size;
    $image_height = GALLERY_THUMBNAIL_HEIGHT / $size;

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $index = $x + ($y * $size);
            if (isset($images[$index])) {
                $image_resource = imagecreatefromstring($images[$index]['data']);
                if (!ImageCopyResampled($canvas_resource, $image_resource, $x * $image_width, $y * $image_height,
                        0, 0, $image_width, $image_height, $images[$index]['width'], $images[$index]['height']))
                    die('Could not copy resampled image');
                imagedestroy($image_resource);
            }
        }
    }

    // write canvas to file
    if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
        die('Could not create new jpeg image');

    imagedestroy($canvas_resource);

    return addslashes(file_get_contents(IMAGE_TEMP_FILE));
}

function regenerate_image_thumbnails($db) {
    $regenerated_thumbnails = 0;
    $sql = "SELECT id, data FROM images";
    foreach (phphoto_db_query($db, $sql) as $image) {
        $temp_resource = imagecreatefromstring($image['data']);
        if (!imagejpeg($temp_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new jpeg image');

        $thumbnail = generate_image_data(IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);
        $sql = "UPDATE images SET thumbnail = '$thumbnail' WHERE id = $image[id]";
        $regenerated_thumbnails += phphoto_db_query($db, $sql);
    }
    return $regenerated_thumbnails;
}


// Generates image data as a byte[]
function generate_image_data($image, $max_width = null, $max_height = null, $panel_color = '#000000') {
    if ($max_width == null && $max_height == null) {
        // keep original image
        return addslashes(file_get_contents($image));
    }

    // parse image data
    $image_info = getimagesize($image);
    $image_width =  $image_info[0];
    $image_height =  $image_info[1];
    $image_aspect = $image_width / $image_height;
    $image_type =  $image_info[2];

    // calculate sizes
    $canvas_width = ($max_width == null) ? $image_width : (($image_width < $max_width) ? $image_width : $max_width);
    $canvas_height = ($max_height == null) ? $image_height : (($image_height < $max_height) ? $image_height : $max_height);
    $canvas_aspect = $canvas_width / $canvas_height;

    // calculate image offset
    $image_scaled_width = $canvas_width;
    $image_scaled_height = $canvas_height;
    $image_delta_x = 0;
    $image_delta_y = 0;
    if ($image_width > $image_height) { // landscape
        $image_scaled_height = ceil($image_scaled_width / $image_aspect);
        $image_delta_y = round(($canvas_height - $image_scaled_height) / 2);
    }
    else { // portrait
        $image_scaled_width = ceil($image_scaled_height * $image_aspect);
        $image_delta_x = round(($canvas_width - $image_scaled_width) / 2);
    }
    
    /*die("image_width: $image_width   image_height: $image_height   image_aspect: $image_aspect<br>" .
        "canvas_width: $canvas_width   canvas_height: $canvas_height   canvas_aspect: $canvas_aspect<br>" .
        "image_scaled_width: $image_scaled_width   image_scaled_height: $image_scaled_height<br>" .
        "image_delta_x: $image_delta_x   image_delta_y: $image_delta_y");*/

    // Read image
    switch ($image_type) {
        case 1: // GIF
            if (!$image_resource = ImageCreateFromGif($image))
                die('Could not create image from gif');
            break;
        case 2: // JPEG
            if (!$image_resource = ImageCreateFromJpeg($image))
                die('Could not create image from jpeg');
            break;
        case 3: // PNG
            if (!$image_resource = ImageCreateFromPng($image))
                die('Could not create image from png');
            break;
        default:
            die('Unrecognized image type');
    }

    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor($canvas_width, $canvas_height))
        die('Failed to create destination image');

    // set canvas background color
    $panel_color = str_replace('#', '', $panel_color);
    if (strlen($panel_color) != 6)
        die("Panel color is not properly formatted: #$panel_color");
    $canvas_r = hexdec(substr($panel_color, 0, 2));
    $canvas_g = hexdec(substr($panel_color, 2, 2));
    $canvas_b = hexdec(substr($panel_color, 4, 2));
    $canvas_bg = imagecolorallocate($canvas_resource, $canvas_r, $canvas_g, $canvas_b);
    imagefill($canvas_resource, 0, 0, $canvas_bg);

    // resize and fit the image on the canvas
    if (!ImageCopyResampled($canvas_resource, $image_resource, $image_delta_x, $image_delta_y,
            0, 0, $image_scaled_width, $image_scaled_height, $image_width, $image_height))
        die('Could not copy resampled image');

    // write canvas to file
    switch ($image_type) {
        case 1: // GIF
            if (!imagegif($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new gif image');
            break;
        case 2: // JPEG
            if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new jpeg image');
            break;
        case 3: // PNG
            if (!imagepng($canvas_resource, IMAGE_TEMP_FILE))
                die('Could not create new png image');
            break;
        default:
            die('Unrecognized image type');
    }

    imagedestroy($image_resource);
    imagedestroy($canvas_resource);

    return addslashes(file_get_contents(IMAGE_TEMP_FILE));
}

////////////////////////////////////////////////////////////////////////////////
//   HTML GENERATORS
////////////////////////////////////////////////////////////////////////////////

function phphoto_to_html_table($header, $tuples) {
    echo "\n<table>";
    if ($header) {
        echo "\n    <tr class='header'><td>".implode('</td><td>', $header)."</td></tr>";
    }
    for ($i = 0; $i < count($tuples); $i++) {
        echo "\n    <tr class='data".($i % 2)."'><td>".implode('</td><td>', $tuples[$i])."</td></tr>";
    }
    echo "\n</table>";
}

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
            echo "\n<div class='message'>Unknown message ($type): $message</div>";
            break;
    }
}

?>