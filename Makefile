init:
	cp .env.example .env
	cp ./src/.env.example ./src/.env
	docker-compose up -d
	sleep 2
	docker exec -it php-fpm sh -c "php artisan migrate"

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose down
	docker-compose up -d

recreate:
	docker-compose down
	docker-compose build
	docker-compose up -d

php-sh:
	docker exec -it php-fpm sh

php-cli-sh:
	docker exec -it php-cli-supervisor sh

restart-php-cli-supervisor:
	docker-compose restart php-cli-supervisor

migrate:
	docker exec -it php-fpm sh -c "php artisan migrate"

test:
	docker exec -it php-fpm sh -c "php artisan test"