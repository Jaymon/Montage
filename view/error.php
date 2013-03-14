<h1><?php echo $this->getContainer()->getResponse()->getTitle(); ?></h1>

<?php if($e = $this->getField('e')): ?>

  <pre><?php echo $e; ?></pre>

<?php endif; ?>

<?php if($e_list = $this->getField('e_list')): ?>

  <?php echo '<hr>'; ?>

  <h2>All Exceptions Thrown</h2>

  <?php foreach($e_list as $e): ?>
    <pre><?php echo $e; ?></pre>
    <?php echo '<hr>'; ?>
  <?php endforeach; ?>

<?php endif; ?>

