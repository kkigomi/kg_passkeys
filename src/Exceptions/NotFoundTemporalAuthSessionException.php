<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Kkigomi\Plugin\Passkeys\Member;

class NotFoundTemporalAuthSessionException extends BaseException
{
    protected $message = '잘못된 접근입니다';
}
