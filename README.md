# Inventory Management System


## Overview

This is a simple inventory management system that allows users to add, edit, and delete items. It manages purchasing and sales, inventory controls between multiple locations is necessary. There is an accounting module that tracks consignment products, payables and P&L.

Demo site: https://inventorymgmt.net/

Login: admin@test.com
Password: admin

## Installation

Create your .env file

    $ mv .env.example .env

Create an empty database for our application. In the .env file, add database information to allow Laravel to connect to the database

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=
    DB_USERNAME=
    DB_PASSWORD=

Install Composer dependencies. The database will be migrated after dependencies are installed. 

    $ composer install 

Install Node dependencies

    $ npm install

On some VPS/Cloud servers the `storage` folder may not be writable. To fix this issue, run the following command:

    $ chmod -R 775 ./storage

Generate an app encryption key

    $ php artisan key:generate

Migrate the database

    $ php artisan migrate

(Optional): Populate the database with seed data

    $ php artisan db:seed

Publish all the livewire assets

    $ php artisan livewire:publish

Compile the CSS and JS files

    $ npm run dev

Start the local development server

    $ php artisan serve

You can now access the server at http://localhost:8000

## <a name="usage"></a>Usage

### <a name="login"></a>Login

The login page can be accessed at http://localhost:8000/login. The default login credentials are:

    Username: admin@test.com
    Password: admin


## <a name="license"></a>License

Free software distributed under the terms of the MIT license.