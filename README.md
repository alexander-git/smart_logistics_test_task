Описание задания лежит в pdf-файле в корне проекта.

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
5. docker exec -t php-fpm sh -с "php artisan migrate --env=testing"

# API
Коллекция запросов и окружение для Postman лежит в папке postman. Environment содержит только одну переменную host.


1. **POST** /api/v1/notification/start<br>
**Инициализирует массовую рассылку уведомлений.**<br>
В теле запроса должен быть json:
 ```
 {
    "text": "Пример текста уведомления",
    "channel": "sms",
    "type": "marketing",
    "receiverIds": [1, 2, 3, 4, 5, 7, 8, 100]
}
 ```
* Возможные значения для **channel** - _sms/email_
* Возможные значения для **type** - _transactional/marketing_


2. **GET** /api/v1/receiver/show-history/{receiverId}<br>
 **Показывает историю для конкретного получателя**<br>
{receiverId} - целое число.


3. **POST** /api/v1/test/init-data<br>
**Инициализирует БД фиксироваными данными для тестирования приложения.** <br>
Удаляет содержимое всех таблиц и создаёт 10 получателей. У четверых из них будет и email и телефон, у 
троих только email, у троих только телефон. В ответе возвращает информацию о созданных получателях.<br>
Для удобства есть консольная команда которая делает то же самое - **php artisan app:init-data**


4. **POST** api/v1/test/init-with-random-data<br>
**Инициализирует БД случайными данными для тестирования приложения.** <br>
Удаляет содержимое всех таблиц и создаёт указанное число получателей. У кого-то из них будет и email и
телефон, у кого-то только email, у кого-то только телефон.
В теле запроса должно быть количество создаваемых получателей - receiverCount.
 ```
 {
    "receiverCount": 100
 }
 ```
Возвращает информацию о созданных получателях в ответе.<br>
Для удобства есть консольная команда которая делает то же самое - **php artisan app:init-with-random-data --receiverCount=100**