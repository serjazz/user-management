<?php

namespace serjazz\modules\UserManagement\controllers;

use serjazz\modules\UserManagement\UserManagementModule;
use serjazz\modules\UserManagement\components\AdminDefaultController;
use serjazz\modules\UserManagement\models\forms\InviteForm;
use serjazz\modules\UserManagement\models\forms\RegistrationForm;
use serjazz\modules\UserManagement\models\UserProfile;
use Yii;
use serjazz\modules\UserManagement\models\User;
use serjazz\modules\UserManagement\models\search\UserSearch;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends AdminDefaultController
{
    /**
     * @var User
     */
    public $modelClass = 'serjazz\modules\UserManagement\models\User';

    /**
     * @var UserProfile
     */
    public $profileClass = 'serjazz\modules\UserManagement\models\UserProfile';

    /**
     * @var UserSearch
     */
    public $modelSearchClass = 'serjazz\modules\UserManagement\models\search\UserSearch';

    /**
     * @return mixed|string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User(['scenario'=>'newUser']);
        $profile = new UserProfile();
        if ( $model->load(Yii::$app->request->post()) && $model->validate() )
        {
            $post = Yii::$app->request->post();
            unset($post['UserProfile']['photo']);
            if($profile->load($post) && $model->validate()){
                $model->save(false);
                $profile->user_id = $model->id;
                $photo = UploadedFile::getInstance($profile, 'photo');
                if($photo){
                    $profile->photo = $photo;
                }
                if($profile->save(false)) {
                    $redirect = $this->getRedirectPage('update', $model);
                    return $redirect === false ? '' : $this->redirect($redirect);
                }
            }
            //return $this->redirect(['view',	'id' => $model->id]);
        }

        return $this->renderIsAjax('create', compact('model','profile'));
    }

    /**
     * @param $id
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionInvite($id){
        $user = $this->findModel($id);
        $profile = $user->getProfile()->one();
        $model = new InviteForm();
        if ( $model->load(Yii::$app->request->post()) && $model->validate() && $model->sendInvite($profile))
        {
            Yii::$app->session->setFlash('success', UserManagementModule::t('back', 'Users was being invited'));
            return $this->redirect(['view',	'id' => $user->id]);
        }
        return $this->renderIsAjax('invite', compact('model'));
    }

    /**
     * @param int $id User ID
     *
     * @throws \yii\web\NotFoundHttpException
     * @return string
     */
    public function actionChangePassword($id)
    {
        $model = User::findOne($id);

        if ( !$model )
        {
            throw new NotFoundHttpException('User not found');
        }

        $model->scenario = 'changePassword';

        if ( $model->load(Yii::$app->request->post()) && $model->save() )
        {
            return $this->redirect(['view',	'id' => $model->id]);
        }

        return $this->renderIsAjax('changePassword', compact('model'));
    }

}
