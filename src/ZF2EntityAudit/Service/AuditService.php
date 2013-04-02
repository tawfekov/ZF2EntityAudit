<?php

namespace ZF2EntityAudit\Service;

use Zend\View\Helper\AbstractHelper
    , ZF2EntityAudit\Entity\AbstractAudit
    ;

class AuditService extends AbstractHelper
{
    private $comment;

    /**
     * To add a comment to a revision fetch this object before flushing
     * and set the comment.  The comment will be fetched by the revision
     * and reset after reading
     */
    public function getComment()
    {
        $comment = $this->comment;
        $this->comment = null;

        return $comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
        return $this;
    }

    public function getEntityValues($entity, $cleanRevison = false) {
        $em = \ZF2EntityAudit\Module::getServiceManager()
            ->get('doctrine.entitymanager.orm_default');

        $metadata = $em->getClassMetadata(get_class($entity));
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        ksort($return);

        return $return;
    }

    public function getEntityIdentifierValues($entity, $cleanRevision = false)
    {
        $entityManager = \ZF2EntityAudit\Module::getServiceManager()->get('doctrine.entitymanager.orm_default');
        $metadataFactory = $entityManager->getMetadataFactory();

        // Get entity metadata - Audited entities will always have composite keys
        $metadata = $metadataFactory->getMetadataFor(get_class($entity));
        $values = $metadata->getIdentifierValues($entity);

        if ($cleanRevision and $values['revision'] instanceof \ZF2EntityAudit\Entity\Revision) {
            unset($values['revision']);
        }

        foreach ($values as $key => $val) {
            if (gettype($val) == 'object') $values[$key] = $val->getId();
        }

        return $values;
    }

    /**
     * Pass an audited entity or the audit entity
     * and return a collection of RevisionEntity s
     * for that record
     */
    public function getRevisionEntities($entity)
    {
        $entityManager = \ZF2EntityAudit\Module::getServiceManager()->get('doctrine.entitymanager.orm_default');

        if (gettype($entity) != 'string' and in_array(get_class($entity), \ZF2EntityAudit\Module::getServiceManager()->get('auditModuleOptions')->getAuditedEntityClasses())) {
            $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', get_class($entity));
            $identifiers = $this->getEntityIdentifierValues($entity);
        } elseif ($entity instanceof AbstractAudit) {
            // In order to get a list of revison entites when a target has been deleted allow either/or
            // target/audit entity to be passed
            $auditEntityClass = get_class($entity);
            $identifiers = $this->getEntityIdentifierValues($entity, true);
        } else {
            $auditEntityClass = 'ZF2EntityAudit\\Entity\\' . str_replace('\\', '_', $entity);
        }

        $search = array('auditEntityClass' => $auditEntityClass);
        if (isset($identifiers)) $search['entityKeys'] = serialize($identifiers);

        return $entityManager->getRepository('ZF2EntityAudit\\Entity\\RevisionEntity')
            ->findBy($search, array('id' => 'DESC'));
    }
}