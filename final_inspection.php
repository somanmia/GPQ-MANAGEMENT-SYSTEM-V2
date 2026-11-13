<?php


require_once('config/db_connect.php');

$logged_in_buyer_id =2026;

?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Management</title>
    
    <!-- CSS Links -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
     <link rel="stylesheet" href="dist/css/admin.css">

<body>

    <!-- ডাটা টেবিল -->
    <div class="table-card shadow-sm">
        <div class="table-responsive">
            <table class="table custom-table" id="myTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Style</th>
                        <th>PO Number</th>
                        <th>O. Qty</th>
                        <th>S. Qty</th>
                        <th>Color</th>
                        <th>% Qty</th>
                        <th>Result</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php
$select_query = "SELECT * FROM inspection WHERE buyer_id = '$logged_in_buyer_id' ORDER BY id DESC";
$records = mysqli_query($conn, $select_query);
if ($records) {
    while ($row = mysqli_fetch_assoc($records)) {
        $order_qty = (float)$row['orderqty'];
        $ship_qty = (float)$row['shipqty'];

        // শতকরা হিসাব বের করার সূত্র: ((Ship Qty - Order Qty) / Order Qty) * 100
        if ($order_qty > 0) {
            $percentage = (($ship_qty - $order_qty) / $order_qty) * 100;
            $sign = ($percentage > 0) ? '+' : '';
            $percentage_text = $sign . number_format($percentage, 2) . '%';
            
            // বেশি হলে সবুজ এবং কম হলে লাল রঙ দেখানোর জন্য ক্লাস নির্ধারণ
            $badge_class = ($percentage > 0) ? 'text-success fw-bold' : (($percentage < 0) ? 'text-danger fw-bold' : 'text-muted');
        } else {
            $percentage_text = '0%';
            $badge_class = 'text-muted';
        }
        ?>
        <tr>
            <td class="text-muted"><?php echo date('d M, Y', strtotime($row['entry_date'])); ?></td>
            <td class="fw-bold"><?php echo htmlspecialchars($row['style_name']); ?></td>
            <td><span class="badge-info"><?php echo htmlspecialchars($row['ponumber']); ?></span></td>
            <td><?php echo number_format($order_qty); ?></td>
            <td><?php echo number_format($ship_qty); ?></td>
            <td><?php echo htmlspecialchars($row['color']); ?></td>
            <td class="small <?php echo $badge_class; ?>"><?php echo $percentage_text; ?></td>
        </tr>
        <?php 
    }
} 
?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('#myTable').DataTable({
            "order": [[0, "desc"]], // তারিখ অনুযায়ী অটো সর্টিং
            "language": {
                "search": "সার্চ করুন:",
                "lengthMenu": "দেখান _MENU_ টি ডাটা",
                "info": "মোট _TOTAL_ টি ডাটার মধ্যে _START_ থেকে _END_ পর্যন্ত দেখাচ্ছেন",
                "paginate": {
                    "next": "পরবর্তী",
                    "previous": "আগের"
                }
            }
        });
    });

    function toggleCandidateForm() {
        var x = document.getElementById("candidateForm");
        x.style.display = (x.style.display === "none" || x.style.display === "") ? "block" : "none";
    }
    </script>
</body>
</html>