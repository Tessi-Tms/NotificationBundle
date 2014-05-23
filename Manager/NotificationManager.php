<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Sekou KOÏTA <sekou.koita@supinfo.com>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use IDCI\Bundle\NotificationBundle\Entity\Notification;
use IDCI\Bundle\NotificationBundle\Notifier\NotifierInterface;
use IDCI\Bundle\NotificationBundle\Event\NotificationEvent;
use IDCI\Bundle\NotificationBundle\Event\NotificationEvents;
use IDCI\Bundle\NotificationBundle\Exception\UndefinedNotifierException;
use IDCI\Bundle\NotificationBundle\Exception\NotificationParametersParseErrorException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

class NotificationManager extends AbstractManager
{
    protected $notifiers;
    protected $notificationProcessingProducer;
    /**
     * Constructor
     *
     * @param ObjectManager $objectManager
     * @param EventDispatcherInterface $entityManager
     */
    public function __construct(ObjectManager $objectManager, EventDispatcherInterface $eventDispatcher, Producer $notificationProcessingProducer)
    {
        parent::__construct($objectManager, $eventDispatcher);
        $this->notificationProcessingProducer = $notificationProcessingProducer;
        $this->notifiers = array();
    }

    /**
     * Get Repository
     *
     * @return \Doctrine\ORM\EntityManager\EntityRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository("IDCINotificationBundle:Notification");
    }

    /**
     * Add
     * Use the object manager to add (persist) the given object
     *
     * @param object $entity
     */
    public function add($entity)
    {
        $this->getEventDispatcher()->dispatch(
            NotificationEvents::PRE_CREATE,
            new NotificationEvent($entity)
        );

        parent::add($entity);
        //TO DISCUSS : entity not necessairely a notification
        $this->enqueueNotification($entity);

        $this->getEventDispatcher()->dispatch(
            NotificationEvents::POST_CREATE,
            new NotificationEvent($entity)
        );
    }

    /**
     * Update
     * Use the object manager to update (persist) the given object
     *
     * @param object $entity
     */
    public function update($entity)
    {
        $this->getEventDispatcher()->dispatch(
            NotificationEvents::PRE_UPDATE,
            new NotificationEvent($entity)
        );

        parent::update($entity);
        //TO DISCUSS : entity not necessairely a notification
        $this->enqueueNotification($entity);

        $this->getEventDispatcher()->dispatch(
            NotificationEvents::POST_UPDATE,
            new NotificationEvent($entity)
        );
    }

    /**
     * Delete
     * Use the object manager to delete (remove) the given object
     *
     * @param object $entity
     */
    public function delete($entity)
    {
        $this->getEventDispatcher()->dispatch(
            NotificationEvents::PRE_DELETE,
            new NotificationEvent($entity)
        );

        parent::delete($entity);

        $this->getEventDispatcher()->dispatch(
            NotificationEvents::POST_DELETE,
            new NotificationEvent($entity)
        );
    }

    /**
     * Get notifiers
     *
     * @return array
     */
    public function getNotifiers()
    {
        return $this->notifiers;
    }

    /**
     * Add notifier
     *
     * @param NotifierInterface $notifier
     * @param string $alias
     */
    public function addNotifier(NotifierInterface $notifier, $alias)
    {
        $this->notifiers[$alias] = $notifier;
    }

    /**
     * Get notifier
     *
     * @param string $alias
     * @return NotifierInterface
     */
    public function getNotifier($alias)
    {
        if (!isset($this->notifiers[$alias])) {
            throw new UndefinedNotifierException($alias);
        }

        return $this->notifiers[$alias];
    }

    /**
     * Process data
     *
     * @param string $type
     * @param string $data in json format
     * @param string|null $sourceName
     */
    public function processData($type, $data, $sourceName = null)
    {
        if (!isset($this->notifiers[$type])) {
            throw new UndefinedNotifierException($type);
        }

        $notificationsData = json_decode($data, true);
        if (!$notificationsData) {
            throw new NotificationParametersParseErrorException($data);
        }

        foreach ($notificationsData as $notificationData) {
            $this->addNotification($type, $notificationData, $sourceName);
        }
    }

    /**
     * Add Notification
     *
     * @param string $type
     * @param array $data
     * @param string|null $sourceName
     */
    public function addNotification($type, $data, $sourceName = null)
    {
        $notifier = $this->getNotifier($type);
        $data = $notifier->cleanData($data);

        $notification = new Notification();
        $notification
            ->setType($type)
            ->setNotifierAlias(isset($data['notifierAlias']) ? $data['notifierAlias'] : null)
            ->setSource(null === $sourceName ? $data['source'] : $sourceName)
            ->setFrom(isset($data['from']) ? json_encode($data['from']) : null)
            ->setTo(isset($data['to']) ? json_encode($data['to']) : null)
            ->setContent(json_encode($data['content']))
        ;

        $this->getObjectManager()->persist($notification);
        $this->getObjectManager()->flush();
        $this->enqueueNotification($notification);
    }

    /**
     * Notify
     *
     * @param Notification $notification
     */
    public function notify(Notification $notification)
    {
        $notifier = $this->getNotifier($notification->getType());
        try {
            $notifier->sendNotification($notification);
            $notification->setStatus(Notification::STATUS_DONE);
        } catch (\Exception $e) {
            $notification->setStatus(Notification::STATUS_ERROR);
            $notification->addLog($e->getMessage());
        }

        $this->getObjectManager()->persist($notification);
        $this->getObjectManager()->flush();
    }

    /**
     * Enqueue an notification
     *
     * @param Notification $notification
     */
    public function enqueueNotification(Notification $notification)
    {
        $message = array('notificationId' => $notification->getId());
        $this->notificationProcessingProducer->publish(serialize($message));
    }
}
