<?php
/*
    todo:
        * avoid sql-injections
        * masking image/gallery editing
        * put texts in database
        * function printing statistics on the first page (images, galleries, used images, unused images)
*/

require_once('./config.php');
require_once('./database.php');
require_once('./admin.php');
require_once('./gallery.php');

$allowed_filetypes = array('gif','jpg','jpeg','png');

function phphoto_main() {
    $db = phphoto_db_connect();
    $admin = (isset($_GET[GET_KEY_ADMIN_QUERY])) ? $_GET[GET_KEY_ADMIN_QUERY] : false;
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

function phphoto_admin_links() {
    echo "\n<div class='settings'>";
    echo "\n    <h1>Administrate</h1>";
    echo "\n    <a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>Galleries</a>";
    echo "\n    <br>";
    echo "\n    <a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>Images</a>";
    echo "\n</div>";
}

function phphoto_upload() {
    global $allowed_filetypes;
    if(isset($_FILES['image'])) {
        $uploaded_image = $_FILES['image'];
        $extension = end(explode(".", $uploaded_image['name']));
        $filesize = filesize($uploaded_image['tmp_name']);

        if (!in_array($extension, $allowed_filetypes)) {
            echo "\n<div class='error'>not a valid filetype: $extension</div>";
        }
        elseif (!is_numeric($filesize) || $filesize > IMAGE_MAX_FILESIZE) {
            echo "\n<div class='error'>the file is too big (".format_byte($filesize)."), allowed is less than ".format_byte(IMAGE_MAX_FILESIZE)."!</div>";
        }
        else {
            $db = phphoto_db_connect();
            $image_id = store_image($db, $uploaded_image);
            phphoto_db_disconnect($db);
            // $image_id ignored... so far...
            //~ header("Location: ".CURRENT_PAGE);
            echo "\n<meta http-equiv='Refresh' content='0; url='.CURRENT_PAGE'>";
            exit;
        }
    }

    $filetypes = implode(', ', $allowed_filetypes);

    echo "\n<div class='settings'>";
    echo "\n    <h1>Upload image</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."' enctype='multipart/form-data'>";
    echo "\n        allowed formats: $filetypes";
    echo "\n        <br>";
    echo "\n        maximum size: ".format_byte(IMAGE_MAX_FILESIZE);
    echo "\n        <br>";
    echo "\n        <input type='file' name='image'>";
    echo "\n        <input type='submit' value='upload'>";
    echo "\n    </form>";
    echo "\n</div>";
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
    $imageData = generate_image($image);
    $resizedData = generate_image($image, IMAGE_RESIZED_WIDTH);
    $thumbnailData = generate_thumbnail($image, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_PANEL_COLOR);

    // Check if exists
    $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM images WHERE filename = '$filename';");
    if ($result[0]['exist'] > 0) {
        return INVALID_ID;
    }

    // Insert into database
    $result = phphoto_db_query($db,
        "INSERT INTO images
        (original, resized, thumbnail, type, width, height, filesize, filename, title, description, created)
        VALUES
        ('$imageData', '$resizedData', '$thumbnailData', $type, $width, $height, $fileSize, '$filename', '', '', NOW());"
    );

    return ($result) ? mysql_insert_id($db) : INVALID_ID;
}

// Returns the image as a byte[]
function generate_image($image, $maxWidth = null) {
    if (!empty($maxWidth)) {
        // Get info about the image
        $imageInfo = getimagesize($image);
        $width =  $imageInfo[0];
        $height =  $imageInfo[1];
        $type =  $imageInfo[2];
        $aspect = $width / $height;
        if ($width > $height) { // Landscape
            $newWidth = $maxWidth;
            $newHeight = $newWidth / $aspect;
        }
        else { // Portrait
            $newHeight = $maxWidth;
            $newWidth = $newHeight * $aspect;
        }

        // Create image temp file


        // Read image
        switch ($type) {
            case 1: // GIF
                if (!$im = ImageCreateFromGif($image))
                    die("Could not create image from gif");
                break;
            case 2: // JPEG
                if (!$im = ImageCreateFromJpeg($image))
                    die("Could not create image from jpeg");
                break;
            case 3: // PNG
                if (!$im = ImageCreateFromPng($image))
                    die("Could not create image from png");
                break;
            default:
                die("Unrecognized image type");
        }

        // Create resized image
        if (!$dst_img = ImageCreateTrueColor($newWidth, $newHeight))
            die("Failed to create destination image");

        // Resize and fit the image on the thumbnail
        if (!ImageCopyResampled( $dst_img, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height))
            die("Could not copy resampled image");

        // Write thumbnail to file
        switch ($type) {
            case 1: // GIF
                if (!imagegif($dst_img, IMAGE_TEMP_FILE, 100))
                    die("Could not create new gif image");
                break;
            case 2: // JPEG
                if (!imagejpeg($dst_img, IMAGE_TEMP_FILE, 100))
                    die("Could not create new jpeg image");
                break;
            case 3: // PNG
                if (!imagepng($dst_img, IMAGE_TEMP_FILE))
                    die("Could not create new png image");
                break;
            default:
                die("Unrecognized image type");
        }

        // Delete image
        imagedestroy($dst_img);

        return addslashes(file_get_contents(IMAGE_TEMP_FILE));
    }
    else {
        return addslashes(file_get_contents($image));
    }

}

// Returns a thumbnail of the image as a byte[]
function generate_thumbnail($image, $maxWidth = 150, $panelColor = '#000000FF') {
    $maxHeight = round($maxWidth * 0.75); // Ratio 4:3
    // Get info about the image
    $imageInfo = getimagesize($image);
    $width =  $imageInfo[0];
    $height =  $imageInfo[1];
    $type =  $imageInfo[2];
    $aspect = $width / $height;
    // Calculate width, height and placement
    $marginTop = 0;
    $marginLeft = 0;
    if ($width > $height) { // Landscape
        $newWidth = $maxWidth;
        $newHeight = ceil($newWidth / $aspect); // ceil is to cover full thumbnail size
        $marginTop = round(($maxHeight - $newHeight) / 2);
    }
    else { // Portrait
        $newHeight = $maxHeight;
        $newWidth = ceil($newHeight * $aspect); // ceil is to cover full thumbnail size
        $marginLeft = round(($maxWidth - $newWidth) / 2);
    }

    // Read image
    switch ($type) {
        case 1: // GIF
            if (!$im = ImageCreateFromGif($image))
                die("Could not create image from gif");
            break;
        case 2: // JPEG
            if (!$im = ImageCreateFromJpeg($image))
                die("Could not create image from jpeg");
            break;
        case 3: // PNG
            if (!$im = ImageCreateFromPng($image))
                die("Could not create image from png");
            break;
        default:
            die("Unrecognized image type");
    }

    // Create thumbnail
    if (!$dst_img = ImageCreateTrueColor($maxWidth, $maxHeight))
        die("Failed to create destination image");

    // Set background color
    $panelColor = str_replace("#", "", $panelColor);
    $r = hexdec(substr($panelColor, 0, 2));
    $g = hexdec(substr($panelColor, 2, 2));
    $b = hexdec(substr($panelColor, 4, 2));
    $a = hexdec(substr($panelColor, 6, 2));
    $bg = imagecolorallocatealpha($dst_img, $r, $g, $b, $a);
    imagefill($dst_img, 0, 0, $bg);

    // Resize and fit the image on the thumbnail
    if (!ImageCopyResampled($dst_img, $im, $marginLeft, $marginTop, 0, 0, $newWidth, $newHeight, $width, $height))
        die("Could not copy resampled image");

    // Write thumbnail to file
    switch ($type) {
        case 1: // GIF
            if (!imagegif($dst_img, IMAGE_TEMP_FILE, 80))
                die("Could not create new gif image");
            break;
        case 2: // JPEG
            if (!imagejpeg($dst_img, IMAGE_TEMP_FILE, 80))
                die("Could not create new jpeg image");
            break;
        case 3: // PNG
            if (!imagepng($dst_img, IMAGE_TEMP_FILE))
                die("Could not create new png image");
            break;
        default:
            die("Unrecognized image type");
    }

    // Delete image
    imagedestroy($dst_img);

    // Escape and return the thumbnail file
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