    <header class="header" id="header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="search-box">
            <input type="text" class="form-control" placeholder="Search...">
        </div>
        
        <div class="header-actions">

            <!-- User Profile -->
            <div class="dropdown">
                <div class="user-profile" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=4e73df&color=fff" alt="User" class="user-avatar">
                    <span class="d-none d-md-inline">
                        <?php echo $_SESSION['user_name'] ?? 'User'; ?>
                    </span>
                    <i class="fas fa-chevron-down ms-1"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                   
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>