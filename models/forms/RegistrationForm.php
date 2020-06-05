<?php
namespace serjazz\modules\UserManagement\models\forms;

use serjazz\modules\UserManagement\models\User;
use serjazz\modules\UserManagement\models\UserProfile;
use serjazz\modules\UserManagement\UserManagementModule;
use yii\base\Model;
use Yii;
use yii\helpers\Html;

class RegistrationForm extends Model
{
    public $username;
    public $password;
    public $repeat_password;
    public $captcha;
    public $chash;
    public $is_company = 1;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            ['captcha', 'captcha', 'captchaAction'=>'/user-management/auth/captcha'],

            [['username', 'password', 'repeat_password', 'captcha'], 'required'],
            [['username', 'password', 'repeat_password'], 'trim'],

            ['username', 'unique',
                'targetClass'     => 'serjazz\modules\UserManagement\models\User',
                'targetAttribute' => 'username',
            ],

            [['username','chash'], 'purgeXSS'],

            [['password','chash'], 'string', 'max' => 255],
            [['is_company'], 'number','min'=>0,'max'=>1],
            ['password', 'match', 'pattern' => Yii::$app->getModule('user-management')->passwordRegexp],
            ['chash','safe'],
            ['repeat_password', 'compare', 'compareAttribute'=>'password'],
        ];

        if ( Yii::$app->getModule('user-management')->useEmailAsLogin )
        {
            $rules[] = ['username', 'email'];
        }
        else
        {
            $rules[] = ['username', 'string', 'max' => 50];

            $rules[] = ['username', 'match', 'pattern'=>Yii::$app->getModule('user-management')->registrationRegexp];
            $rules[] = ['username', 'match', 'not'=>true, 'pattern'=>Yii::$app->getModule('user-management')->registrationBlackRegexp];
        }

        return $rules;
    }

    /**
     * Remove possible XSS stuff
     *
     * @param $attribute
     */
    public function purgeXSS($attribute)
    {
        $this->$attribute = Html::encode($this->$attribute);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'username'        => Yii::$app->getModule('user-management')->useEmailAsLogin ? 'E-mail' : UserManagementModule::t('front', 'Login'),
            'password'        => UserManagementModule::t('front', 'Password'),
            'repeat_password' => UserManagementModule::t('front', 'Repeat password'),
            'captcha'         => UserManagementModule::t('front', 'Captcha'),
            'is_company'         => UserManagementModule::t('front', 'You are company?'),
        ];
    }

    /**
     * @param bool $performValidation
     *
     * @return bool|User
     */
    public function registerUser($performValidation = true)
    {
        if ( $performValidation AND !$this->validate() )
        {
            return false;
        }
        $transaction = User::getDb()->beginTransaction();
        try {
            $user = new User();
            $user->password = $this->password;
            if (Yii::$app->getModule('user-management')->useEmailAsLogin) {
                $user->email = $this->username;

                // If email confirmation required then we save user with "inactive" status
                // and without username (username will be filled with email value after confirmation)
                if (Yii::$app->getModule('user-management')->emailConfirmationRequired) {

                    $user->status = User::STATUS_INACTIVE;
                    $user->generateConfirmationToken();
                    $user->save(false);

                    if($this->saveProfile($user)){
                        $transaction->commit();
                        if ($this->sendConfirmationEmail($user)) {
                            return $user;
                        } else {
                            $this->addError('username', UserManagementModule::t('front', 'Could not send confirmation email'));
                        }
                    }
                } else {
                    $user->username = $this->username;
                }
            } else {
                $user->username = $this->username;
            }

            if ($user->save()) {
                if($this->saveProfile($user)){
                    $transaction->commit();
                }
                return $user;
            } else {
                $this->addError('username', UserManagementModule::t('front', 'Login has been taken'));
            }
        } catch(\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Save user profile
     *
     * @param User $user
     * @return bool
     */
    protected function saveProfile($user)
    {
        //create user profile
        $profile = new UserProfile();
        $profile->user_id = $user->id;
        $profile->lastname = $user->email;
        if(!$this->chash && $this->is_company){
            $profile->is_company = 1;
            $profile->company_hash = $user->generateChash();
        } elseif($this->chash){
            $profile->is_company = 0;
            if($user_id = $user->validateChash($this->chash)){
                $profile->parent_id = $user_id;
            } else {
                $this->addError('username', UserManagementModule::t('front', 'Could not found related company'));
                return false;
            }
        } else {
            $profile->is_company = 0;
        }
        if(!$profile->save()){
            $this->addError('username', UserManagementModule::t('front', 'Could not create user profile: '.implode(',',$profile->getErrorSummary(true))));
            return false;
        }
        return true;
    }


    /**
     * @param User $user
     *
     * @return bool
     */
    protected function sendConfirmationEmail($user)
    {
        return Yii::$app->mailer->compose(Yii::$app->getModule('user-management')->mailerOptions['registrationFormViewFile'], ['user' => $user])
            ->setFrom(Yii::$app->getModule('user-management')->mailerOptions['from'])
            ->setTo($user->email)
            ->setSubject(UserManagementModule::t('front', 'E-mail confirmation for') . ' ' . Yii::$app->name)
            ->send();
    }

    /**
     * Check received confirmation token and if user found - activate it, set username, roles and log him in
     *
     * @param string $token
     *
     * @return bool|User
     */
    public function checkConfirmationToken($token)
    {
        $user = User::findInactiveByConfirmationToken($token);

        if ( $user )
        {
            $profile = $user->getProfile()->one();
            $is_company = $profile->is_company;
            $is_child = $profile->parent_id;

            $user->username = $user->email;
            if(!$is_company && $is_child){
                $user->status = User::STATUS_ACTIVE;
            }
            $user->email_confirmed = 1;
            $user->removeConfirmationToken();
            $user->save(false);

            $roles = (array)Yii::$app->getModule('user-management')->rolesAfterRegistration;

            foreach ($roles as $type=>$role)
            {
                if($is_company && $type === 'company'){
                    User::assignRole($user->id, $role);
                } elseif($is_child &&  $type === 'user') {
                    User::assignRole($user->id, $role);
                } elseif(is_numeric($type)) {
                    User::assignRole($user->id, $role);
                }
            }
            if((!$is_company && $is_child) || !Yii::$app->getModule('user-management')->needModeration) {
                Yii::$app->user->login($user);
            }

            return $user;
        }

        return false;
    }
}
