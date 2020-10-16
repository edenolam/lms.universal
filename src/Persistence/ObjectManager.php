<?php

namespace App\Persistence;

use App\Traits\Log\LoggableTrait;
use Doctrine\ORM\EntityManagerInterface as ObjectManagerInterface;
use Doctrine\ORM\EntityManagerInterfaceDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


use Doctrine\Common\Persistence\ObjectManagerDecorator;

/**
 * ObjectManager
 *
 * @author Free
 */
class ObjectManager extends ObjectManagerDecorator
{
    use LoggableTrait;

    private $monolog;
    private $flushSuiteLevel = 0;
    private $supportsTransactions = false;
    private $hasEventManager = false;
    private $hasUnitOfWork = false;
    private $activateLog = false;
    private $allowForceFlush = true;
    private $showFlushLevel = false;
    private $ignoreForeignKeys = false;

    /**
     * ObjectManager constructor.
     *
     * @param ObjectManagerInterface $om
     */
    public function __construct(ObjectManagerInterface $om, LoggerInterface $logger)
    {
        $this->wrapped = $om;
        $this->supportsTransactions
            = $this->hasEventManager
            = $this->hasUnitOfWork
            = $om instanceof EntityManagerInterface;
        $this->monolog = $logger;
    }

    /**
     * Checks if the underlying manager supports transactions.
     *
     * @return bool
     */
    public function supportsTransactions()
    {
        return $this->supportsTransactions;
    }

    /**
     * Checks if the underlying manager has an event manager.
     *
     * @return bool
     */
    public function hasEventManager()
    {
        return $this->hasEventManager;
    }

    /**
     * Checks if the underlying manager has an unit of work.
     *
     * @return bool
     */
    public function hasUnitOfWork()
    {
        return $this->hasUnitOfWork;
    }

    /**
     * {@inheritdoc}
     *
     * This operation has no effect if one or more flush suite is active.
     */
    public function flush()
    {
        if (0 === $this->flushSuiteLevel) {
            if ($this->activateLog) {
                $this->log('Flush was started.');
            }
            parent::flush();
        }
    }

    /**
     * Starts a flush suite. Until the suite is ended by a call to "endFlushSuite",
     * all the flush operations are suspended. Flush suites can be nested, which means
     * that the flush takes place only when all the opened suites have been closed.
     */
    public function startFlushSuite()
    {
        ++$this->flushSuiteLevel;
        if ($this->activateLog && $this->showFlushLevel) {
            $this->logFlushLevel();
        }
    }

    /**
     * Ends a previously opened flush suite. If there is no other active suite,
     * a flush is performed.
     *
     * @throws NoFlushSuiteStartedException if no flush suite has been started
     */
    public function endFlushSuite()
    {
        if (0 === $this->flushSuiteLevel) {
            throw new NoFlushSuiteStartedException('No flush suite has been started');
        }

        --$this->flushSuiteLevel;
        $this->flush();
        if ($this->activateLog && $this->showFlushLevel) {
            $this->logFlushLevel();
        }
    }

    /**
     * Forces a flush.
     */
    public function forceFlush()
    {
        if ($this->allowForceFlush) {
            if ($this->activateLog) {
                $this->log('Flush was forced for level ' . $this->flushSuiteLevel . '.');
            }
            parent::flush();
        }
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return $this->wrapped->createQueryBuilder();
    }

    /**
     * Starts a transaction.
     *
     * @throws UnsupportedMethodException if the method is not supported by
     *                                    the underlying object manager
     */
    public function beginTransaction()
    {
        $this->assertIsSupported($this->supportsTransactions, __METHOD__);
        $this->wrapped->getConnection()->beginTransaction();
    }

    /**
     * Commits a transaction.
     *
     * @throws UnsupportedMethodException if the method is not supported by
     *                                    the underlying object manager
     */
    public function commit()
    {
        $this->assertIsSupported($this->supportsTransactions, __METHOD__);
        $this->wrapped->getConnection()->commit();
    }

    /**
     * Rollbacks a transaction.
     *
     * @throws UnsupportedMethodException if the method is not supported by
     *                                    the underlying object manager
     */
    public function rollBack()
    {
        $this->assertIsSupported($this->supportsTransactions, __METHOD__);
        $this->wrapped->getConnection()->rollBack();
    }

    /**
     * Returns the event manager.
     *
     * @throws UnsupportedMethodException if the method is not supported by
     *                                    the underlying object manager
     */
    public function getEventManager()
    {
        $this->assertIsSupported($this->hasEventManager, __METHOD__);

        return $this->wrapped->getEventManager();
    }

    /**
     * Returns the unit of work.
     *
     * @return UnitOfWork
     *
     * @throws UnsupportedMethodException if the method is not supported by
     *                                    the underlying object manager
     */
    public function getUnitOfWork()
    {
        $this->assertIsSupported($this->hasUnitOfWork, __METHOD__);

        return $this->wrapped->getUnitOfWork();
    }

