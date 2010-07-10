<?php

function phphoto_upload_image() {
    global $allowed_filetypes;
    if(isset($_FILES['image'])) {
        $uploaded_image = $_FILES['image'];
        $extension = end(explode('.', $uploaded_image['name']));
        $filesize = filesize($uploaded_image['tmp_name']);
        $replace_existing = (isset($_POST['replace']) && $_POST['replace'] == 'true');

        if (!in_array(strtolower($extension), $allowed_filetypes)) {
            echo "\n<div class='message' id='error'>not a valid filetype: $extension</div>";
        }
        elseif (!is_numeric($filesize) || $filesize > IMAGE_MAX_FILESIZE) {
            echo "\n<div class='message' id='error'>the file is too big (".format_byte($filesize)."), allowed is less than ".format_byte(IMAGE_MAX_FILESIZE)."!</div>";
        }
        else {
            $db = phphoto_db_connect();
            $image_id = store_image($db, $uploaded_image, $replace_existing);
            if ($image_id == INVALID_ID) {
                echo "\n    <div class='message' id='error'>Could not insert the image in the database</div>";
            }
            elseif ($image_id == -2) {
                echo "\n    <div class='message' id='warning'>Filename already exists in database</div>";
            }
            else {
                echo "\n    <div class='message' id='info'>Image uploaded successfully</div>";
            }
        }
        unlink($uploaded_image['tmp_name']); // delete temp file
    }

    $filetypes = implode(', ', $allowed_filetypes);

    echo "\n<div class='admin'>";
    echo "\n    <h1>Upload image</h1>";
    echo "\n    <p>";
    echo "\n    allowed formats: $filetypes";
    echo "\n    <br>";
    echo "\n    maximum size: ".format_byte(IMAGE_MAX_FILESIZE);
    echo "\n    </p>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."' enctype='multipart/form-data'>";
    echo "\n        <input type='file' name='image'>";
    echo "\n        <br>";
    echo "\n        <input type='submit' value='Upload'>";
    echo "\n        <input type='checkbox' name='replace' value='true' id='replace'><label for='replace'>Replace existing</label>";
    echo "\n    </form>";
    echo "\n</div>";
}

function phphoto_create_gallery($db) {
    if (isset($_POST['title'])) {
        $title = $_POST['title'];
        $sql = "INSERT INTO galleries (title, description, created) VALUES ('$title', '', NOW())";
        if (phphoto_db_query($db, $sql) == 1) {
            echo "\n    <div class='message' id='info'>Gallery has has been added</div>";
        }
    }
    echo "\n<div class='admin'>";
    echo "\n    <h1>Create gallery</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>";
    echo "\n        <input type='input' name='title' maxlength='255'>";
    echo "\n        <input type='submit' value='Create'>";
    echo "\n    </form>";
    echo "\n</div>";
}

