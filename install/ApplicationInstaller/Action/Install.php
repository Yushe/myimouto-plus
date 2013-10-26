<?php
namespace ApplicationInstaller\Action;

use Rails\ActiveRecord\ActiveRecord;
use ApplicationInstaller\Exception;
use User;
use JobTask;

class Install extends Base
{
    private $adminName;
    
    private $adminPassword;
    
    public function __construct($adminName, $adminPassword)
    {
        $this->adminName     = $adminName;
        $this->adminPassword = $adminPassword;
    }
    
    public function commit()
    {
        $this->createTables();
        $this->insertData();
        $this->setLoginCookies();
        $this->createPublicDataDirs();
        $this->renameIndex();
        $this->deleteInstallFiles();
    }
    
    private function createTables()
    {
        $queries = require $this->root() . '/structure.php';

        foreach ($queries as $query) {
            ActiveRecord::connection()->executeSql($query);
            if ($err = ActiveRecord::lastError()) {
                throw new Exception\RuntimeException(
                    "Error when creating tables.\nQuery:\n$query\nError info:\n" . var_export($err, true)
                );
            }
        }
    }
    
    private function insertData()
    {
        
        # Create admin user
        ActiveRecord::connection()->executeSql(
            'INSERT INTO users (created_at, name, password_hash, level, show_advanced_editing) VALUES (?, ?, ?, ?, ?)',
            date('Y-m-d H:i:s'), $this->adminName, User::sha1($this->adminPassword), 50, 1
        );
        
        ActiveRecord::connection()->executeSql(
            'INSERT INTO user_blacklisted_tags VALUES (?, ?)',
            1, implode("\r\n", CONFIG()->default_blacklists)
        );
        
        # Queries for table_data
        $queries = [
            'INSERT INTO `table_data` VALUES (\'posts\', 0)',
            'INSERT INTO `table_data` VALUES (\'users\', 1)',
            'INSERT INTO `table_data` VALUES (\'non-explicit_posts\', 0)'
        ];
        foreach ($queries as $query) {
            ActiveRecord::connection()->executeSql($query);
        }
        
        # Create JobTasks
        JobTask::create([
            'task_type'    => 'external_data_search',
            'data_as_json' => '{}',
            'status'       => 'pending',
            'repeat_count' => -1
        ]);
        JobTask::create([
            'task_type'    => "upload_batch_posts",
            'data_as_json' => '{}',
            'status'       => "pending",
            'repeat_count' => -1
        ]);
        JobTask::create([
            'task_type'    => "periodic_maintenance",
            'data_as_json' => '{}',
            'status'       => "pending",
            'repeat_count' => -1
        ]);
    }
    
    private function setLoginCookies()
    {
        setcookie('login', $this->adminName, time() + 31556926, '/');
        setcookie('pass_hash', User::sha1($this->adminPassword), time() + 31556926, '/');
    }
    
    private function createPublicDataDirs()
    {
        $dataPath = \Rails::publicPath() . '/data';
        
        $dirs = [
            'avatars',
            'export',
            'image',
            'import',
            'jpeg',
            'preview',
            'sample'
        ];
        
        if (!is_dir($dataPath)) {
            mkdir($dataPath);
        }
        
        foreach ($dirs as $dir) {
            $path = $dataPath . '/' . $dir;
            
            if (!is_dir($path)) {
                mkdir($path);
            }
        }
    }
}
