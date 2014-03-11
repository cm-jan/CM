<?php

class CM_PagingSource_Mongodb extends CM_PagingSource_Abstract {

  private $_fields, $_collection, $_query;

  /** @var array */
  private $_parameters = array();

  function __construct($fields, $collection, $query) {
    $this->_collection = $collection;
    $this->_query = $query;
    $this->_fields = $fields;
  }

  public function getCount($offset = null, $count = null) {
    $mdb = CM_Mongodb_Client::getInstance();
    return $mdb->find($this->_collection, $this->_query)->count();
  }

  public function getItems($offset = null, $count = null) {
    $mdb = CM_Mongodb_Client::getInstance();
    $result = array();
    $cursor = $mdb->find($this->_collection, $this->_query);
    $keyList = array_flip($this->_fields);
    foreach ($cursor as $item) {
      $item['id'] = $item['_id'];
      $result[] = array_intersect_key($item, $keyList);
    }
    return $result;
  }

  protected function _cacheKeyBase() {
    return array($this->_fields, $this->_collection, $this->_query);
  }

  public function getStalenessChance() {
    return 0.01;
  }
}
