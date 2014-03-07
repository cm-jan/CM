<?php

class CM_PagingSource_Mongodb extends CM_PagingSource_Abstract {

  private $_collection, $_query;

  /** @var array */
  private $_parameters = array();

  function __construct($collection, $query) {
     $this->_collection = $collection;
     $this->_query = $query;
  }

  public function getCount($offset = null, $count = null) {
    $mdb = CM_Mongodb_Client::getInstance();
    return $mdb->find($this->_collection, $this->_query)->count();
  }

  public function getItems($offset = null, $count = null) {
    $mdb = CM_Mongodb_Client::getInstance();
    $result = array();
    //var_dump($this->_collection);
    $cursor = $mdb->find($this->_collection, $this->_query);
    foreach ($cursor as $item) {
      $result[] = $item;
    }
    return $result;
  }

  protected function _cacheKeyBase() {
    throw new CM_Exception_Invalid('`' . __CLASS__ . '` does not support caching.');
  }

  public function getStalenessChance() {
    return 0.01;
  }
}
