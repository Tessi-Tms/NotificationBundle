IDCINotificationBundle Notification Service
===========================================


Notification
------------
### What is a Notification ?
A notification is an persisted object called "entity" (using Doctrine). It is used to inform a user about a subject.

Object Notification :

![UML Notification Class Diagram](notification_class.png)


#### What is the role of a notifierAlias ?

A notifierAlias is a string attribut of the entity Notification. This field identify which configuration has to be used to send a notification. Primery the notifierAlias is used to find a configuration in the database. Secondly, if there is no configuration in there then it is used to check in `app/config/config.yml` file.

Example : a configuration identified by an alias in a database

| Id | Alias    | Type  | Configuration
|----|----------|-------|-----------------------------------------------------------------------------------
| 1  | myalias1 | email | {"transport": "smtp","server": "smtp.test.com","login": "toto@test.fr","password": "test","port": 587,"encryption": "tls"}
Note 1 : valides types : email, sms, mail, facebook, twitter
Note 2 : the couple value {alias, type} is used to define the unique constraint in your database.

Example : a configuration identified by an alias in `app/config/config.yml`.
Please check : [Several_configurations_for_each_type_of_notifier](configuration_reference.md#several-configurations-for-each-type-of-notifier)

### How to create a Notification ?
There are 3 methods to create an notification.

#### Method 1 : Use a web service.

| Type   | Path                                                     | Description
|--------|----------------------------------------------------------|------------------------
| POST   | [/notifications](api/notification/post_notifications.md) | Create an notification

#### Method 2 : Create an interface to manage a notification (CRUD)

#### Method 3 : Using [IDCINotificationApiClientBundle](https://github.com/IDCI-Consulting/NotificationApiClientBundle.git).
Exemple : Using the command line `tms:notification:notify` to create an email notification.
```sh
$ php app/console tms:notification:notify email '{"notifierAlias": "alias", "to": "me@mymail.com", "cc": "cc1@mymail.com, cc2@mymail.com", "bcc": "bcc@mymail.com", "subject": "notification via command line", "message": "the message to be send", "htmlMessage": "<h1>Titre</h1><p>Message</p>", "attachments": []}'
```
Note : This command line uses the web service of IDCINotificationBundle to create a notification.

### How to send a Notification ?
To send a Notification you can use this command line : `idci:notification:send`
```sh
$ php app/console idci:notification:send
```
Workflow of this command :

1. Find all notifications with "NEW" status.
2. Guess the notifier for each type of notification to send.
3. Send them one by one.

### How to send Notifications using RabbitMQ ?

#### Enqueue Notification

##### Method 1 :
A Notification will be enqueued automatically when It is added or updated in database.

##### Method 2 : To enqueue all Notifications with status "NEW"
```sh
$ php app/console idci:notification:enqueue
```

##### Method 3 : To enqueue a specific Notification
```sh
$ php app/console idci:notification:enqueue --id=123
```
Note : The id identifies a Notification to be enqueued. Be careful the status must be "NEW".

##### Method 4 : To enqueue several Notifications
```sh
$ php app/console idci:notification:enqueue --id=123 --id=124
```

Notifier
--------
### What is a Notifier ?
A notifier is an object. It is used to send a notification.
There are five notifier types.

| Type         | Notifier            | development state
|--------------|---------------------|-------------------
| email        | EmailNotifier       | 100%
| sms          | SmsNotifier         | 10%
| mail         | MailNotifier        | 10%
| facebook     | FacebookNotifier    | 10%
| twitter      | TwitterNotifier     | 100%
| push_ios     | PushIOSNotifier     | 100%
| push_android | PushAndroidNotifier | 100%

Notifier UML schema:

![Notifier class diagram](notifier_class_diagram.png)

### How to create a Notifier
If you wish to add your own notifier, create a class which extends `IDCI\Bundle\NotificationBundle\Notifier\AbstractNotifier`
```php
<?php

namespace IDCI\Bundle\NotificationBundle\Notifier;

use IDCI\Bundle\NotificationBundle\Entity\Notification;

class MyNotifier extends AbstractNotifier
{
    /**
     * {@inheritdoc}
     */
    public function sendNotification(Notification $notification)
    {
        // Here the code to send your notification
    }

    /**
     * {@inheritdoc}
     */
    public function getToFields()
    {
        // To add custom fields store in to
        return array(
            'toField1'  => array('text', array('required' => true)),
            'toField2'  => array('text', array('required' => false)),
            //...
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getContentFields()
    {
        // To add custom fields store in content
        return array(
            'contentField1'  => array('text', array('required' => false)),
            'contentField2'  => array('text', array('required' => false)),
            //...
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFromFields()
    {
        // To add custom fields store in from
        return array(
            'fromField1'  => array('text', array('required' => false)),
            'fromField2'  => array('text', array('required' => true)),
            //...
        );
    }
}
```
Detail : the values in array are used to create form field
```
'field' => array('text', array('required' => false))
```
'field'    : field name
'text'     : [built-in field type](http://symfony.com/doc/current/book/forms.html#built-in-field-types)
'required' : field type options ([required option](http://symfony.com/doc/current/book/forms.html#field-type-options))

Now declare your notifier as service:
```yml
idci_notification.notifier.mynotifier:
    class: IDCI\Bundle\NotificationBundle\Notifier\MyNotifier
    arguments: []
    tags:
        - { name: idci_notification.notifier, alias: my_notifier }
```

RabbitMQ
--------
To see the [complete documentation](https://github.com/videlalvaro/RabbitMqBundle#producers-consumers-what)

### What is producer and a consumer ?
In a messaging application, the process sending messages to the broker is called producer while the process receiving those messages is called consumer. In your application you will have several of them that you can list under their respective entries in the configuration.
[[source]](https://github.com/videlalvaro/RabbitMqBundle#producers-consumers-what)

### Producer
A producer will be used to send messages to the server.

#### Step 1 : How to configure a producer
```yml
old_sound_rabbit_mq:
    producers:
        your_producer_id:
            connection: connectionId
            exchange_options:
                name: connectionId
                type: direct
```
Note : In this example your service container will contain the service `old_sound_rabbit_mq.your_producer_id_producer`

#### Step 2 : How to use a producer.
Exemple :
```php
<?php
//...
public function indexAction($name)
{
    //...
    $message = 'test';
    $this->get('old_sound_rabbit_mq.your_producer_id_producer')->publish($message);
}
```

### Consumer
A consumer will connect to the server and start a loop waiting for incoming messages to process.
#### Step 1 : How to create a callback for a consumer
```php
<?php
//...

namespace IDCI\Bundle\NotificationBundle\Consumer;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class YourConsumer implements ConsumerInterface
{
    /**
     * Execute an incoming message from rabbitMQ queue
     *
     * @param AMQPMessage $message
     */
    public function execute(AMQPMessage $message)
    {
        /*
        You have to implemente the behavior of the consumer when it receives a message.
        */
    }
}
```
#### Step 2 : Declare this callback as a service
```yml
idci_notification.consumer.your_consumer:
    class: IDCI\Bundle\NotificationBundle\Consumer\YourConsumer
    arguments: []
```

#### Step 3 : How to configure a consumer
Add this callback to a consumer : idci_notification.consumer.your_consumer
```yml
old_sound_rabbit_mq:
    consumers:
        your_consumer_id:
            connection: connectionId
            exchange_options:
                name: connectionId
                type: direct
            queue_options:
                name: connectionId
            callback: idci_notification.consumer.your_consumer
            qos_options:
                prefetch_size: 0
                prefetch_count: 1
                global: false
```
Note : In this example your service container will contain the service `old_sound_rabbit_mq.your_consumer_id_consumer`

#### Step 4 : How to run the consumer
You have to specify the number of messages the consumer should process.
exemple : Consumer will process 3 messages
```sh
$ php app/console rabbitmq:consumer -m 3 connectionId
```

### Overview of RabbitMQ configuration
```yml
# ...
# RabbitMQ configuration
old_sound_rabbit_mq:
    connections:
        connectionId:
            host:     localhost
            port:     5672
            user:     guest
            password: guest
            vhost:    /
            lazy:     true
    producers:
        your_producer_id:
            connection: connectionId
            exchange_options:
                name: connectionId
                type: direct
    consumers:
        your_consumer_id:
            connection: connectionId
            exchange_options:
                name: connectionId
                type: direct
            queue_options:
                name: connectionId
            callback: idci_notification.consumer.your_consumer
            qos_options:
                prefetch_size: 0
                prefetch_count: 1
                global: false
```
