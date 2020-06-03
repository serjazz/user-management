<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_relation}}`.
 */
class m200603_113429_create_user_relation_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ( $this->db->driverName === 'mysql' )
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        // Check if user Table exist
        $tablename = \Yii::$app->getModule('user-management')->relation_user_table;

        $this->createTable($tablename, [
            'id' => $this->primaryKey(),
            'parent_id'       => 'int',
            'user_id'        => 'int',
            0                => 'FOREIGN KEY (parent_id) REFERENCES '.Yii::$app->getModule('user-management')->user_table.' (id) ON DELETE CASCADE ON UPDATE CASCADE',
            1                => 'FOREIGN KEY (user_id) REFERENCES '.Yii::$app->getModule('user-management')->user_table.' (id) ON DELETE CASCADE ON UPDATE CASCADE',
        ],$tableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $tablename = \Yii::$app->getModule('user-management')->relation_user_table;
        $this->dropTable($tablename);
    }
}
