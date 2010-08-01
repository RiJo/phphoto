<?php

/*
 * Adds the image to the database and returns the image ID
 */
function phphoto_store_image($db, $uploaded_image, $replace_existing = false){
    // Validate extension and filesiz
    $image_filename = $uploaded_image['name'];
    $image = $uploaded_image['tmp_name'];

    // Get image data
    $image_filesize = filesize($image);
    $image_info = getimagesize($image);
    $image_width =  $image_info[0];
    $image_height =  $image_info[1];
    $image_type =  $image_info[2];
    // Read exif data
    $exif_temp = exif_read_data($image);
    $exif = phphoto_filter_exif_data($exif_temp);

    // add crop factor (field of view) to exif data
    if (isset($exif['Model'])) {
        $result = phphoto_db_query($db, "SELECT crop_factor FROM cameras WHERE model = '$exif[Model]';");
        if (count($result) == 1)
            $exif['CropFactor'] = $result[0]['crop_factor'];
    }

    $image_exif = addslashes(var_export($exif, true));
    //~ die('<pre>'.$image_exif.'\n\n'.print_r($exif, true).'\n\n'.print_r($exif_temp, true).'</pre>');

    // Generate image data
    $image_data = phphoto_generate_image_data($image);
    $image_thumbnail = phphoto_generate_image_data($image, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);

    // Check if exists
    $result = phphoto_db_query($db, "SELECT COUNT(id) AS exist FROM images WHERE filename = '$image_filename';");
    $image_exists = ($result[0]['exist'] == 1);
    if (!$replace_existing && $image_exists) {
        return -2;
    }

    if ($image_exists) {
        // Update existing
        $sql = "
            UPDATE images SET
                data = '$image_data',
                thumbnail = '$image_thumbnail',
                type = $image_type,
                width = $image_width,
                height = $image_height,
                filesize = $image_filesize,
                exif = '$image_exif'
            WHERE
                filename = '$image_filename'
        ";

        $result = phphoto_db_query($db, $sql);
        return $result;
    }
    else {
        // Insert new
        $sql = "
            INSERT INTO images (
                data,
                thumbnail,
                type,
                width,
                height,
                filesize,
                filename,
                exif,
                title,
                description,
                active,
                created
            )
            VALUES (
                '$image_data',
                '$image_thumbnail',
                $image_type,
                $image_width,
                $image_height,
                $image_filesize,
                '$image_filename',
                '$image_exif',
                '',
                '',
                TRUE,
                NOW()
            )
        ";

        $result = phphoto_db_query($db, $sql);
        return ($result) ? mysql_insert_id($db) : INVALID_ID;
    }
}

/*
 * Regenerates the thumbnail of the given gallery
 */
function phphoto_regenerate_gallery_thumbnail($db, $gallery_id) {
    assert(is_numeric($gallery_id));

    $sql = "
            SELECT
                data,
                width,
                height
            FROM
                images
            WHERE
                id IN (SELECT image_id FROM image_to_gallery WHERE gallery_id = $gallery_id)
            ORDER BY
                RAND()
            LIMIT ".GALLERY_THUMBNAIL_MAXIMUM_IMAGES."
    ";
    $images = phphoto_db_query($db, $sql);
    $thumbnail = phphoto_generate_gallery_data($images);
    $sql = "UPDATE galleries SET thumbnail = '$thumbnail' WHERE id = $gallery_id";
    return (phphoto_db_query($db, $sql) == 1);
}

/*
 * Generates gallery thumbnail data as a byte[]
 */
