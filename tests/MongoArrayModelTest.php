<?php

require_once 'bootstrap.php';

class MongoArrayModelTest extends CTestCase{

	function tearDown(){
		// drop the database after every test
		Yii::app()->mongodb->drop();
	}

	public function testArrayAccessAndCountable(){
		// Not indexed version
		$am=new EMongoArrayModel('Dummy');
		$am[]=array('dum'=>1);
		$this->assertInstanceOf('Dummy', $am[0]);
		$this->assertEquals(1, $am[0]->dum);
		$dummy=new Dummy;
		$dummy->dum=4;
		$am->populate(array(array('dum'=>3), $dummy));
		$this->assertInstanceOf('Dummy', $am[0]);
		$this->assertEquals(3, $am[0]->dum);
		$this->assertInstanceOf('Dummy', $am[1]);
		$this->assertEquals(4, $am[1]->dum);
		$this->assertEquals(2, count($am));
		unset($am[0]);
		$this->assertEquals(4, $am[0]->dum);
		$this->assertEquals(1, count($am));

		// Indexed version
		$am=new EMongoArrayModel('Dummy', array(), 'dum');
		$am[]=array('dum'=>911);
		$this->assertInstanceOf('Dummy', $am[911]);
		$this->assertEquals(911, $am[911]->dum);
		$dummy=new Dummy;
		$dummy->dum=4;
		$am->populate(array(array('dum'=>3), $dummy));
		$this->assertInstanceOf('Dummy', $am[3]);
		$this->assertEquals(3, $am[3]->dum);
		$this->assertInstanceOf('Dummy', $am[4]);
		$this->assertEquals(4, $am[4]->dum);
		$this->assertEquals(2, count($am));
		unset($am['3']);
		$this->assertEquals(4, $am[4]->dum);
		$this->assertEquals(1, count($am));
	}

	public function testAttributesAndValidator(){
		$user=new User;
		$this->assertInstanceOf('EMongoArrayModel', $user->phones);
		$this->assertInstanceOf('EMongoArrayModel', $user->accounts);

		$user=new User;
		$user->phones=array();
		$this->assertInstanceOf('EMongoArrayModel', $user->phones);

		// Set array with data (indexed)
		$user->phones=array(array('num'=>'911', 'comment'=>'Urgent'), array('num'=>100, 'comment' => 'Home'));
		//var_dump($user);
		$homePhone=$user->phones[100];
		$this->assertInstanceOf('Phone', $homePhone);
		$this->assertEquals('Home', $homePhone->comment);

		// Set array with data (not indexed)
		$user->accounts=array(array('bank'=>'Alfa', 'swiftCode'=>'ALFA', 'dummies'=>array(
			array('dum' => 'a'),
			array('dum' => 'b'),
		)));
		$this->assertFalse($user->validate());
		$account=$user->accounts[0];
		$this->assertInstanceOf('Account', $account);
		$this->assertInstanceOf('EMongoArrayModel', $account->dummies);
		$account->swiftCode='1232';
		$this->assertTrue($user->validate());
	}

	public function testIterators()
	{
		$user=new User;
		$user->phones=array();
		$user->phones=array(array('num'=>'911', 'comment'=>'Urgent'), array('num'=>100, 'comment' => 'Home'));
		$result = iterator_to_array($user->phones);
		$this->assertContainsOnly('Phone', $result);
		$this->assertTrue(true);
	}


	public function testSaveUpdateFind()
	{
		// Test save (insert)
		$user=new User;
		$phone = new Phone();
		$phone->num='111';
		$user->phones[]=$phone;
		$user->save();
		$_id=$user->_id;
		$user = User::model()->getCollection()->findOne(array('_id' => $_id));
		$this->assertEquals(array(array('num'=>111, 'comment'=>null)), $user['phones']);

		// Test find and update
		$user = User::model()->findBy_id($_id);
		$this->assertEquals(111, $user->phones['111']->num);
		$this->assertCount(1, $user->phones);
		$user->phones[]=array('num'=>112, 'comment'=>'phone112');
		$user->update();
		$user = User::model()->findBy_id($_id);
		$this->assertCount(2, $user->phones);
		unset($user->phones[111]);
		$user->update(array('phones', 'url'));
		$user = User::model()->findBy_id($_id);
		$this->assertCount(1, $user->phones);
		$this->assertEquals('phone112', $user->phones['112']->comment);
	}

}