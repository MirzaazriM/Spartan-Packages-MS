<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 3:05 PM
 */
namespace Model\Service\Facade;


use Model\Entity\Package;
use Model\Entity\PackageCollection;
use Model\Mapper\PackagesMapper;

class GetPackagesFacade
{
    
    private $lang;
    private $app;
    private $like;
    private $state;
    private $type;
    private $packagesMapper;
    private $configuration;

    
    public function __construct(string $lang, string $app = null, string $like = null, string $state, string $type = null, PackagesMapper $packagesMapper) {
        $this->lang = $lang;
        $this->app = $app;
        $this->like = $like;
        $this->state = $state;
        $this->type = $type;
        $this->packagesMapper = $packagesMapper;
        $this->configuration = $packagesMapper->getConfiguration();
    }


    /**
     * Handle packages
     *
     * @return PackageCollection|null
     */
    public function handlePackages() {
        // set data variable
        $data = null;

        // Calling By App
        if(!empty($this->app)){
            $data = $this->getPackagesByApp();
        }
        // Calling by Search
        else if(!empty($this->like)){
            $data = $this->searchPackages();
        }
        // Calling by State
        else{
            $data = $this->getPackages();
        }

        // return data
        return $data;
    }


    /**
     * Get packages
     *
     * @return PackageCollection
     */
    public function getPackages() {
        // create entity and set its values
        $entity = new Package();
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // call mapper for data
        $collection = $this->packagesMapper->getPackages($entity);

        // return data
        return $collection;
    }


    /**
     * Get packages by app - core
     *
     * @return PackageCollection
     */
    public function getPackagesByApp() {
        // create entity and set its values
        $entity = new Package();
        $entity->setApp($this->app);
        $entity->setLang($this->lang);
        $entity->setState($this->state);
        $entity->setType($this->type);

        // call mapper for data
        $data = $this->packagesMapper->getPackagesByApp($entity);

        // return data
        return $data;
    }


    /**
     * Search packages
     *
     * @return PackageCollection
     */
    public function searchPackages():PackageCollection {
        // create entity and set its values
        $entity = new Package();
        $entity->setName($this->like);
        $entity->setLang($this->lang);
        $entity->setState($this->state);

        // call mapper for data
        $data = $this->packagesMapper->searchPackages($entity);

        // return data
        return $data;
    }
    
}