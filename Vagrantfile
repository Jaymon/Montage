# this needs to be included to get access to the Vagrant::Configuration singleton
require File.join("E:/Projects/Vagrant/_vagrant/lib","VagrantfileCommon")

# place all your custom configuration below this line
vconfig = Vagrant::Configuration.get

vconfig.setName("Montage")
# vconfig.forwardPort(5052, 15052)
# vconfig.setMemory(2048)

#vconfig.addCookbookPath("./site-cookbooks")

# vconfig.setIP("33.33.33.10")

vconfig.addRecipe("apt")
# # vconfig.addRecipe("guest-additions",{:version => "4.1.8"})
vconfig.addRecipe("nginx::phpsite",{
  # :server_name => 'localhost',
  :root => "/vagrant"
})

vconfig.addRecipe("php-fpm")
vconfig.addRecipe("phpunit")
vconfig.addRecipe("zend")

# vconfig.addRecipe("postgres",{
#   :databases => {
#     # username => [dbname,...]
#     "vagrant" => ["vagrant","test_vagrant"],
#   }
# })

vconfig.addRecipe("bash")

# vconfig.addRecipe("nagios::nginx")


# vslave = Vagrant::Configuration.get(:slave)
# vslave.setName("Vagrant test box 2")
# 
# vslave.setBox("oneiric64","http://dl.dropbox.com/u/3886896/oneiric64.box")
# vslave.forwardPort(80, 10081)
# 
# vslave.addRecipe("bash")
# vslave.setIP("33.33.33.11")

# install arbitrary packages
# vconfig.addRecipe("packages",{
#   :install => [
#     "curl"
#   ]
# })
