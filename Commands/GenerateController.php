<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Facades\File;

class GenerateControllerCommand extends Command
{
    protected function configure()
    {
        $this->setName('memek:controller')
            ->setDescription('Generate a new controller, model, migration, and views')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller')
            ->addArgument('model', InputArgument::REQUIRED, 'The name of the model')
            ->addArgument('columns', InputArgument::REQUIRED, 'Columns for the migration table (e.g., name:string,email:string,age:integer)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $controllerName = ucfirst($name) . 'Controller';
        $modelName = ucfirst($input->getArgument('model'));
        $columns = explode(',', $input->getArgument('columns'));
        $fillableAttributes = $this->extractFillableAttributes($columns);
        $migrationColumns = $this->extractMigrationColumns($columns);

        // Process migration columns
        $migrationColumnDefinitions = $this->generateMigrationColumns($migrationColumns);

        // Generate controller
        $controllerContent = <<<PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\\{$modelName};

class {$controllerName} extends Controller
{
    public function index()
    {
        // Retrieve all {$modelName} records
        \${$name} = {$modelName}::all();
        return view('{$name}.index', compact('{$name}'));
    }

    public function create()
    {
        return view('{$name}.create');
    }

    public function store(Request \$request)
    {
        // Validate and store the new {$modelName}
        \${$name} = {$modelName}::create(\$request->all());
        return redirect()->route('{$name}.index')->with('success', '{$modelName} created successfully');
    }

    public function edit(\$id)
    {
        \${$name} = {$modelName}::findOrFail(\$id);
        return view('{$name}.edit', compact('{$name}'));
    }

    public function update(Request \$request, \$id)
    {
        // Validate and update the {$modelName}
        \${$name} = {$modelName}::findOrFail(\$id);
        \${$name}->update(\$request->all());
        return redirect()->route('{$name}.index')->with('success', '{$modelName} updated successfully');
    }

    public function destroy(\$id)
    {
        // Delete the {$modelName}
        \${$name} = {$modelName}::findOrFail(\$id);
        \${$name}->delete();
        return redirect()->route('{$name}.index')->with('success', '{$modelName} deleted successfully');
    }

}
PHP;

        $controllerFilename = app_path('Http/Controllers/' . $controllerName . '.php');
        file_put_contents($controllerFilename, $controllerContent);

        // Generate layouts.app view
        $layoutsDirectory = resource_path('views/layouts');
        if (!File::exists($layoutsDirectory)) {
            File::makeDirectory($layoutsDirectory, 0755, true);
        }
        $layoutsAppContent = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{{ config('app.name', 'Laravel') }}</title>
        </head>
        <body>
            @yield('content')
        </body>
        </html>
        HTML;
    
        $layoutsAppFilename = resource_path('views/layouts/app.blade.php');
        file_put_contents($layoutsAppFilename, $layoutsAppContent);
        
        // Generate route in routes/web.php
        $routeContent = <<<PHP
        Route::resource('{$name}', {$controllerName}::class);
        PHP;

        $routesFile = base_path('routes/web.php');
        file_put_contents($routesFile, $routeContent, FILE_APPEND);


        // Create views directory
        $viewsDirectory = resource_path('views/' . strtolower($name));
        if (!File::exists($viewsDirectory)) {
            File::makeDirectory($viewsDirectory);
        }
        $fillableColumnList = implode(', ', $fillableAttributes);
        // Create index.blade.php view
        $indexViewContent = <<<HTML
    @extends('layouts.app')
    <!-- {!! $fillableColumnList !!} -->
    @section('content')
        <!-- Your index view content here -->
    @endsection
    HTML;

        file_put_contents($viewsDirectory . '/index.blade.php', $indexViewContent);

        $createViewContent = <<<HTML
        @extends('layouts.app')
        <!-- Your create view content here -->
        <!-- {!! $fillableColumnList !!} -->
        @section('content')
            <!-- Your create view content here -->
        @endsection
        HTML;
        


        file_put_contents($viewsDirectory . '/create.blade.php', $createViewContent);

        // Create edit.blade.php view
        $editViewContent = <<<HTML
    @extends('layouts.app')
    <!-- {!! $fillableColumnList !!} -->
    @section('content')
        <!-- Your edit view content here -->
    @endsection
    HTML;

        file_put_contents($viewsDirectory . '/edit.blade.php', $editViewContent);

        // Generate migration
        $migrationName = 'Create' . str_replace('_', '', ucwords($name, '_')) . 'Table';
        $migrationFilename = database_path('migrations/' . date('Y_m_d_His') . '_' . $migrationName . '.php');

        $migrationContent = <<<PHP
    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class {$migrationName} extends Migration
    {
        public function up()
        {
            Schema::create('{$modelName}s', function (Blueprint \$table) {
                \$table->id();
                {$this->generateMigrationColumns($migrationColumns)}
                \$table->timestamps();
            });
        }

        public function down()
        {
            Schema::dropIfExists('{$modelName}s');
        }
    }
    PHP;

        file_put_contents($migrationFilename, $migrationContent);

        // Generate model
        $modelFilename = app_path('Models/' . $modelName . '.php');

        $modelContent = <<<PHP
    <?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;

    class {$modelName} extends Model
    {
        protected \$fillable = [
            {$this->generateFillable($fillableAttributes)}
        ];
    }
    PHP;

        file_put_contents($modelFilename, $modelContent);

        $output->writeln('<info>Controller, model, migration, and views generated successfully!</info>');

        return Command::SUCCESS;
    }


    protected function generateMigrationColumns(array $columns)
    {
        $columnDefinitions = '';
        foreach ($columns as $column) {
            $parts = explode(':', $column);
            $name = $parts[0];
            $type = $parts[1];
            $columnDefinitions .= "\$table->{$type}('$name');\n            ";
        }
        return $columnDefinitions;
    }
    

    protected function generateFillable(array $fillableAttributes)
    {
        $fillableList = '';
        foreach ($fillableAttributes as $attribute) {
            $fillableList .= "'{$attribute}', ";
        }
        return $fillableList;
    }
    protected function extractFillableAttributes(array $columns)
    {
        $fillableAttributes = [];
        foreach ($columns as $column) {
            [$name, $type] = explode(':', $column);
            if ($type !== 'integer' && $type !== 'bigInteger' && $type !== 'float' && $type !== 'double' && $type !== 'boolean') {
                $fillableAttributes[] = $name;
            }
        }
        return $fillableAttributes;
    }

    protected function extractMigrationColumns(array $columns)
    {
        $migrationColumns = [];
        foreach ($columns as $column) {
            [$name, $type] = explode(':', $column);
            $migrationColumns[] = "{$name}:{$type}";
        }
        return $migrationColumns;
    }
}