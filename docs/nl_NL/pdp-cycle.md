---
title: Persoonlijk Ontwikkelingsplan (POP)
group: performance
summary: Seizoens­gebonden ontwikkeldossiers, gespreks­cadans, eindeseizoens­oordeel.
audience: [user]
views: [pdp, my-pdp]
module: TT\Modules\Pdp\PdpModule
capability: tt_view_pdp
order: 60
---

# Persoonlijk Ontwikkelingsplan (POP)

Een **POP-dossier** is een seizoensÂ­gebonden ontwikkelplan voor Ã©Ã©n speler. Het brengt samen wat anders verspreid raakt over evaluaties, doelen en losse aantekeningen â€” en geeft de academie een herhaalbare cadans: instelbare gespreksÂ­momenten over het seizoen, polymorfe koppelingen tussen doelen en de methodische woordenlijst, en een doelbewust eindeseizoensÂ­oordeel ondertekend door het hoofd academie.

## Wie ziet wat

- **Coaches** â€” volledige bewerking van POP-dossiers voor spelers in hun eigen teams. Tegel: **Performance â†’ POP**.
- **Hoofd academie** â€” globale bewerking van alle dossiers plus exclusieve schrijftoegang tot het eindeseizoensÂ­oordeel.
- **Spelers** â€” alleen-lezen op het eigen dossier, gepresenteerd als een seizoenstijdlijn, plus een bewerkbare zelfreflectie voor het ene eerstvolgende geplande gesprek. Tegel: **Mijn â†’ Mijn POP**.
- **Ouders / verzorgers** â€” alleen-lezen op het dossier van hun kind (na ondertekening) plus een per-gesprek bevestigingsÂ­knop.
- **Read-only observer** â€” alleen-lezen op alle dossiers; geen bewerking, geen bevestiging.

## POP-overzicht: wie heeft dit seizoen een POP

De **POP**-tegel opent op één **spelergerichte lijst** voor het huidige seizoen in plaats van een kale lijst met dossiers. Het vertrekpunt is de speler (CLAUDE.md §1): elke speler die je traint wordt één keer getoond, met een duidelijke indicator of het POP **voor dit seizoen** al bestaat.

- Bestrijk je **meer dan één team** (of heb je globale scope), dan kies je eerst een team — *"Selecteer een team om de spelers te zien."* — zodat je afgebakend begint in plaats van alle spelers tegelijk te zien. Een coach met één team gaat direct naar de eigen selectie.
- Bovenaan staat een samenvattingsregel, bijvoorbeeld: *"14 van de 18 spelers hebben een POP voor het huidige seizoen (2025/26)."*
- Elke rij toont de **speler** (gekoppeld aan het spelerrecord), het **team** en een **POP dit seizoen**-status:
 - **Aangemaakt** — een groene *POP ✓*-pil, waar mogelijk met gespreksvoortgang (bijv. *POP ✓ 1/3*), die direct naar het dossier linkt.
 - **Niet gestart** — een grijze *Niet gestart*-pil plus een knop **POP aanmaken** die de aanmaakflow opent, voor­ingevuld voor die speler en dat team.
- **Filters** — teamkeuze + zoeken op speler, op dezelfde manier afgebakend als de rest van de app: coaches zien alleen spelers van hun eigen teams; beheerders zien iedereen.
- **Alleen spelers zonder POP** — een schakelaar met één klik om iedereen te verbergen die al een dossier heeft, zodat je de gaten kunt wegwerken.
- Met de **⋯**-knop aan het eind van de filterrij (voor wie mag herstellen of verwijderen) schakel je de lijst naar de spelers van wie het POP voor het seizoen **gearchiveerd** is, met per rij **Herstellen** / permanent verwijderen. Dit vervangt het oude aparte tabblad Dossiers — gearchiveerde bestanden staan nu in dezelfde lijst.
- Klik op een gedekte rij om het POP-dossier van de speler te openen; klik op een ontbrekende rij om naar de aanmaakflow te springen.

