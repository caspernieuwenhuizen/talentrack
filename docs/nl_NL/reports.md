---
title: Rapporten
group: analytics
summary: De tegellancher — voortgangsgrafieken, teambeoordelingen, coachactiviteit.
audience: [user, admin]
views: [reports, standard-report, report-wizard, attendance-leaderboard, attendance-report-player, attendance-report-team, minutes-report-team, minutes-audit, analytics, explore, scheduled-reports, podium]
module: TT\Modules\Reports\ReportsModule
capability: tt_view_reports
order: 10
---

# Rapporten

De tegel **Rapporten** is een launcher voor verschillende manieren om naar je gegevens te kijken. De rapporten zijn gegroepeerd op doel zodat je het juiste rapport snel vindt: **Ontwikkeling & prestaties** (beoordelingen, voortgang, rate cards), **Speeltijd** (gespeelde minuten en selectiebelasting), **Aanwezigheid** (aanwezigheidsstatistieken per team en speler en de ranglijst), **Werving** (scouting, prospects, trial funnel), **Staf & kwaliteit** (coachactiviteit en beoordelingskwaliteit) en **Seizoensoverzicht** (de jaarlijkse review). Secties waartoe je geen toegang hebt — werving en seizoensbrede rapporten zijn alleen voor academy-beheerders — verschijnen gewoon niet. Alle standaardrapporten — waaronder team- en spelersaanwezigheid, de ranglijst en minuten-per-team — staan hier en tonen **Rapporten** in het kruimelpad; ze staan niet langer dubbel op het Analytics-dashboard.

Als **geen enkel** rapport voor je beschikbaar is — alle uitgeschakeld voor je academie, of geen ervan binnen je toegang — meldt de launcher dat duidelijk in plaats van een leeg raster te tonen, en verwijst je om een beheerder te vragen een rapport in te schakelen of je bereik te verruimen.

## Spelersvoortgang

Snelle visuele rapporten voor coaches:

- **Spelersvoortgang** — radarcharts van je topspelers.
- **Spelervergelijking** — kies twee of meer spelers en zie hun laatste evaluaties als overlappende radars.
- **Teamgemiddelden** — gemiddelden per team over de hoofdcategorieën.

Voor diepere weergaven per speler, zie [Rate cards](rate-cards.md) en [Spelervergelijking](player-comparison.md).

## Teambeoordeling gemiddelden

Een eenvoudige tabel — één rij per team, één kolom per hoofdcategorie, plus een evaluatietelling. Toont het levenslange gemiddelde over actieve evaluaties van elk team. Gearchiveerde spelers en evaluaties tellen niet mee.

Een snelle manier om te zien welk team dit seizoen het sterkst is.

## Coachactiviteit

Hoeveel evaluaties elke coach heeft opgeslagen in het gekozen venster (laatste 7, 30, 90, 180 of 365 dagen). Handig om coaches te signaleren die achterlopen, of om te bevestigen dat een geplande beoordelingsperiode echt heeft plaatsgevonden.

Alleen coaches binnen je eigen club worden meegeteld — het rapport is beperkt tot de huidige tenant en toont nooit activiteit van een andere academie. Een coach van wie het gebruikersaccount is verwijderd verschijnt nog steeds (de opgeslagen evaluaties blijven binnen het venster) maar krijgt het label **Onbekende coach** in plaats van een ruw accountnummer.

## Coach · Evaluatiekwaliteit

De evaluatie-steekproef van het hoofd opleiding als rapport: één rij per coach met het aantal evaluaties, het aantal beoordelingen, de gemiddelde score, de standaarddeviatie, de meest gegeven score (en welk aandeel van alle beoordelingen daarop zit) en de datum van de laatste evaluatie. Filterbaar op team en datumbereik.

Rijen waar de standaarddeviatie onder **0,5** ligt over **10 of meer beoordelingen** krijgen de vlag *lage variatie* — het statistische kenmerk van een coach die iedereen hetzelfde cijfer geeft. Een coach met maar een handvol beoordelingen wordt nooit gevlagd; er valt dan nog geen zinvolle variatie te meten.

Beperkt tot academiebrede rollen (hoofd opleiding / beheerder): coaches kunnen elkaars statistieken niet inzien. De knop **Exporteren (CSV)** downloadt dezelfde rijen; integraties kunnen ze lezen via `GET /wp-json/talenttrack/v1/reports/coach-evaluation-quality` met dezelfde rechtencontrole.

## Frontendrapporten + Afdrukken/Opslaan als PDF

Team-gemiddelden en Coach-activiteit renderen nu rechtstreeks op het publieke dashboard via `?tt_view=reports&type=team_ratings` en `?type=coach_activity` — geen sprong meer naar wp-admin. Elk rapport heeft bovenaan een knop **Afdrukken / Opslaan als PDF**: bij klikken opent het printvenster van de browser met een stijlblad dat dashboard-elementen verbergt, zodat "Opslaan als PDF" een schone PDF oplevert.

## Speler · Voortgang & radar

Het oude wp-admin-rapport "Spelersontwikkeling & Radar" rendert nu rechtstreeks op het dashboard als standaardrapport (Rapporten → *Speler · Voortgang & radar*). Dezelfde drie modi met dezelfde data: **Spelersvoortgang** (de laatste vijf evaluaties van elke geselecteerde speler als gestapelde radarseries — laat de selectie leeg voor de top-10 actieve spelers), **Spelersvergelijking** (de meest recente evaluatie van elke speler over elkaar op één radar; kies er minstens twee) en **Teamgemiddelden** (één radarserie per team, gemiddeld per categorie).

Coaches zien alleen spelers en teams van hun eigen teams; academiebrede rollen zien alles. De oude wp-admin-route stuurt door naar dit rapport, dus bladwijzers blijven werken. Integraties kunnen dezelfde datasets lezen via `GET /wp-json/talenttrack/v1/reports/player-radar?mode=…&player_ids=…`.

## Alleen activiteiten uit het verleden tellen mee

