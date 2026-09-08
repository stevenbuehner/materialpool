# Übergabevertrag für `stevenbuehner/bible-verse-bundle`

## Zuständigkeit

Das Bundle ist ein separates, vom Auftraggeber verwaltetes Composer-Projekt. Der Materialpool ändert weder dessen Repository noch Tags oder Releases. Diese Datei beschreibt die Voraussetzungen für den später vom Auftraggeber bereitgestellten stabilen Release.

## Erforderliches Ziel

- Stabile, unveränderliche SemVer-Version statt `dev-develop`.
- Laufzeitunterstützung für PHP 8.4.
- Keine Abhängigkeit von einer konkreten Laravel-Version; falls Laravel-Komponenten benötigt werden, müssen sie Laravel 9 bis 13 in den jeweiligen Upgrade-Checkpoints unterstützen.
- Composer-2-kompatible Metadaten und PSR-4-Autoloading für `StevenBuehner\BibleVerseBundle\`.
- Keine produktive Abhängigkeit von den derzeit veralteten Dev-Paketen des Bundle-Repositories.

## Zu erhaltende PHP-Verträge

Der Materialpool verwendet mindestens:

- `StevenBuehner\BibleVerseBundle\Service\BibleVerseService`
- `StevenBuehner\BibleVerseBundle\Interfaces\BibleVerseInterface`
- `StevenBuehner\BibleVerseBundle\Entity\BibleVerse`
- `StevenBuehner\BibleVerseBundle\Exceptions\InvalidBookIdException`
- `StevenBuehner\BibleVerseBundle\Exceptions\InvalidBibleVerseRangeException`

Parsing, Zusammenführen, Bereichskonvertierung und deutsche Kurzbezeichnungen müssen kompatibel bleiben. Der Referenzfall `1001001` muss weiterhin als `1Mo 1,1` ausgegeben werden. Der Container-Key `BibleVerseService` bleibt im Materialpool ein Singleton derselben Serviceklasse.

## Zu erhaltende JavaScript-Verträge

Das unveränderte Vue-2-Frontend importiert Dateien direkt aus dem Composer-Paket. Der Release muss daher weiterhin diese Pfade und Exports enthalten:

- `js/out/BibleVerseService_de.js` mit dem Export `BibleVerseService`
- `js/in/BibleVerse.js` mit der Klasse beziehungsweise dem Default-Export `BibleVerse`

Eine Umstellung auf einen anderen Modulpfad oder ein anderes Modulformat ist während P1 nicht zulässig, weil sie eine separate Frontendänderung wäre.

## Übergabe und Abnahme

1. Der Auftraggeber stellt Versionsnummer und installierbaren stabilen Tag bereit.
2. Der Materialpool ersetzt ausschließlich die Composer-Referenz und aktualisiert das Lockfile.
3. `BundleBibleBackupContractTest`, Bibleverse-API-, Extraktions- und Handler-Tests müssen unverändert grün bleiben.
4. `npm ci` und der bestehende Production-Build müssen die direkten JavaScript-Imports weiterhin auflösen.
5. Eine notwendige Verhaltens- oder Exportänderung wird vorab als gesonderter Vorschlag vorgelegt und erst nach ausdrücklicher Freigabe umgesetzt.
