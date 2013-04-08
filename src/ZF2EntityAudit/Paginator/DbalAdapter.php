<?php

namespace ZF2EntityAudit\Paginator;

use Zend\Paginator\Adapter\AdapterInterface;
use Doctrine\DBAL\Query\QueryBuilder;

class DbalAdapter implements AdapterInterface
{
    protected $query;
    protected $ItemCountPerPage = "10";
    protected $currentPageNumber = "1";

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function getItems($offset, $itemCountPerPage )
    {
        $this->query->setFirstResult($offset)
                ->setMaxResults($itemCountPerPage);

        return $this->query->execute()->fetchAll();
    }

    public function count()
    {
        return count($this->query->execute()->fetchAll());
    }

}
