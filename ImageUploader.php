<?php

//MyAppという名前空間を使いつつ、$uploaderのインスタンス化
namespace MyApp;

class ImageUploader {

  private $_imageFileName;
  private $_imageType;

  //upload()のメソッドを作成
  public function upload() {
    try {
      // error check・・アップロードした時にエラーがないかというチェック
      $this->_validateUpload();

      // type check・・画像保存の際、画像の種類によってPHPの命令が変わるので画像のタイプチェック
      //ファイルの拡張子を知るためにextentionという意味の変数をセット
      $ext = $this->_validateImageType();
      // var_dump($ext);
      // exit;

      // save
      $savePath = $this->_save($ext);

      // create thumbnail
      $this->_createThumbnail($savePath);

      //上手く行った時とそうでないときに、セッションにメッセージを入れるように設定
      $_SESSION['success'] = 'Upload Done!';
    } catch (\Exception $e) {
      $_SESSION['error'] = $e->getMessage();
      // exit;
    }
    // redirect・・画像投稿後にindex.phpを再読み込みすると二重投稿になるので、それを防ぐために終わったらindex.phpにリダイレクトさせる
    header('Location: http://' . $_SERVER['HTTP_HOST']);
    exit;
  }

  //アップロードした際のメッセージ表示のメソッド
  public function getResults() {
    $success = null;
    $error = null;
    if (isset($_SESSION['success'])) {
      $success = $_SESSION['success'];
      //何度もリロードした時の同じメッセージを防ぐために
      unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
      $error = $_SESSION['error'];
      unset($_SESSION['error']);
    }
    //配列で渡し、index.phpの方でlistで受け取る
    return [$success, $error];
  }

  //getImagesのメソッド
  public function getImages() {
    //最終的に$imagesを返すので空配列
    $images = [];
    $files = [];
    //あるフォルダの中にあるファイルを精査していきたいときの決まり文句
    $imageDir = opendir(IMAGES_DIR);
    //$imageDirから1行ずつ呼んでそれを$fileに入れてそれがfalseでない間、while文以下を行う
    while (false !== ($file = readdir($imageDir))) {
      //$fileに対してファイルではないカレントディレクトリ(.)親ディレクトリ(..)をチェックし次に回す
      if ($file === '.' || $file === '..') {
        continue;
      }
      //$fileにファイル名を入れてソートできるようにする
      $files[] = $file;
      //サムネイルがあるかどうかで$imagesの中に渡すものが変わるのでチェックする
      if (file_exists(THUMBNAIL_DIR . '/' . $file)) {
        $images[] = basename(THUMBNAIL_DIR) . '/' . $file;
      } else {
        $images[] = basename(IMAGES_DIR) . '/' . $file;
      }
    }
    //$imagesを$filesを使ってソートする
    //$files順に逆向きオプションで$imagesをソートする
    array_multisort($files, SORT_DESC, $images);
    return $images;
  }

  //$savePathの保存したファイルパスを使ってサムネイルを作るメソッド作成・・400px以上だったらサムネイル化
  private function _createThumbnail($savePath) {
    //getimagesize()でファイルのサイズを取得
    $imageSize = getimagesize($savePath);
    $width = $imageSize[0];
    $height = $imageSize[1];
    //$widthが400pxより大きければサムネイルを作る・・400pxはindex.phpの方で定数化してあるのでTHUMBNAIL_WIDTHを引っ張る
    if ($width > THUMBNAIL_WIDTH) {
      //長くなるので別のメソッド(_createThumbnailMain)を作成
      $this->_createThumbnailMain($savePath, $width, $height);
    }
  }

  //サムネイルの作り方メソッド・・サムネイルを作るには元の画像リソースを作り、それを元にサムネイルを作って保存という流れ
  private function _createThumbnailMain($savePath, $width, $height) {
    //$imageTypeをprivateプロパティに上の方で宣言
    switch($this->_imageType) {
      //画像リソースの作り方$srcImage = imagecreatefrom@@@
      case IMAGETYPE_GIF:
        $srcImage = imagecreatefromgif($savePath);
        break;
      case IMAGETYPE_JPEG:
        $srcImage = imagecreatefromjpeg($savePath);
        break;
      case IMAGETYPE_PNG:
        $srcImage = imagecreatefrompng($savePath);
        break;
    }
    //このソースイメージを使ってサムネイルを作っていく。
    //幅が400なので高さを計算してあげる(幅と高さの割合で計算)
    $thumbHeight = round($height * THUMBNAIL_WIDTH / $width);
    //サムネイルの元イメージ作成
    $thumbImage = imagecreatetruecolor(THUMBNAIL_WIDTH, $thumbHeight);
    imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, THUMBNAIL_WIDTH, $thumbHeight, $width, $height);

    switch($this->_imageType) {
      case IMAGETYPE_GIF:
        imagegif($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
        break;
      case IMAGETYPE_JPEG:
        imagejpeg($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
        break;
      case IMAGETYPE_PNG:
        imagepng($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
        break;
    }

  }

  //画像の保存メソッドを作成
  private function _save($ext) {
    //保存するファイル名を決める
    //ファイル名は後でサムネイルを作る時にも使うのでprivateプロパティにし、上の方で宣言
    //ファイル名は他のものと重複しないかつ、後でソートができるように
    $this->_imageFileName = sprintf(
      '%s_%s.%s',//%sは現在までの経過ミリ秒、%sはランダムな文字列、%sは拡張子
      time(),//現在までの経過ミリ秒の設定
      sha1(uniqid(mt_rand(), true)),//ランダムな文字列の設定
      $ext//拡張子が取れる
    );
    //画像を保存するためのパスを作る
    $savePath = IMAGES_DIR . '/' . $this->_imageFileName;
    //tmpファイルに入っていたファイルをちゃんとした$savePathに動かす
    $res = move_uploaded_file($_FILES['image']['tmp_name'], $savePath);
    if ($res === false) {
      throw new \Exception('Could not upload!');
    }
    return $savePath;
  }

  //ファイルの拡張子を知るためのメソッドを作成
  private function _validateImageType() {
    //exif_imagetype()にアップロードしたファイルを渡すと何の種類の画像かを返してくれる
    $this->_imageType = exif_imagetype($_FILES['image']['tmp_name']);
    switch($this->_imageType) {
      case IMAGETYPE_GIF:
        return 'gif';
      case IMAGETYPE_JPEG:
        return 'jpg';
      case IMAGETYPE_PNG:
        return 'png';
      default:
        throw new \Exception('PNG/JPEG/GIF only!');
    }
  }

  //アップロードのエラーチェックのメソッドを作成
  private function _validateUpload() {
    //投稿されたファイルの情報は定義済みの$_FILESという変数にある
    // var_dump($_FILES);
    // exit;

    //$_FILES['image']、$_FILES['image']['error']、がちゃんとセットされているかチェック
    if (!isset($_FILES['image']) || !isset($_FILES['image']['error'])) {
      throw new \Exception('Upload Error!');
    }

    //$_FILES['image']['error']の種類によって、いろいろな例外の種類を変える
    switch($_FILES['image']['error']) {
      case UPLOAD_ERR_OK:
        return true;
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new \Exception('File too large!');
      default:
        throw new \Exception('Err: ' . $_FILES['image']['error']);
    }

  }
}
