<?php

require_once('phphoto.php');

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">



<html>
    <head>
        <title><?php echo GALLERY_NAME.' v.'.GALLERY_VERSION; ?></title>
        <meta http-equiv='Content-Type' content='text/html; charset=<?php echo GALLERY_CHARSET; ?>'>
        <meta http-equiv='Content-Language' content='<?php echo GALLERY_LANGUAGE; ?>'>
<?php

phphoto_stylesheets();

?>
    </head>
    <body>
        <div style="margin:10px; width: 870px;">

<?php

$password = 'abc';
if (isset($_POST['login']) && $_POST['login'] == $password) {
    $_SESSION['authorized'] = true;
}
elseif (isset($_GET['q']) && $_GET['q'] == 'logout') {
    unset($_SESSION['authorized']);
}
$authorized = (isset($_SESSION['authorized']) && $_SESSION['authorized']);
if ($authorized) {
    $additional_items = array(
        'First page' => basename($_SERVER['PHP_SELF']),
        'Logout' => basename($_SERVER['PHP_SELF'])."?q=logout"
    );
    phphoto_admin_links($additional_items);
}
if (isset($_GET['q']) && $_GET['q'] == 'login') {
    echo "\n    <form method='post' action='".basename($_SERVER['PHP_SELF'])."'>";
    echo "\n        <input type='password' name='login'>";
    echo "\n        <input type='submit' value='Authorize'>";
    echo "\n    </form>";
}
else {
    $login_url = 'index.php?q=login';
    phphoto_main($login_url, $authorized);
}

?>

        </div>
    </body>
</html>