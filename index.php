<?php

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

$data = array();
$cachedir = './cache';
$cachefnames = scandir($cachedir);
rsort($cachefnames, SORT_NUMERIC);

if ($cachefnames !== false)
{
    foreach ($cachefnames as $fname)
    {
        $obj = unserialize(file_get_contents("$cachedir/$fname"));
        if ($obj !== false)
            $data[] = $obj;
    }
}

$uname = TWITTER_USERNAME;
$baseurl = "http://twitter.icculus.org/$uname";

header('Content-Type: text/html; charset=UTF-8');
print("<html><head>");
print("<link rel='alternate' type='application/rss+xml' title='Twitter / $uname' href='$baseurl/rss/' />");
print("<title>Twitter / $uname</title></head><body>\n");

foreach ($data as $tweet)
{
    if (isset($tweet->retweeted_status))
        $tweet = $tweet->retweeted_status;
    print($tweet->html);
}

print("\n</body></html>\n\n");

?>

