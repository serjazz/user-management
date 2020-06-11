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
            'parent_id'       => $this->integer()->null(),
            'lastname'           => $this->string()->notNull(),
            'middlename'           => $this->string()->null(),
            'firstname'           => $this->string()->null(),
            'birthdate'           => $this->integer(8)->null(),
            'photo'           => $this->string()->null(),
            'phone'           => $this->integer()->null(),
            'is_company'           => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'company_hash'           => $this->string()->null(),
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

        // creates index for column `parent_id`
        $this->createIndex(
            'idx-profile-parent_id',
            $tablename,
            'parent_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
            'fk-profile-parent_id',
            $tablename,
            'parent_id',
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
            'fk-profile-parent_id',
            $tablename
        );
        $this->dropIndex(
            'idx-profile-parent_id',
            $tablename
        );
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
