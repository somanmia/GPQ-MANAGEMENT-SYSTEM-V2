<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ১. ডাটাবেস কানেকশন
require_once('config/db_connect.php');
$conn = mysqli_connect($servername, $username, $password, $dbname);
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
}

// সেশন থেকে বায়ার আইডি নেওয়া (ডিফল্ট ২০২৬ যদি সেশনে না থাকে)
$logged_in_buyer_id = $_SESSION['buyer_id'] ?? 2026; 

// --- কাউন্ট ভেরিয়েবল ইনিশিয়ালাইজেশন ---
$total_pp_meetings   = 0;
$total_pp_samples    = 0;
$total_gs_samples    = 0;
$total_fit_samples   = 0;
$total_dev_samples   = 0;
$total_inspections   = 0;
$pending_inspections = 0;

// হেলপার ফাংশন: ইউনিক ডাটা কাউন্ট করার জন্য
function get_unique_count($conn, $query, $buyer_id) {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $buyer_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $res['total'] ?? 0;
        }
    } catch (Exception $e) {
        return 0;
    }
    return 0;
}

if ($conn) {
    // ১. পিপি মিটিং কাউন্ট
    $total_pp_meetings = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM pp_meetings WHERE buyer_id = ?", $logged_in_buyer_id);
    if($total_pp_meetings == 0) {
        $total_pp_meetings = get_unique_count($conn, "SELECT COUNT(DISTINCT style_no) as total FROM pp_meetings WHERE buyer_id = ?", $logged_in_buyer_id);
    }

    // ২. স্যাম্পল কাউন্ট (PP, GS, Fit, Dev)
    $total_pp_samples  = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM samples WHERE buyer_id = ? AND sample_type = 'PP Sample'", $logged_in_buyer_id);
    $total_gs_samples  = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM samples WHERE buyer_id = ? AND sample_type = 'GS Sample'", $logged_in_buyer_id);
    $total_fit_samples = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM samples WHERE buyer_id = ? AND (sample_type = 'Fit-1' OR sample_type = 'Fit-2')", $logged_in_buyer_id);
    $total_dev_samples = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM samples WHERE buyer_id = ? AND sample_type = 'Development'", $logged_in_buyer_id);

    // ৩. ইন্সপেকশন কাউন্ট
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'final_inspections'");
    if($check_table && mysqli_num_rows($check_table) > 0) {
        $total_inspections = get_unique_count($conn, "SELECT COUNT(DISTINCT style_name) as total FROM final_inspections WHERE buyer_id = ?", $logged_in_buyer_id);
        if($total_inspections == 0) {
            $total_inspections = get_unique_count($conn, "SELECT COUNT(DISTINCT style_no) as total FROM final_inspections WHERE buyer_id = ?", $logged_in_buyer_id);
        }
    }

    // স্মার্ট পেন্ডিং ক্যালকুলেশন
    $pending_inspections = max(0, $total_pp_meetings - $total_inspections);
}

