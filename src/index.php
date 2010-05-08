<?php
    require_once('phphoto.php');

    session_start();
    $password = 'abc';
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

        <div style="margin:30px; float:right; border: 3px dashed #bbbbbb">
            <a href='index.php'>First page</a>
            <br>

<?php
    if (isset($_POST['login']) && $_POST['login'] == $password) {
        $_SESSION['authorized'] = true;
    }
    if (isset($_SESSION['authorized']) && $_SESSION['authorized']) {
        phphoto_upload();
        phphoto_admin_links();
    }
    else {
        echo "\n    <form method='post' action='".basename($_SERVER['PHP_SELF'])."'>";
        echo "\n        <input type='password' name='login'>";
        echo "\n        <br>";
        echo "\n        <input type='submit' value='Authorize'>";
        echo "\n    </form>";
    }
?>

        </div>

        <div style="margin:30px; width:750px; border: 3px dashed #bbbbbb">

<?php
    phphoto_main();
?>

        </div>

    </body>
</html>