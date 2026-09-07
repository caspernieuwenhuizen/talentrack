---
title: Activiteiten
group: performance
summary: Wedstrijden, trainingen en andere activiteiten — typering, aanwezigheid en evaluaties na wedstrijden.
audience: [user]
views: [activities, my-sessions]
module: TT\Modules\Activities\ActivitiesModule
capability: tt_view_activities
order: 30
---

# Activiteiten en aanwezigheid

Een **activiteit** is alles wat in de agenda staat — een training, een wedstrijd of een ander evenement (team-buildingdag, clubvergadering, …). Bij elke activiteit registreer je wie er aanwezig was.

## Het activiteitenoverzicht

De tegel **Activiteiten** opent een lijst die per datum is gegroepeerd. De groepen lopen van boven naar beneden:

- **⚠ Voorbij — nog open** — voorbije activiteiten die nog op Gepland staan. Ze zijn nooit op Voltooid of Geannuleerd gezet, dus de coach is ze uit het oog verloren. Deze groep verschijnt **standaard** helemaal boven aan de lijst — boven de ingeklapte knop voor voorbije activiteiten — in een eigen oranje-geaccentueerd blok, zodat voorbije maar niet-afgeronde activiteiten niet gemist worden.
- **Vandaag** — wat er voor vandaag op de planning staat.
- **Deze week** — de rest van deze kalenderweek (tot en met zondag).
- **Volgende week** — maandag → zondag van de week erna.
- **Later deze maand** — alles na volgende week, tot en met het einde van de huidige maand.
- **Later** — alles na het einde van de maand.

Lege groepen tonen geen kop — als er volgende week niets gepland staat, verschijnt de kop "Volgende week" simpelweg niet.

Elke rij is een kaart: een datumbadge links (maand + dag, blauw voor vandaag en oranje voor "Voorbij — nog open"), de titel van de activiteit in het midden met een kleurgecodeerde type-pill (Training blauw, Wedstrijd rood, Oefen geel, Overig grijs) en een chevron rechts. Activiteiten die vanuit Spond zijn geïmporteerd dragen een klein blauw **Spond**-chipje op de kaart, zodat je in één oogopslag ziet welke uit de integratie komen; handmatig aangemaakte en gegenereerde activiteiten tonen er geen. Tik ergens op de kaart om de detailpagina te openen.

### Voorbije activiteiten

Voorbije activiteiten (Voltooid of Geannuleerd) zijn boven aan de lijst vastgepind als één knop — `N voorbije activiteiten verborgen · Toon ▼`. Tik om uit te klappen; tik nogmaals om in te klappen. De stand wordt bewaard in de URL als `?include_past=1`, zodat een gedeelde link dezelfde weergave toont als degene die hem deelde.

Voorbije **geplande** activiteiten (nog niet afgerond) staan NIET in deze ingeklapte groep — die verschijnen standaard in de groep **Voorbij — nog open** boven aan de lijst (boven de knop voor voorbije activiteiten en boven Vandaag), omdat ze een signaal zijn waarop de coach nog moet handelen.

### Filters

Boven de lijst staat één **filterbalk**. Op een desktopscherm staat alles op één regel; op een telefoon of tablet klapt de balk in tot een knop **Filters** (met een telbadge voor het aantal actieve filters) en een rij samenvattingschips — tik op **Filters** om een onderpaneel te openen met dezelfde knoppen, en kies daarna **Toepassen** of **Wissen**.

De balk heeft vijf knoppen, elk onder een eigen label:

