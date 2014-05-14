<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Exception;

class FacebookNotifierException extends \Exception
{
    /**
     * Constructor
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct(sprintf(
            'Facebook Notifier exception: %s',
            $message
        ));
    }
}