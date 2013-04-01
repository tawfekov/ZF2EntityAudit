<?php

namespace ZF2EntityAudit\Utils;

/**
 * To add a comment to a revision fetch this object before flushing
 * and set the comment.  The comment will be fetched by the revision
 * and reset after reading
 */

class RevisionComment
{
    private $comment;

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
}