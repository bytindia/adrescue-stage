<?php include 'db.php'; 
$budget_pg =1;
//error_reporting(E_ALL); ini_set('display_errors', '1');

date_default_timezone_set("Asia/Calcutta"); 

//$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh',  8=>'Radhika',  10=>'Nida', 11=>'Maha', 12=>'Bala', 14=>'Charan', 15=>'Mughil');
$media_by = array(1=>'Ramesh',  3=>'Simin',  5=>'Shaheena', 14=>'Charan', 15=>'Mughil');
$cc_card = array(1=>'BYT', 2=>'Client'); 

$per_page = 50; // Set how many records do you want to display per page.
$extQ = '';
$extQ .= " AND hide_temp='0' ";
//$startpoint = ($page * $per_page) - $per_page;
$tblN = 'budget_reminder';
$mon = 'This Month';
//$page = (int)(!isset($_GET["page"]) ? 1 : $_GET["page"]);
$maxDays = date('t');
$nDay = date("d");
//echo "SELECT * FROM ".$statement." $extQ order by cc_card asc LIMIT {$startpoint} , {$per_page}";
$statement = " $tblN WHERE uid='2' AND delete_status=0";

$sqlRev=mysqli_query($conn, "SELECT * FROM ".$statement." $extQ order by cc_card asc");
$i = (($page-1) * $per_page ) + 1;


$totRows = mysqli_num_rows($sqlRev);
$grandBudget = $grandSpent =   $grandEstSpent = $grandActSpent = $grandFbSpent = $grandGSpent = $grandBalance = $grandYestSpent = $grandMonEnd = $grandBudBal = $grandLeads = $grandLeadSpend = $grandLeads2 = $grandLeadSpend2 = $grandBudRecived = $grandClientCash = $grandRetainer = 0;

$grandBudget_byt = $grandSpent_byt =   $grandEstSpent_byt = $grandActSpent_byt = $grandFbSpent_byt = $grandGSpent_byt = $grandBalance_byt = $grandYestSpent_byt = $grandMonEnd_byt = $grandBudBal_byt = $grandLeads_byt = $grandLeadSpend_byt = $grandLeads2_byt = $grandLeadSpend2_byt = $grandBudRecived_byt = $grandClientCash_byt = $grandRetainer_byt = 0;

$grandBudget_cl = $grandSpent_cl =   $grandEstSpent_cl = $grandActSpent_cl = $grandFbSpent_cl = $grandGSpent_cl = $grandBalance_cl = $grandYestSpent_cl = $grandMonEnd_cl = $grandBudBal_cl = $grandLeads_cl = $grandLeadSpend_cl = $grandLeads2_cl = $grandLeadSpend2_cl = $grandBudRecived_cl = $grandClientCash_cl = $grandRetainer_cl = 0;

$med_bud = $med_est = $fb_acc_active = array();

$grandClicksFb = $grandClicksFbY = $grandImprFb = $grandImprFbY = 0;
$grandClicksG = $grandClicksGY = $grandImprG = $grandImprGY = 0;
$grandClicksFb_byt = $grandClicksFbY_byt = $grandImprFb_byt = $grandImprFbY_byt = 0;
$grandClicksG_byt = $grandClicksGY_byt = $grandImprG_byt = $grandImprGY_byt = 0;
$grandClicksFb_cl = $grandClicksFbY_cl = $grandImprFb_cl = $grandImprFbY_cl = 0;
$grandClicksG_cl = $grandClicksGY_cl = $grandImprG_cl = $grandImprGY_cl = 0;

$fb_leads_all = $g_leads_all = 0;
$fb_leads_byt = $g_leads_byt = 0;
$fb_leads_cc = $g_leads_cc = 0;

