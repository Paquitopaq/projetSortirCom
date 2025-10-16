
# 🚀 BougeTonCrew — Application Symfony de gestion de sorties et groupes privés

Pré-requis :
Php:8.3.14
Symfony : 5.15
MySql

1. Faire un git clone de :
2. Créer une base de données nommée "Sortir". (Sinon si autre nom de base de donnée modifier la connexion dans le .env)
3. Se rendre dans le dossier du projet et faire les commandes suivantes : 
   1. composer install
   2. symfony console make:migration (si la migration n'existe pas)
   3. symfony console doctrine:schema:update --force
   4. symfony console doctrine:fixtures:load
4. Lancer l'applicatif avec la commande symfony serve
5. Se rendre sur l'adresse localhost:8000
6. Si vous souhaitez vous identifier en tant qu'administrateur : bob@example.com / password
7. Si vous souhaitez vous identifier en tant qu'utilisateur : alice@example.com / password

Pour tester faite un symfony console doctrine:fixtures:load --env=test puis php bin/phpunit


Bienvenue dans **BougeTonCrew**, une application Symfony permettant aux utilisateurs de créer, rejoindre et gérer des sorties et des groupes privés. Elle propose une interface intuitive, une gestion des rôles, et une organisation communautaire autour d’événements.

---

## 📁 Structure du projet
.📁 Arborescence du projet

    .phpunit.cache/ – Cache des tests PHPUnit

        test_results/

        code_coverage/

    assets/ – Fichiers front-end (JS, SCSS…)

        js/

        styles/

    bin/ – Console Symfony (bin/console)

    config/ – Configuration de l'application

        packages/

        routes/

        services.yaml

    migrations/ – Migrations Doctrine

    public/ – Racine web (index.php, assets compilés)

        index.php

        build/

        uploads/

    src/

        Controller/ – Contrôleurs HTTP

        Entity/ – Entités Doctrine

        Form/ – Formulaires Symfony

        Repository/ – Requêtes personnalisées

        Security/ – Sécurité utilisateur

        Service/ – Services métiers

    src/DataFixtures/ – Fixtures pour peupler la base de données

    templates/ – Vues Twig

    tests/ – Tests fonctionnels et unitaires

    var/ – Logs, cache, sessions

    vendor/ – Dépendances Composer

    README.md