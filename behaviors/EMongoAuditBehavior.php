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
	 * @var array list of Yii::app()->user component properties to save in log
	 */
	public $user = array('id');

	/**
	 * @param CEvent $event
	 */
	public function afterDelete($event){
		$record = array();
		$record['data'] = $this->_oldAttributes;
		$record['op'] = 'd';
		$this->save($record);
	}

	/**
	 * @param CEvent $event
	 */
	public function afterFind($event){
		$this->_oldAttributes = $this->getOwner()->getRawDocument();
	}

	/**
	 * @param CEvent $event
	 */
	public function afterSave($event){
		/** @var  $owner EMongoDocument */
		$owner = $this->getOwner();
		$record = array();
		$newAttributes = $owner->getRawDocument();
		$record['data'] = $this->getDiff($this->_oldAttributes, $newAttributes);
		$record['op'] = $owner->getIsNewRecord() ? 'i' : 'u';
		$this->_oldAttributes = $newAttributes;
		$this->save($record);
	}

	/**
	 * Returns recursive difference between two arrays
	 * @param $new
	 * @param $old
	 * @return array
	 */
	public function getDiff($new, $old){
		$result = array();
		foreach ($old as $key => $value) {
			if (is_array($value)) {
				if (!isset($new[$key]) || !is_array($new[$key])) {
					$result[$key] = $value;
				} else {
					$new_diff = $this->getDiff($value, $new[$key]);
					if (!empty($new_diff)) {
						$result[$key] = $new_diff;
					}
				}
			} elseif (!array_key_exists($key, $new) || $new[$key] !== $value) {
				$result[$key] = $value;
			}
		}
		return $result;
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