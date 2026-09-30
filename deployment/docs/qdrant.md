# Qdrant für Materialpool einrichten

Qdrant speichert den ableitbaren Index der Kontextsuche. Materialpool installiert Qdrant nicht selbst. Für neue Proxmox-Installationen ist ein eigener nativer, unprivilegierter Qdrant-LXC vorgesehen. Alternativ lässt sich eine bereits laufende Qdrant-Instanz anbinden. In beiden Fällen benötigt Materialpool die REST-URL und einen API-Key mit Schreibrechten. Die Sail-Vorgabe `http://qdrant:6333` ist keine Produktionsadresse.

Die Befehle sind für eine Root-Shell auf dem jeweils genannten System gedacht. Beispieladressen vor dem Kopieren ersetzen; die CTID wird interaktiv abgefragt. Schlüssel nie in Shell-Befehle, Tickets, Screenshots oder gemeinsam lesbare Logs schreiben. Vor einem großen Indexaufbau CPU, RAM und SSD-Bedarf mit repräsentativen Dokumenten messen; die Community-Script-Defaults von 1 CPU, 1 GiB RAM und 5 GiB Disk sind keine bestätigte Materialpool-Kapazität. Hierzu auch den offiziellen [Qdrant-Skill zur Dimensionierung](https://www.skills.sh/qdrant/skills/qdrant-sizing) beachten.

## 1) Installation auf Proxmox

### Privates Netz im Proxmox-Webinterface anlegen

Die folgende Anleitung verwendet eine **SDN Simple Zone mit SNAT**. Sie gilt, wenn Laravel- und Qdrant-LXC auf **demselben Proxmox-Node** laufen. Das VNet ist ohne physische Bridge-Ports vom LAN getrennt; der Proxmox-Node ist sein Gateway und ermöglicht ausgehende Verbindungen per SNAT. Das ist für den Qdrant-Installer und spätere Paketdownloads erforderlich. SNAT beschränkt ausgehende Ziele nicht auf das Internet; falls nötig, gelten zusätzlich passende Egress-Regeln. Der Laravel-LXC behält seine vorhandene Netzkarte für Reverse Proxy und Updates und bekommt eine zweite für Qdrant. Ein VNet auf einem anderen Node ist bei einer Simple Zone **nicht** mit diesem verbunden; dafür ist ein gesondertes VLAN-, VXLAN- oder geroutetes Konzept nötig. [Proxmox-SDN: Simple Zone und SNAT](https://github.com/proxmox/pve-docs/blob/master/pvesdn.adoc).

Die Beispielwerte `mpzone`, `mpqnet` und `172.28.63.0/24` nur verwenden, wenn sie noch frei sind. Im Beispiel erhält der Proxmox-Node `172.28.63.1`, Laravel `172.28.63.10` und Qdrant `172.28.63.20`. Ein bestehendes Netz mit diesem CIDR würde zu falschem Routing führen.

| Gerät | Proxmox-Netzkarte | Bridge/VNet | IPv4-Adresse | Gateway | Aufgabe |
| --- | --- | --- | --- | --- | --- |
| Proxmox-Node | SDN-VNet `mpqnet` | `mpqnet` | `172.28.63.1/24` | vorhandener Node-Gateway | Gateway und SNAT für das private Netz |
| Qdrant-LXC | `net0` / `eth0` | `mpqnet` | `172.28.63.20/24` | `172.28.63.1` | Qdrant-REST-API und ausgehende Updates |
| Materialpool-LXC | `net0` / `eth0` | bisherige Bridge, z. B. `vmbr0` | bisherige Adresse | bisheriger Gateway | Reverse Proxy, DNS und Updates |
| Materialpool-LXC | `net1` / `eth1` | `mpqnet` | `172.28.63.10/24` | **leer** | direkte Verbindung zu Qdrant |

Die Interface-Namen sind Beispiele für neue Container. Bei einem bestehenden Materialpool-LXC die tatsächlichen Namen unter **LXC → Network** ablesen; die vorhandene Netzkarte samt IP und Gateway nicht ersetzen.

1. Im Proxmox-Webinterface den **Node auswählen**, auf dem beide LXC laufen sollen. Unter **System → Network** und auf dem Router die vorhandenen Netze ansehen. In der Node-Shell zusätzlich die Routen prüfen; `172.28.63.0/24` darf noch nicht verwendet werden:

   ```bash
   ip -4 route
   ip -4 address
   ```

2. Links **Datacenter → SDN → Zones → Add → Simple** öffnen. Als **ID** `mpzone` eintragen. Unter **Nodes** nur den gewählten Node auswählen. **IPAM** beim Standard `pve` belassen; DHCP für dieses Beispiel nicht einschalten, weil beide LXC feste Adressen erhalten. Mit **Add** speichern.
3. **Datacenter → SDN → VNets → Create** öffnen. Als **ID** `mpqnet`, als **Zone** `mpzone` wählen und speichern. Das VNet wird nach dem Anwenden als Bridge `mpqnet` auf dem Node verfügbar; es braucht keinen physischen Port und keine Änderung an `vmbr0`.
4. In **Datacenter → SDN → VNets** `mpqnet` auswählen, den Bereich **Subnets** öffnen und **Create** wählen. **Subnet** `172.28.63.0/24`, **Gateway** `172.28.63.1` und **SNAT** aktiviert setzen. Kein DHCP-Range anlegen. Speichern. Der Node übernimmt die Gateway-Adresse und setzt ausgehende Verbindungen aus dem VNet um; SNAT allein ist keine Regel für den Zugriff von Laravel auf Qdrant.
5. **Datacenter → SDN → Apply** anklicken und auf einen erfolgreichen Abschluss warten. In der **Node-Shell** das neue Interface prüfen; `mpqnet` muss `172.28.63.1/24` tragen:

   ```bash
   ip -4 address show dev mpqnet
   ip -4 route show 172.28.63.0/24
   ```

   Wenn das VNet fehlt oder der Apply-Vorgang scheitert, **vor der LXC-Installation** im SDN-Status und Node-Log die Ursache prüfen. Weder `vmbr0` noch den bestehenden Gateway-Eintrag zum Ausprobieren verändern. Die Proxmox-Dokumentation beschreibt das Anwenden der SDN-Konfiguration und die lokale Reichweite einer Simple Zone. [Proxmox-SDN](https://github.com/proxmox/pve-docs/blob/master/pvesdn.adoc).

### Qdrant-LXC im privaten Netz installieren

6. Die aktuelle [Community-Script-Seite](https://community-scripts.org/scripts/qdrant), den [Script-Quelltext](https://github.com/community-scripts/ProxmoxVE/blob/main/ct/qdrant.sh) und dessen verlinkte Installationslogik vor dem Ausführen prüfen. Der folgende Befehl lädt und startet Fremdcode auf dem Proxmox-Host:

   ```bash
   bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/qdrant.sh)"
   ```

7. Im Script-Assistenten **Advanced** wählen und die Abfragen für CTID, Storage, Debian 13, **unprivilegierten** Container sowie CPU/RAM/Disk passend zur Last beantworten. Für die **einzige Netzkarte** des Qdrant-LXC `mpqnet` als Bridge/VNet, `172.28.63.20/24` als **statische IPv4**, `172.28.63.1` als IPv4-Gateway und **Firewall: Yes** wählen. Einen DNS-Server eintragen, den der Container über den Node-Gateway erreichen kann. Die genauen Feldnamen des Community-Scripts können sich ändern; entscheidend sind die Werte in der Tabelle und die Nachkontrolle in Schritt 8. Weder eine zweite Karte an `vmbr0` noch eine Portweiterleitung für Qdrant anlegen. Wenn `mpqnet` nicht auswählbar ist oder der Installer damit keine ausgehende Verbindung herstellen kann, SDN-Apply, Subnetz, SNAT und DNS prüfen, bevor die Installation fortgesetzt wird.
8. Nach der Installation im Proxmox-Webinterface **Qdrant-LXC → Network** öffnen: Es muss genau **eine** Karte `net0` mit **Bridge: `mpqnet`**, **IPv4/CIDR: `172.28.63.20/24`**, **Gateway: `172.28.63.1`** und aktivierter **Firewall** geben. Unter **Qdrant-LXC → DNS** den gewählten DNS-Server kontrollieren. Falls ein Wert falsch ist, den neuen Qdrant-LXC kontrolliert stoppen, unter **Network → net0 → Edit** berichtigen und wieder starten; einen produktiv genutzten Container dabei nicht ohne Wartungsfenster unterbrechen. In der **Proxmox-Node-Shell** die tatsächlich vergebene CTID eingeben, Konfiguration und Status prüfen und die Qdrant-Shell öffnen:

   ```bash
   read -r -p 'Qdrant-CTID: ' QDRANT_CTID
   pct config "$QDRANT_CTID"
   pct status "$QDRANT_CTID"
   pct enter "$QDRANT_CTID"
   ```

   In `pct config` muss `net0` die Werte `bridge=mpqnet`, `ip=172.28.63.20/24`, `gw=172.28.63.1` und `firewall=1` enthalten. Vor den folgenden Proxmox-Schritten die Qdrant-Shell mit `exit` verlassen oder eine zweite Node-Shell öffnen.

9. Vor der API-Key-Konfiguration die **Qdrant-Container-Firewall** im Proxmox-Webinterface einrichten. Zuerst unter **Datacenter → Firewall → Options** und **Node → Firewall → Options** den aktuellen Status prüfen. Ist die Datacenter-Firewall noch aus, vor ihrer Aktivierung die bestehenden Regeln für Proxmox-Weboberfläche (`8006/TCP`), SSH (`22/TCP`) und weitere benötigte Host-Dienste vom tatsächlichen Managementnetz prüfen und eine zweite Host-Konsole offen halten: Das Einschalten wirkt auf den gesamten Node beziehungsweise Cluster. Danach **Firewall: Yes** auf Datacenter- und Node-Ebene setzen. Unter **Qdrant-LXC → Network → net0 → Edit** die Option **Firewall** aktivieren. Unter **Qdrant-LXC → Firewall → Options** **Firewall: Yes**, **Input Policy: DROP** und **Output Policy: ACCEPT** setzen. Unter **Qdrant-LXC → Firewall → Add** genau eine eingehende Freigabe für Materialpool anlegen: **Direction: IN**, **Action: ACCEPT**, **Interface: net0**, **Source: `172.28.63.10/32`**, **Protocol: tcp**, **Dest. port: `6333`**, **Enable: Yes**. Keine Freigabe für `6334` hinzufügen. Die Container-Firewall benötigt sowohl die allgemeine Aktivierung als auch die Aktivierung an der Netzkarte. [Proxmox-Firewall](https://github.com/proxmox/pve-docs/blob/master/pve-firewall.adoc).

   Von der **Proxmox-Node-Shell** aus den gesperrten Zugriff auf den öffentlichen Health-Endpunkt prüfen. Der folgende Aufruf muss **fehlschlagen**, denn der Node benutzt `172.28.63.1` und die einzige Freigabe erlaubt `172.28.63.10`. Antwortet er mit HTTP 200, vor dem nächsten Schritt die Firewall-Aktivierung und Regelreihenfolge prüfen:

   ```bash
   curl -fsS --connect-timeout 2 --max-time 4 http://172.28.63.20:6333/readyz
   ```
10. Im **Qdrant-LXC** Adresse, Route, Namensauflösung und HTTPS prüfen, damit Updates funktionieren. Es muss `172.28.63.20/24` auf `eth0` sowie genau die vorgesehene Default-Route über `172.28.63.1` sichtbar sein. Ist einer der Aufrufe erfolglos, DNS, Gateway, SNAT und die Output Policy prüfen, bevor Qdrant angebunden wird:

    ```bash
    ip -4 address show dev eth0
    ip -4 route
    getent hosts github.com
    curl -fsSI --connect-timeout 5 --max-time 10 https://github.com
    ```

### Qdrant im eigenen LXC absichern

11. In der **Qdrant-LXC-Root-Shell** Dienst, tatsächliche Konfiguration und Datenpfade prüfen. Die bisher dokumentierten Pfade sind `/etc/qdrant/config.yaml`, `/var/lib/qdrant/storage` und `/var/lib/qdrant/snapshots`; bei Abweichungen gelten die installierte Unit und Konfiguration:

   ```bash
   systemctl status qdrant --no-pager
   systemctl cat qdrant
   ls -l /etc/qdrant/config.yaml
   grep -nE '^(service:|storage:|  (host|http_port|storage_path|snapshots_path):)' /etc/qdrant/config.yaml
   ls -ld /var/lib/qdrant /var/lib/qdrant/storage /var/lib/qdrant/snapshots
   ```

12. Falls noch kein API-Key eingerichtet ist, einen eigenen zufälligen Key als root-only `EnvironmentFile` erzeugen und per systemd-Drop-in laden. Qdrant-Umgebungsvariablen haben laut [Qdrant-Konfiguration](https://qdrant.tech/documentation/operations/configuration/) Vorrang vor YAML. Ist bereits ein Key aktiv, dessen Herkunft und Wirkung prüfen, statt einen zweiten widersprüchlichen Wert zu setzen.

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

13. Im **Qdrant-LXC** Bereitschaft und Authentifizierung getrennt prüfen. `/readyz` ist laut [Qdrant-Monitoring](https://qdrant.tech/documentation/operations/monitoring/) auch mit aktiviertem Key öffentlich. Erst `/aliases` prüft die Authentifizierung. Ohne Key muss der erste `/aliases`-Aufruf `401` oder `403` liefern; mit Key muss er erfolgreich sein:

   ```bash
   curl -fsS --max-time 5 http://127.0.0.1:6333/readyz
   curl -s -o /dev/null -w '%{http_code}\n' --max-time 5 http://127.0.0.1:6333/aliases
   . /etc/qdrant/materialpool.env
   curl -fsS --max-time 5 -H "api-key: $QDRANT__SERVICE__API_KEY" http://127.0.0.1:6333/aliases
   unset QDRANT__SERVICE__API_KEY
   ```

   Bei Fehlern `journalctl -u qdrant -n 100 --no-pager` lokal prüfen und vor Weitergabe von Ausgaben Geheimnisse und Nutzdaten entfernen. Die vorbereitete Firewall-Regel für TCP 6333 bleibt auf die feste Laravel-IP begrenzt.

### Materialpool verbinden und Betrieb prüfen

14. Den **Materialpool-LXC** bei einer Neuinstallation nach der [Materialpool-Installationsanleitung](installation.md) anlegen. In der **Proxmox-Node-Shell** startet dieser Befehl das Materialpool-Script; es lädt und startet ebenfalls Fremdcode und setzt ein veröffentlichtes, versioniertes Materialpool-Release voraus:

    ```bash
    bash -c "$(curl -fsSL https://raw.githubusercontent.com/stevenbuehner/materialpool/master/ct/materialpool.sh)"
    ```

    Im Script-Assistenten die **bestehende Betriebs-Bridge** (zum Beispiel `vmbr0`) mit der vorgesehenen IP, dem bisherigen Gateway und DNS wählen; **nicht** `mpqnet` als einzige Netzkarte verwenden. Der Release-Installer fragt später „Context-Suche aktivieren?“: zunächst **Nein** antworten, weil die private zweite Netzkarte noch fehlt. Bei einem vorhandenen Materialpool-LXC dessen bisherigen Netzanschluss unverändert lassen.

    Nach erfolgreicher Installation im Proxmox-Webinterface **Materialpool-LXC → Network** öffnen und `net0` samt Bridge, IPv4 und Gateway notieren. Dann **Add → Network Device** wählen und die zweite Karte genau so eintragen:

    | Feld | Wert für das Beispiel |
    | --- | --- |
    | Name | `eth1` (Proxmox-Eintrag `net1`; bei belegtem Slot den nächsten freien Namen) |
    | Bridge | `mpqnet` |
    | VLAN Tag | leer; das VNet selbst ist die private Verbindung |
    | IPv4 | Static |
    | IPv4/CIDR | `172.28.63.10/24` |
    | Gateway (IPv4) | **leer** |
    | IPv6 | keine zusätzliche IPv6-Konfiguration für dieses Beispiel |
    | Firewall | aktiviert |

    Mit **Add** speichern. Auf `net1` keinen zweiten Default-Gateway oder abweichenden DNS eintragen; der bisherige Zugang für Reverse Proxy und Updates bleibt auf `net0`. Wenn Proxmox die Änderung als *pending* markiert, den Materialpool-LXC in einem Wartungsfenster neu starten. Auf dem **Proxmox-Host** die tatsächliche Materialpool-CTID eingeben und prüfen, dass `net0` weiterhin die bisherige Bridge und `net1` `bridge=mpqnet`, `ip=172.28.63.10/24` und `firewall=1`, aber **kein `gw=`**, enthält:

    ```bash
    read -r -p 'Materialpool-CTID: ' MATERIALPOOL_CTID
    pct config "$MATERIALPOOL_CTID"
    pct status "$MATERIALPOOL_CTID"
    pct enter "$MATERIALPOOL_CTID"
    ```

    Im **Materialpool-LXC** danach die Adressen und Routen prüfen:

    ```bash
    ip -4 address
    ip -4 route
    ip -4 route get 172.28.63.20
    ```

    `route get` muss `dev eth1` und `src 172.28.63.10` zeigen (bei anderem Gerätenamen entsprechend angepasst). Es muss eine direkte Route zu `172.28.63.0/24` über die neue Karte geben; der bisherige Default-Gateway muss auf `net0` bleiben. Wenn `src` oder Interface abweichen, vor der Qdrant-Konfiguration die Interface-Adresse, Präfixlänge und vorhandene konkurrierende Routen korrigieren. Aus einem **anderen Netz** darf `172.28.63.20:6333` nicht erreichbar sein. Die Materialpool-Netzkarte benötigt für diese ausgehende Verbindung keine eingehende Portfreigabe; bei einer eigenen restriktiven Output Policy TCP 6333 zur Qdrant-IP ausdrücklich erlauben.

15. Zuerst im **Materialpool-LXC** die Verbindung **vor** dem Speichern unabhängig prüfen. `/readyz` zeigt nur, ob der Dienst antwortet; `/aliases` ohne Key muss `401` oder `403` liefern. Den in Schritt 12 erzeugten Key verdeckt eingeben und `/aliases` erneut aufrufen. Der letzte Aufruf muss erfolgreiches JSON liefern; er bestätigt Erreichbarkeit und Authentifizierung, aber noch keine Schreibrechte. Den Key danach aus der Shell-Variablen entfernen:

   ```bash
   QDRANT_URL='http://172.28.63.20:6333'
   curl -fsS --connect-timeout 2 --max-time 10 "$QDRANT_URL/readyz"
   curl -s -o /dev/null -w '%{http_code}\n' --connect-timeout 2 --max-time 10 "$QDRANT_URL/aliases"
   read -r -s -p 'Qdrant-API-Key: ' QDRANT_KEY; printf '\n'
   curl -fsS --connect-timeout 2 --max-time 10 -H "api-key: $QDRANT_KEY" "$QDRANT_URL/aliases"
   unset QDRANT_KEY
   ```

   Anschließend im **Materialpool-LXC** als root den interaktiven Command starten. Bei `QDRANT_URL` exakt `http://172.28.63.20:6333` eingeben; bei `QDRANT_API_KEY` denselben Key verdeckt einfügen. Der Command prüft `/readyz` und authentifiziert `/aliases`. Erst nach Erfolg schreibt er `QDRANT_URL` und `QDRANT_API_KEY` in die Shared-`.env` und leert den Laravel-Konfigurationscache; eine leere Key-Eingabe übernimmt bei erneuter Konfiguration den bisherigen Key.

   ```bash
   php /srv/materialpool/current/artisan context-search:qdrant:configure
   runuser -u www-data -- php /srv/materialpool/current/artisan materialpool:status
   ```

   Die Statusanzeige soll die richtige URL und „API-Key konfiguriert: ja“ melden, ohne den Key auszugeben. Bei **Timeout** zuerst `ip -4 route get 172.28.63.20`, beide `pct config`-Ausgaben, die Qdrant-Container-Firewall und `systemctl status qdrant` prüfen. Bei **401/403** den im Materialpool-LXC eingegebenen Key mit dem aktiven Qdrant-Key vergleichen; keinen Key in ein Ticket oder Log kopieren. Wenn `curl` funktioniert, der Command aber scheitert, die tatsächlich geladene Release-Konfiguration und die Rechte der Shared-`.env` prüfen. Nach einem fehlgeschlagenen Command bleibt die bisherige Verbindung erhalten.

16. Falls Ollama und Kontextsuche eingerichtet werden sollen, im **Laravel-LXC** den vorhandenen Assistenten ausführen:

   ```bash
   php /srv/materialpool/current/artisan context-search:configure
   ```

   Er prüft Ollama-Server, Modell-Digest, Dimensionen und Probevektor. Ohne Server oder bei Abbruch bleibt `CONTEXT_SEARCH_ENABLED=false`. Der Release-Updater startet diesen Dialog nicht. Auch `CONTEXT_SEARCH_ENABLED=true` ist keine Freigabe für produktive Index-Worker oder Indexdispatch; dafür gilt der [Kontextsuche-Queue-Vertrag](../../docs/ai/context-search-queue-change-contract.md).

17. Auf dem **Proxmox-Host** den Qdrant-LXC samt tatsächlichem Datenpfad sichern. Zusätzliche Mountpoints müssen in der Sicherung enthalten sein. `pct config "$QDRANT_CTID"` und den Backup-Job kontrollieren und einen Restore **in einem anderen, isolierten Test-LXC** prüfen. Qdrant-Snapshots nicht dauerhaft auf derselben SSD belassen. Die Qdrant-Sicherung ersetzt nicht die MariaDB- und Originaldatei-Backups von Materialpool. Siehe [Deployment-Testanleitung](testing.md#n-backup-check) und [Qdrant-Snapshots](https://qdrant.tech/documentation/snapshots/).

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
