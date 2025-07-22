<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_debts_index()
    {
        $this->actingAs($this->user);
        
        Debt::factory()->for($this->user)->create(['status' => 'active']);
        Debt::factory()->for($this->user)->create(['status' => 'paid']);
        
        $response = $this->get(route('debts.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Debts'));
    }

    public function test_user_can_view_debt_details()
    {
        $this->markTestSkipped('DebtDetails.vue page not yet implemented');
        
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create();
        
        $response = $this->get(route('debts.show', $debt));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('DebtDetails'));
    }

    public function test_user_cannot_access_other_users_debt()
    {
        $this->actingAs($this->user);
        
        $otherUser = User::factory()->create();
        $debt = Debt::factory()->for($otherUser)->create();
        
        $response = $this->get(route('debts.show', $debt));
        
        $response->assertStatus(403);
    }

    public function test_user_can_record_payment_on_debt()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'amount' => 1000.00,
            'interest_rate' => 5.00
        ]);
        
        $paymentData = [
            'amount' => 200.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Bank Transfer',
            'note' => 'Monthly payment'
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('debt_payments', [
            'debt_id' => $debt->id,
            'user_id' => $this->user->id,
            'amount' => 200.00,
            'payment_method' => 'Bank Transfer',
            'note' => 'Monthly payment'
        ]);
        
        // Check that debt remaining balance was updated
        $debt->refresh();
        $this->assertEquals(800.00, $debt->getRemainingBalance());
    }

    public function test_payment_recording_calculates_principal_and_interest()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'amount' => 1000.00,
            'interest_rate' => 12.00 // 12% annual interest
        ]);
        
        $paymentData = [
            'amount' => 100.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Credit Card'
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertRedirect();
        
        $payment = DebtPayment::where('debt_id', $debt->id)->first();
        
        // Monthly interest rate = 12% / 12 = 1%
        // Interest amount = 1000 * 0.01 = 10.00
        // Principal amount = 100 - 10 = 90.00
        $this->assertEquals(10.00, $payment->interest_amount);
        $this->assertEquals(90.00, $payment->principal_amount);
    }

    public function test_debt_is_marked_as_paid_when_fully_paid()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'amount' => 1000.00,
            'interest_rate' => 0.00 // No interest for simplicity
        ]);
        
        $paymentData = [
            'amount' => 1000.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Lump Sum'
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Debt paid completely! 🎉');
        
        $debt->refresh();
        $this->assertEquals('paid', $debt->status);
        $this->assertNotNull($debt->paid_at);
    }

    public function test_user_can_mark_debt_as_paid()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create(['status' => 'active']);
        
        $response = $this->post(route('debts.markAsPaid', $debt));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $debt->refresh();
        $this->assertEquals('paid', $debt->status);
        $this->assertNotNull($debt->paid_at);
    }

    public function test_user_can_mark_debt_as_active()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'status' => 'paid',
            'paid_at' => now()
        ]);
        
        $response = $this->post(route('debts.markAsActive', $debt));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $debt->refresh();
        $this->assertEquals('active', $debt->status);
        $this->assertNull($debt->paid_at);
    }

    public function test_user_cannot_record_payment_on_other_users_debt()
    {
        $this->actingAs($this->user);
        
        $otherUser = User::factory()->create();
        $debt = Debt::factory()->for($otherUser)->create();
        
        $paymentData = [
            'amount' => 100.00,
            'payment_date' => now()->format('Y-m-d')
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertStatus(403);
    }

    public function test_payment_validation_rules()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create();
        
        // Test invalid amount
        $response = $this->post(route('debts.recordPayment', $debt), [
            'amount' => -100,
            'payment_date' => now()->format('Y-m-d')
        ]);
        
        $response->assertSessionHasErrors('amount');
        
        // Test missing payment date
        $response = $this->post(route('debts.recordPayment', $debt), [
            'amount' => 100
        ]);
        
        $response->assertSessionHasErrors('payment_date');
    }

    public function test_debt_payment_progress_calculation()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create([
            'amount' => 1000.00,
            'interest_rate' => 0.00
        ]);
        
        // Record a 500 payment (50% progress)
        $debt->addPayment(500.00, now()->format('Y-m-d'));
        
        $debt->refresh();
        $this->assertEquals(50.0, $debt->payment_progress);
        
        // Record another 500 payment (100% progress)
        $debt->addPayment(500.00, now()->format('Y-m-d'));
        
        $debt->refresh();
        $this->assertEquals(100.0, $debt->payment_progress);
    }

    public function test_debt_payment_methods()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create();
        
        $paymentData = [
            'amount' => 100.00,
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Credit Card'
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertRedirect();
        
        $payment = DebtPayment::where('debt_id', $debt->id)->first();
        $this->assertEquals('Credit Card', $payment->payment_method);
    }

    public function test_debt_payment_notes()
    {
        $this->actingAs($this->user);
        
        $debt = Debt::factory()->for($this->user)->create();
        
        $paymentData = [
            'amount' => 100.00,
            'payment_date' => now()->format('Y-m-d'),
            'note' => 'Extra payment from bonus'
        ];
        
        $response = $this->post(route('debts.recordPayment', $debt), $paymentData);
        
        $response->assertRedirect();
        
        $payment = DebtPayment::where('debt_id', $debt->id)->first();
        $this->assertEquals('Extra payment from bonus', $payment->note);
    }
} 