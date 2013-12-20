<?php
return
    array(
		'basePath' => dirname(__FILE__) . '/../..',
		'import' => array(
			'application.*',
			'application.validators.*',
			'application.behaviors.*',
			'application.util.*',
		),
        'components'=>array(
			'mongodb' => array(
				'class' => 'EMongoClient',
				'server' => 'mongodb://localhost:27017',
				'db' => 'super_test',
				'behaviors' => array(
					'writeConcern' => array(
						'class' => 'EMongoWriteConcernBehavior',
						'aliases' => array(
							'logs' => array('w'=>0),
							'files' => array('w'=>'majority'),
							'critical' => array('w'=>2, 'j'=>true),
						)
					)
				),
			),
			'authManager' => array(
				'class' => 'EMongoAuthManager',
			),
        ),
    );