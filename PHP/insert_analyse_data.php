<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set("display_errors", On);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('Asia/Tokyo');

// 食べ物と顔の画像を解析、レスポンスを翻訳、DBに保存するPHP

//SQL データベースに接続
$connectionInfo = array(
  "UID" => "xx",
  "pwd" => "{xx}",
  "Database" => "xx",
  "LoginTimeout" => 30,
  "Encrypt" => 1,
  "TrustServerCertificate" => 0,
  "CharacterSet"=>"UTF-8"
);

$serverName = "xx";
$dish_name = "";
$dish_image = "";
$face_name = "";
$face_image = "";
$trans_google = "";

try
{

$conn = sqlsrv_connect($serverName, $connectionInfo);

//サーバアクセスエラー処理
if(!$conn){
    die( print_r( sqlsrv_errors(), true));
}

$sql0 = "SELECT top 1 * FROM user_data ORDER BY Id DESC";

$result0= sqlsrv_query($conn,$sql0);
if ($result0 == false) {
die( print_r( sqlsrv_errors(), true));
}

while($row = sqlsrv_fetch_object($result0))
{
  $dish_name = $row->dish_name;
  $dish_image = $row->dish_image;
  $face_name = $row->face_name;
  $face_image = $row->dish_image;
}

/**************************************************
食べ物認識
**************************************************/

// APIキー
$api_key = "xx";
// 画像へのパス
$image_path = 'xx/'.$dish_name;
// タイプ
$feature = "LABEL_DETECTION";
// パラメータ設定
$param = array("requests" => array());
$item["image"] = array("content" => base64_encode(file_get_contents($image_path)));
$item["features"] = array(array("type" => $feature, "maxResults" => 3));
$param["requests"][] = $item;

// リクエスト用のJSONを作成
$json = json_encode($param);

// リクエストを実行
$curl = curl_init() ;
curl_setopt($curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

$res1 = curl_exec($curl);
$res2 = curl_getinfo($curl);
curl_close($curl);

// 取得したデータ
$json = substr($res1, $res2["header_size"]);
$array = json_decode($json, true);

// 値取得
$google = $array["responses"][0]["labelAnnotations"][0]["description"];

/**************************************************
顔認識
**************************************************/

// APIキー
$api_key = "xx";

// 画像へのパス
$image_path = 'xx/'.$face_name;

// タイプ
$feature = "FACE_DETECTION";

// パラメータ設定
$param = array("requests" => array());
$item["image"] = array("content" => base64_encode(file_get_contents($image_path)));
$item["features"] = array(array("type" => $feature, "maxResults" => 5));
$param["requests"][] = $item;

// リクエスト用のJSONを作成
$json = json_encode($param);

// リクエストを実行
$curl = curl_init() ;
curl_setopt($curl, CURLOPT_URL, "https://vision.googleapis.com/v1/images:annotate?key=" . $api_key);
curl_setopt($curl, CURLOPT_HEADER, true);
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

$res1 = curl_exec($curl);
$res2 = curl_getinfo($curl);
curl_close($curl);

// 取得したデータ
$json = substr($res1, $res2["header_size"]);
$array = json_decode($json, true);

$data = $array['responses'][0]['faceAnnotations'][0];
$Likelihood = array(
    'UNKNOWN'       => '0',
    'VERY_UNLIKELY' => '1',
    'UNLIKELY'      => '2',
    'POSSIBLE'      => '3',
    'LIKELY'        => '4',
    'VERY_LIKELY'   => '5',
);

// 出力
$smile_level = $Likelihood[$data['joyLikelihood']];

// 現在日時を作成する
$now = new DateTime();
$now = $now->format('Y-m-d H:i:s');

/**************************************************
翻訳
**************************************************/

$apiKey = 'xx';
$text = $google;
$url = 'https://www.googleapis.com/language/translate/v2?key=' . $apiKey . '&q=' . rawurlencode($text) . '&source=en&target=ja';

$handle = curl_init($url);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($handle);
$responseDecoded = json_decode($response, true);
curl_close($handle);

$trans_google = $responseDecoded['data']['translations'][0]['translatedText'];


$sql1 = "INSERT INTO analyse_data (smile_level,food_name,date)
                  VALUES ('$smile_level',N'$trans_google','$now')";
$result1 = sqlsrv_query($conn,$sql1);

  if ($result1 == false) {
	die( print_r( sqlsrv_errors(), true));
  }

  $html = file_get_contents("xx/post_sns.php");

}

catch (PDOException $e)
{
 	//例外処理
 	die('Error:' . $e->getMessage());
}
?>
