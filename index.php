<?php

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

$maxtweets = 20;
$cachedir = './cache';

$data = array();
$cachefnames = scandir($cachedir);
rsort($cachefnames, SORT_NUMERIC);

$count = 0;
if ($cachefnames !== false)
{
    foreach ($cachefnames as $fname)
    {
        $obj = unserialize(file_get_contents("$cachedir/$fname"));
        if ($obj !== false)
        {
            $data[] = $obj;
            $count++;
            if (($maxtweets > 0) && ($count >= $maxtweets))
                break;
        }
    }
}

$uname = TWITTER_USERNAME;
$baseurl = "https://twitter.icculus.org/$uname";

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

