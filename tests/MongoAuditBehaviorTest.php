<?php

require_once 'bootstrap.php';

class MongoAuditBehaviorTest extends CTestCase{

	public function testDiff()
	{
		$audit = new EMongoAuditBehavior();
		$a = new User;
		$a->username = 'a';
		$b = new User;
		$b->username = 'b';
		$b->phones[] = array('num' => 101, 'comment' => 'comment101');
		$this->assertEquals(array(
			'username' => 'b',
			'phones' => array(
				array('num' => 101, 'comment' => 'comment101')
			)),
			$audit->getDiff($a->getRawDocument(), $b->getRawDocument())
		);
	}

	public function testAll(){

		// done
		$user = new User();
		$b = new EMongoAuditBehavior();
		$user->attachBehavior('audit', $b);
		$user->username = 'Mike';
		EMongoAuditModel::model()->deleteAll();

		// then
		$this->assertTrue($user->save());

		// when
		$this->assertEquals(1, EMongoAuditModel::model()->find()->count());
		$audit = EMongoAuditModel::model()->findOne();
		$this->assertInstanceOf('EMongoAuditModel', $audit);
		$this->assertEquals('i', $audit->op);
		$this->assertEquals('users', $audit->name);

		// then
		$user->username = 'Jack';
		$user->save();

		// when
		$this->assertEquals(2, EMongoAuditModel::model()->find()->count());
		$audit = EMongoAuditModel::model()->find()->limit(1)->sort(array('date' => -1))->getNext();
		$this->assertInstanceOf('EMongoAuditModel', $audit);
		$this->assertEquals('u', $audit->op);

		// then
		$raw = $user->getRawDocument();
		$user->delete();

		// then
		$this->assertEquals(3, EMongoAuditModel::model()->find()->count());
		$audit = EMongoAuditModel::model()->find()->limit(1)->sort(array('date' => -1))->getNext();
		$this->assertInstanceOf('EMongoAuditModel', $audit);
		$this->assertEquals('d', $audit->op);;
		$this->assertEquals($raw, $audit->data);
	}
}