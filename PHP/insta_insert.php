<?php
include_once("IXR_Library.php");

// 投稿文
$massage = $_GET['photo_word'];

// 画像のパス
$photo = $_GET['photo_path'];
$photo_path = "../img/" .$photo;

echo $photo_path;
$client=new IXR_Client("http://xx/xmlrpc.php");

$id="xx";
$pw="xx";

$pathname="$photo_path";
$filename=basename($pathname);
$imgInfo=getimagesize($pathname);
$type=$imgInfo['mime'];
$bits=new IXR_Base64(file_get_contents($pathname));
$status=$client->query(	"wp.uploadFile",
						1,
						$id,
						$pw,
						array(	'name' => $filename,
								'type' => $type,
								'bits' => $bits,
								'overwrite' => TRUE
							)
					);
$res=$client->getResponse();

sleep(5);

$status=$client->query(	'wp.getMediaLibrary',
							'1',
							$id,						// ブログID
							$pw);						// パスワード
$ret=$client->getResponse();

$title=$massage;
$description='ここはインスタには反映されない';
$postDate=new IXR_Date(time());
$status=$client->query(	'metaWeblog.newPost',
						'',								//
						$id,							// ブログID
						$pw,							// パスワード
						array(	'title' =>$title,
								'description' => $description,
								'dateCreated' => $postDate,
								'wp_post_thumbnail' => $ret[0]["attachment_id"]		// アイキャッチ画像
							),
						1);								// 0:下書き 1:公開 2:予約投稿
if($status){
	$post_id=$client->getResponse();
	$status=$client->query('mt.setPostCategories',
	$post_id,  // Post ID
	$id,
	$pw,
	array(array('categoryId'=>'3')));
}
?>