Beide aanwezigheidsrapporten — en de ranglijst en het risicopaneel die dezelfde query gebruiken — tellen alleen activiteiten mee die **echt zijn gehouden**: afgerond en in het verleden (sessiedatum vandaag of eerder). Een activiteit met een datum in de toekomst telt nooit mee voor een aanwezigheidsstatistiek, zelfs als de aanwezigheid vooraf is ingevuld. Een activiteit met de datum van **vandaag** telt wel mee. Zo blijft het aanwezigheidscijfer van elke speler kloppen — een coach ziet alleen sessies die de speler echt had kunnen bijwonen.

**"Afgerond" is de status op de activiteitpagina.** Een activiteit telt mee zodra de **Status** op *Voltooid* staat — hetzelfde woord dat de coach op het scherm ziet en met **Markeer als voltooid** zet. Een sessie die nog *Gepland* is, telt niet mee, ook niet als de datum in het verleden ligt en er al aanwezigheid is geregistreerd. Daardoor zijn de getallen controleerbaar: lijkt een cijfer te hoog, open dan de activiteit en lees de status. Het team- en spelersaanwezigheidsrapport, de ranglijst, het risicopaneel, de dagelijkse aanwezigheidsmelding, de team-KPI's en de badge **Activiteiten** op het spelersdossier lezen allemaal deze ene poort en kunnen dus niet uit elkaar lopen.

Aanwezigheid registreren is zelf de uitspraak dat een sessie heeft plaatsgevonden. Daarom **markeert het opslaan van aanwezigheid in het raster een geplande activiteit uit het verleden als voltooid**; de opslagbalk meldt hoeveel er zijn omgezet. Een sessie in de toekomst wordt zo nooit voltooid — een bekende afmelding voor volgende week vooraf vastleggen is geen uitspraak dat volgende week al geweest is. Een activiteit die op **Geannuleerd** staat, blijft altijd geannuleerd.

## De aanwezigheidsrapporten filteren — periodepillen + activiteittype

Zowel het teamrapport als het spelersrapport gebruiken dezelfde filters als de activiteitenlijst:

- **Eén periodekeuze** — *Vorige week*, *Deze maand* (tot vandaag), *Dit seizoen*, en **Aangepast bereik…**. *Vorige week* en *Deze maand* kijken terug (tot vandaag). ***Dit seizoen* beslaat het hele seizoen** — van de startdatum van het seizoen tot en met de **einddatum** van het seizoen — dus het dekt de hele campagne, niet alleen het deel dat al voorbij is. Een voorkeuze kiezen zet het bereik voor je. **Aangepast bereik…** opent de Van/Tot-datums binnen dezelfde keuze, en wat je daar typt wint van de voorkeuze. Omdat het één keuze is, klopt wat er staat altijd met het venster waarop het rapport draaide: kies je een voorkeuze, dan noemt hij die; typ je datums, dan noemt hij de datums.
- **Activiteittype** — beperk tot één type (training / wedstrijd / toernooi, afhankelijk van wat jullie academy heeft ingesteld). Het typefilter beperkt elk cijfer consistent: de KPI-tegels, de tabel, de ranglijst en het risicopaneel.

**Standaardperiode.** Wanneer je een rapport opent zonder een pil te kiezen of een Van/Tot-bereik te typen, staat het standaard op **het huidige seizoen** — van de startdatum van het seizoen tot vandaag. Dit komt overeen met de pil *Dit seizoen* en met hoe de academie over het jaar denkt, in plaats van een willekeurig meelopend venster. Als er geen seizoen is ingesteld, valt het rapport terug op de laatste **90 dagen**, zodat er altijd iets te zien is. Het minutenrapport per team in Analyse volgt dezelfde standaard; de twee standaard-minutenrapporten houden juist een voortschrijdende standaardperiode van 12 maanden aan — zie *De minutenrapporten filteren* hieronder. Een pil kiezen of een handmatig Van/Tot typen overschrijft dit nog steeds. Omdat deze standaard *het* seizoensvenster is, tonen beide aanwezigheidsrapporten nu de pil ***Dit seizoen* gemarkeerd** bij het openen — de filterbalk weerspiegelt het venster dat je echt bekijkt, in plaats van "Aangepast bereik".

**Bereikmelding.** Als je maar enkele teams traint, tonen de aanwezigheidsrapporten alleen die teams. Levert je filter niets op, dan meldt de lege-status dat het rapport **beperkt is tot de teams die je traint**, zodat een leeg venster niet leest als "de academie heeft geen data".

**Een team uitklappen dat je niet mag lezen, zegt dat ook.** In het teamaanwezigheidsrapport klapt elke rij uit naar de verdeling per speler. Mag je de spelers van dat team niet inzien, dan meldt de uitklap nu dat **je geen toegang hebt tot de spelers van dit team** — waar er eerder stond dat er geen aanwezigheid in de periode was. Dat was een uitspraak over de gegevens in plaats van over je toegang, en kon pal onder een rij staan die 92,9% aanwezig meldde.

Op een telefoon klappen de filters samen tot een **Filters**-knop die een bottom sheet opent; vanaf desktopbreedte staan ze inline. Elke besturing is met het toetsenbord te bedienen.

## Een filterset opslaan als benoemde weergave (v4.x+)

Je **opgeslagen weergaven** staan in de filterbalk zelf, als chips aan de rechterkant, op elk standaardrapport — de team-, speler- en ranglijst-aanwezigheidsrapporten en de minutenrapporten. Stel de filters in waar je steeds naar terugkeert (een team, een activiteittype, een periode), klik op **Huidige filters opslaan…**, geef het een naam zoals *"JO17 competitiewedstrijden"*, en het wordt een chip die je met één klik opnieuw toepast. Opgeslagen weergaven zijn **persoonlijk** — alleen jij ziet die van jou — en horen elk bij het ene rapport waarop je ze hebt opgeslagen. Een weergave onthoudt een periodepil als **relatieve** keuze (*Dit seizoen* blijft volgende maand ook "dit seizoen"); een handmatig Van/Tot-bereik wordt als exacte data onthouden. Gebruik het bladwijzer-icoon naast de chips om er een te hernoemen, te vervangen of te verwijderen. Opslaan, tonen en verwijderen lopen via de REST-API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), afgeschermd met de capability van het scherm waar de weergave bij hoort — `tt_view_analytics` voor deze rapporten. De oude paden `/reports/filter-presets` blijven nog één release werken.

