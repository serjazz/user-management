<?php

use serjazz\modules\UserManagement\UserManagementModule;
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
        /**
         * @var $module UserManagementModule
         */
        $module = UserManagementModule::getInstance();
        $tableOptions = null;
        if ( $this->db->driverName === 'mysql' )
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        // Check if user Table exist
        $tablename = $module->user_profile;
        $usertablename = $module->user_table;

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
        $module->addDir($module->photo_path_absolute);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        /**
         * @var $module UserManagementModule
         */
        $module = UserManagementModule::getInstance();
        $files = $module->photo_path_absolute;
        $module->removeFiles($files);
        $tablename = $module->user_profile;
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
