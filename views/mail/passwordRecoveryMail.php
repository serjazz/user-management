<?php
/**
 * @var $this yii\web\View
 * @var $user serjazz\modules\UserManagement\models\User
 */

use serjazz\modules\UserManagement\UserManagementModule;
use yii\helpers\Html;

?>
<?php
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['/user-management/auth/password-recovery-receive', 'token' => $user->confirmation_token]);
?>

<?= UserManagementModule::t('back', 'Hello')?> <?= Html::encode($user->username) ?>, <?= UserManagementModule::t('back', 'follow this link to')?> <?= UserManagementModule::t('back', 'reset your password')?>:
<?= Html::a('Reset password', $resetLink) ?>