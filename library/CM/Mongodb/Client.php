<?php

class CM_Mongodb_Client extends CM_Class_Abstract {

  /** @var \MongoClient */
  private $_mongodb = null;
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

  public function insert($collection, $object) {
    $this->_db->{$collection}->insert($object);
  }

  public function findOne($collection, $query) {
    return $this->_db->{$collection}->findOne($query);
  }

  public function find($collection, $query) {
    return $this->_db->{$collection}->find($query);
  }

  public function count($collection, $query, $limit = 0, $skip = 0) {
    $cursor = $this->find($collection, $query);
    //return $cursor->
    return $this->_db->{$collection}->count($query, $limit, $skip);
  }

  public function drop($collection) {
    $this->_db->{$collection}->drop();
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
