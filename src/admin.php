<?php

function phphoto_upload_image() {
    global $allowed_filetypes;
    if(isset($_FILES['image'])) {
        $uploaded_image = $_FILES['image'];
        $extension = end(explode('.', $uploaded_image['name']));
        $filesize = filesize($uploaded_image['tmp_name']);
        $replace_existing = (isset($_POST['replace']) && $_POST['replace'] == 'true');

        if (!in_array(strtolower($extension), $allowed_filetypes)) {
            phphoto_popup_message("Not a valid filetype: $extension", 'error');
        }
        elseif (!is_numeric($filesize) || $filesize > IMAGE_MAX_FILESIZE) {
            phphoto_popup_message("The file is too big (".format_byte($filesize)."), allowed is less than ".format_byte(IMAGE_MAX_FILESIZE), 'error');
        }
        else {
            $db = phphoto_db_connect();
            $image_id = store_image($db, $uploaded_image, $replace_existing);
            if ($image_id == INVALID_ID) {
                phphoto_popup_message('Could not insert the image in the database', 'error');
            }
            elseif ($image_id == -2) {
                phphoto_popup_message("Filename '$uploaded_image[name]' already exists in database", 'warning');
            }
            else {
                if ($replace_existing)
                    phphoto_popup_message("Image '$uploaded_image[name]' uploaded successfully (replace existing)", 'info');
                else
                    phphoto_popup_message("Image '$uploaded_image[name]' uploaded successfully", 'info');
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

        $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM galleries WHERE title = '$title';");
        $gallery_exists = ($result[0]['exist'] == 1);
        if ($gallery_exists) {
            phphoto_popup_message("Gallery '$title' already exists", 'warning');
        }
        else {
            $sql = "INSERT INTO galleries (title, description, created) VALUES ('$title', '', NOW())";
            if (phphoto_db_query($db, $sql) == 1)
                phphoto_popup_message("Gallery '$title' has has been added", 'info');
            else
                phphoto_popup_message("Could not create gallery '$title'", 'info');
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

        $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM tags WHERE name = '$name';");
        $tag_exists = ($result[0]['exist'] == 1);
        if ($tag_exists) {
            phphoto_popup_message("Tag '$name' already exists", 'warning');
        }
        else {
            $sql = "INSERT INTO tags (name, created) VALUES ('$name', NOW())";
            if (phphoto_db_query($db, $sql) == 1)
                phphoto_popup_message("Tag '$name' has has been added", 'info');
            else
                phphoto_popup_message("Could not create tag '$name'", 'info');
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
        phphoto_popup_message("$regenerated_thumbnails thumbnails have been regenerated", 'info');
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
            phphoto_popup_message('Gallery thumbnail have been regenerated', 'info');
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

function phphoto_echo_admin_default($db) {
    echo "\n<div class='admin'>";
    echo "\n    <h1>".GALLERY_NAME."</h1>";
    echo "\n    version: ".GALLERY_VERSION."<br>";
    echo "\n    updated: ".GALLERY_DATE."<br>";
    echo "\n    developers: ".GALLERY_DEVELOPERS."<br>";
    echo "\n</div>";
}

/*
 * Form for updating an existing gallery
 */
function phphoto_echo_admin_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id));

    // OPERATIONS
    if (isset($_GET[GET_KEY_OPERATION])) {
        if (isset($_REQUEST[GET_KEY_IMAGE_ID]) && is_numeric($_REQUEST[GET_KEY_IMAGE_ID])) {
            // operate on image in gallery
            $image_id = $_REQUEST[GET_KEY_IMAGE_ID];
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE) {
                // add image to gallery
                $sql = "INSERT INTO image_to_gallery (gallery_id, image_id, created) VALUES ($gallery_id, $image_id, NOW())";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Image has has been added', 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // remove image from gallery
                $sql = "DELETE FROM image_to_gallery WHERE gallery_id = $gallery_id AND image_id = $image_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Image has has been removed', 'info');
                }
            }
        }
        else {
            if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['title']) && isset($_POST['description'])) {
                // update gallery
                $title = $_POST['title'];
                $description = $_POST['description'];

                $sql = "UPDATE galleries SET title = '$title', description = '$description' WHERE id = $gallery_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Gallery has been updated', 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // delete gallery
                $sql = "DELETE FROM galleries WHERE id = ".$gallery_id;
                if (phphoto_db_query($db, $sql) == 1) {
                    echo '<meta http-equiv="refresh" content="0;url='.CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'" />';
                    exit();
                }
                else {
                    phphoto_popup_message('Could not remove gallery', 'error');
                }
            }
        }
    }

    phphoto_regenerate_gallery_thumbnail($db, $gallery_id);

    $sql = "SELECT id, title, description, views, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images, changed, created FROM galleries WHERE id = $gallery_id";
    $gallery_data = phphoto_db_query($db, $sql);

    if (count($gallery_data) != 1) {
        phphoto_popup_message("Unknown gallery: $gallery_id", 'error');
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
    echo "\n    <h1>Images not in gallery</h1>";
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
    echo "\n</div>";

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

    echo "\n<div class='admin'>";
    echo "\n    <h1>Images in gallery</h1>";
    phphoto_to_html_table($header, $images);

    echo "\n</div>";
}