De dekkingsdata is ook beschikbaar via REST op `GET /wp-json/talenttrack/v1/pdp-files/coverage` (`season_id`, `filter[team_id]`, `search`, `only_missing`, `archived`), zodat een toekomstige front-end hetzelfde antwoord krijgt.

## De flow

### 1. Open het dossier

Klik op de **POP**-tegel op *POP aanmaken* in de rij van een speler (of op *Nieuw POP-dossier openen*), kies een speler en klik op *Nieuw POP-dossier openen*. Het dossier wordt aangemaakt met Ã©Ã©n gesprek per cyclus (2, 3 of 4 â€” instelbaar per club, te overschrijven per team). Elk `scheduled_at` wordt evenredig over de start- en einddatum van het seizoen verdeeld.

Voor elk gesprek wordt automatisch een native agenda-item bijgehouden.

### 2. Voer de gesprekken

Elk gesprek heeft twee fases:

- **VÃ³Ã³r het gesprek** â€” agenda + de zelfreflectie van de speler.
- **Na het gesprek** â€” notities, afgesproken acties en ondertekening.

Het tabblad **Bewijs** in het formulier verzamelt wat de academie al weet over de speler sinds het vorige gesprek — alleen-lezen, zodat de trainer het gesprek opent op basis van het dossier en niet op basis van zijn geheugen: evaluaties met beoordeling, beoordelaar en notities; aanwezigheid met de verdeling aanwezig / afwezig / afgemeld, gespeelde wedstrijden en speelminuten, met de opbouw per wedstrijd; doelen, en of ze bewogen zijn; de zelfreflectie van de speler; notities van de staf, blessures en gebeurtenissen op de tijdlijn; en de potentieel- en gedragsbeoordelingen uit die periode.

Een onderdeel zonder inhoud zegt dat ook, in plaats van te verdwijnen — “Geen evaluaties in deze periode” is zelf iets om te weten voor een gesprek.

De gesprekken verlopen op volgorde: alleen het **actieve** gesprek — het eerste dat nog niet is afgetekend — is volledig bewerkbaar. Latere gesprekken in de cyclus zijn alleen-lezen, behalve hun **geplande datum**, zodat een coach het hele seizoen vooruit kan plannen zonder een gesprek buiten de beurt in te vullen. Een later gesprek komt vrij voor volledige bewerking zodra het voorgaande gesprek is afgetekend.

De speler kan op elk moment vÃ³Ã³r ondertekening zijn zelfreflectie invullen. Zodra de coach ondertekent, wordt het veld vergrendeld.

### Het gespreksformulier slaat zichzelf op

Er is geen knop **Gesprek opslaan** meer. Het formulier slaat op terwijl je schrijft, en op de plek van de knop staat een statusregel die vertelt hoe ver dat is — *Niet-opgeslagen wijzigingen…*, *Opslaan…*, *Alle wijzigingen opgeslagen*. **Ongedaan maken** en **Wijzigingen terugdraaien** staan ernaast, net als op elk ander automatisch opslaand scherm; beide staan volledig beschreven in [hoe opslaan werkt](save-model.md).

De **zelfreflectie** van de speler werkt hetzelfde, zolang het venster open staat. Buiten het venster is het veld uitgeschakeld, zoals eerder, en wordt er niets opgeslagen.

**Ondertekenen is nu een eigen knop, geen vinkje op het formulier.** Het was een vinkje dat je samen met de rest opsloeg. Op een formulier dat zichzelf opslaat zou dat één misklik verwijderd zijn van het gesprek permanent op slot zetten voor iedereen — daarom staat er nu een aparte knop **Ondertekenen** onder het formulier, achter een bevestiging. Alles daarboven is op dat moment al opgeslagen; ondertekenen is wat het gesprek voor bewerken sluit.

