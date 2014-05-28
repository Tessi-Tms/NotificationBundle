<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\EventListener;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use IDCI\Bundle\NotificationBundle\Event\NotificationEvent;

class EnqueueNotificationListener
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
     * EnqueueNotification
     *
     * @param NotificationEvent $event
     */
    public function enqueueNotification(NotificationEvent $event)
    {
        $notificationId = $event->getNotification()->getId();
        $this->notificationProcessingProducer->publish(serialize($notificationId));
    }

}