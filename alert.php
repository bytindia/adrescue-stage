<style>
.alert-fixed {
  position: fixed;
  top: 70px;       /* distance from top */
  left: 50%;        /* center horizontally */
  transform: translateX(-50%); /* adjust for exact centering */
  min-width: 300px; /* optional fixed width */
  z-index: 1050;    /* keep on top */
}
</style>
<?php if(isset($_SESSION['suc'])) { ?>
	<div class="alert text-center alert-success alert-fixed">
	<?php echo $_SESSION['suc']; ?></div>
<?php unset($_SESSION['suc']); } ?>
<?php if(isset($_SESSION['err'])) { ?>
	<div class="alert text-center alert-danger alert-fixed">
	<?php echo $_SESSION['err']; ?></div>
<?php unset($_SESSION['err']); } ?>