while($sqlROW=mysqli_fetch_array($sqlRev))
{ 
					//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
					 
            $fb_spent = $g_spent = $in_spent = $ta_spent = $tot_bal = $fb_leads_spend=  $fb_leads_spend2 = 0;
            $fb_spent_y = $g_spent_y = $in_spent_y = $ta_spent_y = $fb_leads = $fb_cpl = $fb_leads2 = $fb_cpl2 = 0;

            $fb_acc_active = array_merge($fb_acc_active, explode(',', $sqlROW["fb_id"]));

						if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
						if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
						if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
						if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }

            if($sqlROW["fb_spent_y"]!='') { $fb_spent_y = explode(',',$sqlROW["fb_spent_y"]); $fb_spent_y = array_sum(array_filter($fb_spent_y)); }
						if($sqlROW["g_spent_y"]!='') { $g_spent_y = explode(',',$sqlROW["g_spent_y"]); $g_spent_y = array_sum(array_filter($g_spent_y)); }
						if($sqlROW["in_spent_y"]!='') { $in_spent_y = explode(',',$sqlROW["in_spent_y"]); $in_spent_y = array_sum(array_filter($in_spent_y)); }
						if($sqlROW["ta_spent_y"]!='') { $ta_spent_y = explode(',',$sqlROW["ta_spent_y"]); $ta_spent_y = array_sum(array_filter($ta_spent_y)); }

                      if($sqlROW["fb_leads"]!='') { 
                        $fb_leads_exp = $fb_leads_spend = array();
                        $fb_leads_exp = explode(',',$sqlROW["fb_leads"]); 
                        $fb_leads = array_sum(array_filter($fb_leads_exp)); 
                        $fb_cpl_exp = explode(',',$sqlROW["fb_cpl"]);
                        if($fb_leads>0) {
                          foreach($fb_leads_exp as $lKey => $lval){
                            $fb_leads_spend[] = $lval * $fb_cpl_exp[$lKey];
                          }
                          $fb_leads_spend = array_sum(array_filter($fb_leads_spend));
                          $fb_cpl = round(($fb_leads_spend / $fb_leads));
                        } else {
                          $fb_leads = $fb_leads_spend = 0;
                        }
                      }

                      if($sqlROW["fb_leads2"]!='') { 
                        $fb_leads_exp2 = $fb_leads_spend2 = array();
                        $fb_leads_exp2 = explode(',',$sqlROW["fb_leads2"]); 
                        $fb_leads2 = array_sum(array_filter($fb_leads_exp2)); 
                        $fb_cpl_exp2 = explode(',',$sqlROW["fb_cpl2"] ?? '');
                        if($fb_leads2>0) {
                          foreach($fb_leads_exp2 as $lKey => $lval){
                            $fb_leads_spend2[] = $lval * $fb_cpl_exp2[$lKey];
                          }
                          $fb_leads_spend2 = array_sum(array_filter($fb_leads_spend2));
                          $fb_cpl2 = round(($fb_leads_spend2 / $fb_leads2));
                        } else {
                          $fb_leads2 = $fb_leads_spend2 = 0;
                        }
                      }
                      //if($sqlROW["fb_leads"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
					
					$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
                      $tot_spend_y = $fb_spent_y + $g_spent_y + $in_spent_y + $ta_spent_y;
                      
					if($sqlROW["total_budget"]=='') { $sqlROW["total_budget"]=0; }
                      if($sqlROW["retainer_fee"]=='') { $sqlROW["retainer_fee"]=0; }
					$tot_bal = $sqlROW["total_budget"] - $tot_spend ;

                      
					    $bud_recived = $bud_bal = $bud_reach = 0;
					                      if(isset($sqlROW['budget_received']) && $sqlROW['budget_received']!='') 
					    { 
						        $bud_recived_exp = explode(',', $sqlROW['budget_received'] ?? '');
					            $bud_recived = array_sum($bud_recived_exp);
					            
					    }
					    //$bud_bal =  $bud_recived - $sqlROW["total_budget"]; 
					    if($sqlROW["cc_card"]==2){ $bud_bal = - $sqlROW["retainer_fee"]; } else {  $bud_bal =  $bud_recived - ($tot_spend + $sqlROW["retainer_fee"]); }
					   
					    if ($bud_bal < 0) { $bal_color ='red'; } else { $bal_color ='#20be20'; }
					    
					    $client_cash = ($bud_recived-$tot_spend);
					    if ($client_cash < 0) { $cl_cash_color ='red'; } else { $cl_cash_color ='#20be20'; }
					    if($sqlROW["total_budget"]!=0) {
					      $bud_reach = @($client_cash / @($sqlROW["total_budget"]/$maxDays)); //exit;
					      $grandEstSpent = $grandEstSpent + ($sqlROW["total_budget"]/$maxDays);
					    }
					    

					    $grandBudget = $grandBudget + $sqlROW["total_budget"];
					    $grandRetainer = $grandRetainer + $sqlROW["retainer_fee"];
					    $grandSpent = $grandSpent + $tot_spend;
					    $grandBalance = $grandBalance + $tot_bal;
					    
					    $grandActSpent = $grandActSpent + ($tot_spend/$nDay);
					    $grandFbSpent = $grandFbSpent + $fb_spent;
					    $grandGSpent = $grandGSpent + $g_spent;
					    $grandYestSpent = $grandYestSpent + $tot_spend_y;
					    $grandMonEnd = $grandMonEnd + (($tot_spend/$nDay)*$maxDays);
					    $grandLeads = $grandLeads + $fb_leads;
					    $grandLeadSpend = $grandLeadSpend + $fb_leads_spend;
					    $grandLeads2 = $grandLeads2 + $fb_leads2;
					    $grandLeadSpend2 = $grandLeadSpend2 + $fb_leads_spend2;
					    $grandBudBal = $grandBudBal + $bud_bal;
					    $grandBudRecived = $grandBudRecived + $bud_recived;
					    $grandClientCash = $grandClientCash + $client_cash;
              $tag[0] = $sqlROW["client_name"];
              if($sqlROW["tags"]!='') { $tag = explode(',', $sqlROW["tags"]); }

                        $clicks_fb = isset($sqlROW['clicks_fb']) ? (int)$sqlROW['clicks_fb'] : 0;
                        $clicks_fb_y = isset($sqlROW['clicks_fb_y']) ? (int)$sqlROW['clicks_fb_y'] : 0;
                        $impr_fb = isset($sqlROW['impr_fb']) ? (int)$sqlROW['impr_fb'] : 0;
                        $impr_fb_y = isset($sqlROW['impr_fb_y']) ? (int)$sqlROW['impr_fb_y'] : 0;
                        $clicks_g = isset($sqlROW['clicks_g']) ? (int)$sqlROW['clicks_g'] : 0;
                        $clicks_g_y = isset($sqlROW['clicks_g_y']) ? (int)$sqlROW['clicks_g_y'] : 0;
                        $impr_g = isset($sqlROW['impr_g']) ? (int)$sqlROW['impr_g'] : 0;
                        $impr_g_y = isset($sqlROW['impr_g_y']) ? (int)$sqlROW['impr_g_y'] : 0;
                        $ctr_fb = ($impr_fb > 0) ? round(($clicks_fb / $impr_fb) * 100, 2) : 0;
                        $ctr_g = ($impr_g > 0) ? round(($clicks_g / $impr_g) * 100, 2) : 0;
                        // Increment all group counters
                        $grandClicksFb += $clicks_fb;
                        $grandClicksFbY += $clicks_fb_y;
                        $grandImprFb += $impr_fb;
                        $grandImprFbY += $impr_fb_y;
                        $grandClicksG += $clicks_g;
                        $grandClicksGY += $clicks_g_y;
                        $grandImprG += $impr_g;
                        $grandImprGY += $impr_g_y;

                        $fb_leads = isset($sqlROW['fb_leads']) ? (int)$sqlROW['fb_leads'] : 0;
                        $g_leads = isset($sqlROW['g_leads']) ? (int)$sqlROW['g_leads'] : 0;
                        $fb_leads_all += $fb_leads;
                        $g_leads_all += $g_leads;

                        $bud_rep['client'][] = array(
                            'name' => $sqlROW["client_name"],
                            'bud' => $sqlROW["total_budget"],
                            'bud_rcd' => $bud_recived,
                            'bud_ret' => $sqlROW["retainer_fee"],
                            'bud_bal' => $bud_bal,
                            'bal' => $tot_bal,
                            'spend' => $tot_spend,
                            'spend_est' => round(($sqlROW["total_budget"]/$maxDays)),
                            'spend_act' => round(($tot_spend/$nDay)),
                            'spend_fb' => $fb_spent,
                            'spend_g' => $g_spent,
                            'spend_y' => $tot_spend_y,
                            'spend_mon_end' => round((($tot_spend/$nDay)*$maxDays)),
                            'leads' => $fb_leads,
                            'leads_y' => $fb_leads2,
                            'tag' => trim($tag[0]),
                            'cc_card' => $cc_card[$sqlROW["cc_card"]],
                            'fb_cpl' => $fb_cpl,
                            'clicks_fb' => $clicks_fb,
                            'clicks_fb_y' => $clicks_fb_y,
                            'impr_fb' => $impr_fb,
                            'impr_fb_y' => $impr_fb_y,
                            'clicks_g' => $clicks_g,
                            'clicks_g_y' => $clicks_g_y,
                            'impr_g' => $impr_g,
                            'impr_g_y' => $impr_g_y,
                            'ctr_fb' => $ctr_fb,
                            'ctr_g' => $ctr_g,
                        );

					    if($sqlROW["cc_card"]==1){
					            $grandBudget_byt = $grandBudget_byt + $sqlROW["total_budget"];
					            $grandSpent_byt = $grandSpent + $tot_spend;
					            $grandBalance_byt = $grandBalance_byt + $tot_bal;
					            if($sqlROW["total_budget"]!=0) { $grandEstSpent_byt = $grandEstSpent_byt + ($sqlROW["total_budget"]/$maxDays); }
					            $grandActSpent_byt = $grandActSpent_byt + ($tot_spend/$nDay);
					            $grandFbSpent_byt = $grandFbSpent_byt + $fb_spent;
					            $grandGSpent_byt = $grandGSpent_byt + $g_spent;
					            $grandYestSpent_byt = $grandYestSpent_byt + $tot_spend_y;
					            $grandMonEnd_byt = $grandMonEnd_byt + (($tot_spend/$nDay)*$maxDays);
					            $grandLeads_byt = $grandLeads_byt + $fb_leads;
					            $grandLeadSpend_byt = $grandLeadSpend_byt + $fb_leads_spend;
					            $grandLeads2_byt = $grandLeads2_byt + $fb_leads2;
					            $grandLeadSpend2_byt = $grandLeadSpend2_byt + $fb_leads_spend2;
					            $grandBudBal_byt = $grandBudBal_byt + $bud_bal;
					            $grandBudRecived_byt = $grandBudRecived_byt + $bud_recived;
					            $grandClientCash_byt = $grandClientCash_byt + $client_cash;
					            $grandRetainer_byt = $grandRetainer_byt + $sqlROW["retainer_fee"];
					            $grandClicksFb_byt += $clicks_fb;
					            $grandClicksFbY_byt += $clicks_fb_y;
					            $grandImprFb_byt += $impr_fb;
					            $grandImprFbY_byt += $impr_fb_y;
					            $grandClicksG_byt += $clicks_g;
					            $grandClicksGY_byt += $clicks_g_y;
					            $grandImprG_byt += $impr_g;
					            $grandImprGY_byt += $impr_g_y;
					            $fb_leads_byt += $fb_leads;
					            $g_leads_byt += $g_leads;
					    }
					    
					    if($sqlROW["cc_card"]==2){
                                $grandBudget_cl = $grandBudget_cl + $sqlROW["total_budget"];
                                $grandSpent_cl = $grandSpent + $tot_spend;
                                $grandBalance_cl = $grandBalance_cl + $tot_bal;
                                if($sqlROW["total_budget"]!=0) { $grandEstSpent_cl = $grandEstSpent_cl + ($sqlROW["total_budget"]/$maxDays); }
                                $grandActSpent_cl = $grandActSpent_cl + ($tot_spend/$nDay);
                                $grandFbSpent_cl = $grandFbSpent_cl + $fb_spent;
                                $grandGSpent_cl = $grandGSpent_cl + $g_spent;
                                $grandYestSpent_cl = $grandYestSpent_cl + $tot_spend_y;
                                $grandMonEnd_cl = $grandMonEnd_cl + (($tot_spend/$nDay)*$maxDays);
                                $grandLeads_cl = $grandLeads_cl + $fb_leads;
                                $grandLeadSpend_cl = $grandLeadSpend_cl + $fb_leads_spend;
                                $grandLeads2_cl = $grandLeads2_cl + $fb_leads2;
                                $grandLeadSpend2_cl = $grandLeadSpend2_cl + $fb_leads_spend2;
                                $grandBudBal_cl = $grandBudBal_cl + $bud_bal;
                                $grandBudRecived_cl = $grandBudRecived_cl + $bud_recived;
                                $grandClientCash_cl = $grandClientCash_cl + $client_cash;
                                $grandRetainer_cl = $grandRetainer_cl + $sqlROW["retainer_fee"];
                                $grandClicksFb_cl += $clicks_fb;
                                $grandClicksFbY_cl += $clicks_fb_y;
                                $grandImprFb_cl += $impr_fb;
                                $grandImprFbY_cl += $impr_fb_y;
                                $grandClicksG_cl += $clicks_g;
                                $grandClicksGY_cl += $clicks_g_y;
                                $grandImprG_cl += $impr_g;
                                $grandImprGY_cl += $impr_g_y;
                                $fb_leads_cc += $fb_leads;
                                $g_leads_cc += $g_leads;
					  }

					  $med_bud[$sqlROW["media_by"]][] = $sqlROW["total_budget"];
					  $med_est[$sqlROW["media_by"]][] = (($tot_spend/$nDay)*$maxDays);

                      $i++;
        
}

