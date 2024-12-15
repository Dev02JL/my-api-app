1. Cloner le projet
2. Aller dans le projet ./my-api
3. Dans un Terminal, exécuter les commandes : 
    symfony server:start
    php bin/console make:migration                   
    php bin/console doctrine:migrations:migrate
4. Ouvrir la collection Postman, initialiser les variables d'environnement :
- url = http://localhost:8000
- product_id = 1 (par exemple, à adapter)
- order_id = 1 (par exemple, à adapter)
- authToken s'inialise après une login et se reset après un logout
Regarder les controllers, les routes appellent des fonctions au noms "relevant", 
Postman propose des body adaptés

Au préalable, il faut avoir :
composer
php 8.1 ou +
Extensions php : pdo_mysql, mbstring, xml, intl.
symfony

Pour les dépendances :
composer require doctrine/doctrine-bundle
composer require doctrine/migrations
composer require symfony/framework-bundle
composer require symfony/monolog-bundle
composer require symfony/security-bundle

composer require --dev symfony/debug-bundle
composer require --dev symfony/maker-bundle
composer require --dev symfony/web-profiler-bundle

Si quelconque problème -> dev02@sportyneo.com