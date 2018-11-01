cd ~/Sites/material_pool/vagrant_web/; npm run prod
rsync -av --exclude=".git*" --exclude=".idea*" --exclude="*.DS_Store" --exclude="node_modules/*" --exclude=".env" --exclude="vendor/*" --exclude=".env.example" --exclude="storage/*"  --exclude="update.sh" --exclude="INSTALL.txt" --exclude="bootstrap/*" --exclude="_ide_helper*" --exclude="resources/assets/*" ~/Sites/material_pool/vagrant_web/ steven@192.168.3.110:/var/www/wissensbase
rm public/js/*.js; rm public/js/*.map; rm public/css/*css;  rm public/css/*.map
ssh steven@192.168.3.110 -t 'cd /var/www/wissensbase; composer install --no-dev; sudo -u www-data php artisan migrate --force'
