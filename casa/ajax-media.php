<?php
include '../db.php';
  $url_1 = "https://graph.facebook.com/".$api_ver."/".$_POST['src']."?fields=source,embed_html&access_token=".$_POST['tok']."";

  //exit;
    $req = file_get_contents_curl($url_1);
    $res = json_decode($req, true);
    
    //print_r($res);
    if($res['source']!='') {
    ?>

<video controls width="50%">
    <source src="<?php echo $res['source']; ?>" type="video/webm">

    <source src="<?php echo $res['source']; ?>" type="video/mp4">

    Download the
    <a href="/media/cc0-videos/flower.webm">WEBM</a>
    or
    <a href="/media/cc0-videos/flower.mp4">MP4</a>
    video.
</video>
<?php
} else {
    echo 'Video unavailable';
} ?>