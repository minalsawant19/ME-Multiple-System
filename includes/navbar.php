<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon ">
                    <p style="font-size:30px;">☀</p>
                </div>
                <div class="sidebar-brand-text mx-3">ME Multiple System <sup></sup></div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Interface
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Lead Mangament</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Inquiry:</h6>
                        <a class="collapse-item" href="add-inquiry.php">Add Inquiry</a>
                        <a class="collapse-item" href="view-inquiry.php">View Inquiry</a>
                        <a class="collapse-item" href="view-refollowup.php">View Re-followup</a>
                        <a class="collapse-item" href="view-visit.php">View Visit</a>
                        <a class="collapse-item" href="view-future.php">View future client</a>
                        <a class="collapse-item" href="view-schedule.php">View Schedule client</a>
                        <a class="collapse-item" href="view-quotations.php">View Quotations</a>
                    </div>
                </div>
            </li>

            <!-- Nav Item - Vendor Management -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseVendor"
                    aria-expanded="true" aria-controls="collapseVendor">
                    <i class="fas fa-fw fa-truck"></i>
                    <span>Vendor Management</span>
                </a>

                <div id="collapseVendor" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Vendor Options:</h6>
                        <a class="collapse-item" href="add-vendor.php">Add Vendor</a>
                        <a class="collapse-item" href="view-vendor.php">View Vendor</a>
                    </div>
                </div>
            </li>
            
                    
<!-- Nav Item - Trader Management -->
<li class="nav-item">
    <a class="nav-link collapsed" data-toggle="collapse"
    data-target="#collapseTrader"
    aria-expanded="false"
    aria-controls="collapseTrader">

    <i class="fas fa-fw fa-handshake"></i>
    <span>Trader Management</span>
</a>

<div id="collapseTrader" class="collapse"
data-parent="#accordionSidebar">

<div class="bg-white py-2 collapse-inner rounded">

    <h6 class="collapse-header">Trader Options:</h6>

    <a class="collapse-item" href="add-trader.php">
        Add Trader
    </a>

    <a class="collapse-item" href="view-trader.php">
        View Trader
    </a>