function phphoto_create_tag($db) {
    if (isset($_POST['name'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO tags (name, created) VALUES ('$name', NOW())";
        if (phphoto_db_query($db, $sql) == 1) {
            echo "\n    <div class='message' id='info'>Tag has has been added</div>";
        }
    }
    echo "\n<div class='admin'>";
    echo "\n    <h1>Create tag</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_TAG."'>";
    echo "\n        <input type='input' name='name' maxlength='255'>";
    echo "\n        <input type='submit' value='Create'>";
    echo "\n    </form>";
    echo "\n</div>";
}

function phphoto_regenerate_image_thumbnails($db) {
    if(isset($_POST['regenerate_thumbs'])) {
        $regenerated_thumbnails = regenerate_image_thumbnails($db);
        echo "\n    <div class='message' id='info'>$regenerated_thumbnails thumbnails have been regenerated</div>";
    }

    echo "\n<div class='admin'>";
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
            echo "\n    <div class='message' id='info'>Gallery thumbnail have been regenerated</div>";
        }
    }

    echo "\n<div class='admin'>";
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

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE && isset($_POST[GET_KEY_IMAGE_ID])) {
            // add image
            $sql = "INSERT INTO image_to_gallery (gallery_id, image_id, created) VALUES ($gallery_id, ".$_POST[GET_KEY_IMAGE_ID].", NOW())";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Image has has been added</div>";
            }
        }
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_IMAGE_ID])) {
            // remove image
            $sql = "DELETE FROM image_to_gallery WHERE gallery_id = $gallery_id AND image_id = ".$_GET[GET_KEY_IMAGE_ID];
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Image has has been removed</div>";
            }
        }
        if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
            // update gallery
            $title = $_POST['title'];
            $description = $_POST['description'];

            $sql = "UPDATE galleries SET title = '$title', description = '$description' WHERE id = $gallery_id";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Gallery has been updated</div>";
            }
        }
    }

    phphoto_regenerate_gallery_thumbnail($db, $gallery_id);

    $sql = "SELECT id, title, description, views, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images, changed, created FROM galleries WHERE id = $gallery_id";
    $gallery_data = phphoto_db_query($db, $sql);

    if (count($gallery_data) != 1) {
        echo "\n    <div class='message' id='error'>Unknown gallery</div>";
        echo "\n</div>";
        return;
    }
    $gallery_data = $gallery_data[0];

    $table_data = array();
    array_push($table_data, array('&nbsp;',         "<img src='image.php?".GET_KEY_GALLERY_ID."=".$gallery_id."'>"));
    array_push($table_data, array('Views',          $gallery_data['views']));
    array_push($table_data, array('Images',         $gallery_data['images']));
    array_push($table_data, array('Title',          "<input type='input' name='title' maxlength='255' value='$gallery_data[title]'>"));
    array_push($table_data, array('Description',    "<textarea name='description'>$gallery_data[description]</textarea>"));
    array_push($table_data, array('Changed',        format_date_time($gallery_data['changed'])));
    array_push($table_data, array('Created',        format_date_time($gallery_data['created'])));
    array_push($table_data, array('&nbsp;',         "<input type='submit' value='Save'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>Edit gallery</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_GALLERY_ID."=$gallery_id'>";
    phphoto_to_html_table(null, $table_data);
    echo "\n    </form>";
    echo "\n</div>";

    // images not in this gallery
    echo "\n<div class='admin'>";
    echo "\n    <h1>Images in gallery</h1>";
    $sql = "SELECT id, title, filename FROM images WHERE id NOT IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)";
    $images = phphoto_db_query($db, $sql);
    if (count($images) > 0) {
        echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
                GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.
                GET_KEY_OPERATION.'='.GET_VALUE_CREATE.'&'.
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
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['filename'],
            $row['title'],
            $row['description'],
            "<a href='".CURRENT_PAGE.'?'.
                    GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.
                    GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.
                    GET_KEY_GALLERY_ID.'='.$gallery_id.'&'.
                    GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png'></a>"
        ));
    }
    phphoto_to_html_table($header, $images);

    echo "\n</div>";
}

/*
 * Table showing all galleries available for editing
 */
function phphoto_echo_admin_galleries($db) {
    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_GALLERY_ID])) {
            // delete gallery
            $sql = "DELETE FROM galleries WHERE id = ".$_GET[GET_KEY_GALLERY_ID];
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Gallery has has been removed</div>";
            }
        }
    }

    phphoto_create_gallery($db);

    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM galleries";
    $pages = phphoto_db_query($db, $sql);
    $pages = $pages[0]['pages'];

    $sql = "
        SELECT
            id,
            title,
            views,
            views / (SELECT SUM(views) FROM galleries) AS popularity,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images
        FROM
            galleries
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array('Thumbnail', 'Title', 'Views', 'Images', '&nbsp;');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_GALLERY_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_GALLERY_ID."=$row[id]'></a>",
            $row['title'],
            $row['views']." (".round($row['popularity']*100)."%)",
            $row['images'],
            ((!$row['images']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_GALLERY_ID."=$row[id]'><img src='./icons/process-stop.png'></a>" : "<img src='./icons/process-stop-inactive.png'>")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>Admin galleries</h1>";
    phphoto_to_html_table($header, $data);

    echo "\n    <div class='admin' id='footer'>";
    if ($page_number > 0)
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1)."'><img src='./icons/go-previous.png'></a>";
    else
        echo "<img src='./icons/go-previous-inactive.png'>";
    echo "&nbsp;".($page_number + 1)." (of $pages)&nbsp;";
    if ($page_number < ($pages - 1))
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1)."'><img src='./icons/go-next.png'></a>";
    else
        echo "<img src='./icons/go-next-inactive.png'>";
    echo "\n    </div>";

    echo "\n</div>";
}

/*
 * Form for updating an existing tag
 */
