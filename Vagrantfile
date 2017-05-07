# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration
webserver_name="test.app"

database_name="testdatabase"
database_user="testuser"
database_password="testpassword"

# Languages, PHP Package and xDebug
php_timezone          = "UTC"    # http://php.net/manual/en/timezones.php
php_version           = "7.1"    # Options: 5.5 | (5.6) | 7.1
enable_xdebug         = "true"   # To disable/enable and install xDebug set this to "false" | "true" (default="false")


# Sharing-Stuff
local_share="./vagrant_share"
local_web="./vagrant_web"
remote_share="/home/vagrant/share"
remote_web="/home/vagrant/web"

# Apache Root
webserver_root="#{remote_web}/public"

# Server / Network / SSH Stuff
server_ip= "192.168.22.10"
#ssh_login_pub_key_path=".ssh/id_rsa.pub"
ssh_username="vagrant"
apache_run_user=ssh_username
apache_run_group=ssh_username
mysql_root_password="adminpass"


Vagrant.configure("2") do |config|
  config.vm.box 	= "ubuntu/trusty64"
  config.vm.network "private_network", ip: server_ip  
  config.ssh.password="vagrant"
    
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/increase_swap.sh"
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_mysql.sh", env: {"MYSQL_ROOT_PASS" => mysql_root_password, "MYSQL_ROOT_USER" => "root"}
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysqldb.sh", env: {"MYSQL_DB_NAME" => database_name}
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysql_grant_access_to_user.sh", env: {"MYSQL_USER_NAME" => database_user, "MYSQL_USER_PASSWORD" => database_password, "MYSQL_USER_DB" => database_name}
  # config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysql_import.sh", env: {"MYSQL_DB" => database_name}
  
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_apache2_php.sh", args: [ php_timezone, php_version, enable_xdebug, apache_run_user, apache_run_group ]
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_composer.sh"
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_composer_global.sh", args: ["laravel/installer"], privileged: false
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_vhost_helper.sh", args: [ "-d", webserver_root, "-s", webserver_name ] 
  
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_terminal_startdir.sh", privileged: false, env: {"START_DIR" => remote_web}  

  config.vm.network :forwarded_port, guest: 80, host: 8000
  config.vm.network :forwarded_port, guest: 3306, host: 33060
  config.vm.network :forwarded_port, guest: 9000, host: 9001

  # Mount share "web"
  config.vm.synced_folder local_web, 
    remote_web, 
  	id: "vagrant-web"
  
  # Mount Share "Share"	
  config.vm.synced_folder local_share, 
    remote_share, 
  	id: "vagrant-share"
  	
  # SSH Configuration
  config.ssh.username 	= ssh_username
  id_rsa_ssh_key_pub 	= File.read(File.join(Dir.home, ".ssh", "id_rsa.pub"))
  config.vm.provision :shell, :inline => "echo 'Windows-specific: Copying local id_rsa SSH Key to VM auth_keys for auth purposes (login into VM included)...' && mkdir -p /home/#{ssh_username}/.ssh && echo 'Using PubKey: #{id_rsa_ssh_key_pub }' && echo '#{id_rsa_ssh_key_pub }' > /home/#{ssh_username}/.ssh/authorized_keys && chmod 600 /home/#{ssh_username}/.ssh/authorized_keys", privileged: false


end
