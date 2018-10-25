<?php
/**
 * Created by PhpStorm.
 * User: mirza
 * Date: 6/27/18
 * Time: 6:19 PM
 */

namespace Model\Mapper;

use Model\Entity\Package;
use Model\Entity\PackageCollection;
use Model\Entity\Shared;
use PDO;
use PDOException;
use Component\DataMapper;

class PackagesMapper extends DataMapper
{

    public function getConfiguration()
    {
        return $this->configuration;
    }


    /**
     * Fetch package
     *
     * @param Package $package
     * @return Package
     */
    public function getPackage(Package $package):Package {

        // create response object
        $response = new Package();

        try {
            // set database instructions
            $sql = "SELECT
                       p.id,
                       p.raw_name,
                       p.thumbnail,
                       p.state,
                       p.version,
                       pd.description,
                       pn.name,
                       pn.language,
                       ap.sku,
                       GROUP_CONCAT(DISTINCT pp.workout_plan_child) AS workout_ids,
                       GROUP_CONCAT(DISTINCT pp.recepie_plan_child) AS recepie_ids, 
                       GROUP_CONCAT(DISTINCT pt.tag) AS tags 
                    FROM package AS p
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN package_plans AS pp ON p.id = pp.package_parent
                    LEFT JOIN package_tags AS pt ON p.id = pt.package_parent
                    LEFT JOIN app_packages AS ap ON p.id = ap.package_child
                    WHERE p.id = ?
                    AND pd.language = ?
                    AND pn.language = ?
                    AND p.state = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getId(),
                $package->getLang(),
                $package->getLang(),
                $package->getState()
            ]);

            // fetch data
            $data = $statement->fetch();

