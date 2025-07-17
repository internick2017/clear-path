<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_budgets_index()
    {
        $this->actingAs($this->user);
        
        $budget = Budget::factory()->for($this->user)->create();
        
        $response = $this->get(route('budgets.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Budgets'));
    }

    public function test_user_can_create_budget()
    {
        $this->actingAs($this->user);
        
        $budgetData = [
            'category' => 'Groceries',
            'limit' => 500.00,
            'month' => now()->format('Y-m')
        ];
        
        $response = $this->post(route('budgets.store'), $budgetData);
        
        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'category' => 'Groceries',
            'limit' => 500.00
        ]);
    }

    public function test_user_cannot_create_duplicate_budget_for_same_month()
    {
        $this->actingAs($this->user);
        
        Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'month' => now()->format('Y-m-d')
        ]);
        
        $budgetData = [
            'category' => 'Groceries',
            'limit' => 500.00,
            'month' => now()->format('Y-m')
        ];
        
        $response = $this->post(route('budgets.store'), $budgetData);
        
        $response->assertSessionHasErrors(['category']);
    }

    public function test_user_can_update_budget()
    {
        $this->actingAs($this->user);
        
        $budget = Budget::factory()->for($this->user)->create();
        
        $updateData = [
            'category' => 'Updated Category',
            'limit' => 600.00,
            'month' => now()->format('Y-m')
        ];
        
        $response = $this->put(route('budgets.update', $budget), $updateData);
        
        $response->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'category' => 'Updated Category',
            'limit' => 600.00
        ]);
    }

    public function test_user_cannot_access_other_users_budget()
    {
        $otherUser = User::factory()->create();
        $budget = Budget::factory()->for($otherUser)->create();
        
        $this->actingAs($this->user);
        
        $response = $this->get(route('budgets.edit', $budget));
        $response->assertStatus(403);
    }

    public function test_budget_validation_rules()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('budgets.store'), [
            'category' => '',
            'limit' => -100,
            'month' => 'invalid-date'
        ]);
        
        $response->assertSessionHasErrors(['category', 'limit', 'month']);
    }
} 