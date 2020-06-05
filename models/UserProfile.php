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
 * @property string|null $company_hash
 *
 * @property User $user
 */
class UserProfile extends \yii\db\ActiveRecord
{
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
            [['user_id', 'parent_id', 'birthdate', 'is_company'], 'integer'],
            [['lastname', 'middlename', 'firstname', 'photo', 'company_hash'], 'string', 'max' => 255],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => UserManagementModule::t('back', 'User'),
            'parent_id' => UserManagementModule::t('back', 'Parent'),
            'lastname' => UserManagementModule::t('back', 'Lastname'),
            'middlename' => UserManagementModule::t('back', 'Middlename'),
            'firstname' => UserManagementModule::t('back', 'Firstname'),
            'birthdate' => UserManagementModule::t('back', 'Birthdate'),
            'photo' => UserManagementModule::t('back', 'Photo'),
            'is_company' => UserManagementModule::t('back', 'Is company'),
            'company_hash' => UserManagementModule::t('back', 'Company hash'),
        ];
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