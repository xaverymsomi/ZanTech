<?php

declare(strict_types=1);

namespace Library\Console;

final class ModuleGenerator
{
    public function generate(string $name): void
    {
        $name = ucfirst($name);
        $baseDir = "Modules/{$name}";

        if (is_dir($baseDir)) {
            echo "Error: Module {$name} already exists.\n";
            return;
        }

        // Create directories
        mkdir($baseDir, 0755, true);
        mkdir("{$baseDir}/Views", 0755, true);

        // Create Controller
        $this->createController($name, $baseDir);

        // Create Model
        $this->createModel($name, $baseDir);

        // Create Index View
        $this->createView($name, $baseDir);

        echo "Module {$name} generated successfully in {$baseDir}\n";
    }

    private function createController(string $name, string $dir): void
    {
        $content = "<?php\n\nnamespace Modules\\{$name};\n\nuse Library\\Controller;\n\nclass {$name} extends Controller\n{\n    public string \$module = '{$name}';\n\n    public function __construct()\n    {\n        parent::__construct();\n        \$this->model = new {$name}_Model();\n    }\n\n    public function index(): void\n    {\n        \$this->view()->title = '{$name}';\n        \$this->render('index');\n    }\n}\n";
        file_put_contents("{$dir}/{$name}.php", $content);
    }

    private function createModel(string $name, string $dir): void
    {
        $content = "<?php\n\nnamespace Modules\\{$name};\n\nuse Library\\Model;\n\nclass {$name}_Model extends Model\n{\n    protected string \$table = 'mx_" . strtolower($name) . "';\n}\n";
        file_put_contents("{$dir}/{$name}_Model.php", $content);
    }

    private function createView(string $name, string $dir): void
    {
        $content = "<!-- {$name} Index View -->\n<div class=\"container-fluid\">\n    <div class=\"row\">\n        <div class=\"col-12\">\n            <div class=\"zt-card\">\n                <div class=\"zt-card__header\">\n                    <h5>{$name}</h5>\n                </div>\n                <div class=\"zt-card__body\">\n                    <p>Welcome to the {$name} module.</p>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n";
        file_put_contents("{$dir}/Views/index.php", $content);
    }
}
