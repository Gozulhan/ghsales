# GHSales Plugin Handleiding voor Beginners

Complete Nederlandse handleiding voor het gebruik van de GHSales plugin.

---

## Inhoudsopgave

1. [Wat doet deze plugin?](#wat-doet-deze-plugin)
2. [Een Sale Event aanmaken](#een-sale-event-aanmaken)
3. [Sale Regels toevoegen](#sale-regels-toevoegen)
4. [BOGO (1+1 Gratis) acties](#bogo-acties)
5. [Upsell Aanbevelingen tonen](#upsell-aanbevelingen-tonen)
6. [Veelgestelde vragen](#veelgestelde-vragen)

---

## Wat doet deze plugin?

GHSales is een krachtige WordPress plugin die je helpt om meer omzet te genereren in je WooCommerce webshop door:

### 🎯 **1. Sale Events & Kortingen**
- Maak tijdelijke kortingsacties (bijv. "Zomer Sale", "Black Friday")
- Zet automatisch kortingen op producten
- Toon mooie badges op producten (bijv. "-25%" of "SALE")
- Plan van tevoren wanneer de sale start en eindigt

### 🎁 **2. BOGO Acties (Buy One Get One)**
- 1+1 gratis acties
- 2e product halve prijs
- Koop 3 betaal 2
- En nog veel meer combinaties!

### 💡 **3. Slimme Product Aanbevelingen**
- Toont automatisch relevante producten aan klanten
- "Vaak samen gekocht" suggesties
- Gepersonaliseerde aanbevelingen op basis van browsing gedrag
- Verhoogt gemiddelde bestelbedrag met 25-40%

### 🎨 **4. Kleurenschema's (binnenkort)**
- Verander de kleuren van je hele website tijdens een sale
- Geef je site een "feestelijk" uiterlijk tijdens acties

---

## Een Sale Event aanmaken

### Stap 1: Naar het Sale Event scherm

1. Log in op je WordPress admin dashboard
2. Klik in het menu links op **"Sale Events"**
3. Klik bovenaan op **"Nieuw toevoegen"**

---

### Stap 2: Basis informatie invullen

#### **Titel**
- Geef je sale een herkenbare naam
- Voorbeelden: "Zomer Sale 2025", "Black Friday", "Uitverkoop Winter"
- **Let op:** Deze naam is alleen voor intern gebruik, klanten zien dit niet

---

### Stap 3: Event Settings (Gebeurtenis Instellingen)

#### **Allow stacking with other events** (Stapelen toestaan)
- ✅ **Aangevinkt:** Deze sale kan gecombineerd worden met andere actieve sales
- ❌ **Uitgevinkt:** Alleen deze sale wordt toegepast, andere sales worden genegeerd
- **Voorbeeld:** Als je een algemene "10% korting" sale hebt EN een "Black Friday 20%" sale, en stacking is aan, krijgt de klant mogelijk beide kortingen!

#### **Apply on WooCommerce sale price** (Toepassen op WooCommerce verkoopprijs)
- ✅ **Aangevinkt:** De korting wordt berekend over de WooCommerce sale prijs
- ❌ **Uitgevinkt:** De korting wordt berekend over de normale prijs
- **Voorbeeld:**
  - Product normale prijs: €100
  - WooCommerce sale prijs: €80
  - Jouw sale korting: 10%
  - **Met vinkje:** 10% van €80 = €72 eindprijs
  - **Zonder vinkje:** 10% van €100 = €90 eindprijs

#### **Badge Display** (Badge Weergave)
Kies hoe de kortingspercentage wordt getoond op productafbeeldingen:

- **Percentage (e.g., -25%)** - Toont "-25%" op de badge
- **Fixed Amount** - Toont "€10 korting" op de badge
- **"SALE" text** - Toont alleen het woord "SALE"
- **Custom text** - Jouw eigen tekst (bijv. "ACTIE!")

**Kies "Percentage"** als je wilt dat klanten direct zien hoeveel procent korting ze krijgen.

#### **Color Scheme (optional)** (Kleurenschema optioneel)
- Momenteel nog niet actief (komt binnenkort!)
- Straks kun je hier een kleurenschema kiezen dat je hele website verandert tijdens de sale

---

### Stap 4: Event Details (Gebeurtenis Details)

#### **Start Date & Time** (Startdatum en tijd)
- Klik op het datumveld
- Kies de datum waarop de sale **start**
- Kies het tijdstip (bijv. 00:00 voor middernacht)
- **Belangrijk:** De sale wordt automatisch actief op dit moment!

#### **End Date & Time** (Einddatum en tijd)
- Klik op het datumveld
- Kies de datum waarop de sale **eindigt**
- Kies het tijdstip
- **Belangrijk:** De sale stopt automatisch op dit moment!

**Voorbeeld planning:**
- Start: 24/12/2025 om 00:00 (begint op kerstavond om middernacht)
- Eind: 27/12/2025 om 23:59 (eindigt op tweede kerstdag net voor middernacht)

#### **Description** (Omschrijving)
- Interne notitie voor jezelf
- Klanten zien dit **NIET**
- Gebruik dit om te onthouden waarom je deze sale maakte
- Voorbeeld: "Kerstactie om overtollige voorraad op te ruimen"

---

### Stap 5: Sale Rules toevoegen (DE BELANGRIJKSTE STAP!)

Dit is waar je bepaalt **hoeveel korting** klanten krijgen en **op welke producten**.

#### **Klik op "+ Add Sale Rule"**

Er verschijnt nu een nieuwe regel box met de volgende opties:

---

#### **Discount Type** (Kortingstype)

Kies het type korting:

1. **Percentage Off** (Percentage korting)
   - Geef X% korting op producten
   - Voorbeeld: 20% korting = product van €100 wordt €80

2. **Fixed Amount Off** (Vast bedrag korting)
   - Trek een vast bedrag af van de prijs
   - Voorbeeld: €10 korting = product van €100 wordt €90

3. **Fixed Price** (Vaste prijs)
   - Zet product op een specifieke prijs
   - Voorbeeld: Alle producten voor €50 (ongeacht normale prijs)

**Voor beginners: kies "Percentage Off"** - dit is het meest gebruikelijk.

---

#### **Applies To** (Waar de korting op van toepassing is)

Kies welke producten deze korting krijgen:

1. **All Products** (Alle producten)
   - De korting geldt voor ALLE producten in je winkel
   - Gebruik dit voor algemene sales zoals "20% korting op alles!"

2. **Specific Products** (Specifieke producten)
   - Selecteer handmatig welke producten korting krijgen
   - Er verschijnt een zoekbalk waar je producten kunt selecteren
   - Gebruik dit voor gerichte acties (bijv. alleen winterjassen in de sale)

3. **Product Categories** (Productcategorieën)
   - Kies categorieën die korting krijgen
   - Voorbeeld: Categorie "Schoenen" krijgt 30% korting
   - Alle producten in die categorie krijgen automatisch korting

4. **Product Tags** (Product tags/labels)
   - Kies producten op basis van tags
   - Voorbeeld: Alle producten met tag "uitverkoop" krijgen korting

**Voor beginners: kies "All Products" voor een winkelwijde sale.**

---

#### **Discount Percentage** (Kortingspercentage)

*Dit veld verschijnt alleen als je "Percentage Off" hebt gekozen.*

- Vul hier het percentage in
- Gebruik alleen het **getal** (geen %-teken!)
- Voorbeelden:
  - Vul **20** in voor 20% korting
  - Vul **50** in voor 50% korting
  - Vul **10** in voor 10% korting

**Let op:** Gebruik een punt (.) voor decimalen, niet een komma!
- Correct: **12.5** voor 12,5% korting
- Fout: ~~12,5~~

---

#### **Priority** (Prioriteit)

- Geef een nummer tussen 0 en 100
- **Hoger nummer = hogere prioriteit**
- Wordt alleen gebruikt als er meerdere sale regels zijn die op hetzelfde product van toepassing zijn

**Hoe werkt dit?**

Stel je hebt:
- Regel 1: "20% korting op alle producten" - Priority: **10**
- Regel 2: "30% korting op categorie Schoenen" - Priority: **20**

Een product in categorie "Schoenen" krijgt nu **30% korting** (want priority 20 is hoger dan 10).

**Voor beginners:** Laat dit op **0** staan als je maar één regel hebt.

---

#### **Max Quantity Per Customer (Optional)** (Max aantal per klant - Optioneel)

- Beperk hoeveel stuks een klant met korting kan kopen
- Laat **leeg** voor onbeperkt
- Vul een **getal** in om te beperken

**Voorbeelden:**

- **Leeg laten:** Klanten kunnen onbeperkt producten met korting kopen
- **1:** Elke klant mag max 1 product met korting kopen
- **5:** Elke klant mag max 5 producten met korting kopen

**Hoe werkt de tracking?**
- Ingelogde klanten: Wordt bijgehouden op hun email adres
- Gasten: Wordt bijgehouden in hun browsersessie

**Gebruik dit voor:**
- Zeer hoge kortingen die je wilt beperken
- "Verliesleider" producten (producten die je bijna weggeefd)
- Black Friday "doorbuster" deals

---

### Stap 6: Meerdere regels toevoegen (Optioneel)

Je kunt meerdere regels maken voor één sale event!

**Voorbeeld strategie:**

**Zomer Sale Event met 3 regels:**

**Regel 1:**
- Discount Type: Percentage Off
- Applies To: All Products
- Percentage: 10
- Priority: 5
- *(Standaard 10% op alles)*

**Regel 2:**
- Discount Type: Percentage Off
- Applies To: Product Categories → "Badmode"
- Percentage: 25
- Priority: 10
- *(Extra korting op badmode)*

**Regel 3:**
- Discount Type: Fixed Price
- Applies To: Specific Products → "Opruiming T-shirts"
- Fixed Price: 5 (euro)
- Priority: 15
- Max Quantity: 2
- *(Uitverkoop T-shirts voor €5, max 2 per klant)*

**Resultaat:**
- Normale producten: 10% korting
- Badmode: 25% korting (want priority 10 > priority 5)
- Specifieke T-shirts: €5 vaste prijs (want priority 15 is hoogste)

Klik op **"+ Add Sale Rule"** om nog een regel toe te voegen.

Klik op **"Remove"** om een regel te verwijderen.

---

### Stap 7: Publiceren

#### **Optie 1: Direct publiceren**

1. Controleer of alle instellingen correct zijn
2. Klik rechtsboven op de blauwe knop **"Publiceren"**
3. Je sale is nu LIVE! (als de startdatum al bereikt is)

#### **Optie 2: Opslaan als concept**

1. Klik rechtsboven op **"Opslaan als concept"**
2. De sale wordt opgeslagen maar is nog **niet actief**
3. Je kunt later terug om het af te maken

**Concept later publiceren:**

1. Ga naar **Sale Events** in het menu
2. Hover met je muis over het sale event
3. Klik op **"Snel bewerken"** (Quick Edit)
4. Verander "Status" van **"Concept"** naar **"Gepubliceerd"**
5. Klik op **"Bijwerken"**

---

### Stap 8: Controleren of het werkt

1. Ga naar je webshop (frontend)
2. Bekijk de producten die in de sale zitten
3. Je zou moeten zien:
   - Een **badge** op de productafbeelding (bijv. "-20%")
   - De **doorgestreepte** normale prijs
   - De nieuwe **sale prijs** in rood of een accentkleur

4. Voeg een product toe aan je winkelwagen
5. Controleer of de korting correct is toegepast

**Niet zichtbaar?**
- Controleer of de startdatum al bereikt is
- Controleer of de status op "Gepubliceerd" staat
- Leeg je browser cache (Ctrl+F5)

---

## BOGO Acties

BOGO = **Buy One Get One** (Koop één, krijg één)

### Wat zijn BOGO acties?

Populaire actie-types zoals:
- 🎁 **1+1 gratis** - Koop 1 product, krijg 2e gratis
- 💰 **2e halve prijs** - Koop 2 producten, 2e product 50% korting
- 🎉 **3 halen, 2 betalen** - Koop 3 producten, goedkoopste gratis
- 🏷️ **Koop €50, krijg €10 korting** - Bij minimaal bestelbedrag

### BOGO Event aanmaken

1. Klik in het menu op **"BOGO Events"**
2. Klik op **"Nieuw toevoegen"**
3. Geef een titel (bijv. "1+1 Gratis T-shirts")

**Velden invullen:**

#### **Offer Type** (Type aanbieding)
Kies het type BOGO:

- **Buy X Get Y** - Koop X producten, krijg Y producten korting
  - Voorbeeld: Koop 2, krijg 1 gratis = Buy 2 Get 1

- **Spend X Get Y** - Besteed €X, krijg korting
  - Voorbeeld: Besteed €50, krijg €10 korting

#### **Buy Quantity** (Koop aantal)
Hoeveel producten moet de klant kopen?
- Voorbeeld voor "1+1 gratis": vul **1** in

#### **Get Quantity** (Krijg aantal)
Hoeveel producten krijgen korting?
- Voorbeeld voor "1+1 gratis": vul **1** in

#### **Get Discount Type** (Type korting op gratis product)
Wat voor korting krijgt het "gratis" product?

- **Percentage Off** - Percentage korting (gebruik 100 voor helemaal gratis!)
- **Fixed Amount Off** - Vast bedrag korting
- **Free** - Helemaal gratis

Voor **1+1 gratis**: Kies "Free" of "Percentage Off" met 100%.

#### **Applies To** (Van toepassing op)
Welke producten doen mee?

- **All Products** - Alle producten
- **Specific Products** - Handmatig geselecteerde producten
- **Product Categories** - Hele categorieën
- **Product Tags** - Op basis van tags

#### **Priority**
Zelfde als bij Sale Events - hoger nummer = hogere prioriteit

#### **Max Uses Per Customer**
Hoeveel keer mag dezelfde klant van deze BOGO profiteren?
- Leeg = onbeperkt
- 1 = één keer per klant
- 5 = vijf keer per klant

**Voorbeeld configuratie "1+1 Gratis":**

```
Titel: 1+1 Gratis op alle T-shirts
Offer Type: Buy X Get Y
Buy Quantity: 1
Get Quantity: 1
Get Discount Type: Free
Applies To: Product Categories → "T-shirts"
Priority: 10
Max Uses Per Customer: (leeg laten voor onbeperkt)
Start Date: 01/06/2025 00:00
End Date: 30/06/2025 23:59
```

**Hoe het werkt voor de klant:**
1. Klant legt 1 T-shirt in winkelwagen = normale prijs
2. Klant legt 2e T-shirt in winkelwagen = 2e is GRATIS!
3. Klant legt 3e T-shirt in winkelwagen = normale prijs
4. Klant legt 4e T-shirt in winkelwagen = 4e is GRATIS!
5. Etc.

---

## Upsell Aanbevelingen tonen

Upsells zijn **slimme productaanbevelingen** die klanten verleiden om meer te kopen.

### Wat zijn upsells?

Denk aan:
- "Vaak samen gekocht met dit product"
- "Klanten die dit kochten, kochten ook..."
- "Aanbevolen voor jou"
- "Vergeet deze niet!"

**De plugin analyseert automatisch:**
- Welke producten vaak samen gekocht worden
- Wat de klant eerder heeft bekeken
- Wat populair is in je winkel
- Welke prijzen goed bij elkaar passen

### Methode 1: Via Elementor Widget (AANBEVOLEN)

Dit is de **makkelijkste manier** en geeft het mooiste resultaat.

#### Stap 1: Ga naar de homepage

1. Ga naar **Pagina's** in WordPress menu
2. Klik op je **Homepage**
3. Klik op **"Bewerken met Elementor"** (blauwe knop bovenaan)

Elementor opent nu in een nieuw scherm.

---

#### Stap 2: Voeg de Product Widget toe

1. Klik in de linker sidebar op het **"+"** icoon (Widget toevoegen)
2. Zoek naar **"GulcaN WooCommerce Products"** in de zoekbalk
3. **Sleep** de widget naar de gewenste plek op je pagina
   - Bijv. onder de hero section
   - Bijv. boven de footer
   - Bijv. in een sidebar

---

#### Stap 3: Configureer de widget

De widget settings openen aan de linkerkant. Hier configureer je de upsells:

#### **Product Type** (Producttype)
Dit is de **belangrijkste** instelling!

Klik op het dropdown menu en kies:
- **"GHSales Recommendations"** ← KIES DEZE!

Andere opties (negeer deze voorlopig):
- ~~Latest Products~~ - Nieuwste producten
- ~~Best Sellers~~ - Bestsellers
- ~~Sale Products~~ - Producten in de sale
- ~~Selected Products~~ - Handmatig gekozen producten

**Door "GHSales Recommendations" te kiezen, krijg je:**
- ✅ Automatisch de juiste producten op de juiste plek
- ✅ Op homepage: gepersonaliseerde aanbevelingen
- ✅ Op productpagina: "vaak samen gekocht"
- ✅ Op winkelwagenpagina: aanvullende producten

**De plugin detecteert automatisch waar de widget staat en past de aanbevelingen aan!**

---

#### **Limit** (Aantal producten)
Hoeveel producten wil je tonen?

- **4** = Toont 4 producten (standaard, goed voor homepage)
- **6** = Toont 6 producten
- **8** = Toont 8 producten (goed voor brede secties)
- **12** = Toont 12 producten (veel, alleen voor grote secties)

**Aanbeveling:** Start met **6 of 8** producten.

---

#### **Columns** (Kolommen)
In hoeveel kolommen moeten de producten getoond worden?

- **2** = 2 kolommen (goed voor smalle sidebars)
- **3** = 3 kolommen (mooi gebalanceerd)
- **4** = 4 kolommen (standaard, goed voor desktop)
- **5** = 5 kolommen (alleen voor zeer brede pagina's)
- **6** = 6 kolommen (alleen voor hele brede pagina's)

**Let op:** Op mobiele telefoons worden de producten automatisch in 2 kolommen getoond, ongeacht deze instelling!

**Aanbeveling:** Kies **4 kolommen** voor desktop.

---

#### **Section Title** (Sectie titel)
De koptekst boven de producten.

**Suggesties:**
- "Aanbevolen voor jou"
- "Klanten kochten ook"
- "Vergeet deze niet"
- "Populaire producten"
- "Jouw persoonlijke selectie"
- "Misschien vind je dit ook leuk"

**Laat leeg** als je geen titel wilt.

---

#### **Andere instellingen**

De widget heeft nog meer instellingen (kleuren, spacing, etc.) maar die zijn optioneel. De standaardinstellingen werken prima!

---

#### Stap 4: Opslaan en bekijken

1. Klik linksonder op **"Bijwerken"** (groene knop)
2. Klik op het **oog-icoon** (Preview) om te zien hoe het eruit ziet
3. Klik op **"Afsluiten"** (kruisje linksboven) als je klaar bent

---

### Methode 2: Via Shortcode (Gevorderd)

Als je geen Elementor gebruikt, kun je een shortcode gebruiken.

#### Shortcode voor overal

Plak deze code in een pagina of post:

```
[gulcan_wc_products type="ghsales_recommendations" limit="8"]
```

**Parameters aanpassen:**

```
[gulcan_wc_products type="ghsales_recommendations" limit="6" columns="3"]
```

- `type="ghsales_recommendations"` - Gebruik GHSales aanbevelingen (VERPLICHT!)
- `limit="6"` - Toon 6 producten
- `columns="3"` - Verdeel over 3 kolommen

---

### Waar toon je upsells? (Locatie tips)

#### **Homepage**
- Onder de hero/banner sectie
- Boven de footer
- Tussen content secties
- **Titel suggestie:** "Aanbevolen voor jou" of "Populaire producten"

#### **Productpagina**
- Onder de product beschrijving
- In de sidebar
- Boven de footer
- **Titel suggestie:** "Vaak samen gekocht" of "Dit past er goed bij"

#### **Winkelwagenpagina**
- Boven de checkout knop
- In de sidebar
- **Titel suggestie:** "Vergeet deze niet" of "Maak je bestelling compleet"

#### **Blog/Nieuws pagina's**
- Aan het einde van een artikel
- In de sidebar
- **Titel suggestie:** "Gerelateerde producten"

---

### Hoe werken de slimme aanbevelingen?

De plugin gebruikt een **intelligent scoresysteem**:

#### **Op de Homepage:**
- Kijkt naar wat de bezoeker eerder heeft bekeken
- Toont populaire en trending producten
- Als nieuwe bezoeker: toont bestsellers

#### **Op een Productpagina:**
- Analyseert welke producten vaak **samen gekocht** worden
- Zoekt producten in **dezelfde categorie**
- Gebruikt **prijspsychologie** (producten 25-50% van de prijs van het bekeken product)

#### **Op de Winkelwagenpagina:**
- Kijkt naar wat al in de winkelwagen zit
- Stelt **aanvullende producten** voor
- Zoekt **cross-sell mogelijkheden** (bijv. batterijen bij een speelgoed)

**Alles gebeurt automatisch - jij hoeft niets te configureren!**

---

### Voorbeeld configuratie: Complete homepage

**Homepage sectie indeling:**

1. **Hero Banner** (jouw hoofdafbeelding/slider)

2. **Upsell Sectie 1**
   - Widget: GulcaN WooCommerce Products
   - Type: GHSales Recommendations
   - Limit: 8
   - Columns: 4
   - Titel: "Aanbevolen voor jou"

3. **Uitgelichte Categorie** (bijv. "Nieuwe collectie")

4. **Upsell Sectie 2**
   - Widget: GulcaN WooCommerce Products
   - Type: GHSales Recommendations
   - Limit: 4
   - Columns: 4
   - Titel: "Trending producten"

5. **Footer**

**Resultaat:** Bezoekers zien twee sets met slimme aanbevelingen verspreid over de homepage!

---

## Veelgestelde vragen

### Sale Events

**Q: Mijn sale is niet zichtbaar op de website, wat nu?**

A: Controleer het volgende:
1. Is de status op "Gepubliceerd"? (niet Concept)
2. Is de startdatum al bereikt?
3. Is de einddatum nog niet verstreken?
4. Leeg je browser cache (Ctrl+F5 of Cmd+Shift+R op Mac)
5. Heb je minimaal één Sale Regel toegevoegd?

---

**Q: Kan ik een sale plannen voor de toekomst?**

A: Ja! Vul gewoon een startdatum in de toekomst in. De sale wordt automatisch actief op die datum en tijd.

---

**Q: Kan ik meerdere sales tegelijk actief hebben?**

A: Ja! Je kunt meerdere sale events tegelijk lopen. Gebruik het "Priority" veld om te bepalen welke sale voorrang krijgt als ze overlappen.

---

**Q: Hoe stop ik een sale eerder dan gepland?**

A:
1. Ga naar Sale Events
2. Klik op de sale
3. Verander de status naar "Concept"
4. Of verander de "End Date" naar vandaag

---

**Q: Worden sale prijzen automatisch getoond in de winkelwagen?**

A: Ja! De korting wordt automatisch toegepast en zichtbaar in de winkelwagen en bij checkout.

---

### BOGO Acties

**Q: Kan ik 1+1 gratis en 2e halve prijs tegelijk aanbieden?**

A: Ja, maar maak dan twee aparte BOGO events. Gebruik het "Priority" veld om te bepalen welke voorrang krijgt bij overlapping.

---

**Q: Hoe werkt BOGO met verschillende producten?**

A:
- Als "Applies To" op "All Products" staat, mogen klanten elke combinatie maken
- Als "Applies To" op specifieke producten staat, geld het alleen voor die producten
- Het goedkoopste product krijgt automatisch de korting/gratis

---

**Q: Ziet de klant hoeveel hij bespaart?**

A: Ja! In de winkelwagen staat duidelijk hoeveel korting hij krijgt door de BOGO actie.

---

### Upsells

**Q: Moet ik de upsell producten zelf selecteren?**

A: Nee! De plugin doet dit **automatisch** op basis van:
- Aankoopgeschiedenis (welke producten vaak samen gekocht worden)
- Browsing gedrag van de klant
- Populariteit van producten
- Prijspsychologie

---

**Q: Waarom zie ik op elke pagina andere upsells?**

A: Dat is normaal! De upsells zijn **gepersonaliseerd**:
- Op de homepage: gebaseerd op wat de bezoeker eerder bekeek
- Op een productpagina: producten die goed bij dat specifieke product passen
- Op winkelwagen: producten die bij de winkelwagen inhoud passen

---

**Q: Tonen upsells ook producten die uitverkocht zijn?**

A: Nee, de plugin toont alleen producten die:
- Op voorraad zijn
- Gepubliceerd zijn
- Zichtbaar zijn in je catalogus

---

**Q: Hoeveel upsells moet ik tonen?**

A: Aanbeveling:
- **Homepage:** 6-8 producten
- **Productpagina:** 4-6 producten
- **Winkelwagen:** 4 producten
- **Sidebar:** 2-3 producten

Teveel producten kan overweldigend zijn!

---

**Q: Kan ik de styling van upsells aanpassen?**

A: Ja! De upsells gebruiken automatisch de styling van je thema. Als je het verder wilt aanpassen, kan dat via Custom CSS in je thema.

---

**Q: Werken upsells ook zonder Elementor?**

A: Ja! Je kunt ook de shortcode gebruiken:
```
[gulcan_wc_products type="ghsales_recommendations" limit="6"]
```

---

### Algemeen

**Q: Moet ik iets instellen voor GDPR/privacy?**

A: Nee, de plugin gebruikt een externe cookie consent plugin die je al hebt geïnstalleerd (zoals Cookiebot). De tracking gebeurt automatisch volgens de toestemming die je cookie plugin regelt.

---

**Q: Hoe zie ik hoe goed mijn sales en upsells presteren?**

A: Een analytics dashboard komt binnenkort in een update! Voorlopig kun je WooCommerce rapporten gebruiken.

---

**Q: Kan ik de plugin in meerdere talen gebruiken?**

A: De admin interface is voorlopig in het Engels/Nederlands. Meertalige support komt in een toekomstige update.

---

**Q: Wat als ik een bug vind of een vraag heb?**

A: Neem contact op met support via je gebruikelijke kanaal. Geef altijd deze info mee:
- WordPress versie
- WooCommerce versie
- Thema dat je gebruikt
- Welk probleem je tegenkomt
- Screenshots (als relevant)

---

## Snelle Checklist voor Beginners

### Je eerste Sale Event
- [ ] Ga naar Sale Events → Nieuw toevoegen
- [ ] Vul een titel in (bijv. "Zomer Sale")
- [ ] Kies startdatum en einddatum
- [ ] Klik op "+ Add Sale Rule"
- [ ] Kies "Percentage Off" en "All Products"
- [ ] Vul kortingspercentage in (bijv. 20)
- [ ] Klik op "Publiceren"
- [ ] Controleer op je website of de badges zichtbaar zijn

### Je eerste Upsell sectie
- [ ] Ga naar je Homepage
- [ ] Klik op "Bewerken met Elementor"
- [ ] Voeg "GulcaN WooCommerce Products" widget toe
- [ ] Kies bij Product Type: "GHSales Recommendations"
- [ ] Zet Limit op 8 en Columns op 4
- [ ] Vul een mooie titel in (bijv. "Aanbevolen voor jou")
- [ ] Klik op "Bijwerken"
- [ ] Bekijk je homepage - de upsells zijn zichtbaar!

---

## Extra Tips voor Succes

### Sale Strategy Tips

1. **Combineer sales met upsells**
   - Zet producten in de sale
   - Upsells tonen dan gerelateerde producten (niet in de sale)
   - Klanten kopen vaak beide!

2. **Gebruik urgentie**
   - Maak sales met korte looptijd (3-7 dagen)
   - Klanten maken sneller een beslissing

3. **Test verschillende kortingspercentages**
   - Begin met 15-20% voor algemene sales
   - Gebruik 30-50% voor uitverkoop
   - Gebruik 60-75% alleen voor echte uitverkoop

4. **Stapel niet te veel sales**
   - Teveel tegelijk = verwarrend voor klanten
   - 1-2 grote sales per maand is genoeg

### Upsell Tips

1. **Plaats upsells strategisch**
   - Homepage: algemene aanbevelingen
   - Productpagina: gerelateerde producten
   - Winkelwagen: last-minute toevoegingen

2. **Gebruik pakkende titels**
   - ❌ "Producten" (saai!)
   - ✅ "Klanten kochten ook" (sociaal bewijs)
   - ✅ "Vergeet deze niet!" (urgentie)
   - ✅ "Maak je outfit compleet" (relevant)

3. **Monitor wat werkt**
   - Kijk in WooCommerce Analytics welke producten vaak samen gekocht worden
   - De plugin leert automatisch van deze data!

---

## Nog vragen?

Deze handleiding wordt regelmatig bijgewerkt. Check altijd de nieuwste versie!

**Laatst bijgewerkt:** 18 januari 2025
**Plugin versie:** 1.0.0

---

**Veel succes met je sales en upsells! 🚀💰**

