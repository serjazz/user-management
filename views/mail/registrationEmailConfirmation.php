<?php
/**
 * @var $this yii\web\View
 * @var $user serjazz\modules\UserManagement\models\User
 */

use serjazz\modules\UserManagement\UserManagementModule;
use yii\helpers\Html;

?>
<?php
$returnUrl = Yii::$app->user->returnUrl == Yii::$app->homeUrl ? null : rtrim(Yii::$app->homeUrl, '/') . Yii::$app->user->returnUrl;

$confirmLink = Yii::$app->urlManager->createAbsoluteUrl(['/user-management/auth/confirm-registration-email', 'token' => $user->confirmation_token, 'returnUrl'=>$returnUrl]);
?>
<?= UserManagementModule::t('back', 'Hello')?>, <?= UserManagementModule::t('back', 'you have been registered on')?> <?= Yii::$app->urlManager->hostInfo ?>
    <br/><br/>
<?= UserManagementModule::t('back', 'follow this link to')?> <?= UserManagementModule::t('back', 'confirm your email')?> <?= UserManagementModule::t('back', 'and activate account')?>:
<?= Html::a('confirm E-mail', $confirmLink) ?>