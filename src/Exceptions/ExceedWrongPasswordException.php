<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

class ExceedWrongPasswordException extends BaseException
{
    protected $message = '비밀번호가 틀렸습니다';
}
