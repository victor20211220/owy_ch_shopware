<?php


namespace OwyChTheme\Subscriber;


use OwyChTheme\Struct\NavigationTree;


use Shopware\Storefront\Pagelet\Header\HeaderPageletLoadedEvent;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\Category\Event\NavigationLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;


class PageLoadedEventSubscriber implements EventSubscriberInterface


{
    private $categoryRepository;

    public function __construct(
        EntityRepository $categoryRepository,

    ) {

        $this->categoryRepository = $categoryRepository;
    }

public static function getSubscribedEvents()
{

    return [
        HeaderPageletLoadedEvent::class => 'onPageLoadedEvent',
        


    ];


}


public function onPageLoadedEvent(HeaderPageletLoadedEvent $event)
{
    $currentActivePage = $event->getPagelet()->getNavigation()->getActive();
    $navigationTree = $event->getPagelet()->getNavigation()->getChildren($currentActivePage->getId());
    if ($event->getRequest()->getRequestUri() !== '/') {
        $event->getPagelet()->addExtension('navigationTree', new NavigationTree($navigationTree));
    }



}


}



