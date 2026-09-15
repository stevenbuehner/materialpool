# Autorisierung und Sichtbarkeit in der Materialsuche

## Status und Ziel

Dieses Dokument ist die technische Entscheidungs- und Umsetzungsvorlage für die
Integration der neuen Gruppen- und Rechteverwaltung in alle lesenden
Materialabfragen, insbesondere in `POST /pool/search/get`.

Ziel ist folgende Sicherheitsinvariante:

> Ein Material darf weder als Suchtreffer noch über Zähler, Pagination,
> Sortierung oder mitgeladene Relationen erkennbar sein, wenn der angemeldete
> Benutzer darauf kein Leserecht hat.

Die automatische Spracherkennung ist nicht Bestandteil dieses Vorhabens.

## Geprüfte Grundlagen

Die Vorlage wurde gegen die lokal installierten Skills `laravel-specialist`,
`laravel-patterns`, `laravel-best-practices`, `laravel-security`, `laravel-tdd`,
`testing-best-practices` und `laravel-verification` geprüft. Maßgebliche
Ergebnisse daraus sind:

- Policies und Gates autorisieren Operationen auf einzelnen Modellen.
- Eine Policy filtert keine Eloquent-Collection. Listen, Suche, Exporte und
  Relation-Queries benötigen deshalb eine Einschränkung auf Datenbankebene.
- Die Sichtbarkeit muss vor Suchbedingungen, Sortierung und Pagination Teil
  desselben SQL-Queries sein. Filtern nach `paginate()` ist sicherheitskritisch
  falsch und erzeugt unzutreffende Gesamtzahlen und leere Seiten.
- Controller koordinieren HTTP-Eingabe, Autorisierung, Query-Aufbau und
  Response. Wiederverwendbare Sichtbarkeitsregeln gehören in benannte
  Eloquent-Scopes; komplexe Suchlogik darf in einen fokussierten Query-Service
  ausgelagert werden.
- Ein auth-abhängiger Global Scope ist ungeeignet, weil er auch Jobs, Imports,
  CLI-Befehle und administrative Wartung unbemerkt einschränken würde.
- Request-Daten müssen validiert und kontrollierbare Attribute explizit
  zugewiesen werden. Sichtbarkeitsattribute dürfen nicht durch pauschale
  Massenzuweisung änderbar werden.
- Tests prüfen beobachtbares Verhalten mit echten Datenbankabfragen. Die
  vollständige Berechtigungsmatrix gehört auf Policy-/Scope-Ebene; wenige
  Endpunkttests belegen die korrekte Verdrahtung.
- Indexentscheidungen werden aus realen Query-Mustern und `EXPLAIN` mit
  repräsentativen MySQL-Daten abgeleitet, nicht allein aus einer `WHERE`-Klausel.

Projektregeln aus [Domänen-Invarianten](domain-invariants.md) und
[Qualitätssicherung](quality-gates.md) haben Vorrang vor allgemeinen
Skill-Empfehlungen.

## Bestandsaufnahme und Sicherheitsbefund

### Aktueller Ablauf

`SearchController::get()` ruft `turnRequestIntoQuery()` auf. Diese Methode
startet mit `Material::query()`, lädt `author`, `keywords`, `bibleverses` und
`resources`, ergänzt abhängig von den Suchgruppen mehrere Joins und paginiert
das Resultat anschließend.

Die Route ist authentifiziert. Eine Authentifizierung allein beantwortet aber
nicht, welche Datensätze der Benutzer lesen darf.

### Festgestellte Lücken

1. Die Materialsuche enthält derzeit keine benutzerbezogene
   Sichtbarkeitseinschränkung.
2. `MaterialPolicy::view()` erlaubt momentan jedes Material, weil Materialien
   noch kein eigenes Sichtbarkeitsmerkmal besitzen.
3. Die Suche lädt alle verknüpften Ressourcen. Dadurch können Metadaten einer
   privaten fremden Ressource im Treffer-Payload erscheinen.
4. Eine Resource-Typ-Suche arbeitet mit unbeschränkten Resource-Joins. Eine
   nicht lesbare Ressource kann dadurch das Auftauchen eines Materials auslösen
   und ihre Existenz indirekt verraten.
5. Der API-Materialindex bildet heute ohne `materials.view-all` nur
   Eigentümerschaft ab. Diese Semantik weicht vom Kommentar in der MaterialPolicy
   („öffentlich lesbar“) ab. Vor der Umsetzung muss ein einheitlicher Vertrag
   festgelegt werden.

### Betroffene Bereiche

- `app/Http/Controllers/SearchController.php`
- `app/Http/Controllers/Api/MaterialController.php`
- weitere klassische Material-Detail- und Listenrouten nach Inventur
- `app/Models/Material.php` und `app/Models/Resource.php`
- `app/Policies/MaterialPolicy.php` und `app/Policies/ResourcePolicy.php`
- Material-Request beziehungsweise ein neuer Search-Form-Request
- Materials-Tabelle, Factory und Seeder
- Such-, Policy-, API- und Migrationstests
- optional die Materialbearbeitung, falls `is_public` in der UI steuerbar wird

