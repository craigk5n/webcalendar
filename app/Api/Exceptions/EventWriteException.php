<?php

declare(strict_types=1);

namespace WebCalendar\Api\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class EventWriteException extends HttpException
{
  public function __construct(int $status, public readonly string $errorCode, string $message)
  {
    parent::__construct($status, $message);
  }
}
