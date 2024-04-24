<?php

namespace Acris\StoreLocator\Administration\Controller;

use Acris\StoreLocator\Components\Geocoder;
use Acris\StoreLocator\Custom\StoreLocatorEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


#[Route(defaults: ['_routeScope' => ['api']])]
class AcrisGetCoords extends AbstractController
{
    private ?string $errors;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly Geocoder $geocoder,
        private readonly EntityRepository $storeLocatorRepository)
    {
        $this->errors = null;
    }

    #[Route(path: '/api/_action/acris-get-coords', name: 'api.action.acris-get-coords', methods: ['GET'])]
    public function getCoords(Request $request, Context $context): JsonResponse
    {
        $street = $this->prepareValue($request->get('street'));
        $city = $this->prepareValue($request->get('city'));
        $country = $this->prepareValue($request->get('country'));
        $zipCode = $this->prepareValue($request->get('zipcode'));

        $address = $zipCode . "%20" . $street . "%20" . $city . "%20" . $country;

        $googleApiKey = $this->systemConfigService->get('AcrisStoreLocatorCS.config.storeLocatorGoogleApiKey');
        $geocodeData = $this->geocoder->geocode($address, $googleApiKey);

        return new JsonResponse($geocodeData);
    }

    #[Route(path: '/api/_action/acris-calc-and-save-coords', name: 'api.action.acris-calc-and-save-coords', methods: ['GET'])]
    public function calcAndSaveCoords(Request $request, Context $context): JsonResponse
    {
        $offset = $request->get("offset");
        $limit = $request->get("limit");
        $total = $request->get("total");
        $this->errors = $request->get("errors");



        if ($total == 0) {
            return new JsonResponse(['success' => false, 'error' => 'no data']);
        }

        if (($offset + $limit) > $total) {
            $limit = $total - $offset;
        }

        $criteria = new Criteria();
        $criteria->addAssociation('country');
        $criteria
            ->setOffset($offset)
            ->setLimit($limit);

        $storeResult = $this->storeLocatorRepository->search($criteria, $context);

        if ($storeResult->count() > 0 && $storeResult->first()) {
            $googleApiKey = $this->systemConfigService->get('AcrisStoreLocatorCS.config.storeLocatorGoogleApiKey');

            if ($googleApiKey && $offset < $total) {
                /** @var StoreLocatorEntity $store */
                foreach ($storeResult->getElements() as $store) {
                    $street = $this->prepareValue((string)$store->getStreet());
                    $zipCode = $this->prepareValue((string)$store->getZipcode());
                    $city = $this->prepareValue((string)$store->getCity());
                    $country = $this->prepareValue((string)$store->getCountry()->getName());
                    $address = $zipCode . "%20" . $street . "%20" . $city . "%20" . $country;
                    $geocodeData = $this->geocoder->geocode($address, $googleApiKey);

                    if ($geocodeData === 'no permission' || !is_array($geocodeData)) {
                        if (empty($this->errors)) {
                            $this->errors .= $store->getTranslation('name').', ';
                        } else {
                            $this->errors .= $store->getTranslation('name');
                        }
                    } else {
                        if ($this->systemConfigService->get('AcrisStoreLocatorCS.config.overwriteExistingData') || (empty($store->getLatitude()) || empty($store->getLongitude()))) {

                            $data = [
                                'id' => $store->getId(),
                                'longitude' => (string)$geocodeData[1],
                                'latitude' => (string)$geocodeData[0]
                            ];

                            $this->storeLocatorRepository->upsert([$data], $context);
                        }
                    }

                    $offset++;
                }

                if ($offset == $total) {
                   return new JsonResponse(['success' => true, 'offset' => 'reached', 'errors' => trim($this->errors)]);
                }
                return new JsonResponse(['success' => true, 'offset' => $offset, 'limit' => (int)$limit, 'total' => (int)$total, 'errors' => trim($this->errors)]);
            }
            return new JsonResponse(['success' => false, 'error' => 'no permission']);
        }
        return new JsonResponse(['success' => false, 'error' => 'no data']);
    }

    private function prepareValue(string $value): string
    {
        $value = str_replace("%", "%25", $value);
        $value = str_replace(" ", "%20", $value);
        $value = str_replace("\"", "%22", $value);
        $value = str_replace("+", "%2B", $value);
        $value = str_replace(",", "%2C", $value);
        $value = str_replace("<", "%3C", $value);
        $value = str_replace(">", "%3E", $value);
        $value = str_replace("#", "%23", $value);
        $value = str_replace("|", "%7C", $value);

        return $value;
    }
}