## Entscheidung: explizite Materialsichtbarkeit mit eigenem Leserecht

**Ausgangslage:** Ressourcen besitzen `is_public`; Materialien besitzen dieses
Feld nicht. Ohne eine explizite Materialeigenschaft lässt sich „öffentlich,
eigen oder mit `materials.view-all`“ nicht widerspruchsfrei auswerten. Eine aus
den angehängten Ressourcen abgeleitete Materialsichtbarkeit wäre mehrdeutig:
Ein Material kann keine, eine öffentliche und mehrere private Ressourcen
gleichzeitig besitzen.

**Entschieden:** Option A – `materials.is_public` wird als eigenes, geschütztes
Sichtbarkeitsmerkmal eingeführt. Das Flag allein gewährt jedoch keinen Zugriff:
Öffentliche fremde Materialien dürfen nur Benutzer mit dem neuen Systemrecht
`materials.view-public` lesen. Bestehende Materialien erhalten den Default
`true`; für den Bestandsschutz wird `materials.view-public` der Gruppe
„Standardnutzer“ zugeordnet. Neue Materialien erhalten beim Erstellen explizit
den vom autorisierten Request bestimmten Wert; fehlt er, gilt der dokumentierte
Produktdefault.

| Option | Vorteile | Nachteile/Risiken | Betroffene Bereiche | Rückbauaufwand |
| --- | --- | --- | --- | --- |
| A – eigenes `materials.is_public` plus `materials.view-public` (entschieden) | Eindeutige Domänenregel; öffentliche Lesbarkeit delegierbar; schnelle SQL-Filterung; Bestandsschutz über Standardgruppe | Neue Migration und neues Systemrecht; Public-Flag muss vor Mass Assignment geschützt sein | Datenbank, Rechtekatalog, Standardgruppe, Modell, Policy, Requests, Suche, API, Tests, optional UI | Mittel: Spalte, Recht und darauf aufbauende Logik in einer Folgemigration entfernen |
| B – nur Eigentümer oder `materials.view-all` | Keine neue Spalte; sehr einfache Regel | Alle heutigen fremden Materialien verschwinden für Standardnutzer; widerspricht dem bisherigen Policy-Kommentar und wahrscheinlich dem Bestandsschutz | Policy, Suche, API, Tests | Niedrig technisch, hoch fachlich |
| C – Sichtbarkeit aus Ressourcen ableiten | Keine Materialspalte | Mehrdeutige Semantik; relationale Änderungen verändern überraschend die Materialsichtbarkeit; teurere Queries; Gefahr von Informationslecks | Suche, Policy, Pivots, Attach/Detach, Events, Tests | Hoch |

**Design-/Nutzungswirkung:** Bei Option A muss entschieden werden, ob und an
welcher Stelle ein berechtigter Benutzer ein Material als öffentlich oder
privat kennzeichnet. Diese UI-Erweiterung ist ein eigenes, nachgelagertes
Arbeitspaket. Die sichere Backend-Grundlage kann zuvor mit einem festgelegten
Default umgesetzt werden.

**Noch benötigte Freigabe:** Die Datenmodell- und Rechteentscheidung ist
fachlich getroffen. Vor der Umsetzung bleiben gemäß Repository-Vertrag die
konkrete Migration sowie der Default für neu angelegte Materialien freizugeben.
Die Empfehlung lautet `true`, um das bisherige Erstellungsverhalten zu erhalten.

## Verbindlicher Autorisierungsvertrag

Für die Umsetzung gilt:

### Material lesen

Ein aktiver Benutzer darf ein Material lesen, wenn mindestens eine Bedingung
erfüllt ist:

1. `materials.created_by = user.id`,
2. `materials.is_public = true` **und** der Benutzer besitzt
   `materials.view-public`,
3. der Benutzer besitzt `materials.view-all`, oder
4. der Benutzer ist Global-Admin.

`materials.view-all` umfasst eigene, öffentliche und private Materialien. Das
Recht setzt weder `materials.view-public` noch ein Create- oder Update-Recht
voraus. Eigentümer dürfen ihr eigenes Material unabhängig von
`materials.view-public` lesen.

`materials.view-public` wird in `SystemPermissions` als festes Systemrecht im
Bereich „Material“ ergänzt. Es erlaubt ausschließlich das Lesen fremder
Materialien mit `is_public=true`; private fremde Materialien bleiben verborgen.
Das Recht wird der bestehenden Gruppe „Standardnutzer“ hinzugefügt und ist bei
neuen Benutzern dadurch weiterhin über die vorausgewählte Standardgruppe aktiv.

### Resource lesen

Ein aktiver Benutzer darf eine Ressource lesen, wenn mindestens eine Bedingung
erfüllt ist:

