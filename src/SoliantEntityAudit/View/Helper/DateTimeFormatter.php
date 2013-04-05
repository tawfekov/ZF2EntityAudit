<?php
namespace SoliantEntityAudit\View\Helper ;


use Zend\Http\Request;
use Zend\View\Helper\AbstractHelper;


class DateTimeFormatter extends AbstractHelper
{

    protected $format;

    public function setDateTimeFormat($format = "r")
    {
        $this->format = $format ;
        return $this ;
    }

    public function __invoke(\DateTime $datetime)
    {
        return $datetime->format($this->format);
    }
}
