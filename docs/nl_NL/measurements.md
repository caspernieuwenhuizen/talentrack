---
title: Metingen & testen
group: performance
summary: Vastgelegde testwaarden per speler door de tijd heen, en de trends die daaruit volgen.
audience: [user, admin]
views: [measurements, measurements-entry, measurements-coverage, measurement-tests, test-results, test-trends, player-bmi]
module: TT\Modules\Measurements\MeasurementsModule
order: 80
---

# Metingen & Testen

Een **meting** is één geregistreerde waarde van een test voor één speler op
een datum — een sprinttijd, een lengte, een sprong, een beep-testniveau.
Metingen geven de fysieke en atletische ontwikkeling van een speler een
chronologisch, vergelijkbaar verloop, naast de beoordelingen en doelen.

Deze pagina beschrijft het fundament: het datamodel en wie wat ziet. De
instelwizard, de invoerschermen en het trendoverzicht per speler bouwen
hierop voort.

## De onderdelen

- **Test (definitie)** — iets wat je meet (bijv. "Sprint 30m", "Lengte").
 Elke test hoort bij een **categorie** en heeft een **waardetype**, een
 **eenheid**, een **frequentie** en een **richting** (is hoger of lager
 beter?).
- **Categorie** — de groep waar een test onder valt. Standaard gevuld met
 *Antropometrie*, *Fysiek*, *Techniek* en *Mentaal*; een beheerder kan de
 lijst aanpassen.
- **Eenheid** — de meeteenheid, en wat voor grootheid dat is. Standaard gevuld
 met echte eenheden (s, min, ms, m, cm, mm, km, kg, g, herhalingen, bpm, %,
 niveau); een test kiest er één **of** geeft een eigen, aangepaste eenheid op.
- **Frequentie** — hoe vaak de test moet plaatsvinden: jaarlijks, twee keer
 per jaar, per kwartaal, maandelijks of ad hoc. Dit voedt "wie is aan de
 beurt".
- **Sessie** — een gepland testmoment voor één team: één test, één datum. Staf
 voert per speler één waarde in.
- **Streefwaarde** — een band per leeftijdsgroep (groen / oranje) voor een
 test. Een geregistreerde waarde krijgt groen, oranje of rood ten opzichte
 van de band voor de leeftijdsgroep van de speler, rekening houdend met de
 richting van de test. De band is wat een speler moet *halen*, dus beter dan
 de band is ook groen: bij een test waarbij lager beter is, is een tijd
 sneller dan de groene band groen, en bij hoger-is-beter is een waarde erboven
 groen. Je vult nooit een rode drempel in — rood is simpelweg alles voorbij
 oranje aan de slechtere kant. Bij een **neutrale** test tellen beide randen
 wel, want daar moet de waarde binnen een bereik landen in plaats van er zo
 ver mogelijk voorbij te komen.
- **Statusniveaus** — alleen voor het waardetype **status**: een door de
 beheerder ingestelde, geordende reeks gekleurde niveaus (bijv. *Risico*
 rood, *Aandacht* oranje, *Op koers* groen). Een statustest registreert per
 speler een niveau in plaats van een getal; het laatste niveau van de speler
 verschijnt als een gekleurde chip op het profiel.

## Eenheden dragen een grootheid

Een eenheid is niet zomaar een label achter een getal. Elke eenheid in de lijst
hoort bij een **grootheid** — tijd, lengte, massa, aantal, tempo, percentage of
niveau — en weet hoe zij zich verhoudt tot de basiseenheid daarvan: seconden,
meters, kilogrammen.

Dat heeft drie gevolgen die de moeite waard zijn bij het opzetten van een test.

- **Waarden worden opgeslagen in de basiseenheid en getoond in de jouwe.** Een
 lengte van `182 cm` en een van `1,82 m` zijn hetzelfde opgeslagen getal. Elke
 test toont zijn eigen eenheid, zodat de schermen lezen zoals er gemeten wordt.
- **Een resultaat onthoudt waarin het is ingevoerd.** Verander je later de
 eenheid van een test, dan behouden de al vastgelegde metingen hun betekenis:
 ze zijn opgeslagen tegen een grootheid, niet tegen het label dat de test die
 dag had.
- **Een eigen eenheid heeft geen grootheid.** Typ je een eigen eenheid (zeg
 `watt/kg`), dan wordt de waarde precies zo opgeslagen als ingevoerd, nooit
 omgerekend en nooit vergeleken over eenheden heen. Dat is de prijs van alles
 kunnen meten wat je wilt.

