<?php

require_once('./phphoto.php');

if (isset($_GET[GET_KEY_IMAGE_ID])) {
    $id = $_GET[GET_KEY_IMAGE_ID];

    if (is_numeric($id)) {
        // normal image
        $thumbnail = false;
    }
    else {
        // thumbnail
        $thumbnail = (substr($id, strlen($id) - 1) === IMAGE_THUMBNAIL_SUFFIX);
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
        $image = phphoto_generate_null_image();
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
    die("Not a valid image id ($id): $message");
    exit;
}

?>
