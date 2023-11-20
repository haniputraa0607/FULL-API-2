<?php

namespace App\Console\Commands;

use App\Lib\MyHelper;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Storage;
use Symfony\Component\Process\Process;
 
use League\Flysystem\MountManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
 
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupToStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'file:dailyBackupOnS3';
 
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copy DB backup file to Amazon S3 daily ';
 
    /**
     * Create a new command instance.
     *
     * @return void
    */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $log = MyHelper::logCron('Backup Database');
        try {
                $dbUser = config('database.connections.mysql.username');
                $dbPassword = config('database.connections.mysql.password');
                $dbHost = config('database.connections.mysql.host');
                $dbName = config('database.connections.mysql.database');
         
                $dbBackupFolder = storage_path('app/backup/'); //local folder for backup file
                
                $dbBackupFile =  $dbBackupFolder ."/dbbackup.sql";    //uncompressed file
         
                $dbBackupFileCompressed = $dbBackupFolder .'/dbbackup.sql.gz';               //compressed file
         
                $localFilePath= $dbBackupFile; //local backup file
         
                $s3Folder=   env('S3_BUCKET_BACKUP');  //folder or path  for s3 file
         
                //system command to backup database
         
                $mysql_command = "mysqldump -v -u{$dbUser} -h{$dbHost} -p{$dbPassword}   {$dbName} >  $dbBackupFileUC";
         
                $gzip_command = "gzip -9 -f $dbBackupFileUC";
         
                try {
                      $process_mysql = Process::fromShellCommandline($mysql_command);  
                      $process_mysql->mustRun();
         
                      $process_gzip = Process::fromShellCommandline($gzip_command);
                      $process_gzip->mustRun();
              } catch (ProcessFailedException $exception) {
                      $errorOcurred= $exception->getMessage();         
                      return $this->error($errorOcurred);
              }
         
              //check if file exists in local       
                      $fileFound = file_exists($dbBackupFile);
         
                      if($fileFound == true){
                         $copyTos3= Storage::disk('s3')->put($s3Folder .'/dbbackup.' .'.sql.gz', file_get_contents($dbBackupFile));
             }
            $log->success("");
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }
}