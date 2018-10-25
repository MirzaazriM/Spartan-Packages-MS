<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 6:30 PM
 */

namespace Model\Entity;


class Shared
{

    private $response = [];

    /**
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * @param array $response
     */
    public function setResponse(array $response): void
    {
        $this->response = $response;
    }


}