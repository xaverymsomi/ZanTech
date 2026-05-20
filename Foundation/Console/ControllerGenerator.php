<?php

namespace Foundation\Console;

final class ControllerGenerator
{
    public function generate(string $name): void
    {
        $name = ucfirst($name);
        $baseDir = "Modules/{$name}";

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
            mkdir("{$baseDir}/Views", 0755, true);
        }

        $controllerPath = "{$baseDir}/{$name}.php";
        if (is_file($controllerPath)) {
            echo "Error: Controller {$name} already exists.\n";
            return;
        }

        file_put_contents($controllerPath, $this->controllerContent($name));

        $viewPath = "{$baseDir}/Views/index.php";
        if (!is_file($viewPath)) {
            file_put_contents($viewPath, $this->viewContent($name));
        }

        echo "Controller {$name} generated successfully in {$controllerPath}\n";
    }

    private function controllerContent(string $name): string
    {
        return "<?php\n\nnamespace Modules\\{$name};\n\nuse Http\\Controller;\n\nclass {$name} extends Controller\n{\n    public string $module = '{$name}';\n\n    public function __construct()\n    {\n        parent::__construct();\n        \$this->model = null;\n    }\n\n    public function index(): void\n    {\n        \$this->view()->title = '{$name}';\n        \$this->render('index');\n    }\n}\n";
    }

    private function viewContent(string $name): string
    {
        return "<!-- {$name} Index View -->\n<div class=\"container-fluid\">\n    <div class=\"row\">\n        <div class=\"col-12\">\n            <div class=\"zt-card\">\n                <div class=\"zt-card__header\">\n                    <h5>{$name}</h5>\n                </div>\n                <div class=\"zt-card__body\">\n                    <p>Welcome to the {$name} controller.</p>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n";
    }
}
