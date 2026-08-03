# Exportación de vehículos (Excel/PDF) + PrimeNG en controles de formulario

Fecha: 2026-08-02

## Contexto

El módulo de Personas ya tiene exportación a Excel y PDF del listado filtrado, e
importación masiva por CSV. El módulo de Vehículos no tiene exportación. Además,
varias pantallas de Personas y Vehículos usan `<select>`, checkboxes e
`<input type="number">` nativos en lugar de los componentes equivalentes de
PrimeNG, que ya está instalado (`primeng` v22, tema Lara vía `@primeng/themes/lara`)
y se usa parcialmente (`p-button`, `p-card`, `pInputText`, `pInputTextarea`).

## Objetivo

1. Vehículos: exportar el listado filtrado a Excel y PDF, igual que Personas.
2. Reemplazar `<select>`, checkboxes e `<input type="number">` nativos por sus
   equivalentes de PrimeNG (`p-select`, `p-checkbox`, `p-inputnumber`) en las
   páginas de listado y formulario de Personas y Vehículos.

## Fuera de alcance

- Componentes propios compartidos (diálogo de confirmación, toasts, paginación):
  se quedan como están, no se migran a `p-confirmdialog`/`p-toast`/`p-paginator`.
- `<input type="date">`: se quedan nativos. Migrar a `p-datepicker` cambia el tipo
  de valor (Date vs string ISO) usado hoy en el payload de ambos formularios, sin
  beneficio claro para este pedido.
- El campo "Marca" (`<input>` + `<datalist>`, autocompletado libre) y el buscador
  de personas en la ficha de vehículo (lista de sugerencias a medida): no son un
  `<select>`, se mantienen como están.
- Botones/enlaces con clase `.btn` (acciones de fila, "Volver", "Cancelar",
  botones de importar/exportar ya en `p-button`): no son controles de formulario,
  cambiarlos afecta a casi todas las páginas del sistema.
- Importación masiva por CSV para Vehículos: no se pidió, no se agrega.

## Parte A — Exportación de vehículos

Replica exacta del patrón ya usado en `App\Modules\Persons`.

### Backend

- `App\Modules\Vehicles\Services\VehicleService::getForExport(array $filters): Collection`
  — usa el `query()` privado ya existente (mismos filtros que el listado), sin
  paginar.
- `App\Modules\Vehicles\Exports\VehiclesExport` (implementa `FromCollection`,
  `WithHeadings`, `WithMapping`, `WithStyles`, igual que `PersonsExport`).
  Columnas: ID, Placa, Marca, Modelo, Año, Tipo, Combustible, Color,
  Kilometraje, Estado.
- `resources/views/pdf/vehicles-report.blade.php`, calcado de
  `persons-report.blade.php` (mismos estilos, tabla en A4 apaisado). Columnas:
  ID, Placa, Marca, Modelo, Año, Tipo, Combustible, Kilometraje, Estado.
- `VehicleController`:
  - `exportExcel(IndexVehicleRequest $request)`: descarga
    `Excel::download(new VehiclesExport($vehicles), 'Flota.xlsx')`.
  - `exportPdf(IndexVehicleRequest $request)`: descarga
    `Pdf::loadView('pdf.vehicles-report', compact('vehicles'))->setPaper('a4', 'landscape')->download('Reporte_Vehiculos.pdf')`.
- Rutas (`app/Modules/Vehicles/Routes/api.php`): agregar
  `GET export/excel` y `GET export/pdf` **antes** de `GET {vehicle}`, igual que
  en el módulo de Personas (si no, Laravel intenta resolver "export" como un
  `{vehicle}` y responde 404/500 en vez de exportar).

### Frontend

- `VehicleService` (`features/vehicles/services/vehicle.service.ts`): agrega
  `cleanFilters()`, `exportExcel(filters): Observable<Blob>`,
  `exportPdf(filters): Observable<Blob>`, mismo patrón que `PersonService`
  (`HttpClient.get(..., { responseType: 'blob' })`, requiere inyectar
  `HttpClient` y `environment`, que hoy no usa directamente).