            // set entity values
            if($statement->rowCount() > 0){
                $response->setId($data['id']);
                $response->setThumbnail($this->configuration['asset_link'] . $data['thumbnail']);
                $response->setName($data['name']);
                $response->setRawName($data['raw_name']);
                $response->setVersion($data['version']);
                $response->setState($data['state']);
                $response->setApp($data['sku']);
                $response->setDescription($data['description']);
                $response->setLang($data['language']);
                $response->setRecepiesIds($data['recepie_ids']);
                $response->setWorkoutIds($data['workout_ids']);
                $response->setTags($data['tags']);
            }

        }catch(PDOException $e){
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get package mapper: " . $e->getMessage());
        }

        // return data
        return $response;
    }


    /**
     * Get packages list
     *
     * @param Package $package
     * @return array
     */
    public function getList(Package $package){

        try {

            // get state
            $state = $package->getState();

            // check state and call appropriate query
            if($state === null or $state === ''){
                // set database instructions
                $sql = "SELECT
                       p.id,
                       p.thumbnail,
                       p.raw_name,
                       p.state,
                       p.version,
                       pn.name,
                       ap.sku,
                       pn.language
                    FROM package AS p 
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN app_packages AS ap ON ap.package_child = p.id
                    /* WHERE pn.language = 'en' */
                    GROUP BY p.id
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set state, from and limit as core variables
                $from = $package->getFrom();
                $limit = $package->getLimit();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                // execute query
                $statement->execute();

            }else {
                // set database instructions
                $sql = "SELECT
                       p.id,
                       p.thumbnail,
                       p.raw_name,
                       p.state,
                       p.version,
                       pn.name,
                       ap.sku,
                       pn.language
                    FROM package AS p 
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN app_packages AS ap ON ap.package_child = p.id
                    WHERE pn.language = 'en' AND p.state = :state 
                    GROUP BY p.id
                    LIMIT :from,:limit";
                // set statement
                $statement = $this->connection->prepare($sql);
                // set state, from and limit as core variables
                $from = $package->getFrom();
                $limit = $package->getLimit();
                // bind parametars
                $statement->bindParam(':from', $from, PDO::PARAM_INT);
                $statement->bindParam(':limit', $limit, PDO::PARAM_INT);
                $statement->bindParam(':state', $state);
                // execute query
                $statement->execute();

            }


            // set data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

            // create formatted data variable
            $formattedData = [];

            // loop through data and add link prefixes
            foreach($data as $item){
                $item['thumbnail'] = $this->configuration['asset_link'] . $item['thumbnail'];

                // add formatted item in new array
                array_push($formattedData, $item);
            }

        }catch (PDOException $e){
            $formattedData = [];
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get packages list mapper: " . $e->getMessage());
        }

        // return data
        return $formattedData;
    }


    /**
     * Fetch packages on state and lang
     *
     * @param Package $package
     * @return PackageCollection
     */
    public function getPackages(Package $package):PackageCollection {

        // create response object
        $packageCollection = new PackageCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       p.id,
                       p.thumbnail,
                       p.state,
                       p.version,
                       pd.description,
                       pn.name,
                       pn.language,
                       ap.sku,
                       GROUP_CONCAT(DISTINCT pp.workout_plan_child) AS workout_ids,
                       GROUP_CONCAT(DISTINCT pp.recepie_plan_child) AS recepie_ids, 
                       GROUP_CONCAT(DISTINCT pt.tag) AS tags 
                    FROM package AS p
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN package_plans AS pp ON p.id = pp.package_parent
                    LEFT JOIN package_tags AS pt ON p.id = pt.package_parent
                    LEFT JOIN app_packages AS ap ON p.id = ap.package_child
                    WHERE pd.language = ?
                    AND pn.language = ?
                    AND p.state = ?
                    GROUP BY p.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getLang(),
                $package->getLang(),
                $package->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $package = new Package();

                // set plan values
                $package->setId($row['id']);
                $package->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $package->setName($row['name']);
                $package->setVersion($row['version']);
                $package->setState($row['state']);
                $package->setApp($row['sku']);
                $package->setDescription($row['description']);
                $package->setLang($row['language']);
                $package->setRecepiesIds($row['recepie_ids']);
                $package->setWorkoutIds($row['workout_ids']);
                $package->setTags($row['tags']);

                // add package to the collection
                $packageCollection->addEntity($package);
            }

            // set response status
            if($statement->rowCount() == 0){
                $packageCollection->setStatusCode(204);
            }else {
                $packageCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $packageCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Get packages mapper: " . $e->getMessage());
        }

        // return data
        return $packageCollection;
    }


    /**
     * Fetch packages by app
     *
     * @param Package $package
     * @return PackageCollection
     */
    public function getPackagesByApp(Package $package):PackageCollection {

        // create response object
        $packageCollection = new PackageCollection();

        try {
            // set database instructions
            $sql = "SELECT
                      a.id,
                      a.name,
                      a.identifier,
                      ap.sku AS type,
                      GROUP_CONCAT(ap.package_child) AS package_ids
                    FROM apps AS a
                    LEFT JOIN app_packages AS ap ON a.id = ap.app_parent
                    WHERE a.identifier LIKE ?";
            $term = '%' . $package->getApp() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $term
            ]);

            // fetch data
            $res = $statement->fetch();

            // create and set package entity
            $packageIds = new Package();
            $packageIds->setLang($package->getLang());
            $packageIds->setState($package->getState());
            $ids = explode(',', $res['package_ids']);
            $packageIds->setIds($ids);

            // call another mapper function for data
            $data = $this->getPackagesById($packageIds);

            // check data
            if(!empty($data)){
                return $data;
            }

        }catch(PDOException $e){
            // send monolog record
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], $e->getMessage());
        }

        // return data
        return $data;
    }


    public function getOnlyPackages(Package $package){

        try {
            // set database instructions
            $sql = "SELECT 
                      GROUP_CONCAT(DISTINCT ap.package_child) AS package_ids
                   FROM apps AS a 
                   LEFT JOIN app_packages AS ap ON a.id = ap.app_parent
                   WHERE a.identifier = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getApp()
            ]);

            // extract ids
            $ids = $statement->fetch(PDO::FETCH_ASSOC)['package_ids'];

            // set database instructions for fetching packages data
            $sql = "SELECT
                      p.id,
                      p.thumbnail,
                      p.raw_name,
                      p.state,
                      p.version,
                      pd.description,
                      pn.name
                    FROM package AS p 
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    WHERE p.id IN (" . $ids . ")
                    GROUP BY p.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute();

            // fetch data
            $data = $statement->fetchAll(PDO::FETCH_ASSOC);

        }catch(PDOException $e){
            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], $e->getMessage());
        }

        // return data
        return $data;
    }


    /**
     * Search packages
     *
     * @param Package $package
     * @return PackageCollection
     */
    public function searchPackages(Package $package):PackageCollection {

        // create response object
        $packageCollection = new PackageCollection();

        try {
            // set database instructions
            $sql = "SELECT
                       p.id,
                       p.thumbnail,
                       p.state,
                       p.version,
                       pd.description,
                       pn.name,
                       pn.language,
                       ap.sku,
                       GROUP_CONCAT(DISTINCT pp.workout_plan_child) AS workout_ids,
                       GROUP_CONCAT(DISTINCT pp.recepie_plan_child) AS recepie_ids, 
                       GROUP_CONCAT(DISTINCT pt.tag) AS tags 
                    FROM package AS p
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN package_plans AS pp ON p.id = pp.package_parent
                    LEFT JOIN package_tags AS pt ON p.id = pt.package_parent
                    LEFT JOIN app_packages AS ap ON p.id = ap.package_child
                    WHERE pd.language = ?
                    AND pn.language = ?
                    AND p.state = ?
                    AND (pn.name LIKE ? OR pd.description LIKE ?)
                    GROUP BY p.id";
            $term = '%' . $package->getName() . '%';
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getLang(),
                $package->getLang(),
                $package->getState(),
                $term,
                $term
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $package = new Package();

                // set plan values
                $package->setId($row['id']);
                $package->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $package->setName($row['name']);
                $package->setVersion($row['version']);
                $package->setState($row['state']);
                $package->setDescription($row['description']);
                $package->setApp($row['sku']);
                $package->setLang($row['language']);
                $package->setRecepiesIds($row['recepie_ids']);
                $package->setWorkoutIds($row['workout_ids']);
                $package->setTags($row['tags']);

                // add package to the collection
                $packageCollection->addEntity($package);
            }

            // set response status
            if($statement->rowCount() == 0){
                $packageCollection->setStatusCode(204);
            }else {
                $packageCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $packageCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search packages mapper: " . $e->getMessage());
        }

        // return data
        return $packageCollection;
    }


    /**
     * Get packages by ids
     *
     * @param Package $package
     * @return PackageCollection
     */
    public function getPackagesById(Package $package):PackageCollection {

        // Create response object
        $packageCollection = new PackageCollection();

        // Sql Helper
        $whereIn = $this->sqlHelper->whereIn($package->getIds());

        try {
            // set database instructions
            $sql = "SELECT
                       p.id,
                       p.thumbnail,
                       p.state,
                       p.version,
                       p.raw_name,
                       pd.description,
                       pn.name,
                       pn.language,
                       ap.sku,
                       GROUP_CONCAT(DISTINCT pp.workout_plan_child) AS workout_ids,
                       GROUP_CONCAT(DISTINCT pp.recepie_plan_child) AS recepie_ids, 
                       GROUP_CONCAT(DISTINCT pt.tag) AS tags 
                    FROM package AS p
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN package_plans AS pp ON p.id = pp.package_parent
                    LEFT JOIN package_tags AS pt ON p.id = pt.package_parent
                    LEFT JOIN app_packages AS ap ON p.id = ap.package_child
                    WHERE p.id IN (" . $whereIn . ")
                    AND pd.language = ?
                    AND pn.language = ?
                    AND p.state = ?
                    GROUP BY p.id";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getLang(),
                $package->getLang(),
                $package->getState()
            ]);

            // Fetch Data
            while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                // create new plan
                $package = new Package();

                // set plan values
                $package->setId($row['id']);
                $package->setThumbnail($this->configuration['asset_link'] . $row['thumbnail']);
                $package->setName($row['name']);
                $package->setVersion($row['version']);
                $package->setState($row['state']);
                $package->setDescription($row['description']);
                $package->setLang($row['language']);
                $package->setApp($row['sku']);
                $package->setRecepiesIds($row['recepie_ids']);
                $package->setWorkoutIds($row['workout_ids']);
                $package->setTags($row['tags']);
                $package->setRawName($row['raw_name']);

                // add package to the collection
                $packageCollection->addEntity($package);
            }

            // set response status
            if($statement->rowCount() == 0){
                $packageCollection->setStatusCode(204);
            }else {
                $packageCollection->setStatusCode(200);
            }

        }catch(PDOException $e){
            $packageCollection->setStatusCode(204);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Search packages mapper: " . $e->getMessage());
        }

        // return data
        return $packageCollection;
    }


    /**
     * Delete record
     *
     * @param Package $package
     * @return Shared
     */
    public function deletePackage(Package $package):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "DELETE
                       p.*,
                       pa.*,
                       pd.*,
                       pda.*,
                       pn.*,
                       pna.*,
                       pp.*,
                       ppa.*,
                       pt.*
                    FROM package AS p
                    LEFT JOIN package_audit AS pa ON p.id = pa.package_parent
                    LEFT JOIN package_description AS pd ON p.id = pd.package_parent
                    LEFT JOIN package_description_audit AS pda ON pd.id = pda.package_desc_parent
                    LEFT JOIN package_name AS pn ON p.id = pn.package_parent
                    LEFT JOIN package_name_audit AS pna ON pn.id = pna.package_name_parent
                    LEFT JOIN package_plans AS pp ON p.id = pp.package_parent
                    LEFT JOIN package_plans_audit AS ppa ON pp.id = ppa.package_parent
                    LEFT JOIN package_tags AS pt ON p.id = pt.package_parent
                    WHERE p.id = ?
                    AND p.state != 'R'";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getId()
            ]);

            // set status code
            if($statement->rowCount() > 0){
                $shared->setResponse([200]);
            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Delete package mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Release package
     *
     * @param Package $package
     * @return Shared
     */
    public function releasePackage(Package $package):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // set database instructions
            $sql = "UPDATE 
                      package  
                    SET state = 'R'
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getId()
            ]);

            // set response values
            if($statement->rowCount() > 0){
                // set response status
                $shared->setResponse([200]);

                // get latest version value
                $version = $this->lastVersion();

                // set new version
                $sql = "UPDATE package SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute(
                    [
                        $version,
                        $package->getId()
                    ]
                );

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Release package mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Create package
     *
     * @param Package $package
     * @return Shared
     */
    public function createPackage(Package $package):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // get newest id for the version column
            $version = $this->lastVersion();

            // set database instructions for recepie plans table
            $sql = "INSERT INTO package
                      (thumbnail, raw_name, state, version)
                     VALUES (?,?,?,?)";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getThumbnail(),
                $package->getName(),
                'P',
                $version
            ]);

            // if first transaction passed continue with rest of inserting
            if($statement->rowCount() > 0){
                // get parent id
                $packageParent = $this->connection->lastInsertId();

                // insert name
                $sqlName = "INSERT INTO package_name
                              (name, language, package_parent)
                            VALUES (?,?,?)";
                $statementName = $this->connection->prepare($sqlName);

                // insert description
                $sqlDescription = "INSERT INTO package_description
                                     (description, language, package_parent)
                                   VALUES (?,?,?)";
                $statementDescription = $this->connection->prepare($sqlDescription);

                // loop through names collection
                $names = $package->getNames();
                foreach($names as $name){
                    // execute querys
                    $statementName->execute([
                        $name->getName(),
                        $name->getLang(),
                        $packageParent
                    ]);

                    $statementDescription->execute([
                        $name->getDescription(),
                        $name->getLang(),
                        $packageParent
                    ]);
                }

                // loop through workout ids
                $workoutIds = $package->getWorkoutIds();
                $recepieIds = $package->getRecepiesIds();

                // insert plans
                $sqlPlans = "INSERT INTO package_plans
                                (package_parent, workout_plan_child, recepie_plan_child)
                              VALUES (?,?,?)";
                $statementPlans = $this->connection->prepare($sqlPlans);

                // check which array has more items
                $counter = 0;
                if(count($workoutIds) > count($recepieIds)){
                    $counter = count($workoutIds);
                }else {
                    $counter = count($recepieIds);
                }

                // loop through arrays and insert their values
                for($i = 0; $i < $counter; $i++){
                    // set ids
                    $wid = isset($workoutIds[$i]) ? $workoutIds[$i] : null;
                    $rid = isset($recepieIds[$i]) ? $recepieIds[$i] : null;

                    // execute statement
                    $statementPlans->execute([
                        $packageParent,
                        $wid,
                        $rid
                    ]);
                }

                // insert tags
                $sqlTags = "INSERT INTO package_tags
                                (package_parent, tag)
                              VALUES (?,?)";
                $statementTags = $this->connection->prepare($sqlTags);

                // loop through rounds collection
                $tags = $package->getTags();
                foreach($tags as $tag){
                    // execute query
                    $statementTags->execute([
                        $packageParent,
                        $tag
                    ]);
                }

                // set status code
                $shared->setResponse([200]);

            }else {
                $shared->setResponse([304]);
            }

            // commit transaction
            $this->connection->commit();

        }catch(PDOException $e){
            // rollback everything in case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Create package mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Edit package
     *
     * @param Package $package
     * @return Shared
     */
    public function editPackage(Package $package):Shared {

        // create response object
        $shared = new Shared();

        try {
            // begin transaction
            $this->connection->beginTransaction();

            // update main recepie plans table
            $sql = "UPDATE package SET 
                        thumbnail = ?,
                        raw_name = ?
                    WHERE id = ?";
            $statement = $this->connection->prepare($sql);
            $statement->execute([
                $package->getThumbnail(),
                $package->getName(),
                $package->getId()
            ]);

            // update version
            if($statement->rowCount() > 0){
                // get last version
                $lastVersion = $this->lastVersion();

                // set database instructions
                $sql = "UPDATE package SET version = ? WHERE id = ?";
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    $lastVersion,
                    $package->getId()
                ]);
            }

            // update names query
            $sqlNames = "INSERT INTO
                            package_name (name, language, package_parent)
                            VALUES (?,?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            name = VALUES(name),
                            language = VALUES(language),
                            package_parent = VALUES(package_parent)";
            $statementNames = $this->connection->prepare($sqlNames);

            // update description query
            $sqlDescription = "INSERT INTO
                                    package_description (description, language, package_parent)
                                    VALUES (?,?,?)
                                ON DUPLICATE KEY
                                UPDATE
                                    description = VALUES(description),
                                    language = VALUES(language),
                                    package_parent = VALUES(package_parent)";
            $statementDescription = $this->connection->prepare($sqlDescription);

            // loop through data and make updates if neccesary
            $names = $package->getNames();
            foreach($names as $name){
                // execute name query
                $statementNames->execute([
                    $name->getName(),
                    $name->getLang(),
                    $package->getId()
                ]);

                // execute description query
                $statementDescription->execute([
                    $name->getDescription(),
                    $name->getLang(),
                    $package->getId()
                ]);
            }


            // first delete plan ids
            $sqlDeletePlans = "DELETE FROM package_plans WHERE package_parent = ?";
            $statementDeletePlans =$this->connection->prepare($sqlDeletePlans);
            $statementDeletePlans->execute([
                $package->getId()
            ]);


            // update recepie and workout ids
            $sqlPlans = "INSERT INTO
                                package_plans (package_parent, workout_plan_child, recepie_plan_child)
                                VALUES (?,?,?)
                            ON DUPLICATE KEY
                            UPDATE
                                package_parent = VALUES(package_parent),
                                workout_plan_child = VALUES(workout_plan_child),
                                recepie_plan_child = VALUES(recepie_plan_child)";
            $statementPlans = $this->connection->prepare($sqlPlans);

            // get ids
            $workoutIds = $package->getWorkoutIds();
            $recepieIds = $package->getRecepiesIds();

            // check which array has more items
            $counter = 0;
            if(count($workoutIds) > count($recepieIds)){
                $counter = count($workoutIds);
            }else {
                $counter = count($recepieIds);
            }

            // loop through arrays and insert or update their values
            for($i = 0; $i < $counter; $i++){
                // set ids
                $wid = isset($workoutIds[$i]) ? $workoutIds[$i] : null;
                $rid = isset($recepieIds[$i]) ? $recepieIds[$i] : null;

                $statementPlans->execute([
                    $package->getId(),
                    $wid,
                    $rid
                ]);
            }


            // first delete tag ids
            $sqlDeleteTags = "DELETE FROM package_tags WHERE package_parent = ?";
            $statementDeleteTags =$this->connection->prepare($sqlDeleteTags);
            $statementDeleteTags->execute([
                $package->getId()
            ]);

            // update tags
            $sqlTags = "INSERT INTO
                            package_tags (package_parent, tag)
                            VALUES (?,?)
                        ON DUPLICATE KEY
                        UPDATE
                            package_parent = VALUES(package_parent),
                            tag = VALUES(tag)";
            $statementTags = $this->connection->prepare($sqlTags);

            // loop through data and make updates if neccesary
            $tags = $package->getTags();
            foreach($tags as $tag){
                // execute query
                $statementTags->execute([
                    $package->getId(),
                    $tag
                ]);
            }

            // commit transaction
            $this->connection->commit();

            // set response code
            $shared->setResponse([200]);

        }catch(PDOException $e){
            // rollback everything n case of any failure
            $this->connection->rollBack();
            $shared->setResponse([304]);

            // send monolog record in case of failure
            $this->monologHelper->sendMonologRecord($this->configuration, $e->errorInfo[1], "Edit package mapper: " . $e->getMessage());
        }

        // return response
        return $shared;
    }


    /**
     * Get last version number
     *
     * @return string
     */
    public function lastVersion(){
        // set database instructions
        $sql = "INSERT INTO version VALUES(null)";
        $statement = $this->connection->prepare($sql);
        $statement->execute([]);

        // fetch id
        $lastId = $this->connection->lastInsertId();

        // return last id
        return $lastId;
    }

}