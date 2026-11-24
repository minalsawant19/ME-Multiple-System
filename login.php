<?php
session_start();

if (isset($_SESSION['auth'])) {
    $_SESSION['status'] = "You are already Logged in";
    header('Location: index.php');
    exit(0);
}

//  Include header AFTER session_start() and redirects
include('includes/header.php');
?>

<style>
    .input-group-text {
        background: #fff;
        border-left: 0;
    }

    .input-group .form-control {
        border-right: 0;
    }

    .input-group-text i {
        font-size: 16px;
    }
</style>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center" style="border-bottom:0 !important;">

            <div class="col-xl-6 col-lg-8 col-md-7 mt-5">

                <div class="card o-hidden border-0 shadow-lg mt-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-12">
                                <?php
                                if (isset($_SESSION['auth_status'])) {
                                ?>
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <strong>Hey!</strong> <?php echo $_SESSION['auth_status']; ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                <?php
                                    unset($_SESSION['auth_status']);
                                }
                                ?>


                                <?php
                                include('message.php');
                                ?>
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Login User</h1>
                                    </div>
                                    <form action="logincode.php" method="POST">
                                        <div class="form-group mb-3">
                                            <label>Email Id</label>
                                            <input type="email" name="email" class="form-control" placeholder="Enter Email Address...">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label>Password</label>
                                            <div class="position-relative">
                                                <input type="password"
                                                    name="password"
                                                    id="passwordField"
                                                    class="form-control pr-5"
                                                    placeholder="Enter Password">

                                                <!-- Eye Icon -->
                                                <span id="togglePassword"
                                                    style="position:absolute; right:-100px; top:50%; transform:translateY(-50%); cursor:pointer; color:#6c757d;">
                                                    <i class="fa fa-eye-slash"></i>
                                                </span>
                                            </div>
                                        </div>


                                        <div class="form-group mt-4">
                                            <button type="submit" name="login_btn" class="">
                                                Login
                                            </button>
                                        </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

        <?php
        include('includes/scripts.php');
        ?>

        <script>
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordField = document.getElementById('passwordField');
                const icon = this.querySelector('i');

                if (passwordField.type === "password") {
                    passwordField.type = "text";
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                } else {
                    passwordField.type = "password";
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            });
        </script>