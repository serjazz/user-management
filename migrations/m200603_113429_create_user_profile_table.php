<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_profile}}`.
 */
class m200603_113429_create_user_profile_table extends Migration
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
        $tablename = \Yii::$app->getModule('user-management')->user_profile;
        $usertablename = \Yii::$app->getModule('user-management')->user_table;

        $this->createTable($tablename, [
            'id' => $this->primaryKey(),
            'user_id'       => $this->integer()->notNull(),
            'lastname'           => $this->string()->notNull(),
            'middlename'           => $this->string()->null(),
            'firstname'           => $this->string()->null(),
            'birthdate'           => $this->integer()->notNull(),
            'photo'           => $this->string()->null(),
            0                => 'FOREIGN KEY (user_id) REFERENCES '.$usertablename.' (id) ON DELETE CASCADE ON UPDATE CASCADE',
        ],$tableOptions);

        // creates index for column `user_id`
        $this->createIndex(
            'idx-profile-user_id',
            $tablename,
            'user_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'fk-profile-user_id',
            $tablename,
            'user_id',
            $usertablename,
            'id',
            'CASCADE',
            'CASCADE'
        );

        //add photo folder
        \Yii::$app->getModule('user-management')->addDir(\Yii::$app->getModule('user-management')->photo_path_absolute);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        \Yii::$app->getModule('user-management')->removeFiles(\Yii::$app->getModule('user-management')->photo_path_absolute);
        $tablename = \Yii::$app->getModule('user-management')->user_profile;
        $this->dropForeignKey(
            'fk-profile-user_id',
            $tablename
        );
        $this->dropIndex(
            'idx-profile-user_id',
            $tablename
        );
        $this->dropTable($tablename);
    }
}
