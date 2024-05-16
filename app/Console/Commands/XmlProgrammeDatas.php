<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\XmlProgramme;

class XmlProgrammeDatas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:xml-programme-datas {--url=} {--name=}';

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
        $name = $this->option('name');

        $xmlContent = file_get_contents($url);

        $xmlProgramme = new XmlProgramme();
        $xmlProgramme->generateSummary($xmlContent, $name);
    }
}
