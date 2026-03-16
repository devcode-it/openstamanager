# OpenSTAManager – Codebase Analysis Progress
> File di stato per continuità tra sessioni di analisi

## Progetto
- **Path**: `C:\wamp64\www\openstamanager`
- **Analisi completata**: 2026-03-16

---

## ✅ ANALISI COMPLETA – Tutte e 3 le fasi completate

---

## Fasi completate

### ✅ Fase 1 – Discovery & Architecture
**Scoperte chiave**: architettura ibrida Laravel 12 + legacy PHP, ~70 moduli + ~30 plugin,
sistema custom/ per personalizzazioni, auth multi-provider, API Platform + Sanctum.

### ✅ Fase 2 – Component Analysis
**Scoperte chiave**: tre livelli di query DB coesistenti, pattern Dual Write per traduzioni,
HTMLBuilder DSL `{[]}` / `{()}`, AuthOSM con 5 metodi di autenticazione,
pattern "op" per azioni POST, pattern "dir" per documenti bidirezionali.

### ✅ Fase 3 – Documentation & Recommendations
**Prodotti**: guida onboarding, raccomandazioni prioritizzate (10 items),
guida completa al codebase con schema DB, pattern reference, helper sheet.

---

## File di analisi generati

```
analysis/
├── project-overview.md                           ✅ Fase 1
├── architecture-analysis.md                      ✅ Fase 1
├── code-patterns-identified.md                   ✅ Fase 2
├── comprehensive-codebase-guide.md               ✅ Fase 3
├── developer-onboarding-guide.md                 ✅ Fase 3
├── technical-recommendations.md                  ✅ Fase 3
├── component-deep-dives/
│   ├── database-layer.md                         ✅ Fase 2
│   ├── models-and-traits.md                      ✅ Fase 2
│   ├── htmlbuilder.md                            ✅ Fase 2
│   └── auth-system.md                            ✅ Fase 2
└── codebase-analysis-progress.md                 ✅ (questo file)
```

---

## Raccomandazioni tecniche – top 3

1. **Alta** – Standardizzare le query verso Eloquent/Fluent Builder (abbandonare
   il pattern `fetchArray('... WHERE id='.prepare($id))` nei nuovi file)
2. **Alta** – Estrarre la logica SQL da `init.php` nei Model Eloquent
   (per testabilità e riuso)
3. **Alta** – Aggiungere test unitari per i componenti core
   (Database::sync, AuthOSM::validateOTP, HTMLBuilder::decode, Update)

---

## Continuare l'analisi in una nuova chat

> "Continua l'analisi del codebase – leggi
> `C:\wamp64\www\openstamanager\analysis\codebase-analysis-progress.md`
> per sapere dove ci siamo fermati, poi prosegui con [argomento specifico]."

Argomenti utili per approfondimenti futuri:
- Analisi sicurezza (SQL injection, CSRF, XSS)
- Analisi performance (query N+1, cache)
- Analisi modulo fatturazione elettronica (XML FE, codice SDI)
- Documentazione API REST (endpoint disponibili)
- Analisi sistema di stampa PDF (templates/)
