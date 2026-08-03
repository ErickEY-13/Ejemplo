import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { AssignVehiclePayload, PersonOption, Site, VehicleAssignment } from '../models/assignment.model';

/**
 * Única puerta de entrada hacia /api/assignments.
 *
 * Es el único punto del frontend que conoce tanto a Vehículos como a
 * Personas: la página de detalle de un vehículo la usa para mostrar y
 * gestionar la sede y el responsable asignados, sin que `features/vehicles`
 * tenga que importar nada de `features/persons`.
 */
@Injectable({ providedIn: 'root' })
export class AssignmentService {
  private readonly api = inject(ApiService);
  private readonly resource = 'assignments';

  current(vehicleId: number | string): Observable<VehicleAssignment | null> {
    return this.api.get<VehicleAssignment | null>(`${this.resource}/${vehicleId}`);
  }

  history(vehicleId: number | string): Observable<VehicleAssignment[]> {
    return this.api.get<VehicleAssignment[]>(`${this.resource}/${vehicleId}/history`);
  }

  /** Asignación actual de todos los vehículos, opcionalmente filtrada por sede. */
  listCurrent(siteId?: number): Observable<VehicleAssignment[]> {
    return this.api.get<VehicleAssignment[]>(this.resource, siteId ? { site_id: siteId } : {});
  }

  getSites(): Observable<Site[]> {
    return this.api.get<Site[]>(`${this.resource}/sites`);
  }

  assign(vehicleId: number | string, payload: AssignVehiclePayload): Observable<VehicleAssignment> {
    return this.api.put<VehicleAssignment>(`${this.resource}/${vehicleId}`, payload);
  }

  unassign(vehicleId: number | string): Observable<void> {
    return this.api.delete(`${this.resource}/${vehicleId}`);
  }

  searchPeople(search: string): Observable<PersonOption[]> {
    return this.api.get<PersonOption[]>(`${this.resource}/people`, { search });
  }
}
