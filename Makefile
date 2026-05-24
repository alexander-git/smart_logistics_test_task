init:
	cp .env.example .env
	cp ./src/.env.example ./src/.env
	docker-compose up -d
	docker-compose exec php-fpm sh -c '\
		until php artisan migrate --pretend >/dev/null 2>&1; do \
			sleep 0.5; \
		done; \
		php artisan migrate'

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
	docker exec -t php-fpm  sh -c "php artisan migrate"

test:
	docker exec -t php-fpm sh -c "php artisan test"