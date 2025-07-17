<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Budget;
use App\Services\BudgetService;
use App\Notifications\BudgetExceededNotification;
use App\Notifications\TransactionCategoryChangeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_transactions_index()
    {
        $this->actingAs($this->user);
        
        Transaction::factory()->for($this->user)->create();
        
        $response = $this->get(route('transactions.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Transactions'));
    }

    public function test_user_can_create_transaction()
    {
        $this->actingAs($this->user);
        
        $transactionData = [
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 50.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Weekly groceries'
        ];
        
        $response = $this->post(route('transactions.store'), $transactionData);
        
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 50.00
        ]);
    }

    public function test_expense_transaction_checks_budget_limit()
    {
        Notification::fake();
        
        $this->actingAs($this->user);
        
        // Create a budget that will be exceeded
        Budget::factory()->for($this->user)->create([
            'category' => 'Groceries',
            'limit' => 100.00,
            'spent' => 80.00,
            'month' => now()->format('Y-m-d')
        ]);
        
        $transactionData = [
            'type' => 'expense',
            'category' => 'Groceries',
            'amount' => 50.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'This will exceed budget'
        ];
        
        $response = $this->post(route('transactions.store'), $transactionData);
        
        $response->assertRedirect(route('transactions.index'));
        Notification::assertSentTo($this->user, BudgetExceededNotification::class);
    }

    public function test_user_can_update_transaction()
    {
        $this->actingAs($this->user);
        
        $transaction = Transaction::factory()->for($this->user)->create();
        
        $updateData = [
            'type' => 'income',
            'category' => 'Salary',
            'amount' => 2000.00,
            'date' => now()->format('Y-m-d'),
            'note' => 'Monthly salary'
        ];
        
        $response = $this->put(route('transactions.update', $transaction), $updateData);
        
        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => 'income',
            'category' => 'Salary',
            'amount' => 2000.00
        ]);
    }

    public function test_category_change_sends_notification()
    {
        Notification::fake();
        
        $this->actingAs($this->user);
        
        $transaction = Transaction::factory()->for($this->user)->create([
            'category' => 'Groceries'
        ]);
        
        $updateData = [
            'type' => $transaction->type,
            'category' => 'Dining Out',
            'amount' => $transaction->amount,
            'date' => $transaction->date->format('Y-m-d'),
            'note' => $transaction->note
        ];
        
        $response = $this->put(route('transactions.update', $transaction), $updateData);
        
        $response->assertRedirect(route('transactions.index'));
        Notification::assertSentTo($this->user, TransactionCategoryChangeNotification::class);
    }

    public function test_user_cannot_access_other_users_transaction()
    {
        $otherUser = User::factory()->create();
        $transaction = Transaction::factory()->for($otherUser)->create();
        
        $this->actingAs($this->user);
        
        $response = $this->get(route('transactions.edit', $transaction));
        $response->assertStatus(403);
    }

    public function test_transaction_validation_rules()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('transactions.store'), [
            'type' => 'invalid',
            'category' => '',
            'amount' => -100,
            'date' => 'invalid-date',
            'note' => str_repeat('a', 1001) // Too long
        ]);
        
        $response->assertSessionHasErrors(['type', 'category', 'amount', 'date', 'note']);
    }

    public function test_transactions_can_be_filtered()
    {
        $this->actingAs($this->user);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'income',
            'category' => 'Salary',
            'date' => now()
        ]);
        
        Transaction::factory()->for($this->user)->create([
            'type' => 'expense',
            'category' => 'Groceries',
            'date' => now()
        ]);
        
        $response = $this->get(route('transactions.index', ['type' => 'income']));
        
        $response->assertStatus(200);
        // Additional assertions could be added to verify filtering logic
    }
} 