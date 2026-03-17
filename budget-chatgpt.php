<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget and Spend Report</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Budget and Spend Report</h2>
        <div id="report-content">
            <?php
            session_start(); //exit;   
            date_default_timezone_set('Asia/Kolkata');
            ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
            include 'db.php';
            include 'functions-report.php'; 
            
            require __DIR__ . '/email/vendor/autoload.php';
            include 'email/config.php';
            
            include 'gsquare-taboola.php';
            
            function curl_get_file_contents($URL)
            {
                    $c = curl_init();
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($c, CURLOPT_URL, $URL);
                    $contents = curl_exec($c);
                    curl_close($c);
            
                    if ($contents) return $contents;
                    else return FALSE;
             }
            
            
            $query = "SELECT tbl_id, name, fb_id, g_id,access_token,g_token,g_refresh_token,g_mcc FROM users WHERE tbl_id=2";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            
            $access_token = $row['access_token']; 
            $uId = $row['tbl_id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['fb_id'] = $row['fb_id'];
            $_SESSION['g_id'] = $row['g_id'];
            $_SESSION['g_refresh_token'] = $row['g_refresh_token'];
            $_SESSION['g_token'] = $row['g_token'];
            $_SESSION['g_mcc'] = $row['g_mcc'];
            
            $g_mcc = $row['g_mcc']; 
            $g_refresh_token = $row['g_refresh_token'];
            
            $client_id = '819hf4iznbxt3r';
            $client_secret = getenv('LINKEDIN_CLIENT_SECRET');
            $userRes2 = mysqli_query($conn, "select in_id, acc_tok from users_linkedin WHERE uid='2'");	
            $getRw2 = mysqli_fetch_assoc($userRes2);
            $_SESSION['in_id'] = $getRw2['in_id'];
            $_SESSION['in_acc_tok'] = $getRw2['acc_tok'];
                                
            require_once $server_path .'vendor-linkedin/autoload.php';
            $linkedURL ="https://www.linkedin.com/oauth/v2/authorization";				
            $linkedIn = new Happyr\LinkedIn\LinkedIn($client_id, $client_secret);
            if (isset($_SESSION['in_acc_tok']) && $_SESSION['in_acc_tok']) {
              $linkedIn->setAccessToken($_SESSION['in_acc_tok']); 
            }
            $d = new DateTime('first day of this month');
            $d2 = new DateTime('today');
            $EndDate = $d2->format('Y-m-d').' 23:59:59'; // or your date as well
            $SatrtDate = $d->format('Y-m-d').' 00:00:00';
            //exit;
            
            $taboola_tok = '';
            include 'taboola-config.php';
            include 'google-ads.php';
            
            $app_id = '594832897646145';
            $tok_url = "https://graph.facebook.com/oauth/access_token_info?client_id=".$app_id."&access_token=".$access_token."";
            
            if($access_token!='') {  
                if (!$tok_req = curl_get_file_contents($tok_url)) { 
                      $pg = 'cron-fb-report';      
                      include 'email/mail-error.php';
                      exit;
                } 
            }
            function LeadGen($arr, $filt) {
                $r = 0;
                if(is_array($arr) || is_object($arr) && count($arr)>0) {
                    for($q=0; $q<count($arr); $q++) {
                            if(isset($arr[$q]['action_type']) && $arr[$q]['action_type']==$filt) $r = $arr[$q]['value'];	
                    }
                }
                return $r;
            }
            //exit;
            $gAccIds = $gStats =array();
            $today =  date('Y-m-d');
            $last90 =  date('Y-m-d', strtotime("-1 days")); 
            
            $extQ ="";
            if(isset($_GET['tbl_id'])) {
                $extQ = "tbl_id=".$_GET['tbl_id']." AND ";
            } 

            // Budget Analysis
            $sqlRev = mysqli_query($conn, "SELECT tbl_id,fb_id,fb_stDt,g_id,g_stDt,in_id,in_stDt,ta_id,ta_stDt,fb_received FROM budget_reminder WHERE $extQ uid='".$uId."' AND delete_status=0 AND hide_temp='0'");
            $budgetData = [];

            while ($sqlROW = mysqli_fetch_assoc($sqlRev)) {
                $tot_spent = 0;

                $fb_spent = $sqlROW['fb_spent'] ? array_sum(explode(',', $sqlROW['fb_spent'])) : 0;
                $g_spent = $sqlROW['g_spent'] ? array_sum(explode(',', $sqlROW['g_spent'])) : 0;
                $in_spent = $sqlROW['in_spent'] ? array_sum(explode(',', $sqlROW['in_spent'])) : 0;
                $ta_spent = $sqlROW['ta_spent'] ? array_sum(explode(',', $sqlROW['ta_spent'])) : 0;

                $tot_spent = $fb_spent + $g_spent + $in_spent + $ta_spent;
                $total_budget = $sqlROW['total_budget'] ?: 0;

                $budget_status = '';
                if ($total_budget > $tot_spent) {
                    $budget_status = 'Underspent';
                } elseif ($total_budget < $tot_spent) {
                    $budget_status = 'Overspent';
                } else {
                    $budget_status = 'On Target';
                }

                $budgetData[] = [
                    'tbl_id' => $sqlROW['tbl_id'],
                    'total_budget' => $total_budget,
                    'total_spent' => $tot_spent,
                    'status' => $budget_status
                ];
            }

            // Define date ranges
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $last_7_days = date('Y-m-d', strtotime('-7 days'));
            $last_15_days = date('Y-m-d', strtotime('-15 days'));
            $first_day_of_month = date('Y-m-01');

            // Retrieve and process data for different time periods
            $timeframes = [
                'this_month' => ['since' => $first_day_of_month, 'until' => $today],
                'last_15_days' => ['since' => $last_15_days, 'until' => $today],
                'last_7_days' => ['since' => $last_7_days, 'until' => $today],
                'yesterday' => ['since' => $yesterday, 'until' => $yesterday],
                'today' => ['since' => $today, 'until' => $today]
            ];

            $spendData = [];

            foreach ($timeframes as $key => $timeframe) {
                $dtRange = 'time_range[since]=' . $timeframe['since'] . '&time_range[until]=' . $timeframe['until'];

                // Facebook Data
                if (!empty($row['fb_id'])) {
                    $fbIds = explode(',', $row['fb_id']);
                    foreach ($fbIds as $fbId) {
                        $request_url = 'https://graph.facebook.com/v17.0/act_' . $fbId . '/insights?level=account&fields=spend,actions,cost_per_action_type&access_token=' . $access_token . '&' . $dtRange;
                        $fb_response = json_decode(curl_get_file_contents($request_url), true);

                        $spendData[$key]['fb_spent'] = isset($fb_response['data'][0]['spend']) ? round($fb_response['data'][0]['spend']) : 0;
                        $spendData[$key]['fb_leads'] = isset($fb_response['data'][0]['actions']) ? LeadGen($fb_response['data'][0]['actions'], 'lead') : 0;
                        $spendData[$key]['fb_cpl'] = isset($fb_response['data'][0]['cost_per_action_type']) ? LeadGen($fb_response['data'][0]['cost_per_action_type'], 'lead') : 0;
                    }
                }

                // Google Data
                if (!empty($row['g_id'])) {
                    $gaIds = explode(',', $row['g_id']);
                    //d($gaIds); exit;
                    foreach ($gaIds as $gaId) {
                        $getAccRep = GetCampaigns::main($conn, $_SESSION['g_refresh_token'], $_SESSION['g_mcc'], $gaId, $timeframe['since'], $timeframe['until']);
                        $spendData[$key]['g_spent'] = isset($getAccRep['cost']) ? round($getAccRep['cost']) : 0;
                    }
                }

                // LinkedIn Data
                if (!empty($row['in_id'])) {
                    $inIds = explode(',', $row['in_id']);
                    foreach ($inIds as $inId) {
                        $accQry = 'accounts[0]=urn:li:sponsoredAccount:' . $inId . '&';
                        $stDt = "dateRange.start.day=" . date('d', strtotime($timeframe['since'])) . "&dateRange.start.month=" . date('m', strtotime($timeframe['since'])) . "&dateRange.start.year=" . date('Y', strtotime($timeframe['since'])) . "&";
                        $enDt = "dateRange.end.day=" . date('d', strtotime($timeframe['until'])) . "&dateRange.end.month=" . date('m', strtotime($timeframe['until'])) . "&dateRange.end.year=" . date('Y', strtotime($timeframe['until']));
                        $val = $linkedIn->get('v2/adAnalyticsV2?' . $accQry . 'q=analytics&pivot=ACCOUNT&timeGranularity=ALL&fields=costInLocalCurrency&' . $stDt . $enDt);

                        $spendData[$key]['in_spent'] = isset($val['elements'][0]['costInLocalCurrency']) ? round($val['elements'][0]['costInLocalCurrency']) : 0;
                    }
                }

                // Taboola Data
                if (!empty($row['ta_id']) && !empty($taboola_tok)) {
                    $taIds = explode(',', $row['ta_id']);
                    foreach ($taIds as $taId) {
                        $ta_url = 'https://backstage.taboola.com/backstage/api/1.0/' . $taId . '/reports/campaign-summary/dimensions/month?access_token=' . $taboola_tok . '&start_date=' . $timeframe['since'] . '&end_date=' . $timeframe['until'];
                        $ta_res = json_decode(curl_get_file_contents($ta_url), true);

                        $spendData[$key]['ta_spent'] = isset($ta_res['results']) ? round(array_sum(array_column($ta_res['results'], 'spent'))) : 0;
                    }
                }
            }

            // Account Status
            $accountStatus = [];

            foreach ($budgetData as $account) {
                $status = 'Active';

                // Check if the account has any spend
                if ($account['total_spent'] == 0) {
                    $status = 'Zero Spend';
                }

                // Check for any account errors or suspensions (this would normally come from an API call or an error log)
                // Example: $accountError = checkForAccountErrors($account['tbl_id']);
                // if ($accountError) { $status = 'Account Error'; }

                $accountStatus[] = [
                    'tbl_id' => $account['tbl_id'],
                    'status' => $status
                ];
            }

            // Example final output generation with Bootstrap classes
            $output = '<h4>Budget Analysis</h4>';
            $output .= '<table class="table table-bordered table-striped">';
            $output .= '<thead><tr><th>Account ID</th><th>Total Budget (INR)</th><th>Total Spent (INR)</th><th>Status</th></tr></thead>';
            $output .= '<tbody>';

            foreach ($budgetData as $budget) {
                $output .= '<tr>';
                $output .= '<td>' . $budget['tbl_id'] . '</td>';
                $output .= '<td>' . number_format($budget['total_budget'], 2) . '</td>';
                $output .= '<td>' . number_format($budget['total_spent'], 2) . '</td>';
                $output .= '<td>' . $budget['status'] . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';

            // Spends, Leads & CPL Analysis
            $output .= '<h4>Spends, Leads & CPL Analysis</h4>';
            $output .= '<table class="table table-bordered table-striped">';
            $output .= '<thead><tr><th>Timeframe</th><th>FB Spent (INR)</th><th>FB Leads</th><th>FB CPL (INR)</th><th>Google Spent (INR)</th><th>LinkedIn Spent (INR)</th><th>Taboola Spent (INR)</th></tr></thead>';
            $output .= '<tbody>';

            foreach ($spendData as $timeframe => $data) {
                $output .= '<tr>';
                $output .= '<td>' . ucfirst(str_replace('_', ' ', $timeframe)) . '</td>';
                $output .= '<td>' . number_format($data['fb_spent'] ?? 0, 2) . '</td>';
                $output .= '<td>' . $data['fb_leads'] ?? 0 . '</td>';
                $output .= '<td>' . number_format($data['fb_cpl'] ?? 0, 2) . '</td>';
                $output .= '<td>' . number_format($data['g_spent'] ?? 0, 2) . '</td>';
                $output .= '<td>' . number_format($data['in_spent'] ?? 0, 2) . '</td>';
                $output .= '<td>' . number_format($data['ta_spent'] ?? 0, 2) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';

            // Account Status
            $output .= '<h4>Account Status</h4>';
            $output .= '<table class="table table-bordered table-striped">';
            $output .= '<thead><tr><th>Account ID</th><th>Status</th></tr></thead>';
            $output .= '<tbody>';

            foreach ($accountStatus as $status) {
                $output .= '<tr>';
                $output .= '<td>' . $status['tbl_id'] . '</td>';
                $output .= '<td>' . $status['status'] . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table>';

            echo $output;  // Output the generated HTML
            ?>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>