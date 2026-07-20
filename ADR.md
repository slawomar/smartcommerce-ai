# ADR-001: Inertia.js zamiast oddzielnego SPA

## Status

Accepted

## Kontekst

SmartCommerce AI jest aplikacją e-commerce rozwijaną przez jednego
programistę jako projekt portfolio i potencjalny fundament produktu.

Backend powstaje w Laravelu, natomiast interfejs użytkownika w Vue 3
z TypeScriptem.

Rozważane rozwiązania:

1. oddzielne SPA Vue komunikujące się z Laravel REST API,
2. aplikacja Laravel z Vue poprzez Inertia.js.

## Decyzja

W pierwszej wersji wybieramy Laravel, Vue 3, TypeScript i Inertia.js
w ramach jednej aplikacji.

## Uzasadnienie

- jeden proces wdrożeniowy,
- wspólny routing i autoryzacja Laravela,
- brak konieczności tworzenia osobnego API dla każdej strony,
- łatwiejsza obsługa walidacji formularzy,
- szybsze dostarczenie kompletnej aplikacji,
- możliwość późniejszego wydzielenia REST API dla aplikacji mobilnej.

## Konsekwencje pozytywne

- mniejsza liczba warstw na początku projektu,
- prostsze uwierzytelnianie,
- jeden projekt Git,
- łatwiejsze wdrożenie na Plesku,
- nadal korzystamy z pełnych komponentów Vue.

## Konsekwencje negatywne

- frontend jest powiązany z aplikacją Laravel,
- aplikacja mobilna będzie wymagała osobnego API,
- część logiki prezentacyjnej będzie zależna od danych Inertia.

## Kryterium ponownego rozważenia

Decyzję ponownie ocenimy przed rozpoczęciem aplikacji mobilnej
lub udostępnieniem publicznego API partnerom.
