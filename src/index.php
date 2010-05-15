<?php
    require_once('phphoto.php');

    session_start();
    $password = 'abc';
    
    /*$exif = exif_read_data('./IMAGE_399_s.jpg');
    $filter = array_flip(array('DateTime', 'ISOSpeedRatings', 'Model', 'ExposureTime'));
    $exif2 = array_intersect_key($exif, $filter);
    echo '<pre>'.print_r($exif, true).'</pre>';*/
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">

<html>
    <head>
        <title><?php echo GALLERY_NAME.' v.'.GALLERY_VERSION; ?></title>
        <meta http-equiv='Content-Type' content='text/html; charset=<?php echo GALLERY_CHARSET; ?>'>
        <meta http-equiv='Content-Language' content='<?php echo GALLERY_LANGUAGE; ?>'>
        <link rel='stylesheet' href='<?php echo GALLERY_STYLESHEET; ?>' type='text/css'>
    </head>
    <body>
        <a href='index.php'>First page</a>
        <div style="margin:10px; width: 870px;">
            

<?php
    if (isset($_POST['login']) && $_POST['login'] == $password) {
        $_SESSION['authorized'] = true;
    }
    elseif (isset($_GET['q']) && $_GET['q'] == 'logout') {
        unset($_SESSION['authorized']);
    }
    if (isset($_SESSION['authorized']) && $_SESSION['authorized']) {
        $additional_items = array('Logout' => basename($_SERVER['PHP_SELF'])."?q=logout");
        phphoto_admin_links($additional_items);
    }
    if (isset($_GET['q']) && $_GET['q'] == 'login') {
        echo "\n    <form method='post' action='".basename($_SERVER['PHP_SELF'])."'>";
        echo "\n        <input type='password' name='login'>";
        echo "\n        <input type='submit' value='Authorize'>";
        echo "\n    </form>";
    }
    else {
        phphoto_main();
    }
?>

        </div>
    </body>
</html>