1. `resources.is_public = true`,
2. `resources.created_by = user.id`,
3. der Benutzer besitzt `resources.view-all`, oder
4. der Benutzer ist Global-Admin.

### Status und Global-Admin

- `invited` und `suspended` erhalten auch über einen Scope keine Daten.
- Global-Admins umgehen fachliche Rechte, nicht aber den Aktivstatus. Dies
  entspricht den vorhandenen Policy-`before()`-Methoden.
- Middleware bleibt die erste Abwehr für authentifizierte Requests; Scope und
  Policy bilden zusätzlich eine geschlossene Datenzugriffsgrenze.

### Direkter Zugriff und Nichtexistenz

Ein nicht lesbares einzelnes Material beziehungsweise eine nicht lesbare
einzelne Ressource soll mit `404 Not Found` statt `403 Forbidden` beantwortet
werden, damit die Existenz privater Datensätze nicht bestätigt wird. Laravel
unterstützt dies in Policies über `Response::denyAsNotFound()`.

### Collections und Relationen

- Jede Material-Collection verwendet `Material::visibleTo($user)`.
- Jede Resource-Collection verwendet `Resource::visibleTo($user)`.
- Ein lesbares Material macht eine daran hängende private fremde Ressource nicht
  automatisch lesbar.
- Suchtreffer laden deshalb `resources` mit demselben Resource-Scope.
- Keyword-, Autoren- und Bibelstellenrelationen folgen weiterhin dem
  Materialzugriff; für sie besteht derzeit kein eigener Leserechtekatalog.

## Laravel-konforme Zielarchitektur

### 1. Policy für Einzelobjekte

`MaterialPolicy::view(User $user, Material $material)` und
`ResourcePolicy::view(User $user, Resource $resource)` bleiben die maßgebliche
Autorisierung für Route Model Binding, Detailansicht und Einzeloperationen.

Geeignete Controller verwenden anschließend eines der etablierten Muster:

- Route Middleware `can:view,material`, wenn die Route genau ein gebundenes
  Material liest;
- `$this->authorize('view', $material)` beziehungsweise
  `Gate::authorize('view', $material)` bei zusammengesetzten Abläufen;
- `authorizeResource()` nur dort, wo ein vollständiger Resource-Controller dem
  Laravel-Aktionsschema folgt und dies zum vorhandenen Router passt.

`viewAny()` autorisiert lediglich, ob eine Collection-Funktion benutzt werden
darf. Es darf niemals als Ersatz für die Datensatzfilterung verstanden werden.

### 2. Lokale Scopes für Datenmengen

`Material` und `Resource` erhalten je einen expliziten lokalen Scope
`visibleTo(User $user)`. Für den Laravel-13-Stand ist ein mit `#[Scope]`
gekennzeichneter Scope zulässig. Bei der Umsetzung ist die im jeweiligen Modell
verwendete Formatierung beizubehalten.

Logische Form für normale aktive Benutzer mit `materials.view-public`:

```sql
WHERE (
    materials.created_by = :user_id
    OR materials.is_public = 1
)
```

Ohne `materials.view-public` reduziert sich die Bedingung auf
`materials.created_by = :user_id`. Besitzt der Benutzer `materials.view-all`
oder ist er Global-Admin, ergänzt der Scope keine Zeileneinschränkung. Für
inaktive Benutzer fügt er eine immer falsche Bedingung hinzu. Die
Resource-Variante verwendet weiterhin ihren bestehenden Vertrag auf
`resources`; für öffentliche Resources wird in diesem Vorhaben kein zusätzliches
Recht eingeführt.

Bewusste Grenzen:

- Kein auth-abhängiger Global Scope.
- Kein `Gate::allows()` pro Ergebniszeile.
- Keine in-memory-Filterung.
- Keine unqualifizierten Spaltennamen in Queries mit Joins.

Policy und Scope enthalten notwendigerweise dieselbe fachliche Aussage für
unterschiedliche Auswertungsformen. Eine eigene abstrakte
„Authorization-Engine“ wird in Version 1 nicht eingeführt. Ein expliziter
Konsistenztest schützt vor Auseinanderlaufen, ohne eine unnötige neue
Architekturschicht zu schaffen.

### 3. Fokussierter Such-Request

Ein `SearchMaterialsRequest` validiert die bestehende Payload, ohne gültige
Response-Felder oder Routen zu ändern:

- `q` ist ein begrenztes Array von Suchgruppen;
- jede Gruppe enthält eine begrenzte Anzahl von Kriterien;
- `type` ist ausschließlich `k`, `b`, `t` oder `*`;
- IDs und Bibelstellenbereiche sind Ganzzahlen in erlaubten Grenzen;
- Resource-Typen stammen ausschließlich aus dem bekannten Systemkatalog;
- Freitext besitzt eine sinnvolle Maximallänge;
- `per_page` ist eine Ganzzahl zwischen 1 und 100;
- `order_by` ist ausschließlich `created_at` oder `updated_at`.

