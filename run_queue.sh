#!/bin/bash

# Load environment variables from the .env file
if [ -f ./.env ]; then
    source ./.env
else
    echo ".env file not found."
    exit 1
fi

if [ -n "$WWW_USER" ]; then
    chown -R "$WWW_USER":"$WWW_USER" storage/
fi

if [ -n "$WWW_USER" ]; then
    sudo -u "$WWW_USER" php artisan queue:restart
else
    php artisan queue:restart
fi


if [ "$APP_ENV" == "production" ]; then
    WORKERS=8
else
    WORKERS=2
fi

for ((i=1; i<=WORKERS; i++)); do
    if [ -n "$WWW_USER" ]; then
        sudo -u "$WWW_USER" php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
    else
        php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
    fi
done
