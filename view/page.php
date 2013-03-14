<?php

/* xhtml 1.0 doctype, html tag...
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">

html 4 doctype...
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

Currently, we are going to use the html 5 doctype:
http://ejohn.org/blog/html5-doctype/
http://www.w3schools.com/html5/tag_doctype.asp
*/

?><!DOCTYPE html>

<html>
  
  <head>
  
    <meta name="title" content="<?php echo $this->getField('title',''); ?>">
    <meta name="description" content="<?php echo $this->getField('desc',''); ?>" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $this->getField('charset','utf-8'); ?>" />
    
    <title><?php echo $this->getField('title',''); ?></title>
    
    <?php if($this->hasField('has_favicon')): ?>
      <link rel="icon" href="/favicon.ico" type="image/x-icon" sizes="16x16" />
      <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <?php endif; ?>
  
    <?php if($this->hasField('canon_url')): /* see: http://www.mattcutts.com/blog/canonical-link-tag/ */ ?>
      <link rel="canonical" href="<?php echo $this->getField('canon_url'); ?>"/>
    <?php endif; ?>
    <?php if($this->hasField('rev_canon_url')): ?>
      <link rel="alternate shorter" rev="canonical" href="<?php echo $this->getField('rev_canon_url'); ?>"/>
    <?php endif; ?>
    
    <?php if($this->hasField('rss_url')): ?>
      <link rel="alternate" type="application/rss+xml" title="RSS feed for this page" href="<?php echo $this->getField('rss_url'); ?>" />
    <?php endif; ?>

    <?php if($assets = $this->getContainer()->getAssets()): ?>
    
      <?php echo $assets->render('css'); ?>
      
      <?php echo $assets->render('js'); ?>
    
    <?php endif; ?>

  </head>
  <?php flush(); /* http://developer.yahoo.com/performance/rules.html#flush */ ?>
  
  <body>
  
    <?php if($this->hasField('header_template')): ?>
  
      <!-- BEGIN header -->
      <?php $this->out($this->getField('header_template')); flush(); ?> 
      <!-- END header -->
      
    <?php endif; ?>
      
    <?php if($this->hasField('content_template')): ?>
        
      <!-- BEGIN content -->
      <?php $this->out($this->getField('content_template')); flush() ?> 
      <!-- END content -->
    
    <?php endif; ?>
    
    <?php if($this->hasField('footer_template')): ?>
      
      <!-- BEGIN footer -->
      <?php $this->out($this->getField('footer_template')); flush(); ?> 
      <!-- END footer -->
      
    <?php endif; ?>

  </body>
  
</html>
