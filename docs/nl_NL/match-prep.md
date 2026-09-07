---
title: Wedstrijdvoorbereiding
group: match-day
summary: 'Een wedstrijd voorbereiden: selectie, opstelling, taken en het wedstrijdformulier.'
audience: [user]
views: [match-prep]
module: TT\Modules\MatchPrep\MatchPrepModule
order: 10
---

# Wedstrijdvoorbereiding

Het scherm **Wedstrijdvoorbereiding** laat een hoofdcoach een wedstrijd
plannen in één spreadsheet-achtig overzicht: wie er beschikbaar is,
wie er per helft op welke positie staat, wat de wedstrijddoelen per
fase zijn, wat elke speler moet onthouden, en wie de aanvoerdersband
plus elke standaardsituatie neemt.

Open het scherm vanaf de detailpagina van een wedstrijd-type
activiteit; de URL is `?tt_view=match-prep&activity_id=<id>`. De
eerste keer dat je het scherm voor een wedstrijd opent draait eerst
de **Beschikbaarheids-wizard** zodat de selectie de Aanwezig / Afwezig
/ Geblesseerd-chips krijgt die nodig zijn.

## Indeling

Het scherm bestaat uit drie kolommen die de werksheet van de pilot
volgen:

- **Links** — de beschikbare selectie met drie minutentellers per rij
 (`min 1e`, `min 2e`, `tot`) en totalen onderaan. De minuten worden
 automatisch berekend uit `helftduur × (op veld ? 1 : 0)`; pas je
 **Helftduur** boven aan, dan loopt iedere rij live mee.
- **Midden** — twee halve velden naast elkaar met een `→`-kopieerknop
 ertussen. Daaronder het **Principes**-paneel (zie hieronder), en
 daaronder het **Wedstrijddoelen**-paneel — bovenaan één
 volledige *Algemeen*-box, daarna een 2×2 grid van *Aanvallen /
 Verdedigen* en *Spelhervattingen / Spelhervattingen*. Iedere box
 heeft vier korte tekstvelden (bullet-stijl) — geschreven voor de
 korte regels die de pilot gebruikt.
- **Rechts** — twee gestapelde panelen. **Doen per speler** bevat per
 speler een aandachtspunt, een `!`-vlag voor "dit is een specifiek
 doel voor deze speler", en een camera-icoontje voor "ik heb een
 videoanalist toegewezen aan deze speler." **Rollen &
 standaardsituaties** bevat zes rijen — Aanvoerder, Hoekschop links /
 rechts, Vrije trap links / rechts (voorzet), Strafschop.

## Principes — waar deze wedstrijd over gaat

Boven de doelenvakken staat een alleen-lezen paneel **Principes**: de
principes waaraan de activiteit gekoppeld is, in dezelfde O / A / V-kleurpills
als op de activiteitenpagina. Het staat er zodat je de wedstrijddoelen schrijft
tegen het principe waar het team werkelijk aan werkt, en niet uit je hoofd.

**Principes koppel je op de activiteit, niet hier.** Eén plek die antwoord
geeft op "waar gaat deze wedstrijd over" is precies waarom het paneel meeleest
in plaats van opnieuw te laten kiezen — twee kiezers geven vroeg of laat twee
antwoorden. Heeft een wedstrijd nog geen principes, dan zegt het paneel dat en
verwijst het rechtstreeks naar het bewerkformulier van de activiteit (alleen
als je activiteiten mag bewerken). Draait je academie zonder de
Methodiek-module, dan verschijnt het paneel helemaal niet.

De principes gaan mee in de afdruk. Ze staan op het papieren wedstrijdformulier
en in de PDF-export, boven de doelen, zodat een assistent die met het formulier
werkt hetzelfde kader ziet als jij.

## Spelers op posities zetten

Een positie op het veld is een cirkel. Klik op een cirkel — leeg of
gevuld — om de **spelerkiezer** te openen die naast de cirkel
verschijnt. Typ om te filteren; tik op een naam om toe te wijzen.
Een speler die al ergens anders in dezelfde helft staat wordt
automatisch verplaatst (maximaal één plek per helft).

Je kunt ook **een speler vanaf de linker selectie naar een cirkel
slepen**. Slepen is de desktop-verbetering; klikken is het hoofdpad en
werkt overal hetzelfde.

