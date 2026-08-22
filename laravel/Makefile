.DEFAULT_GOAL := help

.PHONY: help up down rebuild shell migrate seed test logs

help:
	@printf "Available commands: up, down, rebuild, shell, migrate, seed, test, logs\n"

up:
	docker compose up -d --build

down:
	docker compose down

rebuild:
	docker compose build --no-cache

shell:
	docker compose exec app sh

migrate:
	docker compose exec app php artisan migrate

seed:
	docker compose exec app php artisan db:seed

test:
	docker compose exec app php artisan test

logs:
	docker compose logs -f --tail=100