### Tijden als mm:ss

Een test in **minuten of uren staat standaard op mm:ss** — zo'n eenheid kiezen
zegt immers al dat het om tijden gaat. Een resultaat typ je als `5:30` en leest
terug als `5:30`, de streefband schrijf je op dezelfde manier, en het wordt
opgeslagen in seconden zodat trends, gemiddelden en streefwaarden op de echte
grootheid werken.

Tests in **seconden of milliseconden blijven decimaal**: een 10m-sprint is
`2,05 s` en niet `0:02,05`.

**Invoeren en tonen als mm:ss** staat direct onder de eenheidkeuze en overschrijft
de standaard in beide richtingen. Vink het uit bij een test in minuten om
decimale minuten in te voeren, of aan bij een andere tijdseenheid om mm:ss te
gebruiken.

Zonder mm:ss gedraagt een tijd zich als elk ander getal: `5,5` bij een test in
minuten is vijfeneenhalve minuut. `5:30` invullen in een veld dat niet op mm:ss
staat wordt geweigerd in plaats van geraden.

Eén meting is gemaximeerd op vier uur. Dat is ruim genoeg voor een wedstrijd,
een Cooper-test of een circuit, en het is wat een typefout als `1130` bij een
decimale minutentest tegenhoudt — die anders als bijna negentien uur was
opgeslagen.

## Statustests (een handmatige spelersstatus)

Een **status**test is een eenvoudige, handmatig bijgehouden, gedateerde
spelersstatus — een tussenoplossing totdat het berekende statussignaal rijk
genoeg is. Hij gebruikt het metingenraamwerk en krijgt zo automatisch
gedateerde historie en zichtbaarheid op het profiel.

- Kies **Een status (gekleurde niveaus)** als waardetype bij het aanmaken van
 de test. De wizard brengt je daarna naar het bewerkscherm van de test.
- Stel op het bewerkscherm de **statusniveaus** in van laag naar hoog: elk
 niveau heeft een label en een kleur uit een vast palet (groen, limoen,
 geel, amber, oranje, rood, cyaan, blauw, grijs). Maak het label van een niveau
 leeg om het te verwijderen; de rijvolgorde is de bewaarde volgorde.
- Registreer een status net als elke andere test — *Metingen vastleggen* toont
 per speler een gekleurde **statuskiezer** in plaats van een getalveld:
 een keuzelijst waarvan zowel het gesloten veld als elke optie het
 kleurvierkant van het niveau naast het label toont, breed genoeg zodat het
 langste label nooit wordt afgekapt. De kiezer is volledig met toetsenbord en
 touch te bedienen (openen met Enter/Spatie of de pijltjestoetsen, bewegen
 met ↑/↓, type-vooruit, Escape om te sluiten); met JavaScript uit valt hij
 terug op een gewone keuzelijst.
- Op het spelersprofiel verschijnt het laatste niveau als een gekleurde chip
 in het tabblad **Metingen**, in de kleur van dat niveau. Statustests hebben
 geen groene/oranje streefband — hun kleur komt volledig uit het gekozen
 niveau.

Elke statuswijziging is een gedateerde vermelding op het spelersrecord, zodat
de statushistorie van de speler in de tijd opvraagbaar en zichtbaar is. Een
voorgedefinieerde categorie **Spelersstatus** is beschikbaar om deze tests te
groeperen.

## Wie ziet wat

Zichtbaarheid volgt de autorisatiematrix — geen enkele rol is hardgecodeerd:

| Persona | Ziet |
| --- | --- |
| **Speler** | Alleen de eigen metingen en trend. |
| **Ouder** | Alleen de metingen van het eigen kind. |
| **Assistent-/hoofdtrainer, teammanager** | De resultaten en sessies van het eigen team. |
| **Hoofd opleiding, academiebeheerder** | De resultaten van elk team, academiebreed. |

Trainers voeren resultaten in en bewerken die voor hun eigen team. De
testcatalogus (definities en streefwaarden) wordt opgezet door het hoofd
opleiding of een academiebeheerder. Een academiebeheerder of hoofd opleiding
kan elke waarde wijzigen.

## Frequentiewaarden

