<?php
namespace Intaro\MemcachedTagsBundle\Doctrine\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class AssociationCache extends Annotation
{
    /**
     * @var integer
     */
    public $lifetime = 0;

    /**
     * @var array
     */
    public $tags = array();
}
