chown -R apache:apache storage/
sudo -u apache php artisan queue:restart
sudo -u apache php artisan queue:work --queue="low" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="low" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
