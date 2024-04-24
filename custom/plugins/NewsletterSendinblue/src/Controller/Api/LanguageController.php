<?php

namespace NewsletterSendinblue\Controller\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class LanguageController extends AbstractController
{
    /**
    * @Route("/api/v{version}/sendinblue/languages", name="api.v.action.sendinblue.getLanguages", methods={"GET"})
    * @Route("/api/sendinblue/languages", name="api.action.sendinblue.getLanguages", methods={"GET"})
    */
    public function getLanguagesAction(): JsonResponse
    {
        $response = [];
        try {
            /** @var EntityRepository $repository */
            $repository = $this->container->get('language.repository');
            $criteria = new Criteria();
            $criteria->addAssociation('locale');
            $languages = $repository->search($criteria, Context::createDefaultContext());

            $response['success'] = true;
            $response['data'] = $this->prepareEntityAttributes($languages);

        } catch (\Exception $exception) {
            $response['success'] = false;
            $response['data'] = $exception->getMessage();
        }

        return new JsonResponse($response);
    }

    private function prepareEntityAttributes(EntityCollection $entityCollection) : array
    {
        $attributes = [];

        /** @var LanguageEntity $entity */
        foreach ($entityCollection->getElements() as $key => $entity) {
            $attributes[$key]['id'] = $entity->getId();
            $attributes[$key]['name'] = $entity->getName();
            $attributes[$key]['localeCode'] = $entity->getLocale()->getCode();
            $attributes[$key]['localeName'] = $entity->getLocale()->getName();
            $attributes[$key]['default'] = false;
        }

        return $attributes;
    }
}
