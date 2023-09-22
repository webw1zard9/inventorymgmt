# Inventory Management System


## Overview

This is a simple inventory management system that allows users to add, edit, and delete items from a database. The system also allows users to search for items by name, category, or price. The system is built using Python and Flask, and uses a SQLite database to store the data. The system is deployed on Heroku and can be accessed [here](https://inventory-management-system-1.herokuapp.com/).

Demo site: https://inventorymgmt.net/

Login: admin
Password: admin

## Installation

Install Composer dependencies

    $ composer install 

Install Node dependencies

    $ npm install

Create a copy of your .env file

    $ cp .env.example .env

Generate an app encryption key

    $ php artisan key:generate

Create an empty database for our application. In the .env file, add database information to allow Laravel to connect to the database

    DB_CONNECTION=mysql
    DB_HOST=
    DB_PORT=
    DB_DATABASE=
    DB_USERNAME=
    DB_PASSWORD=

Migrate the database

    $ php artisan migrate

(Optional): Populate the database with seed data

    $ php artisan db:seed

Complie the CSS and JS files

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