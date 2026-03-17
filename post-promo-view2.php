<meta name="referrer" content="no-referrer" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-2"/>
<?php include 'db.php'; 

function curl_get_file_contents($URL)
{
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        return $contents;
        
}
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

$pgHeadline = 'Post promotion - setup';
$pgID = 8;
$err =''; 
$query = "SELECT access_token FROM users WHERE tbl_id=2";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
	
$access_token = $row['access_token']; 




                                         
  $request_url = 'https://graph.facebook.com/v16.0/23856371544190310/previews?ad_format=MOBILE_FEED_STANDARD&access_token='.$access_token; //exit;
  // echo $access_token; 
  $requests = curl_get_file_contents($request_url);
   $parsed =str_replace('\\','',$requests); 
  /*exit;
  $parsed =str_replace('u003C','<',$parsed);
  echo  htmlspecialchars_decode ($parsed, ENT_NOQUOTES); exit;
  $parsed = get_string_between($requests, '"body":"', '"}]}');
  $parsed =str_replace('\\','',$parsed);
  $parsed =str_replace('u003C','<',$parsed);
  $parsed = str_replace('&amp;','&',$parsed);
  echo $ifr_url = htmlspecialchars_decode ($parsed, ENT_NOQUOTES); 


  d($ifr_url);*/
  echo '<br>';
  $parsed = get_string_between($parsed, 'src="', '" width="335"');
  $parsed = str_replace('&amp;','&',$parsed);
  //echo $parsed; 
  ?>
  
  <div id="fb-root"></div>

<script>
    window.fbAsyncInit = function () {
        FB.init({
            appId:   '594832897646145',
            xfbml:    true,
            version: 'v17.0'
        });
    };

    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js     = d.createElement(s);
        js.id  = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>

<body>
  <div id="fb-root"></div>
  <script>
  
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/all/xfbml.adpreview.js#xfbml=1&appId=594832897646145";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));</script>

  <!-- other elements -->
  <div class="fb-ad-preview" data-creative-id="23855619873110609" data-page-type="desktopfeed"></div>
  <!-- other elements -->
</body>