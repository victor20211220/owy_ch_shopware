<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Component;

class XmlBuilder
{
    private const EXPORT_DIR = __DIR__ . '/../../../../../files/erp/export/';

    public function build(array $order): void
    {
        file_put_contents(
            sprintf(
                self::EXPORT_DIR . OrderSchemeProvider::FILE_NAME_SCHEME,
                $order['order_number']
            ),
            sprintf(
                OrderSchemeProvider::XML_BODY_SCHEME,
                $this->getOrderHeader($order),
                $this->getOrderPositions($order['details'], $order['shipping_date_earliest'])
            )
        );
    }

    private function getOrderHeader(array $order): string
    {
        return sprintf(
            OrderSchemeProvider::ORDER_HEAD_SCHEME,
            $order['order_number'],
            6 === \strlen($order['customer_number']) ? $order['customer_number'] : '099999',
            (new \DateTime($order['order_date_time']))->format('Y-m-d H:i:s'),
            $order['email'],
            $order['billing']['phone_number'],
            $order['billing']['vat_id'],
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['billing']['salutation']),
            $this->hexUmlauts($order['billing']['title']),
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['billing']['first_name']),
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['billing']['last_name']),
            $this->hexUmlauts($order['billing']['company']),
            $this->hexUmlauts($order['billing']['department']),
            $this->hexUmlauts($order['billing']['additional_address_line1']),
            $this->hexUmlauts($order['billing']['additional_address_line2']),
            $this->hexUmlauts($order['billing']['street']),
            $order['billing']['country_code'],
            $order['billing']['zipcode'],
            $this->hexUmlauts($order['billing']['city']),
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['shipping']['salutation']),
            $this->hexUmlauts($order['shipping']['title']),
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['shipping']['first_name']),
            6 === \strlen($order['customer_number']) ? '' : $this->hexUmlauts($order['shipping']['last_name']),
            $this->hexUmlauts($order['shipping']['company']),
            '',
            $this->hexUmlauts($order['shipping']['additional_address_line1']),
            $this->hexUmlauts($order['shipping']['additional_address_line2']),
            $this->hexUmlauts($order['shipping']['street']),
            $order['shipping']['country_code'] ?? '',
            $order['shipping']['zipcode'] ?? '',
            $this->hexUmlauts($order['shipping']['city']),
            (int) $order['shipping']['differsFromBilling'],
            $order['order_number']
        );
    }

    private function getOrderPositions(array $details, string $earliestShippingDate): string
    {
        $orderPosition = '';
        foreach ($details as $key => $detail) {
            $orderPosition .= sprintf(
                OrderSchemeProvider::POSITION_SCHEME,
                $key + 1,
                $detail['product_number'],
                $detail['quantity'],
                (new \DateTime($earliestShippingDate))->format('d.m.Y'),
                $this->forceDecimalNumber((float) $detail['weight'], 6)
            );
        }

        return $orderPosition;
    }

    private function hexUmlauts(?string $value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        return str_replace(
            ['&', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['&#x26;', '&#xe4;', '&#xf6;', '&#xfc;', '&#xc4;', '&#xd6;', '&#xdc;', '&#xdf;'],
            $value
        );
    }

    private function forceDecimalNumber(float $digitNumber, int $digits = 4): string
    {
        return number_format(round($digitNumber, $digits), $digits, '.', '');
    }
}
