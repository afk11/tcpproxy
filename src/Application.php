<?php declare(strict_types=1);


namespace Afk11\TcpProxy;


class Application
{
    public function run(int $argc, array $argv)
    {
        if ($argc < 2) {
            fwrite(STDERR, "Server URI missing\n");
            exit(1);
        } else if ($argc < 3) {
            fwrite(STDERR, "Target URI missing\n");
            exit(1);
        }
        $loop = \React\EventLoop\Factory::create();
        $serverUri = $argv[1];
        $targetUri = $argv[2];
        $proxy = new TcpProxy($loop, $serverUri, $targetUri);
        $loop->run();
    }
}