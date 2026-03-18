<?php exit;
include '../db.php';
$pg='facebook';
include 'config.php';

$interest = 'NoBroker.com,InterContinental Hotels Group,Godrej Group,Swing trading,Marriott International,JW Marriott Hotels,Indiabulls,Day trading,Stock exchange,First-time buyer,Four Seasons Hotels and Resorts,Sheraton Hotels and Resorts,Trulia,Foreign exchange market,Stock market,Luxury Resorts,Lodha Group,Residential area,Real estate investing,99acres,Hilton Hotels & Resorts,Bombay Stock Exchange,Luxury yacht,BSE SENSEX,Oberoi Realty,Marriott Hotels & Resorts,Zillow,Nifty Fifty,Prestige Group,Shapoorji Pallonji Group,First-time home buyer grant or MagicBricks,Employers: IBM Global Services,Barclays,Morgan Stanley,Merrill Lynch,Oracle,JPMorgan Chase & Co.,Capgemini,TCS - Tata Consultancy Service,Goldman Sachs,Wipro,Tata Consultancy Services,Ernst & Young,Accenture,HCL Infosystems,KPMG,Wipro,Infosys,Tech Mahindra';

//$interest = 'NoBroker.com,InterContinental Hotels Group,Godrej Group,';

$intr_exp = explode(',',$interest);

d($intr_exp);

foreach($intr_exp as $key => $val) {	
   // $request_url ="https://graph.facebook.com/comments/?ids=" . $purl;
    $url  = "https://graph.facebook.com/".$api_ver."/search?type=adinterest&q=".urlencode($val)."&fields=id,name&access_token=".$access_token."";
	$req = file_get_contents_curl($url);
    $res = json_decode($req, true);  
    //$fb_data[$k] = $res['data'];
    //echo $val.': '.d($req); //exit;
    if(isset($res['data'][0]))  {
        $int[] = $res['data'][0];
    }

}
//d($int);
$int_string ='';
foreach($int as $key => $val) {	
    $int_string .='{"id": "'.$val['id'].'","name": "'.$val['name'].'"},';
}
echo $int_string; 