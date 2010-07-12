<?php

session_start();

require_once('./config.php');
require_once('./database.php');

define('THUMBNAIL_SUFFIX',      't');

if (isset($_GET[GET_KEY_IMAGE_ID])) {
    $id = $_GET[GET_KEY_IMAGE_ID];

    if (is_numeric($id)) {
        // normal image
        $thumbnail = false;
    }
    else {
        // thumbnail
        $thumbnail = (substr($id, strlen($id) - 1) === THUMBNAIL_SUFFIX);
        if ($thumbnail) {
            $id = substr($id, 0, strlen($id) - 1);
            if (!is_numeric($id)) {
                not_valid_id($id, 'the id is not numeric');
            }
        }
        else {
            not_valid_id($id, 'the id is not a valid thumbnail id');
        }
    }

    $db = phphoto_db_connect();
    if ($thumbnail) {
        $column = 'thumbnail';
    }
    else {
        $column = 'data';

        // update views counter
        if (!isset($_GET[GET_KEY_ADMIN_QUERY])) { // do not count administrative views
            if (!isset($_SESSION[SESSION_KEY_VIEWS]) || !isset($_SESSION[SESSION_KEY_VIEWS]["i$id"])) {
                phphoto_db_query($db, "UPDATE images SET views = views + 1 WHERE id = $id");
                $_SESSION[SESSION_KEY_VIEWS]["i$id"] = SESSION_VALUE_VIEWS;
            }
        }
    }
    $result = phphoto_db_query($db, "SELECT $column AS image, type FROM images WHERE id = $id;");
    phphoto_db_disconnect($db);

    if (empty($result)) {
        not_valid_id($id, 'there is no image in the database with that id');
    }

    $image = $result[0]['image'];
    $type = $result[0]['type'];

    if ($thumbnail)
        header('Content-type: image/png');
    else
        header('Content-type: ' . image_type_to_mime_type($type));
    echo ($image);
    exit;
}
elseif (isset($_GET[GET_KEY_GALLERY_ID])) {
    $id = $_GET[GET_KEY_GALLERY_ID];

    if (!is_numeric($id)) {
        not_valid_id($id, 'the id is not numeric');
    }

    $db = phphoto_db_connect();
    $result = phphoto_db_query($db, "SELECT thumbnail AS image FROM galleries WHERE id = $id;");
    phphoto_db_connect($db);

    if (empty($result)) {
        not_valid_id($id, 'there is no gallery in the database with that id');
    }

    if ($result[0]['image'] == null)
        $image = generate_null_image();
    else
        $image = $result[0]['image'];

    header('Content-type: image/png');
    echo ($image);
    exit;
}
else {
    not_valid_id('', 'no image requested');
}

function not_valid_id($id, $message) {
    die("not a valid image id ($id):<br>$message");
    exit;
}

function generate_null_image() {
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

    // set invalid-cross color
    $cross_color = str_replace('#', '', GALLERY_THUMBNAIL_INVALID_COLOR);
    if (strlen($cross_color) != 6)
        die("Panel color is not properly formatted: #$panel_color");
    $cross_color = imagecolorallocate(
            $canvas_resource,
            hexdec(substr($cross_color, 0, 2)),
            hexdec(substr($cross_color, 2, 2)),
            hexdec(substr($cross_color, 4, 2))
    );

    imagefill($canvas_resource, 0, 0, $canvas_bg);

    imagesetthickness($canvas_resource, 2);
    imageline (
            $canvas_resource,
            GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT * 0.45,
            GALLERY_THUMBNAIL_WIDTH - GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT - GALLERY_THUMBNAIL_HEIGHT * 0.45,
            $cross_color
    );
    imageline (
            $canvas_resource,
            GALLERY_THUMBNAIL_WIDTH - GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT * 0.45,
            GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT - GALLERY_THUMBNAIL_HEIGHT * 0.45,
            $cross_color
    );

    // write canvas to file
    if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
        die('could not create new jpeg image');

    imagedestroy($canvas_resource);

    return file_get_contents(IMAGE_TEMP_FILE);
}

?>
