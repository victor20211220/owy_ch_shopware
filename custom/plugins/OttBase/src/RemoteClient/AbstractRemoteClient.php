<?php declare(strict_types=1);

namespace Ott\Base\RemoteClient;

abstract class AbstractRemoteClient
{
    protected const ERROR_SERVER_CONNECTION = 'Connection to server could not be established';
    protected const ERROR_USER_CREDENTIALS = 'FTP user data invalid';
    protected const ERROR_MISSING_USER_CREDENTIALS = 'FTP user data incomplete';

    /** @var resource */
    protected $connection;
}
