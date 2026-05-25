<?php



use Foundation\Console\ModuleGenerator;
use PHPUnit\Framework\TestCase;

final class ModuleGeneratorTest extends TestCase
{
    private string $originalCwd;
    private string $workDir;

    protected function setUp(): void
    {
        $this->originalCwd = getcwd() ?: __DIR__;
        $this->workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zantech-module-generator-' . bin2hex(random_bytes(6));
        mkdir($this->workDir, 0777, true);
        chdir($this->workDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        $this->removeDirectory($this->workDir);
    }

    public function testGenerateCreatesModuleSkeletonWithCurrentControllerNamespace(): void
    {
        ob_start();
        (new ModuleGenerator())->generate('billing');
        $output = ob_get_clean();

        $this->assertSame("Module Billing generated successfully in app/Modules/Billing\n", $output);
        $this->assertFileExists($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing.php');
        $this->assertFileExists($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing_Model.php');
        $this->assertFileExists($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Views/index.php');

        $controller = file_get_contents($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing.php');
        $model = file_get_contents($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing_Model.php');

        $this->assertStringContainsString('use Http\\Controller;', (string) $controller);
        $this->assertStringNotContainsString('use Library\\Controller;', (string) $controller);
        $this->assertStringContainsString('use Database\\Model;', (string) $model);
    }

    public function testGenerateDoesNotOverwriteExistingModule(): void
    {
        mkdir($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing', 0777, true);

        ob_start();
        (new ModuleGenerator())->generate('billing');
        $output = ob_get_clean();

        $this->assertSame("Error: Module Billing already exists.\n", $output);
        $this->assertFileDoesNotExist($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing.php');
    }

    public function testGenerateExampleModuleIncludesPermissionGuardAndStatusEndpoint(): void
    {
        ob_start();
        (new ModuleGenerator())->generate('billing', true);
        $output = ob_get_clean();

        $this->assertSame("Module Billing generated successfully with example actions in app/Modules/Billing\n", $output);

        $controller = file_get_contents($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing.php');
        $model = file_get_contents($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Billing_Model.php');
        $view = file_get_contents($this->workDir . DIRECTORY_SEPARATOR . 'app/Modules/Billing/Views/index.php');

        $this->assertStringContainsString('verifyPermission(\'view_billing\')', (string) $controller);
        $this->assertStringContainsString('function status()', (string) $controller);
        $this->assertStringContainsString('exampleItems', (string) $model);
        $this->assertStringContainsString('htmlspecialchars', (string) $view);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
