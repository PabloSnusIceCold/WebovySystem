# Webový systém – Laravel 12 + Docker (MySQL)

Tento projekt je Laravel 12 aplikácia spúšťaná v Dockeri (PHP-FPM + Nginx + MySQL + phpMyAdmin).

POŽIADAVKY
- Docker Desktop
- Git

# **INŠTALÁCIA PROJEKTU**

1) Naklonovanie projektu
- git clone https://github.com/PabloSnusIceCold/WebovySystem.git
- cd WebovySystem

2) Vytvorenie .env
- skopíruj .env.example na .env
  (Windows PowerShell: Copy-Item .env.example .env)

- v súbore .env nastav MySQL pripojenie (projekt beží na MySQL v Dockeri):
  DB_CONNECTION=mysql
  DB_HOST=webovysystem-mysql
  DB_PORT=3306
  DB_DATABASE=laravel
  DB_USERNAME=user
  DB_PASSWORD=user123

Poznámka: .env.example má defaultne SQLite, preto je nutné DB prepnuť na MySQL.

3) Spustenie Docker kontajnerov
- docker compose up -d --build
- docker compose ps

4) Inštalácia závislostí (Composer)
- docker exec -it webovysystem-app composer install

5) Vygenerovanie aplikačného kľúča
- docker exec -it webovysystem-app php artisan key:generate

6) Migrácie databázy
- docker exec -it webovysystem-app php artisan migrate --force

7) Seed databázy (kategórie)
- docker exec -it webovysystem-app php artisan db:seed --force

8) Vyčistenie cache (odporúčané)
- docker exec -it webovysystem-app php artisan optimize:clear

SPUSTENIE A ODKAZY
- Aplikácia: http://localhost:8000
- phpMyAdmin: http://localhost:8080
  Prihlásenie do phpMyAdmin:
  - server/host: mysql (vnútri Docker siete) alebo 127.0.0.1 (z host OS)
  - user: root
  - password: root

PRIHLÁSENIE / REGISTRÁCIA
- Registrácia: /register
- Prihlásenie: /login

ADMIN ROLA
Projekt používa stĺpec role (user | admin).
Admin rolu si vieš nastaviť priamo v DB (napr. cez phpMyAdmin):
- UPDATE users SET role='admin' WHERE email='tvoj@email.com';

TROUBLESHOOTING

1) Chyba "php_network_getaddresses: getaddrinfo for mysql failed"
- v .env musí byť DB_HOST=webovysystem-mysql
- over, že databáza beží: docker compose ps

2) Migrácie padajú na cache/sessions
Ak máš CACHE_STORE=database a ešte nemáš spravené migrácie, spusti najprv migrácie:
- docker exec -it webovysystem-app php artisan migrate --force
Až potom čistite cache:
- docker exec -it webovysystem-app php artisan optimize:clear

KONTROLA STAVU (voliteľné)
- docker exec -it webovysystem-app php artisan route:list
- docker exec -it webovysystem-app php artisan migrate:status
