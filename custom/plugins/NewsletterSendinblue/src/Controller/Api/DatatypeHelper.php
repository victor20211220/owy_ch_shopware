<?php

namespace NewsletterSendinblue\Controller\Api;

use NewsletterSendinblue\Model\Field;

class DatatypeHelper
{
    public static function convertToSendinblueDatatype($datatype) :string
    {
        switch ($datatype) {
            case 'bool':
                $correctDataType = Field::DATATYPE_BOOLEAN;
                break;
            case 'float':
                $correctDataType = Field::DATATYPE_FLOAT;
                break;
            case 'int':
                $correctDataType = Field::DATATYPE_INTEGER;
                break;
            case 'datetime':
                $correctDataType = Field::DATATYPE_DATE;
                break;
            default:
                $correctDataType = Field::DATATYPE_STRING;
        }

        return $correctDataType;
    }
}
