<?php
require_once("./tmhOAuth-master/tmhOAuth.php");

// ツイートしたい文章
$text = $_GET['photo_word'];

// 画像のパス取得
$path = $_GET['photo_path'];
$photo_path = "../img/" .$path;
echo $photo_path;
// 画像設定
$data = file_get_contents($photo_path); // 画像データ取得
$image = base64_encode($data); // base64でエンコード


//初期設定
$tmhOAuth = new tmhOAuth(array(
 'consumer_key' => 'xx', // コンシューマーキー
 'consumer_secret' => 'xx', // コンシューマーシークレット
 'user_token' => 'xx', // ユーザートークン
 'user_secret' => 'xx', // ユーザーシークレット
));


// 画像アップロード
$image_upload = $tmhOAuth->request( 'POST', // リクエストの種類（POST/GETがある）
'https://upload.twitter.com/1.1/media/upload.json', // 画像アップロード用のTwitter REST APIを指定
array( 'media_data' => $image  ) );// 画像データを指定

// 画像id格納
$decode = json_decode($tmhOAuth->response["response"], true); // JSONデコード
$media_id = $decode['media_id_string']; //「media_id_string」の値を格納



// 画像付きでツイートする
$tweet_update = $tmhOAuth->request(
 'POST', //リクエストの種類（POST/GETがある）
 'https://api.twitter.com/1.1/statuses/update.json', // ツイート用のTwitter REST APIを指定
  array(
   'media_ids' => $media_id, // 格納した画像idを指定
   'status' => $text // ツイートしたい文章を指定
  )
);
