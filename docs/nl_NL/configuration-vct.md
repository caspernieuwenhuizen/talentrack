---
title: Configuratie — VCT
group: configuration
summary: Leeftijdsprofielen, belastingsplafonds en de regels die de VCT-planner toepast.
audience: [admin]
views: [vct-config]
order: 120
---

# VCT-configuratie

Het Configuratie-overzicht heeft één **VCT-configuratie**-tegel voor de
Variabel Coachen-template (VCT) module, achter de rechten-cap
`tt_vct_admin_library`, zodat alleen het Hoofd Opleidingen (en
beheerders) hem ziet. De tegel opent de VCT-configuratieweergave
(`?tt_view=vct-config`); de eigen tabbalk regelt het navigeren tussen de
drie onderdelen.

De tegel heeft een groene **NIEUW**-pil + accentrand
(`.tt-cfg-tile--vct`) en een telregel die samenvat hoeveel er is
ingesteld ("%d bloksjablonen · %d leeftijdsgroepen").

## De drie tabbladen

| Tabblad | Wat het doet |
| --- | --- |
| **Macro-blokken** | De periodiseringskalender van het seizoen — een lijst met blokken met datums (opbouw, in-season, taper, …) per seizoen, eventueel per team overschreven. |
| **Leeftijdsprofielen** | Per leeftijdsgroep (JO8 → JO19) het belastingsplafond, de maximale intensiteit per MD-dag en de maximale trainingsduur. Voedt de belastingscheck in de wizard en de afdwinging door de engine, overal waar een VCT-training wordt samengesteld of opgeslagen. |
| **Teamschema's** | De wekelijkse VCT-trainingsdagen per team voor een seizoen. Bepaalt de standaarddatum van de wizard op de eerstvolgende ingestelde weekdag. |

Vóór #1546 waren er twee tegels (één voor macro-blokken en één voor
leeftijdsprofielen) en had het tabblad Teamschema's helemaal geen tegel.
De ene tegel maakt alle drie bereikbaar vanaf één ingang.

## Macro-blokken en cycli zijn niet hetzelfde

Een **macro-blok** is een gedateerd stuk van het seizoen — opbouw,
wedstrijdseizoen, afbouw. Het heeft een begin en een eind, en het gebeurt
één keer.

Een **cyclus** is het ritme van drie, vier of zes weken waarin een team
traint, en dat herhaalt zich. Je stelt hem per team in, en hij beantwoordt
de vraag: in welke week van de cyclus valt deze training?

Heeft een team een cyclus, dan bepaalt de cyclus de intensiteit van de
week. Heeft het die niet, dan doet het macro-blok dat, precies zoals
voorheen — een academie die nooit een cyclus instelt, plant zoals altijd.

Het verschil dat in de praktijk telt: **een week waarin het team speelt
zet de cyclus stil**, en die week wordt niet verbruikt. Een team in week 3
dat zaterdag speelt, zit de week erna nog steeds in week 3. Macro-blokken
kennen dat niet — hun weken lopen door, wat er verder ook in de agenda
staat.

## De cyclus, week voor week

Het tabblad **Cyclus** toont het seizoen van één team week voor week:
welke week van de cyclus het is, de fase en het thema, de intensiteit
en — de kolom die er het meest toe doet — **waarom** de week is wat hij
is.

Bij een neutrale week staat er ofwel "Er is deze week een wedstrijd",
ofwel wie hem met de hand heeft gezet en wanneer. Zonder die regel leest
een neutrale week als een bug, en het eerste wat iemand bij een bug doet
is de planner niet meer vertrouwen.

In de lijst zie je de pauzeregel werken: cyclusweek 3 komt **na** een
neutrale week in plaats van eraan op te gaan. Dat is het deel van de
regel dat mensen verrast, en dit is het enige scherm waar het zichtbaar
is.

### Een week corrigeren

Elke week biedt drie keuzes, geen vinkje:

- **Automatisch** — bepaal hem uit het wedstrijdprogramma. De standaard.
- **Neutraal forceren** — pauzeer een week waarin geen wedstrijd staat.
- **Toch doorlopen** — laat de week gewoon doorlopen *ondanks* een
  wedstrijd. Dit is degene die in de praktijk voorkomt: een
  oefenwedstrijd die nooit bedoeld was om iets te onderbreken.

"Automatisch" verwijdert de correctie helemaal in plaats van een derde
toestand op te slaan, dus een week die je terugzet op automatisch
gedraagt zich alsof hij nooit is aangeraakt.

