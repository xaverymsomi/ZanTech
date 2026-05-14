<?php

declare(strict_types=1);

use Exceptions\ZantechException;
use PHPUnit\Framework\TestCase;
use View\ViewRenderer;

final class ViewRendererTest extends TestCase
{
    private string $appRoot;

    protected function setUp(): void
    {
        $this->appRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zantech-view-renderer-test';
        $this->removeDir($this->appRoot);

        mkdir($this->appRoot . '/modules/Demo/views', 0777, true);
        mkdir($this->appRoot . '/views', 0777, true);

        file_put_contents($this->appRoot . '/modules/Demo/views/index.php', 'Hello <?= $this->e($this->title) ?>');
        file_put_contents($this->appRoot . '/views/plain.php', 'Plain <?= $this->e($this->title) ?>');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->appRoot);
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    public function testRenderCanOutputModuleViewWithoutLayout(): void
    {
        $view = new ViewRenderer($this->appRoot);
        $view->title = '<Demo>';

        ob_start();
        $view->render('Demo', 'index', true);
        $output = (string)ob_get_clean();

        $this->assertSame('Hello &lt;Demo&gt;', $output);
    }

    public function testRenderFullCanOutputDirectTemplateForXhr(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

        $view = new ViewRenderer($this->appRoot);
        $view->title = 'Template';

        ob_start();
        $view->renderFull('views/plain.php');
        $output = (string)ob_get_clean();

        $this->assertSame('Plain Template', $output);
    }

    public function testMissingViewThrowsFrameworkException(): void
    {
        $this->expectException(ZantechException::class);

        (new ViewRenderer($this->appRoot))->render('Demo', 'missing', true);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
