<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/30/18
 * Time: 4:56 PM
 */

namespace Model\Entity;


use Model\Contract\HasId;

class Plans implements HasId
{

    private $id;
    private $type;
    private $collection;


    /**
     * @return mixed
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param mixed $collection
     */
    public function setCollection(PlansCollection $collection): void
    {
        $this->collection = $collection;
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