Een **inhoudelijk vergrendeld** gesprek — een later gesprek in de cyclus — slaat nog wel zijn geplande datum automatisch op, het enige veld dat het je laat wijzigen. Een **ondertekend** gesprek slaat niets op, want er valt niets meer te schrijven.

### 3. Bevestiging

Na ondertekening verschijnt het gesprek op het *Mijn POP*-overzicht van de speler (en de ouder, indien gekoppeld). Beiden kunnen op *Bevestigen* klikken â€” een lichte "ik heb het gezien"-timestamp.

Als het gesprek persoonlijk plaatsvindt, kan de coach die bevestigingen ook op het gespreksformulier vastleggen — *Bevestiging speler vastleggen* / *Bevestiging ouder vastleggen*, elk achter een bevestigingsdialoog. Het legt dezelfde bevestiging vast alsof de speler of ouder er zelf op had geklikt. Bevestig alleen wanneer zij het gesprek daadwerkelijk met u hebben bevestigd.

### Wat de speler ziet: een tijdlijn-eerst Mijn POP

*Mijn POP* is de **voorbereidings- en zelfreflectieruimte** van de speler, opgebouwd rond het seizoen als een tijdlijn.

- **Seizoenstijdlijn bovenaan.** De ontwikkelgesprekken van het seizoen staan als markers op een horizontale rail, elk in een van drie toestanden: **afgerond** (een groene ✓), **gepland** (het gouden eerstvolgende gesprek) en **later** (gedempt). Een voortgangsvulling loopt langs de rail tot aan het laatst afgeronde gesprek. Op een marker tikken **vouwt het gespreksdetail ter plekke uit** - notities, afgesproken acties, agenda, de besproken doelen, een eventueel opgeslagen reflectie en de bevestigingsknop - zodat lang scrollen niet nodig is. De markers zijn bedienbaar met het toetsenbord (Tab om te focussen, Enter/Spatie om te openen, Escape om te sluiten).
- **Actieve doelen onder de tijdlijn.** De huidige focusdoelen van de speler (niet het volledige archief), elk met het doelspecifieke statuslabel (bijv. *In ontwikkeling*) en de streefdatum.
- **Eén zelfreflectie-invoer.** Alleen het **ene eerstvolgende geplande** gesprek kan een reflectie krijgen - eerdere en latere gesprekken tonen nooit een invoerveld. Het veld verschijnt zodra het reflectievenster van 2 weken vóór het gesprek opent; daarvóór legt een melding uit wanneer het verschijnt. Een eerder opgeslagen reflectie staat **rechts** van de invoer op bredere schermen en **eronder gestapeld** op mobiel. De zelfreflectie is bedoeld als hulp, nooit verplicht - er wordt niets geblokkeerd als de speler het overslaat.
- **Eindeseizoensoordeel** sluit de pagina af zodra het is vastgelegd.

Ouders zien dezelfde tijdlijn voor hun kind, alleen-lezen en bezittelijk ("ontwikkelplan van &lt;kind&gt;"): de opgeslagen reflectie is zichtbaar maar er is geen bewerkbaar veld, en zij bevestigen via hun eigen knop. De tijdlijnstatus wordt afgeleid uit de ingeplande gesprekken en hun planvensters - er verandert niets aan de planning of de vensterdata.

### 4. EindeseizoensÂ­oordeel

Wanneer het laatste gesprek van de cyclus is ondertekend, legt het hoofd academie (of de hoofdcoach in sommige configuraties) een eindoordeel vast: **doorstromen**, **behouden**, **uitsluiten**, of **transfer**. Het eindoordeel is een aparte rij, los ondertekend van de gesprekken.

De knop *Eindoordeel vastleggen* staat bij de gesprekkenlijst, onder de cyclus. De knop blijft **uitgeschakeld totdat elk gesprek in de cyclus is afgetekend**, met de voortgang op de knop zelf — bijv. *Eindoordeel vastleggen (3/5 gesprekken afgerond)* — zodat duidelijk is waarom hij nog niet beschikbaar is in plaats van dat hij ontbreekt.

