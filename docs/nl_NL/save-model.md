---
title: Hoe opslaan werkt
group: basics
summary: Welke schermen zichzelf opslaan, welke om Opslaan vragen, en hoe ver Ongedaan maken reikt.
audience: [user, admin, dev]
order: 30
---

# Hoe opslaan werkt

TalentTrack kent drie manieren om je werk vast te leggen, en elk scherm
gebruikt er precies één. Welke dat is, is geen toeval — het volgt uit waar het
scherm voor bedoeld is.

Een trainer hoort nooit te hoeven raden of zijn werk veilig is, dus elk model
laat op het scherm zelf zien welk model het is.

## 1. Het scherm slaat zichzelf op

**Wat je ziet.** Geen opslaanknop. In plaats daarvan een statusregel, overal in
dezelfde woorden: *Niet-opgeslagen wijzigingen…* terwijl je typt, *Opslaan…*
zolang het onderweg is, *Alle wijzigingen opgeslagen* als het klaar is en
*Opslaan mislukt — probeer opnieuw* als het misging. Ernaast staan **Ongedaan
maken** en **Wijzigingen terugdraaien**.

**Waar.**

| Scherm | Toelichting |
| --- | --- |
| [Wedstrijdvoorbereiding](match-prep.md) | Het hele scherm |
| [Wedstrijdanalyse](match-analysis.md) | Alleen het concept; **Markeren als definitief** publiceert het |
| [Evaluaties](evaluations.md) | Bij het bewerken van een bestaande |
| [Spelersdoelen](goals.md) | Bij het bewerken van een bestaand doel |
| [POP-gesprek](pdp-cycle.md) | Tot het is ondertekend |
| [POP-zelfreflectie](pdp-cycle.md) | Zolang het reflectievenster open staat |

**Waarom juist deze.** Het zijn allemaal plekken waar je *schrijft* — zinnen
over een speler, verspreid over minuten, vaak op een telefoon, vaak staand op
een onhandige plek. Wat daar het meest de moeite van beschermen waard is, is de
alinea waar je middenin zat, en een opslaanknop die je moet onthouden is daar
precies de verkeerde bescherming voor.

**Er is geen Annuleren**, want er staat niets open om van weg te lopen.
Ongedaan maken en Terugdraaien zijn hier hoe "dat bedoelde ik niet" eruitziet,
en ze reiken verder dan een formulier verlaten ooit deed.

### Hoe ver Ongedaan maken reikt

Twee bereiken, want ze beantwoorden twee verschillende vergissingen.

- **Ongedaan maken** — de laatst opgeslagen wijziging. Eén stap, geen historie.
  Hij verdwijnt zodra je hem gebruikt; maak de wijziging opnieuw als je hem
  terug wilt. Het terugdraaien wordt zelf opgeslagen, dus herladen brengt de
  wijziging niet terug.
- **Wijzigingen terugdraaien** — het hele scherm terug naar hoe het was toen je
  het opende. Hij vraagt het eerst, noemt hoeveel velden hij herstelt en
  waarschuwt dat het herstel zelf niet ongedaan te maken is.

Beide worden alleen aangeboden op een rustig scherm — zolang de statusregel
*Opslaan…* of *Niet-opgeslagen wijzigingen…* toont zijn ze verborgen, zodat
geen van beide kan botsen met een verzoek dat nog onderweg is.

**Terugdraaien hoort bij dit apparaat en deze sessie.** Het startpunt staat in
je browser, niet op de server. Dat is wat het een herlaadbeurt of een per
ongeluk gesloten tabblad laat overleven. Het betekent ook dat hetzelfde item op
een ander apparaat openen, of de volgende ochtend terugkomen, je het opgeslagen
document geeft zonder aanbod om terug te draaien — de sessie is voorbij. In een
privévenster, een geleegde browser of een browser die opslag weigert wordt er
helemaal niets aangeboden; de rest van het scherm werkt precies hetzelfde.

Geen van beide is versiehistorie, en geen van beide probeert dat te zijn. Er
zijn geen momentopnamen op de server, geen historie per veld en geen herstel
naar een datum.

### Publiceren is geen opslaan

Twee schermen houden één bewuste knop over, en geen van beide is een Opslaan:

- **Markeren als definitief** op een wedstrijdanalyse. Tot je erop drukt meldt
  de stafdeellink dat de analyse nog niet af is; daarna toont de link het
  document. Een definitieve analyse daarna bewerken laat hem definitief.
- **Ondertekenen** op een POP-gesprek. Alles daarboven is al opgeslagen;
  ondertekenen sluit het gesprek voor bewerken, voor iedereen.

Beide zijn in de praktijk onomkeerbaar, en dat is precies waarom geen van beide
een vinkje is op een scherm dat zichzelf opslaat.

## 2. Opslaan, met een echte Annuleren

**Wat je ziet.** Een knop **Opslaan** met daarnaast **Annuleren**, Annuleren
eerst in de tabvolgorde en Opslaan rechts, waar de duim hem vindt.

**Waar.**

- De drie invoerrasters: aanwezigheid, minuten, beoordelingen.
- Korte recordformulieren: speler, team, persoon, activiteit en de rest.
- Een evaluatie of doel **aanmaken** — in tegenstelling tot bewerken.
- Configuratieschermen en keuzelijsten.
- De [VCT-cycluskalender](configuration-vct.md), waar weken op neutraal
  worden gezet of juist gewoon doorlopen.

