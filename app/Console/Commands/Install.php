<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'base-admin:install';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '快速安裝base-admin-api';
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(120);
        
        // 替换数据库信息
        $host = $this->ask('What is your mysql host?', config('database.connections.mysql.host'));
        
        $port = $this->ask('What is your port of mysql?', config('database.connections.mysql.port', '3306'));
        
        $user = $this->ask('What is your username of mysql?', config('database.connections.mysql.username'));
        
        $pass = $this->ask('What is your password of mysql?', config('database.connections.mysql.password'));
        
        $path = $this->envPath();
        
        file_put_contents($path, str_replace([
            'DB_HOST=' . config('database.connections.mysql.host'),
            'DB_PORT=' . config('database.connections.mysql.port'),
            'DB_USERNAME=' . config('database.connections.mysql.username'),
            'DB_PASSWORD=' . config('database.connections.mysql.password'),
        ], [
            'DB_HOST=' . $host,
            'DB_PORT=' . $port,
            'DB_USERNAME=' . $user,
            'DB_PASSWORD=' . $pass
        ], file_get_contents($path)));
        $this->info('保存设置成功.');
        
        // 创建数据库
        $this->info('');
        $this->info('正在创建数据库...');
        Artisan::call('base-admin:create-db', [
            'dbname' => config('database.connections.mysql.database')
        ]);
        $this->info('数据库创建成功.');
        
        // 生成app key
        $this->info('');
        $this->info('正在生成app key...');
        Artisan::call('config:cache');
        Artisan::call('key:generate');
        $this->info('app keys生成成功.');
        
        // 生成jwt secret
        $this->info('');
        $this->info('正在生成jwt secret...');
        Artisan::call('jwt:secret', ['--force' => true]);
        $this->info('jwt secret生成成功.');
        
        // 迁移表结构
        $this->info('');
        $this->info('执行表迁移...');
        Artisan::call('migrate');
        $this->info('表迁移成功.');
        
        // 填充数据
        $this->info('');
        $this->info('执行填充数据...');
        Artisan::call('db:seed');
        $this->info('数据填充成功...');
        
        $this->info('');
        $this->info('base-admin-api安装成功');
    }
    
    /**
     * Get the .env file path.
     *
     * @return string
     */
    protected function envPath() : string
    {
        if (method_exists($this->laravel, 'environmentFilePath')) {
            return $this->laravel->environmentFilePath();
        }
        
        // check if laravel version Less than 5.4.17
        if (version_compare($this->laravel->version(), '5.4.17', '<')) {
            return $this->laravel->basePath() . DIRECTORY_SEPARATOR . '.env';
        }
        
        return $this->laravel->basePath('.env');
    }
}
