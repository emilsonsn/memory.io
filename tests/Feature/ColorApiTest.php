<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_available_color_options(): void
    {
        $this->getJson('/api/colors')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'value' => 'gray',
                'label' => 'Gray',
            ])
            ->assertJsonFragment([
                'value' => 'blue',
                'label' => 'Blue',
            ]);
    }
}
