## about spatialGE

Laravel 10, VueJS 3, Bootstrap 5, Docker...

- Laravel 10
- VueJS 3
- Bootstrap 5
- Docker (r-base with spatialGE and redis)

## Development environment

- composer update
- npm install
- Create the DB in MySQL according to the name in .env 
- php artisan migrate:fresh --seed   (WARNING: this will delete all database tables and create them again)
- php artisan storage:link
- Inside the _docker folder, execute: docker build -t spatialge .
- npm run dev  (this will run vite to compile any change in real-time)
