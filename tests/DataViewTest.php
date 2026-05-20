<?php



use PHPUnit\Framework\TestCase;
use View\DataView;

final class DataViewTest extends TestCase
{
    public function testRenderTableHidesConfiguredColumnsAndEscapesValues(): void
    {
        $view = new DataView();

        $html = $view->renderTable(
            headings: [
                ['column' => 'txt_name', 'label' => 'Name'],
                ['column' => 'secret', 'label' => 'Secret'],
            ],
            records: [
                [
                    'id' => 10,
                    'txt_name' => '<Ada>',
                    'secret' => 'classified-value',
                ],
            ],
            hidden: ['secret']
        );

        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('&lt;Ada&gt;', $html);
        $this->assertStringNotContainsString('Secret', $html);
        $this->assertStringNotContainsString('classified-value', $html);
    }

    public function testRenderTableIncludesActionsWhenProvided(): void
    {
        $html = (new DataView())->renderTable(
            headings: [['column' => 'txt_name', 'label' => 'Name']],
            records: [['txt_row_value' => 'row-1', 'txt_name' => 'Ada']],
            actions: [[
                'name' => 'Edit',
                'icon' => 'fa-edit',
                'type' => 'edit',
                'color' => 'primary',
            ]]
        );

        $this->assertStringContainsString('Actions', $html);
        $this->assertStringContainsString("edit('row-1')", $html);
    }

    public function testGetStylesReturnsStyleBlock(): void
    {
        $styles = DataView::getStyles();

        $this->assertStringContainsString('<style>', $styles);
        $this->assertStringContainsString('.table-row-modern', $styles);
    }
}
