<?php

namespace App\Console\Commands;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Console\Command;
use Storage;

class BackupLogToStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:logdb {--truncate} {--table=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup and Truncate Log Database to s3';

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
        $name = 'alltable';
        $tables = $this->option('table');

        foreach ($tables as $table) {
            if ($table == '*') {
                $table = '';
            }

            if ($table && \DB::connection('mysql2')->table($table)->count() < 1) {
                return;
            }

            $filename = date('YmdHis') . '_' . ($table ?: 'alltable') . '.sql';
            $backupFileUC = storage_path('app/' . $filename);

            $dbUser = env('DB2_USERNAME');
            $dbHost = env('DB2_HOST');
            $dbPassword = env('DB2_PASSWORD');
            $dbName = env('DB2_DATABASE');

            $dbPassword = $dbPassword ? '-p'.$dbPassword : '';

            $mysql_dump_command= "mysqldump -v -u{$dbUser} -h{$dbHost} {$dbPassword} {$dbName} {$table} >  \"$backupFileUC\"";
            $gzip_command= "gzip -9 -f \"$backupFileUC\"";

            $run_mysql= Process::fromShellCommandline($mysql_dump_command);
            $run_mysql->mustRun(); 
            $gzip_process= Process::fromShellCommandline($gzip_command);
            $gzip_process->mustRun();

            Storage::put('_backup_dblog/' . $filename . '.gz', file_get_contents($backupFileUC . '.gz') , 'private');
            unlink($backupFileUC . '.gz');

            if ($this->option('truncate') && $table) {
                \DB::connection('mysql2')->table($table)->truncate();
            }
        }
    }
}
