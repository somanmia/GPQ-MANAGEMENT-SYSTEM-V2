<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('config/db_connect.php');

// ১. ডাইনামিক বায়ার আইডি (সেশন থেকে অথবা ডিফল্ট ২০২৬)
$logged_in_buyer_id =2026;

// ২. ফিল্টার প্যারামিটার রিসিভ (GET Method)
$filter_sample_type = isset($_GET['sample_type']) ? trim($_GET['sample_type']) : '';
$filter_season      = isset($_GET['season']) ? trim($_GET['season']) : '';
$filter_style       = isset($_GET['style']) ? trim($_GET['style']) : '';

// ৩. ডাইনামিক SQL কোয়েরি তৈরি (আপনার নতুন কলাম নেম অনুযায়ী)
$sql = "SELECT Entry_date, Style_name, Season, Sample_type, Qty, Color, Hints 
        FROM samples 
        WHERE Buyer_id = ?";

$params = [$logged_in_buyer_id];
$types  = "i";

// স্যাম্পল টাইপ ফিল্টার
if (!empty($filter_sample_type)) {
    $sql .= " AND Sample_type = ?";
    $params[] = $filter_sample_type;
    $types .= "s";
}

// সিজন ফিল্টার
if (!empty($filter_season)) {
    $sql .= " AND Season = ?";
    $params[] = $filter_season;
    $types .= "s";
}

// স্টাইল নেম ফিল্টার (LIKE Search)
if (!empty($filter_style)) {
    $sql .= " AND Style_name LIKE ?";
    $params[] = "%" . $filter_style . "%";
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

// ৪. ড্রপডাউনের জন্য ইউনিক Sample Type এবং Season ডাটাবেজ থেকে আনায়ন
$sample_types_res = mysqli_query($conn, "SELECT DISTINCT Sample_type FROM samples WHERE Buyer_id = $logged_in_buyer_id AND Sample_type IS NOT NULL AND Sample_type != ''");
$seasons_res      = mysqli_query($conn, "SELECT DISTINCT Season FROM samples WHERE Buyer_id = $logged_in_buyer_id AND Season IS NOT NULL AND Season != ''");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Management & Search</title>
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="dist/css/admin.css">

    <style>
        body { background-color: #f4f6f9; }
        .filter-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .table-card {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .badge-sample {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
        }
        .badge-season {
            background-color: #fef3c7;
            color: #b45309;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="container-fluid py-4">
        
        <!-- ১. প্রফেশনাল সার্চ/ফিল্টার বার (Filter Section) -->
        <div class="filter-card">
            <h6 class="mb-3 text-primary fw-bold">
                <i class="fas fa-filter me-2"></i>Search Your Sample:
            </h6>
            <form method="GET" action="" class="row g-3 align-items-end">
                
                <!-- পেজ ট্র্যাকিং যদি থাকে -->
                <?php if(isset($_GET['page'])): ?>
                    <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']); ?>">
                <?php endif; ?>

                <!-- ফিল্টার ১: স্যাম্পল টাইপ (Dropdown) -->
                <div class="col-md-3">
                    <label for="sample_type" class="form-label font-weight-bold text-secondary small">Sample Type</label>
                    <select name="sample_type" id="sample_type" class="form-select">
                        <option value="">Select</option>
                        <?php 
                        if($sample_types_res && mysqli_num_rows($sample_types_res) > 0) {
                            while($st = mysqli_fetch_assoc($sample_types_res)) {
                                $selected = ($filter_sample_type == $st['Sample_type']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($st['Sample_type'])."' $selected>".htmlspecialchars($st['Sample_type'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- ফিল্টার ২: সিজন (Dropdown) -->
                <div class="col-md-3">
                    <label for="season" class="form-label font-weight-bold text-secondary small">Season</label>
                    <select name="season" id="season" class="form-select">
                        <option value="">Select</option>
                        <?php 
                        if($seasons_res && mysqli_num_rows($seasons_res) > 0) {
                            while($se = mysqli_fetch_assoc($seasons_res)) {
                                $selected = ($filter_season == $se['Season']) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($se['Season'])."' $selected>".htmlspecialchars($se['Season'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- ফিল্টার ৩: স্টাইল (Search Box) -->
                <div class="col-md-4">
                    <label for="style" class="form-label font-weight-bold text-secondary small">Style Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="style" id="style" class="form-control" placeholder="Search by style name..." value="<?php echo htmlspecialchars($filter_style); ?>">
                    </div>
                </div>

                <!-- ফিল্টার একশন বাটন -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Filter</button>
                    <a href="index.php?page=sample" class="btn btn-outline-secondary" title="Reset Filters"><i class="fas fa-undo"></i></a>
                </div>

            </form>
        </div>

        <!-- ২. ডাটা টেবিল সেকশন -->
        <div class="table-card">
            
            <div class="table-responsive">
                <table class="table table-hover align-middle custom-table" id="myTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Style Name</th>
                            <th>Season</th>
                            <th>Sample Type</th>
                            <th>Quantity</th>
                            <th>Color</th>
                            <th>Hints</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Prepared Statement দ্বারা নিরাপদ ডাটা ফেচ
                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt) {
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td class="text-secondary small"><?php echo date('d M, Y', strtotime($row['Entry_date'])); ?></td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['Style_name']); ?></td>
                                        <td>
                                            <?php if(!empty($row['Season'])): ?>
                                                <span class="badge-season"><i class="fa-regular fa-calendar-alt me-1"></i><?php echo htmlspecialchars($row['Season']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-sample"><?php echo htmlspecialchars($row['Sample_type']); ?></span></td>
                                        <td class="fw-bold"><?php echo (int)$row['Qty']; ?></td>
                                        <td><?php echo htmlspecialchars($row['Color']); ?></td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($row['Hints'] ?? ''); ?></td>
                                    </tr>
                                    <?php
                                }
                            }
                            $stmt->close();
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            "responsive": true,
            "pageLength": 10,
            "order": [], // ডিফল্ট অর্ডার ডাটাবেজের ORDER BY id DESC অনুসরণ করবে
            "language": {
                "search": "Search:",
                "lengthMenu": "দেখান _MENU_ টি",
                "info": "Showing _TOTAL_ to _START_ of _END_ entries",
                "paginate": { "next": "Next", "previous": "Previous" },
                "emptyTable": "Data not Found!"
            }
        });
    });
    </script>
</body>
</html>