<?php
/**
 * Resolves the configured payment gateway
 */

class PaymentGatewayFactory
{
    public static function make()
    {
        $gateways = [
            'dummy' => 'DummyPaymentGateway',
        ];

        $name = config('payment_gateway', 'dummy');
        if (!isset($gateways[$name])) {
            throw new RuntimeException("Unknown payment gateway: {$name}");
        }

        require_once __DIR__ . '/PaymentGateway.php';
        $class = $gateways[$name];
        return new $class();
    }
}
