<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Kkigomi\Plugin\Passkeys\Member;

class NotMatchedUserhandleException extends BaseException
{
    protected $message = '잘못된 요청입니다. 패스키를 잘 선택했는지 확인하세요.';
}
