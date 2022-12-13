<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Kkigomi\Plugin\Passkeys\Member;

class TimeoutTemporalAuthSessionException extends BaseException
{
    protected $message = '인증 세션이 만료됨';
}
