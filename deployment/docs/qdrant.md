# Qdrant für Materialpool einrichten

Qdrant speichert den ableitbaren Index der Kontextsuche. Materialpool installiert Qdrant nicht selbst. Für neue Proxmox-Installationen ist ein eigener nativer, unprivilegierter Qdrant-LXC vorgesehen. Alternativ lässt sich eine bereits laufende Qdrant-Instanz anbinden. In beiden Fällen benötigt Materialpool die REST-URL und einen API-Key mit Schreibrechten. Die Sail-Vorgabe `http://qdrant:6333` ist keine Produktionsadresse.

Die Befehle sind für eine Root-Shell auf dem jeweils genannten System gedacht. Beispieladressen vor dem Kopieren ersetzen; die CTID wird interaktiv abgefragt. Schlüssel nie in Shell-Befehle, Tickets, Screenshots oder gemeinsam lesbare Logs schreiben. Vor einem großen Indexaufbau CPU, RAM und SSD-Bedarf mit repräsentativen Dokumenten messen; die Community-Script-Defaults von 1 CPU, 1 GiB RAM und 5 GiB Disk sind keine bestätigte Materialpool-Kapazität. Hierzu auch den offiziellen [Qdrant-Skill zur Dimensionierung](https://www.skills.sh/qdrant/skills/qdrant-sizing) beachten.

## 1) Installation auf Proxmox

### Privates Netz und Container

1. Auf dem **Proxmox-Host** ein privates Netz für den Qdrant-LXC bereitstellen. Vor der Installation die Proxmox-Firewall auf Datacenter-, Node-, Container- und Netzinterface-Ebene aktivieren. Für diesen Container eingehend zunächst alles blockieren. Nach Aktivierung des API-Keys nur TCP **6333** von der festen IP des Laravel-LXC erlauben; TCP **6334** (gRPC) benötigt Materialpool nicht. Keine öffentliche IP und keine Portweiterleitung verwenden. Bei Zugriff über ein nicht vollständig vertrauenswürdiges Netz vor der Freigabe zusätzlich TLS auf Qdrant oder einem Proxy einrichten. Die Wirksamkeit der Regeln später von einem nicht erlaubten Netz aus prüfen. Siehe [Proxmox-Firewall](https://pve.proxmox.com/pve-docs/chapter-pve-firewall.html) und [Qdrant-Sicherheit](https://qdrant.tech/documentation/operations/security/).
2. Die aktuelle [Community-Script-Seite](https://community-scripts.org/scripts/qdrant), den [Script-Quelltext](https://github.com/community-scripts/ProxmoxVE/blob/main/ct/qdrant.sh) und dessen verlinkte Installationslogik vor dem Ausführen prüfen. Der folgende Befehl lädt und startet Fremdcode auf dem Proxmox-Host; der Installer kann Qdrant zunächst ohne API-Key öffnen:

   ```bash
   bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/qdrant.sh)"
   ```

3. Im Assistenten **Advanced** wählen. Container-ID, unprivilegierten LXC, Debian 13, private statische IP, Gateway, DNS, Storage und anhand der Indexgröße bemessene CPU/RAM/Disk festlegen. Anschließend auf dem **Proxmox-Host** die Werte kontrollieren und den Container betreten:

   ```bash
   read -r -p 'Qdrant-CTID: ' QDRANT_CTID
   pct config "$QDRANT_CTID"
   pct status "$QDRANT_CTID"
   pct enter "$QDRANT_CTID"
   ```

### Qdrant im eigenen LXC absichern

4. In der **Qdrant-LXC-Root-Shell** Dienst, tatsächliche Konfiguration und Datenpfade prüfen. Die bisher dokumentierten Pfade sind `/etc/qdrant/config.yaml`, `/var/lib/qdrant/storage` und `/var/lib/qdrant/snapshots`; bei Abweichungen gelten die installierte Unit und Konfiguration:

   ```bash
   systemctl status qdrant --no-pager
   systemctl cat qdrant
   ls -l /etc/qdrant/config.yaml
   grep -nE '^(service:|storage:|  (host|http_port|storage_path|snapshots_path):)' /etc/qdrant/config.yaml
   ls -ld /var/lib/qdrant /var/lib/qdrant/storage /var/lib/qdrant/snapshots
   ```

5. Falls noch kein API-Key eingerichtet ist, einen eigenen zufälligen Key als root-only `EnvironmentFile` erzeugen und per systemd-Drop-in laden. Qdrant-Umgebungsvariablen haben laut [Qdrant-Konfiguration](https://qdrant.tech/documentation/operations/configuration/) Vorrang vor YAML. Ist bereits ein Key aktiv, dessen Herkunft und Wirkung prüfen, statt einen zweiten widersprüchlichen Wert zu setzen.

   ```bash
   test -d /etc/qdrant
   umask 077
   printf 'QDRANT__SERVICE__API_KEY=%s\n' "$(openssl rand -hex 32)" > /etc/qdrant/materialpool.env
   chmod 600 /etc/qdrant/materialpool.env
   install -d -m 755 /etc/systemd/system/qdrant.service.d
   cat > /etc/systemd/system/qdrant.service.d/10-materialpool-key.conf <<'EOF'
   [Service]
   EnvironmentFile=/etc/qdrant/materialpool.env
   EOF
   systemctl daemon-reload
   systemctl restart qdrant
   systemctl is-active qdrant
   ```

   Den Key nur in einer **geschützten lokalen Konsole** zum späteren verdeckten Eingeben im Laravel-LXC einmalig anzeigen und sicher übertragen; die Ausgabe weder protokollieren noch weiterleiten:

   ```bash
   sed -n 's/^QDRANT__SERVICE__API_KEY=//p' /etc/qdrant/materialpool.env
   ```

6. Im **Qdrant-LXC** Bereitschaft und Authentifizierung getrennt prüfen. `/readyz` ist laut [Qdrant-Monitoring](https://qdrant.tech/documentation/operations/monitoring/) auch mit aktiviertem Key öffentlich. Erst `/aliases` prüft die Authentifizierung. Ohne Key muss der erste `/aliases`-Aufruf `401` oder `403` liefern; mit Key muss er erfolgreich sein:

   ```bash
   curl -fsS --max-time 5 http://127.0.0.1:6333/readyz
   curl -s -o /dev/null -w '%{http_code}\n' --max-time 5 http://127.0.0.1:6333/aliases
   . /etc/qdrant/materialpool.env
   curl -fsS --max-time 5 -H "api-key: $QDRANT__SERVICE__API_KEY" http://127.0.0.1:6333/aliases
   unset QDRANT__SERVICE__API_KEY
   ```

   Bei Fehlern `journalctl -u qdrant -n 100 --no-pager` lokal prüfen und vor Weitergabe von Ausgaben Geheimnisse und Nutzdaten entfernen. Erst jetzt TCP 6333 für die feste Laravel-IP freigeben.

### Materialpool verbinden und Betrieb prüfen

7. Im **Laravel-LXC** als root die private Qdrant-Adresse und den Key interaktiv konfigurieren. Als URL `http://<PRIVATE-QDRANT-IP>:6333` angeben, bei TLS `https://...`. Der Command fragt den Key verdeckt ab, prüft `/readyz` und authentifiziert `/aliases`. Er schreibt `QDRANT_URL` und `QDRANT_API_KEY` nur nach erfolgreichem Test in die Shared-`.env`; eine leere Key-Eingabe übernimmt bei erneuter Konfiguration den bisherigen Key.

   ```bash
   php /srv/materialpool/current/artisan context-search:qdrant:configure
   runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status
   ```

   Die Statusanzeige soll die richtige URL und „API-Key konfiguriert: ja“ melden, ohne den Key auszugeben. Für einen unabhängigen Test aus dem **Laravel-LXC** den Key verdeckt eingeben:

   ```bash
   QDRANT_URL='http://10.20.30.40:6333'
   read -r -s -p 'Qdrant-API-Key: ' QDRANT_KEY; printf '\n'
   curl -fsS --connect-timeout 2 --max-time 10 -H "api-key: $QDRANT_KEY" "$QDRANT_URL/aliases"
   unset QDRANT_KEY
   ```

8. Falls Ollama und Kontextsuche eingerichtet werden sollen, im **Laravel-LXC** den vorhandenen Assistenten ausführen:

   ```bash
   php /srv/materialpool/current/artisan context-search:configure
   ```

   Er prüft Ollama-Server, Modell-Digest, Dimensionen und Probevektor. Ohne Server oder bei Abbruch bleibt `CONTEXT_SEARCH_ENABLED=false`. Der Release-Updater startet diesen Dialog nicht. Auch `CONTEXT_SEARCH_ENABLED=true` ist keine Freigabe für produktive Index-Worker oder Indexdispatch; dafür gilt der [Kontextsuche-Queue-Vertrag](../../docs/ai/context-search-queue-change-contract.md).

9. Auf dem **Proxmox-Host** den Qdrant-LXC samt tatsächlichem Datenpfad sichern. Zusätzliche Mountpoints müssen in der Sicherung enthalten sein. `pct config "$QDRANT_CTID"` und den Backup-Job kontrollieren und einen Restore **in einem anderen, isolierten Test-LXC** prüfen. Qdrant-Snapshots nicht dauerhaft auf derselben SSD belassen. Die Qdrant-Sicherung ersetzt nicht die MariaDB- und Originaldatei-Backups von Materialpool. Siehe [Deployment-Testanleitung](testing.md#n-backup-check) und [Qdrant-Snapshots](https://qdrant.tech/documentation/snapshots/).

## 2) Installation bereits anderweitig vorhanden

Dieser Weg verbindet Materialpool mit einer **bestehenden** Qdrant-Instanz, etwa auf einem anderen Server, in einer VM oder bei einem Qdrant-Anbieter. Die folgenden Befehle installieren oder verändern den fremden Dienst nicht. Vor Änderungen an Authentifizierung, Firewall oder Datenablage eines produktiven Bestandsservers dessen Betreiber und andere Clients einbeziehen.

1. Vom Betreiber die vollständige **REST-Basis-URL** (Schema, Host, gegebenenfalls Port und Proxy-Pfad), einen API-Key mit Lese- und **Schreibrechten**, die TLS-Vertrauenskette, Erreichbarkeit vom Laravel-LXC sowie Backup- und Kapazitätsgrenzen erhalten. Ein Read-only-Key reicht für Indexierung, Collection- und Aliasverwaltung nicht. Materialpool benötigt REST, keinen offenen gRPC-Port 6334. Bei gemeinsam genutzter Instanz Collection-Präfix und aktiven Alias mit dem Betreiber abstimmen. Die Vorgaben stehen in `config/context_search.php` (`QDRANT_COLLECTION_PREFIX`, `QDRANT_ACTIVE_ALIAS`).
2. Auf dem **Qdrant-Zielsystem beziehungsweise beim Anbieter** sicherstellen, dass Datenendpunkte einen API-Key verlangen und nur der Laravel-LXC über eine geschützte Verbindung zugreifen kann. TLS mit gültigem Zertifikat verwenden, wenn das Netz nicht vollständig vertrauenswürdig ist. `/readyz` allein belegt keinen Schutz. Bei selbst betriebenen Instanzen sind `service.api_key` oder `QDRANT__SERVICE__API_KEY` die offiziellen Konfigurationswege; eine Änderung am Bestandsserver mit anderen Clients gesondert planen. Siehe [Qdrant-Sicherheit](https://qdrant.tech/documentation/operations/security/).
3. Im **Laravel-LXC** die Verbindung mit verdeckt eingegebenem Key testen. Die URL durch die tatsächliche HTTPS-Adresse ersetzen. Der Erfolg bei `/aliases` belegt Authentifizierung und API-Erreichbarkeit, noch keine Schreibrechte. Schreibrechte über die vereinbarte Key-Rolle oder einen gezielten Test in einer isolierten Test-Collection des Betreibers nachweisen, ohne bestehende Collections zu ändern.

   ```bash
   QDRANT_URL='https://qdrant.example.internal:6333'
   read -r -s -p 'Qdrant-API-Key: ' QDRANT_KEY; printf '\n'
   curl -fsS --connect-timeout 2 --max-time 10 "$QDRANT_URL/readyz"
   curl -fsS --connect-timeout 2 --max-time 10 -H "api-key: $QDRANT_KEY" "$QDRANT_URL/aliases"
   unset QDRANT_KEY
   ```

   TLS-Zertifikate regulär prüfen lassen; kein `curl -k` verwenden. Bei Fehlern zuerst DNS, Route, Firewall, Zertifikat, Port und Key prüfen. Einen unverschlüsselten `http://`-Endpunkt nur in einem vollständig vertrauenswürdigen, abgeschotteten Netz verwenden.

4. Im **Laravel-LXC** den geprüften Endpunkt und denselben Key über den vorhandenen Command eintragen und den Status kontrollieren:

   ```bash
   php /srv/materialpool/current/artisan context-search:qdrant:configure
   runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status
   ```

   Der Command speichert die Verbindung nur nach erfolgreichem Test. Danach bei Bedarf `php /srv/materialpool/current/artisan context-search:configure` für Ollama und Kontextsuche ausführen. Die Freigabegrenze für produktive Index-Worker aus Abschnitt 1 gilt unverändert. Den bestehenden Qdrant-Server und seine Collections nicht zurücksetzen, leeren oder neu erzeugen. Sein Betreiber verantwortet Backup, Restore, Schlüsselwechsel und Kapazitätsüberwachung; Materialpool-MariaDB und Originaldateien bleiben separat zu sichern.
