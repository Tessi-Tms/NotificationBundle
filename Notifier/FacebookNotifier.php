<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Notifier;

use IDCI\Bundle\NotificationBundle\Entity\Notification;
use IDCI\Bundle\NotificationBundle\Exception\FacebookNotifierException;

class FacebookNotifier extends AbstractNotifier
{
    /**
     * {@inheritdoc}
     */
    public function sendNotification(Notification $notification)
    {
        throw new \Exception("facebooknotifier todo.");
    }

    /**
     * {@inheritdoc}
     */
    public function getToFields()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFromFields()
    {
        return array(
            'appId' => array('text', array('required' => false))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentFields()
    {
        return array(
            'message' => array('textarea', array('required' => true))
        );
    }
}