</div>
</div>
</li>



            <!-- Nav Item - Utilities Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseUtilities"
                    aria-expanded="true" aria-controls="collapseUtilities">
                    <i class="fas fa-fw fa-wrench"></i>
                    <span>Client Mangament</span>
                </a>
                <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities"
                    data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Custom Clients:</h6>
                        <a class="collapse-item" href="view-client.php">View Client</a>
                        <a class="collapse-item" href="view-gst-client.php">View GST Client</a>
                        <a class="collapse-item" href="all-pending-work.php">View Pending Work</a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseStaff"
                    aria-expanded="true" aria-controls="collapseStaff">
                    <i class="fas fa-fw fa-user-cog"></i>
                    <span>Employee Management</span>
                </a>

                <div id="collapseStaff" class="collapse" data-parent="#accordionSidebar" aria-labelledby="headingUtilities">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Employee Options:</h6>
                        <a class="collapse-item" href="add-employee.php">Add Employee</a>
                        <a class="collapse-item" href="view-all-employee.php">View Employee</a>
                        <a class="collapse-item" href="add-salary.php">Add Salary</a>
                    </div>
                </div>
            </li>


            <!-- Nav Item - Attendance Management -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseAttendance"
                    aria-expanded="true" aria-controls="collapseAttendance">
                    <i class="fas fa-fw fa-calendar-check"></i>
                    <span>Attendance Management</span>
                </a>

                <div id="collapseAttendance" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Attendance Options:</h6>
                        <a class="collapse-item" href="add-attendance.php">Add Attendance</a>
                        <a class="collapse-item" href="view-attendance.php">View Attendance</a>
                    </div>
                </div>
            </li>


            <!-- Nav Item - Accounts Section -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseAccounts" aria-expanded="false" aria-controls="collapseAccounts">
                    <i class="fas fa-fw fa-users-cog"></i> <!-- Icon for Accounts -->
                    <span>Accounts</span>
                </a>

                <div id="collapseAccounts" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Accounts Management:</h6>

                        <!-- Submenu - Payment Management with Arrow -->
                        <a class="collapse-item" data-toggle="collapse" data-target="#collapsePayment" aria-expanded="false" aria-controls="collapsePayment">
                            Payment
                        </a>
                        <div id="collapsePayment" class="collapse" data-parent="#collapseAccounts">
                            <div class="bg-light py-2 collapse-inner rounded">
                                <a class="collapse-item" href="due-payment.php">Due Payments</a>
                                <a class="collapse-item" href="view-payment.php">View Payments</a>
                            </div>
                        </div>

                        <!-- Submenu - Payment Management with Arrow -->
                        <a class="collapse-item" data-toggle="collapse" data-target="#collapseAdvance" aria-expanded="false" aria-controls="collapseAdvance">
                            Advance
                        </a>
                        <div id="collapseAdvance" class="collapse" data-parent="#collapseAccounts">
                            <div class="bg-light py-2 collapse-inner rounded">
                                <a class="collapse-item" href="add-advance.php">Add Advance</a>
                                <a class="collapse-item" href="view-advance.php">View Advance</a>
                            </div>
                        </div>

                        <!-- Submenu - Expenses -->
                        <a class="collapse-item" data-toggle="collapse" data-target="#collapseExpenses" aria-expanded="false" aria-controls="collapseExpenses">
                            Expenses
                        </a>
                        <div id="collapseExpenses" class="collapse" data-parent="#collapseAccounts">
                            <div class="bg-light py-2 collapse-inner rounded">
                                <a class="collapse-item" href="add-expenses.php">Add Expense</a>
                                <a class="collapse-item" href="view-expenses.php">View Expenses</a>
                            </div>
                        </div>
                              <a class="collapse-item" href="view-invoice.php">View GST Invoice</a>
                    </div>
                </div>
            </li>
            
            <!-- Nav Item - Trash -->
<li class="nav-item">
    <a class="nav-link" href="view-delete.php">
        <i class="fas fa-trash text-danger"></i>
        <span>Trash</span>
    </a>
</li>
            
            
            <li class="nav-item">
    <a class="nav-link" href="admin.php">
        <i class="fas fa-fw fa-image"></i>
        <span>Add Gallery</span>
    </a>
</li>

            <!-- Divider -->
            <hr class="sidebar-divider">

            <!-- Heading -->
            <div class="sidebar-heading">
                Addons
            </div>

            <!-- Nav Item - Pages Collapse Menu -->
            <li class="nav-item">
                <a class="nav-link collapsed" data-toggle="collapse" data-target="#collapseUsers"
                    aria-expanded="true" aria-controls="collapseUsers">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Users</span>
                </a>

                <div id="collapseUsers" class="collapse" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">User Options:</h6>
                        <a class="collapse-item" href="register.php">View User</a>
                    </div>
                </div>
            </li>




            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

    </div>


    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>



                <!-- Topbar Navbar -->
                <ul class="navbar-nav ml-auto">

                    <div class="topbar-divider d-none d-sm-block"></div>

                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle text-dark" href="#" id="userDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php
                            if (isset($_SESSION['auth'])) {
                                echo $_SESSION['auth_user']['user_name'];
                            } else {
                                echo "Not Logged in";
                            }
                            ?>

                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                            aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="code.php" method="POST">
                                <button type="submit" name="logout_btn" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
                                </button>
                            </form>

                        </div>
                    </li>

                </ul>

            </nav>
            <!-- End of Topbar -->


            <!-- Scroll to Top Button-->
            <a class="scroll-to-top rounded" href="#page-top">
                <i class="fas fa-angle-up"></i>
            </a>