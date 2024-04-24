<?php declare(strict_types=1);

namespace Acris\StoreLocator\Components;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Locator{

    /**
     * @var Connection
     */
    private $connection;

    private $container;


    public function __construct(Connection $connection, ContainerInterface $container)
    {
        $this->connection = $connection;
        $this->container = $container;
    }


    public function locateDataByDistance($latitude, $longitude, $distance, $handlerpoints,$context){
        if ($handlerpoints == 'Alle Handler'){

        }

        try{

            if ($handlerpoints == 'Alle Handler'){
                $sql = "SELECT lower(HEX(id)) as id, longitude, latitude,
                                    ( 6371 * acos( cos( radians( ? ) ) * cos( radians( latitude ) )
                                    * cos( radians( longitude ) - radians( ? ) ) + sin( radians( ? ) ) * sin(radians( latitude )) ) ) AS distance
                    FROM acris_store_locator WHERE active = true 
                    AND  handlerpoints = 'Handler mit Cine-Produkten'
                    OR handlerpoints = 'Handler ohne Cine-Produkte'
                    
                    and longitude is not null AND latitude is not null
                    HAVING distance < ?
                    ORDER BY distance ASC";
            }else{
                $sql = "SELECT lower(HEX(id)) as id, longitude, latitude,
                                    ( 6371 * acos( cos( radians( ? ) ) * cos( radians( latitude ) )
                                    * cos( radians( longitude ) - radians( ? ) ) + sin( radians( ? ) ) * sin(radians( latitude )) ) ) AS distance
                    FROM acris_store_locator WHERE active = true 
                    AND handlerpoints = '".$handlerpoints."' 
                    
                    and longitude is not null AND latitude is not null
                    HAVING distance < ?
                    ORDER BY distance ASC";
            }




            $sqlData = $this->connection->fetchAllAssociative($sql, [$latitude, $longitude, $latitude, $distance]);

           
            $id = [];



            foreach($sqlData as $key => $val){
                $id[] = $val["id"];
            }

            if($id != null){
                $result = $this->container->get('acris_store_locator.repository')->search((new Criteria($id))->addAssociation('country')->addAssociation('storeGroup')->addAssociation('storeGroup.icon')->addAssociation('storeGroup.media'), $context);
            }else{
                $result = "no data";
            }



        }
        catch (\Exception $e) {
            throw new \RuntimeException("could not locate data by distance", 0, $e);
        }

        return $result;
    }

}
