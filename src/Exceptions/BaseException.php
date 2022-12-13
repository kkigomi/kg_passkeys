<?php
namespace Kkigomi\Plugin\Passkeys\Exceptions;

use Exception;
use Kkigomi\Plugin\Passkeys\Member;

abstract class BaseException extends Exception
{
    private ? Member $member = null;

    public function __construct(? Member $member = null)
    {
        $this->member = $member;
    }

    public function memberId(): ?string
    {
        if (!$this->isValid()) {
            return null;
        }

        return $this->member->getId();
    }

    /**
     * @return bool|null
     */
    private function isValid()
    {
        if (!$this->member) {
            return null;
        }

        return $this->member->isValid();
    }

}