/*
 * Table showing all galleries available for editing
 */
function phphoto_echo_admin_galleries($db) {
    phphoto_create_gallery($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;

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
        ORDER BY
            $order_by
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array(
        'Thumbnail',
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=2'>Title</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=3'>Views</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY.'&'.GET_KEY_SORT_COLUMN."=5'>Images</a>",
        '&nbsp;'
    );

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
    echo "\n    <h1>Galleries</h1>";
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
        if (isset($_REQUEST[GET_KEY_IMAGE_ID]) && is_numeric($_REQUEST[GET_KEY_IMAGE_ID])) {
            // operate on image in tag
            $image_id = $_REQUEST[GET_KEY_IMAGE_ID];
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_CREATE) {
                // add image to tag
                $sql = "INSERT INTO image_to_tag (tag_id, image_id, created) VALUES ($tag_id, $image_id, NOW())";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Image has has been added', 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // remove image from tag
                $sql = "DELETE FROM image_to_tag WHERE tag_id = $tag_id AND image_id = $image_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Image has has been removed', 'info');
                }
            }
        }
        else {
            if ($_GET[GET_KEY_OPERATION] == GET_VALUE_UPDATE && isset($_POST['name'])) {
                // update tag
                $name = $_POST['name'];

                $sql = "UPDATE tags SET name = '$name' WHERE id = $tag_id";
                if (phphoto_db_query($db, $sql) == 1) {
                    phphoto_popup_message('Tag has been updated', 'info');
                }
            }
            if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE) {
                // delete tag
                $sql = "DELETE FROM tags WHERE id = ".$tag_id;
                if (phphoto_db_query($db, $sql) == 1) {
                    echo '<meta http-equiv="refresh" content="0;url='.CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'" />';
                    exit();
                }
                else {
                    phphoto_popup_message('Could not remove tag', 'error');
                }
            }
        }
    }

    $sql = "SELECT id, name, (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images, changed, created FROM tags WHERE id = $tag_id";
    $tag_data = phphoto_db_query($db, $sql);

    if (count($tag_data) != 1) {
        phphoto_popup_message("Unknown tag: $tag_id", 'error');
        echo "\n</div>";
        return;
    }
    $tag_data = $tag_data[0];

    $table_data = array();
    array_push($table_data, array('Name',           "<input type='input' name='name' maxlength='255' value='$tag_data[name]'>"));
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

    // images not tagged with this tag
    echo "\n<div class='admin'>";
    echo "\n    <h1>Images not tagged</h1>";
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
        echo "\n        <input type='submit' value='Add'>";
        echo "\n    </form>";
    }
    echo "\n</div>";

    // images tagged
    $sql = "SELECT id, title, description, filename FROM images WHERE id IN (SELECT image_id FROM image_to_tag WHERE tag_id = $tag_id)";

    $header = array('Thumbnail', 'Filename', 'Title', 'Description', '&nbsp;');
    $images = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($images, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_IMAGE_ID."=$row[id]'><img src='image.php?".GET_KEY_IMAGE_ID."=$row[id]t'></a>",
            $row['filename'],
            $row['title'],
            $row['description'],
            "<a href='".CURRENT_PAGE.'?'.
                    GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.
                    GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.
                    GET_KEY_TAG_ID.'='.$tag_id.'&'.
                    GET_KEY_IMAGE_ID."=$row[id]'><img src='./icons/process-stop.png'></a>"
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>Tagged images</h1>";
    phphoto_to_html_table($header, $images);

    echo "\n</div>";
}

