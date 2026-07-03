# ROADMAP DE PROMPTS — Sistema Shinhua Transportes
# Orden de ejecución para el agente IA

---

## Cómo usar este roadmap

Ejecuta los prompts en orden. Cada uno tiene prerequisitos del anterior.
Adjunta siempre los SKILLs indicados antes de pegar el prompt.

---

## PROMPTS DISPONIBLES

### ✅ PROMPT-01-migrations-seeders.md
**SKILLs a adjuntar:** `SKILL.md` + `SKILL-database.md`
**Entrega:** 9 migraciones (6 editar + 3 crear) + 5 seeders
**Comando post-entrega:**
```bash
php artisan migrate:fresh --seed
```

---

### ✅ PROMPT-02-models-services.md
**SKILLs a adjuntar:** `SKILL.md` + `SKILL-database.md` + `SKILL-backend.md`
**Entrega:** 10 modelos Eloquent + 5 Services + AppServiceProvider
**Prerequisito:** Tarea 01 completada

---

### ✅ PROMPT-03-api-controllers.md
**SKILLs a adjuntar:** `SKILL.md` + `SKILL-backend.md`
**Entrega:** 6 controladores API + routes/api.php + 2 Resources
**Prerequisito:** Tarea 02 completada
**Prueba rápida:**
```bash
php artisan route:list --path=api
```

---

### ✅ PROMPT-04-dashboard-vue.md
**SKILLs a adjuntar:** `SKILL.md` + `SKILL-frontend.md`
**Entrega:** 4 páginas Vue + 2 componentes + 4 controladores web + routes/web.php
**Prerequisito:** Tarea 03 completada
**Comando post-entrega:**
```bash
npm run dev
```

---

## PRÓXIMOS PROMPTS (pendientes de generar)

### 🔲 PROMPT-05-sunat-greenter.md
**SKILLs:** `SKILL.md` + `SKILL-sunat.md`
**Entrega:** SunatGreenterService.php completo, configuración Greenter, flujo de regularización
**Prerequisito:** Tarea 02 (SunatGreenterService stub)

---

### 🔲 PROMPT-06-sync-job.md
**SKILLs:** `SKILL.md` + `SKILL-backend.md`
**Entrega:** SyncBatchJob.php completo, SyncService.php completo, queue config
**Prerequisito:** Tarea 05

---

### 🔲 PROMPT-07-mobile-api-test.md
**SKILLs:** `SKILL.md` + `SKILL-mobile.md`
**Entrega:** Colección Postman/Bruno con todos los endpoints API probados
**Prerequisito:** Tarea 03

---

### 🔲 PROMPT-08-roles-permissions.md
**SKILLs:** `SKILL.md` + `SKILL-backend.md`
**Entrega:** Middleware de roles, gates, políticas por modelo
**Prerequisito:** Tarea 03

---

## ESTADO DEL PROYECTO

```
Tarea 01 — Migraciones + Seeders      [ LISTO PARA EJECUTAR ]
Tarea 02 — Modelos + Services         [ LISTO PARA EJECUTAR ]
Tarea 03 — API Controllers            [ LISTO PARA EJECUTAR ]
Tarea 04 — Dashboard Vue              [ LISTO PARA EJECUTAR ]
Tarea 05 — SUNAT Greenter             [ pendiente generar prompt ]
Tarea 06 — Sync Job                   [ pendiente generar prompt ]
Tarea 07 — Mobile API Test            [ pendiente generar prompt ]
Tarea 08 — Roles + Permisos           [ pendiente generar prompt ]
```