De `→`-knop tussen de twee velden **kopieert de opstelling van de 1e
helft naar de 2e helft** met één klik, zonder bevestiging. Pas
vervolgens de 2e helft aan (meestal één of twee wissels).

## Aanvoerder en standaardsituaties

Het paneel **Rollen & standaardsituaties** rechts werkt hetzelfde als
een positiecirkel. Klik op een rij — Aanvoerder, Hoekschop links,
Hoekschop rechts, Vrije trap links, Vrije trap rechts, Strafschop —
om dezelfde spelerkiezer te openen. De ×-pil op een gevulde rij maakt
de toewijzing leeg. Wordt een speler in de drawer op Afwezig gezet
dan wordt hij automatisch uit zowel rolrijen als opstelling
verwijderd, zodat het rollenpaneel nooit naar een onbeschikbare
speler verwijst.

## Beschikbaarheids-drawer

Klik **Beschikbaarheid** in de werkbalk om de drawer in te schuiven
met drie chips per speler: **Aanwezig**, **Afwezig (excused)**,
**Geblesseerd**. Voeg desgewenst een reden toe bij een afwezigheid.
**Iedereen aanwezig** is de snelkoppeling voor "vandaag is de hele
selectie er." Het sluiten van de drawer slaat op; wie als afwezig
wordt gemarkeerd wordt uit elke positie en rolrij verwijderd.

Heeft de wedstrijdactiviteit een **geplande selectie** (de verwachte
spelers die je bij het aanmaken koos), dan begint de
beschikbaarheidsstap vanuit dat plan in plaats van iedereen op
Aanwezig te zetten: geplande spelers staan standaard op Aanwezig, en
teamspelers die je uit het plan liet staan vooraf op Afwezig met de
reden "niet in geplande selectie." Pas elke chip aan — de
standaardwaarden zijn slechts een voorzet. Activiteiten zonder
geplande selectie zetten nog steeds iedereen standaard op Aanwezig.

## Formatie

In het dropdown **Formatie** staat elke regel uit
`tt_formation_templates`. De standaard is **4-2-3-1** — de meest
gebruikte vorm van de pilot. Een andere formatie kiezen hervormt de
posities op de velden; toewijzingen die op een doorlopende positie
blijven gaan mee, de rest valt terug op de bank.

De beschrijvende naam staat in je eigen taal (de cijfers van de vorm
blijven in elke taal gelijk) — bijv. **Neutraal 4-3-3**, **Balbezit
4-3-3**. Naast de standaard 3-4-3 is er nu ook een **Aanvallend 3-4-3
(ruit)**: een 3-4-3 met een middenveldruit (DM, twee centrale
middenvelders en een aanvallende middenvelder achter een voorhoede van
drie). Die tekent nu ook echt als ruit op het veld — een opstelling
draagt zijn eigen posities, dus twee opstellingen met hetzelfde
vormgetal (de platte 3-4-3 en de ruit) vallen niet langer op dezelfde
plaatsing terug.

## Spelernamen — korte vorm

Elke spelernaam op het scherm — selectielijst, posities op de velden,
Doen per speler, Rollen-paneel, beschikbaarheidsmodal — wordt
weergegeven als de **voornaam** van de speler (`Daan`, `Senna`,
`Javi`). De volledige naam blijft voor het spelersprofiel en de
teampagina.

Hebben twee spelers in hetzelfde team dezelfde voornaam, dan worden
beiden weergegeven als `<voornaam> <achternaaminitiaal>` (`Daan P`,
`Daan A`) zodat de coach ze in één oogopslag uit elkaar kan houden.
Een derde `Daan` in een ander team heeft geen invloed op de labels
van dit team. Dezelfde speler verschijnt overal op het match-prep
scherm met dezelfde korte vorm.

Heeft een speler geen voornaam in het systeem, dan wordt de
achternaam getoond; ontbreken beide dan toont het scherm `—` tot het
recordprobleem is verholpen.

## Opslaan

Dit is een van de schermen die zichzelf opslaan. [Hoe opslaan werkt](save-model.md)
zet de drie modellen uiteen die TalentTrack gebruikt en welke schermen in welk
model vallen; dit hoofdstuk is de uitwerking voor dit scherm.