- `VehicleListPage`: agrega señales `exportingExcel`/`exportingPdf` y métodos
  `exportExcel()`, `exportPdf()`, `downloadBlob()` (idénticos a
  `PersonListPage`, sin la parte de importación CSV).
- `vehicle-list.page.html`: agrega dos `p-button` ("Excel", "PDF") en
  `.page-header__actions`, junto al botón "Nuevo vehículo", deshabilitados
  cuando `items().length === 0`, con `[loading]` mientras exportan.

## Parte B — PrimeNG en controles de formulario

Se importan y usan `SelectModule` (`primeng/select`, componente `p-select`),
`CheckboxModule` (`primeng/checkbox`, componente `p-checkbox`) e
`InputNumberModule` (`primeng/inputnumber`, componente `p-inputnumber`) en los
4 archivos siguientes. Los metadatos que ya vienen como `{value, label}[]`
(`SelectOption[]`) se pasan directo a `[options]` con
`optionLabel="label" optionValue="value"`. Para los `<select>` con opciones
fijas en el HTML (no vienen de `metadata()`), se define un array de opciones
en el `.ts` de la página.

### `person-list.page.html` / `.ts`

- `<select>` → `p-select`: tipo de documento, área, tipo de contrato, estado
  (opciones fijas: Todos/Activas/Inactivas), por página (opciones fijas:
  15/25/50/100).
- Checkbox "Incluir eliminadas" → `p-checkbox [binary]="true"`.

### `vehicle-list.page.html` / `.ts`

- `<select>` → `p-select`: tipo, combustible, marca (`metadata()?.brands` es
  `string[]`, no `SelectOption[]`: se pasa `[options]` como array de strings
  simple, sin `optionLabel`/`optionValue`), estado (opciones fijas).
- `<input type="number">` (año desde, año hasta) → `p-inputnumber`
  `[useGrouping]="false"`, sin flechas (`showButtons` desactivado, para no
  romper el layout compacto actual del filtro).
- Checkbox "Incluir eliminados" → `p-checkbox [binary]="true"`.

### `person-form.page.html` / `.ts`

- `<select>` → `p-select`: tipo de documento, género, estado civil, nivel
  educativo, área, tipo de contrato, turno, sede, sistema de pensión, y el
  selector de tipo de documento adjunto (`docType`, ligado con `[(ngModel)]`).
- `<input type="number">` (número de hijos) → `p-inputnumber` con
  `[min]="0" [max]="50"`.
- Checkbox "Persona activa" → `p-checkbox [binary]="true"`.

### `vehicle-form.page.html` / `.ts`

- `<select>` → `p-select`: tipo de vehículo, combustible.
- `<input type="number">` (año, kilometraje) → `p-inputnumber` con los mismos
  `min`/`max` que hoy.
- Checkbox "Vehículo activo" → `p-checkbox [binary]="true"`.

## Notas de implementación

- `p-inputnumber` mantiene el valor como `number | null` directamente: se
  eliminan conversiones manuales como `+$event` que hoy hace
  `patchFilters({ per_page: +$event })` (ese caso es un `p-select`, no
  `p-inputnumber`, pero el mismo criterio aplica al resto).
- No se toca la clase CSS `.select`/`.checkbox` en `styles.scss`; puede quedar
  sin uso tras el cambio, pero no se elimina en este trabajo si otras vistas
  (no tocadas) siguen dependiendo de ella.

## Pruebas

- Backend: verificar manualmente `GET /api/vehicles/export/excel` y
  `GET /api/vehicles/export/pdf` (con y sin filtros) devuelven el archivo
  esperado.
- Frontend: `ng build` sin errores de tipos/plantillas. Verificación visual en
  el navegador de los 4 listados/formularios tras la migración a PrimeNG
  (filtros, validaciones, guardado, exportación) para confirmar que no hay
  regresiones funcionales ni de estilo.
