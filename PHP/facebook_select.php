<?php
require_once __DIR__ . './php-graph-sdk-5/src/Facebook/autoload.php';

// 投稿文
$massage = $_GET['photo_word'];
// 画像のパス
$photo_path = $_GET['photo_path'];

$fb = new Facebook\Facebook([
  'app_id' => 'xx',
  'app_secret' => 'xx',
  'default_graph_version' => 'xx',
  'default_access_token' => 'xx'
  ]);

try {
  $res = $fb->get('/frst.joqr/feed?fields=,message&limit=10');

} catch( Facebook\Exceptions\FacebookSDKException $e) {
	var_dump($e);
	exit();
}

var_dump($res->getDecodedBody());
