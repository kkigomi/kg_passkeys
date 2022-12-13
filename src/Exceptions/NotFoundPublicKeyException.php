<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Kkigomi\Plugin\Passkeys\Member;

class NotFoundPublicKeyException extends BaseException
{
    protected $message = '해당 계정에 등록된 패스키가 없습니다';
}
