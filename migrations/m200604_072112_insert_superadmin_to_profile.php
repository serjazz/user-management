<?php

use yii\db\Schema;
use yii\db\Migration;
use serjazz\modules\UserManagement\models\User;
use serjazz\modules\UserManagement\models\UserProfile;

class m200604_072112_insert_superadmin_to_profile extends Migration
{
	public function safeUp()
	{
		if($user = User::findByUsername('superadmin')) {
            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->lastname = 'Admin';
            $profile->firstname = 'Super';
            $profile->save(false);
        }
	}

	public function safeDown()
	{
		if($user = User::findByUsername('superadmin')){
		    if($profile = UserProfile::find()->where('user_id = '.$user->id)->one()){
                $profile->delete();
            }
        }
	}
}
