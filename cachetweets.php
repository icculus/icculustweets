<?php
/* Load required lib files. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

$cachedir = './cache';
if (!file_exists($cachedir))
    mkdir($cachedir);

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

$reprocess_existing = false;
$getfromargv = false;

$skiparg = true;
foreach ($argv as $arg)
{
    if ($skiparg) { $skiparg = false; continue; }  // skip argv[0]
    if ($arg == "--reprocess")
        $reprocess_existing = true;
}

$connection = undef;
$statuses = undef;
$data = undef;

if ($reprocess_existing)
{
    $data = array();
    if ($cachefnames !== false)
    {
        foreach ($cachefnames as $fname)
        {
            $obj = unserialize(file_get_contents("$cachedir/$fname"));
            if ($obj !== false)
                $data[] = $obj;
        }
    }
}
else if ($getfromargv)
{
    $data = array();

    $skip = true;
    foreach ($argv as $x)
    {
        if ($skip) { $skip = false; continue; }
        $data[] = $x;
    }

    /* Create a TwitterOauth object with consumer/user tokens. */
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

    /* If method is set change API call made. Test is called by default. */
    $content = $connection->get('account/verify_credentials');
}
else
{
    $maxtweet = '1';
    if ($cachefnames !== false)
        $maxtweet = $cachefnames[0];
    unset($cachefnames);

    /* Create a TwitterOauth object with consumer/user tokens. */
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);

    /* If method is set change API call made. Test is called by default. */
    $content = $connection->get('account/verify_credentials');

    $data = $connection->get('statuses/user_timeline', array('screen_name' => TWITTER_USERNAME, 'count' => '2000', 'include_entities' => 'true', 'include_rts' => 'true', 'since_id' => $maxtweet));
    if (isset($data->errors))
    {
        print("get timeline failed:\n");
        foreach ($data->errors as $err)
            print("  - {$err->message}\n");
        exit(1);
    }
}


foreach ($data as $tweet)
{
    if ($getfromargv)
    {
        $id = $tweet;
        $tweet = $connection->get('statuses/show', array('id' => $id, 'include_entities' => 'true'));
        if (isset($tweet->errors))
        {
            print("get tweet $id failed:\n");
            foreach ($tweet->errors as $err)
                print("  - {$err->message}\n");
            continue;
        }
    }

    //if ($tweet->user->screen_name != TWITTER_USERNAME) { print ("tweet $id is probably a retweet: '{$tweet->text}'\n"); continue; }

    $id = $tweet->id_str;
    $cachefname = "$cachedir/$id";

    if (!$reprocess_existing && file_exists($cachefname)) 
    {
        print("tweet $id already cached!\n");
        continue;
    }

    $origtweet = $tweet;
    if (isset($tweet->retweeted_status))
        $tweet = $tweet->retweeted_status;


    unset($in_reply_to_name);
    $mediahtml = '';
    $text = isset($tweet->origtext) ? $tweet->origtext : $tweet->text;
    $html = $text;
    if (isset($tweet->entities))
    {
        if (isset($tweet->entities->user_mentions))
        {
            foreach($tweet->entities->user_mentions as $item)
            {
                if (strcasecmp($tweet->in_reply_to_screen_name, $item->screen_name) == 0)
                    $in_reply_to_name = $item->name;
                $html = str_ireplace("@{$item->screen_name}", "<a href='https://twitter.com/{$item->screen_name}/'>@{$item->screen_name}</a>", $html, $temp = 1);
            }
        }
        if (isset($tweet->entities->urls))
        {
            foreach($tweet->entities->urls as $item)
            {
                $text = str_replace($item->url, $item->display_url, $text, $temp = 1);
                $html = str_replace($item->url, "<a href='{$item->expanded_url}'>{$item->display_url}</a>", $html, $temp = 1);
                // !!! FIXME: embed youtube
            }
        }
        if (isset($tweet->entities->media))
        {
            foreach($tweet->entities->media as $item)
            {
                $mediaurl = isset($item->media_url_https) ? $item->media_url_https : $item->media_url;
                if ($item->type == "photo")
                {
                    $mediahtml = $mediahtml . "<img src='$mediaurl' style='max-width:375px;'/><br/>";
                    $text = str_replace($item->url, $item->display_url, $text, $temp = 1);
                    $html = str_replace($item->url, "<a href='{$item->expanded_url}'>{$item->display_url}</a>", $html, $temp = 1);
                }
            }
        }
        if (isset($tweet->entities->hashtags))
        {
            foreach($tweet->entities->hashtags as $item)
                $html = str_replace("#{$item->text}", "<a href='https://twitter.com/search?q=%23{$item->text}'>#{$item->text}</a>", $html, $temp = 1);
        }
    }

    $in_reply_to = '';
    if ($tweet->in_reply_to_status_id_str)
    {
        if (!$in_reply_to_name)
            $in_reply_to_name = $tweet->in_reply_to_screen_name;
        $in_reply_to = "in reply to <a href='https://twitter.com/{$tweet->in_reply_to_screen_name}/status/{$tweet->in_reply_to_status_id_str}'>$in_reply_to_name</a>";
    }

    $datetime = new DateTime($tweet->created_at);  //('D M d H:i:s T Y', $tweet->created_at);
    $created_at = $datetime->format('j M y');
    $tweet->rss_created_at = $datetime->format(DateTime::RSS);
    $tweet->email_created_at = $datetime->format(DateTime::RFC2822);
    $tweet->epoch_created_at = $datetime->format('U');
    $tweet->status_url = "https://twitter.com/{$tweet->user->screen_name}/status/{$tweet->id_str}";

    if (!isset($tweet->origtext))
        $tweet->origtext = $tweet->text;
    $tweet->text = $text;

    $tweet->html = <<<EOS
<div style="padding-bottom:25px;padding-left:50px;padding-right:50px;padding-top:5px;">
  <table><tr>
    <td><img style="width:48px; height:48px;" src='{$tweet->user->profile_image_url}'/></td>
    <td>
      <span style="font-family:'Helvetica Neue', Arial, sans-serif;font-size:18px;font-weight:bold;color:#333;text-decoration:none;display:block;">
        {$tweet->user->name}
      </span>
      <span style="font-family:'Helvetica Neue', Arial, sans-serif;font-size:14px;font-weight:normal;color:#999;text-decoration:none;display:block;">
        <a href="https://twitter.com/{$tweet->user->screen_name}">@{$tweet->user->screen_name}</a>
      </span>
    </td>
  </tr></table>
  <span style="padding-top:5px;padding-bottom:5px;width:438px;line-height: 28px;font-family:Georgia, 'Times New Roman', serif;font-size:22px;color:#333;display:block;">$html</span>
  $mediahtml
  <span style="font-family:'Helvetica Neue', Arial, sans-serif;font-size:12px;color:#999;display:block;">
    Tweeted <a href="{$tweet->status_url}">$created_at</a> $in_reply_to via {$tweet->source}
  </span>
</div>

EOS;

    if (file_put_contents($cachefname, serialize($origtweet)) === false)
    {
        print("couldn't write $cachefname!\n");
        unlink($cachefname);
    }

    //print_r($tweet);
    print("cached tweet $id\n");
}

exit(0);

?>


