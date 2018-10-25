<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 6:19 PM
 */

namespace Model\Service;


use Component\LinksConfiguration;
use Model\Core\Helper\Monolog\MonologSender;
use Model\Entity\NamesCollection;
use Model\Entity\Package;
use Model\Entity\ResponseBootstrap;
use Model\Mapper\PackagesMapper;
use Model\Service\Facade\GetPackagesFacade;

class PackagesService extends LinksConfiguration
{

    private $packagesMapper;
    private $configuration;
    private $monologHelper;

    public function __construct(PackagesMapper $packagesMapper)
    {
        $this->packagesMapper = $packagesMapper;
        $this->configuration = $packagesMapper->getConfiguration();
        $this->monologHelper = new MonologSender();
    }


    /**
     * Get package service
     *
     * @param int $id
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPackage(int $id, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setId($id);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response from database
            $res = $this->packagesMapper->getPackage($entity);
            $id = $res->getId();

            // get tags ids
            $tagIds = $res->getTags();
            // call tags MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
            $tags = $result->getBody()->getContents();

            // get recepies ids
            $recepiePlansIds = $res->getRecepiesIds();
            // call recepies MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['nutritionplans_url'] . '/nutritionplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$recepiePlansIds, []);
            $recepiesData = $result->getBody()->getContents();

            // get workout ids
            $workoutPlansIds = $res->getWorkoutIds();
            // call workouts MS for data
            $client = new \GuzzleHttp\Client();
            $result = $client->request('GET', $this->configuration['workoutplans_url'] . '/workoutplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$workoutPlansIds, []);
            $workoutData = $result->getBody()->getContents();

            // check data and set response
            if(isset($id)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData([
                    'id' => $res->getId(),
                    'thumbnail' => $res->getThumbnail(),
                    'name' => $res->getName(),
                    'raw_name' => $res->getRawName(),
                    'description' => $res->getDescription(),
                    'sku' => $res->getApp(),
                    // 'state' => $res->getState(),
                    'version' => $res->getVersion(),
                    'tags' => json_decode($tags),
                    'nutrition_plans' => json_decode($recepiesData),
                    'training_plans' => json_decode($workoutData)
                ]);
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get package service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get list of packages
     *
     * @param int $from
     * @param int $limit
     * @return ResponseBootstrap
     */
    public function getListOfPackages(int $from, int $limit, string $state = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setFrom($from);
            $entity->setLimit($limit);
            $entity->setState($state);

            // call mapper for data
            $data = $this->packagesMapper->getList($entity);

            // set response according to data content
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get packages list service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get packages service
     *
     * @param string $lang
     * @param string|null $app
     * @param string|null $like
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPackages(string $lang, string $app = null, string $like = null, string $state, string $type = null):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create facade and call its functions for data
            $facade = new GetPackagesFacade($lang, $app, $like, $state, $type, $this->packagesMapper);
            $res = $facade->handlePackages();

            // gettype($res) === 'object'

            // convert data to array for appropriate response
            $data = [];

            for($i = 0; $i < count($res); $i++){
                $data[$i]['id'] = $res[$i]->getId();
                $data[$i]['name'] = $res[$i]->getName();
                $data[$i]['raw_name'] = $res[$i]->getRawName();
                $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                $data[$i]['description'] = $res[$i]->getDescription();
                $data[$i]['sku'] = $res[$i]->getApp();
                $data[$i]['version'] = $res[$i]->getVersion();
                // $data[$i]['state'] = $res[$i]->getState();

                // get tags ids
                $tagIds = $res[$i]->getTags();
                // call tags MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                $tags = $result->getBody()->getContents();

                $data[$i]['tags'] = json_decode($tags);

                // get recepies plans ids
                $recepiePlansIds = $res[$i]->getRecepiesIds();
                // call recepies plans MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['nutritionplans_url'] . '/nutritionplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$recepiePlansIds, []);
                $recepiesData = $result->getBody()->getContents();

                $data[$i]['nutrition_plans'] = json_decode($recepiesData);

                // get workout plans ids
                $workoutPlansIds = $res[$i]->getWorkoutIds();
                // call workouts plans MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['workoutplans_url'] . '/workoutplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$workoutPlansIds, []);
                $workoutData = $result->getBody()->getContents();

                $data[$i]['training_plans'] = json_decode($workoutData);
            }

            // check data and set response
            if($res->getStatusCode() == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get packages service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get only packages core data
     *
     * @param string $lang
     * @param string $app
     * @param string $state
     * @return ResponseBootstrap
     */
    public function getOnlyPackages(string $lang, string $app, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setApp($app);
            $entity->setLang($lang);
            $entity->setState($state);

            // get data from database
            $data = $this->packagesMapper->getOnlyPackages($entity);

            // check data and set response
            if(!empty($data)){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get packages by app service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Get packages by ids
     *
     * @param array $ids
     * @param string $lang
     * @param string $state
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPackagesById(array $ids, string $lang, string $state):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setIds($ids);
            $entity->setLang($lang);
            $entity->setState($state);

            // get response from database
            $res = $this->packagesMapper->getPackagesById($entity);

            // convert data to array for appropriate response
            $data = [];

            for($i = 0; $i < count($res); $i++){
                $data[$i]['id'] = $res[$i]->getId();
                $data[$i]['name'] = $res[$i]->getName();
                $data[$i]['thumbnail'] = $res[$i]->getThumbnail();
                $data[$i]['description'] = $res[$i]->getDescription();
                $data[$i]['sku'] = $res[$i]->getApp();
                $data[$i]['version'] = $res[$i]->getVersion();
                // $data[$i]['state'] = $res[$i]->getState();

                // get tags ids
                $tagIds = $res[$i]->getTags();
                // call tags MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['tags_url'] . '/tags/ids?lang=' .$lang. '&state=R' . '&ids=' .$tagIds, []);
                $tags = $result->getBody()->getContents();

                $data[$i]['tags'] = json_decode($tags);

                // get recepie plans ids
                $recepiePlansIds = $res[$i]->getRecepiesIds();
                // call recepie plans MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['nutritionplans_url'] . '/nutritionplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$recepiePlansIds, []);
                $recepiesData = $result->getBody()->getContents();

                $data[$i]['nutrition_plans'] = json_decode($recepiesData);

                // get workout plans ids
                $workoutPlansIds = $res[$i]->getWorkoutIds();
                // call workout plans MS for data
                $client = new \GuzzleHttp\Client();
                $result = $client->request('GET', $this->configuration['workoutplans_url'] . '/workoutplans/ids?lang=' .$lang. '&state=R' . '&ids=' .$workoutPlansIds, []);
                $workoutData = $result->getBody()->getContents();

                $data[$i]['training_plans'] = json_decode($workoutData);
            }

            // check data and set response
            if($res->getStatusCode() == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
                $response->setData(
                    $data
                );
            }else {
                $response->setStatus(204);
                $response->setMessage('No content');
            }

            // return data
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Get packages by ids service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Delete package
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function deletePackage(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setId($id);

            // get response from database
            $res = $this->packagesMapper->deletePackage($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Delete package service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Release package service
     *
     * @param int $id
     * @return ResponseBootstrap
     */
    public function releasePackage(int $id):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setId($id);

            // get response from database
            $res = $this->packagesMapper->releasePackage($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e) {
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Release package service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Add package
     *
     * @param NamesCollection $names
     * @param array $workoutPlans
     * @param array $recepiePlans
     * @param array $tags
     * @param string $thumbnail
     * @param string $rawName
     * @return ResponseBootstrap
     */
    public function createPackage(NamesCollection $names, array $workoutPlans, array $recepiePlans, array $tags, string $thumbnail, string $rawName):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setNames($names);
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setName($rawName);
            $entity->setWorkoutIds($workoutPlans);
            $entity->setRecepiesIds($recepiePlans);

            // get response from database
            $res = $this->packagesMapper->createPackage($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Create package service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }


    /**
     * Edit package
     *
     * @param int $id
     * @param NamesCollection $names
     * @param array $workoutPlans
     * @param array $recepiePlans
     * @param array $tags
     * @param string $thumbnail
     * @param string $rawName
     * @return ResponseBootstrap
     */
    public function editPackage(int $id, NamesCollection $names, array $workoutPlans, array $recepiePlans, array $tags, string $thumbnail, string $rawName):ResponseBootstrap {

        try {
            // create response object
            $response = new ResponseBootstrap();

            // create entity and set its values
            $entity = new Package();
            $entity->setId($id);
            $entity->setNames($names);
            $entity->setTags($tags);
            $entity->setThumbnail($thumbnail);
            $entity->setName($rawName);
            $entity->setWorkoutIds($workoutPlans);
            $entity->setRecepiesIds($recepiePlans);

            // get response from database
            $res = $this->packagesMapper->editPackage($entity)->getResponse();

            // check data and set response
            if($res[0] == 200){
                $response->setStatus(200);
                $response->setMessage('Success');
            }else {
                $response->setStatus(304);
                $response->setMessage('Not modified');
            }

            // return response
            return $response;

        }catch (\Exception $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, 1000, "Edit package service: " . $e->getMessage());

            $response->setStatus(404);
            $response->setMessage('Invalid data');
            return $response;
        }
    }

}