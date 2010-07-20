<?php

function phphoto_db_connect() {
    if (!$db = mysql_connect(DATABASE_HOST.':'.DATABASE_PORT, DATABASE_USER, DATABASE_PASSWORD))
        die('Could not connect: ' . mysql_error());
    if (!mysql_select_db(DATABASE_NAME, $db))
        die('Could not select database: ' . mysql_error());
    mysql_set_charset('utf8', $db);
    return $db;
}

function phphoto_db_disconnect($db) {
    mysql_close($db);
}

function phphoto_db_query($db, $sql) {
    if (!$db || !$sql)
        return false;
    if (!$result = mysql_query($sql, $db))
        die('Invalid SQL: '.$sql);
    if ($result === true || $result === false)
        return ($result) ? mysql_affected_rows($db) : false;
    $result_array = array();
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        array_push($result_array, $row);
    }
    mysql_free_result($result);
    return $result_array;
}

?>