Iedere wijziging wordt live opgeslagen via REST — er is geen
opslaanknop. De rechterkant van de werkbalk toont de huidige status
("Alle wijzigingen opgeslagen", "Niet-opgeslagen wijzigingen…",
"Opslaan…", "Opslaan mislukt — probeer opnieuw"). Mislukt een save
dan kun je de wijziging opnieuw doen; het netwerk kan kuren hebben.

### De laatste wijziging ongedaan maken

Omdat er geen opslaanknop is, staat een misklik meteen vast zodra hij
is verwerkt. Naast *Alle wijzigingen opgeslagen* verschijnt de knop
**Ongedaan maken**, die de laatst opgeslagen wijziging terugdraait — de
opstellingsplek die je zojuist vulde, de speelhelft-lengte die je net
intypte, het aandachtspunt dat je net schreef.

Drie dingen om te weten:

- **Eén stap, geen historie.** Je draait de laatste opgeslagen
  wijziging terug. Daarna verdwijnt de knop; wil je hem alsnog, voer de
  wijziging dan opnieuw in.
- **Het terugdraaien wordt zelf opgeslagen.** Herlaad de pagina en de
  wijziging blijft teruggedraaid — het is geen schermtruc.
- **De knop verschijnt alleen bij rust.** Zolang de werkbalk *Opslaan…*
  of *Niet-opgeslagen wijzigingen…* toont is hij verborgen, zodat hij
  nooit botst met een save die nog onderweg is.

Aanvoerder en standaardsituatie-nemers worden apart opgeslagen; kies je
er een, dan vervalt het aanbod. Bij je volgende gewone wijziging staat
de knop er weer.

### Terug naar hoe het was toen je het scherm opende

Ongedaan maken is voor de misklik die je meteen ziet. Voor die je pas
tien minuten later ziet, staat ernaast de knop **Wijzigingen
terugdraaien**. Die zet het hele scherm terug in de staat waarin je het
opende — opstelling, speelhelftduur, formatie, wedstrijddoelen en
notities per speler, in één keer.

Hij vraagt het eerst. De bevestiging noemt hoeveel velden hij herstelt
en waarschuwt dat het herstel zelf niet ongedaan te maken is; druk op
Escape of op **Annuleren** om ervan af te zien.

Wat hij wel en niet bereikt:

- **Hij overleeft een herlaadbeurt.** Ververs de pagina, of sluit het
  tabblad per ongeluk en kom terug: de knop staat er nog, met hetzelfde
  startpunt.
- **Hij hoort bij dit apparaat en deze sessie.** Het startpunt staat in
  je browser, niet op de server. Open je dezelfde wedstrijd op een ander
  apparaat — of kom je de volgende ochtend terug — dan krijg je het
  opgeslagen plan zonder aanbod om terug te draaien. De sessie is
  voorbij.
- **Een privévenster, een geleegde browser of een browser die opslag
  blokkeert betekent geen terugdraaien.** De rest van het scherm werkt
  precies als anders, inclusief automatisch opslaan en Ongedaan maken;
  alleen dit ene aanbod ontbreekt.
- **Aanvoerder- en standaardsituatiekeuzes beëindigen het.** Die worden
  via hun eigen endpoint opgeslagen, dus terugdraaien zou ze niet kunnen
  herstellen. In plaats van een deel van het scherm te herstellen en die
  keuzes stilletjes te laten staan, vervalt de knop voor de rest van de
  sessie.
- **Hij verschijnt alleen als er iets terug te draaien is**, en alleen
  op een rustig scherm, om dezelfde reden als Ongedaan maken.

## Afdrukken (of opslaan als PDF)

De knop **Exporteren als PDF (A4)** in de werkbalk maakt een afbeelding
van het wedstrijdvoorbereidingsraster precies zoals het op het scherm
staat — beide opstellingsvelden, de tabel **Selectie · minuten** (minuten
per helft + totalen), de **Wedstrijddoelen**, **Doen per speler** en
**Rollen & standaardsituaties** — en plaatst die op **A4 liggend**. De
positie-nummers, spelernamen, het `!`-icoon (rood) en het camera-icoon
(groen) behouden hun schermkleuren. De PDF wordt direct naar je apparaat
gedownload. Omdat de pagina als afbeelding wordt vastgelegd is het
resultaat pixelgetrouw aan wat je ziet — het nadeel is dat de tekst in
de PDF niet selecteerbaar is.

