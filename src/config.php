<?php

/*
 * This file contains all the possible configuration variables that can be
 * changed in the gallery.
 */

define("GALLERY_NAME",                  "phphoto");
define("GALLERY_VERSION",               "0.1.0");
define("GALLERY_DATE",                  "xxxx-xx-xx");

define("GALLERY_CHARSET",               "iso-8859-1");
define("GALLERY_LANGUAGE",              "sv");
define("GALLERY_STYLESHEET",            "./style.css");

define("DATABASE_HOST",                 "localhost");
define("DATABASE_PORT",                 3306);
define("DATABASE_NAME",                 "phphoto");
define("DATABASE_USER",                 "foo");
define("DATABASE_PASSWORD",             "bar");
define("DATABASE_TABLE_PREFIX",         ""); /* not implemented */

define("GET_KEY_GALLERY_ID",            "gid");
define("GET_KEY_IMAGE_ID",              "iid");
define("GET_KEY_ADMIN_QUERY",           "adm");
define("GET_VALUE_ADMIN_GALLERY",       "g");
define("GET_VALUE_ADMIN_IMAGE",         "i");

define("INVALID_ID",                    -1);
define("CURRENT_PAGE",                  basename($_SERVER['PHP_SELF']));


define("IMAGE_TEMP_FILE",               "/tmp/phphoto.tmp");
define("IMAGE_MAX_FILESIZE",            1048576); // 1MB (same as MySQL's 'max allowed packet')
define("IMAGE_RESIZED_WIDTH",           400);
define("IMAGE_THUMBNAIL_WIDTH",         150);
define("IMAGE_THUMBNAIL_PANEL_COLOR",   "#FFFFFF7F");

define("COLOR_TRANSPARENT",             127); /* not implemented */


/*

    Image properties:
        Name
        Resolution
        Colors
        Type
        Description
        Camera settings (model, aperture, etc)
        

*/

?>