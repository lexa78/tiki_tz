<?php

class AmqpConnector
{
    private static $amqpConnection = null;

    /**
     * @return AMQPConnection
     */

    public static function getAmqpConnection($login, $password)
    {
        if (is_null(self::$amqpConnection))
        {
            self::$amqpConnection = new AMQPConnection();
            self::$amqpConnection->setLogin($login);
            self::$amqpConnection->setPassword($password);
            self::$amqpConnection->connect();

            if (!self::$amqpConnection->isConnected()) {
                throw new AMQPException('Connection Error!');
            }
        }

        return self::$amqpConnection;
    }

    private function __clone() {}
    private function __construct() {}
}