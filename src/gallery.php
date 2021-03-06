<?php

function phphoto_echo_gallery_header($content) {
    echo "\n<div class='header'>";
    echo "\n    <p>$content</p>";
    echo "\n</div>";
}

function phphoto_echo_gallery_footer($content) {
    echo "\n<div class='footer'>";
    echo "\n    <p>..:: $content ::</p>";
    echo "\n</div>";
}

function phphoto_echo_gallery_images($db, $title, $description, $images) {
    echo "\n<div class='container'>";
    echo "\n    <h1>".format_string($title)."</h1>";
    echo "\n    <p>".format_string($description)."</p>";
    echo "\n    <div class='wrapper'>";
    foreach ($images as $image) {
        if ($image['exif']) {
            eval('$exif = ' . $image['exif'] . ';');
            $exif = format_camera_settings($exif);
        }
        if (!$image['exif'] || !$exif) {
            $exif = phphoto_text($db, 'common', 'no_exif_data');
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
    echo "\n    </div>";
    echo "\n</div>";
}

/*
 * Prints out the given gallery
 */
function phphoto_echo_gallery($db, $gallery_id) {
    assert(is_numeric($gallery_id)); // prevent SQL injections

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
            (images.id = image_to_gallery.image_id AND active = TRUE)
        WHERE
            image_to_gallery.gallery_id = $gallery_id
        ORDER BY
            ".IMAGE_SORT_COLUMN."
    ";

    $gallery = phphoto_db_query($db, $gallery_sql);
    $images = phphoto_db_query($db, $images_sql);

    phphoto_echo_gallery_header("<a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a>");

    if (count($gallery) == 1) {
        $gallery = $gallery[0];

        phphoto_echo_gallery_images($db, $gallery['title'], $gallery['description'], $images);
        phphoto_echo_gallery_footer(
                phphoto_text($db, 'footer', 'views', $gallery['views'])." :: ".
                phphoto_text($db, 'footer', 'images', $gallery['images'])." :: ".
                phphoto_text($db, 'footer', 'updated', format_date_time($gallery['changed']))
        );
    }
    else {
        echo "\n<div class='container'>".phphoto_text($db, 'gallery', 'unknown')."</div>";
        phphoto_echo_gallery_footer('');
    }
}

function phphoto_echo_tag($db, $tag_id) {
    assert(is_numeric($tag_id)); // prevent SQL injections

    $tag_sql = "
        SELECT
            name,
            description,
            (SELECT COUNT(*) FROM image_to_tag WHERE tag_id = id) AS images,
            (SELECT MAX(changed) FROM image_to_tag WHERE tag_id = id) AS changed
        FROM
            tags
        WHERE
            id = $tag_id
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
            image_to_tag
                ON
            (images.id = image_to_tag.image_id AND active = TRUE)
        WHERE
            image_to_tag.tag_id = $tag_id
        ORDER BY
            ".IMAGE_SORT_COLUMN."
    ";

    $tag = phphoto_db_query($db, $tag_sql);
    $images = phphoto_db_query($db, $images_sql);

    phphoto_echo_gallery_header("<a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a>");

    if (count($tag) == 1) {
        $tag = $tag[0];

        phphoto_echo_gallery_images($db, $tag['name'], $tag['description'], $images);
        phphoto_echo_gallery_footer(
                phphoto_text($db, 'footer', 'images', $tag['images'])." :: ".
                phphoto_text($db, 'footer', 'updated', format_date_time($tag['changed']))
        );
    }
    else {
        echo "\n<div class='container'>".phphoto_text($db, 'tag', 'unknown')."</div>";
        phphoto_echo_gallery_footer('');
    }
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
            
            (SELECT MAX(changed) FROM
                (
                    (SELECT temp1.changed, temp1.id AS gallery_id FROM galleries temp1)
                    UNION
                    (SELECT temp2.changed, temp2.gallery_id FROM image_to_gallery temp2)
                    UNION
                    (SELECT (SELECT changed FROM images WHERE id = temp3.image_id) AS changed, temp3.gallery_id FROM image_to_gallery temp3)
                ) temp
                WHERE
                    gallery_id = g.id
            ) AS changed
        FROM
            galleries g
        WHERE
            active = TRUE
            AND
            (
                SELECT COUNT(*) FROM image_to_gallery itg WHERE itg.gallery_id = g.id
                AND
                (
                    SELECT COUNT(*) FROM images i WHERE i.id = itg.image_id AND active = TRUE
                ) > 0
            ) > 0
        ORDER BY
            ".GALLERY_SORT_COLUMN."
    ";

    $tag_sql = "
        SELECT
            id,
            name,
            description
        FROM
            tags t
        WHERE
            active = TRUE
            AND
            (
                SELECT COUNT(*) FROM image_to_tag itt WHERE itt.tag_id = t.id
                AND
                (
                    SELECT COUNT(*) FROM images i WHERE i.id = itt.image_id AND active = TRUE
                ) > 0
            ) > 0
        ORDER BY
            name
    ";

    echo "\n<div class='header'>";
    echo "\n    <p><a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a></p>";
    echo "\n</div>";
    echo "\n<div class='container'>";
    echo "\n    <h1>".GALLERY_WELCOME."</h1>";
    echo "\n    <div class='wrapper'>";
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
    echo "\n    </div>";

    // echo links for the different tags
    $tags = array();
    foreach (phphoto_db_query($db, $tag_sql) as $tag) {
        array_push($tags, "<a href='".CURRENT_PAGE."?".GET_KEY_TAG_ID."=$tag[id]' title='$tag[description]'>$tag[name]</a>");
    }
    if (count($tags) > 0) {
        echo "\n    <p>".phphoto_text($db, 'section', 'tags').": ".implode(', ', $tags).'</p>';
    }

    echo "\n</div>";

    phphoto_echo_gallery_footer("<a href='http://github.com/RiJo/phphoto'>" . GALLERY_NAME.' v.'.GALLERY_VERSION . "</a>");
}

?>