Opgeslagen weergaven zijn niet langer alleen voor rapporten. Ze horen bij de gedeelde filterbalk en zijn ook beschikbaar op de zes standaardrapporten en op de lijsten met spelers, teams, personen, evaluaties, doelen, toernooien, vakanties en activiteiten, plus het auditlogboek. Op een lijst onthoudt een opgeslagen weergave ook de **zoekterm en de sorteervolgorde**, niet alleen de filters — een weergave die je sortering terugzet, is niet de weergave die je hebt opgeslagen. De opgeslagen weergaven van elk scherm zijn afgeschermd met de capability van dat scherm zelf, zodat een opgeslagen weergave nooit een scherm kan tonen dat je anders niet mag openen. De invoerroosters voor aanwezigheid en minuten en het scherm met eigen velden bieden ze bewust niet aan.
Je **opgeslagen weergaven** staan in de filterbalk zelf, als chips aan de rechterkant, op elk standaardrapport — de team-, speler- en ranglijst-aanwezigheidsrapporten en de minutenrapporten. Stel de filters in waar je steeds naar terugkeert (een team, een activiteittype, een periode), klik op **Huidige filters opslaan…**, geef het een naam zoals *"JO17 competitiewedstrijden"*, en het wordt een chip die je met één klik opnieuw toepast. Opgeslagen weergaven zijn **persoonlijk** — alleen jij ziet die van jou — en horen elk bij het ene rapport waarop je ze hebt opgeslagen. Een weergave onthoudt een periodepil als **relatieve** keuze (*Dit seizoen* blijft volgende maand ook "dit seizoen"); een handmatig Van/Tot-bereik wordt als exacte data onthouden. Het bladwijzer-icoon naast de chips opent een menu; **Deze weergave hernoemen, vervangen of verwijderen** opent een klein venster waarin je dit kunt doen: hernoem de weergave, vink **Vervang ook de filters door de nu ingestelde filters** aan om hem naar je huidige filters te laten wijzen, of verwijder hem. Hernoemen behoudt de filters; de filters vervangen behoudt de naam. Een naam die je op hetzelfde scherm al gebruikt, wordt geweigerd — dezelfde naam op een ander scherm, of bij iemand anders, mag wel. Opslaan, tonen en verwijderen lopen via de REST-API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), afgeschermd met de capability van het scherm waar de weergave bij hoort — `tt_view_analytics` voor deze rapporten. De oude paden `/reports/filter-presets` blijven nog één release werken. Opgeslagen weergaven zijn niet langer alleen voor rapporten: ze horen bij de gedeelde filterbalk, dus elk scherm dat daarop is gebouwd kan ze aanbieden.
Je **opgeslagen weergaven** staan in de filterbalk zelf, als chips aan de rechterkant, op elk standaardrapport — de team-, speler- en ranglijst-aanwezigheidsrapporten en de minutenrapporten. Stel de filters in waar je steeds naar terugkeert (een team, een activiteittype, een periode), klik op **Huidige filters opslaan…**, geef het een naam zoals *"JO17 competitiewedstrijden"*, en het wordt een chip die je met één klik opnieuw toepast. Opgeslagen weergaven zijn **persoonlijk** — alleen jij ziet die van jou — en horen elk bij het ene rapport waarop je ze hebt opgeslagen. Een weergave onthoudt een periodepil als **relatieve** keuze (*Dit seizoen* blijft volgende maand ook "dit seizoen"); een handmatig Van/Tot-bereik wordt als exacte data onthouden. **Eén standaard openen.** Vink in dat venster **Deze weergave standaard openen op dit scherm** aan en die weergave wordt toegepast zodra je het scherm opent zonder eigen filters — één klik wordt geen klik. Eén standaard per scherm per persoon; een nieuwe aanwijzen laat de oude los. De standaardchip krijgt een ster, en de adresbalk toont de toegepaste filters, zodat de pagina te bookmarken en te delen blijft. Je standaard overschrijft nooit een bewuste keuze: een link die al filters meedraagt, een **← Terug naar**-pil of een gedeelde URL tonen precies wat het adres vraagt. Gebruik **Wissen** in de filterbalk om alles ongefilterd te zien — dat ontsnapt aan de standaard voor dit bezoek in plaats van je erin terug te werpen. Momenteel beschikbaar op de aanwezigheids- en minutenrapporten.

Het bladwijzer-icoon naast de chips opent een menu; **Deze weergave hernoemen, vervangen of verwijderen** opent een klein venster waarin je dit kunt doen: hernoem de weergave, vink **Vervang ook de filters door de nu ingestelde filters** aan om hem naar je huidige filters te laten wijzen, of verwijder hem. Hernoemen behoudt de filters; de filters vervangen behoudt de naam. Een naam die je op hetzelfde scherm al gebruikt, wordt geweigerd — dezelfde naam op een ander scherm, of bij iemand anders, mag wel. Opslaan, tonen en verwijderen lopen via de REST-API (`GET/POST /filter-presets`, `DELETE /filter-presets/{id}`), afgeschermd met de capability van het scherm waar de weergave bij hoort — `tt_view_analytics` voor deze rapporten. De oude paden `/reports/filter-presets` blijven nog één release werken. Opgeslagen weergaven zijn niet langer alleen voor rapporten: ze horen bij de gedeelde filterbalk, dus elk scherm dat daarop is gebouwd kan ze aanbieden.

## De tabel Speler-aanwezigheid lezen

**Aanwezig toont het percentage én de breuk erachter** — *33,3% (1/3)*. Bij drie
activiteiten zegt een percentage op zichzelf weinig; de breuk vertelt zonder
rekenwerk wat er werkelijk gebeurde.

**Te laat, Afwezig, Afgemeld en Geblesseerd tonen het aantal**, geen percentage.
Twee gemiste trainingen staan er als **2**. De kolommen sorteren op getal, dus 2
komt vóór 10.

## Inzoomen op de activiteiten achter een getal

