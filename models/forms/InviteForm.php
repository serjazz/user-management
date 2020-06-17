<?php
namespace serjazz\modules\UserManagement\models\forms;

use webvimark\helpers\LittleBigHelper;
use serjazz\modules\UserManagement\models\User;
use serjazz\modules\UserManagement\models\UserProfile;
use serjazz\modules\UserManagement\UserManagementModule;
use yii\base\Model;
use Yii;
use yii\helpers\Url;

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

    /**
     * Validate reqest email addresses
     * @param $value
     * @return mixed
     */
    public function validateUsers($value){
        if(is_array($value)){
            foreach ($value as $val){
                if(!$this->checkEmail($val)){
                    $this->addError('users',UserManagementModule::t('back', 'Email {email} is not a valid email address',['email' => $val]));
                    break;
                }
            }
        } else {
            $this->addError('users',UserManagementModule::t('back', 'No email specified'));
        }
        return $value;
    }

    /**
     * Check is email correct
     * @param $email
     * @return mixed
     */
    private function checkEmail($email){
        return filter_var($email,FILTER_VALIDATE_EMAIL);
    }

    public function attributeLabels()
    {
        return [
            'users'   => UserManagementModule::t('back', 'Users'),
        ];
    }


    /**
     * send invite user
     * @param $profile
     * @return bool
     */
    public function sendInvite($profile){
        foreach ($this->users as $user){
            if(!$this->sendInviteEmail($user,$profile->company_hash)){
                $this->addError('users',UserManagementModule::t('back', 'Failed to send {email}',['email'=>$user]));
                return false;
            }
        }
        return true;
    }

    /**
     * Send invite email to address
     * @param string $hash
     * @param string $user
     * @return bool
     */
    protected function sendInviteEmail($user,$hash)
    {
        return Yii::$app->mailer->compose(Yii::$app->getModule('user-management')->mailerOptions['inviteFormViewFile'], ['user' => $user,'hash' => $hash])
            ->setFrom(Yii::$app->getModule('user-management')->mailerOptions['from'])
            ->setTo($user)
            ->setSubject(UserManagementModule::t('back', 'Invitation to the common workspace'))
            ->send();
    }


}
