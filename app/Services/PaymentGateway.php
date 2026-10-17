<?php
/**
 * Payment Gateway contract
 *
 * Implement this interface for a real provider (Stripe, PayTabs, PayPal...)
 * and select it with PAYMENT_GATEWAY in .env (see PaymentGatewayFactory).
 */

interface PaymentGateway
{
    /**
     * Charge a tenant for an invoice.
     *
     * @return array ['success' => bool, 'transaction_id' => string|null, 'message' => string]
     */
    public function charge(array $tenant, array $invoice);

    /**
     * Short identifier stored on payment records (e.g. "stripe")
     */
    public function name();
}