Die konkreten Obergrenzen werden aus dem aktuellen UI-Verhalten abgeleitet und
als Request-Vertrag getestet. Das Query Builder Binding schützt SQL-Werte; die
Allowlist schützt dynamische Spalten und Typen. Ob `%` und `_` im Freitext als
Wildcards gelten, bleibt aus Kompatibilitätsgründen zunächst unverändert und
wird explizit getestet.

### 4. Such-Query vor Pagination einschränken

Der Aufbau beginnt in dieser Reihenfolge:

1. `Material::query()`;
2. `visibleTo($request->user())`;
3. qualifiziertes `select('materials.*')`;
4. berechtigt eingeschränkte Eager Loads;
5. validierte Suchgruppen;
6. stabile Sortierung mit eindeutigem Tie-Breaker;
7. `paginate()`.

Die Resource-Typ-Suche wird nicht mehr über einen frei sichtbaren
`resources{index}`-Join aufgebaut. Sie verwendet innerhalb der jeweiligen
ODER-Gruppe sinngemäß:

```php
$query->orWhereHas('resources', function (Builder $resourceQuery) use ($user, $types) {
    $resourceQuery->visibleTo($user)->whereIn('resources.type', $types);
});
```

Damit kann nur eine lesbare Ressource ein Material matchen. Der Resource-Join
ist für diesen Filter nicht mehr erforderlich und kann keine Duplikate oder
Informationslecks erzeugen. Keyword- und Bibelstellen-Joins für die bestehende
Relevanzsortierung werden gesondert erhalten und qualifiziert.

Das Eager Loading verwendet sinngemäß:

```php
'resources' => fn (Builder $query) => $query->visibleTo($user)
```

Die bestehende JSON-Struktur inklusive Pivot `limitation` und Pagination bleibt
unverändert. Eine Umstellung auf Laravel API Resources wäre allgemein sinnvoll,
ist hier aber bewusst nicht Bestandteil der Sicherheitskorrektur, weil sie den
bestehenden v1-Payload verändern könnte.

### 5. Dünner Controller, begrenzte Extraktion

`SearchController` soll Request, Benutzer, Query und Response koordinieren. Die
bereits umfangreiche Suchlogik kann in einen fokussierten
`MaterialSearchQuery` unter dem im Projekt etablierten Service-Namensraum
verschoben werden, wenn die Umsetzung dadurch testbarer wird.

Empfohlene Schnittstelle:

```php
public function build(User $user, array $criteria): Builder
```

Der Builder wird unpaginiert zurückgegeben; Sortierung und Pagination können
entweder vollständig dort erfolgen oder klar im Controller verbleiben. Es wird
keine generische Repository-Schicht eingeführt. Vor der Extraktion ist durch
Regressionstests festzuhalten, dass Relevanz, UND-/ODER-Semantik und Payload
unverändert bleiben.

### 6. Sichere Änderung von `is_public`

`materials.is_public` bleibt gegen Mass Assignment geschützt. Insbesondere darf
es nicht allein deshalb in `$fillable` aufgenommen werden, weil der
`MaterialRequest` es validiert. Store-/Update-Code weist den Wert nach
Autorisierung explizit zu. Unerwartete Payload-Schlüssel dürfen das Feld nicht
ändern.

Die Änderung der Sichtbarkeit ist eine Metadatenänderung und erfordert
`materials.update-metadata-own` beziehungsweise
`materials.update-metadata-all`. Beim Erstellen deckt `materials.create` den
initialen Wert ab.

### 7. Indizes und Abfrageplan

Die Migration ergänzt nicht blind einen zusammengesetzten Index. Zuerst werden
vorhandene Indizes auf `materials.created_by`, `materials.is_public`, Pivots und
Sortierspalten inventarisiert. Anschließend werden die wichtigsten Suchformen
mit repräsentativen Daten über MySQL `EXPLAIN` geprüft:

- normale Suche mit und ohne `materials.view-public`;
- Resource-Typ-Suche mit `whereHas`;
- Keyword-/Bibelstellen-Relevanzsuche;
- Datums- und Rating-Sortierung mit Pagination;
- Global-Admin beziehungsweise `materials.view-all` ohne Sichtbarkeitsfilter.

Erst danach wird der kleinste wirksame Index ergänzt. Zu prüfen sind ein
Einzelindex auf `is_public`, ein bestehender oder neuer Index auf `created_by`
und gegebenenfalls ein zweckgebundener zusammengesetzter Index. Redundante
Indizes und unnötige Schreibkosten sind zu vermeiden.

### 8. Caching

Version 1 führt keinen neuen Suchcache ein. Falls bei der Inventur bereits ein
Cachepfad gefunden wird, muss dessen Schlüssel mindestens Benutzer- oder
Sichtbarkeitskontext und eine Permission-Revision enthalten. Änderungen an
Gruppen, Rechten, Status und `is_public` müssen den Cache invalidieren. Ein
global geteilter Cache ohne Autorisierungskontext ist verboten.

