<?php

require_once('./config.php');
require_once('./database.php');
require_once('./admin.php');
require_once('./gallery.php');

$allowed_filetypes = array('jpg','jpeg','png');

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
    $image_filename = $uploaded_image['name'];
    $image = $uploaded_image['tmp_name'];

    // Get image data
    $image_filesize = filesize($image);
    $image_info = getimagesize($image);
    $image_width =  $image_info[0];
    $image_height =  $image_info[1];
    $image_type =  $image_info[2];
    // Read exif data
    $exif = exif_read_data($image);
    $image_taken = (isset($exif['FileDateTime'])) ? "'".date("Y-m-d H:i:s", trim($exif['FileDateTime']))."'" : "NULL";
    $image_model = (isset($exif['Model'])) ? "'".trim($exif['Model'])."'" : "NULL";
    $image_exposure = (isset($exif['ExposureTime'])) ? "'".trim($exif['ExposureTime'])."'" : "NULL";
    $image_iso = (isset($exif['ISOSpeedRatings'])) ? "'".trim($exif['ISOSpeedRatings'])."'" : "NULL";
    $image_aperture = (isset($exif['COMPUTED']['ApertureFNumber'])) ? "'".trim($exif['COMPUTED']['ApertureFNumber'])."'" : "NULL";
    // Generate image data
    $image_data = generate_image_data($image);
    $image_thumbnail = generate_image_data($image, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);

    // Check if exists
    $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM images WHERE filename = '$image_filename';");
    if ($result[0]['exist'] > 0) {
        return -2;
    }

    // Insert into database
    $sql = "
            INSERT INTO images (
                data,
                thumbnail,
                taken,
                type,
                width,
                height,
                model,
                exposure,
                iso,
                aperture,
                filesize,
                filename,
                title,
                description,
                created
            )
            VALUES (
                '$image_data',
                '$image_thumbnail',
                $image_taken,
                $image_type,
                $image_width,
                $image_height,
                $image_model,
                $image_exposure,
                $image_iso,
                $image_aperture,
                $image_filesize,
                '$image_filename',
                '',
                '',
                NOW()
            )
    ";
    
    //~ die("<pre>".$sql."<br>".print_r($exif, true)."</pre>");
    
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
        die("Failed to create destination image");
    
    // set canvas background color
    $panel_color = str_replace("#", "", GALLERY_THUMBNAIL_PANEL_COLOR);
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
                    die("Could not copy resampled image");
                imagedestroy($image_resource);
            }
        }
    }

    // write canvas to file
    if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
        die("Could not create new jpeg image");

    imagedestroy($canvas_resource);

    return addslashes(file_get_contents(IMAGE_TEMP_FILE));
}

function regenerate_image_thumbnails($db) {
    $regenerated_thumbnails = 0;
    $sql = "SELECT id, data FROM images";
    foreach (phphoto_db_query($db, $sql) as $image) {
        $temp_resource = imagecreatefromstring($image['data']);
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