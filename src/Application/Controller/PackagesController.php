<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 6:19 PM
 */

namespace Application\Controller;

use Model\Entity\Names;
use Model\Entity\NamesCollection;
use Model\Entity\ResponseBootstrap;
use Model\Service\PackagesService;
use Symfony\Component\HttpFoundation\Request;

class PackagesController
{

    private $packagesService;

    public function __construct(PackagesService $packagesService)
    {
        $this->packagesService = $packagesService;
    }


    /**
     * Get package by id
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($id) && isset($lang) && isset($state)){
            return $this->packagesService->getPackage($id, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get packages list
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getList(Request $request):ResponseBootstrap {
        // get data
        $from = $request->get('from');
        $limit = $request->get('limit');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if parameters are present
        if(isset($from) && isset($limit)){ // && isset($state)
            return $this->packagesService->getListOfPackages($from, $limit, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get packages by parametars
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPackages(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $like = $request->get('like');
        $state = $request->get('state');
        $type = $request->get('type');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($lang) && !empty($state)){
            return $this->packagesService->getPackages($lang, $app, $like, $state, $type);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get data by app
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function getCore(Request $request):ResponseBootstrap {
        // get data
        $lang = $request->get('lang');
        $app = $request->get('app');
        $state = $request->get('state');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($lang) && !empty($state) && !empty($app)){
            return $this->packagesService->getOnlyPackages($lang, $app, $state);
        }else{
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Get packages by ids
     *
     * @param Request $request
     * @return ResponseBootstrap
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getIds(Request $request):ResponseBootstrap {
        // get data
        $ids = $request->get('ids');
        $lang = $request->get('lang');
        $state = $request->get('state');

        // comma separated string to array
        $ids = explode(',', $ids);

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(!empty($ids) && !empty($lang) && !empty($state)){
            return $this->packagesService->getPackagesById($ids, $lang, $state);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Delete package
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function delete(Request $request):ResponseBootstrap {
        // get data
        $id = $request->get('id');

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->packagesService->deletePackage($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Release package
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function postRelease(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];

        // create response object in case of failure
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id)){
            return $this->packagesService->releasePackage($id);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Add package
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function post(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $names = $data['names'];
        $tags = $data['tags'];
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $plans = $data['plans'];
        $workoutPlans = $plans['workout_plans'];
        $recepiePlans = $plans['recepie_plans'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setLang($name['language']);
            $temp->setDescription($name['description']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($namesCollection) && isset($workoutPlans) && isset($recepiePlans) && isset($tags) && isset($thumbnail) && isset($rawName)){
            return $this->packagesService->createPackage($namesCollection, $workoutPlans, $recepiePlans, $tags, $thumbnail, $rawName);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }


    /**
     * Edit package
     *
     * @param Request $request
     * @return ResponseBootstrap
     */
    public function put(Request $request):ResponseBootstrap {
        // get data
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $names = $data['names'];
        $tags = $data['tags'];
        $thumbnail = $data['thumbnail'];
        $rawName = $data['raw_name'];
        $plans = $data['plans'];
        $workoutPlans = $plans['workout_plans'];
        $recepiePlans = $plans['recepie_plans'];

        // create names collection
        $namesCollection = new NamesCollection();
        // set names into names collection
        foreach($names as $name){
            $temp = new Names();
            $temp->setName($name['name']);
            $temp->setLang($name['language']);
            $temp->setDescription($name['description']);

            $namesCollection->addEntity($temp);
        }

        // create response object
        $response = new ResponseBootstrap();

        // check if data is set
        if(isset($id) && isset($namesCollection) && isset($workoutPlans) && isset($recepiePlans) && isset($tags) && isset($thumbnail) && isset($rawName)){
            return $this->packagesService->editPackage($id, $namesCollection, $workoutPlans, $recepiePlans, $tags, $thumbnail, $rawName);
        }else {
            $response->setStatus(404);
            $response->setMessage('Bad request');
        }

        // return data
        return $response;
    }

}