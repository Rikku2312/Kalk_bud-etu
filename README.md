# 💰 Kalk Budget — Aplikacja Budżetu Domowego

Profesjonalna aplikacja do zarządzania budżetem domowym z wykresami, analizami i celami oszczędnościowymi. Czarny motyw, nowoczesny design.

---

## 🌐 Link do aplikacji w przeglądarce

```
http://localhost/Cos123/Jonkilol/Kalk/Kalk_bud-etu/index.php
```

> **Wymagane:** XAMPP z uruchomionymi Apache i MySQL.

---

## ⚙️ Konfiguracja (pierwsze uruchomienie)

### 1. Importuj bazę danych

Otwórz **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

Kliknij **Importuj** → wybierz plik `setup.sql` → **Wykonaj**.

Lub przez konsolę XAMPP:
```bash
mysql -u root -p < setup.sql
```

### 2. Sprawdź połączenie z bazą

Otwórz plik `db.php` i upewnij się, że dane są poprawne:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // twój użytkownik MySQL
define('DB_PASS', '');       // twoje hasło MySQL
define('DB_NAME', 'kalk_budget');
```

### 3. Uruchom aplikację

Otwórz w przeglądarce:
```
http://localhost/Cos123/Jonkilol/Kalk/Kalk_bud-etu/index.php
```

---

## 📁 Struktura plików

```
Kalk_bud-etu/
├── index.php          — Dashboard (główna strona)
├── transactions.php   — Lista i zarządzanie transakcjami
├── budget.php         — Budżety miesięczne z limitami
├── categories.php     — Zarządzanie kategoriami
├── savings.php        — Cele oszczędnościowe
├── reports.php        — Raporty i wykresy analityczne
├── api.php            — REST API (backend)
├── db.php             — Połączenie z bazą danych
├── header.php         — Wspólny nagłówek / sidebar
├── footer.php         — Wspólny footer / JavaScript helpers
├── style.css          — Motyw ciemny (CSS)
├── setup.sql          — Skrypt tworzenia bazy danych
└── README.md          — Ten plik
```

---

## ✨ Funkcje aplikacji

### 📊 Dashboard
- Podsumowanie przychodów, wydatków i bilansu
- Nawigacja po miesiącach (poprzedni/następny)
- Wykres trendu 12 miesięcy (przychody vs wydatki)
- Wykres kołowy wydatków według kategorii
- Ostatnie transakcje
- Podgląd budżetów miesięcznych z paskami postępu

### 💳 Transakcje
- Dodawanie, edycja, usuwanie transakcji
- Filtrowanie: typ, kategoria, miesiąc, wyszukiwanie
- Paginacja (20 pozycji na stronę)
- Podsumowanie filtrowanych wyników (przychody/wydatki/bilans)
- **Eksport do CSV**

### 🎯 Budżety
- Ustawianie limitów miesięcznych dla kategorii
- Paski postępu z kolorami (zielony/żółty/czerwony)
- Wykres słupkowy: budżet vs wydatki
- Nawigacja po miesiącach

### 🏷️ Kategorie
- Kategorie przychodów i wydatków z osobnymi zakładkami
- Własna ikona (emoji) i kolor dla każdej kategorii
- Dodawanie, edycja, usuwanie

### 💰 Cele Oszczędnościowe
- Tworzenie celów z kwotą docelową, terminem i ikoną
- Wpłacanie środków na cel (przycisk "Wpłać")
- Automatyczne wykrywanie ukończonych celów (🎉)
- Śledzenie terminów (ile dni zostało)
- Wykres postępu wszystkich celów

### 📈 Raporty
- **6 wskaźników KPI** (przychody, wydatki, bilans, liczba transakcji, średni wydatek/dzień, największy wydatek)
- **Wykres trendu** miesięcznego
- **Wykres kołowy** struktury wydatków
- **Wykres słupkowy** kategorii (top 10)
- **Wykres liniowy** bilansu narastającego
- Top 5 największych wydatków
- Zestawienie tabelaryczne z % udziałem kategorii
- **Eksport do CSV**
- Szybki wybór zakresu: ten miesiąc, poprzedni, ten rok, ostatnie 3/6 miesięcy

---

## 🛠️ Technologie

| Technologia | Zastosowanie |
|-------------|-------------|
| PHP 8+      | Backend, REST API |
| MySQL       | Baza danych |
| HTML5 / CSS3 | Frontend, ciemny motyw |
| JavaScript (Vanilla) | Interaktywność, AJAX |
| Chart.js 4  | Wykresy i grafy |
| Google Fonts (Inter) | Typografia |

---

## 🎨 Motyw

Aplikacja używa **ciemnego motywu** (`#0a0a0f` tło) z białym tekstem, akcentami indygo (`#6366f1`) i kolorowymi wskaźnikami:
- 🟢 Zielony — przychody, cele zrealizowane
- 🔴 Czerwony — wydatki, przekroczony budżet
- 🟡 Żółty — ostrzeżenie (75–100% budżetu)
- 🔵 Fioletowy — akcent, aktywne elementy

---

## 📝 Licencja

Projekt edukacyjny / prywatny. Wszelkie prawa zastrzeżone.
