<?php

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
        echo "\n            <img class='thumbnail' src='image.php?".GET_KEY_IMAGE_ID."=$image[id]t' title='$image[description]' alt='$image[name]'>";
        echo "\n        </a>";
        echo "\n        <h2>$exif</h2>";
        echo "\n        <h1>".format_string($gallery['title'], 30)."</h1>";
        echo "\n        <p>".format_string($gallery['description'])."</p>";
        echo "\n    </div>";
    }
    echo "\n</div>";
    echo "\n<div class='footer'>";
    echo "\n    <p>..:: $gallery[views] views :: $gallery[images] images :: updated " . format_date_time($gallery['changed']) . " ::</p>";
    echo "\n</div>";

//~ $query = sprintf("SELECT firstname, lastname, address, age FROM friends WHERE firstname='%s' AND lastname='%s'",
    //~ mysql_real_escape_string($firstname),
    //~ mysql_real_escape_string($lastname));
}

function phphoto_echo_galleries($db) {
    $gallery_sql = "
        SELECT
            id,
            title,
            description,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images,
            (SELECT MAX(changed) FROM image_to_gallery WHERE gallery_id = id) AS changed
        FROM
            galleries g
        WHERE
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = g.id) > 0
    ";
    /*$gallery_sql = "
        SELECT
            id,
            title,
            description,
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images,
            (SELECT MAX(temp.changed) FROM (
                    (SELECT changed FROM galleries foo WHERE id = g.id)
                    UNION
                    (SELECT changed FROM image_to_gallery WHERE gallery_id = g.id)
                    UNION
                    (SELECT changed FROM images c WHERE id IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = g.id))
                ) AS temp
            ) AS changed
        FROM
            galleries g
        WHERE
            (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = g.id) > 0
    ";


    /*$header = array('Title', 'Description', 'Images', 'Updated');
    $data = array();
    foreach (phphoto_db_query($db, $gallery_sql) as $row) {
        array_push($data, array(
            "<a href='index.php?".GET_KEY_GALLERY_ID."=".$row['id']."'>".$row['title']."</a>",
            $row['description'],
            $row['images'],
            format_date_time($row['changed'])
        ));
    }
    phphoto_to_html_table($header, $data);*/

    echo "\n<div class='header'>";
    echo "\n    <p><a href='".GALLERY_INDEX_PAGE."'>".GALLERY_TITLE."</a></p>";
    echo "\n</div>";
    echo "\n<div class='container'>";
    echo "\n    <h1>Galleries</h1>";
    foreach (phphoto_db_query($db, $gallery_sql) as $gallery) {
        echo "\n    <div class='gallery'>";
        echo "\n        <a href='index.php?".GET_KEY_GALLERY_ID."=$gallery[id]'>";
        echo "\n        <img class='thumbnail' src='image.php?".GET_KEY_GALLERY_ID."=$gallery[id]' title='$gallery[description]' alt='$gallery[title]'>";
        echo "\n        <h1>".format_string($gallery['title'], 30)."</h1>";
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