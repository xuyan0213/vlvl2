<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class GameResetForWeek extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:reset:week';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每星期重置次数';

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
        DB::table('x200901_user')->where('status',2)->update([
            'prize_num' => 1,
            'status' => 0
        ]);   //宜昌中心
        return $this->info('重置成功');
    }
}