## Arbeitspakete

Die Pakete sind so geschnitten, dass sie nacheinander von einem ausführenden
Agenten umgesetzt und einzeln geprüft werden können. Jedes Paket beginnt mit
einer Bestandsaufnahme und endet mit den genannten Akzeptanzkriterien.

### AP 0 – Entscheidung und unveränderliche Verträge festhalten

**Ziel:** Fachliche Freigabe herstellen und unbeabsichtigte API-/UX-Änderungen
verhindern.

**Aufgaben:**

1. Die getroffene Entscheidung `materials.is_public` plus
   `materials.view-public` als verbindlichen Produktvertrag festhalten.
2. Den Default `true` für neu angelegte Materialien bestätigen lassen.
3. Entscheiden, ob die Sichtbarkeit in dieser Stufe bereits in der UI editierbar
   wird oder zunächst nur backendseitig existiert.
4. Aktuelle Response-Struktur von `POST /pool/search/get` mit einem Contract-Test
   festhalten: Materialfelder, Relationen, Pivotdaten und Pagination-Metadaten.
5. Alle Material-Collection- und Detailrouten inventarisieren und in einer
   Checkliste erfassen.
6. Bestätigen, dass `%` und `_` im Suchtext weiterhin Wildcards sind oder als
   Literale behandelt werden sollen.

**Akzeptanz:** Die offenen Produktentscheidungen sind schriftlich beantwortet;
kein nachfolgendes Paket muss eine Sichtbarkeits- oder Payload-Semantik erraten.

**Abhängigkeiten:** keine.

### AP 1 – Sicherheitsregression zuerst als fehlschlagende Tests

**Ziel:** Die gegenwärtige Lücke reproduzierbar machen, bevor Produktivcode
geändert wird.

**Dateikandidaten:**

- `tests/Feature/SearchControllerTest.php`
- neue Policy-/Scope-Tests unter `tests/Feature/Authorization/`

**Aufgaben:**

1. Benutzer, Global-Admin, fremden Eigentümer sowie öffentliche und private
   Materials/Resources ausschließlich über Factories erzeugen.
2. Belegen, dass ein privates fremdes Material weder in `data` noch in `total`
   auftaucht.
3. Belegen, dass eine private fremde Ressource weder serialisiert wird noch bei
   Resource-Typ-Suche ein Material matchen lässt.
4. Tests für eigenes privates Material, öffentliches fremdes Material mit und
   ohne `materials.view-public`, `materials.view-all`, `resources.view-all` und
   Global-Admin ergänzen.
5. Mindestens einen Test mit mehreren Suchgruppen ergänzen, der verhindert, dass
   eine `orWhere`-Klammer die Sichtbarkeitsbedingung aushebelt.

**Akzeptanz:** Die neuen Sicherheitstests schlagen am erwarteten aktuellen
Verhalten fehl; bestehende Suchtests bleiben unverändert grün.

**Abhängigkeiten:** AP 0.

### AP 2 – Reversible Materialsichtbarkeit im Datenmodell

**Ziel:** Eine eindeutige, deploybare Sichtbarkeitsquelle und das zugehörige
Systemrecht schaffen.

**Dateikandidaten:**

- neue Migration `add_is_public_to_materials_table`
- `app/Models/Material.php`
- `app/Support/Authorization/SystemPermissions.php`
- `database/factories/MaterialFactory.php`
- Permission-Katalog, Standardgruppen-Synchronisierung und Migrationstests

**Aufgaben:**

1. Migration mit Boolean-Default `true` und ehrlichem `down()` über Artisan
   erzeugen; bestehende Migrationen nicht ändern.
2. Bestehende Zeilen durch den DB-Default sicher als öffentlich behandeln; bei
   großen Tabellen Lockdauer und phasenweisen Rollout bewerten.
3. Boolean-Cast und Factory-State `public`/`private` ergänzen.
4. `is_public` geschützt lassen und nicht pauschal in `$fillable` aufnehmen.
5. `materials.view-public` in den festen Katalog und die fachliche Gruppe
   „Material“ aufnehmen.
6. Das neue Recht idempotent der Gruppe „Standardnutzer“ zuweisen, ohne
   individuell konfigurierte Gruppenrechte zu überschreiben.
7. Vorhandene Indizes inventarisieren; Indexentscheidung bis AP 7 offenlassen,
   sofern kein klar notwendiger Basisindex freigegeben wurde.

**Tests:** Fresh-Migration, Upgrade mit vorhandenen Materials, Defaultwert,
Cast, Factory-States, vollständiger fester Permission-Katalog,
Standardgruppen-Zuweisung, Wiederholbarkeit der Synchronisierung und
Down-Migration ausschließlich auf isolierter Testdatenbank.