function phphoto_generate_gallery_data($images) {
    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor(GALLERY_THUMBNAIL_WIDTH, GALLERY_THUMBNAIL_HEIGHT))
        die('Failed to create destination image');

    // Turn on alpha blending and set alpha flag
    imagesavealpha($canvas_resource, true);
    imagealphablending($canvas_resource, true);

    // set canvas background color
    $panel_color = str_replace('#', '', GALLERY_THUMBNAIL_PANEL_COLOR);
    if (strlen($panel_color) != 6 && strlen($panel_color) != 8)
        die("Panel color is not properly formatted: #$panel_color");
    $canvas_r = hexdec(substr($panel_color, 0, 2));
    $canvas_g = hexdec(substr($panel_color, 2, 2));
    $canvas_b = hexdec(substr($panel_color, 4, 2));
    $canvas_a = (strlen($panel_color) == 8) ? hexdec(substr($panel_color, 6, 2)) / 2 : 0;
    $canvas_bg = imagecolorallocatealpha($canvas_resource, $canvas_r, $canvas_g, $canvas_b, $canvas_a);
    imagefill($canvas_resource, 0, 0, $canvas_bg);

    // draw image thumbnails on canvas
    if (count($images) < GALLERY_THUMBNAIL_MINIMUM_IMAGES)
        $size = ceil(sqrt(GALLERY_THUMBNAIL_MINIMUM_IMAGES));
    elseif (count($images) > GALLERY_THUMBNAIL_MAXIMUM_IMAGES)
        $size = ceil(sqrt(GALLERY_THUMBNAIL_MAXIMUM_IMAGES));
    else
        $size = floor(sqrt(count($images)));

    $image_width = GALLERY_THUMBNAIL_WIDTH / $size;
    $image_height = GALLERY_THUMBNAIL_HEIGHT / $size;

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $index = $x + ($y * $size);
            if (isset($images[$index])) {
                $image_resource = imagecreatefromstring($images[$index]['data']);
                if (!ImageCopyResampled($canvas_resource, $image_resource, $x * $image_width, $y * $image_height,
                        0, 0, $image_width, $image_height, $images[$index]['width'], $images[$index]['height']))
                    die('Could not copy resampled image');
                imagedestroy($image_resource);
            }
        }
    }

    // write canvas to file
    if (!imagepng($canvas_resource, IMAGE_TEMP_FILE))
        die('Could not create new png image');

    imagedestroy($canvas_resource);

    return addslashes(file_get_contents(IMAGE_TEMP_FILE));
}

/*
 * Regenerate the thumbnails of all the images in the database
 */
