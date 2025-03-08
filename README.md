# Installation Instructions

## Prerequisites

Before installing the project, ensure you have the following installed on your system:

- PHP (version 8.0 or higher)
- Composer (for PHP dependency management)
- Redis (for session and cart storage)
- MySQL or any other supported database
- Node.js (optional, for frontend asset compilation)

---

## Step 1: Clone the Repository

Clone the project repository from GitHub:

```bash
git clone https://github.com/ding0055/ding0055-shopping_cart_php.git
```


## Step 2: Install PHP Dependencies

Install the required PHP dependencies using Composer:

```bash
composer install
```

## Step 3: Configure Environment
Copy the .env.example file to .env:

```bash
cp .env.example .env
```

Update the .env file with your database and Redis credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopping_cart
DB_USERNAME=root
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Step 4: Generate Application Key
Generate a unique application key:

```bash
php artisan key:generate
```

## Step 5: Run Migrations
Run the database migrations to set up the required tables:

```bash
php artisan migrate
```

## Step 6: Start the Development Server
Start the Laravel development server:

```bash
php artisan serve
```

Visit http://localhost:8000/cart in your browser to access the application.

# Manual Testing Guide

This guide provides step-by-step instructions to manually test the shopping cart application. Follow these steps to verify the core functionalities.

---

## Step 1: Add Test Items to the Cart

Click ”Add Test Items" button to simulate adding items to the cart when not login, the shopping cart state is store in Redis cache at this time and also can play with the quantities then update cart to see changes in order summary.

## Step 2: Simulate Login

Click "simulate login" button to simulate user login, at this step the shopping cart state should sync with the same user's shopping cart data stored in database(there is nothing in database at first time loaded). Also play with tne numbers then update cart to see the changes in order summary and this time the cart state will store in database.