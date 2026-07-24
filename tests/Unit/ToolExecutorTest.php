<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Meta\ToolExecutor;

class ToolExecutorTest extends TestCase {
    public function test_get_all_tools_returns_array(): void {
        $tools = ToolExecutor::getAllTools();
        $this->assertIsArray($tools);
        $this->assertGreaterThanOrEqual(7, count($tools));
    }

    public function test_get_tool_type_read(): void {
        $this->assertEquals('read', ToolExecutor::getToolType('get_account'));
        $this->assertEquals('read', ToolExecutor::getToolType('list_campaigns'));
        $this->assertEquals('read', ToolExecutor::getToolType('get_insights'));
    }

    public function test_get_tool_type_write(): void {
        $this->assertEquals('write', ToolExecutor::getToolType('pause_object'));
        $this->assertEquals('write', ToolExecutor::getToolType('resume_object'));
        $this->assertEquals('write', ToolExecutor::getToolType('set_adset_budget'));
        $this->assertEquals('write', ToolExecutor::getToolType('set_campaign_budget'));
    }

    public function test_get_tool_type_unknown(): void {
        $this->assertNull(ToolExecutor::getToolType('nonexistent'));
    }

    public function test_build_preview_pause(): void {
        $preview = ToolExecutor::buildPreview('pause_object', ['objectType' => 'campaign', 'objectId' => '123'], 'DZD');
        $this->assertStringContainsString('PAUSE', $preview);
        $this->assertStringContainsString('campaign', $preview);
    }

    public function test_build_preview_budget(): void {
        $preview = ToolExecutor::buildPreview('set_adset_budget', ['adsetId' => '456', 'dailyBudget' => 5000], 'DZD');
        $this->assertStringContainsString('5000', $preview);
        $this->assertStringContainsString('DZD', $preview);
    }
}
