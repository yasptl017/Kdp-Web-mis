<!DOCTYPE html>
<html lang="en">
<?php 
include 'dptname.php';
$page_title = "Notice Board - " . $DEPARTMENT_NAME . " - K.D. Polytechnic"; 
?>
<?php include '../assets/preload/head.php'; ?>
<body>
    <?php include_once "../Admin/dbconfig.php"; ?>
    
    <?php include '../assets/preload/topbar.php'; ?>
    <?php include '../assets/preload/header.php'; ?>
    <?php include '../assets/preload/navigation.php'; ?>
    <?php include '../assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Notice Board</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Departments</a></li>
                            <li class="breadcrumb-item"><a href="aboutdpt.php"><?php echo $DEPARTMENT_NAME; ?></a></li>
                            <li class="breadcrumb-item active">Notice Board</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php include 'dptnavigation.php'; ?>

    <?php
    $nb_query = "SELECT * FROM nb WHERE department = '$DEPARTMENT_NAME' ORDER BY created_at DESC";
    $nb_result = $conn->query($nb_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="table-card">
                        <div class="table-header">
                            <h4><i class="fas fa-bullhorn me-2"></i><?php echo $DEPARTMENT_NAME; ?> - Notices</h4>
                        </div>
                        
                        <!-- Date Filter -->
                        <div class="date-filter-section">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt me-2"></i>From Date
                                    </label>
                                    <input type="date" class="form-control" id="dateFrom">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar-check me-2"></i>To Date
                                    </label>
                                    <input type="date" class="form-control" id="dateTo">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-secondary w-100" id="resetDates">
                                        <i class="fas fa-redo me-2"></i>Reset Dates
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-body">
                            <div class="table-responsive">
                                <table id="noticeTable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sr. No.</th>
                                            <th>Date</th>
                                            <th>Title</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($nb_result && $nb_result->num_rows > 0):
                                            $sr_no = 1;
                                            while ($notice = $nb_result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $sr_no++; ?></td>
                                                <td data-order="<?php echo strtotime($notice['created_at']); ?>">
                                                    <?php echo date("d-m-Y", strtotime($notice['created_at'])); ?>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($notice['title']); ?></strong></td>
                                                <td>
                                                    <a href="notice-details.php?id=<?php echo $notice['id']; ?>" 
                                                       class="btn btn-sm btn-accent">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No notices available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../assets/preload/footer.php'; ?>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <style>
    .date-filter-section {
        padding: 20px;
        background: #f8fafc;
        border-bottom: 2px solid #e5e7eb;
    }

    .date-filter-section .form-label {
        color: #1e3a8a;
        margin-bottom: 8px;
    }

    .date-filter-section .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
    }

    .date-filter-section .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    #noticeTable_wrapper .dataTables_filter {
        margin-bottom: 15px;
    }

    #noticeTable_wrapper .dataTables_filter input {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 12px;
        margin-left: 8px;
    }

    #noticeTable_wrapper .dataTables_length select {
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 6px 10px;
        margin: 0 8px;
    }

    #noticeTable thead th {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: white;
        font-weight: 600;
        padding: 15px;
        border: none;
    }

    #noticeTable tbody tr {
        transition: all 0.3s ease;
    }

    #noticeTable tbody tr:hover {
        background-color: #f0f9ff !important;
        transform: translateX(5px);
    }

    .dataTables_info {
        color: #6b7280;
        font-weight: 500;
    }

    .pagination .page-link {
        color: #1e3a8a;
        border: 2px solid #e5e7eb;
        margin: 0 2px;
        border-radius: 6px;
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        border-color: #1e3a8a;
    }
    </style>

    <script>
    $(document).ready(function() {
        // Custom date range filtering function
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                var dateFrom = $('#dateFrom').val();
                var dateTo = $('#dateTo').val();
                var dateCol = data[1]; // Date column (index 1)
                
                // Convert date from dd-mm-yyyy to yyyy-mm-dd for comparison
                var dateParts = dateCol.split('-');
                var rowDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
                
                if (dateFrom === '' && dateTo === '') {
                    return true;
                }
                
                if (dateFrom !== '' && dateTo === '') {
                    return rowDate >= dateFrom;
                }
                
                if (dateFrom === '' && dateTo !== '') {
                    return rowDate <= dateTo;
                }
                
                return rowDate >= dateFrom && rowDate <= dateTo;
            }
        );

        // Initialize DataTable
        var table = $('#noticeTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "language": {
                "search": "Search Notices:",
                "lengthMenu": "Show _MENU_ notices",
                "info": "Showing _START_ to _END_ of _TOTAL_ notices",
                "infoEmpty": "No notices available",
                "infoFiltered": "(filtered from _MAX_ total notices)",
                "zeroRecords": "No matching notices found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            },
            "order": [[1, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": [3] }
            ],
            "responsive": true
        });

        // Event listeners for date inputs
        $('#dateFrom, #dateTo').on('change', function() {
            table.draw();
        });

        // Reset dates button
        $('#resetDates').on('click', function() {
            $('#dateFrom').val('');
            $('#dateTo').val('');
            table.draw();
        });

        // Redraw serial numbers after filtering
        table.on('order.dt search.dt', function() {
            table.column(0, {search:'applied', order:'applied'}).nodes().each(function(cell, i) {
                cell.innerHTML = i + 1;
            });
        }).draw();
    });
    </script>
</body>
</html>