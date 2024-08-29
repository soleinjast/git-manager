<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Git-Manager

Git-Manager is a web application built with the Laravel framework, designed to assist educators in tracking and evaluating student contributions in group Git repositories hosted on GitHub. The tool offers detailed commit analysis, automated repository management, accurate tracking through university username attachment, and a comprehensive statistical dashboard for each repository, providing educators with a powerful resource for monitoring and evaluation in group projects.



## Features

- [**Detailed Commit Analysis**](#): Analyze commits to distinguish between meaningful and non-meaningful changes, ensuring a clear understanding of student contributions.
- [**Automated Repository Management**](#): Automatically manage the creation, updating, and deletion of GitHub repositories, streamlining the workflow for educators.
- [**University Username Attachment**](#): Attach university usernames to GitHub repository collaborators for accurate tracking, ensuring that contributions are correctly attributed to individual students.
- [**Comprehensive Statistical Dashboard**](#): Gain in-depth insights into repository activity, individual contributions, and overall project health, providing educators with a powerful tool for monitoring and evaluation.


## Getting Started

### Prerequisites

Ensure you have the following installed on your machine:

- PHP >= 8.2
- Composer
- Node.js & npm (optional for frontend development)

### Project Setup Instructions

1. Install Composer dependencies:
    
    ```bash
    composer install
    ```
2. Install NPM dependencies:
    
    ```bash
    npm install
    ```
3. Set up your environment variables:
    
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
   Generate an application key:
    
    ```bash
    php artisan key:generate
    ```
4. Set up the database:

   If using SQLite, ensure the database file exists:

    ```bash
    touch database/database.sqlite
    ```
    Run the database migrations:
    
     ```bash
    php artisan migrate
    ```
Running the Server

Start the local development server:
 ```bash
php artisan serve
   ```
Visit http://localhost:8000 in your browser to see the application running.

Running Tests

To run all the tests, use the following command:
 ```bash
php artisan test
   ```
Additionally, if you want to generate a code coverage report in HTML format while running tests in parallel, you can use the following command:

```bash
php artisan test --parallel --coverage-html coverage-report
   ```
To generate a code coverage report, make sure you have Xdebug configured in your PHP environment. After configuring Xdebug, you can run your tests with code coverage.
