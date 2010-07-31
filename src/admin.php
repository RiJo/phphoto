<?php

/*
 * Handles the image upload form
 */
function phphoto_upload_image($db) {
    global $allowed_filetypes;
    if(isset($_FILES['image'])) {
        $uploaded_image = $_FILES['image'];

        if (file_exists($uploaded_image['tmp_name'])) {
            $temp = explode('.', $uploaded_image['name']);
            $extension = end($temp);
            $filesize = filesize($uploaded_image['tmp_name']);
            $replace_existing = (isset($_POST['replace']) && $_POST['replace'] == 'true');

            if (!in_array(strtolower($extension), $allowed_filetypes)) {
                phphoto_popup_message(phphoto_text($db, 'image', 'invalid_filetype', $extension), 'error');
            }
            elseif (!is_numeric($filesize) || $filesize > IMAGE_MAX_FILESIZE) {
                phphoto_popup_message(phphoto_text($db, 'image', 'invalid_filesize', format_byte($filesize)), 'error');
            }
            else {
                $db = phphoto_db_connect();
                $image_id = phphoto_store_image($db, $uploaded_image, $replace_existing);
                if ($image_id == INVALID_ID) {
                    phphoto_popup_message(phphoto_text($db, 'image', 'store_error'), 'error');
                }
                elseif ($image_id == -2) {
                    phphoto_popup_message(phphoto_text($db, 'image', 'exists', $uploaded_image['name']), 'warning');
                }
                else {
                    if ($replace_existing)
                        phphoto_popup_message(phphoto_text($db, 'image', 'uploaded_replace', $uploaded_image['name']), 'info');
                    else
                        phphoto_popup_message(phphoto_text($db, 'image', 'uploaded_normal', $uploaded_image['name']), 'info');
                }
            }
            unlink($uploaded_image['tmp_name']); // delete temp file
        }
        else {
            phphoto_popup_message(phphoto_text($db, 'image', 'invalid_temp_file'), 'error');
        }
    }


    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'image', 'upload')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."' enctype='multipart/form-data'>";
    echo "\n        ".phphoto_text($db, 'image', 'allowed_extensions', implode(', ', $allowed_filetypes));
    echo "\n        <br>";
    echo "\n        ".phphoto_text($db, 'image', 'maximum_filesize', format_byte(IMAGE_MAX_FILESIZE));
    echo "\n        <br>";
    echo "\n        <input type='file' name='image'>";
    echo "\n        <br>";
    echo "\n        <input type='submit' value='".phphoto_text($db, 'button', 'upload')."'>";
    echo "\n        <input type='checkbox' name='replace' value='true' id='replace'><label for='replace'>".phphoto_text($db, 'image', 'replace_existing')."</label>";
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Handles the create gallery form
 */
function phphoto_create_gallery($db) {
    if (isset($_POST['title'])) {
        $title = $_POST['title'];

        $sql = sprintf("SELECT COUNT(id) AS exist FROM galleries WHERE title = '%s';",
                mysql_real_escape_string($title, $db));
        $result = phphoto_db_query($db, $sql);
        $gallery_exists = ($result[0]['exist'] == 1);
        if ($gallery_exists) {
            phphoto_popup_message(phphoto_text($db, 'gallery', 'exists', $title), 'warning');
        }
        else {
            $sql = sprintf("INSERT INTO galleries (title, description, created) VALUES ('%s', '', NOW())",
                    mysql_real_escape_string($title, $db));
            if (phphoto_db_query($db, $sql) == 1)
                phphoto_popup_message(phphoto_text($db, 'gallery', 'created', $title), 'info');
            else
                phphoto_popup_message(phphoto_text($db, 'gallery', 'store_error', $title), 'error');
        }
    }
    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'gallery', 'create')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."'>";
    echo "\n        <input type='input' name='title' maxlength='255'>";
    echo "\n        <input type='submit' value='".phphoto_text($db, 'button', 'create')."'>";
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Handles the create tag form
 */
