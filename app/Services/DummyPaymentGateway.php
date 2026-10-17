<?php
/**
 * Dummy Payment Gateway
 *
 * Approves every charge. For development and demos only.
 */

require_once __DIR__ . '/PaymentGateway.php';

class DummyPaymentGateway implements PaymentGateway
{
    public function charge(array $tenant, array $invoice)
    {
        return [
            'success' => true,
            'transaction_id' => 'dummy_' . bin2hex(random_bytes(8)),
            'message' => 'Approved (dummy gateway)',
        ];
    }

    public function name()
    {
        return 'dummy';
    }
}