// --- বার চার্ট পারসেন্টেজ ক্যালকুলেশন ---
$max_value   = max($total_pp_meetings, $total_pp_samples, $total_gs_samples, $total_fit_samples, $total_dev_samples, $pending_inspections, 1);
$pp_m_per    = ($total_pp_meetings / $max_value) * 100;
$pp_s_per    = ($total_pp_samples / $max_value) * 100;
$gs_per      = ($total_gs_samples / $max_value) * 100;
$fit_per     = ($total_fit_samples / $max_value) * 100;
$dev_per     = ($total_dev_samples / $max_value) * 100;
$pending_per = ($pending_inspections / $max_value) * 100;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gpq Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dist/css/admin_home.css">
    
    <style>
    /* Content Header Styling */
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    .content-header h1 {
        font-size: 22px;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    .breadcrumb {
        font-size: 13px;
        color: #6c757d;
        background: transparent;
        margin: 0;
    }

    /* Modern Metric Cards Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0,0,0,0.03);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }

    .stat-card.card-green::before { background: #10b981; }
    .stat-card.card-blue::before { background: #3b82f6; }
    .stat-card.card-teal::before { background: #14b8a6; }
    .stat-card.card-orange::before { background: #f59e0b; }

    .stat-card .card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .stat-card .info-text h3 {
        font-size: 28px;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 4px 0;
        line-height: 1;
    }

    .stat-card .info-text p {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card .icon-holder {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .card-green .icon-holder { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .card-blue .icon-holder { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .card-teal .icon-holder { background: rgba(20, 184, 166, 0.1); color: #14b8a6; }
    .card-orange .icon-holder { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

    .stat-card .card-footer-link {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .stat-card:hover .card-footer-link { color: #0f172a; }

    /* Chart Box */
    .chart-box {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0,0,0,0.03);
        overflow: hidden;
    }

    .chart-title {
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chart-title span {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-print {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-print:hover {
        background-color: #f1f5f9;
        color: #0f172a;
    }

    .chart-container {
        display: flex;
        flex-direction: column;
        gap: 18px;
        padding: 24px;
    }

    .chart-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        align-items: center;
        gap: 15px;
    }

    .chart-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chart-bar-wrapper {
        background-color: #f1f5f9;
        border-radius: 20px;
        height: 22px;
        overflow: hidden;
    }

    .chart-bar {
        height: 100%;
        border-radius: 20px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        min-width: 28px;
    }

    /* Gradient Backgrounds */
    .bg-ppm { background: linear-gradient(90deg, #64748b, #475569); }
    .bg-pps { background: linear-gradient(90deg, #10b981, #059669); }
    .bg-gs  { background: linear-gradient(90deg, #14b8a6, #0d9488); }
    .bg-fit { background: linear-gradient(90deg, #3b82f6, #2563eb); }
    .bg-dev { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .bg-pending { background: linear-gradient(90deg, #ef4444, #dc2626); }

    @media (max-width: 576px) {
        .chart-row { grid-template-columns: 1fr; gap: 6px; }
    }
</style>
</head>
<body>

    <div class="wrapper">
        <nav class="main-header">
            <div class="nav-left" id="sidebarToggle"><i class="fas fa-bars"></i></div>
            <ul class="nav-right">
                <li class="user-profile-top">
                    <img src="dist/images/logo/profile.jpg" alt="Admin">
                    <span>GPQ MMS</span>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar">
            <div class="brand-link" id="brandName"></div>
            <div class="user-panel">
                    <img src="dist/images/logo/profile.jpg" alt="Admin">
                <div class="status-text">
                    <p>GPQ MMS</p>
                    <small><i class="fa fa-circle text-success"></i> Online</small>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li class="menu-header">REPORTS</li>
                <li class="<?php echo (!isset($_GET['page']) || $_GET['page'] == '' || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">
                    <a href="index.php"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
                </li>
                
                <li class="menu-header">MANAGE</li>
                <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'final_inspection') ? 'active' : ''; ?>">
                    <a href="index.php?page=final_inspection"><i class="fas fa-shirt"></i> <span>Shipment Status.</span></a>
                </li>
                <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'sample') ? 'active' : ''; ?>">
                    <a href="index.php?page=sample"><i class="fas fa-shirt"></i> <span>SAMPLE</span></a>
                </li>
                <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'reports') ? 'active' : ''; ?>">
                    <a href="index.php?page=reports"><i class="fas fa-file-alt"></i> <span>Reports File</span></a>
                </li>

                <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'sample_status') ? 'active' : ''; ?>">
                    <a href="index.php?page=sample_status"><i class="fas fa-tasks"></i> <span>Sample Sewing</span></a>
                </li>
                  <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'sample_card') ? 'active' : ''; ?>">
                    <a href="sample_card.php"><i class="fas fa-id-card"></i> <span>Sample Card</span></a>
                </li>
                   <li class="<?php echo (isset($_GET['page']) && $_GET['page'] == 'submission_form') ? 'active' : ''; ?>">
                    <a href="sample_submission.php"><i class="fas fa-clipboard-list"></i> <span>Submission Form</span></a>
                </li>
                
                <li class="menu-header">SETTINGS</li>
                <li><a href="login.php"><i class="fas fa-lock"></i> <span>Admin Login</span></a></li>
            </ul>
        </aside>

        <div class="content-wrapper">
            <?php 
                $page = $_GET['page'] ?? 'dashboard';
                
                if ($page == 'sample') {
                    include 'sample.php';
                } else if($page=='reports'){
                     include 'reports.php';
                }else if($page=='sample_status'){
                     include 'sample_status.php';
                }else if($page=='final_inspection'){
                    include 'final_inspection.php';
                }
                else {
            ?>
                <div class="content-header">
                    <h1>System Dashboard</h1>
                    <div class="breadcrumb">Home > Dashboard</div>
                </div>

                <!-- Modernised Cards matching internal CSS -->
                <div class="stats-grid">
                    <div class="stat-card card-green">
                        <div class="card-body">
                            <div class="info-text">
                                <h3><?php echo $total_pp_samples; ?></h3>
                                <p>Unique PP Samples</p>
                            </div>
                            <div class="icon-holder"><i class="fas fa-tshirt"></i></div>
                        </div>
                        <a href="index.php?page=sample" class="card-footer-link">More info <i class="fa fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card card-blue">
                        <div class="card-body">
                            <div class="info-text">
                                <h3><?php echo $total_fit_samples; ?></h3>
                                <p>Unique Fit Samples</p>
                            </div>
                            <div class="icon-holder"><i class="fas fa-ruler-combined"></i></div>
                        </div>
                        <a href="index.php?page=sample" class="card-footer-link">More info <i class="fa fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card card-teal">
                        <div class="card-body">
                            <div class="info-text">
                                <h3><?php echo $total_gs_samples; ?></h3>
                                <p>Unique GS Samples</p>
                            </div>
                            <div class="icon-holder"><i class="fas fa-tags"></i></div>
                        </div>
                        <a href="index.php?page=sample" class="card-footer-link">More info <i class="fa fa-arrow-right"></i></a>
                    </div>

                    <div class="stat-card card-orange">
                        <div class="card-body">
                            <div class="info-text">
                                <h3><?php echo $total_dev_samples; ?></h3>
                                <p>Unique Dev. Samples</p>
                            </div>
                            <div class="icon-holder"><i class="fas fa-code-branch"></i></div>
                        </div>
                        <a href="index.php?page=sample" class="card-footer-link">More info <i class="fa fa-arrow-right"></i></a>
                    </div>
                </div>

                <div class="chart-box">
                    <div class="chart-title">
                        <span><i class="fa-solid fa-chart-bar text-primary"></i>Garments Quality & Sample Record Tally (Unique by Style Name)</span>
                        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Print Analytics</button>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-row">
                            <div class="chart-label"><i class="fa-solid fa-handshake text-secondary"></i> PP Meetings</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-ppm" style="width: <?php echo $pp_m_per; ?>%;"><?php echo $total_pp_meetings; ?></div></div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-label"><i class="fas fa-tshirt text-success"></i> PP Samples</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-pps" style="width: <?php echo $pp_s_per; ?>%;"><?php echo $total_pp_samples; ?></div></div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-label"><i class="fas fa-tags text-info"></i> GS Samples</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-gs" style="width: <?php echo $gs_per; ?>%;"><?php echo $total_gs_samples; ?></div></div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-label"><i class="fas fa-ruler-combined text-primary"></i> Fit Samples</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-fit" style="width: <?php echo $fit_per; ?>%;"><?php echo $total_fit_samples; ?></div></div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-label"><i class="fas fa-code-branch text-warning"></i> Dev. Samples</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-dev" style="width: <?php echo $dev_per; ?>%;"><?php echo $total_dev_samples; ?></div></div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-label"><i class="fas fa-clock text-danger"></i> Pending Inspect.</div>
                            <div class="chart-bar-wrapper"><div class="chart-bar bg-pending" style="width: <?php echo $pending_per; ?>%;"><?php echo $pending_inspections; ?></div></div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>

        <footer style="margin-left: 230px; background: #fff; padding: 15px; color: #444; border-top: 1px solid #dee2e6; transition: margin-left 0.3s ease-in-out;" id="main-footer">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <strong>Copyright &copy; <?php echo date('Y'); ?> </strong> 
                    <a href="#" style="color: #3c8dbc; text-decoration: none; font-weight: 600;">GPQ Smart Record System</a>. 
                </div>
                <div style="font-size: 13px; color: #777;"><b>Version</b> 1.6.5</div>
            </div>
        </footer>
    </div>

    <script>
        const sidebarToggle = document.getElementById('sidebarToggle');
        const body = document.body;
        const brandName = document.getElementById('brandName');
        const footer = document.getElementById('main-footer');

        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 768) {
                body.classList.toggle('sidebar-open');
            } else {
                body.classList.toggle('sidebar-collapse');
                footer.style.marginLeft = body.classList.contains('sidebar-collapse') ? "50px" : "230px";
                brandName.innerText = body.classList.contains('sidebar-collapse') ? "GPQ" : "GPQ RECORD";
            }
        });
        if (window.innerWidth <= 768) { footer.style.marginLeft = "0"; }
    </script>
</body>
</html>