function phphoto_echo_admin_tag($db, $tag_id) {
    assert(is_numeric($tag_id));

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['name'])) {
            // update tag
            $name = $_POST['name'];

            $sql = "UPDATE tags SET name = '$name' WHERE id = $tag_id";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Tag has been updated</div>";
            }
        }
    }

    $sql = "SELECT id, name, (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images, changed, created FROM tags WHERE id = $tag_id";
    $tag_data = phphoto_db_query($db, $sql);

    if (count($tag_data) != 1) {
        echo "\n    <div class='message' id='error'>Unknown tag</div>";
        echo "\n</div>";
        return;
    }
    $tag_data = $tag_data[0];

    $sql = "SELECT id, IF (LENGTH(title) > 0, title, filename) AS name FROM images WHERE id IN (SELECT image_id FROM image_to_tag WHERE tag_id = $tag_id)";
    $image_data = phphoto_db_query($db, $sql);

    $image_names = array();
    foreach ($image_data as $image)
        array_push($image_names, "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$image[id]'>$image[name]</a>");

    $table_data = array();
    array_push($table_data, array('Name',           "<input type='input' name='name' maxlength='255' value='$tag_data[name]'>"));
    array_push($table_data, array('Image use',      implode('<br>', $image_names)));
    array_push($table_data, array('Changed',        format_date_time($tag_data['changed'])));
    array_push($table_data, array('Created',        format_date_time($tag_data['created'])));
    array_push($table_data, array('&nbsp;',         "<input type='submit' value='Save'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>Edit tag</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_TAG_ID."=$tag_id'>";
    phphoto_to_html_table(null, $table_data);
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Table showing all tags available for editing
 */
function phphoto_echo_admin_tags($db) {
    // OPERATIONS
    //~ if (isset($_GET[GET_KEY_OPERATION])) {
        //~ if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_GALLERY_ID])) {
            //~ // delete gallery
            //~ $sql = "DELETE FROM galleries WHERE id = ".$_GET[GET_KEY_GALLERY_ID];
            //~ if (phphoto_db_query($db, $sql) == 1) {
                //~ echo "\n    <div class='message' id='info'>Gallery has has been removed</div>";
            //~ }
        //~ }
    //~ }

    phphoto_create_tag($db);

    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM tags";
    $pages = phphoto_db_query($db, $sql);
    $pages = $pages[0]['pages'];

    $sql = "
        SELECT
            id,
            name,
            (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images
        FROM
            tags
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array('Name', 'Images', /*'&nbsp;'*/);
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_TAG_ID."=$row[id]'>$row[name]</a>",
            $row['images'],
            //((!$row['images']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_TAG_ID."=$row[id]'><img src='./icons/process-stop.png'></a>" : "<img src='./icons/process-stop-inactive.png'>")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>Admin galleries</h1>";
    phphoto_to_html_table($header, $data);

    echo "\n    <div class='admin' id='footer'>";
    if ($page_number > 0)
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1)."'><img src='./icons/go-previous.png'></a>";
    else
        echo "<img src='./icons/go-previous-inactive.png'>";
    echo "&nbsp;".($page_number + 1)." (of $pages)&nbsp;";
    if ($page_number < ($pages - 1))
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1)."'><img src='./icons/go-next.png'></a>";
    else
        echo "<img src='./icons/go-next-inactive.png'>";
    echo "\n    </div>";

    echo "\n</div>";
}

/*
 * Form for updating an existing image
 */
function phphoto_echo_admin_image($db, $image_id) {
    assert(is_numeric($image_id));

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
            // update image
            $title = $_POST['title'];
            $description = $_POST['description'];

            $sql = "UPDATE images SET title = '$title', description = '$description' WHERE id = $image_id";
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Image has been updated</div>";
            }
        }
    }

    $sql = "SELECT id, type, width, height, filesize, filename, exif, title, description, changed, created FROM images WHERE id = $image_id";
    $image_data = phphoto_db_query($db, $sql);
    $sql = "SELECT id, title FROM galleries WHERE id IN (SELECT gallery_id FROM image_to_gallery WHERE image_id = $image_id)";
    $gallery_data = phphoto_db_query($db, $sql);
    $sql = "SELECT id, name FROM tags WHERE id IN (SELECT tag_id FROM image_to_tag WHERE image_id = $image_id)";
    $tag_data = phphoto_db_query($db, $sql);

    if (count($image_data) != 1) {
        echo "\n    <div class='message' id='error'>Unknown image</div>";
        echo "\n</div>";
        return;
    }

    $gallery_names = array();
    foreach ($gallery_data as $gallery)
        array_push($gallery_names, "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_GALLERY_ID."=$gallery[id]'>$gallery[title]</a>");

    $tag_names = array();
    foreach ($tag_data as $tag)
        array_push($tag_names, "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_TAG_ID."=$tag[id]'>$tag[name]</a>");

    $image_data = $image_data[0];
    if ($image_data['exif'])
        eval('$exif = ' . $image_data['exif'] . ';');
    else
        $exif = array();

    $table_data = array();
    array_push($table_data, array('&nbsp;',         "<a href='image.php?".GET_KEY_IMAGE_ID.'='.$image_id."'><img src='image.php?".GET_KEY_IMAGE_ID.'='.$image_id."t'></a>"));
    array_push($table_data, array('Filename',       $image_data['filename']));
    array_push($table_data, array('Format',         image_type_to_mime_type($image_data['type'])));
    array_push($table_data, array('Filesize',       format_byte($image_data['filesize'])));
    array_push($table_data, array('Resolution',     $image_data['width'].'x'.$image_data['height'].' ('.
                                                    aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array('Filename',       $image_data['filename']));
    array_push($table_data, array('EXIF version',   ((isset($exif['ExifVersion'])) ? $exif['ExifVersion'] : VARIABLE_NOT_SET)));
    array_push($table_data, array('Camera',         "<img src='./icons/camera-photo.png'>&nbsp;&nbsp;&nbsp;".format_camera_model($exif)));
    array_push($table_data, array('Settings',       "<img src='./icons/image-x-generic.png'>&nbsp;&nbsp;&nbsp;".format_camera_settings($exif)));
    array_push($table_data, array('Gallery use',    implode('<br>', $gallery_names)));
    array_push($table_data, array('Tag use',    implode('<br>', $tag_names)));
    array_push($table_data, array('Title',          "<input type='input' name='title' maxlength='255' value='$image_data[title]'>"));
    array_push($table_data, array('Description',    "<textarea name='description'>$image_data[description]</textarea>"));
    array_push($table_data, array('Changed',        format_date_time($image_data['changed'])));
    array_push($table_data, array('Created',        format_date_time($image_data['created'])));
    array_push($table_data, array('&nbsp;',         "<input type='submit' value='Save'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>Edit image</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_IMAGE_ID."=$image_id'>";
    phphoto_to_html_table(null, $table_data);
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Table showing all images available for editing
 */
function phphoto_echo_admin_images($db) {
    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_IMAGE_ID])) {
            // delete image
            $sql = "DELETE FROM images WHERE id = ".$_GET[GET_KEY_IMAGE_ID];
            if (phphoto_db_query($db, $sql) == 1) {
                echo "\n    <div class='message' id='info'>Image has has been removed</div>";
            }
        }
    }

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
            IF (LENGTH(title) > 0, title, filename) AS name,
            views,
            views / (SELECT SUM(views) FROM images) AS popularity,
            (SELECT COUNT(*) FROM image_to_gallery WHERE image_id = id) AS in_use
        FROM
            images
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array('Thumbnail', 'Name', 'Resolution', 'Filesize', 'Views', '&nbsp;');
    $max_text_length = 12;
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            wordwrap($row['name'], 20, '<br>', true),
            $row['width'].'x'.$row['height'].'<br>'.aspect_ratio($row['width'], $row['height']),
            format_byte($row['filesize']),
            $row['views']." (".round($row['popularity']*100)."%)",
            ((!$row['in_use']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png'></a>" : "<img src='./icons/process-stop-inactive.png'>")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>Admin images</h1>";
    phphoto_to_html_table($header, $data);
    
    echo "\n    <div class='admin' id='footer'>";
    if ($page_number > 0)
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1)."'><img src='./icons/go-previous.png'></a>";
    else
        echo "<img src='./icons/go-previous-inactive.png'>";
    echo "&nbsp;".($page_number + 1)." (of $pages)&nbsp;";
    if ($page_number < ($pages - 1))
        echo "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1)."'><img src='./icons/go-next.png'></a>";
    else
        echo "<img src='./icons/go-next-inactive.png'>";
    echo "\n    </div>";

    echo "\n</div>";
}

?>