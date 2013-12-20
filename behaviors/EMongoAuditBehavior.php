<?php
/**
 * EMongoAuditBehavior class file
 *
 * @author Pavel E. Tetyaev <pahanini@gmail.com>
 * @license http://www.yiiframework.com/license/
 *
 */

/**
 * EMongoAuditBehaviour allows audit changes of EMongoDocuments
 */
class EMongoAuditBehavior extends CActiveRecordBehavior {

	private $_oldAttributes=array();
	private $_user=false;

	/**
	 * @var string name of collection to save audit log
	 */
	public $collectionName = 'audit';

	/**
	 * @var array key names to included in diff if any other keys are of different
	 */
	public $include = array();

	/**
	 * @var array list of Yii::app()->user component properties to save in log
	 */
	public $user = array('id');

	/**
	 * @param CEvent $event
	 */
	public function afterDelete($event){
		$record = array();
		$record['data'] = null;
		$record['op'] = 'd';
		$this->save($record);
	}

	/**
	 * @param CEvent $event
	 */
	public function afterFind($event){
		$this->_oldAttributes = $this->getClone($this->getOwner()->getAttributes());
	}

	/**
	 * @param CEvent $event
	 */
	public function afterSave($event){
		/** @var  $owner EMongoDocument */
		$owner = $this->getOwner();
		$record = array();
		$newAttributes = $this->getClone($owner->getAttributes());
		$record['data'] = $this->getDiff($this->_oldAttributes, $newAttributes);
		$record['op'] = $owner->getIsNewRecord() ? 'i' : 'u';
		$this->_oldAttributes = $newAttributes;
		$this->save($record);
	}

	/**
	 * @param CComponent $owner
	 */
	public function attach($owner){
		parent::attach($owner);
		$this->afterFind(null);
	}

	/**
	 * @param $val
	 * @return array|null
	 */
	private function getAsArray($val) {
		if ($val instanceof EMongoModel) {
			return $val->getAttributes();
		}
		if ($val instanceof EMongoArrayModel) {
			return $val->getIndexedRawValues();
		}
		return $val;
	}

	/**
	 * @param $attributes
	 * @return array
	 * @ignore
	 */
	private function getClone($attributes)
	{
		$result = array();
		foreach ($attributes as $key => $val) {
			if ($val instanceof EMongoArrayModel) {
				$val = clone $val;
			}
			if (is_array($val)) {
				$result[$key] = $this->getClone($val);
			} else {
				$result[$key] = $val;
			}
		}
		return $result;
	}


	/**
	 * Calculates recursive difference between two arrays, array values can be any type
	 *
	 * @param array $old
	 * @param array $new
	 * @param null $indexName
	 * @return array
	 */
	private function getDiffArray(array $old, array $new, $indexName=null)
	{
		$result = array();
		foreach ($new as $key => $newValue) {
			// newValue does not exist in old array
			if (!array_key_exists($key, $old)) {
				$result[$key] = $newValue;
				continue;
			}
			// Convert EMongoArrayModels with empty index to arrays
			$oldValue = $old[$key];
			if ($newValue instanceof EMongoArrayModel && !$newValue->getIndexName()) {
				$newValue = $newValue->getRawValues();
			}
			if ($oldValue instanceof EMongoArrayModel && !$oldValue->getIndexName()) {
				$oldValue = $oldValue->getRawValues();
			}
			// Convert EMongoModels to arrays
			if ($newValue instanceof EMongoModel) {
				$newValue = $newValue->getAttributes();
			}
			if ($oldValue instanceof EMongoModel) {
				$oldValue = $oldValue->getAttributes();
			}
			// newValue is array
			if (is_array($newValue)) {
				if (is_array($oldValue)) {
					if ($diff = $this->getDiffArray($oldValue, $newValue)) {
						if ($indexName) {
							if (array_key_exists($indexName, $newValue)) {
								$diff[$indexName] = $newValue[$indexName];
							} elseif (array_key_exists($indexName, $oldValue)) {
								$diff[$indexName] = $oldValue[$indexName];
							}
						}
						$result[$key] = $diff;
					}
				} else {
					$result[$key] = $newValue;
				}
				continue;
			}
			// newValue is EMongoArrayModel with not empty index (empty indexes was converted to arrays at prev. step)
			if ($newValue instanceof EMongoArrayModel) {
				if ($oldValue instanceof EMongoArrayModel && $oldValue->getIndexName() == $newValue->getIndexName()) {
					if ($diff = $this->getDiffArray($oldValue->getIndexedRawValues(), $newValue->getIndexedRawValues(), $newValue->getIndexName())) {
						foreach ($diff as $diffKey => $diffVal) {
							$diff[$diffKey][$newValue->getIndexName()]=$diffKey;
						}
						$result[$key] = array_values($diff);
					}
				} else {
					$result[$key] = $newValue;
				}
				continue;
			}
			// Scalar values
			if ($oldValue !== $newValue) {
				$result[$key] = $newValue;
			}
		}

		// mark removed keys with null
		$newKeys = array_keys($new);
		$oldKeys = array_keys($old);
		if ($oldDeleted = array_diff($oldKeys,$newKeys)) {
			foreach ($oldDeleted as $key) {
				$result[$key] = null;
			}
		}

		return $result;
	}

	/**
	 * Returns recursive difference between two sets of attributes
	 * @param EMongoModel|array|EArrayMongoModel $old
	 * @param EMongoModel|array|EArrayMongoModel $new
	 * @return array|null
	 */
	public function getDiff($old, $new) {
		$old = $this->getAsArray($old);
		$new = $this->getAsArray($new);
		if (is_array($old) &&  is_array($new)) {
			$result = $this->getDiffArray($old, $new);
		} else {
			if ($old !== $new) {
				return $new;
			} else {
				return null;
			}
		}
		$tmp = new EMongoModel();
		return $tmp->filterRawDocument($result);
	}

	/**
	 * @return array|bool|null
	 */
	public function getUser(){
		if ($this->_user === false) {
			if ($user = Yii::app()->getComponent('user')){
				$this->_user=array();
				foreach($this->user as $val) {
					$this->_user[$val] = $user->$val;
				}
			} else {
				$this->_user=null;
			}
		}
		return $this->_user;
	}

	/**
	 * @param array $record
	 */
	private function save($record){
		$record['date'] = new MongoDate();
		$record['user'] = $this->getUser();
		$record['name'] =$this->getOwner()->collectionName();
		$connection = $this->getOwner()->getDbConnection();
		$connection->selectCollection($this->collectionName)->insert($record, array('w' => 0));
	}
}