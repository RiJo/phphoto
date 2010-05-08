<?php

/*
 * Form for updating an existing gallery
 */
function phphoto_echo_admin_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id));

    echo "\n<div class='settings'>";
    echo "\n    <h1><a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>Admin galleries</a> >>> Edit gallery</h1>";

    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_IMAGE_ID])) {
            $sql = "DELETE FROM image_to_gallery WHERE gallery_id = $gallery_id AND image_id = ".$_GET[GET_KEY_IMAGE_ID];
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='info'>Image has has been removed</div>";
            }
        }
        if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];

            $sql = "UPDATE galleries SET title = '$title', description = '$description' WHERE id = $gallery_id";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='info'>Gallery has been updated</div>";
            }
        }
    }

    $sql = "SELECT id, title, description, views, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images, changed, created FROM galleries WHERE id = $gallery_id";
    $gallery_data = phphoto_db_query($db, $sql);

    if (count($gallery_data) != 1) {
        echo "\n    <div class='error'>Unknown gallery</div>";
        echo "\n</div>";
        return;
    }
    $gallery_data = $gallery_data[0];

    $table_data = array();
    array_push($table_data, array("Views",          $gallery_data['views']));
    array_push($table_data, array("Images",         $gallery_data['images']));
    array_push($table_data, array("Title",          "<input type='input' name='title' value='$gallery_data[title]'>"));
    array_push($table_data, array("Description",    "<textarea name='description'>$gallery_data[description]</textarea>"));
    array_push($table_data, array("Changed",        format_date_time($gallery_data['changed'])));
    array_push($table_data, array("Created",        format_date_time($gallery_data['created'])));
    array_push($table_data, array("&nbsp;",         "<input type='submit' value='Save'>"));

    echo "\n    <form method='post' action='".CURRENT_PAGE."?".
            GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".
            GET_KEY_OPERATION."=".GET_VALUE_UPDATE."&".
            GET_KEY_GALLERY_ID."=$gallery_id'>";
    phphoto_to_html_table(null, $table_data);
    echo "\n    </form>";

    // images in this gallery
    $sql = "SELECT id, title, description, filename FROM images WHERE id IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)";

    $header = array('Thumbnail', 'Filename', 'Title', 'Description', '&nbsp;');
    $images = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($images, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_IMAGE_ID."=$row[id]'><img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['filename'],
            $row['title'],
            $row['description'],
            "<a href='".CURRENT_PAGE."?".
                    GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".
                    GET_KEY_OPERATION."=".GET_VALUE_DELETE."&".
                    GET_KEY_GALLERY_ID."=".$gallery_id."&".
                    GET_KEY_IMAGE_ID."=$row[id]'>Remove</a>"
        ));
    }
    phphoto_to_html_table($header, $images);

    echo "\n</div>";
}

/*
 * Table showing all galleries available for editing
 */
function phphoto_echo_admin_galleries($db) {
    $sql = "SELECT id, title, description, views, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images FROM galleries";

    $header = array('Title', 'Description', 'Views', 'Images');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".GET_KEY_GALLERY_ID."=$row[id]'>$row[title]</a>",
            $row['description'],
            $row['views'],
            $row['images']
        ));
    }

    echo "\n<div class='settings'>";
    echo "\n    <h1>Admin galleries</h1>";
    phphoto_to_html_table($header, $data);
    echo "\n</div>";
}

/*
 * Form for updating an existing image
 */
function phphoto_echo_admin_image($db, $image_id) {
    assert(is_numeric($image_id));

    echo "\n<div class='settings'>";
    echo "\n    <h1><a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>Admin images</a> >>> Edit image</h1>";

    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];

            $sql = "UPDATE images SET title = '$title', description = '$description' WHERE id = $image_id";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='info'>Image has been updated</div>";
            }
        }
    }

    $sql = "SELECT id, width, height, type, filesize, filename, title, description, changed, created FROM images WHERE id = $image_id";
    $image_data = phphoto_db_query($db, $sql);
    $sql = "SELECT id, title FROM galleries WHERE id IN (SELECT gallery_id FROM image_to_gallery WHERE image_id = $image_id)";
    $gallery_data = phphoto_db_query($db, $sql);

    $gallery_names = array();
    foreach ($gallery_data as $gallery)
        array_push($gallery_names, "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".GET_KEY_GALLERY_ID."=$gallery[id]'>$gallery[title]</a>");

    if (count($image_data) != 1) {
        echo "\n    <div class='error'>Unknown image</div>";
        echo "\n</div>";
        return;
    }
    $image_data = $image_data[0];

    $table_data = array();
    array_push($table_data, array("&nbsp;",         "<img src='image.php?".GET_KEY_IMAGE_ID."=".$image_id."t'>"));
    array_push($table_data, array("Filename",       $image_data['filename']));
    array_push($table_data, array("Format",         image_type_to_mime_type($image_data['type'])));
    array_push($table_data, array("Filesize",       round($image_data['filesize']/1024).' kB'));
    array_push($table_data, array("Resolution",     $image_data['width'].'x'.$image_data['height'].' ('.
                                                    aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array("Filename",       $image_data['filename']));
    array_push($table_data, array("Used in",        implode(', ', $gallery_names)));
    array_push($table_data, array("Title",          "<input type='input' name='title' value='$image_data[title]'>"));
    array_push($table_data, array("Description",    "<textarea name='description'>$image_data[description]</textarea>"));
    array_push($table_data, array("Changed",        format_date_time($image_data['changed'])));
    array_push($table_data, array("Created",        format_date_time($image_data['created'])));
    array_push($table_data, array("&nbsp;",         "<input type='submit' value='Save'>"));

    echo "\n    <form method='post' action='".CURRENT_PAGE."?".
            GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".
            GET_KEY_OPERATION."=".GET_VALUE_UPDATE."&".
            GET_KEY_IMAGE_ID."=$image_id'>";
    phphoto_to_html_table(null, $table_data);
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Table showing all images available for editing
 */
function phphoto_echo_admin_images($db) {
    $sql = "SELECT id, width, height, filesize, filename, title, description FROM images";

    $header = array('Thumbnail', 'Resolution', 'Aspect', 'Filesize', 'Filename', 'Title', 'Description');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_IMAGE_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['width'].'x'.$row['height'],
            aspect_ratio($row['width'], $row['height']),
            round($row['filesize'] / 1024)." kB",
            $row['filename'],
            $row['title'],
            $row['description']
        ));
    }

    echo "\n<div class='settings'>";
    echo "\n    <h1>Admin images</h1>";
    phphoto_to_html_table($header, $data);
    echo "\n</div>";
}

?>