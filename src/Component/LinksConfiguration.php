<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 7/16/18
 * Time: 11:43 AM
 */

namespace Component;


class LinksConfiguration
{

    private $config = 'LOCAL';
    private $localTagsUrl = 'http://spartan-tags:8888';
    private $localNutritionPlansUrl = 'http://spartan-nutrition-plans:8888';
    private $localWorkoutPlansUrl = 'http://spartan-workout-plans:8888';
    private $onlineTagsUrl = '56.43.214.09';
    private $onlineNutritionPlansUrl = '324.67.98.12';
    private $onlineWorkoutPlansUrl = '111.234.566.09';

    public function __construct()
    {
    }

    /**
     * Get urls
     *
     * @return array
     */
    public function getUrls():array {

        if($this->config == 'LOCAL'){
            return [
                $this->localTagsUrl,
                $this->localNutritionPlansUrl,
                $this->localWorkoutPlansUrl
            ];
        }else {
            return [
                $this->onlineTagsUrl,
                $this->onlineNutritionPlansUrl,
                $this->onlineWorkoutPlansUrl
            ];
        }
    }

}