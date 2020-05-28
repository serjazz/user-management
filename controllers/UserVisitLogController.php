<?php

namespace serjazz\modules\UserManagement\controllers;

use Yii;
use serjazz\modules\UserManagement\models\UserVisitLog;
use serjazz\modules\UserManagement\models\search\UserVisitLogSearch;
use serjazz\components\AdminDefaultController;

/**
 * UserVisitLogController implements the CRUD actions for UserVisitLog model.
 */
class UserVisitLogController extends AdminDefaultController
{
	/**
	 * @var UserVisitLog
	 */
	public $modelClass = 'serjazz\modules\UserManagement\models\UserVisitLog';

	/**
	 * @var UserVisitLogSearch
	 */
	public $modelSearchClass = 'serjazz\modules\UserManagement\models\search\UserVisitLogSearch';

	public $enableOnlyActions = ['index', 'view', 'grid-page-size'];
}
