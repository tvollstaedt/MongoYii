<?php
/**
 * Class EMongoAuditModel
 *
 * Helps to work with audit data
 *
 */
class EMongoAuditDocument extends EMongoDocument
{
	public $date;
	public $data;
	public $name;
	public $op;
	public $user;

	/**
	 * @var string operation: (i)nsert, (u)pdate, (d)elete
	 */
	public $operation;

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