foreach($med_bud as $k => $v){ 
    if(isset($media_by[$k])){                    
        $tot_bud = array_sum($med_bud[$k]);
        $tot_est = array_sum($med_est[$k]);
        $tot_dif = $tot_bud - $tot_est;
        $bud_2per = $tot_bud * 0.05;
        if($tot_dif>0) { $spendTy= 'Under'; $penalty = $tot_dif - $bud_2per; } else { $spendTy= 'Over'; $penalty = ($tot_dif + $bud_2per) * -1; }
        if( $penalty > 0 ) { $bgcolor='#f97878'; } else { $bgcolor='#14bb34'; $penalty=0; }
        $bud_rep['media_buyer'][] = array('name'=>$media_by[$k], 'bud'=>round($tot_bud), 'mon_end_est'=>round($tot_est), 'est_diff'=>round($tot_dif), 'est_diff_per'=>round(($tot_dif/$tot_bud)*100), 'sp_type'=>$spendTy);
    }
}
$bud_rep['all'] = array(
    'bud' => $grandBudget,
    'bud_rcd' => $grandBudRecived,
    'bud_ret' => $grandRetainer,
    'bud_bal' => $grandBudBal,
    'bal' => $grandBalance,
    'spend' => $grandSpent,
    'spend_est' => round($grandEstSpent),
    'spend_act' => round($grandActSpent),
    'spend_fb' => $grandFbSpent,
    'spend_g' => $grandGSpent,
    'spend_y' => $grandYestSpent,
    'spend_mon_end' => round($grandMonEnd),
    'leads' => $grandLeads,
    'leads_y' => $grandLeads2,
    'clicks_fb' => $grandClicksFb,
    'clicks_fb_y' => $grandClicksFbY,
    'impr_fb' => $grandImprFb,
    'impr_fb_y' => $grandImprFbY,
    'clicks_g' => $grandClicksG,
    'clicks_g_y' => $grandClicksGY,
    'impr_g' => $grandImprG,
    'impr_g_y' => $grandImprGY,
    'ctr_fb' => ($grandImprFb > 0) ? round(($grandClicksFb / $grandImprFb) * 100, 2) : 0,
    'ctr_g' => ($grandImprG > 0) ? round(($grandClicksG / $grandImprG) * 100, 2) : 0,
    'tot_clicks' => $grandClicksFb + $grandClicksG,
    'tot_impr' => $grandImprFb + $grandImprG,
    'tot_ctr' => (($grandImprFb + $grandImprG) > 0) ? round((($grandClicksFb + $grandClicksG) / ($grandImprFb + $grandImprG)) * 100, 2) : 0,
    'fb_leads' => $fb_leads_all,
    'g_leads' => $g_leads_all,
    'tot_leads' => $fb_leads_all + $g_leads_all,
    'fb_cpl' => ($fb_leads_all > 0) ? round($grandFbSpent / $fb_leads_all, 2) : 0,
    'g_cpl' => ($g_leads_all > 0) ? round($grandGSpent / $g_leads_all, 2) : 0,
    'tot_cpl' => ($fb_leads_all + $g_leads_all > 0) ? round(($grandFbSpent + $grandGSpent) / ($fb_leads_all + $g_leads_all), 2) : 0,
);