Eén knop **Weken opslaan** legt alles vast. Eén week wijzigen verschuift
elke week erna, dus elke schakelaar opslaan op het moment dat je hem
aantikt zou een reeks half doordachte verschuivingen doorvoeren zonder
één punt om ze vanaf terug te draaien. De lijst wordt na het opslaan
opnieuw opgebouwd, zodat het doorwerkende effect meteen zichtbaar is.

Een week corrigeren vraagt de VCT-configuratierechten. Trainers kunnen de
kalender wel zien — je moet het ritme kunnen lezen waarnaar je plant —
maar een week verschuiven verandert de rest van het seizoen voor de hele
selectie.

## De cyclus van een team instellen

Onder de trainingsdagen van elk team op het tabblad **Teamschema's**
staat een cyclusgedeelte. Drie velden:

- **Cycluslengte** — drie, vier of zes weken. Zes is de standaard.
- **Start op** — de week waarin cyclusweek 1 begint. Welke dag je ook
  kiest, hij wordt opgeslagen als de maandag van die week, want de hele
  functie rekent in hele weken; de startdatum van het seizoen staat als
  standaard klaar.
- **Weekvorm** — welk referentiefaseprofiel de cyclus herhaalt. Laat hem
  op *Passend bij de cycluslengte* staan voor het meegeleverde profiel
  van die lengte.

De regel onder de velden noemt elke week van de cyclus, zodat je de vorm
kunt lezen zonder iets anders te openen.

Een vorm moet één regel per week van de cyclus hebben. Een vorm van vier
weken kiezen bij een cyclus van zes weken wordt geweigerd in plaats van
geaccepteerd — een cyclus waarvan de laatste twee weken zonder
zichtbare reden vlak zijn, herleidt niemand tot dit scherm.

**Cyclus verwijderen** laat het team weer plannen vanuit de macro-blokken
van het seizoen, en wist de weken die met de hand op neutraal zijn gezet.
Trainingen die al gepland zijn, houden de cyclusweek die ze kregen.

Een cyclus instellen vraagt de VCT-configuratierechten, die het hoofd
opleiding heeft. Een cyclus bepaalt hoe zwaar een selectie week na week
wordt belast, dus het hoort niet bij het gewone beheer.

## Een seizoen en team kiezen

Seizoen en team zijn nu **keuzelijsten** — geen ID's meer intypen.

- **Seizoen** staat standaard op het actieve seizoen van de academie en
 **laadt vanzelf bij wijziging**: kies een ander seizoen en de weergave
 herlaadt ervoor. Er is geen aparte "Laden"-knop (alleen zonder
 JavaScript verschijnt een terugvalknop). Geldt voor de tabbladen
 Macro-blokken en Teamschema's.
- **Team** (tabblad Macro-blokken) is een keuzelijst met bovenaan de
 optie **Clubstandaard (alle teams)**. De clubstandaard geldt voor elk
 team; kies een specifiek team om er alleen voor hen een uitzondering te
 maken.

## Macro-blokken bewerken

De blokkenset wordt bewerkt met een gestructureerde editor — één rij per
blok, elk met een **naam**, een **startdatum** en een **einddatum**
(eigen datumkiezers). Rijen zijn toe te voegen, te verwijderen en te
herordenen (omhoog / omlaag). De editor markeert overlap, ontbrekende
namen en omgekeerde datums meteen tijdens het typen.

Elk blok kan een optioneel **wekelijks faseprofiel** dragen. Voor het
gewone geval (naam + datums) is niets extra's nodig; voor het geavanceerde
geval accepteert een uitklapbaar onderdeel "Geavanceerd: wekelijks
faseprofiel (JSON)" per blok een reeks `{ week, phase, multiplier }`-objecten.

Opslaan stuurt de hele blokkenset naar
`PUT /vct/macro-blocks?season_id=N&team_id=M`, die alles aan de
serverkant opnieuw valideert: 1–12 blokken, opeenvolgende volgnummers
(1..N), geldige `YYYY-MM-DD`-datums, einde op/na start, geen overlappende
periodes. De gedeelde `VctMacroBlockValidator` is de enige bron van
waarheid, gebruikt door zowel het REST-eindpunt als andere schrijvers,
zodat de WordPress-weergave en een toekomstige SaaS-frontend dezelfde
antwoorden geven.

