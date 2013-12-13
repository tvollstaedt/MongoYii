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
		EMongoAuditDocument::model()->deleteAll();

		// then
		$this->assertTrue($user->save());

		// when
		$this->assertEquals(1, EMongoAuditDocument::model()->find()->count());
		$audit = EMongoAuditDocument::model()->findOne();
		$this->assertInstanceOf('EMongoAuditDocument', $audit);
		$this->assertEquals('i', $audit->op);
		$this->assertEquals('users', $audit->name);

		// then
		$user->username = 'Jack';
		$user->save();

		// when
		$this->assertEquals(2, EMongoAuditDocument::model()->find()->count());
		$audit = EMongoAuditDocument::model()->find()->limit(1)->sort(array('date' => -1))->getNext();
		$this->assertInstanceOf('EMongoAuditDocument', $audit);
		$this->assertEquals('u', $audit->op);

		// then
		$raw = $user->getRawDocument();
		$user->delete();

		// then
		$this->assertEquals(3, EMongoAuditDocument::model()->find()->count());
		$audit = EMongoAuditDocument::model()->find()->limit(1)->sort(array('date' => -1))->getNext();
		$this->assertInstanceOf('EMongoAuditDocument', $audit);
		$this->assertEquals('d', $audit->op);;
		$this->assertEquals($raw, $audit->data);
	}
}