**Waarom de cycluskalender.** Eén week wijzigen verschuift elke week erna
voor de rest van het seizoen. Elke schakelaar opslaan op het moment dat hij
wordt aangetikt, zou een reeks half doordachte verschuivingen één voor één
doorvoeren, zonder één punt om ze vanaf terug te draaien — één keer opslaan
voor de hele set maakt het doorwerkende effect leesbaar.

**Waarom deze — de rasters eerst, want dat is het interessante geval.** De
rasters staan niet in de wachtrij tot automatisch opslaan hen bereikt.
Uitdrukkelijk opslaan is het juiste model voor een trainer die langs het veld
een hele selectie beoordeelt op een wankele verbinding: die krijgt **één
vastlegmoment**, zodat het dossier ofwel de sessie is die hij invoerde, ofwel
die van daarvoor — nooit een halve mengeling van de twee. En Annuleren betekent
annuleren. Een half afgemaakte vastlegging is erger dan een verloren
vastlegging wanneer wat je vastlegt een reeks oordelen is die alleen samen
betekenis hebben.

**Waarom de korte recordformulieren.** De velden zijn een kleine, bekende set,
en Opslaan is een nuttige adempauze — het moment waarop je controleert of de
datum klopt voordat je hem vastlegt. Die automatisch opslaan zou die pauze
weghalen en niets opleveren.

**Waarom aanmaken anders is dan bewerken.** Automatisch opslaan schrijft *naar
een record*, en tijdens het aanmaken is er nog niets om naartoe te schrijven.
Een aanmaakformulier dat automatisch opsloeg zou een lege evaluatie of een leeg
doel achterlaten in het dossier van een speler, bij iedereen die het formulier
opende en zich bedacht.

## 3. Concept, daarna versturen

**Wat je ziet.** Een wizard: Vorige / Volgende / Annuleren, en één vastlegging
op de laatste stap.

**Waar.** Elke wizard — nieuwe speler, nieuwe evaluatie, nieuw doel, nieuwe
wedstrijdanalyse, installatie en import.

**Waarom.** Een wizard houdt tussen de stappen al zijn eigen concept bij, dus
er gaat niets verloren als je halverwege stopt en later terugkomt. Wat hij niet
doet, is in het echte record schrijven vóór de laatste stap, en dat is precies
wat je toelaat er netjes mee te stoppen.

## Plaatsen is geen opslaan

Nog iets dat op opslaan lijkt en het niet is: de **gespreksvelden** —
spelersnotities en het gesprek bij een doel.

Een notitie is een *bericht*, geen veld. Hij wordt verstuurd als je op
**Versturen** drukt en niet eerder: er komt niets halfgeschrevens bij de staf
terecht, en er gaat geen melding uit voor een zin waar je nog de woorden voor
zocht. Een verstuurde notitie kun je vijf minuten lang nog bewerken; daarna
staat hij zoals hij geschreven is.

Wat het veld wél doet, is je concept bewaren. Sluit de tab, kom later op
hetzelfde apparaat terug, en de zin waar je middenin zat staat er nog. Hij
wordt gewist zodra de notitie echt verstuurd is en verlaat je eigen browser
nooit.

## Voor ontwikkelaars: een model kiezen voor een nieuw scherm

Vraag wat het scherm ís, niet wat handig is:

1. **Wordt hier geschreven?** Zinnen, oordelen, iets dat over minuten ontstaat
   in plaats van wordt ingevuld. → **Automatisch opslaan.** Gebruik
   `\TT\Shared\Frontend\Components\FormAutosave` en
   `\TT\Shared\Frontend\Components\SaveState`; de statusregel, ongedaan maken
   en terugdraaien komen mee. Bouw geen eigen debounce.
2. **Is een half afgemaakte vastlegging erger dan een verloren vastlegging?**
   Een reeks waarden die alleen samen betekenis hebben, in één keer ingevoerd,
   mogelijk op een slechte verbinding. → **Uitdrukkelijk opslaan**, met een
   echte Annuleren volgens `CLAUDE.md` §6.
3. **Wordt hier een record aangemaakt?** → **Uitdrukkelijk opslaan of een
   wizard**, nooit automatisch opslaan. Er is nog geen record om naartoe te
   schrijven.

Drie regels die gelden welk model je ook kiest:

- **Het endpoint moet gedeeltelijke updates accepteren voordat automatisch
  opslaan erop wordt gericht.** Een endpoint dat de hele rij opnieuw opbouwt uit
  het verzoek, plus een debounce, is een fout die data vernietigt — en hij meldt
  zichzelf niet: het lijkt op de analyse van een trainer die verdwijnt zodra hij
  een ander veld bewerkt. Voeg een test toe die aantoont dat een schrijfactie
  die een veld weglaat dat veld met rust laat;
  `tests/php/AutosaveWriteContractTest.php` is het patroon.
- **Een vastlegging die niet terug te draaien is, is nooit een veld op een
  formulier dat zichzelf opslaat.** Publiceren, ondertekenen, indienen: eigen
  knop, eigen bevestiging, buiten het formulier.
- **Zeg welk model het scherm gebruikt.** Een scherm dat zichzelf opslaat toont
  de statusregel; een scherm dat dat niet doet toont een opslaanknop. Zwijgen is
  precies de fout die deze regel wegneemt.

`CLAUDE.md` bevat de korte versie als altijd geldend principe, naast de
Opslaan + Annuleren-regel die het nuanceert.