Zowel het aantal bij **Activiteiten** als de risicomarkering opent de
activiteitenlijst, beperkt tot precies waaruit het getal is opgebouwd: die
speler, die periode, dat team — **en het activiteitstype waarop het rapport
gefilterd staat**. Filter je het rapport op *Training*, dan toont de doorklik
trainingen en niet de hele teamkalender.

De lijst zegt dat er ook bij. Boven de filterbalk staat een strook **Alleen
getoond:** met de speler en de periode, elk met een **×** die alléén die
beperking opheft en de rest laat staan. Team en type staan in de filterbalk
zelf, waar je ze al ziet en kunt aanpassen.

## Inzoomen op de spelers van een team (teamrapport)

In het teamrapport is elke teamrij **tikken-om-uit-te-klappen**: tik op de teamnaam om een inline subtabel met de spelers van dat team te openen (speler · aanwezig %, met risicospelers gemarkeerd), op aanvraag geladen voor de actieve periode en filters. Nogmaals tikken klapt hem in; er is één team tegelijk open. Zonder JavaScript opent een **Spelers bekijken**-link naast elk team het spelersrapport, vooraf gefilterd op dat team — het inzoomen is altijd bereikbaar.

## Gespeelde minuten — totalen en trace per wedstrijd

De minutenrapporten lezen alleen **vastgelegde** wedstrijdminuten: werkelijke,
niet-gast-aanwezigheid. Geplande (verwachte) selectierijen en gastoptredens
tellen nooit mee, en een wedstrijd zonder vastgelegde minuten draagt niets bij —
de rapporten schatten, berekenen of construeren nooit minuten, dus een nul is een
eerlijke "geen gegevens vastgelegd" in plaats van een gok. Minuten worden één
keer bepaald, wanneer een gespeelde wedstrijd wordt vastgelegd (de match-execution
wordt afgerond, of de minuten worden met de hand ingevoerd op het
aanwezigheidsscherm), en op de aanwezigheidsregel opgeslagen. Elk rapport leest
alleen die opgeslagen waarde; een wedstrijd die wel gespeeld maar nooit afgerond
is, toont daarom niets tot de minuten zijn vastgelegd, in plaats van te worden
herleid uit de geplande opstelling.

**Alleen gespeelde wedstrijden verschijnen.** Een wedstrijd telt mee zodra één
van drie dingen waar is: de activiteit staat op **voltooid**, de **datum is
voorbij**, of er zijn al **minuten vastgelegd**. Een wedstrijd die vanavond
begint staat dus niet in de tabel per wedstrijd en niet in de KPI's ernaast —
hij is niet gespeeld tot je hem voltooit, de minuten vastlegt, of de dag om is
— terwijl een wedstrijd die je vanochtend speelde en langs de lijn voltooide
meteen meetelt.

De datum telt op zichzelf mee omdat een activiteit voltooien een aparte,
bewuste handeling is: een wedstrijd van vorig seizoen staat op *gepland* als
niemand op de knop heeft gedrukt, en is toch gespeeld.

**Wedstrijden, games en toernooien tellen allemaal mee.** De minutenrapporten
behandelen wedstrijden, games en toernooien op dezelfde manier — elk is een
activiteit die minuten oplevert. Een toernooi met één wedstrijd legt minuten
precies als een wedstrijd vast (opstelling plannen, het live-wedstrijdscherm
draaien, afronden); een toernooidag met meerdere wedstrijden legt minuten vast
met de handmatige invoer per speler op het aanwezigheidsscherm (hoeveel minuten
elke speler die dag daadwerkelijk speelde). Hoe dan ook belanden de minuten op de
aanwezigheidsregel en leest elk rapport ze. Een toernooi zonder vastgelegde
minuten toont nog steeds niets — dezelfde eerlijke nul als een wedstrijd.

### Doelpunten en assists naast de minuten

**Team · Minutenverdeling** heeft naast de minuten de kolommen **Doelpunten
gemaakt** en **Assists**. Minuten zeggen hoeveel van de wedstrijd een speler
kreeg; doelpunten en assists zeggen wat hij ermee deed, en die twee van
verschillende schermen moeten aflezen is precies wat de vergelijking
tegenhoudt.

Beide worden geteld uit de doelpunten die op het wedstrijduitvoeringsscherm
zijn vastgelegd, over hetzelfde tijdvak als de rest van de tabel. Een speler
die niets bijdroeg toont een nul, geen leeg vakje — leeg zou lezen als "niet
gemeten".

Drie regels zijn goed om te weten, want ze bepalen wiens getal beweegt:

- Een doelpunt dat niemand heeft toegekend telt mee voor de **stand** maar
  voor **geen enkele speler**. Het hoort bij de wedstrijd totdat iemand de
  maker invult.
- Een **eigen doelpunt** telt nooit mee bij de doelpunten van de maker. In
  eigen doel schieten is geen aanvallende bijdrage.
- Een **ongedaan gemaakt** doelpunt telt voor niemand.

Dezelfde getallen staan op de spelerskaart zelf — de tegel **Doelpunten
gemaakt** in de overzichtsstrook, met de assists op de regel eronder. Let op
de bewoording: de tegel ernaast, kortweg **Doelen**, telt de ontwikkeldoelen
van een speler. Die twee betekenissen worden overal uit elkaar gehouden waar
ze elkaar tegenkomen.

### De minutenrapporten filteren

**Speler · Gespeelde minuten** en **Team · Minutenverdeling** hebben dezelfde
filterbalk als de aanwezigheidsrapporten — snelkeuzes voor de periode (*Vorige
week*, *Deze maand*, *Dit seizoen*) plus een handmatig bereik **Van / Tot** dat
de snelkeuze overschrijft. Alles op de pagina volgt de gekozen periode: de
KPI-tegels, de tabel per wedstrijd of de spelerbalken, de drill-down van elke
speler en de link **Verkenner →**.

**Standaardperiode: de laatste 12 maanden.** Anders dan de
aanwezigheidsrapporten gaan de twee minutenrapporten standaard uit van een
voortschrijdende periode van **12 maanden** in plaats van het huidige seizoen —
dat is de periode die ze altijd al gebruikten, en zo verandert er geen enkel
getal alleen maar omdat de filterbalk erbij komt. De optie zonder snelkeuze
heet daarom *Laatste 12 maanden*. Kies een snelkeuze of typ een bereik om te
versmallen; **Wissen** brengt je terug naar de standaardperiode. De subregel van
het rapport noemt altijd de datums die de getallen daadwerkelijk beslaan.

