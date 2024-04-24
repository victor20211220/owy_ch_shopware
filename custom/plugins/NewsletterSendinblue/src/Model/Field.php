<?php

namespace NewsletterSendinblue\Model;


class Field
{
    const DATATYPE_INTEGER = 'Integer';
    const DATATYPE_BOOLEAN = 'Boolean';
    const DATATYPE_STRING = 'String';
    const DATATYPE_DATE = 'Date';
    const DATATYPE_ARRAY = 'Array';
    const DATATYPE_OBJECT = 'Object';
    const DATATYPE_BINARY = 'Binary';
    const DATATYPE_FLOAT = 'Float';

    const FIELD_ID = 'id';
    const FIELD_NAME = 'name';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_TYPE = 'type';

    private $id;
    private $name;
    private $description;
    private $type;

    /**
     * Field constructor.
     * @param $id
     * @param string $type
     * @param string $name
     * @param string $description
     */
    public function __construct($id, $type = Field::DATATYPE_STRING, $name = null, $description = '')
    {
        $this->id = $id;
        $this->type = $type;
        if (is_null($name)) {
            $this->setName($id);
        } else {
            $this->name = $name;
        }
        $this->description = $description;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        $this->name = implode(' ', $ret);
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }
}
