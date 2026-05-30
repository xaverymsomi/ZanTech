<?php

namespace Foundation\Console;

final class ModuleGenerator
{
    public function generate(string $name, bool $example = false): void
    {
        $name = $this->normalizeName($name);
        $baseDir = "app/Modules/{$name}";

        if (is_dir($baseDir)) {
            echo "Error: Module {$name} already exists.\n";
            return;
        }

        mkdir($baseDir, 0755, true);
        mkdir("{$baseDir}/Views", 0755, true);

        $this->createController($name, $baseDir, $example);
        $this->createModel($name, $baseDir, $example);
        $this->createView($name, $baseDir, $example);

        $suffix = $example ? ' with example actions' : '';
        echo "Module {$name} generated successfully{$suffix} in {$baseDir}\n";
    }

    private function normalizeName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?: 'Module';
        return ucfirst($name);
    }

    private function createController(string $name, string $dir, bool $example): void
    {
        if (!$example) {
            $content = "<?php\n\nnamespace Modules\\{$name};\n\nuse Http\\Controller;\n\nclass {$name} extends Controller\n{\n    public string \$module = '{$name}';\n\n    public function __construct()\n    {\n        parent::__construct();\n        \$this->model = new {$name}_Model();\n    }\n\n    public function index(): void\n    {\n        \$this->view()->title = '{$name}';\n        \$this->render('index');\n    }\n}\n";
            file_put_contents("{$dir}/{$name}.php", $content);
            return;
        }

        $permission = 'view_' . strtolower($name);
        $content = <<<PHP
<?php

namespace Modules\\{$name};

use Authentication\\Gate;
use Http\\Controller;

class {$name} extends Controller
{
    public string \$module = '{$name}';

    public function __construct()
    {
        parent::__construct();
        \$this->model = new {$name}_Model();
    }

    public function index(): void
    {
        \$this->requirePermission('{$permission}');

        \$this->view()->title = '{$name}';
        \$this->view()->items = \$this->model->exampleItems();
        \$this->render('index');
    }

    public function status()
    {
        return \$this->responseSuccess(200, '{$name} module ready', [
            'module' => '{$name}',
            'table' => \$this->model->getTable(),
        ]);
    }
}
PHP;

        file_put_contents("{$dir}/{$name}.php", $content);
    }

    private function createModel(string $name, string $dir, bool $example): void
    {
        if (!$example) {
            $content = "<?php\n\nnamespace Modules\\{$name};\n\nuse Database\\Model;\n\nclass {$name}_Model extends Model\n{\n    protected string \$table = 'mx_" . strtolower($name) . "';\n}\n";
            file_put_contents("{$dir}/{$name}_Model.php", $content);
            return;
        }

        $table = 'mx_' . strtolower($name);
        $content = <<<PHP
<?php

namespace Modules\\{$name};

use Database\\Model;

class {$name}_Model extends Model
{
    protected string \$table = '{$table}';
    protected string \$title = '{$name}';

    public function exampleItems(): array
    {
        return [
            ['name' => 'Controller', 'status' => 'Ready'],
            ['name' => 'Model', 'status' => 'Ready'],
            ['name' => 'View', 'status' => 'Ready'],
        ];
    }
}
PHP;

        file_put_contents("{$dir}/{$name}_Model.php", $content);
    }

    private function createView(string $name, string $dir, bool $example): void
    {
        if (!$example) {
            $content = "<!-- {$name} Index View -->\n<div class=\"container-fluid\">\n    <div class=\"row\">\n        <div class=\"col-12\">\n            <div class=\"zt-card\">\n                <div class=\"zt-card__header\">\n                    <h5>{$name}</h5>\n                </div>\n                <div class=\"zt-card__body\">\n                    <p>Welcome to the {$name} module.</p>\n                </div>\n            </div>\n        </div>\n    </div>\n</div>\n";
            file_put_contents("{$dir}/Views/index.php", $content);
            return;
        }

        $content = <<<PHP
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="zt-card">
                <div class="zt-card__header">
                    <h5><?= htmlspecialchars(\$title ?? '{$name}', ENT_QUOTES, 'UTF-8') ?></h5>
                </div>
                <div class="zt-card__body">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Part</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ((\$items ?? []) as \$item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) \$item['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) \$item['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
PHP;

        file_put_contents("{$dir}/Views/index.php", $content);
    }
}
