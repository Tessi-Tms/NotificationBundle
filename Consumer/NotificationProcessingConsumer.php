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

class NotificationProcessingConsumer implements ConsumerInterface
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
        $notificationId = unserialize($message->body);
        $notification = $notificationManager->findOneBy(array(
            'id'     => $notificationId,
            'status' => Notification::STATUS_NEW
        ));

        try{
            echo sprintf(
                '[INFO] Send notification (id : %s)',
                $notificationId
            )."\r\n";
            $notificationManager->notify($notification);
            if ($notification->getStatus() == Notification::STATUS_ERROR) {
                echo sprintf(
                    '[ERROR] Notification (id : %s) not sent',
                    $notificationId
                )."\r\n";
            } else {
                echo sprintf(
                    '[COMMENT] Notification (id : %s) sent',
                    $notificationId
                )."\r\n";
            }
            $notificationManager->clear();
        } catch (\Exception $e) {
            echo sprintf(
                '[ERROR] %s',
                $e->getMessage()
            )."\r\n";
        }
    }
}