**Akzeptanz:** Bestehende Materials sind nach Upgrade als öffentlich markiert
und für Standardnutzer weiterhin sichtbar; Benutzer ohne
`materials.view-public` sehen fremde öffentliche Materials nicht; neue
Testdatensätze haben deterministische Sichtbarkeit; individuell konfigurierte
Gruppen und fremde Daten wurden nicht überschrieben oder gelöscht.

**Abhängigkeiten:** AP 0 und Freigabe der Migration.

### AP 3 – Policy und lokale Sichtbarkeitsscopes

**Ziel:** Einzel- und Collection-Autorisierung konsistent implementieren.

**Dateikandidaten:**

- `app/Models/Material.php`
- `app/Models/Resource.php`
- `app/Policies/MaterialPolicy.php`
- `app/Policies/ResourcePolicy.php`
- `tests/Feature/Authorization/MaterialResourcePolicyTest.php`
- neue Scope-Testklasse am relativen Modellpfad

**Aufgaben:**

1. `visibleTo(User $user)` auf beiden Modellen ergänzen.
2. MaterialPolicy an den freigegebenen Sichtbarkeitsvertrag anpassen.
3. Policy-Ablehnungen für Einzelobjekte als `404` abbilden.
4. Bestehende Global-Admin- und Aktivstatuslogik bewahren.
5. Policy und Scope nicht über `auth()` koppeln; Benutzer immer explizit
   übergeben.

**Tests:** Vollständige Matrix für öffentlich/privat, eigen/fremd,
mit/ohne View-Public, View-All/Global-Admin und active/invited/suspended. Ein
Konsistenztest prüft für dieselben Fixtures, dass `Gate::allows('view', model)`
genau der Zugehörigkeit zu `Model::visibleTo($user)` entspricht.

**Akzeptanz:** Policy und Scope liefern für alle Matrixfälle dasselbe Ergebnis;
der Scope erzeugt eine einzelne SQL-Abfrage und lädt keine Modelle zur
Autorisierungsentscheidung.

**Abhängigkeiten:** AP 2.

### AP 4 – Suchpayload validieren und Query sicher umbauen

**Ziel:** Sichtbarkeit ist Bestandteil des Such-SQL und kann durch keine
Suchkombination umgangen werden.

**Dateikandidaten:**

- neuer `app/Http/Requests/SearchMaterialsRequest.php`
- `app/Http/Controllers/SearchController.php`
- optional neuer fokussierter Service unter `app/Services/MaterialHandling/`
- `tests/Feature/SearchControllerTest.php`

**Aufgaben:**

1. Gültige bestehende Payloads inventarisieren und Form-Request-Regeln
   hinzufügen.
2. `visibleTo($user)` unmittelbar nach `Material::query()` anwenden.
3. Alle Sichtbarkeitsteile vollständig klammern und Tabellennamen qualifizieren.
4. Resource-Typ-Joins durch berechtigtes `orWhereHas('resources', ...)`
   ersetzen.
5. Resource-Eager-Load mit `Resource::visibleTo($user)` einschränken und
   `limitation` im Pivot erhalten.
6. Relevanzsortierung unverändert halten; bei Gleichstand einen stabilen
   eindeutigen Sortierschlüssel ergänzen.
7. Erst nach allen Filtern paginieren.
8. Nur wenn die Methode weiterhin unübersichtlich bleibt, die Query in den
   beschriebenen fokussierten Service extrahieren.

**Tests:** Leere Suche, Titel, Keyword inklusive Nachfahren, Autor, Bibelstelle,
Resource-Typ, mehrere UND-/ODER-Gruppen, beide Datumssortierungen, Rating,
Wildcards, maximale und ungültige Payload, stabile Pagination und korrekter
`total`-Wert. Jeder fachliche Suchtyp enthält mindestens einen nicht sichtbaren
Kontrolltreffer.

**Akzeptanz:** Kein nicht lesbares Material beeinflusst Treffer, Reihenfolge,
Gesamtzahl oder Seiten; keine nicht lesbare Resource wird serialisiert oder als
Match verwendet; der bestehende gültige Response-Vertrag bleibt gleich.

**Abhängigkeiten:** AP 1 und AP 3.

### AP 5 – Alle lesenden Materialpfade vereinheitlichen

**Ziel:** Die Suche ist kein isolierter Sonderfall; dieselbe Regel gilt auf
Detail-, API-, Auswahl- und Exportpfaden.

**Aufgaben:**

1. Inventur aus AP 0 routeweise abarbeiten.
2. Einzelrouten per Policy schützen und nicht lesbare IDs mit 404 beantworten.
3. Material- und Resource-Collections jeweils mit `visibleTo($user)` filtern.
4. Den bisherigen owner-only-Filter im API-Materialindex durch den gemeinsamen
   Scope ersetzen.
5. Kopieren, Attach/Detach und Foreign-ID-Endpunkte getrennt prüfen: Leserecht
   ersetzt dort niemals Create-/Update-Rechte.
6. Queue-, Import- und Konsolenabfragen prüfen; sie erhalten keinen impliziten
   Benutzer-Scope.