function phphoto_create_tag($db) {
    if (isset($_POST['name'])) {
        $name = $_POST['name'];

        $sql = sprintf("SELECT COUNT(id) AS exist FROM tags WHERE name = '%s';",
                mysql_real_escape_string($name, $db));
        $result = phphoto_db_query($db, $sql);
        $tag_exists = ($result[0]['exist'] == 1);
        if ($tag_exists) {
            phphoto_popup_message(phphoto_text($db, 'tag', 'exists', $name), 'warning');
        }
        else {
            $sql = sprintf("INSERT INTO tags (name, created) VALUES ('%s', NOW())",
                    mysql_real_escape_string($name, $db));
            if (phphoto_db_query($db, $sql) == 1)
                phphoto_popup_message(phphoto_text($db, 'tag', 'created', $name), 'info');
            else
                phphoto_popup_message(phphoto_text($db, 'tag', 'store_error', $name), 'error');
        }
    }
    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'tag', 'create')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_TAG."'>";
    echo "\n        <input type='input' name='name' maxlength='255'>";
    echo "\n        <input type='submit' value='".phphoto_text($db, 'button', 'create')."'>";
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Handles the regenerate image thumbnails form
 */
function phphoto_image_thumbnails($db) {
    if(isset($_POST['regenerate_image_thumbs'])) {
        $regenerated_thumbnails = phphoto_regenerate_image_thumbnails($db);
        phphoto_popup_message(phphoto_text($db, 'image', 'thumbs_regenerated', $regenerated_thumbnails), 'info');
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'image', 'regenerate_thumbs')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_IMAGE."'>";
    echo "\n        <input type='submit' name='regenerate_image_thumbs' value='".phphoto_text($db, 'button', 'start')."'>";
    echo "\n        <br>";
    echo "\n        ".phphoto_text($db, 'image', 'note_long_time');
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Handles the regenerate gallery thumbnail form
 */
function phphoto_gallery_thumbnail($db, $gallery_id) {
    if(isset($_POST['regenerate_gallery_thumb'])) {
        if (phphoto_regenerate_gallery_thumbnail($db, $gallery_id)) {
            phphoto_popup_message(phphoto_text($db, 'gallery', 'thumb_regenerated'), 'info');
        }
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'gallery', 'regenerate_thumb')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE."?".GET_KEY_ADMIN_QUERY."=".GET_VALUE_ADMIN_GALLERY."&".GET_KEY_GALLERY_ID."=$gallery_id'>";
    echo "\n        <input type='submit' name='regenerate_gallery_thumb' value='".phphoto_text($db, 'button', 'start')."'>";
    echo "\n    </form>";
    echo "\n    <p>".phphoto_text($db, 'gallery', 'note_long_time')."</p>";
    echo "\n</div>";
}

/*
 * The default page of the admin pages
 */
function phphoto_echo_admin_default($db) {
    echo "\n<div class='admin'>";
    echo "\n    <h1>".GALLERY_NAME."</h1>";
    echo "\n    <p>";
    echo "\n        ".phphoto_text($db, 'info', 'version', GALLERY_VERSION)."<br>";
    echo "\n        ".phphoto_text($db, 'info', 'last_updated', GALLERY_DATE)."<br>";
    echo "\n        ".phphoto_text($db, 'info', 'developers', GALLERY_DEVELOPERS)."<br>";
    echo "\n    </p>";
    echo "\n    <p>";
    echo "\n        <a href='http://jigsaw.w3.org/css-validator/check/referer'>";
    echo "\n            <img src='http://jigsaw.w3.org/css-validator/images/vcss' alt='Valid CSS!' />";
    echo "\n        </a>";
    echo "\n    </p>";
    echo "\n</div>";
}

/*
 * Page which lists all the cameras used in the images
 */
function phphoto_echo_admin_cameras($db) {
    $sql = phphoto_sql_exif_values('Model');
    $table_data = array();

    $header = array(
        phphoto_text($db, 'header', 'model'),
        phphoto_text($db, 'header', 'crop_factor'),
        phphoto_text($db, 'header', 'images')
    );

    foreach (phphoto_db_query($db, $sql) as $row) {
        $model = $row['ExifValue'];
        if (strlen($model) > 0) {
            $sql = "SELECT crop_factor FROM cameras WHERE model = '$model'";
            $result = phphoto_db_query($db, $sql);
            $crop_factor = (count($result) == 1) ? $result[0]['crop_factor'] : '-';

            $sql = phphoto_sql_exif_images('Model', $model);
            $images = count(phphoto_db_query($db, $sql));

            array_push($table_data, array($model, $crop_factor, $images));
        }
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'section', 'cameras')."</h1>";
    phphoto_to_html_table($table_data, $header);
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Form for updating an existing gallery
 */
function phphoto_echo_admin_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id)); // prevent SQL injections

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if (isset($_REQUEST[GET_KEY_IMAGE_ID]) && is_numeric($_REQUEST[GET_KEY_IMAGE_ID])) {
            // operate on image in gallery
            $image_id = $_REQUEST[GET_KEY_IMAGE_ID];
            assert(is_numeric($image_id)); // prevent SQL injections
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE) {
                // add image to gallery
                $sql = "INSERT INTO image_to_gallery (gallery_id, image_id, created) VALUES ($gallery_id, $image_id, NOW())";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'gallery', 'image_added'), 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // remove image from gallery
                $sql = "DELETE FROM image_to_gallery WHERE gallery_id = $gallery_id AND image_id = $image_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'gallery', 'image_removed'), 'info');
                }
            }
        }
        else {
            if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
                // update gallery
                $title = $_POST['title'];
                $description = $_POST['description'];

                $sql = sprintf("UPDATE galleries SET title = '%s', description = '%s' WHERE id = %s",
                        mysql_real_escape_string($title, $db),
                        mysql_real_escape_string($description, $db),
                        $gallery_id);
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'gallery', 'updated'), 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // delete gallery
                $sql = "DELETE FROM galleries WHERE id = $gallery_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'gallery', 'deleted'), 'info');
                    phphoto_echo_admin_galleries($db);
                    return;
                }
                else {
                    phphoto_popup_message(phphoto_text($db, 'gallery', 'delete_error'), 'error');
                }
            }
        }
    }

    $sql = "SELECT id, title, description, views, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images, changed, created FROM galleries WHERE id = $gallery_id";
    $gallery_data = phphoto_db_query($db, $sql);

    if (count($gallery_data) != 1) {
        phphoto_popup_message(phphoto_text($db, 'gallery', 'unknown'), 'error');
        echo "\n</div>";
        return;
    }
    $gallery_data = $gallery_data[0];

    phphoto_gallery_thumbnail($db, $gallery_id);

    $table_data = array();
    array_push($table_data, array('&nbsp;',                                     "<img src='image.php?".GET_KEY_GALLERY_ID."=".$gallery_id."' />"));
    array_push($table_data, array(phphoto_text($db, 'header', 'views'),         $gallery_data['views']));
    array_push($table_data, array(phphoto_text($db, 'header', 'images'),        $gallery_data['images']));
    array_push($table_data, array(phphoto_text($db, 'header', 'title'),         "<input type='input' name='title' maxlength='255' value='$gallery_data[title]'>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'description'),   "<textarea name='description'>$gallery_data[description]</textarea>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'changed'),       format_date_time($gallery_data['changed'])));
    array_push($table_data, array(phphoto_text($db, 'header', 'created'),       format_date_time($gallery_data['created'])));
    array_push($table_data, array('&nbsp;',                                     "<input type='submit' value='".phphoto_text($db, 'button', 'update')."'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'gallery', 'edit')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_GALLERY_ID."=$gallery_id'>";
    phphoto_to_html_table($table_data);
    echo "\n    </form>";
    echo "\n</div>";

    // images not in this gallery
    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'gallery', 'images_not_in')."</h1>";
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
        echo "\n        <input type='submit' value='".phphoto_text($db, 'button', 'add')."'>";
        echo "\n    </form>";
    }
    echo "\n</div>";

    // images in this gallery
    $sql = "SELECT id, title, description, filename FROM images WHERE id IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)";

    $header = array(
        phphoto_text($db, 'header', 'thumbnail'),
        phphoto_text($db, 'header', 'filename'),
        phphoto_text($db, 'header', 'title'),
        phphoto_text($db, 'header', 'description'),
        '&nbsp;'
    );
    $images = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($images, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t' /></a>",
            $row['filename'],
            $row['title'],
            $row['description'],
            "<a href='".CURRENT_PAGE.'?'.
                    GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.
                    GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.
                    GET_KEY_GALLERY_ID.'='.$gallery_id.'&'.
                    GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png' /></a>"
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'gallery', 'images_in')."</h1>";
    phphoto_to_html_table($images, $header);

    echo "\n</div>";
}

