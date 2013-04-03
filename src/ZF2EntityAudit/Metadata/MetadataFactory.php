<?php

namespace ZF2EntityAudit\Metadata;

class MetadataFactory
{

    private $auditedEntities = array();

    public function __construct($auditedEntities)
    {
        $this->auditedEntities = array_flip($auditedEntities);
    }

    public function isAudited($entity)
    {
        return isset($this->auditedEntities[$entity]);
    }

    public function getAllClassNames()
    {
        return array_flip($this->auditedEntities);
    }

}