$bud_rep['byt'] = array(
    'bud' => $grandBudget_byt,
    'bud_rcd' => $grandBudRecived_byt,
    'bud_ret' => $grandRetainer_byt,
    'bud_bal' => $grandBudBal_byt,
    'bal' => $grandBalance_byt,
    'spend' => $grandSpent_byt,
    'spend_est' => round($grandEstSpent_byt),
    'spend_act' => round($grandActSpent_byt),
    'spend_fb' => $grandFbSpent_byt,
    'spend_g' => $grandGSpent_byt,
    'spend_y' => $grandYestSpent_byt,
    'spend_mon_end' => round($grandMonEnd_byt),
    'leads' => $grandLeads_byt,
    'leads_y' => $grandLeads2_byt,
    'clicks_fb' => $grandClicksFb_byt,
    'clicks_fb_y' => $grandClicksFbY_byt,
    'impr_fb' => $grandImprFb_byt,
    'impr_fb_y' => $grandImprFbY_byt,
    'clicks_g' => $grandClicksG_byt,
    'clicks_g_y' => $grandClicksGY_byt,
    'impr_g' => $grandImprG_byt,
    'impr_g_y' => $grandImprGY_byt,
    'ctr_fb' => ($grandImprFb_byt > 0) ? round(($grandClicksFb_byt / $grandImprFb_byt) * 100, 2) : 0,
    'ctr_g' => ($grandImprG_byt > 0) ? round(($grandClicksG_byt / $grandImprG_byt) * 100, 2) : 0,
    'tot_clicks' => $grandClicksFb_byt + $grandClicksG_byt,
    'tot_impr' => $grandImprFb_byt + $grandImprG_byt,
    'tot_ctr' => (($grandImprFb_byt + $grandImprG_byt) > 0) ? round((($grandClicksFb_byt + $grandClicksG_byt) / ($grandImprFb_byt + $grandImprG_byt)) * 100, 2) : 0,
    'fb_leads' => $fb_leads_byt,
    'g_leads' => $g_leads_byt,
    'tot_leads' => $fb_leads_byt + $g_leads_byt,
    'fb_cpl' => ($fb_leads_byt > 0) ? round($grandFbSpent_byt / $fb_leads_byt, 2) : 0,
    'g_cpl' => ($g_leads_byt > 0) ? round($grandGSpent_byt / $g_leads_byt, 2) : 0,
    'tot_cpl' => ($fb_leads_byt + $g_leads_byt > 0) ? round(($grandFbSpent_byt + $grandGSpent_byt) / ($fb_leads_byt + $g_leads_byt), 2) : 0,
);

