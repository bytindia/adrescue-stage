<?php
require('fpdf.php');
include '../db.php';
setlocale(LC_MONETARY, 'en_IN');

$inv_date = date('d-M-Y');
$inv_no = 'BYT/SW/'.rand(250,300);
$name = '';
$address1 = '';
$address2 = '';
$gst = '';
$byt_fee = 0;
$fb_fee = 0;
$g_fee = 0;
$cgst = 0;
$sgst = 0;
$igst = 0;

$fb_spend = 10000;
$g_spend = 15000;

if(isset($_GET['id'])) {
	$getData = array();
    $sqlRev2=mysqli_query($conn, "SELECT * FROM address WHERE fb_acc='".$_GET['id']."' limit 0,1");
	while($sqlROW2=mysqli_fetch_array($sqlRev2)) { 
		//$getData = $sqlROW2;
		$name = $sqlROW2['name'];
		$address1 = $sqlROW2['address1'];
		$address2 = $sqlROW2['address2'];
		$gst = $sqlROW2['gst'];
		$byt_fee = $sqlROW2['byt_fee'];
		$fb_fee = $sqlROW2['fb_fee'];
		$g_fee = $sqlROW2['g_fee'];
		$cgst = $sqlROW2['cgst'];
		$sgst = $sqlROW2['sgst'];
		$igst = $sqlROW2['igst'];
	}
}

class PDF extends FPDF
{
	//Page header
	function Header()
	{
		//Logo
		
		//Arial bold 15
		$this->SetTextColor(112,149,222);
	
		$this->SetFont('Arial','B',15);
		//Move to the right
		//$this->Cell(80);
		//Title
		$this->Cell(75,10,'Beyond 2000 Technologies',0,0,'L');
		$this->Ln(8);
		
		$this->SetTextColor(100,100,100);
		$this->SetFont('Arial','B',11);
		$this->Cell(75,10,'119A, Gill Nagar 1st St. Extension,',0,0,'L');
		$this->Ln(6);
		$this->Cell(75,10,'Gill Nagar, Chennai - 600 094',0,0,'L');
		$this->Ln(6);
		$this->Cell(75,10,'e: faheem@bytindia.com | t: 044 - 4353 6664',0,0,'L');
		//$this->Cell(30,10,'Title',1,0,'C');
		$this->Image('logo_pb.png',150,8,33);
		//Line break
		$this->Ln(10);
	}
	 
	//Page footer
	function Footer()
	{
		//Position at 1.5 cm from bottom
		$this->SetY(-15);
		//Arial italic 8
		$this->SetFont('Arial','I',8);
		
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
	}
}
 
//Instanciation of inherited class
$pdf=new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Times','',10);
$pdf->Cell(150);
$pdf->Cell(25,7, $inv_date ,0,200,'R');
$pdf->Ln(2);

$pdf->Cell(80);
$pdf->SetFont('Arial','BU',10);
$pdf->Cell(25,10,'INVOICE',0,200,'C');
$pdf->Ln(2);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(75,10,$name,0,0,'L');
$pdf->Ln(5);
$pdf->SetFont('Arial','',9);
$pdf->Cell(75,10,$address1,0,0,'L');
$pdf->Ln(5);

$pdf->Cell(75,10,$address2,0,0,'L');
$pdf->Ln(5);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(75,10,'GST ID: '.$gst,0,0,'L');
$pdf->Ln(10);

$pdf->SetFont('Arial','',9);
$pdf->Cell(60,10,'Invoice No',0,0,'L');
$pdf->Cell(60,10,': '.$inv_no, 0, 0, 'L');
$pdf->Ln(6);

