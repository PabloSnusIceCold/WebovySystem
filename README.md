Postup instalacie po naclonovani projektu z gitu:

git clone https://github.com/PabloSnusIceCold/WebovySystem.git

PowerShell (alebo terminal):
    - cd WebovySystem (chod do adresara projektu)
    - cp .env.example .env (svoje udaje mam ja na teamse)
    - docker compose up -d (spusti kontainery)
    - docker exec -it webovysystem-app composer install (nainstaluj zavislosti)
    - docker exec -it webovysystem-app php artisan key:generate (vygeneruj aplikacny kluc)
    - Vycisti konfiguraciu:
        docker exec -it webovysystem-app php artisan config:clear
        docker exec -it webovysystem-app php artisan cache:clear
        docker exec -it webovysystem-app php artisan route:clear
    - docker exec -it webovysystem-app php artisan migrate --force (spusti migracie)





