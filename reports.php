<?php
// ১. ডাটাবেজ কানেকশন ফাইল ইনক্লুড করা
require_once('config/db_connect.php');

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ক্যারেক্টার সেট এনকোডিং ঠিক করা (বাংলা ডাটার জন্য)
mysqli_set_charset($conn, "utf8mb4");

// নির্দিষ্ট বা ডিফল্ট buyer_id
$logged_in_buyer_id = 2026; 
$error_msg = "";

// সার্চ ও ফিল্টার ভেরিয়েবল রিসিভ করা
$search_style = trim($_GET['search_style'] ?? '');
$search_comment = trim($_GET['search_comment'] ?? '');

// ড্রপডাউনের জন্য ইউনিক ডেসক্রিপশন (Comments) লিস্ট ফেচ করা
$comment_list = [];
$comment_query = "SELECT DISTINCT Comments FROM final_inline_inspection WHERE buyer_id = ? AND Comments IS NOT NULL AND Comments != '' ORDER BY Comments ASC";
$stmt_comment = $conn->prepare($comment_query);
if ($stmt_comment) {
    $stmt_comment->bind_param("i", $logged_in_buyer_id);
    $stmt_comment->execute();
    $result_comment = $stmt_comment->get_result();
    while ($row = $result_comment->fetch_assoc()) {
        $comment_list[] = $row['Comments'];
    }
    $stmt_comment->close();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gpqmms system - Inspection Records</title>
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
    
    <!-- ইন্টারনাল সিএসএস (Internal CSS) -->
    <style>
        body {
            background-color: #f4f7f6 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            color: #333;
            overflow-x: hidden;
        }
        .main-container {
            width: 100% !important;
            padding: 10px !important;
        }
        @media (min-width: 992px) {
            .main-container {
                padding: 30px !important;
                max-width: 1400px;
                margin: 0 auto;
            }
        }
        .card-custom {
            background: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            padding: 15px;
            margin-bottom: 15px;
        }
        @media (min-width: 768px) {
            .card-custom {
                padding: 20px;
                margin-bottom: 20px;
            }
        }
        .form-label-custom {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: block;
            font-size: 13px;
        }
        @media (min-width: 768px) {
            .form-label-custom {
                font-size: 14px;
            }
        }
        .table-custom thead th {
            background-color: #212529 !important;
            color: white !important;
            padding: 10px 8px;
            font-weight: 600;
            border: none;
            font-size: 13px;
            white-space: nowrap;
        }
        .table-custom tbody td {
            padding: 10px 8px;
            vertical-align: middle;
            color: #333;
            font-size: 13px;
        }
        .btn-download {
            background: #e7f1ff;
            color: #0d6efd;
            border: 1px solid #b6d4fe;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        .btn-download:hover {
            background: #0d6efd;
            color: #fff;
        }
        /* বাটন ও ফিল্টার সেকশন ফিক্সড */
        .btn-action-container {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        .btn-action-container .btn {
            flex: 1;
            padding: 8px 10px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .dataTables_wrapper .dataTables_info, 
            .dataTables_wrapper .dataTables_paginate {
                font-size: 12px;
                text-align: center !important;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- মূল কন্টেইনার -->
    <div class="main-container">
        
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger" style="display: block !important;">
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- সার্চ ও ফিল্টার ফর্ম বক্স -->
        <div class="card-custom">
            <form method="GET" action="">
                <?php if (isset($_GET['page'])): ?>
                    <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']); ?>">
                <?php endif; ?>

                <div class="row align-items-end g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Search by Style Name</label>
                        <input type="text" name="search_style" value="<?php echo htmlspecialchars($search_style); ?>" placeholder="Enter style name..." class="form-control">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom">Filter by Description</label>
                        <select name="search_comment" class="form-select">
                            <option value="">-- All Descriptions --</option>
                            <?php foreach ($comment_list as $comm): ?>
                                <option value="<?php echo htmlspecialchars($comm); ?>" <?php echo ($search_comment == $comm) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comm); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label-custom d-none d-md-block">&nbsp;</label>
                        <div class="btn-action-container">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="<?php echo strtok($_SERVER['REQUEST_URI'], '?'); ?><?php echo isset($_GET['page']) ? '?page=' . htmlspecialchars($_GET['page']) : ''; ?>" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ডাটা টেবিল কার্ড বক্স -->
        <div class="card-custom">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0 table-custom" id="myTable" style="width: 100% !important;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Style Name</th>
                            <th>Description</th>
                            <th class="text-center">Download File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ডাইনামিক ফিল্টার কুয়েরি তৈরি
                        $sql = "SELECT id, style_name, Comments, drive_link, created_at FROM final_inline_inspection WHERE buyer_id = ?";
                        $params = [$logged_in_buyer_id];
                        $types = "i";

                        if (!empty($search_style)) {
                            $sql .= " AND style_name LIKE ?";
                            $params[] = "%" . $search_style . "%";
                            $types .= "s";
                        }

                        if (!empty($search_comment)) {
                            $sql .= " AND Comments = ?";
                            $params[] = $search_comment;
                            $types .= "s";
                        }

                        $sql .= " ORDER BY id DESC";

                        $select_stmt = $conn->prepare($sql);
                        if ($select_stmt) {
                            if (count($params) > 1) {
                                $select_stmt->bind_param($types, ...$params);
                            } else {
                                $select_stmt->bind_param("i", $logged_in_buyer_id);
                            }
                            $select_stmt->execute();
                            $records = $select_stmt->get_result();
                            
                            if ($records && $records->num_rows > 0) {
                                while ($row = $records->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td style="color: #6c757d; font-weight: 500; white-space: nowrap;">
                                            <?php echo (!empty($row['created_at'])) ? date('d M, Y', strtotime($row['created_at'])) : 'N/A'; ?>
                                        </td>
                                        <td style="font-weight: bold; color: #212529;"><?php echo htmlspecialchars($row['style_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Comments']); ?></td>
                                        <td class="text-center">
                                            <?php 
                                            if (!empty($row['drive_link'])): 
                                                $raw_link = trim($row['drive_link']);
                                                $download_link = $raw_link;

                                                if (strpos($raw_link, 'drive.google.com') !== false) {
                                                    if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $raw_link, $matches)) {
                                                        $file_id = $matches[1];
                                                        $download_link = "https://docs.google.com/uc?id=" . $file_id . "&export=download";
                                                    }
                                                }
                                            ?>
                                                <a href="<?php echo htmlspecialchars($download_link); ?>" target="_blank" download class="btn-download">
                                                    Download PDF
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #6c757d; font-size: 12px; white-space: nowrap;">No File</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            $select_stmt->close();
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
    <script src="https://cdn.jsdelivr.net/npm/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            "responsive": true,
            "pageLength": 10,
            "searching": false,    /* ডিফল্ট কুইক সার্চ বক্স রিমুভ করা হয়েছে */
            "lengthChange": false, /* ডিফল্ট শো এন্ট্রি ড্রপডাউন রিমুভ করা হয়েছে */
            "order": [], 
            "language": {
                "info": "Showing _TOTAL_ to _START_ of _END_ entries",
                "emptyTable": "Data not Found!",
                "paginate": { 
                    "next": "Next", 
                    "previous": "Previous"
                }
            }
        });
    });
    </script>
</body>
</html>