## Carryover

Bij het instellen van een nieuw huidig seizoen draait een eenmalige taak: elk open POP-dossier uit het vorige seizoen wordt voor het nieuwe seizoen gerepliceerd â€” verse gesprekken, vers `created_at`, maar de open doelen van de speler (alles behalve `completed` of `archived`) worden meegenomen.

Tekstuele inhoud van gesprekken wordt **niet** meegenomen. Elk seizoen begint schoon; de geschiedenis blijft staan waar het stond.

## DoelenÂ­koppelingen

Een doel kan nu gekoppeld worden aan Ã©Ã©n of meer methodische entiteiten:

- een **principe** (bv. *opbouwen vanaf achteren*)
- een **voetbalhandeling** (bv. *passen onder druk*)
- een **positie** (bv. *nummer 8*)
- een **spelerswaarde** (toewijding, leerbaarheid, leiderschap, veerkracht, communicatie, werkethiek, fairplay, ambitie â€” bewerkbaar via Configuratie â†’ Lookups)

De koppelingen verschijnen op het spelerprofiel en in de printsjabloon; ze maken queries mogelijk als "alle doelen gekoppeld aan *veerkracht* in de academie" of "elke speler die werkt aan *opbouwen vanaf achteren*".

### Doelen en ontwikkelgesprekken

Een doel kan ook aan een **ontwikkelgesprek** worden gekoppeld. Op het gespreksformulier vinkt de coach onder **Doelen besproken in dit gesprek** de actieve doelen van de speler aan die zijn behandeld; die koppelingen worden bij het gesprek opgeslagen. Op *Mijn POP* toont een uitgevouwen gespreksmarker vervolgens een lijst **Besproken doelen**, zodat de zelfreflectie van de speler ingaat op de doelen die echt aan bod kwamen - POP en doelen worden zo echt gecombineerd in plaats van naast elkaar te staan. (Een afgesproken actie omzetten in een gloednieuw doel is een geplande vervolgstap; deze stap is het lees-/koppelweefsel.)

## Printen

De **Printen / PDF**-knop in het detailoverzicht opent een schone A4-layout: foto, seizoenslabel, huidige doelen + status, afgesproken acties per gesprek, en handtekeningÂ­regels voor coach / speler / ouder. Schakel *Opnieuw renderen met bewijspagina* in voor een tweede A4 met recente evaluaties en activiteiten.

## Configuratie

- **Configuratie â†’ Lookups â†’ SpelersÂ­waarden** â€” bewerk de waarde-woordenlijst.
- **Hoofdmenu â†’ Seizoenen** â€” lijst, toevoegen, huidig instellen. Een nieuw huidig seizoen instellen activeert de carryover.
- **Configuratie â†’ Systeem** â€” *POP-cyclusstandaard* (2 / 3 / 4) en de *Print: standaard bewijs meenemen*-knop.
- **Per-team override** â€” op de team-bewerkÂ­pagina kun je *POP-cyclusÂ­grootte* afwijkend instellen.

## WerkflowÂ­herinneringen

Er zijn drie taaktemplates geregistreerd:

- `POP_conversation_due` â€” herinnert de verantwoordelijke coach wanneer `scheduled_at` van een gesprek nadert.
- `POP_verdict_due` â€” herinnert het hoofd academie aan het einde van het seizoen.

Beide gebruiken de werkflow- en takenmotor â€” dezelfde inbox, dezelfde herinneringsÂ­cadans die je instelt via Configuratie â†’ Werkflow.

### Zelfreflectie-nudge

