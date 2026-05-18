
# Инициализация проекта

Выполнить **make init**<br> 
или
1. Скопировать содержимое .env.example в .env
2. Скопировать содержимое src/.env.example в src/.env
3. docker-compose up -d
4. docker exec -it php-fpm sh -c "php artisan migrate"


### Создание БД для запуска тестов

1. docker exec -it postgres sh
2. psql -U admin -d postgres
3. CREATE DATABASE notification_test;
4. exit
5. docker exec -it php-fpm sh -с "php artisan migrate --env=testing"

# API
Коллекция запросов и окружение для Postman лежит в папке postman. Environment содержит только одну переменную host.

Есть три эндпоинта.

1. **POST** /api/v1/notification/start<br>
Инициализирует массовую рассылку уведомлений.<br>
В теле запроса должен быть json:
 ```
 {
    "text": "Маркетинговое уведомление",
    "channel": "sms",
    "type": "marketing",
    "receiverIds": [1, 2, 3, 4, 5, 7, 8, 100]
}
 ```
* Возможные значения для **channel** - _sms/email_
* Возможные значения для **type** - _transactional/marketing_


2. **GET** /api/v1/receiver/show-history/**{receiverId}**<br>
 Показывает историю для конкретного получателя, **{receiverId}** - целое число.


3. **POST** /api/v1/test/data/init<br>
Используется только для облегчения тестирования. <br>
Удаляет содержимое всех таблиц и создаёт 10 получателей. У четверых из них будет и email и телефон, у 
троих только email, у троих только телефон.
