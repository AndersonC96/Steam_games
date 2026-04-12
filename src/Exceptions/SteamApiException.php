<?php

declare(strict_types=1);

namespace Anderson\SteamGames\Exceptions;

use Exception;

class SteamApiException extends Exception {}
class UserNotFoundException extends SteamApiException {}
class PrivateProfileException extends SteamApiException {}
class ApiLimitExceededException extends SteamApiException {}
