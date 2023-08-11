# memek---Laravel-CLI-tools-for-creating-backend-template
-
Memek - Laravel CLI tools for creating backend ready template

The custom Artisan command, called memek:controller, serves as a powerful utility in your Laravel application, automating the creation of essential components in laravel project for managing resources. This command is designed to automate the process of generating controllers, models, migrations, and views for a specific resource, significantly reducing development time and effort.

here's the step for usage:
1. Create Your Laravel Project
   `composer create-project laravel/laravel projectname`
2. Clone this repository inside App\Console\
   `git clone https://github.com/Irvan741/memek---Laravel-CLI-tools-for-creating-backend-template.git`
3. finaly run
  `php artisan memek:controller {yourcontrollerName (without using controller afterward)} {YourModelName} your_column_name:datatype,your_column_name:datatype,.....`

   for example
   `php artisan memek:controller User User name:string,email:string,age:integer,password:string,address:text`
