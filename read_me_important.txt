IMPORTANT!

Since this project was compressed without the heavy system libraries (vendor and node_modules) to reduce its size, you MUST run the following commands in your Terminal (or Command Prompt) immediately after extracting the files so the system can work properly:

1. Install PHP dependencies (Laravel framework):
composer install

2. Install frontend dependencies (JavaScript & CSS):
npm install

3. (Optional) Compile frontend assets:
npm run build

4. (Very Important) Copy the environment configuration file:
copy .env.example .env

5. (Very Important) Generate a new application key:
php artisan key:generate

After completing these steps, you can start the local server by running:
php artisan serve
