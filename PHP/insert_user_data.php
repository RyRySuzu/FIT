<?php
ini_set("display_errors", On);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");

// 画像のパスを用いてサーバーから画像取得しデコードするPHP

//SQL データベースに接続
$connectionInfo = array(
  "UID" => "xx",
  "pwd" => "{xx}",
  "Database" => "xx",
  "LoginTimeout" => 30,
  "Encrypt" => 1,
  "TrustServerCertificate" => 0
);

  $serverName = "xx";

  $dish_name = $_POST["dish_name"];
  $dish_image = $_POST["dish_data"];
  $face_name = $_POST["face_name"];
  $face_image = $_POST["face_data"];

try
{

$conn = sqlsrv_connect($serverName, $connectionInfo);

//サーバアクセスエラー処理
if(!$conn){
    die( print_r( sqlsrv_errors(), true));
}

// 現在日時を作成する
$now = new DateTime();
$now = $now->format('Y-m-d H:i:s');

  $sql1 = "INSERT INTO user_data (dish_name,dish_image,face_name,face_image,date)
                  VALUES ('$dish_name','$dish_image','$face_name','$face_image','$now')";

  $result1 = sqlsrv_query($conn,$sql1);
  if ($result1==false) {
	die( print_r( sqlsrv_errors(), true));
	}

  // 料理写真と顔写真のデコード
  $data = base64_decode($dish_image);
  $im = imagecreatefromstring($data);
  if ($im !== false) {
      header('Content-Type: image/jpg');
      imagesavealpha($im, TRUE); // 透明色の有効
      imagepng($im ,'../img/'.$dish_name);
  }
  else {
      echo 'エラーが発生しました。';
  }

  $face_data = base64_decode($face_image);
  $face_im = imagecreatefromstring($face_data);
  if ($face_im !== false) {
      header('Content-Type: image/jpg');
      imagesavealpha($face_im, TRUE); // 透明色の有効
      imagepng($face_im,'../img/'.$face_name);
  }
  else {
      echo 'エラーが発生しました。';
  }

  $html = file_get_contents("https://xx/insert_analyse_data.php");

}

catch (PDOException $e)
{
 	//例外処理
 	die('Error:' . $e->getMessage());
}
?>
