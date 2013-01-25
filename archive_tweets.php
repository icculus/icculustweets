<?php

session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

header('Content-Type: text/plain; charset="utf-8"');

function append_to_already_archived($id, $alreadyarchivedfname)
{
    $fp = fopen($alreadyarchivedfname, 'ab');
    if ($fp === false)
        return false;
    if (fputs($fp, "$id\n") != (strlen($id) + 1))
    {
        fclose($fp);
        return false;
    }
    if (!fclose($fp))
        return false;
    return true;
}

$VERSION = '1.0';
$cachedir = './cache';
$argv0 = array_shift($argv);
$to = array_shift($argv);
$maildir = array_shift($argv);
if ((!$to) || (!$maildir))
{
    print("USAGE: $argv0 <toaddr> <maildir> <cachefname1..cachefnameN|--all>\n");
    exit(1);
}

$doall = false;
$cachefnames = array();
foreach ($argv as $arg)
{
    if ($arg == '--all')
    {
        $doall = true;
        break;
    }
    $cachefnames[] = $arg;
}

$alreadyarchivedfname = "$maildir/tweets-archived.txt";
$archivedlist = @file($alreadyarchivedfname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$isarchived = array();
if ($archivedlist !== false)
{
    foreach ($archivedlist as $id)
        $isarchived[$id] = true;
}
unset($archivedlist);

if ($doall)
{
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
}

if ($cachefnames === false)
    exit(0);  // nothing to do.

$pid = getmypid();
foreach ($cachefnames as $cachefname)
{
    $cachefname = "$cachedir/$cachefname";
    $tweet = unserialize(file_get_contents($cachefname));
    if (!$tweet)
    {
        print("Failed to load $cachefname\n");
        continue;
    }

    if (isset($tweet->retweeted_status))
        $tweet = $tweet->retweeted_status;

    $id = $tweet->id_str;
    $text = $tweet->text;
    $html = $tweet->html;
    $statusurl = $tweet->status_url;
    $emaildate = $tweet->email_created_at;
    $realname = $tweet->user->name;
    $screenname = $tweet->user->screen_name;

    $subject = str_replace("\r\n", "\n", $text);
    $subject = str_replace("\r", "\n", $subject);
    $subject = str_replace("\n", ' ', $subject);
    $subject = "@$screenname: $subject";

    if ($isarchived[$id])
    {
        //print("Already archived $cachefname\n");
        continue;  // already archived this one.
    }

    $hash_addon = 0;
    do
    {
        $mimeboundary = "--mime_" . sha1($statusurl . $emaildate . $hash_addon);
        $hash_addon++;
    } while ((strstr($text, $mimeboundary)) || (strstr($html, $mimeboundary)));

    $detailline = "Tweeted {$tweet->simple_created_at}";
    if (isset($tweet->in_reply_to_name_proper))
        $detailline = "$detailline in reply to {$tweet->in_reply_to_name_proper}";

    $output = <<<EOS
Return-Path: <$to>
Delivered-To: $to
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="$mimeboundary"; charset="utf-8"
Content-Transfer-Encoding: binary
X-Mailer: archive_tweets.php $VERSION
From: $to
To: $to
Date: $emaildate
Subject: $subject

This is a multipart message in MIME format.

--$mimeboundary
Date: $emaildate
Mime-Version: 1.0
Content-Type: text/plain; charset="utf-8"
Content-Transfer-Encoding: binary


$text

$realname (@$screenname)
$detailline
$statusurl


--$mimeboundary
Date: $emaildate
Mime-Version: 1.0
Content-Type: text/html; charset="utf-8"
Content-Transfer-Encoding: binary


$html


EOS
    ;

    $bytes = strlen($output);
    $basefname = "{$tweet->epoch_created_at}.$pid.tweet-$screenname-$id,S=$bytes";
    $tmpfname = "$maildir/tmp/$basefname";
    $newfname = "$maildir/new/$basefname";
    if (file_put_contents($tmpfname, $output) !== $bytes)
    {
        print("failed to write '$tmpfname'\n");
        unlink($tmpfname);
    }
    else if (!rename($tmpfname, $newfname))
    {
        print("failed to rename '$tmpfname' to '$newfname'\n");
        unlink($tmpfname);
    }
    else if (!append_to_already_archived($id, $alreadyarchivedfname))
    {
        print("failed to update archived list\n");
        unlink($newfname);
    }
    else
    {
        $isarchived[$id] = true;
        //print("Archived $cachefname to $newfname\n");
    }
}

exit(0);
?>

