<?php
$host = 'sql105.infinityfree.com';
$dbname = 'if0_37280528_ryuo_store';
$username = 'if0_37280528';
$password = 'm9RLB5iHMPr';

$db = new mysqli($host, $username, $password, $dbname);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>
