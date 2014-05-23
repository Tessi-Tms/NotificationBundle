<?php

namespace IDCI\Bundle\NotificationBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use IDCI\Bundle\NotificationBundle\Entity\Notification;
use IDCI\Bundle\NotificationBundle\Manager\NotificationManager;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function execute(AMQPMessage $message)
    {
        $countErrors = 0;
        $notificationManager = $this->getNotificationManager();
        $notificationData = unserialize($message->body);
        $notification = $notificationManager->findOneBy(array(
            'id'     => $notificationData['notificationId'],
            'status' => Notification::STATUS_NEW
        ));

        try{
            echo sprintf(
                '[INFO] Send notifications (%d)',
                count($notification)
            )."\r\n";
            $notificationManager->notify($notification);
            if ($notification->getStatus() == Notification::STATUS_ERROR) {
                $countErrors++;
                echo sprintf(
                    '[ERROR] Notification %s not sent',
                    $notification
                )."\r\n";
            } else {
                echo sprintf(
                    '[COMMENT] Notification %s sent',
                    $notification
                )."\r\n";
            }
            echo sprintf(
                '[INFO] %d notification(s) processed, %d error(s)',
                count($notification),
                $countErrors
            )."\r\n";
        } catch (\Exception $e) {
            echo sprintf(
                '[ERROR] %s',
                $e->getMessage()
            )."\r\n";
        }
    }
}
