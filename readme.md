 # API GALLERY
 
 <details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#o-projekte">O projekte</a>
    </li>
    <li>
      <a href="#spustenie">Spustenie</a>
    </li>
    <li>
     <a href="#endpointy">Endpointy</a>
      <ul>
        <li>
         <a href="#práca-s-galériou">Práca s galériou</a> 
        </li>
      </ul>
     <ul>
        <li>
         <a href="#práca-s-obrázkami">Práca s obrázkami</a> 
        </li>
      </ul>
    </li>
    <li><a href="#súbory">Súbory</a></li>
  </ol>
</details>
 
 ### O projekte

Api aplikácia na správu galérii a fotografií. Na vytvorenie tejto aplikácie som použil programovací jazyk PHP verzia 8.1 spoločne s frameworkom Symfony verzia 5.4. K tomu Imagine balíček pre správu a jednoduchšiu prácu s fotografiami. Symfony som si vybral preto, lebo v zadaní bolo potrebné pracovať so súbormi a priačinkami, a spomínaný framework ma k tomu výbornu dokumentáciu, taktiež som s nim pracoval už v minulosti no aj tak som sa nevyhol niektorým veciam, kde bolo potrebné použiť logiku čistáho PHP. Na vytvorenie requestov a respons som používal aplikáciu Postman.

### Spustenie

Pre spustenie aplikácie je potrebné mať nainštalovaný Docker. 

1.) Najprv si projekt stiahneme:
```
git clone https://github.com/DonBarbaro/image-gallery.git
```
2.) Presunieme sa v termínali do súboru a spustíme:
```
docker compose up -d
```
3.) Pomocou príkazu
```
docker compose exec php bash
```
sa presunieme do vytvoreného containera a nainštalujeme všetky potrebné baličky:
```
composer install
```

Ak sme spravili všetko správne, inštalácia by mala zbehnúť v poriadku a server by sa mal spustit na adrese **127.0.0.1:8000**

### Endpointy

V aplikacii bolo potrebné vytvoriť viaceré endpointy, ktoré boli dane a špecifikované v zadaní.

#### Práca s galériou

- **_POST_** - 127.0.0.1:8000/gallery\
Endpoint na vytvorenie galérie, do body vložime len názov galérie. V pričinku "public/files/" sa vytvorí pričinok s názvom galérie a taktiež sa vytvorí _gallery.json_, ktorý obsahuje informácie o galérii.\
**REQUEST**\
![image](https://user-images.githubusercontent.com/42190301/206431402-932c4cfd-2078-4980-9217-8949bb4445a2.png)\
**RESPONSE**\
![image](https://user-images.githubusercontent.com/42190301/206410845-73d02011-6c89-4d61-983e-cd695253a825.png)

- **_GET_** - 127.0.0.1:8000/gallery\
Endpoint, ktorý nám vypíše všetky existjúce galérie.\
**RESPONSE**\
![image](https://user-images.githubusercontent.com/42190301/206411765-55392c56-e80e-4ba0-b429-0dca0228e4de.png)\

#### Práca s obrázkami

- **_POST_** - 127.0.0.1:8000/gallery/{path}\
Endpoint, pomocou ktorého nahrajeme nami vybraný obrázok. Musí byť vo formáte .jpg. V pričinku "public/files/Animals" sa vytvorý nový súbor s názvom _items.json_, ktorý obsahuje všetky informácie ohľadom nahratých obrázkov.\
**REQUEST**\
![image](https://user-images.githubusercontent.com/42190301/206413416-3d8c29f9-79dd-447f-a743-963edb11fe62.png)\
**RESPONSE**\
![image](https://user-images.githubusercontent.com/42190301/206413614-fd41031f-e81d-43fa-9d85-3248bcbc0354.png)

- **_GET_** - 127.0.0.1:8000/gallery/{path}
Endpoit, ktorý nám vypíše galáriu a všetky obrázky ktoré sa v nej nachádzajú.\
**RESPONSE**\
![image](https://user-images.githubusercontent.com/42190301/206414978-5d95ce40-23f2-4fb9-91b1-389fffdbb31c.png)

- **_DELETE_** - 127.0.0.1.8000/gallery/{path}\
Endpoint, kde {path} je názov gelérie, ktorú chceme vymazať.

**REQUEST**
```
127.0.0.1:8000/gallery/Animals
```
**RESPONSE**
```
"Gallery was deleted"
```

- **_DELETE_** - 127.0.0.1.8000/gallery/{path}/{name}\
Endpoint, kde {path} je názov gelérie, ktorú chceme vymazať a {name} je názov obrázka ktorý chceme vymazať.

**REQUEST**
```
127.0.0.1:8000/gallery/Animals/lama.jpg
```
**RESPONSE**
```
"Photo was deleted"
```

- **_GET_** - 127.0.0.1:8000/images/{w}x{h}/{path}/{name}\
Enpoint, kde {w} je šírka obrázka, {h} je výška obrázka, {path} je názov galérie a {name} je názov obrázka ktorý chceme vygenerovať.\

**REQUEST**
```
127.0.0.1:8000/gallery/Animals/lama.jpg
```
**RESPONSE**\
![image](https://user-images.githubusercontent.com/42190301/206419291-a3cca05b-b59d-4291-b492-4c519cb7377d.png)

##### Vlastná funkcionalita

- **_POST_** - 127.0.0.1:8000/gallery/{path}/{name}\
Endpoint na premiestnovanie fotiek medzi galériami, kde {path} je názov galérie odkiaľ chceme obrázok premiestnit a {name} je názov obrázka ktorý chceme premiestniť.
Do body vložíme len názov galérie kam ma byť obrázok premiestnený.

**REQUEST**\
![image](https://user-images.githubusercontent.com/42190301/208786005-1dcbbe1e-1354-4de5-bb5d-2392788f098f.png)

**RESPONSE**\
```
"Photo from gallery Test was moved to gallery Api%20test"
```

### Súbory
> 
    public/
    ├── bundels
    ├── files/                    
        ├── gallery/          
            ├── Animals/
                ├── gallery.json   #obsahuje informácie o galérii
                ├── items.json     #obsahuje informácie o všetkých obrázkoch, ktoré sa nachádzajú v pričinku
                ├── lama.jpg      
                ├── ...
    

### Oprava po zhodnotení

Na mojom zadaní som sa snažil zapracovť podľa pripomienok, snažil som sa refactorovť kód ako som vedel no som si vedomý že sa to da určite aj lepšie. Zameral som sa na vladáciu súbor, expections, taktiež som vymazal ukladanie JSON súboru ku každej galéríi. Taktiež som sa snažil opraviť nekonzistentné formatovanie kódu.






