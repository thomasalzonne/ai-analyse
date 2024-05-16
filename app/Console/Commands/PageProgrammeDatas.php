<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PageProgrammeDatas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:page-programme-datas {--url=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->option('url');

        $pageProgramme = new PageProgramme();
        $pageProgramme->handle($url, $name);
    }
}
