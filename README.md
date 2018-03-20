# database-checker


[![Coverage Status](https://coveralls.io/repos/github/starker-xp/database-checker/badge.svg?branch=master)](https://coveralls.io/github/starker-xp/database-checker?branch=master) [![Build Status](https://travis-ci.org/starker-xp/database-checker.svg?branch=master)](https://travis-ci.org/starker-xp/database-checker) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/starker-xp/database-checker/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/starker-xp/database-checker/?branch=master)

Je me suis retrouvé confronté à un problème de synchronisation des base de données clients. Et je n'avais aucun outils pour vérifier si l'instance du client était bel est bien valide.

A Faire :
- [ ] Gestion des `FOREIGN KEY` (dépends du moteur de stockage).
- [ ] Suppresion des index avant un `ALTER COLUMN`.
- [ ] Créer la class `MysqlDatabase` afin de gérer les montés de version de mysql exemple les index fulltext n'était pas gérer en innodb sur les versions antérieurs à 5.6
- [ ] Permettre d'ignorer certaines tables.
- [ ] Permettre d'ignorer certaines colonnes.
- [ ] Permettre d'ignorer certains index.
- [ ] Permettre la modification de colonne. (Ajout, modification, suppression, rename)
- [ ] Vérifier les exports json/objet suite à la création de `MysqlDatabase`.

Terminé :
- [X] Générer l'object depuis un fichier JSON.
- [X] Générer l'object depuis une base de données.
- [X] Conversion des `ENUM('0','1')` en `TYINT(1)`.
- [X] Génération du diff entre deux objets.
- [x] Gestion sensitive de la casse.
- [x] Intégrer le check de `collate`.
- [x] Création des index `FULLTEXT`.
- [x] Check moteur de stockage
- [x] Gérer les `DROP COLUMNS`.
- [x] Une fois la gestion des `DROP` et `REMOVE COLUMNS` effecutées, permettre au logiciel de n'être qu'en création only via une configuration.


- [ ] Vérifier les datas de certaines table (Ex: La liste des civilités possibles).
- [ ] Ajouter des optimisations de structure (Ex: `TEXT`, `BLOB` dans une table avec beaucoup d'entrée).
- [ ] Suggestion d'index.
- [ ] A partir d'une requête SQL vérifier que les index soit définit.

```
// watcher
gulp start
// lance les tests unitaires (nécessite phpunit)
gulp phpunit
// prépare le projet pour la production
gulp build
// permet de générer la couverture du code (nécessite xdebug/phpuni)
gulp coverage
```
