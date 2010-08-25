Options +FollowSymLinks +ExecCGI

<IfModule mod_rewrite.c>
  RewriteEngine On

  # uncomment the following line, if you are having trouble
  # getting no_script_name to work
  #RewriteBase /
  
  # these 3 lines actually ignores files and folders...
  # http://www.ilovejackdaniels.com/apache/ignore-directories-in-mod-rewrite/
  RewriteCond %{REQUEST_FILENAME} -f [NC,OR]
  RewriteCond %{REQUEST_FILENAME} -d [NC]
  RewriteRule .* - [L]

  # push everything to the main index, let it sort it out...
  RewriteRule ^(.*)$ <?php echo $this->getField('name',''); ?> [QSA,L]
</IfModule>
