/**
 * Espejo de `App\Modules\Assignments\Http\Resources\VehicleAssignmentResource`.
 * Si cambia el recurso en el backend, hay que actualizar esta interfaz.
 */
export interface VehicleAssignment {
  id: number;
  vehicle_id: number;
  site: Site | null;
  person: AssignedPerson | null;
  notes: string | null;
  assigned_at: string;
  ended_at: string | null;
  expected_return_at: string | null;
  is_overdue: boolean;
}

/** Sede de un vehículo. Espejo de `App\Modules\Assignments\Models\Site`. */
export interface Site {
  id: number;
  code: string;
  name: string;
}

export interface AssignedPerson {
  id: number;
  full_name: string;
  document_number: string;
  site: string | null;
  is_active: boolean;
  deleted_at: string | null;
}

/** Resultado de GET /api/assignments/people (selector de personas). */
export interface PersonOption {
  id: number;
  full_name: string;
  document_number: string;
  site: string | null;
}

/** Cuerpo que acepta PUT /api/assignments/{vehicle}. */
export interface AssignVehiclePayload {
  site_id: number;
  person_id: number | null;
  expected_return_at: string | null;
  notes: string | null;
}
