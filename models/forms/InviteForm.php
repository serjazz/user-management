<?php
namespace serjazz\modules\UserManagement\models\forms;

use webvimark\helpers\LittleBigHelper;
use serjazz\modules\UserManagement\models\User;
use serjazz\modules\UserManagement\models\UserProfile;
use serjazz\modules\UserManagement\UserManagementModule;
use yii\base\Model;
use Yii;

class InviteForm extends Model
{
	public $users;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
            ['users', 'filter', 'filter' => [$this, 'validateUsers']],
		    [['users'], 'required'],
		];
	}

	public function validateUsers($value){
        
	    return $value;
    }

	public function attributeLabels()
	{
		return [
			'users'   => UserManagementModule::t('back', 'Users'),
		];
	}



	public function sendInvite($profile){

    }


}