$pdf->Cell(60,10,'PAN CARD NO. ',0,0,'L');
$pdf->Cell(60,10,': ARYPS5942E',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(60,10,'Service Taxes No.',0,0,'L');
$pdf->Cell(60,10,': ARYPS5942ESD002',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(60,10,'GST Provisional ID Number',0,0,'L');
$pdf->Cell(60,10,': 33ARYPS5942E1Z8',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(60,10,'GST SAC Code',0,0,'L');
$pdf->Cell(60,10,': 998313',0,0,'L');
$pdf->Ln(10);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(120,7,'SERVICES OFFERED',1,0,'C');
$pdf->Cell(40,7,'COST (INR)',1,0,'C');
$pdf->Ln();


$pdf->Cell(120,6,'Digital & Social Media Marketing -Strategy & execution',1);
$pdf->SetFont('Arial','',9);
$pdf->Cell(40,18,$byt_fee,1,0,'R');
$pdf->Cell(10,6,' ',0);
$pdf->Ln();
$pdf->Cell(120,6,'Content as explained above',1);
$pdf->Ln();
$pdf->Cell(120,6,'Online reputation management -as explained',1);
$pdf->Ln();

$pdf->Cell(120,7,'Facebook Ads Management Fees ('.$fb_fee.'% of the Ads Spend Rs.'.$fb_spend.')',1,0,'L');
$tot_fb = $fb_spend * ($fb_fee/100);
$pdf->Cell(40,7,$tot_fb,1,0,'R');
$pdf->Ln();
$pdf->Cell(120,7,'Google Ads Management Fees ('.$g_fee.'% of the Ads Spend Rs.'.$g_spend.')',1,0,'L');
$tot_g = $g_spend * ($g_fee/100);
$pdf->Cell(40,7,$tot_g,1,0,'R');
$pdf->Ln();
$pdf->SetFont('Arial','B',9);
$tot_sub = $byt_fee + $tot_fb + $tot_g;
$pdf->Cell(120,7,'Total',1,0,'L');
$pdf->Cell(40,7,$tot_sub,1,0,'R');
$pdf->Ln();
$pdf->SetFont('Arial','',9);
$pdf->Cell(120,7,'CGST '.$cgst.'%',1,0,'L');
$tot_cgst = $tot_sub * ($cgst/100);
$pdf->Cell(40,7,$tot_cgst,1,0,'R');
$pdf->Ln();
$pdf->Cell(120,7,'SGST '.$sgst.'%',1,0,'L');
$tot_sgst = $tot_sub * ($sgst/100);
$pdf->Cell(40,7,$tot_sgst,1,0,'R');
$pdf->Ln();

$pdf->SetFont('Arial','B',9);
$pdf->Cell(120,7,'Total inclusive of taxes',1,0,'L');
$tot_grand = $tot_sub + $tot_cgst + $tot_sgst;
$pdf->Cell(40,7,$tot_grand,1,0,'R');


$pdf->Ln();
$pdf->SetFont('Arial','B',9);
$pdf->Cell(75,10,'1% TDS to be deducted against all the services as per this invoice in accordance to the Sec 194C of the Income Tax Act 1961',0,0,'L');
$pdf->Ln(10);


$pdf->Cell(60,10,'Bank Transfer:',0,0,'L');
$pdf->Ln(6);

$pdf->SetFont('Arial','',9);
$pdf->Cell(45,10,'Bank & Branch:',0,0,'L');
$pdf->Cell(60,10,'Canara Bank, Anna Nagar East',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(45,10,'Account Name:',0,0,'L');
$pdf->Cell(60,10,'BEYOND 2000 TECHNOLOGIES',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(45,10,'AC/NO: ',0,0,'L');
$pdf->Cell(60,10,'0974201002708',0,0,'L');
$pdf->Ln(6);

$pdf->Cell(45,10,'IFSC Code:',0,0,'L');
$pdf->Cell(60,10,'CNRB0000974',0,0,'L');
$pdf->Ln(9);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(60,10,'Best Regards,:',0,0,'L');
$pdf->Ln(6);
$pdf->Cell(60,10,'For Beyond 2000 Technologies,:',0,0,'L');
$pdf->Ln(10);

$pdf->Image('faheem-sign.png',10,245,33);
$pdf->Ln(10);

$pdf->Cell(60,10,'Faheem S Ahmed',0,0,'L');
$pdf->Ln(6);

$pdf->SetFont('Arial','',9);
$pdf->Cell(60,10,'Founder & CEO.,',0,0,'L');

$pdf->Ln(6);
for($i=1;$i<=10;$i++)
  //$pdf->Cell(0,10,'Printing line number '.$i,0,1);
$pdf->Output();
?>