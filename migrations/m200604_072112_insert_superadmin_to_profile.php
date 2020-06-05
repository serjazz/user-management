<?php

use yii\db\Schema;
use yii\db\Migration;
use serjazz\modules\UserManagement\models\User;

class m200604_072112_insert_superadmin_to_profile extends Migration
{
	public function safeUp()
	{
		$user = User::findOne(1);
		$user->superadmin = 1;
		$user->status = User::STATUS_ACTIVE;
		$user->username = 'superadmin';
		$user->password = 'superadmin';
		$user->save(false);
	}

	public function safeDown()
	{
		$user = User::findByUsername('superadmin');

		if ( $user )
		{
			$user->delete();
		}
	}
}
