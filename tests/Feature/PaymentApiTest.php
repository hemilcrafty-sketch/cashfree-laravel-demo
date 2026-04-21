<?php

namespace Tests\Feature;

use App\Models\Order;
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
                'data' => new class {
                    public function getPaymentSessionId() { return 'sess_mock_123'; }
                }
            ]);
        });

        $response = $this->post('/pay', [
            'amount' => 100,
            'customer_email' => 'test@example.com',
            'customer_phone' => '1234567890'
        ]);

        $response->assertStatus(302); // Redirect to Cashfree

        $this->assertDatabaseHas('orders', [
            'amount' => 100,
            'customer_email' => 'test@example.com'
        ]);
    }

    /** @test */
    public function it_can_verify_a_payment()
    {
        $order = Order::create([
            'order_id' => 'ORD_123',
            'amount' => 100,
            'status' => 'pending',
            'customer_email' => 'test@example.com',
            'customer_phone' => '1234567890'
        ]);

        $this->mock(CashfreeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrder')->once()->andReturn([
                'success' => true,
                'status' => 200,
                'data' => new class {
                    public function getOrderStatus() { return 'PAID'; }
                }
            ]);
        });

        $response = $this->getJson('/api/payments/verify/ORD_123');

        $response->assertStatus(200)
            ->assertJsonPath('payment_status', 'paid');

        $this->assertEquals('paid', $order->fresh()->status);
    }
}
