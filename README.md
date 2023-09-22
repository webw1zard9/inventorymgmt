# Inventory Management System


## Overview

This is a simple inventory management system that allows users to add, edit, and delete items. It manages purchasing and sales, inventory controls between multiple locations is necessary. There is an accounting module that tracks consignment products, payables and P&L.

Demo site: https://inventorymgmt.net/

Login: admin@test.com
Password: admin

## Installation

Create your .env file

    $ mv .env.example .env

Generate an app encryption key

    $ php artisan key:generate

Create an empty database for your application. In the .env file, add database information to allow Laravel to connect to the database

    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=
    DB_USERNAME=
    DB_PASSWORD=

Update any other configuration settings in the .env file, such as the email driver, email host, email address, etc
You can also set 'Product Types' and 'Product Unit of Measure'

    PRODUCT_TYPES=Product,Service
    PRODUCT_UOM=Unit,Dozen

Install Composer dependencies. The database will be migrated after dependencies are installed. 

    $ composer install 

On some VPS/Cloud servers the `storage` folder may not be writable. If you experience issues, create the necessary folders and assign the correct permissions:

    $ mkdir -p ./storage/framework/sessions storage/framework/views storage/framework/cache/data
    $ chmod -R 775 ./storage
    $ composer install

Install Node dependencies

    $ npm install

Migrate the database (This might have already been done when Composer dependencies were installed)

    $ php artisan migrate

Populate the database with seed data. This will create a user allowing you to login to the application

    $ php artisan db:seed

On some servers the live wire assets need to be published

    $ php artisan livewire:publish

Compile the CSS and JS files for development

    $ npm run dev

Start the local development server

    $ php artisan serve

For production, run the following command to minify the CSS and JS files

    $ npm run prod

You can now access the server at http://localhost:8000

## <a name="usage"></a>Usage

### <a name="login"></a>Login

The login page can be accessed at http://localhost:8000/login. The default login credentials are:

    Username: admin@test.com
    Password: admin


## <a name="license"></a>License

Free software distributed under the terms of the [MIT license](./MIT-LICENSE.txt).
