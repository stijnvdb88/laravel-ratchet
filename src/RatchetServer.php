<?php

namespace Askedio\LaravelRatchet;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use GrahamCampbell\Throttle\Facades\Throttle;

abstract class RatchetServer implements MessageComponentInterface
{
    /**
     * Clients.
     *
     * @var [type]
     */
    protected $clients;

    /**
     * Console.
     *
     * @var [type]
     */
    protected $console;

    /**
     * Total connections.
     * @var [type]
     */
    protected $connections;

    /**
     * Current connection.
     * @var ConnectionInterface
     */
    protected $conn;

    /**
     * Throttled.
     * @var [type]
     */
    protected $throttled = false;

    /**
     * Set clients and console.
     *
     * @param [type] $console [description]
     */
    public function __construct($console)
    {
        $this->clients = new \SplObjectStorage();
        $this->console = $console;
    }

    /**
     * Perform action on open.
     *
     * @param ConnectionInterface $conn [description]
     *
     * @return [type] [description]
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $this->conn = $conn;

        $this->attach()->throttle()->limit();

        event('ratchetOpen', $this->conn);
    }

    private function attach()
    {
        $this->clients->attach($this->conn);
        $this->console->info(sprintf('Connected: %d', $this->conn->resourceId));

        $this->connections = count($this->clients);
        $this->console->info(sprintf('%d %s', $this->connections, str_plural('connection', $this->connections)));

        return $this;
    }

    /**
     * Throttle connections.
     * @return [type] [description]
     */
    private function throttle()
    {
        if ($this->isThrottled($this->conn, 'onOpen')) {
            $this->console->info(sprintf('Connection throttled: %d', $this->conn->resourceId));
            $this->conn->send(trans('ratchet::messages.toManyConnectionAttempts'));
            $this->throttled = true;
            $this->conn->close();
        }

        return $this;
    }

    /**
     * Limit connections.
     * @return [type] [description]
     */
    private function limit()
    {
        if ($connectionLimit = config('ratchet.connectionLimit') && $this->connections - 1 >= $connectionLimit) {
            $this->console->info(sprintf('To many connections: %d of %d', $this->connections - 1, $connectionLimit));
            $this->conn->send(trans('ratchet::messages.toManyConnections'));
            $this->conn->close();
        }

        return $this;
    }

    /**
     * Check if the called function is throttled.
     * @param  [type]  $conn    [description]
     * @param  [type]  $setting [description]
     * @return boolean          [description]
     */
    private function isThrottled($conn, $setting)
    {
        $connectionThrottle = explode(':', config(sprintf('ratchet.throttle.%s', $setting)));

        return !Throttle::attempt([
          'ip' => $conn->remoteAddress,
          'route' => $setting,
        ], $connectionThrottle[0], $connectionThrottle[1]);
    }

    /**
     * Perform action on message.
     *
     * @param ConnectionInterface $conn  [description]
     * @param [type]              $input [description]
     *
     * @return [type] [description]
     */
    public function onMessage(ConnectionInterface $conn, $input)
    {
        $this->console->comment(sprintf('Message from %d: %s', $conn->resourceId, $input));
        event('ratchetMessage', [$conn, $input]);

        if ($this->isThrottled($conn, 'onMessage')) {
            $this->console->info(sprintf('Message throttled: %d', $conn->resourceId));
            $this->send($conn, trans('ratchet::messages.toManyMessages'));
            $this->throttled = true;

            if (config('ratchet.abortOnMessageThrottle')) {
                $this->abort($conn);
            }
        }

    }

    /**
     * Perform action on close.
     *
     * @param ConnectionInterface $conn [description]
     *
     * @return [type] [description]
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $this->console->error(sprintf('Disconnected: %d', $conn->resourceId));
        event('ratchetClose', $conn);
    }

    /**
     * Perform action on error.
     *
     * @param ConnectionInterface $conn      [description]
     * @param Exception           $exception [description]
     *
     * @return [type] [description]
     */
    public function onError(ConnectionInterface $conn, \Exception $exception)
    {
        $message = $exception->getMessage();
        $conn->close();
        $this->console->error(sprintf('Error: %s', $message));
        event('ratchetError', [$conn, $message]);
    }

    /**
     * Close the current connection.
     *
     * @return [type] [description]
     */
    public function abort(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        $conn->close();
    }

    /**
     * Send a message to the current connection.
     *
     * @param [type] $message [description]
     *
     * @return [type] [description]
     */
    public function send(ConnectionInterface $conn, $message)
    {
        $conn->send($message);
    }

    /**
     * Send a message to all connections.
     *
     * @param [type] $message [description]
     *
     * @return [type] [description]
     */
    public function sendAll($message)
    {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }
}