- **Team** — beperk tot één team. Standaard staan alle teams aan waar de coach toegang toe heeft.
- **Type** — beperk tot één activiteittype (Training / Wedstrijd / Oefen / Overig / elk eigen type dat je academie heeft toegevoegd). Elk type heeft zijn eigen icoon — een voetbal voor een wedstrijd, een beker voor een toernooi, een klembord voor een overleg, een pion voor training — getekend in dezelfde lijnstijl als de rest van het product, zodat ze op het scherm en op papier hetzelfde ogen.
- **Periode** — een datumvenster: **Alle · Deze week · Volgende week · Deze maand · Volgende maand · Dit seizoen**. Eén kiezen beperkt de lijst zonder datums te typen — weken lopen van maandag tot zondag, maanden zijn kalendermaanden, en **Dit seizoen** gebruikt je ingestelde huidige seizoen.
- **Status** — een keuze **Actief · Gearchiveerd · Alle**. **Actief** is de standaard — de tijdlijn die je normaal ziet. **Gearchiveerd** vervangt de tijdlijn door een platte lijst van de activiteiten die je hebt gearchiveerd, elk met een knop **Herstellen** en (voor beheerders) een knop **Definitief verwijderen**. **Alle** toont de actieve tijdlijn met de gearchiveerde lijst eronder.
- **Geannuleerd** — een schakelaar **Toon**, standaard uit. Geannuleerde activiteiten worden verborgen zodat het schema overzichtelijk blijft; zet hem aan om ze terug te halen, gedimd en doorgestreept met een Geannuleerd-label in welk datumvak ze ook vallen.

Elke keuze blijft bewaard in de URL (`?team_id=N&activity_type_key=match&period=this_week&archived=archived&show_cancelled=1`), zodat deep-links vanaf het dashboard op dezelfde gefilterde weergave landen, en de knoppen vrij combineren.

## Lijst- of kalenderweergave (v4.x+)

Een knop **Kalenderweergave** in de kop schakelt de activiteitenpagina van de chronologische lijst naar een **weekraster** — hetzelfde alleen-lezen raster dat de Teamplanner gebruikt, met de dagen als kolommen en één rij per team. Klik op **Lijstweergave** om terug te schakelen. Je keuze wordt per gebruiker onthouden, zodat de pagina opent zoals je hem verliet. De kalender toont de teams die je mag zien (dezelfde scope als de lijst); kom je binnen met een `?team_id=N`-filter, dan wordt hij tot dat ene team beperkt. Het is een alleen-lezen overzicht — activiteiten aanmaken en bewerken gebeurt nog steeds vanuit de lijst en het activiteitenformulier, en de volledige bewerkbare planner blijft op zijn eigen **Teamplanner**-pagina.

**De kalender houdt je filters vast (v4.x+).** De gekozen periode (of een handmatig Van/Tot-bereik) en het **Type** activiteit gaan mee, zodat de kalender dezelfde activiteiten over dezelfde datums toont in plaats van terug te vallen op een standaardvenster. Twee details om te weten: het raster tekent hele weken, dus een venster dat midden in de week begint wordt vanaf de eerste dag van die week getoond — nooit minder dan je vroeg; en staat de periode op **Alle**, dan is er geen begrensd bereik om te tekenen en valt de kalender terug op zijn standaard voorwaartse venster. In beide gevallen staan de getoonde datums boven het raster.

## De activiteitdetailpagina

Door op een kaart te tikken open je de detailpagina van de activiteit. De hele detailweergave zit in één **groeperingspaneel** — een begrensde, zacht getinte container met de hero, een compacte kerncijferstrook en de sectiekaarten, zodat ze als één bewust opgezet geheel lezen in plaats van losse kaarten op de pagina (ook als slechts een paar secties van toepassing zijn). De pagina past zich aan tussen een **training** en een **wedstrijddag**:

