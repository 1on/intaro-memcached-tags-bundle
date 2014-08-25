<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Intaro\MemcachedTagsBundle\Doctrine\ORM;

use Doctrine\ORM\UnitOfWork as BaseUnitOfWork;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;

/**
 * {@inheritDoc}
 */
class UnitOfWork extends BaseUnitOfWork
{
    /**
     * The entity persister instances used to persist entity instances.
     *
     * @var array
     */
    private $persisters = array();

    /**
     * The EntityManager that "owns" this UnitOfWork instance.
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;


    /**
     * {@inheritDoc}
     */
    public function __construct(DoctrineEntityManager $em)
    {
        parent::__construct($em);
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityPersister($entityName)
    {
        if (isset($this->persisters[$entityName])) {
            return $this->persisters[$entityName];
        }

        $class = $this->em->getClassMetadata($entityName);

        switch (true) {
            case ($class->isInheritanceTypeNone()):
                $persister = new Persisters\BasicEntityPersister($this->em, $class);
                break;

            case ($class->isInheritanceTypeSingleTable()):
                $persister = new Doctrine\ORM\Persisters\SingleTablePersister($this->em, $class);
                break;

            case ($class->isInheritanceTypeJoined()):
                $persister = new Doctrine\ORM\Persisters\JoinedSubclassPersister($this->em, $class);
                break;

            default:
                $persister = new Doctrine\ORM\Persisters\UnionSubclassPersister($this->em, $class);
        }

        $this->persisters[$entityName] = $persister;

        return $this->persisters[$entityName];
    }
}
