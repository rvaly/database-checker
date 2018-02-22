# database-checker


[![Coverage Status](https://coveralls.io/repos/github/starker-xp/database-checker/badge.svg?branch=master)](https://coveralls.io/github/starker-xp/database-checker?branch=master) [![Build Status](https://travis-ci.org/starker-xp/database-checker.svg?branch=master)](https://travis-ci.org/starker-xp/database-checker) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/starker-xp/database-checker/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/starker-xp/database-checker/?branch=master)

Je me suis retrouvé confronté à un problème de synchronisation des base de données clients. Et je n'avais aucun outils pour vérifier si l'instance du client était bel est bien valide.

- [X] Générer l'object depuis un fichier JSON.
- [X] Générer l'object depuis une base de données.
- [X] Conversion des `ENUM('0','1')` en `TYINT(1)`.
- [X] Génération du diff entre deux objets.
- [ ] Gestion sensitive de la casse.
- [ ] Ajouter des optimisations de structure (Ex: `TEXT`, `BLOB` dans une table avec beaucoup d'entrée).
- [ ] Permettre d'ignorer certaines tables.
- [ ] Vérifier les datas de certaines table (Ex: La liste des civilités possibles).
- [ ] Gérer les `DROP` et `REMOVE COLUMNS`.
- [ ] Une fois la gestion des `DROP` et `REMOVE COLUMNS` effecutées, permettre au logiciel de n'être qu'en création only via une configuration.
- [ ] Intégrer le check de `collate`.
- [ ] Gestion des `FOREIGN KEY`.
- [ ] Suggestion d'index.
- [ ] Suppresion des index avant un `ALTER COLUMN`.
- [ ] A partir d'une requête SQL vérifier que les index soit définit.
