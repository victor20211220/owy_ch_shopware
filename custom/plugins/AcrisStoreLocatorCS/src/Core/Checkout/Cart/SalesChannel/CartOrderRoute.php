<?php declare(strict_types=1);

namespace Acris\StoreLocator\Core\Checkout\Cart\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRouteResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\Annotation\Route;


#[Route(defaults: ['_routeScope' => ['store-api']])]
class CartOrderRoute extends \Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRoute
{
    public function __construct(
        private readonly AbstractCartOrderRoute $cartOrderRoute
    ) {
    }

    public function getDecorated(): AbstractCartOrderRoute
    {
        return $this->cartOrderRoute;
    }

    #[Route(path: '/store-api/v{version}/checkout/order', name: 'store-api.checkout.cart.order', methods: ['POST'])]
    public function order(Cart $cart, SalesChannelContext $context, ?RequestDataBag $data = null): CartOrderRouteResponse
    {
        if ($data->has('acrisStoreLocatorStore')) {
            $context->getContext()->addExtension('AcrisStoreLocatorSelection', new ArrayStruct(['value' => $data->get('acrisStoreLocatorStore')]));
        }

        return $this->cartOrderRoute->order($cart, $context, $data);
    }
}
