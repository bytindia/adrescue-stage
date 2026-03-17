<?php


class PDF extends FPDF
{
	//Page header
	// Inline Image
    function InlineImage($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='')
    {
        // ----- Code from FPDF->Image() -----
        // Put an image on the page
        if($file=='')
            $this->Error('Image file name is empty');
        if(!isset($this->images[$file]))
        {
            // First use of this image, get info
            if($type=='')
            {
                $pos = strrpos($file,'.');
                if(!$pos)
                    $this->Error('Image file has no extension and no type was specified: '.$file);
                $type = substr($file,$pos+1);
            }
            $type = strtolower($type);
            if($type=='jpeg')
                $type = 'jpg';
            $mtd = '_parse'.$type;
            if(!method_exists($this,$mtd))
                $this->Error('Unsupported image type: '.$type);
            $info = $this->$mtd($file);
            $info['i'] = count($this->images)+1;
            $this->images[$file] = $info;
        }
        else
            $info = $this->images[$file];

        // Automatic width and height calculation if needed
        if($w==0 && $h==0)
        {
            // Put image at 96 dpi
            $w = -96;
            $h = -96;
        }
        if($w<0)
            $w = -$info['w']*72/$w/$this->k;
        if($h<0)
            $h = -$info['h']*72/$h/$this->k;
        if($w==0)
            $w = $h*$info['w']/$info['h'];
        if($h==0)
            $h = $w*$info['h']/$info['w'];

        // Flowing mode
        if($y===null)
        {
            if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak())
            {
                // Automatic page break
                $x2 = $this->x;
                $this->AddPage($this->CurOrientation,$this->CurPageSize,$this->CurRotation);
                $this->x = $x2;
            }
            $y = $this->y;
            $this->y += $h;
        }

        if($x===null)
            $x = $this->x;
        $this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
        if($link)
            $this->Link($x,$y,$w,$h,$link);
        # -----------------------