$bud_rep['cc'] = array(
    'bud' => $grandBudget_cl,
    'bud_rcd' => $grandBudRecived_cl,
    'bud_ret' => $grandRetainer_cl,
    'bud_bal' => $grandBudBal_cl,
    'bal' => $grandBalance_cl,
    'spend' => $grandSpent_cl,
    'spend_est' => round($grandEstSpent_cl),
    'spend_act' => round($grandActSpent_cl),
    'spend_fb' => $grandFbSpent_cl,
    'spend_g' => $grandGSpent_cl,
    'spend_y' => $grandYestSpent_cl,
    'spend_mon_end' => round($grandMonEnd_cl),
    'leads' => $grandLeads_cl,
    'leads_y' => $grandLeads2_cl,
    'clicks_fb' => $grandClicksFb_cl,
    'clicks_fb_y' => $grandClicksFbY_cl,
    'impr_fb' => $grandImprFb_cl,
    'impr_fb_y' => $grandImprFbY_cl,
    'clicks_g' => $grandClicksG_cl,
    'clicks_g_y' => $grandClicksGY_cl,
    'impr_g' => $grandImprG_cl,
    'impr_g_y' => $grandImprGY_cl,
    'ctr_fb' => ($grandImprFb_cl > 0) ? round(($grandClicksFb_cl / $grandImprFb_cl) * 100, 2) : 0,
    'ctr_g' => ($grandImprG_cl > 0) ? round(($grandClicksG_cl / $grandImprG_cl) * 100, 2) : 0,
    'tot_clicks' => $grandClicksFb_cl + $grandClicksG_cl,
    'tot_impr' => $grandImprFb_cl + $grandImprG_cl,
    'tot_ctr' => (($grandImprFb_cl + $grandImprG_cl) > 0) ? round((($grandClicksFb_cl + $grandClicksG_cl) / ($grandImprFb_cl + $grandImprG_cl)) * 100, 2) : 0,
    'fb_leads' => $fb_leads_cc,
    'g_leads' => $g_leads_cc,
    'tot_leads' => $fb_leads_cc + $g_leads_cc,
    'fb_cpl' => ($fb_leads_cc > 0) ? round($grandFbSpent_cl / $fb_leads_cc, 2) : 0,
    'g_cpl' => ($g_leads_cc > 0) ? round($grandGSpent_cl / $g_leads_cc, 2) : 0,
    'tot_cpl' => ($fb_leads_cc + $g_leads_cc > 0) ? round(($grandFbSpent_cl + $grandGSpent_cl) / ($fb_leads_cc + $g_leads_cc), 2) : 0,
);
//d($bud_rep); exit;

//Leads
$sqlRev2=mysqli_query($conn, "SELECT leads.page_id, pages.pg_name, COUNT(*) as tot FROM `leads` JOIN `pages` ON leads.page_id = pages.pg_id WHERE MONTH(leads.created) = MONTH(CURRENT_DATE) AND YEAR(leads.created) = YEAR(CURRENT_DATE) GROUP BY leads.page_id, pages.pg_name ORDER BY tot DESC");
while($sqlROW=mysqli_fetch_array($sqlRev2))
{ 
  $bud_rep['leads'][]  = array('name'=>$sqlROW["pg_name"], 'leads'=>$sqlROW["tot"]);
}