### Wat "wedstrijden" betekent in het rapport Team · Minutenverdeling

De tegel *Wedstrijden vastgelegd* telt de wedstrijden die **vastgelegde minuten
hebben opgeleverd** — precies de wedstrijden waaruit de spelerbalken eronder
zijn opgebouwd, zodat de tegel die nooit kan tegenspreken. Daaronder staat de
eerlijke noemer: hoeveel wedstrijden er in de periode zijn **gespeeld**.
Verschillen die twee, dan krijgt de tegel een signaal: *"3 gespeelde
wedstrijden hebben geen minuten"* maakt duidelijk dat het om een hiaat in de
vastlegging gaat en niet om een leeg seizoen.

**Een wedstrijd die vanavond begint is nog niet gespeeld.** Hij telt voor geen
van beide getallen mee tot je hem voltooit, de minuten vastlegt, of de dag om
is — anders waarschuwt het rapport dat de wedstrijd van vanavond minuten mist.
Eén van de drie is genoeg, en juist daardoor telt een wedstrijd die je
vanochtend speelde nog dezelfde dag mee, en blijft een seizoen aan wedstrijden
waar niemand ooit op *Voltooien* heeft gedrukt gewoon in de noemer staan.

Wedstrijden die **gearchiveerd, in de prullenbak, afgelast of nog niet gespeeld**
zijn, tellen voor geen van beide getallen mee. Een vooraf ingelezen
wedstrijdprogramma leest daarom als *"0 van 0 gespeelde wedstrijden vastgelegd"*
tot de eerste wedstrijd is gespeeld, en als *"0 van 19"* zodra er negentien
gespeeld zijn maar nog niets is vastgelegd — in plaats van de misleidende "19
wedstrijden" naast een lege selectie die eerdere versies toonden.

**Vertrokken spelers tellen evenmin mee.** Minuten die zijn vastgelegd op een
speler die inmiddels gearchiveerd is, tellen niet mee in de tegel *Wedstrijden
vastgelegd* — net zoals die speler niet meer in de selectie eronder staat. De
tegel en de rijen beschrijven één selectie; een getal dat de rijen niet kunnen
verklaren is erger dan een kleiner getal.

### Het relatieve getal: Minutenaandeel

Deze twee rapporten antwoorden in absolute getallen, en één ding kunnen ze niet
zeggen: welk deel van wat er werkelijk te vergeven was, elke speler kreeg.
**Team · Minutenaandeel** doet dat wel: de eigen duur van elke gespeelde
wedstrijd opgeteld tot een noemer, de vastgelegde minuten per speler daarover,
en een instelbare norm — standaard 30% — die iedereen daaronder markeert. Zie
[Minutenaandeel](minutes-share.md) voor het volledige verhaal.

Gebruik het rapport **Minutencontrole** (hieronder) om te zien welke spelers bij
welke wedstrijd minuten missen.

**Basisplaatsen tellen alleen vastgelegde wedstrijden.** De *basisplaatsen* van
een speler en het *% beschikbaar* tellen alleen wedstrijden die daadwerkelijk zijn
vastgelegd (die minuten hebben opgeleverd) — een wedstrijd die wel gepland was,
met een opstelling, maar nooit gespeeld of vastgelegd werd, telt voor geen van
beide mee. Basisplaatsen kunnen daarom nooit meer zijn dan wedstrijden. Voor een
toernooidag met meerdere wedstrijden zijn de uit de opstelling afgeleide
"basisplaatsen" bij benadering (één opstelling dekt meerdere wedstrijden), dus
zijn daar de vastgelegde *minuten* de betekenisvolle waarde, niet het aantal
basisplaatsen.

Het minutentotaal van elke speler is een **drill-down**: open het om de rijen per
wedstrijd te zien die optellen tot het totaal — datum, wedstrijd, type, bron
(`werkelijk` vastgelegde minuten) en minuten. De uitsplitsing sluit exact aan op
het totaal, zodat je een gerapporteerd
getal altijd kunt herleiden tot de bronrijen. In het rapport Team · Minutenverdeling
klapt elke spelersbalk uit; in het Analytics-minutenrapport opent elk Totaal de
tabel per wedstrijd onder de rij. Beide werken op een telefoon en met het
toetsenbord; zonder JavaScript blijven de rijen per wedstrijd inline zichtbaar.

Integraties kunnen dezelfde trace lezen — met de `tt_view_reports`-toegang en
dezelfde team-scope-beperking als het rapport:

- `GET /wp-json/talenttrack/v1/teams/{team_id}/players/{player_id}/minutes?from=…&to=…` — de minutenrijen per wedstrijd voor één speler plus het aansluitende `total_minutes`.

Om een totaal tegen de ruwe opgeslagen rijen te controleren, zijn de
`tt_attendance`-minutenrijen (`minutes_played`, `record_type`, `is_guest`,
`activity_id`) doorzoekbaar in de **Data Browser**.

De uitsplitsingstabel per wedstrijd is nu één **gedeelde component** voor zowel
het rapport Team · Minutenverdeling als het Analytics-minutenrapport, zodat de
twee nooit uit elkaar lopen en beide op dezelfde manier aansluiten op het
totaal van de speler.

## Minuten-audit — wedstrijden × spelers auditmatrix

Het rapport **Minuten-audit** (bereikbaar vanuit de rapportenlauncher onder
*Speeltijd*, of rechtstreeks via `?tt_view=minutes-audit`) is de audit-tegenhanger
van het minutenrapport. Het beantwoordt een andere vraag: *welke spelers uit de
selectie hebben per wedstrijd wél en welke géén geregistreerde minuten?* — zodat
een beheerder of hoofdcoach de gaten kan opsporen en aanpakken voordat de
minutengegevens van een seizoen verouderen.

