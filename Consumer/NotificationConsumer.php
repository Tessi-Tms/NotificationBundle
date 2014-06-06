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
use Symfony\Bridge\Monolog\Logger;

class NotificationConsumer implements ConsumerInterface
{
    protected $notificationManager;
    protected $logger;

    public function __construct(NotificationManager $notificationManager, Logger $logger)
    {
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;
    }

    /**
     * GetNotificationManager
     *
     * @return NotificationManager
     */
    protected function getNotificationManager()
    {
        return $this->notificationManager;
    }

    /**
     * GetLogger
     *
     * @return Logger
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * Execute a pulled notification from rabbitMQ queue
     *
     * @param AMQPMessage $message
     */
    public function execute(AMQPMessage $message)
    {
        $notificationId = $message->body;
        $notification = $this->getNotificationManager()->findOneById($notificationId);

        if (null === $notification ) {
            $this->getLogger()->error(sprintf(
                'Notification [%s] does not exist in database.',
                $notificationId
            ));
        } elseif (Notification::STATUS_NEW !== $notification->getStatus()) {
            $this->getLogger()->error(sprintf(
                'Notification [%s] already processed.',
                $notificationId
            ));
        } else {
            $this->getNotificationManager()->notify($notification);
        }
    }
}
