<?php
require_once('config/db_connect.php');

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$results = [];

if (isset($_GET['search'])) {
    $search_id = trim($_GET['style_id']);
    
    $search_id_esc = mysqli_real_escape_string($conn, $search_id);
    $sql = "SELECT * FROM sample_card WHERE style_id LIKE '%$search_id_esc%'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            for ($i = 0; $i < 4; $i++) {
                $results[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Sample Grid Print Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        /* Top Navigation Bar with Search (Modernized) */
        .top-nav-bar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .brand-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #f8fafc;
        }

        .search-input-group {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .search-input-group:focus-within {
            background: #fff;
            border-color: #38bdf8;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25);
        }

        .search-input-group input {
            background: transparent !important;
            border: none !important;
            color: #fff;
            font-size: 14px;
            padding: 6px 12px;
        }

        .search-input-group:focus-within input {
            color: #000 !important;
        }

        .search-input-group input::placeholder {
            color: #94a3b8;
        }

        .search-input-group:focus-within input::placeholder {
            color: #64748b;
        }

        .nav-btn {
            font-size: 13px;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-search-custom {
            background-color: #38bdf8;
            color: #0f172a;
            border: none;
            font-weight: 700;
        }

        .btn-search-custom:hover {
            background-color: #0ea5e9;
            color: #fff;
        }

        .btn-action-custom {
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-action-custom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.4);
        }

        /* A4 Page Layout - Exact 2x2 Grid */
        .a4-page {
            width: 210mm;
            height: 297mm;
            padding: 4mm;
            margin: 20mm auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            page-break-after: always;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-content: space-between;
            box-sizing: border-box;
        }

        .form-card {
            width: 49.2%;  
            height: 49.2%; 
            background: #fff;
            padding: 1mm;
            box-sizing: border-box;
            overflow: hidden;
            color: #000;
        }
        table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000 !important;
            padding: 1px 3px;
            font-size: 6.5pt;
            vertical-align: middle;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #000;
        }
        .wrap-text {
            white-space: normal !important;
            word-break: break-word;
            line-height: 1.2 !important; 
            vertical-align: top;
        }
        .section-header {
            background-color: #e2e8f0;
            font-weight: bold;
            text-align: center;
        }
        
        .col-1-label {
            width: 27%;
            font-weight: bold;
        }
        .col-2-val {
            width: 22%;
        }
        .col-3-label {
            width: 23%;
            font-weight: bold;
        }
        .col-4-val {
            width: 17%;
        }
        
        @media print {
            body {
                background: none;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                box-shadow: none;
                margin: 0;
                width: 210mm;
                height: 297mm;
                page-break-after: always;
                border-radius: 0;
            }
        }
        .if_available{
            height: 30px;
            vertical-align: top;
        }
    </style>
</head>
<body>

<!-- Modernized Top Navigation Bar -->
<div class="top-nav-bar no-print">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-3">
        
        <!-- Left: Brand Title -->
        <div class="d-flex align-items-center gap-2">
            <div class="bg-info bg-opacity-20 p-2 rounded-2 text-info d-flex align-items-center justify-content-center">
                <i class="fa-solid fa-layer-group fs-5"></i>
            </div>
            <span class="brand-title">Sample Grid Portal</span>
        </div>

        <!-- Middle: Search Bar -->
        <form method="GET" action="" class="d-flex align-items-center m-0">
            <div class="search-input-group d-flex align-items-center">
                <input type="text" class="form-control" id="style_id" name="style_id" placeholder="Enter Style ID..." required value="<?php echo isset($_GET['style_id']) ? htmlspecialchars($_GET['style_id']) : ''; ?>" style="width: 230px;">
                <button type="submit" name="search" class="nav-btn btn-search-custom rounded-0 rounded-end px-3">
                    <i class="fa-solid fa-filter"></i> <span class="d-none d-sm-inline ms-1">Generate</span>
                </button>
            </div>
        </form>

        <!-- Right: Action Buttons -->
        <div>
            <a href="index.php" class="nav-btn btn-action-custom">
                <i class="fa-solid fa-arrow-left"></i> <span>Dashboard</span>
            </a>
        </div>

    </div>
</div>

<!-- Data Section -->
<?php
if (!empty($results)) {
    $chunks = array_chunk($results, 4);
    foreach ($chunks as $page_items) {
        echo '<div class="a4-page">';
        foreach ($page_items as $row) {
            ?>
            <div class="form-card">
                <table>
                    <tr>
                        <td class="col-1-label">DATE:</td>
                        <td colspan="3"><?php echo htmlspecialchars($row['sample_date']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">STYLE NAME:</td>
                        <td colspan="3"><?php echo htmlspecialchars($row['style_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">STYLE DESCRIPTION:</td>
                        <td colspan="3"><?php echo htmlspecialchars($row['style_description']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">SUPPLIER NAME:</td>
                        <td colspan="3" ><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">ATT OF:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['att_of']); ?></td>
                        <td colspan="2" class="section-header">SAMPLE TYPE</td>
                    </tr>
                    <tr>
                        <td class="col-1-label">SEASON:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['season']); ?></td>
                        <td class="col-3-label">DEVELOPMENT:</td>
                        <td class="col-4-val text-center"><?php echo htmlspecialchars($row['development']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">COLOR:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['color']); ?></td>
                        <td class="col-3-label">1ST FIT:</td>
                        <td class="col-4-val text-center"><?php echo htmlspecialchars($row['first_fit']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">FIT NAME:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['fit_name']); ?></td>
                        <td class="col-3-label">2ND FIT:</td>
                        <td class="col-4-val text-center fw-bold"><?php echo htmlspecialchars($row['second_fit']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">SIZE:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['sample_size']); ?></td>
                        <td class="col-3-label">PP SAMPLE:</td>
                        <td class="col-4-val text-center fw-bold text-center"><?php echo htmlspecialchars($row['pp_sample']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">WEIGHT:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['weight']); ?></td>
                        <td class="col-3-label">GOLD SEAL:</td>
                        <td class="col-4-val text-center fw-bold"><?php echo htmlspecialchars($row['gold_seal']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">OTHERS:</td>
                        <td class="col-2-val"><?php echo htmlspecialchars($row['others_1']); ?></td>
                        <td class="col-3-label">OTHERS:</td>
                        <td class="col-4-val text-center"><?php echo htmlspecialchars($row['others_2']); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="wrap-text"><strong>Yarn/Fabric composition:</strong> <?php echo htmlspecialchars($row['fabric_composition']); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="wrap-text"><strong>Yarn/Fabric Construction:</strong> <?php echo htmlspecialchars($row['fabric_construction']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">WASH:</td>
                        <td colspan="3"><?php echo htmlspecialchars($row['wash']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label">FINISH:</td>
                        <td colspan="3"><?php echo htmlspecialchars($row['finish']); ?></td>
                    </tr>
                    
                    <tr>
                        <td class="col-1-label" colspan="2">MAIN FABRIC (Correct / Available):</td>
                        <td colspan="2" class="fw-bold"><?php echo htmlspecialchars($row['main_fabric']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label" colspan="2">TRIMS (Correct / Available):</td>
                        <td colspan="2" class="fw-bold"><?php echo htmlspecialchars($row['trims']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label" colspan="2">Branding/Labelling (Correct / Available):</td>
                        <td colspan="2" class="fw-bold"><?php echo htmlspecialchars($row['branding']); ?></td>
                    </tr>
                    <tr>
                        <td class="col-1-label" colspan="2">PACKAGING (Correct / Available):</td>
                        <td colspan="2" class="fw-bold"><?php echo htmlspecialchars($row['packaging']); ?></td>
                    </tr>
                    
                    <tr>
                        <td colspan="4" class="wrap-text if_available" ><strong>If anything available pls specify:</strong> <?php echo htmlspecialchars($row['specify_details']); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="wrap-text" style="height: 43px; vertical-align: top;">
                            <strong>COMMENTS:</strong> <?php echo htmlspecialchars($row['comments']); ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }
        echo '</div>';
    }
} elseif (isset($_GET['search'])) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center shadow-sm py-3">কোনো ডাটা পাওয়া যায়নি!</div></div>';
}
$conn->close();
?>

<!-- Print Button -->
<?php if (!empty($results)): ?>
<div class="text-center my-4 no-print">
    <button onclick="window.print()" class="btn btn-primary px-5 py-2 fw-bold shadow">
        <i class="fa-solid fa-print me-2"></i> PRINT
    </button>
</div>
<?php endif; ?>

</body>
</html>