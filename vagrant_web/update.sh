# Updating git-Version-Tag
cd ~/Sites/material_pool/vagrant_web/
VERSION=`git describe --tags --abbrev=0 | awk -F. '{$NF+=1; OFS="."; print $0}'`
git tag -a $VERSION -m "New Release pushed to Materialpool"

npm run prod
rsync -av --exclude=".git*" --exclude=".idea*" --exclude="*.DS_Store" --exclude="node_modules/*" --exclude=".env" --exclude="vendor/*" --exclude=".env.example" --exclude="storage/*"  --exclude="update.sh" --exclude="INSTALL.txt" --exclude="bootstrap/cache/*" --exclude="_ide_helper*" ~/Sites/material_pool/vagrant_web/ steven@192.168.3.110:/var/www/wissensbase
rm public/js/*.js; rm public/js/*.map; rm public/css/*css;  rm public/css/*.map
ssh steven@192.168.3.110 -t 'cd /var/www/wissensbase; composer install --no-dev; sudo -u www-data php artisan migrate --force'