- **Hero** — een typegekleurd icoontje, de titel en een subregel met `datum · tijd · team · locatie`. Bij een wedstrijddag waarvan beide teams bekend zijn leest de titel `Jouw team vs Tegenstander` en toont de subregel de aftraptijd en of het thuis of uit is. De pillen onder de titel tonen het type (plus het wedstrijdsubtype of het Overig-label) en de status. Bewerken, Opkomst registreren en de overige acties staan in de paginakop erboven.
- **Cijferstrook** — een compacte rij met de kerncijfers onder de hero. Een wedstrijd toont **Aanwezig** (gekomen / selectie) · **Wissels** · **Wedstrijdduur**; een training toont **Aanwezig** · **Duur**. Cijfers zonder waarde worden weggelaten. **Aanwezig** verschijnt pas als de activiteit **voltooid** is, net als de kaart Aanwezigheid eronder: bij een sessie die nog *Gepland* is, zou het cijfer een opkomst noemen die nog niet heeft plaatsgevonden. Het telt alleen geregistreerde aanwezigheid — de planning op de kaart **Verwachte opkomst** is een aparte lijst en wordt er nooit bij opgeteld.
- **Feitenbalk** — vier snelle feiten. Een training toont Datum · Tijd · Type · Status; een wedstrijddag toont Tegenstander · Thuis/Uit · Aftrap · Opstelling. Feiten zonder waarde worden weggelaten.
- **Kaarten** — elk met een kop met titel, alleen kaarten met inhoud verschijnen, zodat de pagina overzichtelijk blijft:
 - **Gekoppelde spelprincipes** — de geoefende principes als kleurgecodeerde O/A/V-pillen, elk met een link naar de methodiekverkenner.
 - **Notities** — de vrije tekstnotities van de activiteit.
 - **Opstelling** (wedstrijddag) — de basiself en de bank, elke speler met rugnummer en de gespeelde positie (met terugval op de voorkeurspositie).
 - **Verwachte opkomst** — de geplande selectie (zie hieronder).
 - **Opkomst** (afgeronde activiteiten) — een verdeelbalk met legenda over Aanwezig / Afwezig / Te laat / Met kennisgeving / Geblesseerd (plus eventuele eigen statussen), met de kop `X / Y aanwezig (Z%)` die linkt naar het opkomstformulier. Een melding waarschuwt wanneer selectiespelers nog geen opkomstregel hebben. Daaronder toont een inklapbare lijst **Toon aanwezigheid** elke geregistreerde speler met zijn statuslabel (gasten getagd), zodat je ziet wie welke status had zonder het bewerkformulier te openen.
 - **Toernooi** — voor toernooi-activiteiten het gekoppelde toernooi met data en aantal wedstrijden.
- **Auditvoettekst** — wie de activiteit heeft aangemaakt en als laatste gewijzigd. Bij een activiteit die vanuit Spond is geïmporteerd staat er ook **Team laatst gesynchroniseerd vanuit Spond: <tijd>** — de meest recente Spond-synchronisatie van het team (het tijdstip is op teamniveau, niet per activiteit, en het label zegt dat ook), zodat je kunt inschatten hoe vers de geïmporteerde gegevens zijn.

Ziet die synchronisatie er verouderd uit — het event is in Spond verplaatst, of de selectie is gewijzigd — dan staat er bij elke vanuit Spond geïmporteerde activiteit een knop **Team synchroniseren vanuit Spond** in de paginakop. Die haalt de teamkalender meteen opnieuw op, zodat je niet hoeft te wachten op de geplande synchronisatie of een academie-beheerder hoeft te vragen. Het is een verversing van het **hele team** (Spond kan geen los event opnieuw ophalen), dus de bevestiging zegt dat er ook bij, en de wijziging die je zocht kan op een andere activiteit in de lijst terechtkomen. De knop verschijnt alleen voor wie de Spond-koppeling van dat team mag beheren — een academie-beheerder voor elk team, een hoofdtrainer voor zijn eigen team — en alleen zolang de activiteit actief is, niet gearchiveerd.

De pagina leest prettig op een telefoon: de kaarten stapelen in één kolom en verbreden naar twee kolommen op een tablet of desktop.

## Een activiteit aanmaken

1. Open de tegel **Activiteiten**.
2. Kies het **type** uit de keuzelijst. Vijf types staan standaard klaar (Training, Wedstrijd, Toernooi, Bespreking, Overig) en je academie kan ze hernoemen of nieuwe toevoegen.
3. Bij een **wedstrijd**: kies optioneel het subtype (Oefen, Beker, Competitie).
4. Bij **Overig**: geef het een korte omschrijving.
5. Kies het team, stel de datum in en voeg eventueel een locatie, een begin-/eindtijd en notities toe. Bij een wedstrijd vult het invoeren van de aftraptijd automatisch de eindtijd 105 minuten later in (90' spelen + 15' rust); je kunt die altijd aanpassen.
6. Bij een **wedstrijd**-type (Wedstrijd, Toernooi of een eigen wedstrijd-/oefenwedstrijdtype) verschijnt een optioneel veld **Aanwezigheidstijd** — de tijd waarop families aanwezig moeten zijn. Dit verschijnt in de week-PDF van de teamplanner als `Aanwezig HH:MM`.
7. Opslaan. Nieuwe activiteiten starten op **Gepland**.

