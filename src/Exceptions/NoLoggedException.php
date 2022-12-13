<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

class NoLoggedException extends BaseException
{
    protected $message = '로그인 상태가 아닙니다';
}
