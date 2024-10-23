# "Simppeli" Kisakeskus järjestelmä

Tämä projekti on virtuaalinen "Simppeli" Kisakeskus järjestelmä, jossa osallistumiset lisätään luokkiin automaattisesti. Järjestelmä tallentaa ilmoittautumistiedot JSON-tiedostoihin, suojaa lomakkeen bottiliikenteeltä ja lähettää vahvistussähköposteja osallistujille jos käyttäjä niin haluaa.

## Ominaisuudet

- Osallistujat voivat ilmoittaa hevosia eri luokkiin tapahtuman päivämäärän ja sääntöjen mukaisesti.
- Järjestelmä käyttää Google reCAPTCHA -järjestelmää ja hunajapurkki-tekniikkaa estääkseen bottien roskapostin.
- Osallistujat saavat halutessaan ilmoittautumisen jälkeen sähköpostin, joka vahvistaa rekisteröinnin ja sisältää tapahtuman URL-osoitteen, päivämäärän ja osallistuneet hevoset luokittain.
- Tapahtumien tiedot, kuten säännöt ja luokat, tallennetaan JSON-tiedostoihin.
- Lomake tukee rekisteröitymistä useilla hevosilla ja luokilla tapahtuman sääntöjen mukaisesti.
- Vaatii PHP-tuen, muttei tietokantaa
- ...

## Suositeltu hakemistorakenne

```
competitions/
│
├── data/           # JSON files for each event
│   ├── koulu240410.json
│   ├── koulu250410.json
│   └── ...
│
├── backend/        # PHP server-side code
│   ├── save_participants.php
│   └── ...
│
├── index.html      # Lists all events (current and upcoming)
│
├── event.html      # Event page
```


## Asennus

1. **Lataa tai kloonaa** tämä repositorio paikalliselle palvelimelle:
    ```bash
    git clone https://github.com/Tilli-simgame/SG-EventCenter.git
    ```

2. **Asenna PHPMailer** (valinnainen, jos käytät PHPMaileria):
    ```bash
    composer require phpmailer/phpmailer
    ```

3. **Aseta Google reCAPTCHA**:
    - Mene osoitteeseen [Google reCAPTCHA](https://www.google.com/recaptcha/) ja rekisteröi sivustosi saadaksesi *Site Key* ja *Secret Key*.
    - Lisää *Site Key* HTML-lomakkeeseen ja *Secret Key* PHP-tiedostoon.

4. **Määritä SMTP** (jos käytät PHPMaileria):
    - Päivitä `backend/save_participants.php` PHPMailerin asetukset (SMTP-palvelin, käyttäjänimi ja salasana).

5. **Palvelimen konfigurointi**:
    - Varmista, että PHP-palvelin on oikein asennettu ja PHP-tiedostot käsitellään oikein.
    - Aseta hakemistojen oikeudet niin, että `data/`-hakemistossa olevia JSON-tiedostoja voidaan lukea ja päivittää.

## Miten järjestelmä toimii

### Osallistuminen

1. Osallistuja täyttää osallistumislomakkeen antamalla ratsastajan nimen, (sähköpostiosoitteen), hevoset ja luokat.
2. Kun lomake on täytetty, järjestelmä tarkistaa tiedot:
   - Ylittääkö hevosten tai ratsastajien määrä sallitut rajat luokassa.
   - Onko sama hevonen ilmoitettu useampaan luokkaan kuin säännöt sallivat.
3. Jos ilmoittautuminen onnistuu lisätään hevoset kuhunkin luokkaan.
4. Jos osallistuja on klikannut haluavansa kopion sähköpostiin lähetetään lopuksi varmistusmaili osallistumisesta.

### Botin havaitseminen

- **Google reCAPTCHA**: Lomake käyttää Google reCAPTCHA v2 -suojaa estääkseen automatisoituja botteja lähettämästä lomaketta.
- **Hunajapurkki-kenttä**: Lomakkeessa on piilotettu kenttä (honeypot), jonka useimmiten vain botit täyttävät, ja tällöin lomake hylätään.

## JSON-tiedostojen rakenne

Tapahtumakohtaisessa JSON-tiedostossa (esim. `data/koulu240410.json`) on seuraava rakenne:

```json
{
    "date": "2024-04-10",
    "lastDate": "2024-04-05",
    "classList": [
        "01. Classname",
        "02. Classname",
        "03. Classname"
    ],
    "rules": {
        "maxHorsesPerClass": 50,
        "maxClassesPerHorse": 2,
        "maxHorsesPerRiderInClass": 3
    },
    "classes": {
        "01. Classname": [],
        "02. Classname": [],
        "03. Classname": []
    }
}
```

Huomaa että `classlist` ja `classes` pitää olla täsmälleen samat luokkien nimet, muuten järjestelmä ei toimi oikein. Luokkien pitää myös olla uniikkeja nimiltään, koska muuten järjestelmä ei myöskään osaa laittaa osallistujia oikeisiin luokkiin. Suosittelen käyttämään tuota numerointia: `1.`/ `01.` / jne. yksilöivänä elementtinä.

- **date:** Tapahtuman päivämäärä.
- **lastDate:** Viimeinen ilmoittautumispäivä.
- **classList:** Luettelo tapahtuman luokista.
- **rules:** Tapahtuman säännöt, kuten maksimihevosten - määrä per luokka ja ratsastaja.
classes: Ilmoittautuneet osallistujat per luokka.