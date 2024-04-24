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
        if ($order['shipping']['id'] === $order['billing']['id']) {
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

        $order['details'] = $this->gateway->getOrderDetails($order['id']);

        $this->builder->build($order);

        $orderExport = new OrderExportEntity();
        $orderExport->setOrderId($order['id']);

        $this->gateway->setOrderExported($orderExport);
    }
}
