<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use IDCI\Bundle\NotificationBundle\Entity\Notification;
use IDCI\Bundle\NotificationBundle\Manager\NotificationManager;

class NotificationConsumer implements ConsumerInterface
{
    protected $notificationManager;

    public function __construct(NotificationManager $notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    /**
     * Get notificationManager
     *
     * @return NotificationManager
     */
    protected function getNotificationManager()
    {
        return $this->notificationManager;
    }

    /**
     * Execute a pulled notification from rabbitMQ queue
     *
     * @param AMQPMessage $message
     * @throw Exception
     */
    public function execute(AMQPMessage $message)
    {
        $notificationManager = $this->getNotificationManager();
        $notificationId = $message->body;
        $notification = $notificationManager->findOneById($notificationId);

        if (null !== $notification && Notification::STATUS_NEW == $notification->getStatus()) {
            $notificationManager->notify($notification);
        }

    }
}
