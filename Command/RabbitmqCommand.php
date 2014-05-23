<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pichet PUTH <pichet.puth@utt.fr>
 * @license: GPL
 *
 */

namespace IDCI\Bundle\NotificationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use IDCI\Bundle\NotificationBundle\Entity\Notification;

class RabbitmqCommand extends ContainerAwareCommand
{
    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('idci:notification:enqueue')
            ->setDescription('Enqueue notification from spool')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command enqueues all modified notifications in RabbitMQ queue.
Here is an example of usage of this command <info>php app/console tms:notification:enqueue</info>
EOT
            )
        ;
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $notificationManager = $this->getContainer()->get('idci_notification.manager.notification');

        $countErrors = 0;
        $notifications = $notificationManager->findBy(array('status' => Notification::STATUS_NEW));
        $output->writeln(sprintf("<info>Enqueue notifications (%d)</info>", count($notifications)));
        foreach($notifications as $notification) {
            try {
                $notificationManager->enqueueNotification($notification);
            } catch (\Exception $e) {
                $countErrors++;
                $output->writeln(sprintf(
                    "<error>Notification %s not enqueued : %s</error>",
                    $notification,
                    $e->getMessage()
                ));
            }
        }
        $output->writeln(sprintf('%d notification(s) processed, %d error(s)',
            count($notifications),
            $countErrors
        ));
    }
}
