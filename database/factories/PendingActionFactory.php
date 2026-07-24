<?php
namespace Database\Factories;

use App\Models\PendingAction;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PendingActionFactory extends Factory {
    protected $model = PendingAction::class;

    public function definition(): array {
        $conv = Conversation::factory()->create();
        return [
            'conversation_id' => $conv->id,
            'tool_name' => 'pause_object',
            'tool_input' => ['objectType' => 'campaign', 'objectId' => '123'],
            'preview' => 'Pause campaign 123',
            'conversation_state' => [],
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
