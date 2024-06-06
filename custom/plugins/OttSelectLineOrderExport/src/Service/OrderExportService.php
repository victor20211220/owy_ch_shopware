<?php declare(strict_types=1);

namespace Ott\SelectLineOrderExport\Service;

use Ott\SelectLineOrderExport\Component\XmlBuilder;
use Ott\SelectLineOrderExport\Entity\OrderExportEntity;
use Ott\SelectLineOrderExport\Gateway\OrderExportGateway;

class OrderExportService
{
    private OrderExportGateway $gateway;
    private XmlBuilder $builder;

    public function __construct(OrderExportGateway $gateway)
    {
        $this->gateway = $gateway;
        $this->builder = new XmlBuilder();
    }

    public function getOrders(): array
    {
        return $this->gateway->getOrdersToExport();
    }

    public function processOrder(array $order): void
    {
        $order['billing'] = $this->gateway->getBillingAddress($order['id']);
        $order['shipping'] = $this->gateway->getShippingAddress($order['id']);
        if (
            $order['shipping']['id'] === $order['billing']['id']
            && 6 !== \strlen($order['customer_number'])
        ) {
            $order['shipping'] = [
                'salutation'               => '',
                'title'                    => '',
                'first_name'               => '',
                'last_name'                => '',
                'company'                  => '',
                'additional_address_line1' => '',
                'additional_address_line2' => '',
                'street'                   => '',
                'country_code'             => '',
                'zipcode'                  => '',
                'city'                     => '',
                'differsFromBilling'       => false,
            ];
        }

        if (6 === \strlen($order['customer_number'])) {
            $order['billing'] = [
                'salutation'               => '',
                'title'                    => '',
                'first_name'               => '',
                'last_name'                => '',
                'company'                  => '',
                'department'               => '',
                'additional_address_line1' => '',
                'additional_address_line2' => '',
                'street'                   => '',
                'country_code'             => '',
                'zipcode'                  => '',
                'city'                     => '',
                'phone_number'             => '',
                'vat_id'                   => '',
            ];
        }

        $order['details'] = $this->gateway->getOrderDetails($order['id']);

        $orderTotalPrice = 0;
        foreach ($order['details'] as $detail) {
            $orderTotalPrice = $orderTotalPrice + $detail['total_price'];
        }

        if (1000 > $orderTotalPrice) {
            $order['details'][] = [
                'quantity'       => 1,
                'product_number' => 'PP',
                'weight'         => 0,
            ];
        }

        $this->builder->build($order);

        $orderExport = new OrderExportEntity();
        $orderExport->setOrderId($order['id']);

        $this->gateway->setOrderExported($orderExport);
    }
}
