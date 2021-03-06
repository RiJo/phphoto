<?php

define('GALLERY_NAME',                      'phphoto');
define('GALLERY_VERSION',                   '1.6.0');
define('GALLERY_DATE',                      '2010-08-07');
define('GALLERY_DEVELOPERS',                'RiJo');
define('GALLERY_INDEX_PAGE',                'index.php');

define('SETTINGS_FILE',                     './settings.txt');

define('DATABASE_HOST',                     'localhost');
define('DATABASE_PORT',                     3306);
define('DATABASE_NAME',                     'phphoto');
define('DATABASE_USER',                     'foo');
define('DATABASE_PASSWORD',                 'bar');

define('GET_KEY_GALLERY_ID',                'gid');
define('GET_KEY_TAG_ID',                    'tid');
define('GET_KEY_IMAGE_ID',                  'iid');
define('GET_KEY_ADMIN_QUERY',               'adm');
define('GET_KEY_OPERATION',                 'op');
define('GET_KEY_SORT_COLUMN',               's');
define('GET_KEY_PAGE_NUMBER',               'p');
define('GET_KEY_ITEMS_PER_PAGE',            'pp');
define('GET_VALUE_ADMIN_DEFAULT',           'd');
define('GET_VALUE_ADMIN_GALLERY',           'g');
define('GET_VALUE_ADMIN_TAG',               't');
define('GET_VALUE_ADMIN_IMAGE',             'i');
define('GET_VALUE_ADMIN_CAMERA',            'c');
define('GET_VALUE_CREATE',                  'mk');
define('GET_VALUE_DELETE',                  'rm');
define('GET_VALUE_UPDATE',                  'up');

define('SESSION_KEY_VIEWS',                 'phphoto_view');
define('SESSION_VALUE_VIEWS',               'check');

define('INVALID_ID',                        -1);
define('CURRENT_PAGE',                      basename($_SERVER['PHP_SELF']));
define('CURRENT_QUERY',                     $_SERVER['QUERY_STRING']);
define('CURRENT_URL',                       CURRENT_PAGE.'?'.CURRENT_QUERY);

define('DEFAULT_ITEMS_PER_PAGE',            20); // only on admin pages

define('IMAGE_TEMP_FILE',                   '/tmp/phphoto.tmp');
define('IMAGE_MAX_FILESIZE',                1024*1024*10); // should be same as MySQL's 'max allowed packet'
define('IMAGE_EXIF_KEYS',                   'Make,Model,FirmwareVersion,ImageType,DateTimeOriginal,CCDWidth,ExposureTime,ShutterSpeedValue, 
                                            ExposureBiasValue,ISOSpeedRatings,FNumber,FocalLength,WhiteBalance,Flash,ExifVersion');

define('IMAGE_SORT_COLUMN',                 'name');
define('IMAGE_THUMBNAIL_SUFFIX',            't');
define('IMAGE_THUMBNAIL_QUALITY',           80);

define('GALLERY_SORT_COLUMN',               'title');
define('GALLERY_THUMBNAIL_MINIMUM_IMAGES',  4);
define('GALLERY_THUMBNAIL_MAXIMUM_IMAGES',  16);
define('GALLERY_THUMBNAIL_QUALITY',         50);

?>