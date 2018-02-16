<?php
require "../xx/TwistOAuth-master/build/TwistOAuth.phar";
header('Content-Type: text/html; charset=UTF-8');
ini_set("display_errors", On);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('Asia/Tokyo');

//文章の作成と投稿を行うPHP

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
$dish_db_name = "";
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


$sql2 = "SELECT top 1 * FROM analyse_data ORDER BY Id DESC";
$result2= sqlsrv_query($conn,$sql2);

  if ($result2 == false) {
  die( print_r( sqlsrv_errors(), true));
  }

  while($row2 = sqlsrv_fetch_object($result2))
  {
    $smile_level = $row2->smile_level;
    $dish_db_name = $row2->food_name;
    $date = $row2->date;
  }

$date_db = $date->format('Y年m月d日');

// 初期設定
$consumer_key = 'xx';
$consumer_secret = 'xx';
$access_token = 'xx';
$access_token_secret = 'xx';

$connection = new TwistOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);

$text_array = array();
$marukohu_sentence = array();
$FB_T_marukohu_sentence = array();
$insta_T_marukohu_sentence = array();

// キーワードによるツイート検索
$tweets_params = ['q' => $dish_db_name.' AND 美味しい' ,'count' => '10'];
$tweets = $connection->get('search/tweets', $tweets_params)->statuses;

foreach ($tweets as $value) {
    $text = htmlspecialchars($value->text, ENT_QUOTES, 'UTF-8', false);
    // 検索キーワードをマーキング
    $keywords = preg_split('/,|\sOR\s/', $tweets_params['q']);
    // 配列化
    foreach ($keywords as $key) {
        $text = str_ireplace($key, '<span class="keyword">'.$key.'</span>', $text);
    }
  $text_array[] = $text;
}

function array_from_new_sentence_generation($array) {
    $str = "";

    foreach ($array as $str_foreach) {
      $str .= $str_foreach;
    }

    $keyword = "";

    $POST_DATA = array(
        'app_id' => 'xx',
        'sentence' => $str
    );
    $curl=curl_init("https://labs.goo.ne.jp/api/morph");
    curl_setopt($curl,CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($POST_DATA));
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl,CURLOPT_COOKIEJAR,      'cookie');
    curl_setopt($curl,CURLOPT_COOKIEFILE,     'tmp');
    curl_setopt($curl,CURLOPT_FOLLOWLOCATION, TRUE);

    $output= curl_exec($curl);

    $arr = json_decode($output,true);

    $subject;
    $Verb;

    // 品詞取得
    for ($n = 0; $n < count($arr["word_list"]); $n++){
      for ($i = 0; $i < count($arr["word_list"][$n]); $i++){
        // 名詞の場合
        if ($arr["word_list"][$n][$i][1] == "名詞") {
          if ($arr["word_list"][$n][$i+1][1] == "名詞接尾辞") {
            // 主語(
            $subject[] = $arr["word_list"][$n][$i][0].$arr["word_list"][$n][$i+1][0];
          } else {
            // 名詞接尾辞がなかったら1つ目だけ
            $subject[] = $arr["word_list"][$n][$i][0];
          }
        // 形容詞の場合
        } else if ($arr["word_list"][$n][$i][1] == "形容詞語幹") {
          if ($arr["word_list"][$n][$i+1][1] == "形容詞接尾辞") {
            // 述語
            $Verb[] = $arr["word_list"][$n][$i][0].$arr["word_list"][$n][$i+1][0];
          }
        }
      }
    }

    $subject_rand = array_rand($subject,1);
    $Verb_rand = array_rand($Verb,1);
    return $subject[$subject_rand].$Verb[$Verb_rand];
}

for($k=0;$k<=count($text_array)-1;$k++){

$comment = $text_array[$k];
$url = "https://language.googleapis.com/v1/documents:analyzeSentiment?key=xx";
$document = array('type' =>'PLAIN_TEXT','language' =>'ja','content' =>$comment);
$postdata = array('encodingType' => 'UTF8', 'document' => $document);
$json_post = json_encode($postdata);
$key_number = -3;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_post);
$result = curl_exec($ch);
curl_close($ch);
 $result_array = json_decode($result,true);
 for($i=0;$i<=count($result_array)-1;$i++){
     $temp_number = $result_array['sentences'][$i]['sentiment']['score'];
     if($key_number <= $temp_number)
     {
       $key_number = $i;
     }
}
     $marukohu_sentence[] = $result_array['sentences'][$key_number]['text']['content'];

}

$Tw = array_from_new_sentence_generation($marukohu_sentence);
$Tw_s = array_from_new_sentence_generation($marukohu_sentence);
$Fb = array_from_new_sentence_generation($FB_marukohu_sentence);
$Fb_s = array_from_new_sentence_generation($FB_marukohu_sentence);
$In = array_from_new_sentence_generation($insta_marukohu_sentence);
$In_s = array_from_new_sentence_generation($insta_marukohu_sentence);


/**************************************************
文章作成ソース
**************************************************/

// 作成部分は載せない

}

catch (PDOException $e)
{
 	//例外処理
 	die('Error:' . $e->getMessage());
}
?>
