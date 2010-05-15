<?php

require_once('./config.php');
require_once('./database.php');
require_once('./admin.php');
require_once('./gallery.php');

$allowed_filetypes = array('jpg','jpeg',);

function phphoto_main() {
    $db = phphoto_db_connect();
    $admin = (isset($_GET[GET_KEY_ADMIN_QUERY])) ? $_GET[GET_KEY_ADMIN_QUERY] : '';
    if (strlen($admin) > 0)
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
        case GET_VALUE_ADMIN_IMAGE:
            $image_id = (isset($_GET[GET_KEY_IMAGE_ID])) ? $_GET[GET_KEY_IMAGE_ID] : INVALID_ID;
            if (is_numeric($image_id) && $image_id != INVALID_ID)
                phphoto_echo_admin_image($db, $image_id);
            else
                phphoto_echo_admin_images($db);
            break;
        default:
            die("not a valid admin page: $admin");
    }
}

function phphoto_admin_links($additional_items = array()) {
    echo "\n<ul>";
    echo "\n    <li><a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>Galleries</a></li>";
    echo "\n    <li><a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>Images</a></li>";
    foreach ($additional_items as $name=>$url)
        echo "\n    <li><a href='$url'>$name</a></li>";
    echo "\n</ul>";
}

////////////////////////////////////////////////////////////////////////////////
//   GENERATORS
////////////////////////////////////////////////////////////////////////////////

function format_byte($bytes) {
    $bounds = array(
        array("GB", 1024 * 1024 * 1024),
        array("MB", 1024 * 1024),
        array("kB", 1024),
        array("bytes", 1)
    );
    
    for ($i = 0; $i < count($bounds); $i++) {
        if ($bytes >= $bounds[$i][1]) {
            if ($i == count($bounds)-1)
                return sprintf("%d %s", $bytes/$bounds[$i][1], $bounds[$i][0]);
            else
                return sprintf("%d %s (%d %s)", $bytes/$bounds[$i+1][1], $bounds[$i+1][0], $bytes/$bounds[$i][1], $bounds[$i][0]);
        }
    }
    return $bytes; // should not come here
}

// Returns a date-time string with proper formatting
function format_date_time($string) {
    return date(DATE_FORMAT, strtotime($string));
}

// Returns formatted aspect ratio for the width and height
function aspect_ratio($width, $height) {
    $lcd = 1;
    for ($i = 2; $i < ($width/2) && $i < ($height/2); $i++) {
        if ($width % $i == 0 && $height % $i == 0)
            $lcd = $i;
    }
    return $width/$lcd.":".$height/$lcd;
}

// Adds the image to the database and returns the image ID
function store_image($db, $uploaded_image){
    // Validate extension and filesiz
    $filename = $uploaded_image['name'];
    $image = $uploaded_image['tmp_name'];

    // Get image data
    $fileSize = filesize($image);
    $imageInfo = getimagesize($image);
    $width =  $imageInfo[0];
    $height =  $imageInfo[1];
    $type =  $imageInfo[2];

    $imageData = generate_image_data($image);
    $thumbnailData = generate_image_data($image, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);

    // Check if exists
    $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM images WHERE filename = '$filename';");
    if ($result[0]['exist'] > 0) {
        return -2;
    }

    // Insert into database
    $sql = "INSERT INTO images
            (original, thumbnail, type, width, height, filesize, filename, title, description, created)
            VALUES
            ('$imageData', '$thumbnailData', $type, $width, $height, $fileSize, '$filename', '', '', NOW());";
    $result = phphoto_db_query($db, $sql);

    return ($result) ? mysql_insert_id($db) : INVALID_ID;
}

function regenerate_thumbnails($db) {
    $regenerated_thumbnails = 0;
    $sql = "SELECT id, original FROM images";
    foreach (phphoto_db_query($db, $sql) as $image) {
        $temp_resource = imagecreatefromstring($image['original']);
        if (!imagejpeg($temp_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die("Could not create new jpeg image");

        $thumbnail = generate_image_data(IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);
        $sql = "UPDATE images SET thumbnail = '$thumbnail' WHERE id = $image[id]";
        $regenerated_thumbnails += phphoto_db_query($db, $sql);
    }
    return $regenerated_thumbnails;
}

// Generates image data as a byte[]
function generate_image_data($image, $max_width = null, $max_height = null, $panel_color = "#000000") {
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
                die("Could not create image from gif");
            break;
        case 2: // JPEG
            if (!$image_resource = ImageCreateFromJpeg($image))
                die("Could not create image from jpeg");
            break;
        case 3: // PNG
            if (!$image_resource = ImageCreateFromPng($image))
                die("Could not create image from png");
            break;
        default:
            die("Unrecognized image type");
    }

    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor($canvas_width, $canvas_height))
        die("Failed to create destination image");

    // set canvas background color
    $panel_color = str_replace("#", "", $panel_color);
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
        die("Could not copy resampled image");

    // write canvas to file
    switch ($image_type) {
        case 1: // GIF
            if (!imagegif($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die("Could not create new gif image");
            break;
        case 2: // JPEG
            if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die("Could not create new jpeg image");
            break;
        case 3: // PNG
            if (!imagepng($canvas_resource, IMAGE_TEMP_FILE))
                die("Could not create new png image");
            break;
        default:
            die("Unrecognized image type");
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

?>