$query = "SELECT ai.bud_rem_id, br.tags, SUM(CAST(ai.spend AS UNSIGNED)) AS total_spend, SUM(CAST(ai.lead AS UNSIGNED)) AS total_leads, SUM(CAST(ai.pur AS UNSIGNED)) AS total_purchases, SUM(CAST(ai.pur_val AS UNSIGNED)) AS total_purchase_value FROM acc_insights as ai JOIN budget_reminder br ON ai.bud_rem_id = br.tbl_id  and ai.mon_yr='".date('m-Y')."' GROUP BY ai.bud_rem_id";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
  $tag[0] = $row["client_name"];
  if($row["tags"]!='') { $tag = explode(',', $row["tags"]); }
    $bud_rep['acc_insights'][]  = array('name'=>$tag[0], 'spend'=>$row['total_spend'], 'leads'=>$row['total_leads'], 'purchase'=>$row['total_purchases'], 'purchase_val'=>$row['total_purchase_value']);
}

$query = "SELECT l.tbl_id, l.client_name, l.fb_acc, l.g_acc, g.name as gname, f.name as fname, l.email_ids FROM accounts_invoice as l LEFT JOIN gaccounts as g ON l.g_acc=g.account_id AND g.uid='2' LEFT JOIN adAccounts as f ON l.fb_acc=f.account_id AND f.uid='2' WHERE l.uid='2' AND l.admin_delete=0 order by l.tbl_id desc";

$result = mysqli_query($conn, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $bud_rep['inv_acc'][]  = array('id'=>$row["tbl_id"], 'name'=>$row["client_name"], 'email_ids'=>$row["email_ids"] );
}

// Troubleshoot
$extQ = '';
$objectives = [
    'LEAD_GENERATION' => ['metric' => 'lead', 'valueKey' => 'lead', 'name'=>'LG', 'key'=>'lg', 'cpl'=>'CPL', 'name2'=>'Lead'],
    'CONVERSIONS' => ['metric' => 'offsite_conversion', 'valueKey' => 'offsite_conversion.fb_pixel_lead', 'name'=>'Conv.', 'key'=>'conv', 'cpl'=>'CPC', 'name2'=>'Conversion'],
    'OUTCOME_SALES' => ['metric' => 'purchase', 'valueKey' => 'purchase', 'name'=>'LG', 'name'=>'Ecom.', 'key'=>'sale', 'cpl'=>'CPP', 'name2'=>'Purchase']
];
$client_name = $last30d_cpl = array();
$sqlRev=mysqli_query($conn, "SELECT acc_id,acc_name FROM adset_report WHERE  delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[$sqlROW['acc_id']] = $sqlROW['acc_name'];
}

$sqlRev=mysqli_query($conn, "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $acc_data[$sqlROW['acc_id']] = array('spend'=>round($sqlROW['spend']), 'leads'=>round($sqlROW['leads']), 'cpl'=>round($sqlROW['cpl']));
    $report_dt = date('d-m-Y h:i a', strtotime($sqlROW['updated']));
}

$sqlRev=mysqli_query($conn, "SELECT * FROM adset_report  WHERE {$extQ} delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $ecom_cpl = 0;
    if($sqlROW['ecom_lead']>0 && $sqlROW['spend']>0) { $ecom_cpl = $sqlROW['ecom_lead']/$sqlROW['spend']; }
    $acc_data[$sqlROW['acc_id']] = array('spend'=>round($sqlROW['spend']), 'leads'=>round($sqlROW['leads']), 'cpl'=>round($sqlROW['cpl']), 'ecom_lead'=>round($sqlROW['ecom_lead']),'ecom_cpl'=>round($ecom_cpl));
}

$sqlRev=mysqli_query($conn, "SELECT * FROM adset_report_data $extQ ");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
    /*$pecentage =  round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100);
    
    $bg='';
    if($pecentage>0 && $pecentage<25) { $bg= 'bg0'; } 
    elseif($pecentage>24 && $pecentage<50) { $bg= 'bg25'; } 
    elseif($pecentage>49 && $pecentage<75) { $bg= 'bg50'; } 
    elseif($pecentage>74) { $bg= 'bg75'; } */
    $ecom_lead = $ecom_cpl = $ecom_lead_acc = $ecom_cpl_acc = '';
    //$as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
    if($sqlROW['obj']=='OUTCOME_SALES' && $sqlROW['ecom_lead']!='' && $sqlROW['spend']>0) {
        $ecom_lead = '<br><small class="ecom">'.$sqlROW['ecom_lead'].'</small>';
        $ecom_cpl = '<br><small class="ecom">'.round($sqlROW['spend']/$sqlROW['ecom_lead']).'</small>';
        
    }
    if($sqlROW['obj']=='OUTCOME_SALES' && $acc_data[$sqlROW['acc_id']]['ecom_lead']>0) {
        $ecom_lead_acc = ' - '.$acc_data[$sqlROW['acc_id']]['ecom_lead'].'';
        $ecom_cpl_acc = ' - '.round($acc_data[$sqlROW['acc_id']]['spend']/$acc_data[$sqlROW['acc_id']]['ecom_lead']).'';
    }
    $st_clour = '#db3f3fde';
    if($sqlROW['adset_status']=='yes'){ $st_clour = '#58d558'; }

    $bud_rep['troubleshoot'][]  = array('name'=>$client_name[$sqlROW['acc_id']], 'adset'=>$sqlROW['adset_name'], 'campaign'=>$sqlROW['camp_name'], 'obj'=>$objectives[$sqlROW['obj']]['name'], 'spend'=>$fmt->format(round($sqlROW['spend'])), 'results'=>$sqlROW['leads'], 'cpa'=>$fmt->format(round($sqlROW['cpl'])).''.$ecom_cpl, 'spend_acc'=>$fmt->format(round($acc_data[$sqlROW['acc_id']]['spend'])), 'results_acc'=>$fmt->format($acc_data[$sqlROW['acc_id']]['leads']).''.$ecom_lead_acc, 'cpa_acc'=>$fmt->format(round($acc_data[$sqlROW['acc_id']]['cpl'])).''.$ecom_cpl_acc);
}


