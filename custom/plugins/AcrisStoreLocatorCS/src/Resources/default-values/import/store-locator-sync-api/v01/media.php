if(!is_array($value)) {
    return;
}

foreach ($value as $key => $media) {
    if(!array_key_exists('mediaId', $media) && !array_key_exists('id', $media) && array_key_exists('fileName', $media)){
        $mediaFileName = pathinfo($media['fileName'], PATHINFO_FILENAME);
        $mediaId = null;

        $mediaRepository = $this->container->get("media.repository");
        $searchResult = $mediaRepository->searchIds((new Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria())->addFilter(new Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter("fileName",
            $mediaFileName)), $context);
        $mediaId = $searchResult->firstId();
        if(!empty($mediaId)) {
            $value[$key]['mediaId'] = $mediaId;
        }
    }
}