| Waarde | Betekenis |
| --- | --- |
| `annual` | Eén keer per seizoen |
| `biannual` | Twee keer per seizoen |
| `quarterly` | Vier keer per seizoen |
| `monthly` | Maandelijks |
| `adhoc` | Geen vaste frequentie |

## De metingen van een speler bekijken

Spelers en ouders krijgen een tegel **Mijn metingen** die de
*Metingen*-weergave opent: elke test gegroepeerd per categorie, met de
laatste waarde, een groen/oranje/rood vlaggetje ten opzichte van de
streefwaarde voor de leeftijdsgroep, een kleine trendlijn en de
frequentie. Een ouder ziet de weergave van het kind.

Staf ziet hetzelfde **in context** op het spelersprofiel: open een speler
en ga naar het tabblad **Metingen** (naast Beoordelingen). De badge op
het tabblad toont voor hoeveel tests de speler resultaten heeft.

### Het volledige verloop achter een test

De kleine trendlijn beantwoordt in één oogopslag "welke kant gaat dit
op?". Voor de rest heeft elke test met meer dan één meting een link
**Toon verloop** die eronder de leesbare versie opent. Wat je daar ziet,
hangt af van het soort test — een verloop betekent alleen iets in de
termen van de test zelf:

| Soort test | Wat het verloop toont |
| --- | --- |
| Een getal waarbij **hoger of lager beter is** (sprinttijd, sprongkracht) | Een grafiek met datums, de waarde-as, elke meting gelabeld en de **streefzone van de leeftijdsgroep gearceerd**, zodat je ziet wanneer de speler die binnenkwam. |
| Een getal **zonder goed of fout** (lengte, gewicht, schoenmaat) | De **metingen per datum, in kolommen** — geen grafiek, geen streefzone, geen oordeel. Zie hieronder. |
| Een **statustest** (niveaus als *Op koers* / *Aandacht*) | Eén blok per meetmoment in de kleur van dat niveau. Geen lijn: niveaus zijn benoemde standen, geen afstanden, dus een lijn ertussen zou een precisie suggereren die de data niet heeft. |
| **Gehaald / niet gehaald** | Een vinkje of kruisje per datum plus de telling (*3 van 4*). |
| Elke test met **één meting** | Een zin die dat zegt. Een grafiek om één punt leest als ontbrekende data in plaats van als een beginpunt. |

Bij een grafiek waar **lager beter is**, gaat een verbeterende lijn omláág.
Dat staat er onder elke zo'n grafiek in woorden bij — de helling alleen mag
het niet dragen, want een dalende lijn leest als achteruitgang voor wie
niet weet welke kant goed is.

### Tests zonder goed of fout

Lengte, gewicht en schoenmaat worden gemeten en gevolgd, maar een hogere
waarde is geen betere waarde. Deze tests staan per categorie bij elkaar als
**waarden per datum in kolommen**, met achteraan een kolom **Verloop**
(`+6`) in gewone tekst.

Ze krijgen bewust geen grafiek, geen streefzone en geen ranglijst. Een
stijgende lijn zou vooruitgang suggereren, een gearceerde band een norm, en
een lijstje "meest verbeterd" dat de langste speler het beste presteert —
alle drie zijn onwaar. Een gemist meetmoment toont als `—`, nooit als een
nul, en het verloop rekent over de datums waarop wél een meting staat.

Het **In één oogopslag**-paneel van de speler bevat ook een signaal
**Metingen** naast Gem. beoordeling, Aanwezigheid en Doelen: het aantal
tests waarvoor de speler nu een waarde heeft, met een hint hoeveel daarvan
*onder de norm* vallen (oranje of rood ten opzichte van de leeftijdsband)
— of *op schema* als dat er geen zijn. Het verwijst rechtstreeks naar het
tabblad Metingen voor de volledige tijdlijn per test. Het signaal
verschijnt alleen voor wie metingen mag inzien, zodat de stand nooit
zichtbaar wordt voor een rol die de onderliggende tests niet mag openen.

## Resultaten vastleggen

Staf krijgt een tegel **Metingen vastleggen**. Kies een team, een test en
een datum, voer per speler één waarde in en klik op **Alles opslaan** — de
hele selectie wordt in één keer opgeslagen (lege spelers worden
overgeslagen) en gekoppeld aan een testsessie voor die datum. Numerieke
tests tonen een getalveld met de eenheid; geslaagd/niet-tests tonen een
keuzelijst. Een trainer kan alleen voor de eigen teams vastleggen; het
hoofd opleiding en de academiebeheerder kunnen voor elk team vastleggen.