function phphoto_regenerate_image_thumbnails($db) {
    $regenerated_thumbnails = 0;
    $sql = "SELECT id, data FROM images";
    foreach (phphoto_db_query($db, $sql) as $image) {
        $temp_resource = imagecreatefromstring($image['data']);
        if (!imagejpeg($temp_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new jpeg image');

        $thumbnail = phphoto_generate_image_data(IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_WIDTH, IMAGE_THUMBNAIL_HEIGHT, IMAGE_THUMBNAIL_PANEL_COLOR);
        $sql = "UPDATE images SET thumbnail = '$thumbnail' WHERE id = $image[id]";
        $regenerated_thumbnails += phphoto_db_query($db, $sql);
    }
    return $regenerated_thumbnails;
}


/*
 * Generates image data as a byte[]
 */
function phphoto_generate_image_data($image, $max_width = null, $max_height = null, $panel_color = '#000000') {
    if ($max_width == null && $max_height == null) {
        // keep original image
        return addslashes(file_get_contents($image));
    }

    // parse image data
    $image_info = getimagesize($image);
    $image_width =  $image_info[0];
    $image_height =  $image_info[1];
    $image_aspect = $image_width / $image_height;
    $image_type =  $image_info[2];

    // calculate sizes
    $canvas_width = ($max_width == null) ? $image_width : (($image_width < $max_width) ? $image_width : $max_width);
    $canvas_height = ($max_height == null) ? $image_height : (($image_height < $max_height) ? $image_height : $max_height);
    $canvas_aspect = $canvas_width / $canvas_height;

    // calculate image offset
    $image_scaled_width = $canvas_width;
    $image_scaled_height = $canvas_height;
    $image_delta_x = 0;
    $image_delta_y = 0;
    if ($image_width > $image_height) { // landscape
        $image_scaled_height = ceil($image_scaled_width / $image_aspect);
        $image_delta_y = round(($canvas_height - $image_scaled_height) / 2);
    }
    else { // portrait
        $image_scaled_width = ceil($image_scaled_height * $image_aspect);
        $image_delta_x = round(($canvas_width - $image_scaled_width) / 2);
    }

    // Read image
    switch ($image_type) {
        case 1: // GIF
            if (!$image_resource = ImageCreateFromGif($image))
                die('Could not create image from gif');
            break;
        case 2: // JPEG
            if (!$image_resource = ImageCreateFromJpeg($image))
                die('Could not create image from jpeg');
            break;
        case 3: // PNG
            if (!$image_resource = ImageCreateFromPng($image))
                die('Could not create image from png');
            break;
        default:
            die('Unrecognized image type');
    }

    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor($canvas_width, $canvas_height))
        die('Failed to create destination image');

    // Turn on alpha blending and set alpha flag
    imagesavealpha($canvas_resource, true);
    imagealphablending($canvas_resource, true);

    // set canvas background color
    $panel_color = str_replace('#', '', $panel_color);
    if (strlen($panel_color) != 6 && strlen($panel_color) != 8)
        die("Panel color is not properly formatted: #$panel_color");
    $canvas_r = hexdec(substr($panel_color, 0, 2));
    $canvas_g = hexdec(substr($panel_color, 2, 2));
    $canvas_b = hexdec(substr($panel_color, 4, 2));
    $canvas_a = (strlen($panel_color) == 8) ? hexdec(substr($panel_color, 6, 2)) / 2 : 0;
    $canvas_bg = imagecolorallocatealpha($canvas_resource, $canvas_r, $canvas_g, $canvas_b, $canvas_a);
    imagefill($canvas_resource, 0, 0, $canvas_bg);

    // resize and fit the image on the canvas
    if (!ImageCopyResampled($canvas_resource, $image_resource, $image_delta_x, $image_delta_y,
            0, 0, $image_scaled_width, $image_scaled_height, $image_width, $image_height))
        die('Could not copy resampled image');

    // write canvas to file
    /*switch ($image_type) {
        case 1: // GIF
            if (!imagegif($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new gif image');
            break;
        case 2: // JPEG
            if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
                die('Could not create new jpeg image');
            break;
        case 3: // PNG*/
            if (!imagepng($canvas_resource, IMAGE_TEMP_FILE))
                die('Could not create new png image');
            /*break;
        default:
            die('Unrecognized image type');
    }*/

    imagedestroy($image_resource);
    imagedestroy($canvas_resource);

    return addslashes(file_get_contents(IMAGE_TEMP_FILE));
}

/*
 * Generates a gallery thumbnail on-the-fly when no thumbnail exists
 */
function phphoto_generate_null_image() {
    // create image canvas
    if (!$canvas_resource = ImageCreateTrueColor(GALLERY_THUMBNAIL_WIDTH, GALLERY_THUMBNAIL_HEIGHT))
        die('Failed to create destination image');
    
    // set canvas background color
    $panel_color = str_replace('#', '', GALLERY_THUMBNAIL_PANEL_COLOR);
    if (strlen($panel_color) != 6)
        die("Panel color is not properly formatted: #$panel_color");
    $canvas_r = hexdec(substr($panel_color, 0, 2));
    $canvas_g = hexdec(substr($panel_color, 2, 2));
    $canvas_b = hexdec(substr($panel_color, 4, 2));
    $canvas_bg = imagecolorallocate($canvas_resource, $canvas_r, $canvas_g, $canvas_b);
    imagefill($canvas_resource, 0, 0, $canvas_bg);

    // set invalid-cross color
    $cross_color = str_replace('#', '', GALLERY_THUMBNAIL_INVALID_COLOR);
    if (strlen($cross_color) != 6)
        die("Panel color is not properly formatted: #$panel_color");
    $cross_color = imagecolorallocate(
            $canvas_resource,
            hexdec(substr($cross_color, 0, 2)),
            hexdec(substr($cross_color, 2, 2)),
            hexdec(substr($cross_color, 4, 2))
    );

    imagefill($canvas_resource, 0, 0, $canvas_bg);

    imagesetthickness($canvas_resource, 2);
    imageline (
            $canvas_resource,
            GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT * 0.45,
            GALLERY_THUMBNAIL_WIDTH - GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT - GALLERY_THUMBNAIL_HEIGHT * 0.45,
            $cross_color
    );
    imageline (
            $canvas_resource,
            GALLERY_THUMBNAIL_WIDTH - GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT * 0.45,
            GALLERY_THUMBNAIL_WIDTH * 0.45,
            GALLERY_THUMBNAIL_HEIGHT - GALLERY_THUMBNAIL_HEIGHT * 0.45,
            $cross_color
    );

    // write canvas to file
    if (!imagejpeg($canvas_resource, IMAGE_TEMP_FILE, IMAGE_THUMBNAIL_QUALITY))
        die('could not create new jpeg image');

    imagedestroy($canvas_resource);

    return file_get_contents(IMAGE_TEMP_FILE);
}

?>