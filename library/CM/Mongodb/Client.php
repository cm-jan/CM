<?php

class CM_Mongodb_Client extends CM_Class_Abstract {

  /** @var \MongoClient */
  private $_mongodb = null;

  /** @var MongoDB */
  private $_db = null;

  public function __construct() {
    $this->_mongodb = new MongoClient();
    $this->useDatabase('fuckbook');
  }

  public function getNewId() {
    $mongoId = new MongoId();
    return (string)$mongoId;
  }

  public function useDatabase($databaseName) {
    if ($this->_db) {
      unset($this->_db);
    }
    $this->_db = $this->_mongodb->{$databaseName};
  }

  public function getCollection($collection) {
    return $this->_db->{$collection};
  }

  public function insert($collection, $object) {
    return $this->getCollection($collection)->insert($object);
  }

  /**
   * @param $collection
   * @param $query
   * @return array
   */
  public function findOne($collection, $query) {
    return $this->getCollection($collection)->findOne($query);
  }

  /**
   * @param $collection
   * @param $query
   * @return MongoCursor
   */
  public function find($collection, $query) {
    return $this->getCollection($collection)->find($query);
  }

  /**
   * @param     $collection
   * @param     $query
   * @param int $limit
   * @param int $skip
   * @return int
   */
  public function count($collection, $query, $limit = 0, $skip = 0) {
    return $this->getCollection($collection)->count($query, $limit, $skip);
  }

  public function drop($collection) {
    return $this->getCollection($collection)->drop();
  }

  /**
   * @return CM_Mongodb_Client
   */
  public static function getInstance() {
    static $instance;
    if (!$instance) {
      $instance = new self();
    }
    return $instance;
  }
}
