<?php include 'db.php'; 
$budget_pg =1;
date_default_timezone_set("Asia/Calcutta"); 
if(!isset($_SESSION['stDt'])) {
	$start = date('m/d/Y',strtotime('today - 30 days'));
	$end = date('m/d/Y');
	$_SESSION['stDt'] = $start;
	$_SESSION['enDt'] = $end;
}
$media_by = array(1=>'Ramesh', 2=>'RajKumar', 3=>'Simin', 4=>'Bargavi', 5=>'Shaheena', 6=>'Samadh', 7=>'Vedika', 8=>'Radhika', 9=>'Dhanush', 10=>'Nida', 11=>'Maha', 12=>'Bala', 13=>'Pavithra');
$cc_card = array(1=>'BYT', 2=>'Client'); 

$pgHeadline = 'Ads Budget - detailed report';
$pgID = 8;
$err =''; 
function moneyFormatIndia($num) {
    $explrestunits = "" ;
    if(strlen($num)>3) {
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if($i==0) {
                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Management Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    .table-container {
      margin: 20px;
    }
    .table th, .table td {
      text-align: center;
      vertical-align: middle;
    }
    .table thead {
      background-color: #2196f3;
      color: white;
    }
    .table tbody tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    .table tbody tr:nth-child(odd) {
      background-color: #e9ecef;
    }
    /* Highlight Y/N values with colors */
    .yes-cell {
      background-color: #28a745;
     
      font-weight: bold;
    }
    .no-cell {
      background-color: #dc3545;
      
      font-weight: bold;
    }
    body {
         font-family: "Helvetica Neue", Roboto, Arial, "Droid Sans", sans-serif;
    font-size: 13px;
    }
  </style>
</head>
<body>
   
  
  <div class="table-container">
    
    <table class="table table-bordered table-striped table-hover">
  <thead>
    <tr>
      <th rowspan="2">Client</th>
      <th colspan="2">Last Updated</th>
      <th rowspan="2">Budgets</th>
      <th rowspan="2">US / OS (in %)</th>
      <th colspan="2">No Leads (1d)</th>
      <th colspan="2">H CPL (CPL >3d)</th>
      <th rowspan="2">Placements</th>
    </tr>
    <tr>
      <th>M</th>
      <th>G</th>
      <th>M</th>
      <th>G</th>
      <th>M</th>
      <th>G</th>
    </tr>
  </thead>
  <tbody>
    <?php 
    $currentYear = date('Y');
    $currentMonth = date('m');
    $maxDays = date('t');
    $nDay = date("d");

    $sqlRev = mysqli_query($conn, "SELECT a.tbl_id, a.total_budget, a.fb_spent as fb_spent30, a.g_spent as g_spent30, b.fb_data,b.g_data, a.updated, a.client_name FROM budget_reminder as a, management_dash as b WHERE a.tbl_id=b.bud_tbl");

    while($sqlROW = mysqli_fetch_array($sqlRev)) {
      //d($sqlROW);
        $fb_spent = $g_spent = 0;

        if (!empty($sqlROW["fb_spent30"])) {
            $fb_spent = array_sum(array_filter(explode(',', $sqlROW["fb_spent30"])));
        }

        if (!empty($sqlROW["g_spent30"])) {
            $g_spent = array_sum(array_filter(explode(',', $sqlROW["g_spent30"])));
        }

        $tot_spend = $fb_spent + $g_spent;
        $projectedSpend = ($tot_spend / $nDay) * $maxDays;
        $totalBudget = $sqlROW["total_budget"];

        if ($projectedSpend > $totalBudget) {
            $diff = $projectedSpend - $totalBudget;
            $percentage = "" . round(($diff / $totalBudget) * 100) . " <i class='fas fa-arrow-up'></i>";
        } elseif ($projectedSpend < $totalBudget) {
            $diff = $totalBudget - $projectedSpend;
            $percentage = "" . round(($diff / $totalBudget) * 100) . " <i class='fas fa-arrow-down'></i>";
        } else {
            $percentage = "On Track";
        }

      $fb_data = unserialize($sqlROW['fb_data']);
      $g_data = unserialize($sqlROW['g_data']);
      //d($fb_data);
      // Initialize counters
      $fb_highCPLCount = $fb_zeroLeadsCount = $fb_totalAdsets = 0;
      $g_highCPLCount = $g_zeroLeadsCount = $g_totalAdsets = 0;
      $fb_mostRecentDate = $g_mostRecentDate = null;

      // --- FACEBOOK LOGIC ---
      if (is_array($fb_data)) {
    foreach ($fb_data as $entry) {
        $last3_cpl = isset($entry['last3_cpl']) ? (float)$entry['last3_cpl'] : 0;
        $last3_cpp = isset($entry['last3_cpp']) ? (float)$entry['last3_cpp'] : 0;  // Add the check for last3_cpp

        if (!empty($entry['last_updated']) && strtotime($entry['last_updated'])) {
            if (!$fb_mostRecentDate || strtotime($entry['last_updated']) > strtotime($fb_mostRecentDate)) {
                $fb_mostRecentDate = $entry['last_updated'];
            }
        }

        if (!empty($entry['adset_data']) && is_array($entry['adset_data'])) {
            foreach ($entry['adset_data'] as $adset) {
                $fb_totalAdsets++;

                // Get cpl and leads from the adset
                $cpl = isset($adset['cpl']) ? (float)$adset['cpl'] : 0;
                $leads = isset($adset['leads']) ? (float)$adset['leads'] : 0;

                // Check the value of obj and determine the value to compare
                if ($adset['obj'] == 'LEAD_GENERATION') {
                    // Compare with last3_cpl
                    if ($cpl > $last3_cpl) $fb_highCPLCount++;
                } else {
                    // Compare with last3_cpp
                    if ($cpl > $last3_cpp) $fb_highCPLCount++;
                }

                // Check for zero leads
                if ($leads == 0) $fb_zeroLeadsCount++;
            }
        }
    }
}

//d($g_data);
      // --- GOOGLE LOGIC ---
      if (is_array($g_data)) {
          foreach ($g_data as $entry) {
              $last3_cpl = isset($entry['last3_cpl']) ? (float)$entry['last3_cpl'] : 0;

              if (!empty($entry['last_updated']) && strtotime($entry['last_updated'])) {
                  if (!$g_mostRecentDate || strtotime($entry['last_updated']) > strtotime($g_mostRecentDate)) {
                      $g_mostRecentDate = $entry['last_updated'];
                  }
              }

              if (!empty($entry['adset_data']) && is_array($entry['adset_data'])) {
                  foreach ($entry['adset_data'] as $adset) {
                      $g_totalAdsets++;

                      $cpl = isset($adset['cpl']) ? (float)$adset['cpl'] : 0;
                      $leads = isset($adset['leads']) ? (float)$adset['leads'] : 0;

                      if ($cpl > $last3_cpl) $g_highCPLCount++;
                      if ($leads == 0) $g_zeroLeadsCount++;
                  }
              }
          }
      }

// --- Calculate Days Since Update ---
$today = new DateTimeImmutable('today');


//echo $fb_mostRecentDate; 
$fb_daysSinceLastUpdate = $fb_mostRecentDate
    ? $today->diff((new DateTimeImmutable($fb_mostRecentDate))->setTime(0, 0))->days
    : '-';

$g_daysSinceLastUpdate = $g_mostRecentDate
    ? $today->diff((new DateTimeImmutable($g_mostRecentDate))->setTime(0, 0))->days
    : '-';

$fb_daysSinceLastUpdateText = match (true) {
    $fb_daysSinceLastUpdate === '-'    => '-',
    $fb_daysSinceLastUpdate === 0      => 'Today',
    default                            => $fb_daysSinceLastUpdate 
};

$g_daysSinceLastUpdateText = match (true) {
    $g_daysSinceLastUpdate === '-'    => '-',
    $g_daysSinceLastUpdate === 0      => 'Today',
    default                           => $g_daysSinceLastUpdate 
};



// --- Placement Check ---
$placementFlag = 'N';
if (!empty($fb_data)) {
    foreach ($fb_data as $entry) {
        if (isset($entry['placement']) && $entry['placement'] === 'Y') {
            $placementFlag = 'Y';
            break;
        }
    }
}

    ?>
    <tr>
  <td><?php echo htmlspecialchars($sqlROW['client_name']); ?></td>
  <td><?php echo $fb_daysSinceLastUpdateText; ?></td>
  <td><?php echo $g_daysSinceLastUpdateText; ?></td>
  <td class="yes-cell"><?php echo (date('Y-m') == date('Y-m', strtotime($sqlROW['updated']))) ? 'Y' : 'N'; ?></td>
  <td><?php echo $percentage; ?></td>
  <td><?php echo ($fb_totalAdsets > 0) ? "$fb_zeroLeadsCount / $fb_totalAdsets" : '-'; ?></td>
  <td><?php echo ($g_totalAdsets > 0) ? "$g_zeroLeadsCount / $g_totalAdsets" : '-'; ?></td>
  <td><?php echo ($fb_totalAdsets > 0) ? "$fb_highCPLCount / $fb_totalAdsets" : '-'; ?></td>
  <td><?php echo ($g_totalAdsets > 0) ? "$g_highCPLCount / $g_totalAdsets" : '-'; ?></td>
  <td class="yes-cell"><?php echo $placementFlag; ?></td>
</tr>

    <?php } ?>
  </tbody>
</table>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
