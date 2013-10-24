<?php

namespace Stormpath\Exception;

/**
 * Functions used to view exception details
 *
 * getMessage
 * getCode
 * getStatus
 * getDeveloperMessage
 * getMoreInfo
 */

class ApiException extends \Exception
{
    protected $status;
    protected $developerMessage;
    protected $moreInfo;

    public function exchangeArray($data)
    {
        $this->setStatus(isset($data['status']) ? $data['status']: null);
        $this->setDeveloperMessage(isset($data['developerMessage']) ? $data['developerMessage']: null);
        $this->setMoreInfo(isset($data['moreInfo']) ? $data['moreInfo']: null);
    }

    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setDeveloperMessage($value)
    {
        $this->developerMessage = $value;
        return $this;
    }

    public function getDeveloperMessage()
    {
        return $this->developerMessage;
    }

    public function setMoreInfo($value)
    {
        $this->moreInfo = $value;
        return $this;
    }

    public function getMoreInfo()
    {
        return $this->moreInfo;
    }

}