//Cashflow

$sqlRev=mysqli_query($conn, "SELECT * FROM cashflow2024 WHERE uid='2' AND delete_status=0 order by tbl_id desc ");
$totRows = mysqli_num_rows($sqlRev);
$grandRecived = $grandSpent = $grandBalance = $i = 0;

while($sqlROW=mysqli_fetch_array($sqlRev))
{ 
	//$tot_spend = $sqlROW["fb_spent"]+$sqlROW["g_spent"]+$sqlROW["in_spent"]+$sqlROW["ta_spent"];
	
	$fb_spent = $g_spent = $in_spent = $ta_spent = 0;
	
	if($sqlROW["fb_spent"]!='') { $fb_spent = explode(',',$sqlROW["fb_spent"]); $fb_spent = array_sum(array_filter($fb_spent)); }
	if($sqlROW["g_spent"]!='') { $g_spent = explode(',',$sqlROW["g_spent"]); $g_spent = array_sum(array_filter($g_spent)); }
	if($sqlROW["in_spent"]!='') { $in_spent = explode(',',$sqlROW["in_spent"]); $in_spent = array_sum(array_filter($in_spent)); }
	if($sqlROW["ta_spent"]!='') { $ta_spent = explode(',',$sqlROW["ta_spent"]); $ta_spent = array_sum(array_filter($ta_spent)); }
	
	$tot_spend = $fb_spent + $g_spent + $in_spent + $ta_spent;
	
	$tot_bal = $sqlROW["tot_paid"] - $tot_spend + $sqlROW["tot_penalty"];

  $grandRecived = $grandRecived + $sqlROW["tot_paid"];
  $grandSpent = $grandSpent + $tot_spend;
  $grandBalance = $grandBalance + $tot_bal;

  $bud_rep['cashflow'][] = array('name'=>$sqlROW["client_name"], 'received'=>round($sqlROW["tot_paid"]), 'spend'=>round($tot_spend), 'balance'=>round($tot_bal));
  $i++;
  if($i==($totRows+1)){
    $bud_rep['cashflow'][] = array('name'=>'Total', 'received'=>round($grandRecived), 'spend'=>round($grandSpent), 'balance'=>round($grandBalance));
  }
}


//ad account
$sqlRev=mysqli_query($conn, "SELECT * FROM adAccounts WHERE uid='2' AND account_status=1 order by name asc ");
								
while($row=mysqli_fetch_array($sqlRev))
{
    $acc_name[$row["account_id"]] = $row["name"];
    //$bud_rep['ad_acc'][]  = array('id'=>$row["account_id"], 'name'=>$row["name"]);
}
$fb_acc_active = array_unique($fb_acc_active);
$fb_acc_active = array_map('trim', $fb_acc_active);

foreach ($fb_acc_active as $id) {
  if (isset($acc_name[$id])) {
      //echo "ID: $id, Name: " . $acc_name[$id] . "<br>";
      $bud_rep['ad_acc'][]  = array('id'=>$id, 'name'=>$acc_name[$id]);
  } 
}


//Ad Tracker

$client_name = $last30d_cpl = array();
$sqlRev=mysqli_query($conn, "SELECT acc_id,client_name FROM troubleshoot WHERE uid='2' AND delete_status=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
}
$ext_q = $ext_q2 = 'leads>0 AND';
$sqlRev=mysqli_query($conn, "SELECT acc_id,obj,cpl FROM troubleshoot_cpl WHERE cpl>0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
    $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']] = $sqlROW['cpl'];
    $ext_q .= " (acc_id='".$sqlROW['acc_id']."' AND obj='".$sqlROW['obj']."' AND cpl>".round($sqlROW['cpl'])." ) OR ";
    $ext_q2 .= " (acc_id='".$sqlROW['acc_id']."' AND obj='".$sqlROW['obj']."' AND cpl<".round($sqlROW['cpl'])." AND cpl!=0 ) OR ";
}
$zero_lead_sum = array();
$cirRes = mysqli_query($conn, "SELECT acc_id, obj, COUNT(*) AS tot_lead_0 FROM troubleshoot_data WHERE leads=0 GROUP BY acc_id, obj");	
while($sqlROW=mysqli_fetch_array($cirRes))
{
    $zero_lead_sum[$sqlROW['obj']][] = array('acc_id'=>$sqlROW['acc_id'], 'acc_name'=>$client_name[$sqlROW['acc_id']], 'tot_adset'=>$sqlROW['tot_lead_0'], 'chart_div'=>$objectives[$sqlROW['obj']]['key'], 'title'=>$objectives[$sqlROW['obj']]['name2']);
}
//AdTracker - zero lead
$sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE leads=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    //$client_name[str_replace('act_', '', $sqlROW['acc_id'])] = $sqlROW['client_name'];
    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
    $bud_rep['zero_lead'][] = array('client'=>$client_name[$sqlROW['acc_id']], 'ad_set'=>$sqlROW['adset_name'], 'campaign'=>$sqlROW['camp_name'], 'objective'=>$objectives[$sqlROW['obj']]['name'], 'spend'=>round($sqlROW['spend']));
}

$sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE {$ext_q} id=0");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
  $pecentage =  round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100);
  $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
  $bud_rep['high_cpl'][] = array('client'=>$client_name[$sqlROW['acc_id']], 'ad_set'=>$sqlROW['adset_name'], 'campaign'=>$sqlROW['camp_name'], 'objective'=>$objectives[$sqlROW['obj']]['name'], 'spend'=>round($sqlROW['spend']), 'leads'=>$sqlROW['leads'], 'cpl'=>$sqlROW['cpl'], 'last30d_cpl'=>$last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]);
}

$sqlRev=mysqli_query($conn, "SELECT * FROM troubleshoot_data  WHERE {$ext_q2} id=0 ");										
while($sqlROW=mysqli_fetch_array($sqlRev))
{
    $pecentage =  abs(round((($sqlROW['cpl'] - $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) / $last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]) * 100));
    $as_link = $fb_url_adset.''.$sqlROW['acc_id'].''.$qry_str_adset.''.$sqlROW['adset_id'].''.$dt_qry_3;
    $bud_rep['low_cpl'][] = array('client'=>$client_name[$sqlROW['acc_id']], 'ad_set'=>$sqlROW['adset_name'], 'campaign'=>$sqlROW['camp_name'], 'objective'=>$objectives[$sqlROW['obj']]['name'], 'spend'=>round($sqlROW['spend']), 'leads'=>$sqlROW['leads'], 'cpl'=>$sqlROW['cpl'], 'last30d_cpl'=>$last30d_cpl[$sqlROW['acc_id']][$sqlROW['obj']]);
}


// =====================
// Add daily_budget data
// =====================
$bud_rep['daily_budget'] = array();
$sqlTopup = mysqli_query($conn, "SELECT t.client, t.daily_budget_fb, t.daily_budget, t.bud_tbl_id, b.tags, b.total_budget, b.fb_spent, b.g_spent FROM topup t LEFT JOIN budget_reminder b ON t.bud_tbl_id = b.tbl_id");
while ($row = mysqli_fetch_assoc($sqlTopup)) {
    // Sum comma-separated values for fb_budget and g_budget
    $fb_budget = 0;
    $g_budget = 0;
    if ($row['daily_budget_fb'] !== null && $row['daily_budget_fb'] !== '') {
        $fb_parts = explode(',', $row['daily_budget_fb']);
        foreach ($fb_parts as $val) {
            $fb_budget += (int)trim($val);
        }
    }
    if ($row['daily_budget'] !== null && $row['daily_budget'] !== '') {
        $g_parts = explode(',', $row['daily_budget']);
        foreach ($g_parts as $val) {
            $g_budget += (int)trim($val);
        }
    }
    // Parse and sum fb_spent and g_spent (comma-separated)
    $fb_spent = 0;
    if (!empty($row['fb_spent'])) {
        $fb_spent = array_sum(array_filter(explode(',', $row['fb_spent'])));
    }
    $g_spent = 0;
    if (!empty($row['g_spent'])) {
        $g_spent = array_sum(array_filter(explode(',', $row['g_spent'])));
    }
    $tot_spend = $fb_spent + $g_spent;
    $bud_rep['daily_budget'][] = array(
        'client' => $row['client'],
        'fb_budget' => $fb_budget,
        'g_budget' => $g_budget,
        'tags' => $row['tags'],
        'total_budget' => $row['total_budget'],
        'fb_spent' => $fb_spent,
        'g_spent' => $g_spent,
        'tot_spend' => $tot_spend
    );
}

// =====================
// Add no_lead_counts data
// =====================
$bud_rep['no_lead_counts'] = array();
$sqlNoLead = mysqli_query($conn, "SELECT a.tbl_id, a.client_name, b.fb_data, b.g_data,a.tags FROM budget_reminder as a, management_dash as b WHERE a.tbl_id=b.bud_tbl");
while($row = mysqli_fetch_array($sqlNoLead)) {
    $fb_data = @unserialize($row['fb_data']);
    $g_data = @unserialize($row['g_data']);
    $meta_zero_leads = $meta_total_adsets = 0;
    $google_zero_leads = $google_total_adsets = 0;
    // Meta (Facebook)
    if (is_array($fb_data)) {
        foreach ($fb_data as $entry) {
            if (!empty($entry['adset_data']) && is_array($entry['adset_data'])) {
                foreach ($entry['adset_data'] as $adset) {
                    $meta_total_adsets++;
                    $leads = isset($adset['leads']) ? (float)$adset['leads'] : 0;
                    if ($leads == 0) $meta_zero_leads++;
                }
            }
        }
    }
    // Google
    if (is_array($g_data)) {
        foreach ($g_data as $entry) {
            if (!empty($entry['adset_data']) && is_array($entry['adset_data'])) {
                foreach ($entry['adset_data'] as $adset) {
                    $google_total_adsets++;
                    $leads = isset($adset['leads']) ? (float)$adset['leads'] : 0;
                    if ($leads == 0) $google_zero_leads++;
                }
            }
        }
    }
    $bud_rep['no_lead_counts'][] = array(
        'client' => $row['tags'],
        'meta_zero_leads' => $meta_zero_leads,
        'meta_total_adsets' => $meta_total_adsets,
        'google_zero_leads' => $google_zero_leads,
        'google_total_adsets' => $google_total_adsets
    );
}


header('Content-Type: application/json; charset=utf-8');
echo json_encode($bud_rep);
