<!DOCTYPE html>
<html lang="en">
<?php $page_title = "Circulars - K.D. Polytechnic"; ?>
<?php include 'assets/preload/head.php'; ?>
<body>
    <?php include_once "Admin/dbconfig.php"; ?>
    
    <?php include 'assets/preload/topbar.php'; ?>
    <?php include 'assets/preload/header.php'; ?>
    <?php include 'assets/preload/navigation.php'; ?>
    <?php include 'assets/preload/mobilenav.php'; ?>

    <section class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="page-title">Circulars</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Circulars</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <?php
    $circulars_query = "SELECT * FROM circulars ORDER BY date DESC";
    $circulars_result = $conn->query($circulars_query);
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                            <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>All Circulars</h5>
                        </div>
                        
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="circularsTable" class="table table-hover align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="width: 80px;">Sr. No.</th>
                                            <th style="width: 130px;">Date</th>
                                            <th>Title</th>
                                            <th style="width: 100px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($circulars_result && $circulars_result->num_rows > 0):
                                            $sr_no = 1;
                                            while ($circular = $circulars_result->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo $sr_no++; ?></td>
                                                <td data-order="<?php echo strtotime($circular['date']); ?>"><?php echo date("d-m-Y", strtotime($circular['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($circular['title']); ?></td>
                                                <td>
                                                    <a href="circular-details.php?id=<?php echo $circular['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>View
                                                    </a>
                                                </td>
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
                </div>
            </div>
        </div>
    </section>

    <?php include 'assets/preload/footer.php'; ?>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <style>
    .dataTables_wrapper {
        padding: 0 !important;
    }

    .dataTables_wrapper .dataTables_length {
        margin-bottom: 20px;
    }

    .dataTables_wrapper .dataTables_length label {
        margin: 0;
        font-weight: 500;
        color: #6b7280;
    }

    .dataTables_wrapper .dataTables_length select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        margin: 0 8px;
    }

    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 20px;
    }

    .dataTables_wrapper .dataTables_filter label {
        margin: 0;
        font-weight: 500;
        color: #6b7280;
    }

    .dataTables_wrapper .dataTables_filter input {
        padding: 8px 15px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        width: 250px;
        margin-left: 8px;
    }

    #circularsTable thead th {
        background: #f9fafb !important;
        color: #1e3a8a !important;
        font-weight: 600 !important;
        border-bottom: 2px solid #e5e7eb !important;
        padding: 12px 15px !important;
        font-size: 14px !important;
    }

    #circularsTable tbody td {
        padding: 12px 15px !important;
        font-size: 14px !important;
        border-bottom: 1px solid #f3f4f6 !important;
    }

    #circularsTable tbody tr:hover {
        background-color: #f9fafb !important;
    }

    .dataTables_wrapper .dataTables_info {
        padding-top: 15px;
        color: #6b7280;
        font-size: 14px;
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: 15px;
    }

    .dataTables_wrapper .dataTables_empty {
        text-align: center;
        color: #9ca3af;
        padding: 30px !important;
        font-size: 15px;
    }

    /* Override student-pages.css pagination styles */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        border-radius: 0 !important;
        color: inherit !important;
        background: none !important;
        transition: none !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background: none !important;
        color: inherit !important;
        border-color: transparent !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: none !important;
        color: inherit !important;
        border-color: transparent !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        opacity: 1 !important;
        cursor: default !important;
        background: none !important;
        color: inherit !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        background: none !important;
        color: inherit !important;
    }

    /* Apply proper pagination styles */
    .dataTables_wrapper .dataTables_paginate .pagination {
        margin: 0;
        justify-content: flex-end;
    }

    .dataTables_wrapper .page-link {
        padding: 8px 14px !important;
        border: 1px solid #e5e7eb !important;
        color: #1e3a8a !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        margin: 0 3px !important;
        border-radius: 6px !important;
        transition: all 0.2s !important;
        background: #fff !important;
    }

    .dataTables_wrapper .page-link:hover {
        background-color: #eff6ff !important;
        border-color: #3b82f6 !important;
        color: #1e3a8a !important;
    }

    .dataTables_wrapper .page-item.active .page-link {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #fff !important;
    }

    .dataTables_wrapper .page-item.disabled .page-link {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        background-color: #fff !important;
        color: #9ca3af !important;
        border-color: #e5e7eb !important;
    }

    .dataTables_wrapper .page-item.disabled .page-link:hover {
        background-color: #fff !important;
        color: #9ca3af !important;
        border-color: #e5e7eb !important;
    }

    #circularsTable thead th.sorting,
    #circularsTable thead th.sorting_asc,
    #circularsTable thead th.sorting_desc {
        cursor: pointer;
        position: relative;
        padding-right: 30px !important;
    }

    #circularsTable thead th.sorting:after,
    #circularsTable thead th.sorting_asc:after,
    #circularsTable thead th.sorting_desc:after {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.5;
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        font-size: 12px;
    }

    #circularsTable thead th.sorting:after {
        content: '\f0dc';
    }

    #circularsTable thead th.sorting_asc:after {
        content: '\f0de';
        opacity: 1;
        color: #3b82f6;
    }

    #circularsTable thead th.sorting_desc:after {
        content: '\f0dd';
        opacity: 1;
        color: #3b82f6;
    }

    @media (max-width: 768px) {
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            text-align: center;
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            margin: 10px 0 0 0;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            text-align: center;
        }

        .dataTables_wrapper .dataTables_paginate .pagination {
            justify-content: center;
        }
    }
    </style>

    <script>
    $(document).ready(function() {
        var table = $('#circularsTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[1, "desc"]],
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": [0, 3] 
                },
                { 
                    "orderDataType": "dom-data-order",
                    "type": "num",
                    "targets": 1 
                }
            ],
            "language": {
                "lengthMenu": "Show _MENU_ entries",
                "search": "Search:",
                "info": "Showing _START_ to _END_ of _TOTAL_ circulars",
                "infoEmpty": "No circulars available",
                "emptyTable": "No circulars available",
                "zeroRecords": "No matching circulars found",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });

        // Custom sorting for data-order attribute
        $.fn.dataTable.ext.order['dom-data-order'] = function(settings, col) {
            return this.api().column(col, {order:'index'}).nodes().map(function(td, i) {
                return $(td).attr('data-order') * 1;
            });
        };

        // Update serial numbers on draw
        table.on('draw', function() {
            var pageInfo = table.page.info();
            table.column(0, {page: 'current'}).nodes().each(function(cell, i) {
                cell.innerHTML = pageInfo.start + i + 1;
            });
        });
    });
    </script>
</body>
</html>