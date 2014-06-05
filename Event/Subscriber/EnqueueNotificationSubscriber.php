<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use IDCI\Bundle\NotificationBundle\Entity\Notification;
use IDCI\Bundle\NotificationBundle\Event\NotificationEvent;
use IDCI\Bundle\NotificationBundle\Event\NotificationEvents;

class EnqueueNotificationSubscriber implements EventSubscriberInterface
{
    protected $notificationProcessingProducer;

    /**
     * Constructor
     *
     * @param Producer $notificationProcessingProducer
     */
    public function __construct(Producer $notificationProcessingProducer)
    {
        $this->notificationProcessingProducer = $notificationProcessingProducer;
    }

    /**
     * GetSubscribedEvents
     *
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            NotificationEvents::POST_CREATE => 'enqueueNotification',
            NotificationEvents::POST_UPDATE => 'enqueueNotification'
        );
    }

    /**
     * EnqueueNotification
     *
     * @param NotificationEvent $event
     */
    public function enqueueNotification(NotificationEvent $event)
    {
        if (Notification::STATUS_NEW == $event->getNotification()->getStatus()) {
            $this->notificationProcessingProducer->publish($event->getNotification()->getId());
        }
    }
}