Het blad **benoemt de wedstrijd**. Een kopbalk bovenaan draagt de
affiche, de datum, het aanvangstijdstip, thuis of uit en de locatie, plus
de opstelling, de speelhelftduur en hoeveel van de selectie beschikbaar
is. Wat jullie academie heeft vastgelegd verschijnt; wat niet is
vastgelegd blijft weg in plaats van leeg op papier te komen. Op het
scherm zie je dit niet — daar staat het al boven het raster — alleen op
het geëxporteerde blad.

De export hangt niet meer af van het apparaat waarop je hem start. Hij
wordt opgemaakt op de paginabreedte zelf, dus dezelfde wedstrijd levert
vanaf een telefoon en vanaf een desktop hetzelfde document op. Het blad
wordt op de pagina gepast en gecentreerd; is het hoger dan één pagina,
dan wordt het op ware grootte over twee pagina's afgedrukt in plaats van
weggekrompen tot iets onleesbaars.

### Het afgedrukte blad is voor papier opgemaakt

De PDF is **één liggende pagina**, ingedeeld voor in de hand in plaats
van voor het scherm:

- **Links** — Selectie · minuten, met Rollen & standaardsituaties
  eronder. Het zijn allebei dezelfde spelers op dezelfde manier gelezen,
  een naam per regel, dus ze delen de smalle kolom.
- **Midden** — de twee halve velden en daaronder de wedstrijddoelen.
- **Rechts** — Doen per speler, met altijd **vijftien regels**: eerst de
  spelers die al een notitie hebben, daarna lijntjes tot dat aantal. Dit
  is het blok waarin je tijdens de warming-up schrijft, dus de ruimte
  staat er of je hem vooraf hebt ingevuld of niet.

Lege doelvakken en ongebruikte spelersregels komen als **schrijflijntjes**
op papier, niet als gaten of als grijze hulptekst. Een volle selectie van
22 spelers past nog steeds op die ene pagina.

De route **Wedstrijdformulier afdrukken** (`?tt_match_prep_print=1`) drukt
dezelfde indeling af, zodat de pagina die je zonder JavaScript afdrukt en
de PDF die je exporteert hetzelfde document zijn.

Twee dingen blijven er bewust af. **Lege velden komen leeg op papier**:
een doelstelling of spelersnotitie die je niet hebt ingevuld wordt een
lege schrijfregel, niet de grijze hint ("Doelstelling 2…", "…"), en een
standaardsituatie zonder speler blijft leeg in plaats van
"— Kies speler —". En **de knoppen printen niet**: het `×` waarmee je
een speler bij een standaardsituatie wist en de `→` waarmee je de
opstelling van de eerste helft naar de tweede kopieert, zijn knoppen op
het scherm en hebben op een geprint blad niets te zoeken.

De afbeeldingsmotor laadt pas wanneer je voor het eerst op Exporteren
klikt (hij wordt niet eerder gedownload), zodat de match-prep-pagina
zelf niet zwaarder wordt. Lukt het vastleggen niet op je apparaat, dan
verschijnt een korte melding; val dan terug op de eigen afdrukdialoog
van de browser (**Ctrl/Cmd+P → Opslaan als PDF**), die de pagina
rechtstreeks afdrukt.

De knop **Wedstrijdformulier afdrukken** ernaast opent het teamblad
voor langs de lijn (basiself / bank / selectie met handtekeningregels)
op een schone afdrukpagina in een nieuw tabblad, met een eigen keuze
**Exporteren als PDF (A4 liggend)** / **Afdrukken**. De centrale
`?tt_view=exports`-pagina heeft nog steeds een server-side match-prep /
teamblad PDF-exporter als terugvaloptie voor wie liever vandaaruit
werkt.

### Twee formulieren, twee schakelaars

Het zijn twee documenten voor twee lezers, en je academie zet ze los van
elkaar aan of uit (**Configuratie → Modules**, onder Match prep):

