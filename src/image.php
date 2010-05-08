<?php

require_once('./config.php');
require_once('./database.php');

define("RESIZED_SUFFIX", "r");
define("THUMBNAIL_SUFFIX", "t");

if(isset($_GET[GET_KEY_IMAGE_ID])) {
    $id = $_GET[GET_KEY_IMAGE_ID];

    if (is_numeric($id)) {
        // normal image
        $resized = false;
        $thumbnail = false;
    }
    else {
        // scaled image
        $resized = (substr($id, strlen($id) - 1) === RESIZED_SUFFIX);
        $thumbnail = (substr($id, strlen($id) - 1) === THUMBNAIL_SUFFIX);
        if ($resized || $thumbnail) {
            $id = substr($id, 0, strlen($id) - 1);
            if (!is_numeric($id)) {
                not_valid_id($id, 'the id is not numeric');
            }
        }
        else {
            not_valid_id($id, 'the id is not numeric');
        }
    }

    $db = phphoto_db_connect();
    if ($resized)
        $result = phphoto_db_query($db, "SELECT resized AS image, type FROM images WHERE id = $id;");
    elseif ($thumbnail)
        $result = phphoto_db_query($db, "SELECT thumbnail AS image, type FROM images WHERE id = $id;");
    else
        $result = phphoto_db_query($db, "SELECT original AS image, type FROM images WHERE id = $id;");
    phphoto_db_connect($db);

    if (empty($result)) {
        not_valid_id($id, 'there is no image in the database with that id');
    }

    $image = $result[0]['image'];
    $type = $result[0]['type'];

    header("Content-type: " . image_type_to_mime_type($type));
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

?>
