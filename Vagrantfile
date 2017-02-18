# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration

local_share="."
remote_share="/home/vagrant"
webserver_root="/home/vagrant/web"
webserver_name="test.app"

database_name="testdatabase"
database_user="testuser"
database_password="testpassword"

server_ip= "192.168.22.10"
login_username="vagrant"
apache_run_user=login_username
apache_run_group=login_username

# Languages, PHP Package and xDebug
php_timezone          = "UTC"    # http://php.net/manual/en/timezones.php
php_version           = "5.5"    # Options: 5.5 | (5.6) | 7.1
enable_xdebug         = "true"   # To disable/enable and install xDebug set this to "false" | "true" (default="false")


Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  # config.ssh.private_key_path = "~/.ssh/id_rsa"
  config.ssh.username = login_username
  config.vm.network "private_network", ip: server_ip
    
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/increase_swap.sh"
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_mysql.sh", env: {"MYSQL_ROOT_PASS" => "adminpass", "MYSQL_ROOT_USER" => "root"}
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysqldb.sh", env: {"MYSQL_DB_NAME" => database_name}
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysql_grant_access_to_user.sh", env: {"MYSQL_USER_NAME" => database_user, "MYSQL_USER_PASSWORD" => database_password, "MYSQL_USER_DB" => database_name}
  # config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_mysql_import.sh", env: {"MYSQL_DB" => database_name}
  
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_apache2_php.sh", args: [ php_timezone, php_version, enable_xdebug, apache_run_user, apache_run_group ]
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/install_composer.sh"
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_vhost_helper.sh", args: [ "-d", webserver_root, "-s", webserver_name ] 
  
  config.vm.provision :shell, path: "~/Sites/vagrant_scripts/setup_terminal_startdir.sh", privileged: false, env: {"START_DIR" => webserver_root}  

  config.vm.network :forwarded_port, guest: 80, host: 8000
  config.vm.network :forwarded_port, guest: 3306, host: 33060
  config.vm.network :forwarded_port, guest: 9000, host: 9001

   config.vm.synced_folder local_share, 
   		remote_share, 
  		id: "vagrant-www"
end
