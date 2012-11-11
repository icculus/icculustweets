To set this up:

(These dev.twitter.com instructions are right as of November 2012, but the UI
could always change. Wing it.)

- Go to dev.twitter.com, login, go to "My applications" on the menu that pops
up when you mouse over your avatar on the top right of the page.
- Click the "Create a new application" button.
- Fill in whatever you want here. You can leave "Callback URL" blank. Agree to
the license and click the create button.
- When you app is created, click "Create my access token" at the bottom of the
next page.

This app only needs to be read-only; it never tries to post or edit anything
with your account. We just need this so we can grab your tweets from Twitter.

Now you should have four magic values on that page: Consumer Key,
Consumer Secret, Access Token, and Access Token Secret.

Make a file in this directory called config.php, with these four magic values,
and your screen name, like this:


<?php
define('CONSUMER_KEY', '3k9sjcS4thSfsfsgW');
define('CONSUMER_SECRET', 'AKCdfsdfSF9sdf98989sdfFSsFQasdfokfAdsfqFRTAcVx');
define("OAUTH_TOKEN", '34672782-wwgovSDFSdf9sdf0DFSFLf08afamkg2GZga';
define("OAUTH_SECRET", 'sf0fkv9s82sffah2k3333FSFsfkskaf9aFAf9faghjQ');
define("TWITTER_USERNAME", 'icculus');


Now run "php ./cachetweets.php" and if all went well, it should pull in a
bunch of your timeline. You'll want to run cachetweets.php in a cronjob,
so new tweets show up in your feed whenever the script sees them.

Make sure this directory is somewhere on your web server, and point your
browser at index.php. See your tweets? You're good to go. Point the web 
browser at rss/index.php to get an RSS feed.

You probably want to put this on the webserver with the directory set
to "AllowOverride Limit" so that the .htaccess file works as expected.

You may need to sniff around for hardcoded things. Grep for 'icculus' to
fix a hardcoded URL or two. Send patches.

Questions: ask me.

--ryan.

icculus@icculus.org

