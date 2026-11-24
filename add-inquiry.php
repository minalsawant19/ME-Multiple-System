<?php
include('authentication.php'); // must include before content
include('includes/header.php');
include('includes/navbar.php');
include('message.php');
?>



<!-- Begin Page Content -->
          <div class="container-fluid">
            <div class="inquiry-container" >
              <h2>Add Inquiry</h2>

              <form action="inquiry-code.php" method="POST">
                  <input type="hidden" name="source" value="admin">
                <label>Name</label>
                <input type="text" name="name" placeholder="Enter Full Name" />

                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Enter Phone Number" />

                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter Email Address" />

                <label>Address</label>
                <input type="text" name="address" placeholder="Enter Address" />

                <label>Message</label>
                <textarea name="message" placeholder="Enter Message"></textarea>

                <button type="submit" name="save_inquiry" type="submit">Submit Inquiry</button>
              </form>
            </div>
          </div>
          <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->

<?php
    include('includes/scripts.php');
    include('includes/footer.php');
    ?>
    