    /**
     * Finds a set of objects by their ids.
     *
     * @param $class
     * @param array $ids
     * @param bool $orderStrict keep the same order as ids array
     *
     * @return array [object]
     *
     * @throws MissingObjectException if any of the requested objects cannot be found
     *
     * @internal param string $objectClass
     *
     * @todo make this method compatible with odm implementations
     */
    public function findByIds($class, array $ids, $orderStrict = false)
    {
        return $this->findList($class, 'id', $ids, $orderStrict);
    }

    /**
     * Finds a set of objects.
     *
     * @param $class
     * @param $property
     * @param array $list
     * @param bool $orderStrict keep the same order as ids array
     *
     * @return array [object]
     *
     * @throws MissingObjectException if any of the requested objects cannot be found
     *
     * @internal param string $objectClass
     *
     * @todo make this method compatible with odm implementations
     */
    public function findList($class, $property, array $list, $orderStrict = false)
    {
        if (0 === count($list)) {
            return [];
        }

        $dql = "SELECT object FROM {$class} object WHERE object.{$property} IN (:list)";
        $query = $this->wrapped->createQuery($dql);
        $query->setParameter('list', $list);
        $objects = $query->getResult();

        if (($entityCount = count($objects)) !== ($idCount = count($list))) {
            $this->monolog->warning("{$entityCount} out of {$idCount} ids don't match any existing object");
        }

        if ($orderStrict) {
            // Sort objects to have the same order as given $ids array
            $sortIds = array_flip($list);
            usort($objects, function ($a, $b) use ($sortIds) {
                return $sortIds[$a->getId()] - $sortIds[$b->getId()];
            });
        }

        return $objects;
    }

    /**
     * Counts objects of a given class.
     *
     * @param string $class
     *
     * @return int
     *
     * @todo make this method compatible with odm implementations
     */
    public function count($class)
    {
        $dql = "SELECT COUNT(object) FROM {$class} object";
        $query = $this->wrapped->createQuery($dql);

        return (int) $query->getSingleScalarResult();
    }

    private function assertIsSupported($isSupportedFlag, $method)
    {
        if (!$isSupportedFlag) {
            throw new UnsupportedMethodException(
                "The method '{$method}' is not supported by the underlying object manager"
            );
        }
    }

    /**
     * Please be carefull if you remove the force flush...
     */
    public function allowForceFlush($bool)
    {
        $this->allowForceFlush = $bool;
    }

    //override the monolog logger if something else is needed for debug
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function activateLog()
    {
        $this->activateLog = true;

        return $this;
    }

    public function disableLog()
    {
        $this->activateLog = false;

        return $this;
    }

    public function showFlushLevel()
    {
        $this->showFlushLevel = true;
    }

    public function hideFlushLevel()
    {
        $this->showFlushLevel = false;
    }

    private function logFlushLevel()
    {
        $stack = debug_backtrace();

        foreach ($stack as $call) {
            if ('endFlushSuite' === $call['function'] || 'startFlushSuite' === $call['function']) {
                $this->log('Function "' . $call['function'] . '" was called from file ' . $call['file'] . ' on line ' . $call['line'] . '.', LogLevel::DEBUG);
            }
        }

        $this->log('Flush level: ' . $this->flushSuiteLevel . '.');
    }

    public function save($object, $options = [], $log = true)
    {
        $this->persist($object);

        if ($log) {
            //maybe log some stuff according to the options
        }

        $this->flush();
    }

    /**
     * @param string $class
     * @param string|int $id
     */
    public function find($class, $id)
    {
        return $this->wrapped->getRepository($class)->findOneBy(
            !is_numeric($id) && property_exists($class, 'uuid') ?
                ['uuid' => $id] :
                ['id' => $id]
        );
    }

    /**
     * Fetch an object from database according to the class and the id/uuid of the data.
     */
    public function getObject(array $data, $class)
    {
        if (isset($data['id']) || isset($data['uuid'])) {
            if (isset($data['uuid'])) {
                $object = $this->getRepository($class)->findOneByUuid($data['uuid']);
            } else {
                $object = !is_numeric($data['id']) && property_exists($class, 'uuid') ?
                $this->getRepository($class)->findOneByUuid($data['id']) :
                $this->getRepository($class)->findOneById($data['id']);
            }

            return $object;
        }

        //else we look what's fetchable or no for that class
    }

    public function ignoreForeignKeys()
    {
        $conn = $this->wrapped->getConnection();
        $sql = 'SET FOREIGN_KEY_CHECKS=0;';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }

    public function restoreForeignKeys()
    {
        $conn = $this->wrapped->getConnection();
        $sql = 'SET FOREIGN_KEY_CHECKS=1;';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
}
