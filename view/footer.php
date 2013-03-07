<?php if($total = $this->getContainer()->getProfile()->getTotal()): ?>
  
  <div style="width:100%">
    
    <p style="float:right;font-size:85%;padding:10px 10px 10px 0;">Page rendered in <?php echo $total; ?> ms.</p>
    
  </div>

<?php \out::i($this->getContainer()->getProfile()); ?>
  
<?php endif; ?>
