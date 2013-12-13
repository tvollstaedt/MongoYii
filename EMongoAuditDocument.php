<?php
/**
 * Class EMongoAuditModel
 *
 * Helps to work with audit data
 *
 */
class EMongoAuditDocument extends EMongoDocument
{
	/**
	 * @var MongoDate date of modification
	 */
	public $date;

	/**
	 * @var array modified data
	 */
	public $data;

	/**
	 * @var string collection name
	 */
	public $name;

	/**
	 * @var string operation: (i)nsert, (u)pdate, (d)elete
	 */
	public $op;

	/**
	 * @var array user info
	 */
	public $user;

	public function collectionName()
	{
		return 'audit';
	}

	/**
	 * Returns the static model of the specified AR class.
	 *
	 * @param string $className
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}