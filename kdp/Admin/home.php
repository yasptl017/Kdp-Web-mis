<!DOCTYPE html>
<html lang="en">
<?php include "head.php"; ?>
<body>
<?php include "sidebar.php"; ?>
    <!-- Header -->
<?php include "header.php"; ?>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        
        <!-- Page Title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Dashboard Overview</h2>
            <button class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Add New
            </button>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Users</p>
                            <h3 class="mb-0">2,543</h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 12% increase</small>
                        </div>
                        <div class="icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Revenue</p>
                            <h3 class="mb-0">$45,678</h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 8% increase</small>
                        </div>
                        <div class="icon bg-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Orders</p>
                            <h3 class="mb-0">1,234</h3>
                            <small class="text-warning"><i class="fas fa-minus"></i> 2% decrease</small>
                        </div>
                        <div class="icon bg-warning">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Products</p>
                            <h3 class="mb-0">567</h3>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 5% increase</small>
                        </div>
                        <div class="icon bg-danger">
                            <i class="fas fa-box"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Forms Section -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="form-card">
                    <h5><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="Enter full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" placeholder="Enter email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" placeholder="Enter phone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User Role</label>
                            <select class="form-select">
                                <option selected>Select role</option>
                                <option value="1">Administrator</option>
                                <option value="2">Manager</option>
                                <option value="3">User</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="userStatus" checked>
                                <label class="form-check-label" for="userStatus">Active</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save User
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="form-card">
                    <h5><i class="fas fa-box me-2"></i>Add New Product</h5>
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" placeholder="Enter product name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select">
                                <option selected>Select category</option>
                                <option value="1">Electronics</option>
                                <option value="2">Clothing</option>
                                <option value="3">Books</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <input type="number" class="form-control" placeholder="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="3" placeholder="Enter product description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Product
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders Table -->
        <div class="table-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Orders</h5>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary">Export</button>
                    <button class="btn btn-sm btn-outline-primary">Filter</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#ORD-001</td>
                            <td>John Doe</td>
                            <td>Laptop Pro 15"</td>
                            <td>$1,299.00</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>2024-01-15</td>
                            <td>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>#ORD-002</td>
                            <td>Jane Smith</td>
                            <td>Wireless Mouse</td>
                            <td>$29.99</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>2024-01-14</td>
                            <td>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>#ORD-003</td>
                            <td>Mike Johnson</td>
                            <td>Gaming Keyboard</td>
                            <td>$89.99</td>
                            <td><span class="badge bg-info">Processing</span></td>
                            <td>2024-01-14</td>
                            <td>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>#ORD-004</td>
                            <td>Sarah Williams</td>
                            <td>USB-C Cable</td>
                            <td>$15.99</td>
                            <td><span class="badge bg-danger">Cancelled</span></td>
                            <td>2024-01-13</td>
                            <td>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>#ORD-005</td>
                            <td>David Brown</td>
                            <td>Monitor 27"</td>
                            <td>$349.99</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>2024-01-12</td>
                            <td>
                                <button class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-end mb-0">
                    <li class="page-item disabled">
                        <a class="page-link" href="#">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
        
    </main>
</body>
<?php include "footer.php"; ?>
</html>
