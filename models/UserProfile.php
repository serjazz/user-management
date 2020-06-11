<?php


namespace serjazz\modules\UserManagement\models;
use serjazz\modules\UserManagement\UserManagementModule;
use Yii;

/**
 * This is the model class for table "user_profile".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $user_id
 * @property string $lastname
 * @property string|null $middlename
 * @property string|null $firstname
 * @property int $birthdate
 * @property string|null $photo
 * @property int $is_company
 * @property int $phone
 * @property string|null $company_hash
 *
 * @property User $user
 */
class UserProfile extends \yii\db\ActiveRecord
{
    /**
     * User full name
     *
     * @var string
     */
    public $fullname;
    /**
     * User avatar
     *
     * @var string
     */
    public $user_photo;
    /**
     * Formatted user phone as (999) 999 - 9999
     *
     * @var string
     */
    public $phone_beautyfy;
    /**
     * If user is not a company and have relation with company
     *
     * @var string
     */
    public $work_place;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'lastname'], 'required'],
            [['user_id', 'parent_id', 'birthdate', 'is_company', 'phone'], 'integer'],
            [['lastname', 'middlename', 'firstname', 'photo', 'company_hash'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        $attrLabels = [
            'id' => 'ID',
            'user_id' => UserManagementModule::t('back', 'User'),
            'parent_id' => UserManagementModule::t('back', 'Parent'),
            'fullname' => UserManagementModule::t('back', 'FIO'),
            'lastname' => UserManagementModule::t('back', 'Lastname'),
            'middlename' => UserManagementModule::t('back', 'Middlename'),
            'firstname' => UserManagementModule::t('back', 'Firstname'),
            'birthdate' => UserManagementModule::t('back', 'Birthdate'),
            'photo' => UserManagementModule::t('back', 'Photo'),
            'phone' => UserManagementModule::t('back', 'Phone'),
            'is_company' => UserManagementModule::t('back', 'Is company'),
            'company_hash' => UserManagementModule::t('back', 'Company hash'),
        ];
        if($this->is_company){
            $attrLabels['lastname'] = UserManagementModule::t('back', 'Company');
            $attrLabels['photo'] = UserManagementModule::t('back', 'Logo');
        }
        return $attrLabels;
    }

    public function afterFind()
    {
        $this->userPhoto();
        $this->compileFullname();
        $this->workPlace();
        parent::afterFind();
    }

    /**
     * Get work place
     */
    private function workPlace(){
        if(!$this->is_company && $this->parent_id){
            $this->work_place = self::find()->where('user_id = :parent',[':parent' => $this->parent_id])->one()->lastname;
        }
    }

    /**
     * Beautyfy phone number
     */
    private function beautyfyPhone(){
        if($this->phone){
            $code = \mb_substr($this->phone,0,3);
            $first_part = \mb_substr($this->phone,2,3);
            $second_part = \mb_substr($this->phone,5,4);
            $this->phone_beautyfy = '('.$code.') '.$first_part.' - '.$second_part;
        }
    }

    /**
     * Get user avatar path
     */
    private function userPhoto(){
        if(!$this->photo){
            $this->photo = 'default_profile.jpg';
        }
        $this->user_photo = Yii::$app->getModule('user-management')->photo_path.'/'.$this->photo;
    }

    /**
     * Compile user full name
     */
    private function compileFullname(){
        if($this->is_company && $this->lastname){
            $this->fullname .=  $this->lastname;
        } else {
            if($this->firstname){
                $this->fullname .=  $this->firstname.' ';
            }
            if($this->middlename){
                $this->fullname .=  $this->middlename.' ';
            }
            if($this->lastname){
                $this->fullname .=  $this->lastname;
            }
        }
    }

    /**
     * Gets query for [[User]].
     *
     * @return User
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return User
     */
    public function getParentUser()
    {
        return $this->hasOne(User::className(), ['id' => 'parent_id']);
    }
}