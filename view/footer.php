<?php if($total = $this->getContainer()->getProfile()->getTotal()): ?>
  
  <div style="width:100%">
    
    <p style="float:right;font-size:85%">Page rendered in <?php echo $total; ?> ms.</p>
    
  </div>
  
<?php endif; ?>
