<?php
// ユーザネームから固有のuser_IDを取得する。
define("INSTAGRAM_ACCESS_TOKEN", ACCESS TOKEN);
// ユーザアカウント名
$user_account = ACCOUNT;

// ユーザアカウント名からユーザデータを取得する。
$user_api_url = 'https://api.instagram.com/v1/users/search?q=' . $user_account . '&access_token=' . INSTAGRAM_ACCESS_TOKEN;
$user_data = json_decode(@file_get_contents($user_api_url));

// 取得したデータの中から正しいデータを選出
foreach ($user_data->data as $user_data) {
    if ($user_account == $user_data->username) {
        $user_id = $user_data->id;
    }
}

$photos = array();
$tags = Hash Tag;

// 特定ユーザの投稿データ最新5件を取得する
$photos_api_url = 'https://api.instagram.com/v1/tags/'.$tags.'/media/recent?access_token=' . INSTAGRAM_ACCESS_TOKEN . "&count=50";
$photos_data = json_decode(@file_get_contents($photos_api_url));

// 処理する配列を任意の数にまとめる
$photos_data_sliced = array_slice($photos_data->data, 0, $num);

$data = array();
foreach ($photos_data_sliced as $photo) {
	// ユーザーIDでフィルタリング
	if($photo->user->id == $user_id){
		$photodata = array();
		$photodata['url'] = $photo->images->low_resolution->url;
		$photodata['link'] = $photo->link;

		//コメントから#タグ削除
		$text = preg_replace('/[#＃]+[A-Za-z0-9-_ぁ-ヶ亜-黑]+/', '',$photo->caption->text);
		//ダブルでエンコーディング
		$text = mb_convert_encoding($text, "utf8", "auto");
		$text = mb_convert_encoding($text, "utf8", "utf8");
		$photodata['text'] = $text;

		$ts = $photo->caption->created_time;
		$date = new DateTime("@$ts");
		$photodata['date'] = $date->format('Y/m/d');
		$data[] = $photodata;
	}
}
echo json_encode($data);
?>
