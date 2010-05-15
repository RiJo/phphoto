<?php

function phphoto_echo_gallery($db, $gallery_id) {
    $gallery_sql = "SELECT title, description FROM galleries WHERE id = $gallery_id";
    $images_sql = "
        SELECT
            images.id,
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

    phphoto_db_query($db, "UPDATE galleries SET views = views + 1 WHERE id = $gallery_id");

    $gallery = phphoto_db_query($db, $gallery_sql);
    $images = phphoto_db_query($db, $images_sql);

    $title = $gallery[0]['title'];
    $description = $gallery[0]['description'];

    echo "\n<div class='gallery'><h1>$title</h1><p>$description</p>";
    foreach ($images as $image) {
        $image_id_full = $image['id'];
        $image_id_thumbnail = $image['id'] . "t";
        $image_name = $image['name'];
        $image_description = $image['description'];

        echo "\n    <div class='thumbnail'>";
        echo "\n        <a href='image.php?".GET_KEY_IMAGE_ID."=$image_id_full'>";
        echo "\n        <img src='image.php?".GET_KEY_IMAGE_ID."=$image_id_thumbnail' title='$image_description'>";
        echo "\n        </a>";
        echo "\n        <br>";
        echo "\n        <p>$image_name</p>";
        echo "\n    </div>";
    }
    echo "\n</div>";


//~ $query = sprintf("SELECT firstname, lastname, address, age FROM friends WHERE firstname='%s' AND lastname='%s'",
    //~ mysql_real_escape_string($firstname),
    //~ mysql_real_escape_string($lastname));
}

function phphoto_echo_galleries($db) {
    $sql = 'SELECT id, title, description, (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) AS images, changed FROM galleries WHERE (SELECT COUNT(*) FROM image_to_gallery WHERE gallery_id = id) > 0';

    $header = array('Title', 'Description', 'Images', 'Updated');
    $data = array();
    foreach (phphoto_db_query($db, $sql) as $row) {
        array_push($data, array(
            "<a href='index.php?".GET_KEY_GALLERY_ID."=".$row['id']."'>".$row['title']."</a>",
            $row['description'],
            $row['images'],
            format_date_time($row['changed'])
        ));
    }
    phphoto_to_html_table($header, $data);
}

?>