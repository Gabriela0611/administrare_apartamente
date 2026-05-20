# Administrare Apartamente

Aplicație web pentru administrarea unui portofoliu de apartamente. Permite gestionarea chiriașilor, contractelor, facturilor și cererilor de mentenanță, cu roluri distincte pentru administrator, proprietar și chiriaș.

## Tehnologii

- **Backend:** PHP 8.2 (mysqli, sesiuni)
- **Baza de date:** MySQL (auto-create la prima rulare)
- **Frontend:** HTML5 + CSS custom
- **Server local:** XAMPP

## Instalare și rulare

### Cerințe

- XAMPP cu Apache și MySQL pornite

### Pași

1. Copiază folderul `administrare_apartamente` în `C:\xampp\htdocs\`
2. Pornește Apache și MySQL din XAMPP Control Panel
3. Deschide în browser: `http://localhost/administrare_apartamente/login.php`

Baza de date și tabelele se creează automat la prima accesare. Contul de admin implicit este creat tot automat.

## Conturi implicite

| Rol | Email | Parolă |
|-----|-------|--------|
| Admin | `admin@test.com` | `admin123` |
| Proprietar | `proprietar@test.com` | `proprietar123` |
| Chiriaș | `chirias@test.com` | `chirias123` |

> Conturile de proprietar și chiriaș sunt create doar dacă rulezi `seed_test_data.php`.

## Date de test

Accesează `http://localhost/administrare_apartamente/seed_test_data.php` pentru a popula baza de date cu:
- 5 apartamente (3 ocupate, 2 libere)
- 3 chiriași cu contracte
- 8 facturi (unele plătite, altele restante)
- 4 cereri de mentenanță

## Structura aplicației

```
administrare_apartamente/
├── config/
│   └── db.php               # Conexiune MySQL + creare tabele
├── laboratoare/             # Exerciții HTML (week1–week9)
├── sessions/                # Fișiere de sesiune PHP
├── login.php                # Autentificare
├── register.php             # Înregistrare cont nou
├── logout.php               # Deconectare
├── auth.php                 # Verificare sesiune și roluri
├── dashboard.php            # Pagina principală cu statistici
├── menu.php                 # Navigație laterală
├── index.php                # Redirect la dashboard
├── adauga_apartament.php    # Adaugă apartament
├── sterge_apartament.php    # Șterge apartament
├── chiriasi.php             # Listă chiriași
├── adauga_chirias.php       # Adaugă chiriaș
├── sterge_chirias.php       # Șterge chiriaș
├── contracte.php            # Listă contracte
├── contract_chirias.php     # Detalii contract
├── facturi.php              # Listă facturi
├── adauga_factura.php       # Adaugă factură
├── sterge_factura.php       # Șterge factură
├── schimba_status_factura.php     # Marchează factură ca plătită
├── mentenanta.php           # Cereri de mentenanță
├── adauga_mentenanta.php    # Adaugă cerere
├── sterge_mentenanta.php    # Șterge cerere
├── schimba_status_mentenanta.php  # Actualizează status cerere
├── plati.php                # Istoric plăți
├── rapoarte.php             # Rapoarte financiare
├── documente.php            # Documente chiriași
├── utilizatori.php          # Administrare conturi
├── flash.php                # Setare mesaje flash
├── flash_messages.php       # Afișare mesaje flash
├── style.css                # Stiluri globale
└── seed_test_data.php       # Populare bază de date cu date de test
```

## Roluri și permisiuni

| Funcționalitate | Admin | Proprietar | Chiriaș |
|----------------|-------|------------|---------|
| Toate apartamentele | ✓ | ✓ | — |
| Toți chiriașii | ✓ | ✓ | — |
| Toate facturile | ✓ | ✓ | propriile |
| Toate cererile mentenanță | ✓ | ✓ | propriile |
| Administrare utilizatori | ✓ | — | — |

## Schema bazei de date

| Tabel | Descriere |
|-------|-----------|
| `users` | Conturi utilizatori (email, parolă hash, rol) |
| `apartamente` | Apartamente (adresă, camere, suprafață, chirie, status) |
| `chiriasi` | Chiriași cu date contract și documente |
| `facturi` | Facturi per apartament (tip, sumă, scadență, status) |
| `cereri_mentenanta` | Cereri de reparații (problemă, prioritate, status) |

