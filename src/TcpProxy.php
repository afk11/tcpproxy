<?php declare(strict_types=1);


namespace Afk11\TcpProxy;


use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use React\Socket\Server;
use React\Socket\ServerInterface;

class TcpProxy
{
    private string $targetUri;
    private LoopInterface $loop;
    private ServerInterface $server;
    private ConnectorInterface $connector;
    /**
     * @var ConnectionInterface[]
     */
    private array $clients = [];

    public function __construct(LoopInterface $loop, string $serverUri, string $targetUri)
    {
        $this->loop = $loop;
        $this->connector = new Connector($loop);
        $this->targetUri = $targetUri;
        $this->server = new Server($serverUri, $loop);
        $this->server->on('connection', function (ConnectionInterface $connection) {
            if (null === $connection->getRemoteAddress()) {
                throw new \RuntimeException("can't expect me to work under these conditions - remote address was null on incoming connection");
            } else if (array_key_exists($connection->getRemoteAddress(), $this->clients)) {
                $connection->close();
            }
            $this->clients[$connection->getRemoteAddress()] = $connection;
        });
        $this->connectTarget();
    }

    public function connectTarget() {
        $this->connector->connect($this->targetUri)->then(function (ConnectionInterface $connection) {
            $connection->on('data', function (string $data) {
                foreach ($this->clients as $client) {
                    $client->write($data);
                }
            });
            $connection->on('close', function () {
                $this->loop->addTimer(5, function () {
                    $this->connectTarget();
                });
            });
        });
    }
}