        // Update Y
        $this->y += $h;
    }
	
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
		$this->Image('http://bytsocial.com/fb-ads/demo/pdf/logo_pb.png', 150,8,33);
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
function createPDF($client_name, $addr1, $addr2, $byt_fee, $byt_fee_c, $byt_fee_i, $gst, $igst_gst, $seo, $seo_c, $seo_i, $seo_m, $add_project, $add_project_c, $add_project_i, $add_project_m, $gif_ban, $gif_ban_c, $gif_ban_i, $gif_ban_m, $linkedin, $linkedin_c, $linkedin_i, $linkedin_m, $web_maint, $web_maint_c, $web_maint_i, $web_maint_m, $shopify, $shopify_c, $shopify_i, $shopify_m, $tax_note, $g_acc, $fb_acc, $duration, $fb_fee, $fb_fee_i, $g_fee, $g_fee_i, $ads_mgnt, $ads_mgnt_c, $ads_mgnt_i,  $ads_spend, $ads_spend_c, $ads_spend_i, $cgst, $sgst, $igst, $inv_date, $inv_no, $fb_spend=0, $g_spend=0, $fileName, $invTy) {
	
	$tot_byt_fee = $tot_fb = $tot_g = $tot_cgst = $tot_sgst = $tot_igst = $tot_seo = $tot_add_project = $tot_gif_ban = $tot_linkedin = $tot_web_maint = $tot_shopify = $tot_ads_mgnt = $tot_ads_spend = 0;
	
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
	$pdf->Cell(75,10,$client_name,0,0,'L');
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(75,10,$addr1,0,0,'L');
	$pdf->Ln(5);
	
	$pdf->Cell(75,10,$addr2,0,0,'L');
	$pdf->Ln(5);
	
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(75,10,'GST ID: '.$gst,0,0,'L');
	$pdf->Ln(10);
	
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(60,10,'Invoice No',0,0,'L');
	$pdf->Cell(60,10,': BYT/SW/'.$inv_no, 0, 0, 'L');
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
	$pdf->SetFont('Arial','',9);
	
	
	$l_mon = ' - '.date('M Y', strtotime("first day of last month"));
	$c_mon = ' - '.date('M Y');
	
	if($byt_fee!=0 && $byt_fee!='' && $byt_fee_i==$invTy) {	
		if($byt_fee_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }	
		$pdf->Cell(120,7,'Digital & Social Media Marketing - Strategy & execution'.$mon, 1);		
		$tot_byt_fee = $byt_fee;
		$pdf->Cell(40,7,$tot_byt_fee,1,0,'R');
		/*$pdf->Cell(10,6,' ',0);
		$pdf->Ln();
		$pdf->Cell(120,6,'Content as explained above',1);
		$pdf->Ln();
		$pdf->Cell(120,6,'Online reputation management -as explained',1);*/
		$pdf->Ln();
	}
	
	if($fb_fee!=0 && $fb_fee!=''  && $fb_spend!=0 && $fb_fee_i==$invTy) {
		if($fb_fee_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Facebook Ads Management Fees ('.$fb_fee.'% of Rs.'.$fb_spend.')'.$mon,1,0,'L');
		$tot_fb = round($fb_spend * ($fb_fee/100));
		$pdf->Cell(40,7,$tot_fb,1,0,'R');
		$pdf->Ln();
	}
	
	if($g_fee!=0 && $g_fee!='' && $g_spend!=0 && $g_fee_i==$invTy) {
		//if($g_spend_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Google Ads Management Fees ('.$g_fee.'% of Rs.'.$g_spend.')',1,0,'L');
		$tot_g = round($g_spend * ($g_fee/100));
		$pdf->Cell(40,7,$tot_g,1,0,'R');
		$pdf->Ln();
	}
	
	if($ads_mgnt!=0 && $ads_mgnt!='' && $ads_mgnt_i==$invTy) {
		if($ads_mgnt_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Ads Management Fees'.$mon,1,0,'L');
		$tot_ads_mgnt = $ads_mgnt;
		$pdf->Cell(40,7,$ads_mgnt,1,0,'R');
		$pdf->Ln();
	}
	
	if($ads_spend!=0 && $ads_spend!='' && $ads_spend_i==$invTy) {
		if($ads_spend_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Facebook & Google Ads Spend'.$mon,1,0,'L');
		$tot_ads_spend = $ads_spend;
		$pdf->Cell(40,7,$ads_spend,1,0,'R');
		$pdf->Ln();
	}
	
	if($seo!=0 && $seo!='' && $seo_i==$invTy) {
		if($seo_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Search Engine Optimization'.$mon,1,0,'L');
		$tot_seo = $seo;
		$pdf->Cell(40,7,$seo,1,0,'R');
		$pdf->Ln();
	}
	
	if($add_project!=0 && $add_project!='' && $add_project_i==$invTy) {
		if($add_project_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'1 Additional Project'.$mon,1,0,'L');
		$tot_add_project = $add_project;
		$pdf->Cell(40,7,$add_project,1,0,'R');
		$pdf->Ln();
	}
	
	if($gif_ban!=0 && $gif_ban!='' && $gif_ban_i==$invTy) {
		if($gif_ban_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'GIF Banner Ads',1,0,'L');
		$tot_gif_ban = $gif_ban;
		$pdf->Cell(40,7,$gif_ban,1,0,'R');
		$pdf->Ln();
	}
	
	if($linkedin!=0 && $linkedin!='' && $linkedin_i==$invTy) {
		if($linkedin_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'LinkedIn Pulse and Blog Writing',1,0,'L');
		$tot_linkedin = $linkedin;
		$pdf->Cell(40,7,$linkedin,1,0,'R');
		$pdf->Ln();
	}
	
	if($web_maint!=0 && $web_maint!='' && $web_maint_i==$invTy) {
		if($web_maint_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Website Maintenance'.$mon,1,0,'L');
		$tot_web_maint = $web_maint;
		$pdf->Cell(40,7,$web_maint,1,0,'R');
		$pdf->Ln();
	}
	
	if($shopify!=0 && $shopify!='' && $shopify_i==$invTy) {
		if($shopify_c=='on') { $mon=$c_mon; } else { $mon=$l_mon; }
		$pdf->Cell(120,7,'Shopify monthly subscription fee'.$mon,1,0,'L');
		$tot_shopify = $shopify;
		$pdf->Cell(40,7,$shopify,1,0,'R');
		$pdf->Ln();
	}
	
	
	$pdf->SetFont('Arial','B',9);
	$tot_sub = round($tot_byt_fee + $tot_fb + $tot_g + $tot_seo + $tot_add_project + $tot_gif_ban + $tot_linkedin + $tot_web_maint + $tot_shopify + $tot_ads_mgnt + $tot_ads_spend);
	$pdf->Cell(120,7,'Total',1,0,'L');
	$pdf->Cell(40,7,$tot_sub,1,0,'R');
	$pdf->Ln();
	
	if($igst_gst==0) {
		$pdf->SetFont('Arial','',9);
		$pdf->Cell(120,7,'CGST '.$cgst.'%',1,0,'L');
		$tot_cgst = round($tot_sub * ($cgst/100));
		$pdf->Cell(40,7,$tot_cgst,1,0,'R');
		$pdf->Ln();
		
		$pdf->Cell(120,7,'SGST '.$sgst.'%',1,0,'L');
		$tot_sgst = round($tot_sub * ($sgst/100));
		$pdf->Cell(40,7,$tot_sgst,1,0,'R');
		$pdf->Ln();
	} else if($igst_gst==1) {
		$pdf->SetFont('Arial','',9);
		$pdf->Cell(120,7,'IGST '.$igst.'%',1,0,'L');
		$tot_igst = round($tot_sub * ($igst/100));
		$pdf->Cell(40,7,$tot_igst,1,0,'R');
		$pdf->Ln();
	}
	
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(120,7,'Total inclusive of taxes',1,0,'L');
	$tot_grand = round($tot_sub + $tot_cgst + $tot_sgst + $tot_igst);
	$pdf->Cell(40,7,$tot_grand,1,0,'R');
	
	if($tax_note!='') {
		$pdf->Ln();
		$pdf->SetFont('Arial','B',9);
		$pdf->Cell(75,10,$tax_note,0,0,'L');
		$pdf->Ln(10);
	}
	
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
	
	//$pdf->Image('pdf/faheem-sign.png',10,245,33);
	$pdf->Cell(0, 0, $pdf->InlineImage('http://bytsocial.com/fb-ads/demo/pdf/faheem-sign.png', $pdf->GetX(), $pdf->GetY(), 40), 0, 0, 'L', false );
	$pdf->Ln(0);
	
	$pdf->Cell(60,10,'Faheem S Ahmed',0,0,'L');
	$pdf->Ln(6);
	
	$pdf->SetFont('Arial','',9);
	$pdf->Cell(60,10,'Founder & CEO.,',0,0,'L');
	
	$pdf->Ln(6);
	//for($i=1;$i<=10;$i++)
	  //$pdf->Cell(0,10,'Printing line number '.$i,0,1);
	$server_path = '/home/faheems/webapps/bytsocial_com/fb-ads/demo/';
	$path= $server_path."invoices/".$fileName.".pdf";
	$pdf->Output($path,'F');
	//$pdf->Output();
}
//$duration = date('F-Y',strtotime($start));
//$fileName = str_replace(" ","_",$client_name.'_'.$duration);
//createPDF($client_name=1, $addr1=1, $addr2=1, $byt_fee=1, $gst=1, $igst_gst=1, $seo=1, $seo_m =1, $add_project=1, $add_project_m=1, $gif_ban=1,  $gif_ban_m=1, $linkedin=1, $linkedin_m =1, $web_maint=1, $web_maint_m=1, $shopify=1, $shopify_m=1, $tax_note=1, $g_acc=1, $fb_acc=1, $duration=1, $byt_fee=1, $fb_fee=1, $g_fee=1, $cgst=1, $sgst=1, $igst=1, $inv_date=1, $inv_no=1, $fb_spend=0, $g_spend=0, $fileName=1);
?>