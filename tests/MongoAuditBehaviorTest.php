<?php

require_once 'bootstrap.php';

class MongoAuditBehaviorTest extends CTestCase{

	public function testSave()
	{
		User::model()->deleteAll();
		EMongoAuditDocument::model()->deleteAll();

		$user = new User();
		$user->attachBehavior('audit', 'EMongoAuditBehavior');
		$user->phones[] = array('num' => 911, 'comment' => 'Comment');
		$this->assertTrue($user->save());

		$user = User::model()->findOne();
		$user->attachBehavior('audit', 'EMongoAuditBehavior');
		$user->phones[911]->comment = 'Comment2';
		$this->assertTrue($user->save());

		$audit = EMongoAuditDocument::model()->find();
		$this->assertEquals(2, $audit->count());
		$audit = $audit->skip(1)->getNext();
		$this->assertEquals(array(
			array('num' => 911, 'comment' => 'Comment2')
		), $audit->data['phones']);
		$this->assertInstanceOf('MongoId', $audit->data['_id']);
	}

	public function testDiff()
	{
		$audit = new EMongoAuditBehavior();

		// then
		$a = new User;
		$a->username = 'a';
		$b = new User;
		$b->username = 'b';
		$b->phones[] = array('num' => 101, 'comment' => 'comment101');

		// when
		$this->assertEquals(array(
			'username' => 'b',
			'phones' => array(
				array('num' => 101, 'comment' => 'comment101')
			)),
			$audit->getDiff($a->getAttributes(), $b->getAttributes())
		);
		$this->assertEquals(array(
				'username' => 'a',
				'phones' => null),
			$audit->getDiff($b->getAttributes(), $a->getAttributes())
		);

		// then
		$a->phones[] = array('num' => 101);

		// when
		$this->assertEquals(array(
				'username' => 'b',
				'phones' => array(
					array('num' => 101, 'comment' => 'comment101')
				)),
			$audit->getDiff($a->getAttributes(), $b->getAttributes())
		);

		$this->assertEquals(array(
				'username' => 'a',
				'phones' => array(
					array('num' => 101, 'comment' => null)
				)),
			$audit->getDiff($b->getAttributes(), $a->getAttributes())
		);

		// then
		$c = new User;
		$c->username = 'c';
		$c->phones[] = array('num' => 101, 'comment' => 'comment101c');

		// when
		$this->assertEquals(array(
				'username' => 'c',
				'phones' => array(
					array('num' => 101, 'comment' => 'comment101c')
				)),
			$audit->getDiff($b->getAttributes(), $c->getAttributes())
		);

		// difficult case 2 keys were deleted
		// then
		$d = new User();
		$d->username = 'd';
		$d->phones[]=array('num' => 101, 'comment' => 'comment101');
		$e = new User();
		$e->username = 'e';
		$e->phones[]=array('num' => 101, 'comment' => 'comment101');
		$e->phones[]=array('num' => 102, 'comment' => 'comment102');
		$e->phones[]=array('num' => 103, 'comment' => 'comment103');

		// when
		$this->assertEquals(array(
				'username' => 'd',
				'phones' => array(
					array('num' => 102),
					array('num' => 103)
				)),
			$audit->getDiff($e->getAttributes(), $d->getAttributes())
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
		$user->delete();

		// then
		$this->assertEquals(3, EMongoAuditDocument::model()->find()->count());
		$audit = EMongoAuditDocument::model()->find()->limit(1)->sort(array('date' => -1))->getNext();
		$this->assertInstanceOf('EMongoAuditDocument', $audit);
		$this->assertEquals('d', $audit->op);;
		$this->assertEquals(null, $audit->data);
	}
}