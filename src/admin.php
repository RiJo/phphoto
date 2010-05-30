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
            if ($image_id == INVALID_ID) {
                echo "\n    <div class='error'>Could not insert the image in the database</div>";
            }
            elseif ($image_id == -2) {
                echo "\n    <div class='warning'>Filename already exists in database</div>";
            }
            else {
                echo "\n    <div class='info'>Image uploaded successfully</div>";
            }
        }
    }

    $filetypes = implode(', ', $allowed_filetypes);

    echo "\n<div class='settings'>";
    echo "\n    <h1>Upload image</h1>";
    echo "\n    <p>";
    echo "\n    allowed formats: $filetypes";
    echo "\n    <br>";
    echo "\n    maximum size: ".format_byte(IMAGE_MAX_FILESIZE);
    echo "\n    </p>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."' enctype='multipart/form-data'>";
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

function phphoto_regenerate_image_thumbnails($db) {
    if(isset($_POST['regenerate_thumbs'])) {
        $regenerated_thumbnails = regenerate_image_thumbnails($db);
        echo "\n    <div class='info'>$regenerated_thumbnails thumbnails have been regenerated</div>";
    }

    echo "\n<div class='settings'>";
    echo "\n    <h1>Regenerate thumbnails</h1>";
    echo "\n    <p>Note: this may take a while depending on the number of images in the database.</p>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>";
    echo "\n        <input type='submit' name='regenerate_thumbs' value='Start'>";
    echo "\n    </form>";
    echo "\n</div>";
}


function phphoto_regenerate_gallery_thumbnail($db, $gallery_id) {
    if(isset($_POST['regenerate_thumbs'])) {
        if (regenerate_gallery_thumbnail($db, $gallery_id)) {
            echo "\n    <div class='info'>Gallery thumbnail have been regenerated</div>";
        }
    }

    echo "\n<div class='settings'>";
    echo "\n    <h1>Regenerate thumbnail</h1>";
    echo "\n    <p>Note: this may take a while depending on the number of images in the gallery.</p>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".GET_KEY_GALLERY_ID."=$gallery_id'>";
    echo "\n        <input type='submit' name='regenerate_thumbs' value='Start'>";
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Form for updating an existing gallery
 */
function phphoto_echo_admin_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id));

    phphoto_regenerate_gallery_thumbnail($db, $gallery_id);

    echo "\n<div class='settings'>";
    echo "\n    <h1>Edit gallery</h1>";

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
    array_push($table_data, array("&nbsp;",         "<img src='gallery_thumb.php?".GET_KEY_GALLERY_ID."=".$gallery_id."'>"));
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
                    GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/tango/actions/process-stop.png'></a>"
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

    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;

    $sql = "
        SELECT
            id,
            title,
            description,
            views,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images
        FROM
            galleries
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array('Title', 'Description', 'Views', 'Images', '&nbsp;');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".GET_KEY_GALLERY_ID."=$row[id]'>$row[title]</a>",
            $row['description'],
            $row['views'],
            $row['images'],
            "<a href='".CURRENT_PAGE."'><img src='./icons/tango/actions/process-stop.png'></a>"
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
    echo "\n    <h1>Edit image</h1>";

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

    $sql = "SELECT id, type, width, height, filesize, filename, exif, title, description, changed, created FROM images WHERE id = $image_id";
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
    if ($image_data['exif'])
        eval('$exif = ' . $image_data['exif'] . ';');
    else
        $exif = array();

    $table_data = array();
    array_push($table_data, array("&nbsp;",         "<img src='image.php?".GET_KEY_IMAGE_ID."=".$image_id."t'>"));
    array_push($table_data, array("Filename",       $image_data['filename']));
    array_push($table_data, array("Format",         image_type_to_mime_type($image_data['type'])));
    array_push($table_data, array("Filesize",       format_byte($image_data['filesize'])));
    array_push($table_data, array("Resolution",     $image_data['width'].'x'.$image_data['height'].' ('.
                                                    aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array("Filename",       $image_data['filename']));
    array_push($table_data, array("EXIF version",   ((isset($exif['ExifVersion'])) ? $exif['ExifVersion'] : VARIABLE_NOT_SET)));
    array_push($table_data, array("Camera",         ((isset($exif['Model'])) ? $exif['Model'] : VARIABLE_NOT_SET)));
    array_push($table_data, array("Settings",       format_camera_settings($exif)));
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
    phphoto_regenerate_image_thumbnails($db);

    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM images";
    $pages = phphoto_db_query($db, $sql);
    $pages = $pages[0]['pages'];

    $sql = "
        SELECT
            id,
            width,
            height,
            filesize,
            filename,
            exif,
            title,
            description
        FROM
            images
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array('Thumbnail', 'Resolution', 'Camera', 'Settings', 'Filesize', 'Filename', 'Title', 'Description', '&nbsp;');
    $max_text_length = 12;
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        if ($row['exif'])
            eval('$exif = ' . $row['exif'] . ';');
        else
            $exif = array();

        array_push($data, array(
            "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_IMAGE_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['width'].'x'.$row['height'].'<br>'.aspect_ratio($row['width'], $row['height']),
            ((isset($exif['Model'])) ? $exif['Model'] : VARIABLE_NOT_SET),
            ((isset($exif['ExposureTime'])  || isset($exif['ISOSpeedRatings']) ||isset($exif['FNumber'])) ? 
                    '<br>'.$exif['ISOSpeedRatings'].'<br>'.$exif['FNumber'] : VARIABLE_NOT_SET),
            format_byte($row['filesize']),
            (strlen($row['filename']) < $max_text_length) ? $row['filename'] : substr($row['filename'], 0, $max_text_length).'...',
            (strlen($row['title']) < $max_text_length) ? $row['title'] : substr($row['title'], 0, $max_text_length).'...',
            (strlen($row['description']) < $max_text_length) ? $row['description'] : substr($row['description'], 0, $max_text_length).'...',
            "<a href='".CURRENT_PAGE."'><img src='./icons/tango/actions/process-stop.png'></a>"
        ));
    }

    echo "\n<div class='settings'>";
    echo "\n    <h1>Admin images</h1>";
    phphoto_to_html_table($header, $data);
    
    if ($page_number > 0)
        echo "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_PAGE_NUMBER."=".($page_number - 1)."'><img src='./icons/tango/actions/go-previous.png'></a>";
    echo "&nbsp;".($page_number + 1)." (of $pages)&nbsp;";
    if ($page_number < ($pages - 1))
        echo "<a href='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."&".GET_KEY_PAGE_NUMBER."=".($page_number + 1)."'><img src='./icons/tango/actions/go-next.png'></a>";
    echo "\n</div>";
}

?>