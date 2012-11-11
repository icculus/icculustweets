<?php

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

$data = array();
$cachedir = './cache';
$cachefnames = scandir($cachedir);
if ($cachefnames !== false)
{
    while (($cachefnames[0] == '.') || ($cachefnames[0] == '..'))
        array_shift($cachefnames);
    if (count($cachefnames) == 0)
        $cachefnames = false;
    else
        rsort($cachefnames, SORT_NUMERIC);
}

if ($cachefnames !== false)
{
    foreach ($cachefnames as $fname)
    {
        $obj = unserialize(file_get_contents("$cachedir/$fname"));
        if ($obj !== false)
            $data[] = $obj;
    }
}

header('Content-Type: text/plain; charset=UTF-8');
print_r($data);

