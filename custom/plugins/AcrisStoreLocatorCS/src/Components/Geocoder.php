<?php declare(strict_types=1);

namespace Acris\StoreLocator\Components;


use Acris\StoreLocator\AcrisStoreLocatorCS as AcrisStoreLocator;


class Geocoder
{
    public $geocodeErrors;

    // function to geocode address, it will return false if unable to geocode address
    public function geocode($address, $key){

        // google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?&address={$address}&sensor=true&key={$key}";

        // get the json response
        $resp_json = file_get_contents($url);

        // decode the json
        $resp = json_decode($resp_json, true);

        // response status will be 'OK', if able to geocode given address
        if($resp['status']=='OK'){

            // get the important data
            $lati = $resp['results'][0]['geometry']['location']['lat'];
            $longi = $resp['results'][0]['geometry']['location']['lng'];
            $formatted_address = $resp['results'][0]['formatted_address'];

            // verify if data is complete
            if(($lati != null || $lati != "") && ($longi != null || $longi != "") && ($formatted_address != null || $formatted_address != "")){
                // put the data in the array
                $data_arr = array();

                array_push(
                    $data_arr,
                    $lati,
                    $longi,
                    $formatted_address
                );

                $data_arr['success'] = true;
                return $data_arr;

            }
            else {
                $this->geocodeErrors = "no data";
               return $this->geocodeErrors;
            }
        } else {
            if (!empty($resp) && array_key_exists('error_message', $resp) && !empty($resp['error_message'])) {
                $this->geocodeErrors = $resp['error_message'];
            } elseif (!empty($resp) && array_key_exists('status', $resp) && !empty($resp['status']) && $resp['status'] === 'ZERO_RESULTS') {
                $this->geocodeErrors = "zeroResult".$address;
            } else {
                $this->geocodeErrors = "no permission";
            }

            return $this->geocodeErrors;
        }
    }
}