Het overzicht is alleen-lezen. De link *Bewerk* / *Registreer* per rij — alleen
zichtbaar voor gebruikers die geregistreerde minuten mogen bewerken — opent de
**per-wedstrijd minuten-editor** (zie hieronder). Gebruikers zonder die
bevoegdheid zien de volledige matrix, alleen zonder de bewerklink.

Het scherm is een matrix in spreadsheet-stijl:

- **Rijen** zijn de wedstrijd-, match- en toernooiactiviteiten van het team in de
 periode (dezelfde set die het minutenrapport telt).
- **Kolommen** zijn de selectie — elke speler die voorkomt op de **aanwezigheid**
 van die wedstrijden. De selectie wordt bepaald op basis van aanwezigheid, niet
 op basis van de teamindeling van een speler, zodat een speler die voor één
 wedstrijd werd geleend toch verschijnt en een speler die het team verliet maar
 eerder in de periode speelde niet stil wordt weggelaten.
- **Cellen** tonen de geregistreerde minuten van die speler in die wedstrijd. Een
 groene cel is geregistreerde minuten; een rode **0** is een speler die in de
 selectie zat maar geen geregistreerde minuten heeft (een gat om aan te pakken);
 een gearceerd streepje is een speler die niet in de selectie van die wedstrijd
 zat.
- Elke rij heeft een **rijtotaal**, een **statuschip** voor volledigheid —
 *Volledig* (elke speler uit de selectie heeft minuten), *Onvolledig* (sommigen
 wel, sommigen niet) of *Niet geregistreerd* (niets geregistreerd voor de
 wedstrijd) — en de onderste **kolomtotaal**-rij telt de minuten van elke speler
 over de zichtbare wedstrijden op.

Boven de matrix vatten vier **gat-KPI's** — *Wedstrijden*, *Volledig
geregistreerd*, *Onvolledig*, *Niet geregistreerd* — de periode samen. Elke KPI is
klikbaar en filtert de matrix op die volledigheidscategorie, zodat *Niet
geregistreerd* meteen naar de wedstrijden springt die nog minuten missen.

Omdat de audit **dezelfde** geregistreerde, werkelijke, niet-gast-minuten leest
als het minutenrapport, sluiten de cijfers exact aan op dat rapport. De
eerlijke-nul-regels gelden hier ook: een team met wedstrijden maar zonder
geregistreerde minuten toont elke wedstrijd, eerlijke *Niet geregistreerd*-chips
en een duidelijke vervolgstap — nooit een misleidende "0 spelers"-lege staat. Een
lege periode (helemaal geen wedstrijden) meldt dat apart.

Coaches zien alleen de teams die ze coachen; academiebrede rollen zien de hele
club. De filterbalk heeft de gedeelde besturing voor team / periode /
wedstrijdtype / datumbereik en staat standaard op het venster van het huidige
seizoen.

### Per-wedstrijd minuten-editor

De link *Bewerk* / *Registreer* op een matrixrij opent de **per-wedstrijd
minuten-editor** (`?tt_view=minutes-audit&match_id=N`). Deze toont de selectie van
de wedstrijd en laat een beheerder of hoofdcoach de **geregistreerde minuten per
speler** corrigeren, waarbij de gezaghebbende geregistreerde waarde wordt
weggeschreven — dezelfde bron die de rapporten en het wedstrijdverloop-scherm
lezen. Een wedstrijd die *na* een audit-bewerking in het wedstrijdverloop wordt
geopend, toont de gewijzigde cijfers.

De editor kiest automatisch het juiste schrijfpad:

- Voor een wedstrijd die is voorbereid en langs de lijn is gespeeld (met een
 wedstrijdverloop) legt een opgeslagen cijfer een **expliciete correctie per
 speler** vast. Die correctie staat naast de waarde die uit het wissellog wordt
 afgeleid, zodat ze elke latere herberekening overleeft en corrigeerbaar blijft
 zelfs nadat de wedstrijd is afgerond.
- Voor een papieren wedstrijd (zonder wedstrijdverloop) schrijft een opgeslagen
 cijfer de geregistreerde minuten van de speler rechtstreeks.

Het bewerken gaat behoedzaam met wedstrijdgegevens om: een leeggemaakt veld is een
*expliciete* wissing (de correctie of geregistreerde waarde wordt gewist en valt
terug op een eventueel afgeleid cijfer), nooit een stille nul, en een rij die je
niet aanraakt wordt nooit weggeschreven. Elke rij toont het afgeleide cijfer naast
de bewerkbare waarde, zodat een correctie altijd zichtbaar is.

De editor is afgeschermd met dezelfde bewerkbevoegdheid die de schrijf-endpoints
afdwingen, zodat de link nooit verschijnt voor een gebruiker die niet kan opslaan.
Opslaan en Annuleren zijn beide aanwezig; Annuleren keert terug naar het overzicht.

**Reikwijdte van deze eerste versie:** de editor bewerkt de **totale minuten per
speler** — de waarde die de geregistreerde gegevens werkelijk opslaan. Het bewerken
van de basisopstelling per helft en het wissellog (met de minuut van elke wissel)
is een geplande vervolgstap; die staan vandaag op het wedstrijdverloop-scherm.

## Standaardrapporten — eerlijke cijfers

Elk standaardrapport benoemt nu de periode en de bron waaruit het put, zodat een
cijfer nooit een stille gok is:

- **Eerlijke lege toestanden.** Als een rapport niets te tonen heeft, zegt het
 in gewone taal *waarom* — "Geen wedstrijden geregistreerd in deze periode",
 "Geen beoordelingen geregistreerd voor dit team in deze periode", "Geen
 scoutingskandidaten geregistreerd in deze periode" — in plaats van de oude
 algemene "pas een filter aan"-tekst (de meeste van deze rapporten hebben geen
 filter om aan te passen). Het Seizoensoverzicht toont geen lege pagina meer
 onder de kop-tegels wanneer er geen teams zijn.
- **Speler · Gespeelde minuten** beslaat de **laatste 12 maanden** (vermeld in
 de subregel van de pagina, gelijk aan de Explorer-drill), en wanneer een
 speler meer dan 50 wedstrijden in die periode heeft, staat er *"De 50 meest
 recente wedstrijden worden getoond"* zodat een langere historie nooit
 ongemerkt wegvalt.
