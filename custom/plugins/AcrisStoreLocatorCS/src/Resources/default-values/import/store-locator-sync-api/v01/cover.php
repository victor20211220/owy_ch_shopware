if(!is_array($value)) {
    return;
}
$cover = $value;

if(!array_key_exists('mediaId', $cover) && !array_key_exists('id', $cover)) {
    if(array_key_exists('fileName', $cover)){
        $mediaFileName = pathinfo($cover['fileName'], PATHINFO_FILENAME);
        $mediaId = null;

        $mediaRepository = $this->container->get("media.repository");
        $searchResult = $mediaRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter("fileName",
            $mediaFileName)), $context);
        $mediaId = $searchResult->firstId();
        if (!empty($mediaId)) {
            $value['mediaId'] = $mediaId;
        }
    }
}
