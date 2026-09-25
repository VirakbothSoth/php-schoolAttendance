<?php
$database = "exam";
$server = "localhost";
$db_user = "root";
$db_pass = "";
$link = mysqli_connect($server, $db_user, $db_pass, $database);

if (!$link) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('The application is temporarily unavailable.');
}

mysqli_set_charset($link, 'utf8mb4');
