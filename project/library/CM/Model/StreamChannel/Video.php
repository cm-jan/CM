<?php

class CM_Model_StreamChannel_Video extends CM_Model_StreamChannel_Abstract {

	const TYPE = 19;

	public function onPublish(CM_Model_Stream_Publish $streamPublish) {
	}

	public function onSubscribe(CM_Model_Stream_Subscribe $streamSubscribe) {
	}

	public function onUnpublish(CM_Model_Stream_Publish $streamPublish) {
	}

	public function onUnsubscribe(CM_Model_Stream_Subscribe $streamSubscribe) {
	}

	/**
	 * @return int
	 */
	public function getWidth() {
		return (int) $this->_get('width');
	}

	/**
	 * @return int
	 */
	public function getHeight() {
		return (int) $this->_get('height');
	}

	protected function _loadData() {
		return CM_Mysql::exec("SELECT * FROM TBL_CM_STREAMCHANNEL JOIN TBL_CM_STREAMCHANNEL_VIDEO USING (`id`) WHERE `id` = ?", $this->getId())->fetchAssoc();
	}

	protected static function _create(array $data) {
		$key = (string) $data['key'];
		$width = (int) $data ['width'];
		$height = (int) $data ['height'];
		$id = CM_Mysql::insert(TBL_CM_STREAMCHANNEL, array('key' => $key, 'type' => static::TYPE));
		try {
			CM_Mysql::insert(TBL_CM_STREAMCHANNEL_VIDEO, array('id' => $id, 'width' => $width, 'height' => $height));
		} catch (CM_Exception $ex) {
			CM_Mysql::delete(TBL_CM_STREAMCHANNEL, array('id' => $id));
			throw $ex;
		}
		return new static($id);
	}
}