### Lengte en gewicht werken ook het spelersprofiel bij

Op het spelersprofiel staan een lengte en een gewicht, en dat waren tot
nu toe de getallen die iemand invulde toen de speler werd aangemaakt. Bij
een groeiende 13-jarige kloppen die binnen een paar maanden niet meer, en
nergens op het scherm was te zien dat ze verouderd waren.

Voortaan volgt het profiel de metingen. Zodra een nieuwe lengte- of
gewichtsmeting de meest recente van de speler is, toont het profiel dat
getal.

Beide staan op de kaart **Identiteit** van het tabblad Profiel van een
speler, met de datum waarop ze zijn gemeten erbij — "172 cm · gemeten 18
aug". Een getal dat iemand op het spelersformulier heeft ingevuld in
plaats van gemeten, staat er zonder datum, want die is er niet. Bij een
speler zonder allebei staat er helemaal geen regel.

Een paar dingen om te weten:

- **De test moet herkend worden.** Voor lengte: noem hem `Lengte`,
  `Height`, `Length` of `Stature`; voor gewicht: `Gewicht`, `Weight` of
  `Mass`. Een academie bepaalt zelf hoe tests heten, dus dit gaat op naam
  en niet op een vaste test. Hoofdletters en spaties maken niets uit —
  maar een naam met extra woorden erin, zoals `Lengte (cm)`, wordt niet
  herkend.
- **De twee staan los van elkaar.** Een gewichtsmeting kijkt alleen het
  gewicht opnieuw na en zegt niets over welke lengte de meest recente is.
- **De meest recente meting wint, niet de laatst ingevoerde.** Corrigeer
  je een meting van afgelopen januari, dan blijft het profiel op de
  nieuwere staan. Verplaats je een oude meting naar voren zodat die de
  meest recente wordt, dan volgt het profiel mee.
- **De laatste meting verwijderen maakt het profiel niet leeg.** Het
  getal dat er staat kan van vóór jullie testsessies komen, en een oud
  getal is bruikbaarder dan geen getal.
- **Een duidelijk verkeerd ingetikte meting wordt niet overgenomen.** Een
  lengte moet tussen 50 en 250 cm liggen en een gewicht tussen 10 en 200
  kg om op een profiel terecht te komen. De meting zelf blijft staan zoals
  je hem invoerde.
- **Je kunt beide nog steeds op het spelersformulier aanpassen.** Voor
  een academie die geen testsessies draait is dat precies goed — maar
  zodra er een meting is, neemt die het over.
- **Metingen die je al had, zijn bij de upgrade bijgewerkt.** Dit ging
  pas gaandeweg de metingen volgen, dus een eenmalige ronde heeft elk
  bestaand profiel in lijn gebracht met de metingen die eronder lagen.
  Er is niets leeggemaakt: een speler zonder bruikbare meting hield de
  waarde die er al stond.