| Functie | Wat het regelt | Wie het leest |
| - | - | - |
| **Match prep PDF-export** | De knop **Exporteren als PDF** en de afdrukpagina erachter | De trainer — hierop staat het plan: minuten, afspraken per speler, doelen |
| **Wedstrijdformulier** | De knop **Wedstrijdformulier afdrukken**, de afdrukpagina ervan en de server-side export op `?tt_view=exports` | De scheidsrechter en de tegenstander — hierop staan identiteit en speelgerechtigdheid |

Beide staan standaard aan. Zet het wedstrijdformulier uit als jullie
competitie de formulieren digitaal indient: de knop verdwijnt uit de
werkbalk en de afdruk-URL weigert, terwijl de export van de trainer
gewoon blijft. Zet je er één uit, dan blijft het match-prep-scherm zelf
volledig bruikbaar.

## Het plan delen met staf zonder account

Met **Plan delen** maak je een link die je naar een assistent-trainer, een
analist of een keeperstrainer kunt sturen. Die opent hem en leest het plan — de
opstelling, de doelen per fase, de aandachtspunten per speler — zonder in te
loggen en zonder account op deze installatie.

Alleen lezen. Wie de link opent kan niets wijzigen, en de pagina wordt buiten
zoekmachines gehouden.

Drie dingen om te weten voordat je er een verstuurt:

- **De link ís de sleutel.** Iedereen die hem heeft kan het plan lezen. Behandel
  hem zoals je het geprinte wedstrijdformulier behandelt: geef hem aan wie hem
  nodig heeft, en niet aan een groepsapp die je niet in de hand hebt.
- **Er staan spelersnamen in, en wie er naar verwachting begint.** Daarvoor
  stuur je hem, en daarom stuur je hem niet door buiten de staf.
- **Je kunt hem intrekken.** Met **Link vervangen** komt er een nieuwe en stopt
  de oude direct met werken — voor iedereen die hem heeft. Gebruik dat als een
  link bij de verkeerde groep terechtkwam, of als iemand de staf verlaat.

De link blijft meelopen terwijl je bijwerkt: wie hem opent ziet het plan zoals
het nu is, niet zoals het was toen je hem verstuurde. Dat is het verschil met
een PDF mailen, die verouderd is zodra je één basisspeler wisselt.

Delen kan voor de hele academie worden uitgezet onder **Modules en functies**,
los van het delen van wedstrijdanalyses. Het plan schrijven en afdrukken
verandert er niet door.

## Wie het kan openen

Het plan hoort bij het team dat de wedstrijd speelt. Je opent — en
wijzigt — de wedstrijdvoorbereiding van wedstrijden van teams die je
zelf coacht. Open je de URL van een wedstrijd van een team dat je niet
coacht, dan krijg je *Je bent geen trainer van het team van deze
activiteit.*
Academiebeheer en Hoofd Opleiding zien alle teams, zoals overal.

Datzelfde geldt voor de deellink en de rolverdeling: het team van het
plan bepaalt, niet het feit dat je ergens in de academie coach bent.

## Wat hier niet kan

- De selectie aanpassen (spelers toevoegen / verwijderen) — dat doe
 je op de pagina **Teams**.
- De wedstrijd zelf draaien — dat is **Match Execution**, de live
 telefoon-app voor de assistent-coach. De knop **Start match** wordt
 pas actief op de wedstrijddag zelf — daarvoor is hij wel zichtbaar
 maar uitgeschakeld, met een tooltip die de datum noemt waarop hij
 vrijkomt ("Beschikbaar op wedstrijddag (14 jun)"). Zo wordt een
 wedstrijd niet per ongeluk te vroeg gestart.
- De wedstrijd achteraf bespreken — dat is de **wedstrijdanalyse**, die
 je vanaf dezelfde activiteit opent zodra de wedstrijd gespeeld is. Wat
 je hier plant staat daar naast de bijbehorende fase, en de spelers die
 je gemarkeerd hebt staan er met hun aandachtspunt bij, zodat de
 bespreking antwoord geeft op wat je gevraagd hebt. De camera-vlag
 markeert nog steeds alleen wie er als analist is aangewezen; wat die
 analist zag hoort in de analyse.

## Op je abonnement

Wedstrijdvoorbereiding is een **Pro**-functie. Op Standard blijft een plan dat de club al maakte op het scherm staan en afdrukbaar; een nieuw plan starten, of een wijziging opslaan, is wat het abonnement toevoegt. Zie [Licentie en account](license-and-account.md).