/*
 * Table showing all tags available for editing
 */
function phphoto_echo_admin_tags($db) {
    phphoto_create_tag($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;

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
        ORDER BY
            $order_by
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array(
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_SORT_COLUMN."=2'>Name</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_SORT_COLUMN."=3'>Images</a>",
        '&nbsp;'
    );

    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_TAG_ID."=$row[id]'>$row[name]</a>",
            $row['images'],
            ((!$row['images']) ? "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG.'&'.GET_KEY_OPERATION.'='.GET_VALUE_DELETE.'&'.GET_KEY_TAG_ID."=$row[id]'><img src='./icons/process-stop.png'></a>" : "<img src='./icons/process-stop-inactive.png'>")
        ));
    }

    echo "\n<div class='admin'>";
    echo "\n    <h1>Tags</h1>";
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
                phphoto_popup_message('Image has been updated', 'info');
            }
        }
        if($_GET[GET_KEY_OPERATION] == GET_VALUE_DELETE && isset($_GET[GET_KEY_IMAGE_ID])) {
            // delete image
            $sql = "DELETE FROM images WHERE id = ".$image_id;
            if (phphoto_db_query($db, $sql) == 1) {
                echo '<meta http-equiv="refresh" content="0;url='.CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'" />';
                exit();
            }
            else {
                phphoto_popup_message('Could not remove image', 'error');
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
        phphoto_popup_message("Unknown image: $image_id", 'error');
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
    array_push($table_data, array('&nbsp;',         "<a href='image.php?".GET_KEY_IMAGE_ID.'='.$image_id.'&'.GET_KEY_ADMIN_QUERY."=preview'><img src='image.php?".GET_KEY_IMAGE_ID.'='.$image_id."t'></a>"));
    array_push($table_data, array('Filename',       $image_data['filename']));
    array_push($table_data, array('Format',         image_type_to_mime_type($image_data['type'])));
    array_push($table_data, array('Filesize',       format_byte($image_data['filesize'])));
    array_push($table_data, array('Resolution',     $image_data['width'].'x'.$image_data['height'].' ('.
                                                    aspect_ratio($image_data['width'], $image_data['height']).')'));
    array_push($table_data, array('Filename',       $image_data['filename']));
    array_push($table_data, array('EXIF version',   ((isset($exif['ExifVersion'])) ? $exif['ExifVersion'] : VARIABLE_NOT_SET)));
    array_push($table_data, array('Camera',         "<img src='./icons/camera-photo.png'>&nbsp;&nbsp;&nbsp;".format_camera_model($exif)));
    array_push($table_data, array('Settings',       "<img src='./icons/image-x-generic.png'>&nbsp;&nbsp;&nbsp;".format_camera_settings($exif)));
    array_push($table_data, array('Galleries',      implode('<br>', $gallery_names)));
    array_push($table_data, array('Tags',           implode('<br>', $tag_names)));
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
    phphoto_upload_image();
    phphoto_regenerate_image_thumbnails($db);

    $order_by = (isset($_GET[GET_KEY_SORT_COLUMN])) ? $_GET[GET_KEY_SORT_COLUMN] : 2;

    $items_per_page = (isset($_GET[GET_KEY_ITEMS_PER_PAGE])) ? $_GET[GET_KEY_ITEMS_PER_PAGE] : DEFAULT_ITEMS_PER_PAGE;
    $page_number = (isset($_GET[GET_KEY_PAGE_NUMBER])) ? $_GET[GET_KEY_PAGE_NUMBER] : 0;
    $sql = "SELECT CEIL(COUNT(*) / $items_per_page) AS pages FROM images";
    $pages = phphoto_db_query($db, $sql);
    $pages = $pages[0]['pages'];

    $sql = "
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
            $order_by
        LIMIT
            ".($page_number * $items_per_page).", $items_per_page
    ";

    $header = array(
        'Thumbnail',
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=2'>Name</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=3'>Resolution</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=5'>Filesize</a>",
        "<a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE.'&'.GET_KEY_SORT_COLUMN."=6'>Views</a>",
        '&nbsp;'
    );
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
    echo "\n    <h1>Images</h1>";
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