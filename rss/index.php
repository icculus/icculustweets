<?php

require_once('../config.php');

$cachedir = '../cache';
$maxtweets = 20;

$xmlents = array('&#8212;','&#34;','&#38;','&#38;','&#60;','&#62;','&#160;','&#161;','&#162;','&#163;','&#164;','&#165;','&#166;','&#167;','&#168;','&#169;','&#170;','&#171;','&#172;','&#173;','&#174;','&#175;','&#176;','&#177;','&#178;','&#179;','&#180;','&#181;','&#182;','&#183;','&#184;','&#185;','&#186;','&#187;','&#188;','&#189;','&#190;','&#191;','&#192;','&#193;','&#194;','&#195;','&#196;','&#197;','&#198;','&#199;','&#200;','&#201;','&#202;','&#203;','&#204;','&#205;','&#206;','&#207;','&#208;','&#209;','&#210;','&#211;','&#212;','&#213;','&#214;','&#215;','&#216;','&#217;','&#218;','&#219;','&#220;','&#221;','&#222;','&#223;','&#224;','&#225;','&#226;','&#227;','&#228;','&#229;','&#230;','&#231;','&#232;','&#233;','&#234;','&#235;','&#236;','&#237;','&#238;','&#239;','&#240;','&#241;','&#242;','&#243;','&#244;','&#245;','&#246;','&#247;','&#248;','&#249;','&#250;','&#251;','&#252;','&#253;','&#254;','&#255;');
$htmlents = array('&mdash;','&quot;','&amp;','&amp;','&lt;','&gt;','&nbsp;','&iexcl;','&cent;','&pound;','&curren;','&yen;','&brvbar;','&sect;','&uml;','&copy;','&ordf;','&laquo;','&not;','&shy;','&reg;','&macr;','&deg;','&plusmn;','&sup2;','&sup3;','&acute;','&micro;','&para;','&middot;','&cedil;','&sup1;','&ordm;','&raquo;','&frac14;','&frac12;','&frac34;','&iquest;','&Agrave;','&Aacute;','&Acirc;','&Atilde;','&Auml;','&Aring;','&AElig;','&Ccedil;','&Egrave;','&Eacute;','&Ecirc;','&Euml;','&Igrave;','&Iacute;','&Icirc;','&Iuml;','&ETH;','&Ntilde;','&Ograve;','&Oacute;','&Ocirc;','&Otilde;','&Ouml;','&times;','&Oslash;','&Ugrave;','&Uacute;','&Ucirc;','&Uuml;','&Yacute;','&THORN;','&szlig;','&agrave;','&aacute;','&acirc;','&atilde;','&auml;','&aring;','&aelig;','&ccedil;','&egrave;','&eacute;','&ecirc;','&euml;','&igrave;','&iacute;','&icirc;','&iuml;','&eth;','&ntilde;','&ograve;','&oacute;','&ocirc;','&otilde;','&ouml;','&divide;','&oslash;','&ugrave;','&uacute;','&ucirc;','&uuml;','&yacute;','&thorn;','&yuml;');
function xml_entities($str)
{
    global $xmlents, $htmlents;
    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    $str = str_replace($htmlents, $xmlents, $str);
    $str = str_ireplace($htmlents, $xmlents, $str);
    return $str;
} // xml_entities

$data = array();

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
    if (($maxtweets <= 0) || ($maxtweets > count($cachefnames)))
        $maxtweets = count($cachefnames);

    for ($i = 0; $i < $maxtweets; $i++)
    {
        $fname = $cachefnames[$i];
        $obj = unserialize(file_get_contents("$cachedir/$fname"));
        if ($obj !== false)
            $data[] = $obj;
    }
}

header('Content-Type: text/xml; charset=UTF-8');

$uname = TWITTER_USERNAME;

print( <<<EOS
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
  <title>Twitter / $uname</title>
  <link>https://twitter.com/$uname</link>
  <tagline>Tweets from @$uname</tagline>
  <image>
    <url>https://twitter.com/images/resources/twitter-bird-light-bgs.png</url>
    <title>Twitter / $uname</title>
    <link>https://twitter.com/$uname</link>
  </image>

EOS
);

foreach($data as $tweet)
{
    $origid = $tweet->id_str;
    if (isset($tweet->retweeted_status))
        $tweet = $tweet->retweeted_status;

    $text = xml_entities("@{$tweet->user->screen_name}: {$tweet->text}");
    $embedhtml = xml_entities($tweet->html);

    print( <<<EOS
  <item>
    <guid>tag:twitter.icculus.org/$uname/status/$origid</guid>
    <title>$text</title>
    <link>https://twitter.com/{$tweet->user->screen_name}/status/{$tweet->id_str}</link>
    <summary>$text</summary>
    <description>$embedhtml</description>
    <pubDate>{$tweet->rss_created_at}</pubDate>
  </item>

EOS
);

}

print( <<<EOS

</channel>
</rss>

EOS
);

?>