Het bewerkformulier verandert geen status — **status verander je met expliciete knoppen** (zie [Een activiteit voltooien](#een-activiteit-voltooien)). Aanwezigheid leg je normaal vast in de begeleide voltooiingsflow, maar zodra een **training** (niet-wedstrijd) is **voltooid** toont het bewerkformulier óók een **bewerkbare aanwezigheidstabel** — één regel per speler met een statuskeuzelijst en een notitie — zodat je een gemiste of verkeerde aanwezigheid daar direct corrigeert en op **Activiteit bijwerken** klikt. Dit is ook de terugvaloptie wanneer de begeleide wizards zijn uitgeschakeld. Wedstrijdactiviteiten houden hun minuten-bewuste voltooiingsflow, dus hun aanwezigheid blijft daar (het bewerkformulier linkt je ernaartoe).

### Een activiteit voltooien

Je zet de status niet meer met een keuzelijst. Een **geplande** activiteit toont een knop **Activiteit voltooien** — zowel op de **lijstkaart** (zodat de meeste activiteiten in één klik voltooid zijn zonder de detailpagina te openen) als op het **detailscherm**. De knop is type-bewust:

- **Training** → opent de evaluatiewizard op de activiteit: aanwezigheid registreren, optioneel beoordelen, klaar.
- **Wedstrijd zonder live-gevolgde uitvoering** ("papieren wedstrijd") → dezelfde wizard, maar bij aanwezigheid worden ook per-speler **minuten** verzameld.
- **Wedstrijd met een live-match-uitvoering** → gaat naar de **Hervatten / Afronden**-flow van de wedstrijd; de minuten komen uit het afronden, dus de wizard vraagt er niet opnieuw naar.

De activiteit gaat pas op **Voltooid** als de flow is afgerond (de laatste stap van de wizard, of het afronden van de wedstrijd). Onderbreek je de flow, dan blijft ze **Gepland** — opnieuw starten hervat zonder aanwezigheid te dupliceren.

Het detailscherm toont ook **Activiteit annuleren** (bij een geplande activiteit) en **Heropenen** (bij een voltooide of geannuleerde) — directe, bevestigde statuswijzigingen.

**De knoppen in de kop volgen de status.** Alleen een geplande activiteit kun je nog wijzigen, dus alleen daar staan **Bewerken** en de uitvoerknoppen. Zodra een activiteit voltooid of geannuleerd is, schakelt de kop over op teruglezen wat er gebeurd is:

- **Bewerken** verdwijnt. **Heropenen** is de weg terug naar een bewerkbaar record — de activiteit gaat terug naar *Gepland* en Bewerken komt daarmee terug.
- De wedstrijdvoorbereiding heet dan **Wedstrijdvoorbereiding bekijken** en opent de opstelling die je had voorbereid. Bij een geplande wedstrijd zegt het label al of er een voorbereiding is: **Wedstrijdvoorbereiding plannen** als die er nog niet is, **Wedstrijdvoorbereiding** zodra die er wel is.
- De wedstrijddagknop biedt op een afgeronde activiteit geen **Wedstrijd starten** of **Wedstrijd hervatten** meer. **Wedstrijd bekijken** blijft als enige over, en alleen als de wedstrijd ook echt live gevolgd is.
- Een training leest **Deze training bekijken** en opent het plan dat is uitgevoerd. Bij een afgeronde training zonder gekoppeld plan staat er helemaal geen uitvoerknop — er valt niets te bekijken, en anders zou de knop je uitnodigen er alsnog een te starten.
- De beoordelingsknop vertelt wat er nog te doen is: **Spelers beoordelen** als er nog niemand beoordeeld is, **Doorgaan met beoordelen** als een deel al klaar is, en hij verdwijnt zodra elke aanwezige speler een beoordeling heeft. Een activiteit afronden beoordeelt op zichzelf niemand — de beoordeelstap is over te slaan en een wedstrijd afronden schrijft alleen speelminuten — dus de knop blijft na afronden beschikbaar tot het werk echt gedaan is. De knop **Beoordelingsraster** ernaast blijft er in alle gevallen, om bestaande beoordelingen na te lezen of te corrigeren.

Een **gearchiveerde** activiteit is nog strenger: alles wat wijzigt is verborgen en alleen **Herstellen** blijft over.

### Vier knoppen, en een ⋯ voor de rest

De koptekst zette vroeger elke beschikbare actie op één rij — elf stuks bij een geplande wedstrijd, allemaal even groot, zodat de twee die ertoe deden niet te onderscheiden waren van de zes die ze ondersteunen. Nu staat er waarvoor je gekomen bent, en de rest zit achter een **⋯**-knop op dezelfde rij:

| Status | Op de rij | Achter ⋯ |
| --- | --- | --- |
| Gepland | Bewerken · Wedstrijdvoorbereiding (of Training uitvoeren) · Activiteit voltooien · Activiteit annuleren | Wedstrijd starten, de drie rasters, Synchroniseren vanuit Spond, Archiveren |
| Voltooid | Wedstrijdanalyse · Archiveren | Heropenen, Wedstrijdvoorbereiding bekijken, beoordelen, de drie rasters, Synchroniseren vanuit Spond |
| Geannuleerd | Archiveren | Heropenen |

Er wordt niets nieuws bereikbaar door het weg te vouwen: elke actie houdt de rechten-, feature- en archiefregels die ze al had, en wat je eerder niet zag, zie je nog steeds niet. Zijn alle weggevouwen acties voor jou verborgen, dan verschijnt de ⋯-knop helemaal niet.

Het menu opent met een klik, met Enter en met de spatiebalk, sluit met Escape of een klik ernaast, en zet je bij het sluiten terug op de ⋯-knop. Op een telefoon beslaat het de breedte van de koptekst in plaats van rechts uit te steken.

**De drie rasters en Synchroniseren vanuit Spond zijn iconen.** *Aanwezigheidsraster*, *Minuten + statistieken* en *Beoordelingsraster* zijn snelkoppelingen naar hetzelfde bulkscherm en geen drie aparte beslissingen, en een Spond-agenda opnieuw ophalen is onderhoud. In de koptekst zouden het vier tekstknoppen zijn die concurreren met Voltooien en Annuleren; in het menu krijgen ze hun woorden terug. Alle vier noemen zichzelf nog steeds voor een schermlezer en bij hoveren.

**De wedstrijdanalyse wacht op voltooien.** Die verscheen voorheen zodra de datum was aangebroken, waardoor een wedstrijd die om zeven uur 's avonds begon *wedstrijdanalyse schrijven* aanbood naast *activiteit voltooien*. Voltooi de activiteit en de knop verschijnt.

**Staan de begeleide wizards uit**, dan heet de knop **Aanwezigheid registreren** en opent hij het [aanwezigheidsraster](attendance-grid.md) op de kolom van die activiteit — op dezelfde plek op de lijstkaart en het detailscherm, en ook de hero **Aanwezigheid registreren** op je dashboard gaat daarheen. Een wedstrijd die je wél live volgt, gaat nog steeds naar Hervatten / Afronden. Omdat één keer opslaan in het raster weken aan sessies kan omvatten, rondt aanwezigheid registreren daar bewust niets af: een geplande activiteit krijgt daarvoor de knop **Markeren als afgerond**, naast Annuleren. Vul eerst de aanwezigheid in en markeer de activiteit daarna als afgerond — met **Heropenen** draai je dat terug.

### Speelminuten (papieren wedstrijden)

Wanneer je een **wedstrijd**-type activiteit voltooit die nooit live is gevolgd, krijgt de aanwezigheidsstap van de wizard kolommen **Basisspeler** en **Minuten** zodat je kunt vastleggen hoe lang iedere speler echt heeft gespeeld. De minuten voeden het minutenrapport en het belastingsbeeld van de speler. Voor een wedstrijd die je *wel* live volgde, komen de minuten uit het afronden van de wedstrijduitvoering.

### Toernooiminuten

**Ook toernooien leggen minuten vast** — het is een minuut-dragend type net als een wedstrijd. Hoe je ze vastlegt hangt af van het toernooi:

- **Toernooi met één wedstrijd** — plan een **wedstrijdvoorbereiding** (opstelling) en draai het **live-wedstrijd**scherm precies zoals bij een wedstrijd; de minuten komen uit het afronden. De knoppen **Wedstrijdvoorbereiding plannen** en de wedstrijddag-CTA's verschijnen op de detailweergave van een toernooi net als bij een wedstrijd.
- **Toernooidag met meerdere wedstrijden** — leg de minuten met de hand vast via de **Minuten**-invoer op het aanwezigheidsscherm (dezelfde kolommen Basisspeler/Minuten als bij een papieren wedstrijd), waarbij je registreert hoeveel minuten elke speler die dag heeft gespeeld.

Beide paden schrijven de vastgelegde minuten naar de aanwezigheidsregel, zodat de minutenrapporten ze oppikken. Voor een dag met meerdere wedstrijden zijn de uit de opstelling afgeleide **basisplaatsen** bij benadering — één opstelling dekt meerdere wedstrijden — dus zijn de vastgelegde *minuten* de betekenisvolle waarde, niet het aantal basisplaatsen. Een toernooi zonder vastgelegde minuten toont nog steeds 0, precies als een wedstrijd.

In het overzicht zie je het type als een gekleurde pill, zodat trainingen, wedstrijden, toernooien, besprekingen en overige activiteiten in één oogopslag te onderscheiden zijn.

## Verwachte opkomst

Bij het aanmaken van een activiteit kies je welke spelers worden verwacht — de selectiestap staat standaard op het hele team, en je vinkt iedereen uit van wie je al weet dat die er niet is. Die keuze is de **geplande selectie** van de activiteit.

Open de detailpagina van een activiteit en je ziet een paneel **Verwachte opkomst** met die spelers (gasten worden gemarkeerd) en het aantal in de kop, zodat je vóór de sessie weet wie je kunt verwachten. Zijn er spelers afgemeld, dan toont het paneel een samenvatting zoals *"2 komen niet · 1 misschien"* en wordt elke betrokken speler gemarkeerd. Het paneel verschijnt niet als je bij het aanmaken voor "Aanwezigheid later instellen" koos. Wie daadwerkelijk kwam, markeer je in de begeleide voltooiingsflow (**Activiteit voltooien** / **Beoordelen voortzetten**) — de geplande selectie is wat je verwachtte, de gemarkeerde aanwezigheid is wat er gebeurde. Het detailscherm houdt een alleen-lezen aanwezigheidssamenvatting bij op voltooide activiteiten.

### De planning aanpassen

De planning ligt niet vast bij het aanmaken. Bewerk je een activiteit die **nog niet is voltooid**, dan vind je een sectie **Geplande opkomst**: één rij per verwachte speler met een status die je kunt instellen — **Verwacht**, **Komt niet** of **Misschien** — plus een vrij **notitie**veld (bijv. "geappt, geblesseerd"). Opslaan en de planning is bijgewerkt. De link **Planning bewerken** in het detailpaneel brengt je er direct naartoe.

Is de activiteit aangemaakt met "Aanwezigheid later instellen" (dus zonder geplande selectie), dan vult de sectie zich met de huidige teamselectie waarbij iedereen op **Verwacht** staat, zodat je vanaf nul kunt beginnen met plannen.

Een speler vroeg op **Komt niet** zetten voedt de latere aanwezigheidsstandaarden: bij het voltooien van de activiteit weet de wedstrijdvoorbereidingsstap al wie je niet verwachtte, zodat je vroege notitie niet verloren gaat. Het aanpassen van de planning raakt nooit de geregistreerde (voltooide) aanwezigheid — die twee blijven gescheiden, dus de aanwezigheidsrapporten worden niet beïnvloed.

Maak je een activiteit aan die **al op Voltooid staat** (ze vond in het verleden plaats) en vul je geen aanwezigheid in, dan wordt de volledige actieve selectie automatisch als **aanwezig** geregistreerd, zodat de activiteit meteen te beoordelen is — pas eventuele afwezigheden daarna aan in de begeleide voltooiingsflow.

## Waarom het type ertoe doet

Elk activiteittype kan gekoppeld worden aan een workflow-sjabloon dat afgaat zodra je een activiteit van dat type opslaat. Standaard:

- **Wedstrijd** maakt een evaluatietaak per speler in de inbox van de coach.
- **Training** en **Overig** maken geen automatische taak.

Je beheerder kan via **Configuratie → Activiteittypes** wijzigen welk sjabloon bij welk type hoort, of een nieuw type toevoegen en daar een sjabloon aan koppelen. De standaardtypes kunnen niet verwijderd worden omdat de evaluatietaak afhankelijk is van het bestaan van **Wedstrijd**.

## In welke week van de cyclus een training valt

Heeft het team een [VCT-cyclus](configuration-vct.md), dan legt elke
training vast als welke week van die cyclus hij is gepland, en de
detailpagina toont dat: **Week 3**, of **Neutraal — wedstrijd deze week**.

Bij een neutrale week staat er altijd waarom. Alleen "Neutraal" leest als
een bug, en wie denkt dat de planner rammelt, blijft hem niet gebruiken.

De week wordt vastgelegd bij het opslaan van de training en is een
**vastlegging van wat de training was**, geen live berekening. Voeg in
oktober een wedstrijd toe en elke training erna schuift een week terug —
maar de trainingen *ervoor* houden de week waarvoor ze gepland waren. Dat
is bewust: de trainingsgeschiedenis van een speler van drie maanden
geleden hoort nog steeds te zeggen wat hij toen zei, in plaats van
zichzelf stilletjes te herschrijven bij elke wijziging in het seizoen.

### Het voor één training aanpassen

Het veld **Cyclusweek** op het bewerkformulier kent drie antwoorden:

- **De cyclus volgen** — de standaard. De week wordt voor je bepaald en
  bijgehouden.
- **Neutraal** — deze ene training draait op neutrale intensiteit.
- **Een specifieke week** — zet hem vast op een week van de cyclus die
  je zelf kiest.

Kies je iets anders dan "De cyclus volgen", dan ligt de week van die
training definitief vast: hij schuift niet meer mee als wedstrijden
verplaatsen. Zet je hem terug op "De cyclus volgen", dan gaat hij meteen
terug naar de cyclus en wordt hij ter plekke opnieuw berekend.

Dit verandert **één training**. Wil je de week van het hele team
verschuiven — en alles wat erna komt — gebruik dan de cycluskalender
onder VCT-configuratie. De twee zijn makkelijk te verwarren, en maar één
ervan is wat je wilt als het ritme van een hele selectie niet klopt.

## Status en bron

Naast het type heeft elke activiteit twee extra velden:

- **Status** — waar de activiteit zich in de levenscyclus bevindt. **Gepland** is de standaard bij nieuwe activiteiten. Hij gaat op **Voltooid** wanneer je de begeleide voltooiingsflow afrondt (zie [Een activiteit voltooien](#een-activiteit-voltooien)), en op **Geannuleerd** via de knop **Activiteit annuleren** op het detailscherm; **Heropenen** zet hem terug op Gepland. De lijst statuswaarden is uitbreidbaar via **Configuratie → Lookups** (lookup-type `activity_status`).
- **Bron** — wie of wat de activiteit heeft aangemaakt. **Handmatig** voor activiteiten die in de app zijn gemaakt, **Gegenereerd** voor activiteiten van de demo-data-generator, en **Spond** voor activiteiten die uit een Spond-agenda zijn gesynchroniseerd (zodra de integratie aanstaat). De bron wordt automatisch gezet, niet handmatig op het formulier. Net als status is de lijst bronnen uitbreidbaar.

De 90-daagse rollup die het Hoofd Opleidingen gebruikt toont één regel per actief type — hernoem of voeg types toe en de rollup volgt automatisch.

## Wie het heeft aangemaakt en gewijzigd

Onderaan het detailpaneel van een activiteit staat een kleine regel: **Aangemaakt door** wie de activiteit heeft toegevoegd en op welke datum, en **Laatst gewijzigd door** wie het laatst heeft bewerkt. Dit wordt vanaf nu automatisch vastgelegd — activiteiten die vóór deze toevoeging zijn aangemaakt tonen hier niets (er is geen historie om in te vullen), en de regel verschijnt pas zodra er een auteur bekend is.

## Gasten

Je kunt spelers van buiten de selectie toevoegen aan een activiteit — bijvoorbeeld een speler die je leent van een ander team voor een oefenwedstrijd, of een proefspeler.

Er zijn twee soorten gasten:

- **Gekoppelde gast** — een bestaande speler van een ander team. Zoek op naam en kies hem of haar. Een evaluatie die je schrijft komt op het profiel van die speler.
- **Anonieme gast** — alleen een naam, nog geen record. Handig voor een eenmalige proefspeler. Je kunt hem later via **Promoveren naar speler** omzetten naar een echte speler.

Open de activiteit, scroll naar het kopje **Gasten**, klik **+ Gast toevoegen**, vul de velden in en klik **Toevoegen**.

Gasten tellen niet mee in de teamstatistieken — aanwezigheidspercentages en het podium gebruiken alleen de selectie.

Een gastoptreden telt **wel** mee voor de belasting van de gastspeler zelf. Op het profiel van een speler — de aanwezigheids-KPI en de belastingsinvoer van de statusmotor — wordt een sessie die hij als gast bij een ander team speelde, meegeteld naast zijn eigen teamsessies, zodat de belasting van een veel meespelende speler klopt. De scheiding is bewust: *spelersbelasting = alles wat de speler waar dan ook deed; teamstatistieken = alleen de eigen selectie.*

## Opruimen

Je kunt een activiteit **archiveren** om oude seizoenen op te ruimen zonder de historie kwijt te raken. De knop **Archiveren** op de detailpagina van de activiteit verbergt de activiteit uit de actieve tijdlijn, maar bewaart de rij — en de opkomst — volledig.

Archiveren is een zachte verwijdering, dus de knoppen **Archiveren** en **Herstellen** vereisen het recht om activiteiten *aan te maken/te verwijderen* — dezelfde rechten als het aanmaken van een activiteit. Een coach die activiteiten alleen mag bewerken (bijvoorbeeld een assistent-coach) kan een activiteit nog wel wijzigen, maar ziet Archiveren en Herstellen niet.

Gearchiveerde activiteiten staan onder het statustabblad **Gearchiveerd** in het activiteitenoverzicht (zie [Filters](#filters)). Daar kun je:

- Een activiteit **herstellen** — ze keert precies zoals ze was terug naar de actieve tijdlijn. De detailpagina van een gearchiveerde activiteit toont nu ook een knop **Herstellen** in de koptekst (in plaats van Archiveren), zodat je haar met één klik terughaalt zonder in de lijst te zoeken. Een gearchiveerde activiteit is alleen-lezen tot ze is hersteld — de knoppen Bewerken en de wedstrijdacties blijven verborgen tot ze weer actief is.
- **Definitief verwijderen** — een actie alleen voor beheerders (vereist de rechten *instellingen bewerken*) die de activiteit voorgoed verwijdert. Dit kan niet ongedaan worden gemaakt. Als de activiteit nog gekoppelde gegevens heeft (opkomst, oefeningen, wedstrijdgegevens), wordt het verwijderen geblokkeerd en blijft de activiteit gearchiveerd — herstel haar of verwijder die gegevens eerst. Archiveren is de veilige standaard; definitief verwijderen is de zeldzame uitzondering.

## Geoefende principes

Elke activiteit kan worden gekoppeld aan één of meer methodologie-principes, zodat rapporten kunnen aangeven "hoe vaak hebben we deze periode aan principe X gewerkt?" De multiselect Geoefende principes verschijnt op zowel de publieke Activiteit-pagina als het wp-admin-formulier. De koppeling is optioneel.

## Gastenpaneel in admin

De wp-admin Activiteit-pagina toont nu een alleen-lezen lijst met gast-aanwezigen die zijn vastgelegd. Voeg gasten toe of verwijder ze via de publieke Activiteit-pagina; het admin-paneel blijft synchroon.
