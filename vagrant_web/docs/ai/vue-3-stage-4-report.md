# Vue-3-Migration – Stufe 4: Compat entfernen

Status: In Arbeit. Dieser Bericht ergänzt den verbindlichen [Vue-3-Migrationsvertrag](vue-3-migration-contract.md). Jede Teilstufe erhält vor dem nächsten Eingriff einen grünen Prüfstand und einen eigenen Git-Rücksprungpunkt.

## Teilstufe 4.1 – Vue-2-`model`-Optionen entfernen

- Die sieben verbliebenen Vue-2-`model`-Optionen wurden aus Toggle, Sternebewertung, Sidebar-Feld-Mixin, Keyword, Keyword-Eingabe, Bibelstelle und Bibelstellen-Eingabe entfernt.
- Bereits explizit angebundene Verbraucher behalten ihre bisherigen Props und Events (`value`/`isToggled`, `rating`/`rating-selected`, `value`/`input`). Es wurde kein öffentlicher Payload, API-Vertrag oder sichtbares Verhalten geändert.
- Die tatsächlich von der Vue-2-Sondersemantik abhängigen Kindbindungen sind jetzt explizit: Keyword und Bibelstelle erhalten ihr Fachobjekt per Prop und liefern den gespeicherten Event-Payload zurück. Die jeweilige Eingabekomponente ersetzt damit zuerst denselben Listeneintrag und emittiert anschließend weiterhin `updated` mit der vollständigen Liste.
- Der MaterialCreator bindet die beiden Listen explizit über `keywords`/`updated` und `bibleverses`/`updated`. Das Vue-3-`v-model:rating` der lokalen Rating-Komponente bleibt bestehen und ist keine Vue-2-Altstelle.
- Abschlussinventur: In `resources/js` existiert keine Komponenten- oder Mixin-Option `model` mehr. Die kritischen Inventarkategorien für Vue-2-Muster bleiben auf null.
- Abnahme: Production-Build, 37 Unit-Tests, ESLint ohne Fehler bei 323 dokumentierten Altwarnungen, Frontend-Inventar, Diff-Check und 16/16 funktionale WebKit-Tests auf Desktop und Mobile sind grün. Die Browser-Suite enthält weiterhin das globale Null-Gate für Vue-Compat-Warnungen.
- Grenze: `@vue/compat`, globaler MODE 2 und die Root-Render-Ausnahme sind noch aktiv. Sie werden erst in den folgenden Teilstufen umgeschaltet beziehungsweise entfernt. `php artisan db:seed` bleibt gemäß Vertrag dem isolierten Sail-/MySQL-Gate vorbehalten und wurde in dieser rein clientseitigen Teilstufe mangels verfügbarer Sail-/MySQL-Umgebung nicht durch einen Host-SQLite-Ersatz verfälscht.
