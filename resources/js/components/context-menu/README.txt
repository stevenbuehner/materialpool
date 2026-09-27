Folgende Struktur:

        <context-menu ref="menu">
            <context-menu-item @click="searchForTag">Suche nach Tag</context-menu-item>
            <context-menu-item :to="{name='search', params: {...}}">Generiert einen <router-link>, der auch mit Command+Click in einem neuen Fenster geöffnet werden kann</context-menu-item>
            <context-menu-item disabled>Deaktiviert</context-menu-item>
        </context-menu>


1) Click-Handler werden direkt an die context-menu-items gehängt
2) context-menu-items können mit "disabled" deaktiviert werden

3) Das Menü wird geöffnet, indem die this.$refs.openMenu(event) übergeben wird.