- **Team · Teambeoordelingsoverzicht** toont per speler een datum **Laatst
 beoordeeld**, zodat een verouderde rij in één oogopslag zichtbaar is.
- **Team · Trends uit wedstrijdanalyses** telt hoe vaak elke spelfase in de
 periode is beoordeeld als *Ging goed*, *Wisselend* of *Moet beter*. Het telt
 en middelt nooit — drie geordende woorden zijn geen cijfer — een fase die de
 coach niet beoordeeld heeft valt buiten de telling in plaats van als neutraal
 mee te tellen, en onder drie beoordeelde wedstrijden staat er *"nog te weinig
 wedstrijden"* in plaats van een lijn door één punt. Zie
 [Wedstrijdanalyse](match-analysis.md) voor de spelerskant van dezelfde
 gegevens.
- **Seizoensoverzicht** telt gearchiveerde activiteiten niet meer mee in de
 wedstrijdaantallen per team (op de join zelf, niet alleen in de telling),
 waardoor een bron van opgeblazen joins verdwijnt.

### Aansluiting van de trial-trechter

De Seizoen · Trial-trechter **sluit nu aan**. De tabel Per beslissing toont de
uitkomsten van cases die *in de periode geopend* zijn, plus een rij **In
afwachting (nog niet beslist)** en een **Totaal**-rij die optelt tot *Geopende
trial-cases*. De tegel **Beslissingspercentage** draagt een korte toelichting
dat de teller (besliste cases, op beslisdatum) en de noemer (geopende cases, op
openingsdatum) verschillende periodes gebruiken, zodat het percentage niet als
één-cohortpercentage wordt gelezen. Elke scoutnaam in de tabel Per scout linkt
naar de **Scoutrapportkaart** van die scout (afgeschermd op `tt_view_reports`,
dezelfde rechten die de kaart afdwingt).

### Gespeelde minuten (team) — gedeelde filter- en KPI-chrome

Het rapport Gespeelde minuten (team) gebruikt nu de **gedeelde filterbalk**
(team, retrospectieve periodepillen — Vorige week / Deze maand / Dit seizoen —
een wedstrijdtype-keuze en een handmatig Van/Tot-bereik) en de **gedeelde
KPI-strip**, gelijk aan de aanwezigheidsrapporten. De standaardperiode is het
huidige seizoen. Op een telefoon klappen de filters in het gebruikelijke
bottom-sheet; elke bediening houdt een aanraakdoel van 48px.

### Standaardrapporten — gedeelde filterbalk, selectiefix, drill-downs

Vier standaardrapporten dragen nu **dezelfde gedeelde filterbalk** die de
aanwezigheids- en minutenrapporten gebruiken: retrospectieve **periodepillen**
(Vorige week / Deze maand / Dit seizoen) plus een handmatig **Van/Tot**-bereik.
De standaardperiode is het **huidige seizoen** (seizoensstart → vandaag; een
voortschrijdend venster van 90 dagen als er geen seizoen is ingesteld). Een
periodepil kiezen laat een handmatig bereik vallen; een Van/Tot typen overschrijft
de pil. Het betreft **Team · Teambeoordelingssamenvatting**, **Seizoensoverzicht**,
**Seizoen · Trial-trechter** en de **Scoutrapportkaart**. Op een telefoon klappen
de filters in het gebruikelijke bottom-sheet; elke bediening houdt een aanraakdoel
van 48px. De subregel van elk rapport en de Verkenner-drill noemen nu hetzelfde
venster, zodat het cijfer en de drill overeenkomen.

Het rapport **Team · Minutenverdeling** had een selectiefout: het telde
wedstrijden op basis van de activiteiten van het team, maar bouwde de spelerslijst
op uit `tt_players.team_id` — dus een team waarvan de spelers geen `team_id`
hadden, toonde "18 wedstrijden, 0 spelers". De selectie wordt nu op **dezelfde
manier bepaald als in de rest van de analytics** — spelers met geregistreerde
aanwezigheid op de wedstrijd-/toernooiactiviteiten van het team — zodat de
spelerslijst en het wedstrijdaantal één definitie delen, en een speler
verschijnt ook met **0 geregistreerde minuten**. Minuten komen nog steeds
uitsluitend uit vastgelegde `record_type='actual'`-aanwezigheidsrijen (ze worden
nooit geschat), dus een wedstrijd zonder geregistreerde minuten telt 0.

**KPI's van standaardrapporten zijn nu drill-downs** waar een gefilterde lijst
aansluit op het cijfer: de tegel *Spelers* van Team · Minutenverdeling opent de
teamselectie en de tegel *Wedstrijden* de activiteitenlijst gefilterd op de
wedstrijden van dat team; de tegels *Actieve spelers / Actieve teams /
Wedstrijden* van het Seizoensoverzicht openen hun lijsten; de tegel *Prospects
vastgelegd* van de Trial-trechter opent de prospectslijst. Elke drill draagt een
**← Terug naar …**-hint en is verborgen wanneer de gebruiker de capability van
de bestemming mist (§7 verbergen-niet-plagen).

## Spelersaanwezigheid — ranglijst + risicomarkering

Het aanwezigheidsrapport per speler staat standaard op **laagste aanwezigheid eerst** (laagste aanwezig-%), zodat de spelers die aandacht nodig hebben bovenaan staan. Het toont **elke speler** met geregistreerde aanwezigheid in de periode — geen top-N-limiet — en elke kolom blijft sorteerbaar (klik op een kop om opnieuw te sorteren).

Spelers die een instelbaar aantal activiteiten hebben **gemist** in de periode (afwezig / afgemeld / geblesseerd) worden **gemarkeerd**: een ⚠-badge met het aantal gemiste activiteiten, een licht gekleurde rij, en een paneel **Risicospelers** boven de tabel. De drempel (standaard **3**) is de *enige bron van waarheid* die gedeeld wordt met de dagelijkse aanwezigheidsmelding, zodat het rapport en de melding altijd overeenkomen.

