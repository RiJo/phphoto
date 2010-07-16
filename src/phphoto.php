<?php

session_start();

require_once('./config.php');
require_once('./database.php');
require_once('./common.php');
require_once('./gd.php');
require_once('./admin.php');
require_once('./gallery.php');

date_default_timezone_set(GALLERY_TIMEZONE);

assert_options(ASSERT_BAIL, true); // stop executing on assertion

$allowed_filetypes = array('jpg','jpeg','png');

/*
 * Prints out the required stylesheets
 */
function phphoto_stylesheets() {
    echo "\n<link rel='stylesheet' href='./themes/".GALLERY_THEME."/gallery.css' type='text/css' />";
    echo "\n<link rel='stylesheet' href='./themes/".GALLERY_THEME."/admin.css' type='text/css' />";
}

/*
 * Prints out the gallery/admin pages depending on query strings
 */
function phphoto_main($authorized = false) {
    $db = phphoto_db_connect();
    $admin = (isset($_GET[GET_KEY_ADMIN_QUERY])) ? $_GET[GET_KEY_ADMIN_QUERY] : '';
    if ($authorized)
        phphoto_admin_links($db);
    if ($authorized && strlen($admin) > 0)
        phphoto_admin($db, $admin);
    else
        phphoto_gallery($db);
    phphoto_db_disconnect($db);
}

/*
 * Prints out the gallery
 */
function phphoto_gallery($db) {
    $gallery_id = (isset($_GET[GET_KEY_GALLERY_ID])) ? $_GET[GET_KEY_GALLERY_ID] : INVALID_ID;
    if (is_numeric($gallery_id) && $gallery_id != INVALID_ID)
        phphoto_echo_gallery($db, $gallery_id);
    else
        phphoto_echo_galleries($db);
}

/*
 * Prints out the admin pages
 */
function phphoto_admin($db, $admin) {
    switch ($admin) {
        case GET_VALUE_ADMIN_GALLERY:
            $gallery_id = (isset($_GET[GET_KEY_GALLERY_ID])) ? $_GET[GET_KEY_GALLERY_ID] : INVALID_ID;
            if (is_numeric($gallery_id) && $gallery_id != INVALID_ID)
                phphoto_echo_admin_gallery($db, $gallery_id);
            else
                phphoto_echo_admin_galleries($db);
            break;
        case GET_VALUE_ADMIN_TAG:
            $tag_id = (isset($_GET[GET_KEY_TAG_ID])) ? $_GET[GET_KEY_TAG_ID] : INVALID_ID;
            if (is_numeric($tag_id) && $tag_id != INVALID_ID)
                phphoto_echo_admin_tag($db, $tag_id);
            else
                phphoto_echo_admin_tags($db);
            break;
        case GET_VALUE_ADMIN_IMAGE:
            $image_id = (isset($_GET[GET_KEY_IMAGE_ID])) ? $_GET[GET_KEY_IMAGE_ID] : INVALID_ID;
            if (is_numeric($image_id) && $image_id != INVALID_ID)
                phphoto_echo_admin_image($db, $image_id);
            else
                phphoto_echo_admin_images($db);
            break;
        case GET_VALUE_ADMIN_CAMERA:
            phphoto_echo_admin_cameras($db);
            break;
        default:
            phphoto_echo_admin_default($db);
            break;
    }
}

/*
 * Prints out links used for the admin pages
 */
function phphoto_admin_links($db) {
    $text_index = phphoto_text($db, 'menu', 'index');
    $text_admin = phphoto_text($db, 'menu', 'admin');
    $text_galleries = phphoto_text($db, 'menu', 'galleries');
    $text_tags = phphoto_text($db, 'menu', 'tags');
    $text_images = phphoto_text($db, 'menu', 'images');
    $text_cameras = phphoto_text($db, 'menu', 'cameras');
    
    echo "\n<ul>";
    echo "\n    <li".((!isset($_GET[GET_KEY_ADMIN_QUERY]))?" class=active":'').
            "><a href='".CURRENT_PAGE."'>$text_index</a></li>";
    echo "\n    <li".((isset($_GET[GET_KEY_ADMIN_QUERY]) && $_GET[GET_KEY_ADMIN_QUERY] == GET_VALUE_ADMIN_DEFAULT)?" class=active":'').
            "><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_DEFAULT."'>$text_admin</a></li>";
    echo "\n    <li".((isset($_GET[GET_KEY_ADMIN_QUERY]) && $_GET[GET_KEY_ADMIN_QUERY] == GET_VALUE_ADMIN_GALLERY)?" class=active":'').
            "><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_GALLERY."'>$text_galleries</a></li>";
    echo "\n    <li".((isset($_GET[GET_KEY_ADMIN_QUERY]) && $_GET[GET_KEY_ADMIN_QUERY] == GET_VALUE_ADMIN_TAG)?" class=active":'').
            "><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_TAG."'>$text_tags</a></li>";
    echo "\n    <li".((isset($_GET[GET_KEY_ADMIN_QUERY]) && $_GET[GET_KEY_ADMIN_QUERY] == GET_VALUE_ADMIN_IMAGE)?" class=active":'').
            "><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_IMAGE."'>$text_images</a></li>";
    echo "\n    <li".((isset($_GET[GET_KEY_ADMIN_QUERY]) && $_GET[GET_KEY_ADMIN_QUERY] == GET_VALUE_ADMIN_CAMERA)?" class=active":'').
            "><a href='".CURRENT_PAGE.'?'.GET_KEY_ADMIN_QUERY.'='.GET_VALUE_ADMIN_CAMERA."'>$text_cameras</a></li>";
    echo "\n</ul>";
}

?>