**Tests:** Pro Endpunkt ein erlaubter und ein verweigerter HTTP-Fall zusätzlich
zur zentralen Policy-Matrix; Payload-Kompatibilität für v1/v2; Gastzugriff nach
dem tatsächlich verwendeten Guard als Redirect oder 401.

**Akzeptanz:** Es existiert kein authentifizierter HTTP-Pfad, der Materialdaten
außerhalb des Vertrags liefert; Systemprozesse ohne HTTP-Benutzer funktionieren
unverändert.

**Abhängigkeiten:** AP 3 und AP 4.

### AP 6 – Sichtbarkeit sicher schreibbar machen

**Ziel:** Der Public-Status kann nur über die vorgesehenen Berechtigungen
geändert werden.

**Aufgaben:**

1. Material-Request um Boolean-Validierung ergänzen.
2. Beim Erstellen den initialen Wert nach `materials.create` explizit zuweisen.
3. Bei späterer Änderung `updateMetadata` autorisieren und den Wert explizit
   zuweisen.
4. Einen unerwarteten Schlüssel und Mass-Assignment-Versuche testen.
5. Falls freigegeben, vorhandene Vue-/Pinia-Adapter und Übersetzungen für einen
   Public-Schalter verwenden; Lade-, 403-, Fokus- und Mobilzustände ergänzen.

**Tests:** Erstellen mit/ohne Wert, erlaubte und verbotene Own-/All-Änderung,
unerwartete Payload-Schlüssel, Global-Admin und gesperrter Benutzer. Bei UI-
Umsetzung zusätzlich Komponenten- und ein fokussierter Browsertest.

**Akzeptanz:** Kein Benutzer kann `is_public` ohne das passende Create- oder
Metadata-Recht ändern; direkte Massenzuweisung öffnet keinen Datensatz.

**Abhängigkeiten:** AP 2, AP 3 und gesonderte UI-/API-Freigabe.

### AP 7 – Performance und Indexentscheidung

**Ziel:** Sicherheitsfilterung ohne vermeidbare Verschlechterung der Suche.

**Aufgaben:**

1. Repräsentativen anonymisierten Testdatenumfang herstellen.
2. Für die in „Indizes und Abfrageplan“ genannten Queries SQL und `EXPLAIN`
   dokumentieren.
3. Nur den nachweislich wirksamen Index in einer reversiblen Migration ergänzen.
4. Prüfen, dass `whereHas` keine N+1-Abfrage erzeugt und die eingeschränkten
   Relationen eager geladen werden.
5. Langsamste Suchtests messen; keine brittle Assertion auf eine zufällige
   exakte Millisekundenzahl einführen.

**Akzeptanz:** Queryplan und Indexentscheidung sind nachvollziehbar
dokumentiert; keine N+1-Abfrage; Write-Kosten und redundante Indizes wurden
bewertet.

**Abhängigkeiten:** AP 4; neue Indexmigration erneut freigabepflichtig.

#### Ausführungsstand 2026-09-15

Die statische Inventur der Migrationen ergab für `materials` ausschließlich
den Primärschlüssel sowie den vorhandenen Index `material_flag`. Insbesondere
existieren derzeit keine Indizes auf `materials.created_by`,
`materials.is_public` oder `materials.updated_at`. Die für
`material_resource` und `bibleverse_material` benötigten Pivot-Indizes sind
vorhanden; bei `resources` bestehen bereits Indizes auf `type`, `created_by`,
`updated_at` und die Kombination `is_public, created_by`.

Die relevanten aktuellen Query-Formen sind:

- Materiallisten: Sichtbarkeitsbedingung auf `created_by` und optional
  `is_public`, sortiert nach `updated_at`.
- Suche: dieselbe Sichtbarkeitsbedingung, optional `whereHas(resources)` mit
  Resource-Sichtbarkeit und `resources.type`.
- Keyword- und Bibelstellensuche: Sichtbarkeitsbedingung plus Pivot-Joins und
  Relevanzsortierung.

Die lokale Sail-/MySQL-Testumgebung war bei der Inventur nicht verfügbar
(`Docker or Podman is not running`). Deshalb wurden weder `EXPLAIN`-Pläne noch
Laufzeitmessungen erzeugt und bewusst keine Indexmigration angelegt. Ein
zusammengesetzter Index wäre ohne repräsentative Daten spekulativ und könnte
die Schreibkosten unnötig erhöhen. Die Entscheidung bleibt offen, bis die
isolierte Datenbank `testing` gemäß Qualitätssicherung verfügbar ist; dann sind
die in Abschnitt „Indizes und Abfrageplan“ genannten Varianten mit `EXPLAIN`
nachzuholen und nur der nachweislich wirksame Index separat freizugeben.

### AP 8 – Vollständige Verifikation und Übergabe

**Ziel:** Sicherheits-, Regressions- und Deploymentreife nachweisen.

**Reihenfolge:**

