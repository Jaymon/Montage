<h2>Last Exception Thrown</h2>

<?php if($e = $this->getField('e')): ?>
  <pre><?php echo $e; ?></pre>
<?php endif; ?>

<?php echo '<hr>'; ?>

<h2>All Exceptions Thrown</h2>

<?php if($e_list = $this->getField('e_list')): ?>
  <?php foreach($e_list as $e): ?>
    <pre><?php echo $e; ?></pre>
    <?php echo '<hr>'; ?>
  <?php endforeach; ?>
<?php endif; ?>

