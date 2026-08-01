/**
 * Espejo de `App\Modules\Assignments\Http\Resources\VehicleAssignmentResource`.
 * Si cambia el recurso en el backend, hay que actualizar esta interfaz.
 */
export interface VehicleAssignment {
  vehicle_id: number;
  person: AssignedPerson;
  notes: string | null;
  assigned_at: string;
  expected_return_at: string | null;
  is_overdue: boolean;
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
  person_id: number;
  expected_return_at: string | null;
  notes: string | null;
}
