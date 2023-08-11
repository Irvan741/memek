# memek---Laravel-CLI-tools-for-creating-backend-template
-
Memek - Laravel CLI tools for creating backend ready template

here's the step for usage:
1. Create Your Laravel Project
   `composer create-project laravel/laravel projectname`
2. Clone this repository inside App\Console\
   `git clone https://github.com/Irvan741/memek---Laravel-CLI-tools-for-creating-backend-template.git`
3. finaly run
  `php artisan memek:controller {yourcontrollerName (without using controller afterward)} {YourModelName} your_column_name:datatype,your_column_name:datatype,.....`

   for example
   `php artisan memek:controller User User name:string,email:string,age:integer,password:string,address:text`