De BMI-rapportage gebruikt de profiellengte bewust niet: een BMI heeft de
lengte nodig die gold op de dag van de weging, niet de laatst bekende.
Zie [BMI naar leeftijd](#bmi-naar-leeftijd).

## Testdekking (wie is aan de beurt)

Staf krijgt ook een tegel **Testdekking**. Kies een team en het scherm
toont, voor elke test met een herhaling, hoeveel van de selectie
**up-to-date** is versus het tekort - en noemt de spelers die **te laat**,
**binnenkort aan de beurt** of **nooit** getest zijn. Het is spelergericht:
het begint bij de selectie en laat precies zien wie deze cyclus nog een test
nodig heeft, zodat je een sessie kunt plannen. Tests zonder herhaling
(*ad hoc*) tellen niet mee voor de dekking. Een coach ziet de eigen teams;
het hoofd opleiding en de academiebeheerder zien elk team. Dezelfde gegevens
zijn beschikbaar via REST op
`GET /wp-json/talenttrack/v1/teams/{team_id}/measurement-coverage`.

## Een test aanmaken

Het hoofd opleiding (of een academiebeheerder) maakt tests aan met de
wizard **Nieuwe test** — bereikbaar vanaf het scherm *Metingen
vastleggen*. De wizard kent drie stappen:

1. **Gegevens** — de categorie, een naam en het type waarde (een getal,
 een schaalscore, geslaagd/niet of een status met gekleurde niveaus).
2. **Eenheid & frequentie** — de eenheid (uit de lijst of een eigen
 eenheid), of hoger of lager beter is, en hoe vaak de test plaatsvindt.
3. **Streefwaarden** — optionele groene en oranje banden per
 leeftijdsgroep; een geregistreerde waarde krijgt een vlaggetje ten
 opzichte van de band voor de leeftijdsgroep van de speler. Je kunt deze
 leeg laten en later toevoegen.

Bij voltooien worden de test en de streefwaarden in één keer aangemaakt.

## De testcatalogus beheren

Het hoofd opleiding (of een academiebeheerder) krijgt een tegel **Tests
beheren** onder *Configuratie*. Die opent een lijst met elke test die je
academie heeft ingesteld — naam, categorie, eenheid, richting en frequentie
— met de status **Actief** of **Inactief**, en drie acties per rij:

- **Bewerken** — opent de test in een plat formulier. Je kunt de naam,
 categorie, het type waarde, de eenheid (uit de lijst of een eigen
 eenheid), de schaalgrenzen, de richting, de frequentie, de
 actief-schakelaar en of de resultaten van de test **op het spelersprofiel
 worden getoond** wijzigen, en de groene/oranje streefbanden per
 leeftijdsgroep ter plekke aanpassen. **Opslaan** legt vast; **Annuleren**
 brengt je terug naar de lijst (of naar waar je vandaan kwam).
 Geslaagd/niet-tests hebben geen streefbanden.
- **Tonen op het spelersprofiel** — een vinkje per test (standaard aan). Zet
 het uit om een test buiten de metingenweergave op het spelersprofiel te
 houden terwijl die nog wel resultaten vastlegt en in de resultatenbrowser,
 rapporten en exports verschijnt. Handig voor interne of experimentele
 tests die je (nog) niet aan spelers en ouders wilt tonen. Bestaande tests
 blijven na de upgrade zichtbaar.
- **Activeren / Deactiveren** — een inactieve test blijft in de catalogus
 en behoudt de geschiedenis, maar wordt verborgen in de keuzelijst van
 *Metingen vastleggen*, zodat staf er geen nieuwe resultaten meer voor kan
 vastleggen.
- **Exporteren naar Excel** — downloadt alle vastgelegde resultaten van deze
 test als een opgemaakt `.xlsx`-bestand (zie hieronder).
- **Archiveren** — verplaatst de test naar de prullenbak (soft delete). Er
 gaat niets verloren; een beheerder kan hem herstellen.

### De resultaten van een test exporteren

Elke testregel — en het bewerkscherm van de test — heeft een actie
**Exporteren naar Excel**. Die maakt een opgemaakt werkblad voor die ene
test: een kopblok (testnaam, eenheid of *status*, datumbereik en club) boven
een vastgezette, vette kolomkoprij, en daaronder één regel per vastgelegd
resultaat met **speler, team, vastlegdatum, waarde, leeftijdsgroep en
vastgelegd-door**. Resultaten staan per speler gegroepeerd, zodat de reeks
van een speler in de tijd bij elkaar staat.

Bij een **status**-test toont de waardekolom het vastgelegde **niveaulabel**
(bijvoorbeeld *Op koers*), en de cel krijgt de kleur van dat niveau, zodat
het werkblad in één oogopslag leesbaar is — net als de statuschip op het
spelersprofiel. Numerieke tests tonen het getal met de eenheid.

De werkmap heeft een tweede tabblad **Trends** dat de resultaten van elke
speler **in de tijd** toont: één rij per speler, één kolom per meetdatum (in
chronologische volgorde), met in elke cel de vastgelegde waarde. Een
**lijndiagram** onder de tabel zet elke speler als reeks uit op dezelfde
datum-as, zodat je in één oogopslag ziet wie vooruitgaat, stagneert of
achteruitgaat. Numerieke tests en schaalscores worden in het diagram getoond;
een **status**-test toont per datum het niveau van elke speler ter referentie,
maar zonder diagram (er is geen numerieke as om te tekenen).

De export gebruikt dezelfde exportpijplijn als de rest van het systeem en is
afgeschermd met dezelfde *lees*-rechten op `measurements`: alleen staf die de
resultaten van een test mag zien, mag ze exporteren. De werkmap bevat precies
de rijen die diegene op het scherm ziet: een teamgebonden coach krijgt de
spelers van zijn eigen teams, en een team kiezen dat hij niet coacht wordt
geweigerd in plaats van stilzwijgend verbreed.

Een test aanmaken gaat nog steeds via de wizard **Nieuwe test**, bereikbaar
boven aan deze lijst en vanaf *Metingen vastleggen*. Dezelfde catalogus is
beschikbaar via REST op
`/wp-json/talenttrack/v1/measurement-definitions` voor integraties en de
SaaS-frontend.

## Resultaten doorbladeren — het scherm Testresultaten

De tegel **Testresultaten** (in de groep **Analyse** op het dashboard) opent
een overzicht om alle geregistreerde resultaten op één plek te lezen,
geordend per speler. Het beantwoordt de vraag "hoe doet elke speler het nu
op deze test?" zonder dat je de profielen één voor één hoeft te openen.

1. **Kies een test.** Tot je er een kiest, vraagt het overzicht erom. De
 keuzelijst toont elke test uit de catalogus, gegroepeerd per categorie.
2. **Verfijn eventueel** op **team**, **leeftijdsgroep** en een
 **periode** (van / tot). De filters herladen het overzicht zodra je op
 *Toon* drukt.
3. **Lees het overzicht.** Eén rij per speler met een waarde voor de test,
 met de **laatste waarde in de periode**:
 - **Statustests** tonen het **kleurvlakje met label** van het niveau
 (bijv. een groen *Op koers*), dezelfde kleuren als de chip op het
 spelersprofiel.
 - **Numerieke en schaaltests** tonen de **waarde met eenheid**, een kleine
 **trendpijl** (▲ verbeterd, ▼ verslechterd, ▬ gelijk) ten opzichte van
 het vorige resultaat van die speler, en een **vlag** — groen *op
 niveau*, oranje *onder niveau*, rood *ruim onder niveau* — tegen de band
 van hun leeftijdsgroep.

Het overzicht is **sorteerbaar** (tik op een kolomkop op tablet en desktop)
en elke **spelersnaam linkt naar het profiel**, met een terug-pil zodat je
met één klik terugkeert naar het overzicht. Een knop **Exporteren naar
Excel** downloadt de huidige test (met inachtneming van de team- en
periodefilters) via dezelfde opgemaakte werkmap als de export bij *Tests
beheren*.

Teamgebonden staf (coaches met alleen *lees*-rechten op hun eigen teams)
ziet uitsluitend resultaten van die teams; lezers met academiebreed bereik
zien iedereen. Een coach zonder teamtoewijzing ziet hier niets, niet de hele
academie. Dezelfde rijen zijn beschikbaar via REST op
`/wp-json/talenttrack/v1/measurement-results?definition_id=…` (filters:
`team_id`, `age_group`, `from`, `to`), afgeschermd met dezelfde
`measurements`-*lees*-rechten en beperkt tot dezelfde teams als het scherm:
`team_id` weglaten verbreedt het antwoord niet, en een team buiten je bereik
noemen levert `403` op. Voor integraties en de SaaS-frontend.

## Testverloop — één test, elke speler, over het seizoen

*Testresultaten* beantwoordt "hoe staat elke speler er **nu** voor op deze
test". **Testverloop** (groep Analyse) beantwoordt de andere helft: **wie
ontwikkelt zich en wie stagneert**. Het is het tabblad *Trends* uit de
Excel-export, nu op het scherm.

Kies een test, beperk desgewenst op team en een datumbereik, en klik
**Toon**. Wat je krijgt hangt af van de test — dezelfde regel die het
spelersverloop volgt, want een verloop betekent alleen iets in de termen
van de test zelf:

- **Een test met een richting** (sprinttijd, sprongkracht) begint bij de
 cijfers: een tabel met de waarde van elke speler op elk meetmoment en het
 **verschil**, daarna **Meest verbeterd** en **Teruggelopen**, en als
 laatste een **grafiek** met één lijn per speler over de gedeelde datum-as,
 plus een zwaardere gestreepte lijn voor het **teamgemiddelde**, zodat het
 gemiddelde nooit als nóg een speler leest.
- **Een test zonder richting** (lengte, gewicht) krijgt de metingen per
 datum — geen grafiek en geen ranglijst, want er is geen beter of slechter
 om op te ranken. Het verschil wordt wel getoond, met een grijze ▲ of ▼ die
 alleen zegt welke kant de waarde op ging.

**Je speler terugvinden in de grafiek.** Elke speler heeft een eigen kleur,
en diezelfde kleur staat als kort lijntje vóór de naam in de tabel en in de
twee ranglijsten — dat lijntje ís de legenda. Een selectie groter dan tien
gaat voorbij de tien kleuren die een lezer nog uit elkaar houdt, dus de elfde
speler krijgt de eerste kleur opnieuw, maar **gestreept**; de eenentwintigste
krijgt hem **gestippeld**. Kleur en patroon samen houden een volledige
selectie leesbaar: op het scherm, in een zwart-witafdruk, en voor een
kleurenblinde lezer.

**Stap voor stap, en in totaal.** Tussen elk paar datums staat een kolom **Δ**
met de verandering ten opzichte van het vorige meetmoment; de laatste kolom,
**Totaal**, beslaat alle meetmomenten die de speler heeft. Bij twee
meetmomenten zeggen die twee hetzelfde. Vanaf drie niet meer, en daar gaat het
om: een speler die 2 kg aankwam en 1,5 kg afviel heeft hetzelfde totaal als een
speler die rustig 0,5 kg aankwam, en alleen de stappen laten het verschil zien.
Ontbreekt aan één kant van een paar een meting, dan toont de stap `—`; hij
wordt nooit over het gat heen doorgetrokken naar de meting daarvoor.

**De kolom Verschil lezen.** Elk verschil — elke stap én het totaal — heeft een
teken, niet alleen een kleur, zodat het rapport ook in zwart-wit en voor een
kleurenblinde lezer leesbaar blijft:

| Teken | Betekenis |
| --- | --- |
| groene ▲ | verbeterd, in de termen van deze test |
| rode ▼ | teruggelopen |
| grijze ▬ | gelijk gebleven (minder dan 2% verschil) |
| grijze ▲ / ▼ | de waarde ging omhoog of omlaag bij een test zonder beter of slechter |
| — | geen eerdere meting om mee te vergelijken |

De pijl volgt het **oordeel**, nooit het teken van het getal. Bij een test
waar lager beter is hoort −0,08 s bij een groene ▲. Beweeg over het teken
(of laat het voorlezen) voor het woord erbij.

Spelersnamen in elke tabel en in beide ranglijsten linken door naar het
spelersdossier en tonen een samenvattingskaart als je erover beweegt.
- **Een statustest** krijgt een matrix speler × datum met de niveaus in hun
 eigen kleur. Geen lijnen: niveaus zijn benoemde standen, geen afstanden.
- **Gehaald / niet gehaald** krijgt een vinkje of kruisje per datum, de
 telling per speler en het **slagingspercentage per ronde** — het enige
 getal dat over tijd iets zegt zonder twee uitkomsten als schaal te
 behandelen.

**Het verschil wordt gelezen in de richting van de test.** Bij een test
waar lager beter is, is −0,08 s vooruitgang: het staat groen, het leest als
*verbeterd* en het staat bij *Meest verbeterd*. Een speler met een
verandering kleiner dan **2%** geldt als *nagenoeg gelijk* en staat in geen
van beide ranglijsten — één procent op een handmatig geklokte sprint valt
binnen de ruis, en dat vooruitgang noemen zou meer beweren dan er gemeten
is.

Elke spelersnaam linkt naar het profiel met een terug-pil. Een coach met
teamscope ziet alleen de eigen teams; een link naar de data van een ander
team wordt geweigerd in plaats van stilzwijgend verbreed. Integraties lezen
dezelfde getallen via
`GET /wp-json/talenttrack/v1/reports/test-trends?definition_id=…`.

Een beheerder kan het rapport verbergen via **Instellingen → Functies →
Testverloop**; staat het uit, dan verdwijnt de tegel en wordt een directe
link geweigerd.

## Wisselen tussen de schermen

**Tests & metingen** heeft vier schermen voor staf — *Tests beheren* (de
catalogus inrichten), *Metingen vastleggen* (resultaten invoeren),
*Testdekking* (zien wie aan de beurt is) en *Testresultaten* (ieders
resultaten doorbladeren) — en de beheerschermen verwijzen naar elkaar,
zodat je niet terug hoeft naar het dashboard:

- *Metingen vastleggen* toont een link **Tests beheren** naast **+ Nieuwe
 test**, zodat je snel de frequentie of banden van een test kunt aanpassen
 en meteen terugkeert.
- *Tests beheren* toont boven aan de lijst de links **Metingen vastleggen**
 en **Testdekking**.
- *Testdekking* toont een link **Tests beheren** (alleen voor staf die de
 catalogus mag bewerken).

Elke link draagt bij aankomst een contextuele terug-pil, zodat het
bestemmingsscherm een terugroute met één klik biedt naar waar je vandaan
kwam.

## BMI naar leeftijd

**Speler · BMI naar leeftijd** gebruikt de lengte en het gewicht die je al
vastlegt en zet die af tegen een gepubliceerde groeicurve. Je vindt het onder
**Rapportages**.

De meest recente waarde staat ook bovenaan het tabblad **Metingen** van een
speler: alleen het getal en het percentiel. Alles wat een BMI toelicht — tegen
welke curve hij is afgezet, hoeveel dagen er tussen beide metingen zaten en hoe
de waarde is verschoven — staat in de rapportage, het scherm dat je opent als
BMI is waarvoor je komt. Het tabblad is een blik in het voorbijgaan.

Een BMI op zichzelf zegt weinig over een jeugdspeler. Dezelfde waarde die bij
een zestienjarige niets bijzonders is, kan bij een elfjarige hoog zijn. Daarom
wordt elk getal hier getoond als **percentiel** voor de leeftijd en het geslacht
van die speler, en niet als losse waarde. Een percentiel beantwoordt de enige
vraag die ertoe doet: waar staat deze speler ten opzichte van leeftijdsgenoten?

Groeigegevens horen bij het persoonlijkste dat het systeem over een kind
vastlegt, dus beide helften van de rapportage blijven binnen je teambereik: het
overzicht toont de teams die je traint, en de curve van één speler openen wordt
geweigerd voor een speler daarbuiten — ook via een handmatig ingetypte link. Een
academiebreed meetrecht ziet nog steeds elke speler.

### Wat je eerst nodig hebt

In je testcatalogus moeten een **lengte**-test en een **gewicht**-test staan. De
gebruikelijke namen werken allemaal — *Lengte*, *Height*, *Gewicht*, *Weight* —
want de rapportage zoekt op de naam van de test, niet op een vast nummer.
Ontbreekt er één, dan zegt de rapportage dat, in plaats van een leeg raster te
tonen.

Een speler heeft ook een **geboortedatum** en een **geslacht** nodig. Zonder
geboortedatum is er geen leeftijd, en zonder geslacht is er geen curve. In die
gevallen toont de rapportage wel de BMI, maar blijft het percentiel leeg in
plaats van dat er iets wordt gegokt.

### Hoe een BMI tot stand komt

Een gewicht wordt gekoppeld aan de dichtstbijzijnde lengte die **binnen 30
dagen** is vastgelegd. Daarbuiten wordt geen BMI berekend: bij een groeiend kind
beschrijft een lengte van twee maanden geleden een ander lichaam. In de
rapportage staat bij elke waarde hoeveel dagen er tussen beide metingen zaten,
zodat je het zelf kunt beoordelen.

Spelers zonder bruikbaar paar staan wél in de tabel, met de reden erbij. Weten
van wie je geen gegevens hebt, is meestal het eerste om op te pakken.

### Wat de rapportage niet doet

Ze zegt niet dat een speler te zwaar of te licht is. Geen rode regels, geen
waarschuwingskleuren, geen grenswaarden. Ze toont een positie op een curve en
hoe die positie is verschoven sinds de vorige meting — de kolom **Verandering**
laat de verschuiving in standaarddeviaties zien, en dat is het getal dat over
een seizoen iets betekent.

Een groeicurve klinisch duiden is werk voor iemand die daarvoor gekwalificeerd
is. Dit zijn kinderen, en een scherm dat er één een etiket opplakt waar
toevallig iemand meekijkt, hoort niet in dit systeem.

### De referentie

Percentielen gebruiken de **WHO-groeireferentie 2007 voor 5–19 jaar**, die in de
rapportage wordt genoemd zodat je altijd weet welke curve je leest. Ze loopt van
5 tot en met 19 jaar; bij een speler daarbuiten zie je wel een BMI, maar geen
percentiel — ook op het tabblad van de speler, waar dat er met zoveel woorden bij
staat in plaats van dat het getal compleet lijkt.

De referentie is verwisselbaar: heeft jullie academie een andere nodig, dan kan
die worden ingewisseld zonder dat er verder iets aan de rapportage verandert.
