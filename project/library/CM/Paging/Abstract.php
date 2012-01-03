<?php

class CM_Paging_Abstract extends CM_Class_Abstract implements Iterator {
	private $_count = null;
	private $_itemsRaw = null, $_items = null;
	private $_pageOffset = 0;
	private $_pageSize = null;
	private $_source = null;
	private $_itemsPosition = 0;
	private $_filters = array();

	/**
	 * @param CM_PagingSource_Abstract $source
	 */
	function __construct(CM_PagingSource_Abstract $source = null) {
		$this->_source = $source;
	}

	/**
	 * @param int $offset Negative: from end
	 * @param int $length
	 * @param bool $skipNonexistent
	 * @return array
	 */
	public function getItems($offset = null, $length = null, $skipNonexistent = true) {
		if ($this->_items === null) {
			$itemsRaw = $this->_getItemsRaw();
			$this->_items = array();
			foreach ($itemsRaw as &$itemRaw) {
				try {
					$item = $this->_processItem($itemRaw);
					if (!$this->_isFilterMatch($item)) {
						$this->_items[] = $item;
					}
				} catch (CM_Exception_Nonexistent $e) {
					if (!$skipNonexistent) {
						$this->_items[] = null;
					}
				}
				if (count($this->_items) === $this->_pageSize) {
					break;
				}
			}
		}
		if ($offset) {
			return array_slice($this->_items, $offset, $length);	
		}
		return $this->_items;
	}

	/**
	 * Return Un-processed, un-filtered items
	 * 
	 * @return array
	 */
	public function getItemsRaw() {
		$itemsRaw = $this->_getItemsRaw();
		if (null !== $this->_pageSize && count($itemsRaw) > $this->_pageSize) {
			$itemsRaw = array_slice($itemsRaw, 0, $this->_pageSize);
		}
		return $itemsRaw;
	}

	/**
	 * @param int $offset Negative: from end
	 * @return mixed|null Item at given index
	 */
	public function getItem($offset) {
		$items = $this->getItems($offset, 1);
		return array_shift($items);
	}

	/**
	 * @return int
	 */
	public function getCount() {
		if ($this->_count === null && $this->_source) {
			$this->_setCount($this->_source->getCount($this->_getItemOffset(), ceil($this->_pageSize * $this->_getPageFillRate())));
		}
		return (int) $this->_count;
	}

	/**
	 * @return int
	 */
	public function getPage() {
		return ($this->_pageOffset + 1);
	}

	/**
	 * @param int $page
	 * @param int $size
	 * @return CM_Paging_Abstract
	 */
	public function setPage($page, $size) {
		$this->_clearItems();
		$this->_pageOffset = max(((int) $page - 1), 0);
		$this->_pageSize = max((int) $size, 0);
		$this->_validatePageOffset();
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPageCount() {
		if (!$this->getCount()) {
			return 0;
		}
		if (!$this->_pageSize) {
			return 0;
		}
		return ceil($this->getCount() / $this->_pageSize);
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		return $this->getCount() == 0;
	}

	/**
	 * Filter result of getItems() by a callback
	 * 
	 * @param callback $filter function(mixed $item): boolean
	 */
	public function filter($filter) {
		$this->_clearItems();
		$this->_filters[] = $filter;
	}

	/**
	 * @param array|mixed $items
	 */
	public function exclude($items) {
		if (!is_array($items)) {
			$items = array($items);
		}
		if (count($items) == 0) {
			return;
		}

		$comparable = true;
		foreach ($items as $item) {
			$comparable &= ($item instanceof CM_Comparable);
		}

		if ($comparable) {
			$filter = function ($item) use ($items) {
				foreach ($items as $itemExcluded) {
					if ($item->equals($itemExcluded)) {
						return false;
					}
				}
				return true;
			};
		} else {
			$filter = function ($item) use ($items) {
				return !in_array($item, $items);
			};
		}

		$this->filter($filter);
	}

	/**
	 * Items in the underlying source have changed
	 */
	public function _change() {
		if (!$this->_source) {
			throw new CM_Exception('Cannot change paging without source');
		}
		$this->_source->clearCache();
		$this->_clearItems();
		$this->_clearCount();
	}

	/**
	 * @return int Multiple of items per page to load from CM_PagingSource_Abstract
	 */
	protected function _getPageFillRate() {
		return 1;
	}

	/**
	 * @param mixed $itemRaw
	 * @return mixed Processed item
	 * @throws CM_Exception_Nonexistent
	 */
	protected function _processItem($itemRaw) {
		return $itemRaw;
	}

	/**
	 * @param mixed $item
	 * @return boolean Whether the item is matched by any of the registered filters
	 */
	private function _isFilterMatch($item) {
		foreach ($this->_filters as $filter) {
			if (false === $filter($item)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array Raw items (might contain more than $this->_pageSize)
	 */
	private function _getItemsRaw() {
		if ($this->_itemsRaw === null) {
			$this->_itemsRaw = array();
			if ($this->_source) {
				$count = ($this->_pageSize === null) ? null : ceil($this->_pageSize * $this->_getPageFillRate());
				$itemsRaw = $this->_source->getItems($this->_getItemOffset(), $count);
				foreach ($itemsRaw as &$itemRaw) {
					if (is_array($itemRaw) && count($itemRaw) == 1) {
						$itemRaw = reset($itemRaw);
					}
					$this->_itemsRaw[] = $itemRaw;
				}
			}
		}
		return $this->_itemsRaw;
	}

	/**
	 * @return int OR null if no pageSize set
	 */
	private function _getItemOffset() {
		if ($this->_pageSize === null) {
			return null;
		}
		return (int) $this->_pageOffset * $this->_pageSize;
	}

	private function _clearItems() {
		$this->_items = null;
		$this->_itemsRaw = null;
		$this->_itemsPosition = 0;
	}

	private function _clearCount() {
		$this->_count = null;
	}

	/**
	 * @param int $count
	 * @return CM_Paging_Abstract
	 */
	public function _setCount($count) {
		$this->_count = max((int) $count, 0);
		$this->_validatePageOffset();
		return $this;
	}

	private function _validatePageOffset() {
		if ($this->_pageSize !== null) {
			if ($this->_pageOffset * $this->_pageSize >= $this->getCount()) {
				if ($this->_pageSize == 0 || $this->getCount() == 0) {
					$this->_pageOffset = 0;
				} else {
					$this->_pageOffset = ceil($this->getCount() / $this->_pageSize) - 1;
				}
			}
		}
	}

	/* Iterator functions */
	function rewind() {
		$this->_itemsPosition = 0;
	}
	function current() {
		return $this->getItem($this->_itemsPosition);
	}
	function key() {
		return $this->_itemsPosition;
	}
	function next() {
		++$this->_itemsPosition;
	}
	function valid() {
		$items = $this->getItems();
		return isset($items[$this->_itemsPosition]);
	}

}
