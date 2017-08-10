<?php

session_start();
require 'autoload.php';
require 'config.php';

use Abraham\TwitterOAuth\TwitterOAuth;

$data = [];
if (!isset($_SESSION['access_token'])) {
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));
    $_SESSION['oauth_token'] = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
    header('Location: ' . $url);
} else {
    $access_token = $_SESSION['access_token'];
    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    $user = $connection->get("account/verify_credentials");
    $loggedIn_user = $user->screen_name;

    $tweets = $connection->get('statuses/home_timeline', ['count' => 1000, 'exclude_replies' => true, 'screen_name' => $loggedIn_user, 'include_rts' => false]);
    $totalTweets[] = $tweets;
    $tweets_data = [];

    for ($count = 1; $count <= 800; $count ++) {
        foreach ($totalTweets as $items) {
            $data['user_screen_name'] = $items[$count]->user->screen_name;
            $data['user_name'] = $items[$count]->user->name;
            $data['verified'] = $items[$count]->user->verified;
            $data['user_profile_image'] = $items[$count]->user->profile_image_url;
            $tweet_text = $items[$count]->text;
            $tweet_text = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@', '<a href="$1">$1</a>', $tweet_text);
            $tweet_text = preg_replace('/@(\w+)/', '<a href="http://twitter.com/$1">@$1</a>', $tweet_text);
            $tweet_text = preg_replace('/\s+#(\w+)/', ' <a href="http://search.twitter.com/search?q=%23$1">#$1</a>', $tweet_text);
            $data['tweet_text'] = $tweet_text;
            $data['date_time'] = strftime('%h %d', strtotime($items[$count]->created_at));
            $data['tweet_id'] = $items[$count]->id;
            $data['retweet_count'] = $items[$count]->retweet_count;
            $data['likes_count'] = $items[$count]->favorite_count;
            $data['media_image'] = $items[$count]->entities->media;
            $data['link_to_tweet'] = $items[$count]->entities->urls;
            $data['link_to_tweet_url'] = $link_to_tweet->url;
            $tweets_data[] = $data;
        }
    }
    $type = $_REQUEST["dType"];

    if ($type == "json") {

        $fp = fopen("tweets.json", "w");
        fwrite($fp, json_encode($tweets_data));
        fclose($fp);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . basename('tweets.json'));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize('tweets.json'));
        readfile('tweets.json');
        ignore_user_abort(true);
        unlink('tweets.json');
        exit();
    } else if ($type == "csv") {

        header('Content-Disposition: attachment; filename="tweets.csv";');
        header("Pragma: no-cache");
        header("Expires: 0");
        $fp = fopen('tweets.csv', 'w');
        foreach ($tweets_data as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        readfile('tweets.csv');
        ignore_user_abort(true);
        unlink('tweets.csv');
        exit();
    }

    $u_name = $_REQUEST['uname'];
    if (isset($u_name)) {
        $tweets = $connection->get('statuses/user_timeline', ['count' => 1000, 'exclude_replies' => true, 'screen_name' => $u_name, 'include_rts' => false]);
        $totalTweets[] = $tweets;
        $tweets_data = [];

        for ($count = 1; $count <= 800; $count ++) {
            foreach ($totalTweets as $items) {
                $data['user_screen_name'] = $items[$count]->user->screen_name;
                $data['user_name'] = $items[$count]->user->name;
                $data['verified'] = $items[$count]->user->verified;
                $data['user_profile_image'] = $items[$count]->user->profile_image_url;
                $tweet_text = $items[$count]->text;
                $tweet_text = preg_replace('@(https?://([-\w\.]+)+(/([\w/_\.]*(\?\S+)?(#\S+)?)?)?)@', '<a href="$1">$1</a>', $tweet_text);
                $tweet_text = preg_replace('/@(\w+)/', '<a href="http://twitter.com/$1">@$1</a>', $tweet_text);
                $tweet_text = preg_replace('/\s+#(\w+)/', ' <a href="http://search.twitter.com/search?q=%23$1">#$1</a>', $tweet_text);
                $data['tweet_text'] = $tweet_text;
                $data['date_time'] = strftime('%h %d', strtotime($items[$count]->created_at));
                $data['tweet_id'] = $items[$count]->id;
                $data['retweet_count'] = $items[$count]->retweet_count;
                $data['likes_count'] = $items[$count]->favorite_count;
                $data['media_image'] = $items[$count]->entities->media;
                $data['link_to_tweet'] = $items[$count]->entities->urls;
                $data['link_to_tweet_url'] = $link_to_tweet->url;
                $tweets_data[] = $data;
            }
        }
        header('Content-Disposition: attachment; filename="user_tweets.csv";');
        header("Pragma: no-cache");
        header("Expires: 0");
        $fp = fopen('user_tweets.csv', 'w');
        foreach ($tweets_data as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        readfile('user_tweets.csv');
        ignore_user_abort(true);
        unlink('user_tweets.csv');
        exit();
    }
}