/*
 * Table showing all galleries available for editing
 */
function phphoto_echo_admin_galleries($db) {
    phphoto_create_gallery($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;
    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    assert(is_numeric($items_per_page)); // prevent SQL injections
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    assert(is_numeric($page_number)); // prevent SQL injections

    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM galleries";
    $pages = phphoto_db_query($db, $sql);
    $pages = ($pages[0]['pages'] > 0) ? $pages[0]['pages'] : 1;

    $sql = sprintf("
        SELECT
            id,
            title,
            views,
            views / (SELECT SUM(views) FROM galleries) AS popularity,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images
        FROM
            galleries
        ORDER BY
            %s
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page",
            mysql_real_escape_string($order_by, $db)
    );

    $header = array(
        phphoto_text($db, 'header', 'thumbnail'),
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=2'>".phphoto_text($db, 'header', 'title')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=3'>".phphoto_text($db, 'header', 'views')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=5'>".phphoto_text($db, 'header', 'images')."</a>",
        '&nbsp;'
    );

    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_GALLERY_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_GALLERY_ID."=$row[id]' /></a>",
            format_string($row['title']),
            $row['views']." (".round($row['popularity']*100)."%)",
            $row['images'],
            ((!$row['images']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_GALLERY_ID."=$row[id]'><img src='./icons/process-stop.png' /></a>" : "<img src='./icons/process-stop-inactive.png' />")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'section', 'galleries')."</h1>";

    $url_previous = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1);
    $url_next = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1);
    $footer = array( phphoto_page_numbering($db, $page_number, $pages, $url_previous, $url_next) );

    phphoto_to_html_table($data, $header, $footer);

    echo "\n</div>";
}

