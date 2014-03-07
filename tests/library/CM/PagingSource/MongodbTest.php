<?php

class CM_PagingSource_Mongodb_Test extends CMTest_TestCase {

  /** @var CM_Mongodb_Client */
  private $_mongodb;
  private $_collection = 'unitTest';

  public function setUp() {
    $this->_mongodb = CM_Mongodb_Client::getInstance();
    $this->tearDown(); // cleanup all failed tests leftovers

    $this->_mongodb->insert($this->_collection, array('userId' => 1, 'message' => 'message 1'));
    $this->_mongodb->insert($this->_collection, array('userId' => 2, 'message' => 'message 2'));
    $this->_mongodb->insert($this->_collection, array('userId' => 1, 'message' => 'message 3'));
    $this->_mongodb->insert($this->_collection, array('userId' => 2, 'message' => 'message 4'));
    $this->_mongodb->insert($this->_collection, array('userId' => 2, 'message' => 'message 5'));
    $this->_mongodb->insert($this->_collection, array('userId' => 2, 'message' =>'message 6'));
    $this->_mongodb->insert($this->_collection, array('userId' => 1, 'message' => 'message 7'));
    $this->_mongodb->insert($this->_collection, array('userId' => 2, 'message' => 'message 8'));
  }

  public function tearDown() {
    $this->_mongodb->drop($this->_collection);
  }

  public function testCount() {
    $source = new CM_PagingSource_Mongodb($this->_collection, array('userId' => 2));
    $this->assertSame(5, $source->getCount());
  }
}