De alleen-lezen tabel **Referentie-faseprofielen** boven de editor toont
de meegeleverde sjabloonprofielen ter referentie. Er zijn er vier: een
van drie weken, een van vier weken, een van zes weken (de standaard) en
de speelwijzecyclus van vijf weken.

Het profiel van drie weken loopt van introductie via opbouw naar deload.
In drie weken is geen ruimte voor een piekweek, dus het is geen ingekorte
ladder van zes weken maar een andere vorm — en de juiste wanneer het blok
waarin het valt kort is.

## Welke leeftijdscategorieën gemodelleerd zijn, en waarom de jongste niet

Er worden vijf profielen meegeleverd: **JO10, JO11, JO12, JO13 en JO14**. De
trainingsgenerator maakt alleen een concept voor een categorie die er één heeft,
want het profiel levert het leeftijdsveilige intensiteitsplafond — en daar valt
bij kinderen niet naar te gokken.

De jongste categorieën vallen daar **bewust** buiten. Op JO7–JO9 wordt
trainingsbelasting niet in getallen gepland; de trainer bouwt de training zelf.
De generator maakt voor hen nooit een concept, en zegt dat nu als antwoord in
plaats van te melden dat er een profiel ontbreekt — de oude tekst stuurde
trainers op zoek naar een instelling die niet bestaat.

Alles boven het bereik is een ander verhaal: dat heeft simpelweg nog geen
profiel, en dat kun je toevoegen. De grens tussen die twee is geen vaste lijst
categorieën, maar het jongste profiel dat je club daadwerkelijk heeft. Voeg een
JO9-profiel toe en JO9 valt vanaf dat moment binnen het bereik.

## Een leeftijdsprofiel toevoegen en verwijderen

Onderaan het tabblad **Leeftijdsprofielen** biedt **Leeftijdsprofiel toevoegen**
elke categorie aan die er nog geen heeft.

Er wordt niets voorgevuld. Deze getallen bepalen hoe lang en hoe zwaar kinderen
trainen, dus een plausibel ogende suggestie zou slechter zijn dan een leeg veld —
die nodigt uit tot instemmen in plaats van beslissen. De meegeleverde profielen
staan op hetzelfde scherm als voorbeeld, en de maximale trainingsduur en het
intensiteitsplafond zijn verplicht.

Bij het toevoegen wordt ook de **trainingsvorm** gekopieerd van de dichtstbijzijnde
categorie die er al een heeft — JO15 erft het sjabloon van JO14, niet van JO10.
Die vorm is een startpunt; de grenzen die je zojuist hebt ingevuld bepalen wat de
training echt begrenst. Beide moeten bestaan, anders zou alleen een profiel
toevoegen het concept één stap later alsnog blokkeren met een andere melding.

**Verwijderen** wordt geweigerd zolang er nog een team in die categorie zit: die
teams zouden stilletjes geen conceptrainingen meer krijgen, en niemand koppelt
dat weken later aan een klik op dit scherm. Verplaats of archiveer die teams
eerst. Al geplande trainingen worden nooit geraakt — een opgeslagen plan draagt
zijn eigen blokken, en het profiel wordt alleen tijdens het concept gelezen.

Beide acties vragen het recht op VCT-configuratie, dat bij de Hoofd Opleiding
ligt. Dit zijn de plafonds die bepalen hoe zwaar minderjarigen worden belast, dus
ze horen niet bij het algemene beheer.

## Leeftijdsprofielen en teamschema's bewerken

Beide tabbladen gebruiken verzorgde `<details>`-accordeons — één per
leeftijdsgroep / team. Elke samenvatting toont de kerngetallen (minuten +
intensiteitsband bij een leeftijdsprofiel; de trainingsdagen bij een
team). De formulieren erin gebruiken het gedeelde `tt-field`-raster en
stapelen op 360px tot één kolom. Elk formulier slaat afzonderlijk op
(instellingen-subformulieren; alleen Opslaan volgens CLAUDE.md §6 (a)).

## Aanvulling per team: VCT-standaardenpaneel

Het centrale tabblad Teamschema's bewerkt alle teams in één seizoen
tegelijk. Het **VCT-paneel op de teamdetailpagina** onderaan
`?tt_view=teams&id=N` bewerkt één team apart (weekdag-chips, standaard
starttijd + duur). Beide schermen slaan op via dezelfde
`VctTeamSchedulesRepository::upsert()` en worden gelezen door de
basisstap van de nieuwe-VCT-wizard. Zelfde rechten-cap, zelfde
design-tokens.
