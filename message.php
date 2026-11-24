<?php if (isset($_SESSION['status'])) { ?>
  <div class="alert alert-success alert-dismissible fade show custom-alert" role="alert">
    <?= $_SESSION['status']; ?>
    <button type="button" class="close custom-close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php unset($_SESSION['status']); } ?>

<style>
  .custom-alert {
    position: relative;
    padding: 12px 45px 12px 16px;
    font-size: 15px;
    border-radius: 6px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
}

.custom-alert .custom-close {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    padding: 0;
    background: transparent;
    border: none;
    font-size: 22px;
    line-height: 1;
    opacity: 0.7;
}

.custom-alert .custom-close:hover {
    opacity: 1;
    color: #000;
}


</style>

<script>
setTimeout(function () {
    $('.custom-alert').alert('close');
}, 2000);
</script>