/*
 * Form for updating an existing tag
 */
function phphoto_echo_admin_tag($db, $tag_id) {
    assert(is_numeric($tag_id)); // prevent SQL injections

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if (isset($_REQUEST[GET_KEY_IMAGE_ID]) && is_numeric($_REQUEST[GET_KEY_IMAGE_ID])) {
            // operate on image in tag
            $image_id = $_REQUEST[GET_KEY_IMAGE_ID];
            assert(is_numeric($image_id)); // prevent SQL injections
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE) {
                // add image to tag
                $sql = "INSERT INTO image_to_tag (tag_id, image_id, created) VALUES ($tag_id, $image_id, NOW())";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'tag', 'image_added'), 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // remove image from tag
                $sql = "DELETE FROM image_to_tag WHERE tag_id = $tag_id AND image_id = $image_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'tag', 'image_removed'), 'info');
                }
            }
        }
        else {
            if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['name'])) {
                // update tag
                $name = $_POST['name'];
                $sql = sprintf("UPDATE tags SET name = '%s' WHERE id = %s",
                        mysql_real_escape_string($name, $db),
                        $tag_id);
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'tag', 'updated'), 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // delete tag
                $sql = "DELETE FROM tags WHERE id = $tag_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message(phphoto_text($db, 'tag', 'deleted'), 'info');
                    phphoto_echo_admin_tags($db);
                    return;
                }
                else {
                    phphoto_popup_message(phphoto_text($db, 'tag', 'delete_error'), 'error');
                }
            }
        }
    }

    $sql = "SELECT id, name, (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images, changed, created FROM tags WHERE id = $tag_id";
    $tag_data = phphoto_db_query($db, $sql);

    if (count($tag_data) != 1) {
        phphoto_popup_message(phphoto_text($db, 'tag', 'unknown'), 'error');
        echo "\n</div>";
        return;
    }
    $tag_data = $tag_data[0];

    $table_data = array();
    array_push($table_data, array(phphoto_text($db, 'header', 'name'),      "<input type='input' name='name' maxlength='255' value='$tag_data[name]'>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'changed'),   format_date_time($tag_data['changed'])));
    array_push($table_data, array(phphoto_text($db, 'header', 'created'),   format_date_time($tag_data['created'])));
    array_push($table_data, array('&nbsp;',         "<input type='submit' value='".phphoto_text($db, 'button', 'update')."'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'tag', 'edit')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_TAG_ID."=$tag_id'>";
    phphoto_to_html_table($table_data);
    echo "\n    </form>";
    echo "\n</div>";

    // images not tagged with this tag
    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'tag', 'not_tagged_images')."</h1>";
    $sql = "SELECT id, title, filename FROM images WHERE id NOT IN (SELECT image_id FROM image_to_tag WHERE tag_id = $tag_id)";
    $images = phphoto_db_query($db, $sql);
    if (count($images) > 0) {
        echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
                GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.
                GET_KEY_OPERATION.'='.GET_VALUE_CREATE.'&'.
                GET_KEY_TAG_ID."=$tag_id'>";
        echo "\n        <select name='".GET_KEY_IMAGE_ID."'>";
        foreach ($images as $row) {
            echo "\n            <option value='$row[id]'>".((empty($row['title'])?$row['filename']:$row['title']))."</option>";
        }
        echo "\n        </select>";
        echo "\n        <input type='submit' value='".phphoto_text($db, 'button', 'add')."'>";
        echo "\n    </form>";
    }
    echo "\n</div>";

    // images tagged
    $sql = "SELECT id, title, description, filename FROM images WHERE id IN (SELECT image_id FROM image_to_tag WHERE tag_id = $tag_id)";

    $header = array(
        phphoto_text($db, 'header', 'thumbnail'),
        phphoto_text($db, 'header', 'filename'),
        phphoto_text($db, 'header', 'title'),
        phphoto_text($db, 'header', 'description'),
        '&nbsp;'
    );
    $images = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($images, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t' /></a>",
            $row['filename'],
            $row['title'],
            $row['description'],
            "<a href='".CURRENT_PAGE.'?'.
                    GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.
                    GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.
                    GET_KEY_TAG_ID.'='.$tag_id.'&'.
                    GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png' /></a>"
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'tag', 'tagged_images')."</h1>";
    phphoto_to_html_table($images, $header);

    echo "\n</div>";
}

/*
 * Table showing all tags available for editing
 */
function phphoto_echo_admin_tags($db) {
    phphoto_create_tag($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;
    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    assert(is_numeric($items_per_page)); // prevent SQL injections
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    assert(is_numeric($page_number)); // prevent SQL injections

    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM tags";
    $pages = phphoto_db_query($db, $sql);
    $pages = ($pages[0]['pages'] > 0) ? $pages[0]['pages'] : 1;

    $sql = sprintf("
        SELECT
            id,
            name,
            (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images
        FROM
            tags
        ORDER BY
            %s
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page",
                mysql_real_escape_string($order_by, $db)
    );

    $header = array(
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_SORT_COLUMN."=2'>".phphoto_text($db, 'header', 'name')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_SORT_COLUMN."=3'>".phphoto_text($db, 'header', 'images')."</a>",
        '&nbsp;'
    );

    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_TAG_ID."=$row[id]'>".format_string($row['name'])."</a>",
            $row['images'],
            ((!$row['images']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_TAG_ID."=$row[id]'><img src='./icons/process-stop.png' /></a>" : "<img src='./icons/process-stop-inactive.png' />")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'section', 'tags')."</h1>";

    $url_previous = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1);
    $url_next = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1);
    $footer = array( phphoto_page_numbering($db, $page_number, $pages, $url_previous, $url_next) );

    phphoto_to_html_table($data, $header, $footer);

    echo "\n</div>";
}

/*
 * Form for updating an existing image
 */
function phphoto_echo_admin_image($db, $image_id) {
    assert(is_numeric($image_id)); // prevent SQL injections

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
            // update image
            $title = $_POST['title'];
            $description = $_POST['description'];
            $sql = sprintf("UPDATE images SET title = '%s', description = '%s' WHERE id = %s",
                    mysql_real_escape_string($title, $db),
                    mysql_real_escape_string($description, $db),
                    $image_id);
            if (phphoto_db_query($db, $sql) == 1) {
                phphoto_popup_message(phphoto_text($db, 'image', 'updated'), 'info');
            }
        }
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_IMAGE_ID])) {
            // delete image
            $sql = "DELETE FROM images WHERE id = $image_id";
            if (phphoto_db_query($db, $sql) == 1) {
                phphoto_popup_message(phphoto_text($db, 'image', 'deleted'), 'info');
                phphoto_echo_admin_images($db);
                return;
            }
            else {
                phphoto_popup_message(phphoto_text($db, 'image', 'delete_error'), 'error');
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
        phphoto_popup_message(phphoto_text($db, 'image', 'unknown'), 'error');
        echo "\n</div>";
        return;
    }

    $gallery_names = array();
    foreach ($gallery_data as $gallery)
        array_push($gallery_names, "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_GALLERY_ID."=$gallery[id]'>".format_string($gallery['title'])."</a>");

    $tag_names = array();
    foreach ($tag_data as $tag)
        array_push($tag_names, "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_TAG_ID."=$tag[id]'>".format_string($tag['name'])."</a>");

    $image_data = $image_data[0];
    if ($image_data['exif'])
        eval('$exif = ' . $image_data['exif'] . ';');
    else
        $exif = array();

    $table_data = array();
    array_push($table_data, array('&nbsp;',                                     "<a href='image.php?".GET_KEY_IMAGE_ID.'='.$image_id.'&'.GET_KEY_ADMIN_QUERY."=preview'><img src='image.php?".GET_KEY_IMAGE_ID.'='.$image_id."t' /></a>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'filename'),      $image_data['filename']));
    array_push($table_data, array(phphoto_text($db, 'header', 'format'),        image_type_to_mime_type($image_data['type'])));
    array_push($table_data, array(phphoto_text($db, 'header', 'filesize'),      format_byte($image_data['filesize'])));
    array_push($table_data, array(phphoto_text($db, 'header', 'resolution'),    $image_data['width'].'x'.$image_data['height'].' ('.
                                                    phphoto_image_aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array(phphoto_text($db, 'header', 'camera'),        "<img src='./icons/camera-photo.png' />&nbsp;&nbsp;&nbsp;".format_camera_model($exif)));
    array_push($table_data, array(phphoto_text($db, 'header', 'settings'),      "<img src='./icons/image-x-generic.png' />&nbsp;&nbsp;&nbsp;".format_camera_settings($exif)));
    array_push($table_data, array(phphoto_text($db, 'header', 'galleries'),     implode('<br>', $gallery_names)));
    array_push($table_data, array(phphoto_text($db, 'header', 'tags'),          implode('<br>', $tag_names)));
    array_push($table_data, array(phphoto_text($db, 'header', 'title'),         "<input type='input' name='title' maxlength='255' value='$image_data[title]'>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'description'),   "<textarea name='description'>$image_data[description]</textarea>"));
    array_push($table_data, array(phphoto_text($db, 'header', 'changed'),       format_date_time($image_data['changed'])));
    array_push($table_data, array(phphoto_text($db, 'header', 'created'),       format_date_time($image_data['created'])));
    array_push($table_data, array('&nbsp;',         "<input type='submit' value='".phphoto_text($db, 'button', 'update')."'>"));

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'image', 'edit')."</h1>";
    echo "\n    <form method='post' action='".CURRENT_PAGE.'?'.
            GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.
            GET_KEY_OPERATION.'='.GET_VALUE_UPDATE.'&'.
            GET_KEY_IMAGE_ID."=$image_id'>";
    phphoto_to_html_table($table_data);
    echo "\n    </form>";
    echo "\n</div>";
}

/*
 * Table showing all images available for editing
 */
function phphoto_echo_admin_images($db) {
    phphoto_upload_image($db);
    phphoto_image_thumbnails($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;
    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    assert(is_numeric($items_per_page)); // prevent SQL injections
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    assert(is_numeric($page_number)); // prevent SQL injections

    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM images";
    $pages = phphoto_db_query($db, $sql);
    $pages = ($pages[0]['pages'] > 0) ? $pages[0]['pages'] : 1;

    $sql = sprintf("
        SELECT
            id,
            IF (LENGTH(title) > 0, title, filename) AS name,
            width,
            height,
            filesize,
            views,
            views / (SELECT SUM(views) FROM images) AS popularity,
            (SELECT COUNT(*) FROM image_to_gallery WHERE image_id = id) AS in_use
        FROM
            images
        ORDER BY
            %s
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page",
                mysql_real_escape_string($order_by, $db)
    );

    $header = array(
        phphoto_text($db, 'header', 'thumbnail'),
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=2'>".phphoto_text($db, 'header', 'name')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=3'>".phphoto_text($db, 'header', 'resolution')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=5'>".phphoto_text($db, 'header', 'filesize')."</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=6'>".phphoto_text($db, 'header', 'views')."</a>",
        '&nbsp;'
    );
    $max_text_length = 12;
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'>
                    <img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t' /></a>",
            wordwrap(format_string($row['name']), 20, '<br>', true),
            $row['width'].'x'.$row['height'].'<br>'.phphoto_image_aspect_ratio($row['width'], $row['height']),
            format_byte($row['filesize']),
            $row['views']." (".round($row['popularity']*100)."%)",
            ((!$row['in_use']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png' /></a>" : "<img src='./icons/process-stop-inactive.png' />")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>".phphoto_text($db, 'section', 'images')."</h1>";

    $url_previous = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number - 1);
    $url_next = CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_PAGE_NUMBER.'='.($page_number + 1);
    $footer = array( phphoto_page_numbering($db, $page_number, $pages, $url_previous, $url_next) );

    phphoto_to_html_table($data, $header, $footer);

    echo "\n</div>";
}

?>