import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { ApiService, QueryParams } from '../../../core/api/api.service';
import { Paginated } from '../../../core/api/api.types';
import { Person, PersonFilters, PersonMetadata, PersonPayload } from '../models/person.model';

/**
 * Única puerta de entrada del módulo hacia /api/persons.
 * Ningún componente debería llamar a `ApiService` directamente.
 */
@Injectable({ providedIn: 'root' })
export class PersonService {
  private readonly api = inject(ApiService);
  private readonly resource = 'persons';

  list(filters: Partial<PersonFilters>): Observable<Paginated<Person>> {
    return this.api.list<Person>(this.resource, filters as QueryParams);
  }

  metadata(): Observable<PersonMetadata> {
    return this.api.get<PersonMetadata>(`${this.resource}/metadata`);
  }

  find(id: number | string): Observable<Person> {
    return this.api.get<Person>(`${this.resource}/${id}`);
  }

  create(payload: PersonPayload): Observable<Person> {
    return this.api.post<Person>(this.resource, payload);
  }

  update(id: number | string, payload: PersonPayload): Observable<Person> {
    return this.api.patch<Person>(`${this.resource}/${id}`, payload);
  }

  remove(id: number | string): Observable<void> {
    return this.api.delete(`${this.resource}/${id}`);
  }

  restore(id: number | string): Observable<Person> {
    return this.api.post<Person>(`${this.resource}/${id}/restore`, {});
  }
}
