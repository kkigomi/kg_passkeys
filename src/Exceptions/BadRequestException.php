<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

class BadRequestException extends BaseException
{
    protected $message = '잘못된 요청입니다';
}
