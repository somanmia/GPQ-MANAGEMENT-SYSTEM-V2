<?php
require_once('config/db_connect.php');

if (!isset($conn) || !$conn) {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}

// ড্রপডাউনের জন্য ইউনিক Buyer এবং Session ডাটা নিয়ে আসা
$buyers_result   = mysqli_query($conn, "SELECT DISTINCT buyer FROM sample_status WHERE buyer != ''");
$sessions_result = mysqli_query($conn, "SELECT DISTINCT session FROM sample_status WHERE session != ''");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Status Public View</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        body {
            font-family: 'Hind Siliguri', 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }

        /* Top Filter Card Box */
        .filter-card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 20px 25px;
            margin-bottom: 25px;
        }

        .filter-title {
            color: #0d6efd;
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label-custom {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .filter-card .form-select, 
        .filter-card .form-control {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 0.9rem;
        }

        .filter-card .form-select:focus, 
        .filter-card .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .btn-filter {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            padding: 9px 20px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s;
        }

        .btn-filter:hover {
            background-color: #0b5ed7;
            color: white;
        }

        .btn-reset {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 9px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-reset:hover {
            background-color: #f8f9fa;
            color: #333;
        }

        /* Main Data Card */
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 20px;
        }

        .custom-table {
            white-space: nowrap;
        }

        .custom-table thead th {
            background-color: #eef2f7;
            color: #495057;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 14px;
            border-bottom: 2px solid #dee2e6;
        }

        .custom-table tbody td {
            vertical-align: middle;
            padding: 14px;
            font-size: 0.9rem;
            border-bottom: 1px solid #edf2f7;
        }

        /* Badges Styling */
        .badge-style {
            background-color: #e7f1ff;
            color: #0d6efd;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-qty {
            background-color: #e6f4ea;
            color: #137333;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-user {
            background-color: #fef7e0;
            color: #b06000;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="container-fluid py-4 px-4">
        
        <!-- Filter Card Section -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="fa-solid fa-filter"></i> Search Your Sample:
            </div>
            
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label-custom">Buyer Name</label>
                    <select id="filter_buyer" class="form-select">
                        <option value="">Select</option>
                        <?php while($b = mysqli_fetch_assoc($buyers_result)): ?>
                            <option value="<?= htmlspecialchars($b['buyer']); ?>"><?= htmlspecialchars($b['buyer']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label-custom">Session</label>
                    <select id="filter_session" class="form-select">
                        <option value="">Select</option>
                        <?php while($s = mysqli_fetch_assoc($sessions_result)): ?>
                            <option value="<?= htmlspecialchars($s['session']); ?>"><?= htmlspecialchars($s['session']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label-custom">Style Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="filter_style" class="form-control border-start-0" placeholder="Search by style name...">
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="button" id="btn_filter" class="btn btn-filter w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Filter
                    </button>
                   
                </div>
            </div>
        </div>

        <!-- Table Data Card -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table custom-table align-middle" id="sampleTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Buyer</th>
                            <th>Style</th>
                            <th>Session</th>
                            <th>Color</th>
                            <th>Sewing Start</th>
                            <th>Sewing End</th>
                            <th>Qty</th>
                            <th>Sewing By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM sample_status ORDER BY id DESC";
                        $result = mysqli_query($conn, $query);

                        if ($result && mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                        ?>
                        <tr>
                            <td class="fw-semibold text-secondary"><?= htmlspecialchars($row['buyer']); ?></td>
                            <td><span class="badge-style"><?= htmlspecialchars($row['style']); ?></span></td>
                            <td><?= htmlspecialchars($row['session']); ?></td>
                            <td>
                                <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: <?= htmlspecialchars($row['color']); ?>;"></span>
                                <?= htmlspecialchars($row['color']); ?>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($row['sewing_start']); ?></small></td>
                            <td><small class="text-muted"><?= htmlspecialchars($row['sewing_end']); ?></small></td>
                            <td><span class="badge-qty"><?= htmlspecialchars($row['qty']); ?> Pcs</span></td>
                            <td><span class="badge-user"><?= htmlspecialchars($row['done_by']); ?></span></td>
                        </tr>
                        <?php 
                            endwhile;
                        endif; 
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
        var table = $('#sampleTable').DataTable({
            "pageLength": 10,
            "destroy": true,
            "order": [],
            "language": {
                "search": "Search:",
                "lengthMenu": "দেখান _MENU_ টি",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": { 
                    "next": "Next", 
                    "previous": "Previous" 
                },
                "emptyTable": "No sample data available",
                "zeroRecords": "No matching records found"
            }
        });

        // Filter Button Action (Matching exact table column indexes)
        $('#btn_filter').on('click', function() {
            var buyer   = $('#filter_buyer').val();
            var session = $('#filter_session').val();
            var style   = $('#filter_style').val();

            table.column(0).search(buyer)   // Column 0: Buyer
                 .column(2).search(session) // Column 2: Session
                 .column(1).search(style)   // Column 1: Style
                 .draw();
        });

        // Reset Button Action
        $('#btn_reset').on('click', function() {
            $('#filter_buyer').val('');
            $('#filter_session').val('');
            $('#filter_style').val('');

            table.columns().search('').draw();
        });
    });
    </script>
</body>
</html>