De ⚠-badge (en elke naam in het paneel **Risicospelers**) is een **link** — tik erop om de markering te herleiden tot de onderliggende sessies. Hij opent dezelfde spelergerichte activiteitenlijst als het *Activiteiten*-aantal (deze speler, het team van het rapport, de periode van het rapport), zodat je de datumsessies ziet die de speler bijwoonde en het aantal gemiste activiteiten kunt verifiëren. Een **← Terug**-link keert terug naar het rapport.

### Het activiteitenaantal natrekken (drill-down)

Het **Activiteiten**-aantal van elke speler is een link. Open het om de daadwerkelijke sessies achter het getal te zien: de activiteitenlijst opent gefilterd op die speler, het team van het rapport en de periode van het rapport, en toont alleen activiteiten waarvoor de speler een geregistreerde aanwezigheidsregel heeft. Vanaf daar opent elke activiteit het detail met de geregistreerde aanwezigheidsstatus, zodat een coach het aantal kan verifiëren tegen de bronregels — dezelfde tracering die het minutenrapport biedt. Een **← Terug**-link keert terug naar het rapport.

### De risicodrempel instellen

De drempel staat onder **Configuratie → Algemeen → Risicodrempel aanwezigheid** (een instelling voor de academy-beheerder). Eén getal, tussen 1 en 50, bepaalt elke risicomarkering: het aanwezigheidsrapport per speler, de aanwezigheidsranglijst en de dagelijkse aanwezigheidsmelding lezen het allemaal. Zet hem lager om verzuim eerder op te merken, of hoger als jullie academy alleen op aanhoudend verzuim wil reageren.

## Aanwezigheidsranglijst

Een aparte ranglijst die je opent vanuit de Rapporten-startpagina (*Aanwezigheidsranglijst*). Hij rangschikt spelers over de gekozen periode in twee tabellen naast elkaar: **Aandacht nodig** (de laagste aanwezigheid-%, waar risicospelers hun ⚠-badge houden) en **Meest betrouwbaar** (de hoogste aanwezigheid-%). Standaard toont hij **alle** spelers in de periode; typ een aantal in *Hoeveel* om elke tabel tot dat aantal rijen te beperken. Beperk eventueel tot één team. Coaches zien alleen hun eigen teams; academy-brede rollen zien de hele club.

Hij deelt dezelfde filterbalk en chrome als het spelersaanwezigheidsrapport: een **team**-keuze, retrospectieve **periode**-pillen (afgelopen week / maand / seizoen enzovoort), een **activiteittype**-filter en een handmatig **datumbereik** dat de actieve periode overschrijft, plus de ranglijst-specifieke *Hoeveel*-limiet. Open je hem zonder filters, dan valt hij terug op het huidige **seizoen**. Boven de tabellen vat een KPI-strip de gerangschikte spelers samen — aantal spelers, gemiddelde aanwezigheid en hoeveel er risico lopen — berekend uit dezelfde gegevens, dus zonder extra query.

Op een telefoon stapelen de twee tabellen tot één kolom zonder horizontaal scrollen; vanaf tabletbreedte staan ze naast elkaar. Bovenop de standaardrangschikking is elke kolom sorteerbaar.

Integraties kunnen dezelfde gegevens lezen — met dezelfde `tt_view_analytics`-toegang en team-afbakening — via:

- `GET /wp-json/talenttrack/v1/reports/attendance-leaderboard?from=…&to=…&n=…&team_id=…&activity_type_key=…` — `{ top, bottom, total }`.
- `GET /wp-json/talenttrack/v1/reports/attendance-at-risk?from=…&to=…&team_id=…&activity_type_key=…` — gemarkeerde spelers met de slechtste eerst, elk met een `declining`-trendindicator, plus de actieve `threshold`.
- `GET /wp-json/talenttrack/v1/reports/attendance?from=…&to=…&team_id=…&activity_type_key=…` — de aanwezigheidsrijen per speler voor één periode (voedt het inzoomen in het teamrapport): `{ players, threshold }`.

De optionele `activity_type_key` op elk aanwezigheids-endpoint beperkt tot één activiteittype, gelijk aan het Type-filter in de rapport-UI.

## Dimensieverkenner — rijlimiet en filtervalidatie

De dimensieverkenner (de *Verken*-actie bij een KPI) laat je de onderliggende feitrijen van een metriek filteren en erop inzoomen. Drie waarborgen houden het inzoomen betrouwbaar:

- **Limiet van 5000 rijen, nu zichtbaar.** De verkenner leest per inzoomactie maximaal **5000** feitrijen. Wanneer een gefilterde set die grens raakt, toont de tabel onder de paginering de melding **"Beperkt tot 5000 rijen — gebruik groeperen om grotere sets samen te vatten."**, zodat het zichtbare aantal pagina's nooit voor de volledige dataset wordt aangezien. Groepeer op een dimensie om grotere sets samen te vatten in plaats van door ruwe rijen te bladeren.
- **Filters gevalideerd tegen de dimensies van de KPI.** Alleen de dimensies die een KPI daadwerkelijk aanbiedt om te verkennen, worden als filter geaccepteerd. Een `filter_<key>` voor een dimensie die de KPI niet aanbiedt, wordt stilzwijgend genegeerd — het bereikt de query of de CSV-/PDF-export nooit, zodat de filters die je op het scherm ziet altijd overeenkomen met de filters die op het geëxporteerde bestand zijn toegepast.
- **Datumgrenzen accepteren een absolute of een relatieve vorm.** *Datum na* en *Datum voor* accepteren zowel een exacte datum (`2026-01-15`) als een relatieve verschuiving (`-30 days`, `-12 months`, `+7 days`; de eenheid mag `day`, `week`, `month` of `year` zijn, enkelvoud of meervoud). Een relatieve grens wordt bij elke keer openen opnieuw ten opzichte van vandaag berekend, zodat een opgeslagen link "de laatste 30 dagen" blijft betekenen in plaats van vast te lopen op de dag waarop je hem opsloeg. Een grens die geen van beide is — een typefout als `30 dayz ago`, of een onmogelijke datum als `2026-02-30` — wordt **genegeerd**; het rapport toont dan gewoon geen datumgrens. Er wordt nooit naar geraden: een filter dat stilzwijgend op het verkeerde venster inzoomt, valt minder snel op dan een filter dat zichtbaar niet is toegepast.
