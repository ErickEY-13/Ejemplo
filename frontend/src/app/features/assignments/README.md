# Módulo frontend: Asignación de Vehículos

> Puente entre `features/vehicles` y `features/persons`.

No tiene rutas ni páginas propias: es un modelo + servicio que consume la
página de detalle de un vehículo (`features/vehicles/pages/vehicle-detail`)
para mostrar y gestionar el responsable asignado.

Habla con su propio backend (`/api/assignments`), que ya resuelve los datos
de la persona — así ni `features/vehicles` importa de `features/persons`,
ni al revés.

## Estructura

```
assignments/
├── models/assignment.model.ts       # Espejo tipado de VehicleAssignmentResource (backend)
└── services/assignment.service.ts   # Única puerta hacia /api/assignments
```
