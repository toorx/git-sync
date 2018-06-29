# Git Sync PHP

Tool PHP utile a fare il git clone/pull di uno o più progetti (anche in modo asincrono), scegliendo il gruppo e/o le repo da usare.

Oltre il sync delle repo git è possibile specificare anche le operazioni da eseguire dopo il pull/clone vedi file `repo.json` di esempio e assicurati che il parametro **"postCommands"** sul file _`config.json`_ sia a **"TRUE"**.
 
## Configuration

### file config.json
Configurazione di esempio:

```
{
  "priority" : true,
  "skip_question" : false,
  "postCommands" : true,
  "checkCommandInit" : ["git"],
  "async" : {
    "active" : true,
    "concurrency" : 10
  },
  "git" : {
    "cacheCredential" : true,
    "cacheCredentialTimeout" : 36000
  }
}
```

**priority** (bool) -> attiva la priorità della repo in fase di scarimento nella modalità sincrona _(vedi file es. file.json)_.

**skip_question** (bool) -> true, nella modalità interattiva evita di chiedere all'utente se vuole davvero creare le cartelle.

**postCommands** (bool) -> utile ad eseguire i comandi specificati sul repo dopo aver fatto il clone/pull _(vedi file es. file.json)_.

**checkCommandInit** (array) -> serve a garantire che i comandi esistano, in caso il tool esce tornando un errore.

**async** -> **active** (bool) -> definisce se attivare più processi di git simultaneamente (garantisce maggiore velocità).

**async** -> **concurrency** (int) -> definisce quanti processi avviare in simultaneamente
 
**git** -> **cacheCredential** (bool) -> utile a cachare le credenziali git.

**git** -> **cacheCredentialTimeout** (int) -> definisce per quanto tempo  cachare le credenziali git in secondi.

### file repo.json
Configurazione di esempio:

```
    {
      "title" : "test1",
      "group" : "personale",
      "priority" : 1,
      "uri" : "https://github.com/example/test1.git",
      "gitAction" : "fetch",
      "destination" : "./repos/personale/test1",
      "active" : true
    },
    {
      "title" : "test2",
      "group" : "personale",
      "priority" : 2,
      "uri" : "https://github.com/example/test2.git",
      "gitAction" : "pull",
      "destination" : "./repos/personale/test2",
      "active" : true
    },
    {
      "title" : "work1",
      "group" : "work",
      "priority" : 1,
      "uri" : "https://github.com/example/gitwork1.git",
      "gitAction" : "pull",
      "destination" : "./repos/work/work1",
      "postCommand" : ["composer install"],
      "active" : true
    },
    {
      "title" : "work2",
      "group" : "work",
      "priority" : 2,
      "uri" : "https://github.com/example/gitwork2.git",
      "gitAction" : "pull",
      "destination" : "./repos/work/work2",
      "postCommand" : ["composer install"],
      "active" : true
    }
```


### Prerequisites

Gli unici requisiti richiesti sono PHP 7+, Composer, e ovviamente Git command line. 

### Installing

Per iniziare ad usare il tool sarà necessario installare le dipendenze tramite composer:

```
composer install
```



## Running intercative mode

Lanciare lo script come segue:

```
php go.php
```

Sarà possibile selezionare una o più repo o tutte.

gif animata console.
![Alt Text](https://media.giphy.com/media/vFKqnCdLPNOKc/giphy.gif)

## Running parameter mode

Per visualizzare la lista di paramatri lanciare:

```
php go.php -h
```

immagine di esempio comando help
![Alt Text](https://media.giphy.com/media/vFKqnCdLPNOKc/giphy.gif)

di default, lo script cerca il config.json e il file repo.json sulla stessa cartella dello script,
ma è possibile utilizzare altri file passandoli nei parametri:

esempio:
```
php go.php -r altro_file_repo.json -c altro_file_confi.json
```

esempio su come eseguire le operazioni su uno o più gruppi:

```
php go.php -g "personale, work"
```

esempio su come eseguire le operazioni su determinate repo:

```
php go.php -n "test1, work2"
```

Se viene specificato il parametro **"-p"** viene attivato il post commands, anche se sul file di config è disattivo.


## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* [COLLECT](https://github.com/tightenco/collect) - Collect - Illuminate Collections
* [COLORS](https://github.com/kevinlebrun/colors.php) - Colors PHP Console
* [ASYNC](https://github.com/spatie/async) - Asynchronous and parallel PHP
* [GETOPTS](https://github.com/fostam/php-getopts?files=1) - Flexible PHP command line argument parser with automated help message generation and validation support.

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Roberto Calabrese**

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
* Inspiration
* etc
