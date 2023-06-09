php artisan queue:restart
php artisan queue:work --queue="low" --timeout=1200 --tries=1 &
php artisan queue:work --queue="default" --timeout=1200 --tries=1 &
