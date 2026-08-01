import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { ApiService, QueryParams } from '../../../core/api/api.service';
import { Paginated } from '../../../core/api/api.types';
import { Vehicle, VehicleFilters, VehicleMetadata, VehiclePayload } from '../models/vehicle.model';

/**
 * Única puerta de entrada del módulo hacia /api/vehicles.
 * Ningún componente debería llamar a `ApiService` directamente.
 */
@Injectable({ providedIn: 'root' })
export class VehicleService {
  private readonly api = inject(ApiService);
  private readonly resource = 'vehicles';

  list(filters: Partial<VehicleFilters>): Observable<Paginated<Vehicle>> {
    return this.api.list<Vehicle>(this.resource, filters as QueryParams);
  }

  metadata(): Observable<VehicleMetadata> {
    return this.api.get<VehicleMetadata>(`${this.resource}/metadata`);
  }

  find(id: number | string): Observable<Vehicle> {
    return this.api.get<Vehicle>(`${this.resource}/${id}`);
  }

  create(payload: VehiclePayload): Observable<Vehicle> {
    return this.api.post<Vehicle>(this.resource, payload);
  }

  update(id: number | string, payload: VehiclePayload): Observable<Vehicle> {
    return this.api.patch<Vehicle>(`${this.resource}/${id}`, payload);
  }

  remove(id: number | string): Observable<void> {
    return this.api.delete(`${this.resource}/${id}`);
  }

  restore(id: number | string): Observable<Vehicle> {
    return this.api.post<Vehicle>(`${this.resource}/${id}/restore`, {});
  }

  uploadPhoto(id: number | string, photo: File): Observable<Vehicle> {
    const formData = new FormData();
    formData.append('photo', photo);

    return this.api.post<Vehicle>(`${this.resource}/${id}/photo`, formData);
  }

  deletePhoto(id: number | string): Observable<Vehicle> {
    return this.api.delete<Vehicle>(`${this.resource}/${id}/photo`);
  }
}
