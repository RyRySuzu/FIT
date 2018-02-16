<?php
require_once __DIR__ . './php-graph-sdk-5/src/Facebook/autoload.php';

// 投稿文
$massage = $_GET['photo_word'];
// 画像のパス
$photo = $_GET['photo_path'];
$photo_path = '../img/' .$photo;

$fb = new Facebook\Facebook([
  'app_id' => 'xx',
  'app_secret' => 'xx',
  'default_graph_version' => 'v2.2',
  ]);

$data = [
  'message' => $massage,
  'source' => $fb->fileToUpload($photo_path),
  "privacy" => array(
    "value" => "EVERYONE",
  ),
];

try {
  // Returns a `Facebook\FacebookResponse` object
  $response = $fb->post('/me/photos', $data, 'xx');
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

$graphNode = $response->getGraphNode();

echo 'Photo ID: ' . $graphNode['id'];
