<?php

/*
 * Prints out the given gallery
 */
function phphoto_echo_gallery($db, $gallery_id) {

    // update views counter
    if (!isset($_SESSION[SESSION_KEY_VIEWS]) || !isset($_SESSION[SESSION_KEY_VIEWS]["g$gallery_id"])) {
        phphoto_db_query($db, "UPDATE galleries SET views = views + 1 WHERE id = $gallery_id");
        $_SESSION[SESSION_KEY_VIEWS]["g$gallery_id"] = SESSION_VALUE_VIEWS;
    }

    $gallery_sql = "
        SELECT
            title,
            description,
            views,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images,
            (SELECT MAX(changed) FROM image_to_gallery WHERE gallery_id = id) AS changed
        FROM
            galleries
        WHERE
            id = $gallery_id
    ";
    $images_sql = "
        SELECT
            id,
            exif,
            IF (LENGTH(title) > 0, title, filename) AS name,
            description
        FROM
            images
                INNER JOIN
            image_to_gallery
                ON
            (images.id = image_to_gallery.image_id)
        WHERE
            image_to_gallery.gallery_id = $gallery_id
        ORDER BY
            name
    ";

    $gallery = phphoto_db_query($db, $gallery_sql);
    $images = phphoto_db_query($db, $images_sql);

    if (count($gallery) != 1) {
        echo "\n<div class='error'>Unkown gallery</div>";
        return;
    }

    $gallery = $gallery[0];

    echo "\n<div class='header'>";
    echo "\n    <p><a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a></p>";
    echo "\n</div>";
    echo "\n<div class='container'>";
    echo "\n    <h1>".format_string($gallery['title'])."</h1>";
    echo "\n    <p>".format_string($gallery['description'])."</p>";
    foreach ($images as $image) {
        if ($image['exif']) {
            eval('$exif = ' . $image['exif'] . ';');
            $exif = format_camera_settings($exif);
        }
        if (!$image['exif'] || !$exif) {
            $exif = NO_EXIF_DATA;
        }
        echo "\n    <div class='image'>";
        echo "\n        <a href='image.php?".GET_KEY_IMAGE_ID."=$image[id]'>";
        echo "\n            <img class='thumbnail' src='image.php?".GET_KEY_IMAGE_ID."=$image[id]t' title='$image[description]' alt='$image[name]' />";
        echo "\n        </a>";
        echo "\n        <h2>$exif</h2>";
        echo "\n        <h1>".format_string($image['name'], 30)."</h1>";
        echo "\n        <p>".format_string($image['description'])."</p>";
        echo "\n    </div>";
    }
    echo "\n</div>";
    echo "\n<div class='footer'>";
    echo "\n    <p>..:: $gallery[views] views :: $gallery[images] images :: updated " . format_date_time($gallery['changed']) . " ::</p>";
    echo "\n</div>";
}

/*
 * Prints out all galleries with more than 0 images
 */
function phphoto_echo_galleries($db) {
    $gallery_sql = "
        SELECT
            id,
            title,
            description,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = g.id) AS images,
            (SELECT MAX(changed) FROM image_to_gallery WHERE gallery_id = g.id) AS changed
        FROM
            galleries g
        WHERE
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = g.id) > 0
    ";

    echo "\n<div class='header'>";
    echo "\n    <p><a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a></p>";
    echo "\n</div>";
    echo "\n<div class='container'>";
    echo "\n    <h1>".GALLERY_WELCOME."</h1>";
    foreach (phphoto_db_query($db, $gallery_sql) as $gallery) {
        echo "\n    <div class='gallery'>";
        echo "\n        <a href='".CURRENT_PAGE."?".GET_KEY_GALLERY_ID."=$gallery[id]'>";
        echo "\n        <img class='thumbnail' src='image.php?".GET_KEY_GALLERY_ID."=$gallery[id]' title='$gallery[description]' alt='$gallery[title]' />";
        echo "\n        <h1>".format_string($gallery['title'], 30)."</h1>";
        echo "\n        <h2>updated ".format_date_time($gallery['changed'])."</h2>";
        echo "\n        <p>".format_string($gallery['description'])."</p>";
        echo "\n        </a>";
        echo "\n    </div>";
    }
    echo "\n</div>";
    echo "\n<div class='footer'>";
    echo "\n    <p>..:: <a href='http://github.com/RiJo/phphoto'>" . GALLERY_NAME.' v.'.GALLERY_VERSION . "</a> ::</p>";
    echo "\n</div>";
}

?>