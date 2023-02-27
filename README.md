# rabbitmq-consumerpool
An OpenSwoole-based RabbitMQ consumer using a process pool.

## For the Dockerfile integrating this package:
```
RUN mkdir /rcpool && chown -R application:application /rcpool && \
  git clone https://github.com/dodo1708/rabbitmq-consumerpool.git /rcpool/rabbitmq-consumerpool && \
  cd /rcpool/rabbitmq-consumerpool && composer install
```
