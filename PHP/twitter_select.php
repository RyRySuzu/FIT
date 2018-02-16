<?php
require "./TwistOAuth-master/build/TwistOAuth.phar";
$select_word = $_GET['select_word'];

// 初期設定
$consumer_key = 'xx';
$consumer_secret = 'xx';
$access_token = 'xx';
$access_token_secret = 'xx';

$connection = new TwistOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);


// キーワードによるツイート検索
$select_word;
$tweets_params = ['q' => 'xx AND xx' ,'count' => '10'];
$tweets = $connection->get('search/tweets', $tweets_params)->statuses;

// ハッシュタグによるツイート検索
// $hash_params = ['q' => '#html5,#css3' ,'count' => '10', 'lang'=>'ja'];
// $hash = $connection->get('search/tweets', $hash_params)->statuses;

foreach ($tweets as $value) {
    $text = htmlspecialchars($value->text, ENT_QUOTES, 'UTF-8', false);
    // 検索キーワードをマーキング
    $keywords = preg_split('/,|\sOR\s/', $tweets_params['q']); //配列化
    foreach ($keywords as $key) {
        $text = str_ireplace($key, '<span class="keyword">'.$key.'</span>', $text);
    }
    // ツイート表示のHTML生成
    disp_tweet($value, $text);
}

function disp_tweet($value, $text){
    $icon_url = $value->user->profile_image_url;
    $screen_name = $value->user->screen_name;
    $updated = date('Y/m/d H:i', strtotime($value->created_at));
    $tweet_id = $value->id_str;
    $url = 'https://twitter.com/' . $screen_name . '/status/' . $tweet_id;

    echo '<div class="tweetbox">' . PHP_EOL;
    echo '<div class="thumb">' . '<img alt="" src="' . $icon_url . '">' . '</div>' . PHP_EOL;
    echo '<div class="meta"><a target="_blank" href="' . $url . '">' . $updated . '</a>' . '<br>@' . $screen_name .'</div>' . PHP_EOL;
    echo '<div class="tweet">' . $text . '</div>' . PHP_EOL;
    echo '</div>' . PHP_EOL;
}
