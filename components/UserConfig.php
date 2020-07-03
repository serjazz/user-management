<?php

namespace serjazz\modules\UserManagement\components;

use yii\web\User;
use Yii;

/**
 * Class UserConfig
 * @package serjazz\modules\UserManagement\components
 */
class UserConfig extends User
{

    /**
     * @inheritdoc
     */
    public $identityClass = 'serjazz\modules\UserManagement\models\User';

    /**
     * @inheritdoc
     */
    public $enableAutoLogin = true;

    /**
     * @inheritdoc
     */
    public $cookieLifetime = 2592000;

    /**
     * @inheritdoc
     */
    public $loginUrl = ['/user-management/auth/login'];

    /**
     * Allows to call Yii::$app->user->isSuperadmin
     *
     * @return bool
     */
    public function getIsSuperadmin()
    {
        return @Yii::$app->user->identity->superadmin == 1;
    }

    /**
     * Allows to call Yii::$app->user->timezone
     * @return bool
     */
    public function getTimezone()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->timezone;
        }
        return null;
    }

    /**
     * Allows to call Yii::$app->user->isCompany
     * @return bool
     */
    public function getIsCompany()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->is_company == 1;
        }
        return null;
    }

    /**
     * Allows to call Yii::$app->user->isManager
     * @return bool
     */
    public function getIsManager()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->is_manager == 1;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->username;
        }
        return null;
    }

    /**
     * Allows to call Yii::$app->user->photo
     * @return string
     */
    public function getPhoto()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->photo;
        }
        return null;
    }

    /**
     * Allows to call Yii::$app->user->fullname
     * @return string
     */
    public function getFullname()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return @Yii::$app->user->identity->fullname;
        }
        return null;
    }

    /**
     * Allows to call Yii::$app->user->companyid
     * @return string
     */
    public function getCompanyid()
    {
        if (!Yii::$app->user->getIsGuest()) {
            return Yii::$app->user->identity->company_id;
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        AuthHelper::updatePermissions($identity);

        parent::afterLogin($identity, $cookieBased, $duration);
    }

}
