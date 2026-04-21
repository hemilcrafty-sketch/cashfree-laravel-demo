<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\CashfreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_cashfree_order()
    {
        $this->mock(CashfreeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createOrder')->once()->andReturn([
                'success' => true,
                'status' => 200,
                'data' => [
                    'order_id' => 'ORD_123456',
                    'payment_session_id' => 'sess_mock_123'
                ]
            ]);
        });

        $response = $this->postJson('/api/payments/create-order', [
            'amount' => 100,
            'email' => 'test@example.com',
            'phone' => '1234567890'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('order_id', 'ORD_123456');

        $this->assertDatabaseHas('payments', [
            'order_id' => 'ORD_123456',
            'amount' => 100
        ]);
    }

    /** @test */
    public function it_can_verify_a_payment()
    {
        $payment = Payment::create([
            'order_id' => 'ORD_123',
            'amount' => 100,
            'status' => 'pending'
        ]);

        $this->mock(CashfreeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrder')->once()->andReturn([
                'success' => true,
                'status' => 200,
                'data' => [
                    'order_status' => 'PAID'
                ]
            ]);
        });

        $response = $this->getJson('/api/payments/verify/ORD_123');

        $response->assertStatus(200)
            ->assertJsonPath('payment_status', 'success');

        $this->assertEquals('success', $payment->fresh()->status);
    }

    /** @test */
    public function it_can_handle_a_valid_webhook()
    {
        $payment = Payment::create([
            'order_id' => 'ORD_WEBHOOK',
            'amount' => 500,
            'status' => 'pending'
        ]);

        $this->mock(CashfreeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifySignature')->once()->andReturn(true);
        });

        $response = $this->withHeaders(['x-webhook-signature' => 'valid_sig'])
            ->postJson('/api/payments/webhook', [
                'data' => [
                    'order' => ['order_id' => 'ORD_WEBHOOK'],
                    'payment' => [
                        'payment_status' => 'SUCCESS',
                        'cf_payment_id' => 'cf_999'
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $this->assertEquals('success', $payment->fresh()->status);
        $this->assertEquals('cf_999', $payment->fresh()->payment_id);
    }

    /** @test */
    public function it_rejects_invalid_webhook_signature()
    {
        $this->mock(CashfreeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifySignature')->once()->andReturn(false);
        });

        $response = $this->withHeaders(['x-webhook-signature' => 'invalid'])
            ->postJson('/api/payments/webhook', [
                'data' => [
                    'order' => ['order_id' => 'ORD_BAD']
                ]
            ]);

        $response->assertStatus(400);
    }
}
