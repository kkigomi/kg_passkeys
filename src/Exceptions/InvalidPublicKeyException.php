<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Kkigomi\Plugin\Passkeys\Member;

class InvalidPublicKeyException extends BaseException
{
    protected $message = '등록하지 않았거나 제거된 패스키입니다.';
}
