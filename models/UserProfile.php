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
 * @property int $firstday
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
     * @var string
     */
    public $fullname_short;
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
            [['lastname'], 'required'],
            [['user_id', 'parent_id', 'birthdate', 'is_company', 'remove_photo', 'firstday'], 'integer'],
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
     * return first day of week
     * @@param bool $short
     * @return array
     */
    public function firstDaysList($short=false){
        if($short){
            return [
                0=>UserManagementModule::t('back', 'Sun'),
                1=>UserManagementModule::t('back', 'Mon'),
                2=>UserManagementModule::t('back', 'Tue'),
                3=>UserManagementModule::t('back', 'Wed'),
                4=>UserManagementModule::t('back', 'Thu'),
                5=>UserManagementModule::t('back', 'Fri'),
                6=>UserManagementModule::t('back', 'Sat'),
            ];
        }
        return [
            0=>UserManagementModule::t('back', 'Sunday'),
            1=>UserManagementModule::t('back', 'Monday'),
            2=>UserManagementModule::t('back', 'Tuesday'),
            3=>UserManagementModule::t('back', 'Wednesday'),
            4=>UserManagementModule::t('back', 'Thursday'),
            5=>UserManagementModule::t('back', 'Friday'),
            6=>UserManagementModule::t('back', 'Saturday'),
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
            'firstday' => UserManagementModule::t('back', 'First day of week'),
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
        if($this->birthdate){

            $birthdate = \DateTime::createFromFormat('U', $this->birthdate);
            $this->birthdate =  $birthdate->format('d.m.Y');
        }
        $this->userPhoto();
        $this->compileFullname();
        $this->workPlace();
        $this->beautyfyPhone();
        $this->old_profile = $this;
        parent::afterFind();
    }

    public function beforeValidate()
    {
        if($this->birthdate){
            $birthdate = new \DateTime($this->birthdate);
            $this->birthdate =  $birthdate->format('U');
        }
        return parent::beforeValidate();
    }

    public function beforeSave($insert)
    {
        if(Yii::$app->user->isCompany){
            $this->parent_id = Yii::$app->user->id;
        }
        if(is_object($this->photo)){
            if($this->old_profile && $this->old_profile->photo && $this->isPhotoExist($this->old_profile->photo)){
                $this->removePhoto($this->old_profile->photo);
            }
            $name = $this->generatePhotoName($this->photo->baseName). '.'. $this->photo->extension;
            $this->photo->saveAs(Yii::$app->getModule('user-management')->photo_path_absolute.'/'. $name);
            $this->photo = $name;
        } elseif($this->old_profile && $this->remove_photo && $this->old_profile->photo && $this->isPhotoExist($this->old_profile->photo)){
            $this->removePhoto($this->old_profile->photo);
            $this->photo = null;
        }
        return parent::beforeSave($insert);
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
            $this->fullname_short = $this->lastname;
        } else {
            if($this->lastname){
                $this->fullname .=  $this->lastname.' ';
                $this->fullname_short .= $this->lastname.' ';
            }
            if($this->firstname){
                $this->fullname .=  $this->firstname.' ';
                $first_symb = mb_substr($this->firstname,0,1);
                $this->fullname_short .= static::mb_ucfirst($first_symb).'.';
            }
            if($this->middlename){
                $this->fullname .=  $this->middlename;
                $first_symb = mb_substr($this->middlename,0,1);
                $this->fullname_short .= static::mb_ucfirst($first_symb).'.';
            }

        }
    }

    /**
     * Analog of ucfirst() function for multibyte literals
     * @param $string
     * @param string $enc
     * @return string
     */
    public static function mb_ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
            mb_substr($string, 1, mb_strlen($string, $enc), $enc);
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