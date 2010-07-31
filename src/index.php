<?php

/*
 * demostration of phphoto gallery
 *
 * index.php is only an example of how the gallery is used. phphoto is developed
 * in such way that it will fit into its parent container. in this example the
 * parent container is a div with a specific width. this makes the gallery as
 * wide as the divs width.
 *
 * there are only two function calls needed to create the gallery:
 *    - phphoto_stylesheets()   includes the proper stylesheets
 *    - phphoto_main(bool);     echoes the gallery, parameter shows admin stuff
 */

require_once('phphoto.php');

$password = 'abc';
if (isset($_POST['login']) && $_POST['login'] == $password) {
    $_SESSION['authorized'] = true;
}
elseif (isset($_GET['q']) && $_GET['q'] == 'logout') {
    unset($_SESSION['authorized']);
}
$authorized = (isset($_SESSION['authorized']) && $_SESSION['authorized']);

echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'";
echo "\n'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";

echo "\n<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>";
echo "\n    <head>";
echo "\n        <title>".GALLERY_TITLE."</title>";
echo "\n        <meta http-equiv='Content-Type' content='text/html; charset=".GALLERY_CHARSET."' />";
echo "\n        <meta http-equiv='Content-Language' content='".GALLERY_LANGUAGE."' />";

phphoto_stylesheets();

echo "\n   </head>";
echo "\n    <body>";

if ($authorized)
    echo "\n        <a href='".basename($_SERVER['PHP_SELF'])."?q=logout' style='float:right;margin:10px;color:#aaa;'>Logout</a>";
else
    echo "\n        <a href='".basename($_SERVER['PHP_SELF'])."?q=login' style='float:right;margin:10px;color:#aaa;'>Login</a>";

echo "\n        <div style='margin:10px; width: 870px;'>";

if (isset($_GET['q']) && $_GET['q'] == 'login') {
    echo "\n    <form method='post' action='".basename($_SERVER['PHP_SELF'])."'>";
    echo "\n        <input type='password' name='login'>";
    echo "\n        <input type='submit' value='Authorize'>";
    echo "\n    </form>";
}
else {
    phphoto_main($authorized);
}

echo "\n        </div>";
echo "\n    </body>";
echo "\n</html>";

?>