Wanneer het planvenster van een gesprek opent, krijgt de speler een taak **"Bereid je voor op je ontwikkelgesprek"** in *Mijn taken / Werk van vandaag*, met als deadline de gespreksdatum. Erop tikken opent *Mijn POP* bij de zelfreflectie. De sweep die deze taken aanmaakt draait op de planner van de werkflowmotor en is idempotent - precies een taak per gesprek, geen duplicaten. Het derde sjabloon `pdp_self_review` levert deze taak.

Het is een **duwtje, geen poort**:

- Het opslaan van de reflectie **voltooit** de taak.
- Het voeren van het gesprek **lost de taak automatisch op** zonder gevolgen, ook als de reflectie nooit is ingevuld.
- Er wordt nooit iets geblokkeerd als de speler het negeert.

Aan de coachkant krijgt de gesprekslijst een kolom **Zelfreflectie** met **Klaar / Nog niet** per komend gesprek - alleen ter info, nooit een poort voor het voeren of ondertekenen.

## Cyclusvoortgang en bevestigingen

Op de POP-detailpagina staat de cyclusvoortgang nu als **(X van N ondertekend)** naast de cyclusgrootte, zodat je in één oogopslag ziet hoe ver de cyclus is. Elke gespreksregel laat zien:

- een afgeleide **status**-badge (Gepland / Gehouden / Ondertekend) in plaats van drie aparte datum/ja-kolommen
- een **Bevestigingen**-kolom met iconen voor ouder (👤) en speler (⚽) — `✓` na bevestiging, `·` zolang de bevestiging nog ontbreekt

De samenvattingskaart heeft een hulpknop die het PDP-onderwerp direct in de docs-drawer opent.

## Archiveren versus definitief verwijderen

POP-dossiers kennen **twee** verwijderpaden zodat destructief opruimen nooit per ongeluk de verkeerde regel raakt.

- **Archiveren** — soft-delete. Het dossier verdwijnt uit de standaardlijst, maar elke rij blijft in de database staan. Coaches met bewerkrechten kunnen een actief dossier archiveren (knop *Archiveren* in de actiekolom). Beheerders zetten de schakelaar *Gearchiveerd tonen* aan op de POP-lijst en klikken op *Herstellen* om het dossier terug te halen. Dit is het juiste antwoord wanneer een speler halverwege het seizoen vertrekt of de cyclus per ongeluk is geopend.
- **Definitief verwijderen** — onomkeerbare hard-delete. Alleen beschikbaar voor operators met de capability `tt_delete_pdp` (standaard alleen voor beheerders). Twee ingangen: de knop *PDP definitief verwijderen* op de detailpagina van het POP-dossier, **en** een actie *Permanent verwijderen* per rij bij gearchiveerde dossiers (zet de schakelaar *Gearchiveerd tonen* aan op de POP-lijst — operators met `tt_delete_pdp` zien deze schakelaar ook zonder herstelrechten). Beide openen dezelfde bevestigingspagina die:
 - een **cascade-samenvatting** toont — hoeveel gesprekken / eindoordelen / kalenderkoppelingen / POP-blokken / doel-koppelingen er verdwijnen.
 - vereist dat de operator de **naam van de speler** letterlijk overtypt voordat de knop *PDP definitief verwijderen* actief wordt (hoofdletterongevoelig, met tolerantie voor extra spaties).
 - een **CSV-momentopname vóór verwijdering** schrijft naar `wp-content/uploads/tt-pdp-deletes/pdp-<dossier-id>-<tijdstempel>.csv` voordat de cascade wordt uitgevoerd. Het absolute pad wordt vastgelegd in de audit-log-entry `pdp.deleted_with_cascade`, samen met de rijaantallen per tabel.
 - de cascade over vijf tabellen draait binnen één transactie. Elke fout draait alles terug; gedeeltelijke status na een fout is onmogelijk.

Gebruik standaard *Archiveren*. Grijp alleen naar *Definitief verwijderen* voor AVG-wisbeleid, ouderverzoeken of andere legitieme bewaartermijn-zaken. Het CSV-bestand is je audit trail — bewaar het.
