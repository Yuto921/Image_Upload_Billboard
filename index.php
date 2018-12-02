<!--
PHPで画像をアップロードする掲示板

＊環境
ローカル開発環境（vagrant）

＊仕様
アップロードできる画像の種類
JPEG/GIF/PNG
1MBバイト以下の画像
400pxの横幅を超えるものに関しては、サムネイルを作成し表示する
-->





<?php

//アップロードした際のメッセージをセッションを使って受け渡し
session_start();

//様々な設定
//エラーメッセージをHTMLの方にも表示させる
ini_set('display_errors', 1);
define('MAX_FILE_SIZE', 1 * 1024 * 1024); // 1MB
//サムネイルを作る際の閾値となる幅を設定
define('THUMBNAIL_WIDTH', 400);
define('IMAGES_DIR', __DIR__ . '/images');
define('THUMBNAIL_DIR', __DIR__ . '/thumbs');

//画像の処理をする際に必要なGDというプラグインがあるかどうかをチェックする＝imagecreatetruecolorという関数があるかどうかをチェックすることでGD（グラフィックを扱うライブラリ）がインストールされているか確かめる
if (!function_exists('imagecreatetruecolor')) {
  echo 'GD not installed';
  exit;
}

//いろいろな表示をしていくにあたって、エスケープするための関数を作成
function h($s) {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

//画像のアップロード処理
require 'ImageUploader.php';

//$uploaderのアップロード処理はこのフォームが投稿された時に呼び出される
$uploader = new \MyApp\ImageUploader();

//定義済みの定数REQUEST_METHODがPOSTだったら、formがPOSTされたという意味
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uploader->upload();
}

//アップロードの際のメッセージを表示する
list($success, $error) = $uploader->getResults();

//アップロードした画像をブラウザに表示
$images = $uploader->getImages();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Image Uploader</title>
  <style>
  body {
    text-align: center;
    font-family: Arial, sans-serif;
  }
  ul {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  li {
    margin-bottom: 5px;
  }
  input[type=file] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
    /*input(type=file)を透明にしている*/
    opacity: 0;
  }
  .btn {
    position: relative;
    display: inline-block;
    width: 300px;
    padding: 7px;
    border-radius: 5px;
    margin: 10px auto 20px;
    color: #fff;
    box-shadow: 0 4px #0088cc;
    background: #00aaff;
  }
  .btn:hover {
    opacity: 0.8;
  }
  .msg {
    margin: 0 auto 15px;
    width: 400px;
    font-weight: bold;
  }
  .msg.success {
    color: #4caf50;
  }
  .msg.error {
    color: #f44336;
  }
  </style>
</head>
<body>

<div class="btn">
  Upload!
  <form action="" method="post" enctype="multipart/form-data" id="my_form">
  <!--ファイルをアップロードするときはenctype="multipart/form-data"-->
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo h(MAX_FILE_SIZE); ?>">
    <!--「input type="file"」の前にファイルの最大サイズを指定するための隠し項目を入れる-->
    <input type="file" name="image" id="my_file">
  </form>
</div>

  <!--アップロードの際のメッセージ表示-->
  <?php if (isset($success)) : ?>
    <div class="msg success"><?php echo h($success); ?></div>
  <?php endif; ?>
  <?php if (isset($error)) : ?>
    <div class="msg error"><?php echo h($error); ?></div>
  <?php endif; ?>

  <!--リスト表示-->
  <ul>
    <?php foreach ($images as $image) : ?>
      <li> <!--リンク先はフォルダ名＝basename(IMAGES_DIR)-->
        <a href="<?php echo h(basename(IMAGES_DIR)) . '/' . h(basename($image)); ?>">
          <img src="<?php echo h($image); ?>">
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

<!--アップロードの際のメッセージを表示後にふわっと消したいので、jQueryを使ってやる。GoogleでホストしているHosted Librariesがあるのでそのリンクを使用-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> -->
<!--メッセージを消すjQueryのscript文-->
<script>
$(function() {
  $('.msg').fadeOut(3000);
  //input type="file"の中身が更新された時にフォームをサブミットする設定
  //my_fileの中身が変更されたらmy_formをsubmitする
  $('#my_file').on('change', function() {
    $('#my_form').submit();
  });
});
</script>
</body>
</html>