1. PHP-Syntax der geänderten Dateien im PHP-8.4-Sail-Container.
2. Neue Policy-/Scope-Tests.
3. `SearchControllerTest` und betroffene API-/Materialtests.
4. Isolierte MySQL-Migrationsprüfung mit explizit aufgelöster
   `DB_DATABASE=testing`; niemals Entwicklungsdaten verwenden.
5. Vollständige PHPUnit-Suite und Prüfung gegen die dokumentierte
   Mindesttestzahl.
6. `composer validate --strict` und `composer audit --locked`.
7. Bei Frontendänderung `npm run test:ci`, Production-Build, fokussierte
   Playwright- und visuelle Prüfung auf Desktop und Mobil.
8. `git diff --check` und Abschlussbericht nach
   [Qualitätssicherung](quality-gates.md).

**Akzeptanz:** Alle ausgeführten Gates sind mit Ergebnis dokumentiert. Nicht
ausführbare Gates haben einen konkreten Grund. Bekannte Baseline-Fehler werden
von neuen Regressionen getrennt. Verbleibende Sicherheits- oder
Performance-Risiken sind vor Merge sichtbar.

**Abhängigkeiten:** alle umgesetzten Pakete.

## Testmatrix

Die folgende Matrix ist mindestens auf Scope- und Policy-Ebene abzudecken:

| Benutzer | Material | Erwartung Material | private fremde Resource im Payload | Resource darf Typ-Match auslösen |
| --- | --- | --- | --- | --- |
| aktiv, `materials.view-public` | öffentlich, fremd | sichtbar | nein | nein |
| aktiv, kein Material-Leserecht | öffentlich, fremd | unsichtbar | nein | nein |
| aktiv, kein Material-Leserecht | privat, eigen | sichtbar | nur eigene/öffentliche Resources | nur eigene/öffentliche Resources |
| aktiv, `materials.view-public` | privat, fremd | unsichtbar | nein | nein |
| aktiv, `materials.view-all` | privat, fremd | sichtbar | nein ohne `resources.view-all` | nein ohne Resource-Leserecht |
| aktiv, `materials.view-public` und `resources.view-all` | öffentlich, fremd | sichtbar | ja | ja |
| aktiv, beide View-All-Rechte | privat, fremd | sichtbar | ja | ja |
| aktiver Global-Admin | beliebig | sichtbar | ja | ja |
| invited oder suspended | beliebig | unsichtbar/Request abgewiesen | nein | nein |

Zusätzliche Angriffsfälle:

- eine ODER-Bedingung hebt den äußeren Sichtbarkeitsscope nicht auf;
- `total`, `last_page` und leere Folgeseiten verraten keine versteckten Treffer;
- ein privater Resource-Typ verrät weder Ressource noch Material;
- ungültige `order_by`-, Typ- und ID-Werte erreichen keine dynamischen
  SQL-Bestandteile;
- ein unerwartetes `is_public` in einem nicht dafür vorgesehenen Payload ändert
  keinen Datensatz;
- nicht lesbare direkte IDs liefern 404;
- Permission-Änderungen wirken beim nächsten Request und werden nicht durch
  einen falsch geteilten Cache verdeckt.

## Risiken und Rückbau

- **Semantikänderung:** Der größte fachliche Punkt ist nicht der Scope, sondern
  die Einführung privater Materialien. Deshalb ist AP 0 zwingend.
- **Join-Komplexität:** Die bestehende Relevanzsortierung kann bei mehreren
  Suchgruppen Zeilen vervielfachen. Umbauten müssen durch Contract- und
  Reihenfolgetests abgesichert werden.
- **Payload-Kompatibilität:** Eingeschränkte Resource-Relationen ändern den
  Inhalt nur dort, wo heute bereits ein Informationsleck besteht. Andere Felder
  dürfen in diesem Vorhaben nicht nebenbei umgebaut werden.
- **Performance:** Eine ODER-Sichtbarkeitsbedingung kann je nach Datenverteilung
  einen Index-Scan erzeugen. `EXPLAIN` entscheidet über den Index, nicht eine
  pauschale Konvention.
- **Rollout:** Code, der `is_public` liest, darf erst nach verfügbarer Spalte
  ausgerollt werden, sofern Deployment keine atomare Migration garantiert.
- **Rückbau:** Die Such- und Policyänderungen sind code-seitig direkt
  rückbaubar. Das Entfernen der Spalte ist potenziell datenverlustbehaftet,
  sobald Benutzer private Werte gesetzt haben, und muss dann als neue
  freigegebene Migration erfolgen.

## Nicht Bestandteil

- direkte Benutzerrechte außerhalb von Gruppen;
- Änderung des Global-Admin-Vertrags;
- neuer Suchindex oder externe Search Engine;
- Volltextsuche oder geänderte Ranking-Fachlogik;
- grundlegende Umstellung bestehender v1/v2-Responses auf API Resources;
- globaler Benutzer-Scope auf Material oder Resource;
- automatische Spracherkennung.
