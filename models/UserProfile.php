<?php


namespace serjazz\modules\UserManagement\models;
use serjazz\modules\UserManagement\UserManagementModule;
use yii\helpers\ArrayHelper;
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
 * @property string|null $timezone
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
     * @var int
     */
    public $remove_photo = 0;

    /**
     * @var UserProfile|null
     */
    public $old_profile;

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
            [['lastname', 'middlename', 'firstname', 'phone'], 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            [['user_id', 'lastname'], 'required'],
            [['user_id', 'parent_id', 'birthdate', 'is_company', 'remove_photo'], 'integer'],
            [['lastname', 'middlename', 'firstname', 'company_hash'], 'string', 'max' => 255],
            [['timezone'], 'string', 'max' => 150],
            [['phone'], 'string', 'max' => 11],
            [['fullname'], 'safe'],
            [['photo'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg'],
            ['phone', 'filter', 'filter' => [$this, 'normalizePhone']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * normalize phone value
     * @param string $value
     * @return mixed
     */
    public function normalizePhone($value) {
        $value = (int)$value;
        if(($len = mb_strlen($value)) > 10 && (int)mb_substr($value,0,1) === 8){
            $value = '7'.mb_strcut($value,1,($len-1));
        }
        return (string)$value;
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
            'remove_photo' => UserManagementModule::t('back', 'Remove photo'),
            'timezone' => UserManagementModule::t('back', 'Timezone'),
        ];
        if($this->is_company){
            $attrLabels['lastname'] = UserManagementModule::t('back', 'Company');
            $attrLabels['photo'] = UserManagementModule::t('back', 'Logo');
            $attrLabels['remove_photo'] = UserManagementModule::t('back', 'Remove logo');

        }
        return $attrLabels;
    }

    public function afterFind()
    {
        $this->userPhoto();
        $this->compileFullname();
        $this->workPlace();
        $this->beautyfyPhone();
        $this->old_profile = $this;
        parent::afterFind();
    }

    public function afterValidate()
    {
        if(is_object($this->photo)){
            if($this->old_profile->photo && $this->isPhotoExist($this->old_profile->photo)){
                $this->removePhoto($this->old_profile->photo);
            }
            $name = $this->generatePhotoName($this->photo->baseName). '.'. $this->photo->extension;
            $this->photo->saveAs(Yii::$app->getModule('user-management')->photo_path_absolute.'/'. $name);
            $this->photo = $name;
        } elseif($this->remove_photo && $this->old_profile->photo && $this->isPhotoExist($this->old_profile->photo)){
            $this->removePhoto($this->old_profile->photo);
            $this->photo = null;
        }
        return parent::beforeValidate();
    }

    /**
     * Remove old photo
     * @param $photo
     * @return bool
     */
    private function removePhoto($photo){
        return unlink(Yii::$app->getModule('user-management')->photo_path_absolute.'/'. $photo);
    }

    /**
     * Is photo exist
     * @param $photo
     * @return bool
     */
    private function isPhotoExist($photo){
        return file_exists(Yii::$app->getModule('user-management')->photo_path_absolute.'/'. $photo);
    }

    /**
     * generate photo name
     * @return string
     */
    public function generatePhotoName($name){
        return md5($name.time().'bGhayU');
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
            $fchar = \mb_substr($this->phone,0,1);
            $code = \mb_substr($this->phone,1,3);
            $first_part = \mb_substr($this->phone,4,3);
            $second_part = \mb_substr($this->phone,7);
            $this->phone_beautyfy = $fchar.' ('.$code.') '.$first_part.' - '.$second_part;
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

    /**
     * Get name of proile (company name or FIO)
     * @param $id
     * @return string
     */
    public static function getNameById($id){
        return static::findOne($id)->fullname;
    }

    /**
     * Company list
     * @return mixed
     */
    public static function getCompanies(){
        $where = 'is_company = :company';
        $param = [':company'=>1];
        $model = static::find()->where($where,$param)->all();
        return ArrayHelper::map($model,'user_id','lastname');
    }

    /**
     * User list
     * @param int $company_id
     * @param bool $prompt
     * @return mixed
     */
    public static function getUsers($company_id = 0,$prompt=false){
        $where = 'parent_id IS NOT NULL';
        $param = [];
        if(!Yii::$app->user->isSuperadmin){
            $where = 'parent_id = :parent_id';
            $param = [':parent_id'=>Yii::$app->user->companyid];
        }elseif($company_id){
            $where = 'parent_id = :parent_id';
            $param = [':parent_id'=>(int)$company_id];
        }
        $modelAr = static::find()->where($where,$param)->all();
        $mapAr = [];
        if($prompt){
            $mapAr = [0=>UserManagementModule::t('back', 'Select the user')];
        }
        if($modelAr){
            foreach($modelAr as $model){
                $id = ArrayHelper::getValue($model, 'user_id');
                $fullname = $model->fullname;
                $mapAr[$id] = $fullname;
            }
        }
        return $mapAr;
    }

    /**
     * return list of all timzones
     * @return array
     */
    public static function timezones(){
        $tznAr = \DateTimeZone::listIdentifiers();
        $timezones = [];
        foreach ($tznAr as $value){
            $timezones[$value] = $value;
        }
        return $timezones;
    }
}