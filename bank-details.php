<?php
// Database configuration
$conn = mysqli_connect('localhost', 'digitalb2k_adsninja', getenv('DB_PASS'), 'digitalb2k_adsninja');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Initialize variables
$debtor = 0;
$creditor = 0;
$receivable = 0;
$payable = 0;
$savings_balance = 0;
$od_balance = 0;
$credit_card_balance = 0;

// Fetch data from tally_payments table
$query = "SELECT data FROM tally_payments";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $json_data = $row['data'];
        $data = json_decode($json_data, true);
        
        if ($data && isset($data['type']) && isset($data['entries']) && is_array($data['entries'])) {
            $type = $data['type'];
            $entries = $data['entries'];
            
            // Calculate total amount from entries
            $total_amount = 0;
            foreach ($entries as $entry) {
                if (isset($entry['amount'])) {
                    // Sum absolute values of amounts
                    $total_amount += abs($entry['amount']);
                }
            }
            
            // Assign to appropriate category based on type
            if ($type == 'Payment') {
                $debtor += $total_amount;
            } elseif ($type == 'Receipt') {
                $creditor += $total_amount;
            }
        }
    }
}

// For other values, you can add queries here
// $receivable = ...;
// $payable = ...;
// $savings_balance = ...;
// $od_balance = ...;
// $credit_card_balance = ...;

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Details Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
            padding: 20px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 40px;
            padding: 20px 0;
        }

        .dashboard-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .dashboard-header p {
            font-size: 1rem;
            color: #7f8c8d;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .bank-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid #e9ecef;
            height: auto;
        }

        .bank-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #667eea, #764ba2);
            transform: scaleY(0);
            transform-origin: top;
            transition: transform 0.3s ease;
        }

        .bank-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            border-color: #dee2e6;
        }

        .bank-card:hover::before {
            transform: scaleY(1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-title .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ff4757;
            display: inline-block;
            flex-shrink: 0;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .bank-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
        }

        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.2;
            margin-top: 8px;
        }

        .card-value.positive {
            color: #27ae60;
        }

        .card-value.negative {
            color: #e74c3c;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0;
            font-weight: 400;
            margin-bottom: 0;
        }

        /* Specific card colors */
        .card-doctor .card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card-creditor .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .card-receivable .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .card-payable .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .card-savings .card-icon {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
        }

        .card-od .card-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        .card-credit-card .card-icon {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        /* Bank Balance Card with sub-items */
        .bank-balance-card {
            grid-column: span 1;
        }

        .sub-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .sub-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
            border-left: 3px solid;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .sub-card:hover {
            background: #e9ecef;
            transform: translateX(3px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .sub-card.savings {
            border-left-color: #30cfd0;
        }

        .sub-card.od {
            border-left-color: #fed6e3;
        }

        .sub-card-title {
            font-size: 0.7rem;
            color: #6c757d;
            margin-bottom: 4px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sub-card-value {
            font-size: 1rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }

            .card-value {
                font-size: 1.5rem;
            }

            .sub-cards {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .bank-card {
                padding: 18px;
            }

            .card-value {
                font-size: 1.5rem;
            }

            .sub-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bank-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .bank-card:nth-child(1) { animation-delay: 0.1s; }
        .bank-card:nth-child(2) { animation-delay: 0.2s; }
        .bank-card:nth-child(3) { animation-delay: 0.3s; }
        .bank-card:nth-child(4) { animation-delay: 0.4s; }
        .bank-card:nth-child(5) { animation-delay: 0.5s; }
        .bank-card:nth-child(6) { animation-delay: 0.6s; }
        .bank-card:nth-child(7) { animation-delay: 0.7s; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1><i class="fas fa-university"></i> Bank Details Dashboard</h1>
            <p>Financial Overview & Account Balances</p>
        </div>

        <div class="cards-grid">
            <!-- Debtor Card -->
            <div class="bank-card card-doctor">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        DEBITOR
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo formatCurrency($debtor); ?></div>
                <div class="card-subtitle">Total Payment Amount</div>
            </div>

            <!-- Creditor Card -->
            <div class="bank-card card-creditor">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        CREDITOR
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo formatCurrency($creditor); ?></div>
                <div class="card-subtitle">Total Receipt Amount</div>
            </div>

            <!-- Receivable Card -->
            <div class="bank-card card-receivable">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        RECEIVABLE
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-arrow-circle-down"></i>
                    </div>
                </div>
                <div class="card-value positive"><?php echo formatCurrency($receivable); ?></div>
                <div class="card-subtitle">Amount to Receive</div>
            </div>

            <!-- Payable Card -->
            <div class="bank-card card-payable">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        PAYABLE
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-arrow-circle-up"></i>
                    </div>
                </div>
                <div class="card-value negative"><?php echo formatCurrency($payable); ?></div>
                <div class="card-subtitle">Amount to Pay</div>
            </div>

            <!-- Bank Balance Card -->
            <div class="bank-card bank-balance-card card-savings">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        BANK BALANCE
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                </div>
                <div class="card-value"><?php echo formatCurrency($savings_balance + $od_balance); ?></div>
                <div class="card-subtitle">Total Bank Balance</div>
                
                <div class="sub-cards">
                    <div class="sub-card savings">
                        <div class="sub-card-title">Savings</div>
                        <div class="sub-card-value positive"><?php echo formatCurrency($savings_balance); ?></div>
                    </div>
                    <div class="sub-card od">
                        <div class="sub-card-title">OD (Overdraft)</div>
                        <div class="sub-card-value <?php echo $od_balance < 0 ? 'negative' : 'positive'; ?>"><?php echo formatCurrency($od_balance); ?></div>
                    </div>
                </div>
            </div>

            <!-- Credit Card Balance Card -->
            <div class="bank-card card-credit-card">
                <div class="card-header">
                    <div class="card-title">
                        <span class="dot"></span>
                        CREDIT CARD BALANCE
                    </div>
                    <div class="card-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
                <div class="card-value negative"><?php echo formatCurrency($credit_card_balance); ?></div>
                <div class="card-subtitle">Outstanding Balance</div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scroll and additional interactivity
        $(document).ready(function() {
            // Add click effect to cards
            $('.bank-card').on('click', function() {
                $(this).css('transform', 'scale(0.98)');
                setTimeout(() => {
                    $(this).css('transform', '');
                }, 200);
            });

            // Animate numbers on load (optional)
            $('.card-value, .sub-card-value').each(function() {
                const $this = $(this);
                const text = $this.text();
                const number = parseFloat(text.replace(/[₹,]/g, ''));
                
                if (!isNaN(number)) {
                    $this.prop('Counter', 0).animate({
                        Counter: number
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function(now) {
                            $this.text('₹' + Math.ceil(now).toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }));
                        },
                        complete: function() {
                            $this.text(text); // Restore original formatted text
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

