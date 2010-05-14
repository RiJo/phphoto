<?php

function phphoto_upload_image() {
    global $allowed_filetypes;
    if(isset($_FILES['image'])) {
        $uploaded_image = $_FILES['image'];
        $extension = end(explode(".", $uploaded_image['name']));
        $filesize = filesize($uploaded_image['tmp_name']);

        if (!in_array(strtolower($extension), $allowed_filetypes)) {
            echo "\n<div class='error'>not a valid filetype: $extension</div>";
        }
        elseif (!is_numeric($filesize) || $filesize > IMAGE_MAX_FILESIZE) {
            echo "\n<div class='error'>the file is too big (".format_byte($filesize)."), allowed is less than ".format_byte(IMAGE_MAX_FILESIZE)."!</div>";
        }
        else {
            $db = phphoto_db_connect();
            $image_id = store_image($db, $uploaded_image);
            echo "\n    <div class='info'>Image uploaded successfully</div>";
            //~ phphoto_db_disconnect($db);

            //~ echo "\n<meta http-equiv='Refresh' content='0; url='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>";
            //~ exit;
        }
    }

    $filetypes = implode(', ', $allowed_filetypes);

    echo "\n<div class='settings'>";
    echo "\n    <h1>Upload image</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."' enctype='multipart/form-data'>";
    echo "\n        allowed formats: $filetypes";
    echo "\n        <br>";
    echo "\n        maximum size: ".format_byte(IMAGE_MAX_FILESIZE);
    echo "\n        <br>";
    echo "\n        <input type='file' name='image'>";
    echo "\n        <input type='submit' value='Upload'>";
    echo "\n    </form>";
    echo "\n</div>";
}

function phphoto_create_gallery($db) {
    if(isset($_POST['title'])) {
        $title = $_POST['title'];
        $sql = "INSERT INTO galleries (title, description, created) VALUES ('$title', '', NOW())";
        if (phphoto_db_query($db, $sql) == 1) {
            echo "\n    <div class='info'>Gallery has has been added</div>";
        }
    }
    echo "\n<div class='settings'>";
    echo "\n    <h1>Create gallery</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>";
    echo "\n        <input type='input' name='title' maxlength='255'>";
    echo "\n        <input type='submit' value='Create'>";
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Form for updating an existing gallery
 */
function phphoto_echo_admin_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id));

    echo "\n<div class='settings'>";
    echo "\n    <h1><a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>Admin galleries</a> >>> Edit gallery</h1>";

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE && isset($_POST[GET_KEY_IMAGE_ID])) {
            $sql = "INSERT INTO image_to_gallery (gallery_id, image_id, created) VALUES ($gallery_id, ".$_POST[GET_KEY_IMAGE_ID].", NOW())";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='info'>Image has has been added</div>";
            }
        }
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
    array_push($table_data, array("Title",          "<input type='input' name='title' maxlength='255' value='$gallery_data[title]'>"));
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

    // images not in this gallery
    $sql = "SELECT id, title, filename FROM images WHERE id NOT IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)";
    $images = phphoto_db_query($db, $sql);
    if (count($images) > 0) {
        echo "\n    <form method='post' action='".CURRENT_PAGE."?".
                GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".
                GET_KEY_OPERATION."=".GET_VALUE_CREATE."&".
                GET_KEY_GALLERY_ID."=$gallery_id'>";
        echo "\n        <select name='".GET_KEY_IMAGE_ID."'>";
        foreach ($images as $row) {
            echo "\n            <option value='$row[id]'>".((empty($row['title'])?$row['filename']:$row['title']))."</option>";
        }
        echo "\n        </select>";
        echo "\n        <input type='submit' value='Add'>";
        echo "\n    </form>";
    }

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
    phphoto_create_gallery($db);

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

    // OPERATIONS
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
    array_push($table_data, array("Filesize",       format_byte($image_data['filesize'])));
    array_push($table_data, array("Resolution",     $image_data['width'].'x'.$image_data['height'].' ('.
                                                    aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array("Filename",       $image_data['filename']));
    array_push($table_data, array("Used in",        implode(', ', $gallery_names)));
    array_push($table_data, array("Title",          "<input type='input' name='title' maxlength='255' value='$image_data[title]'>"));
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
    phphoto_upload_image();

    $sql = "SELECT id, width, height, filesize, filename, title, description FROM images";

    $header = array('Thumbnail', 'Resolution', 'Aspect', 'Filesize', 'Filename', 'Title', 'Description');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_IMAGE_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['width'].'x'.$row['height'],
            aspect_ratio($row['width'], $row['height']),
            format_byte($row['filesize']),
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