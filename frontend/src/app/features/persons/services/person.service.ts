import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { ApiService, QueryParams } from '../../../core/api/api.service';
import { Envelope, Paginated } from '../../../core/api/api.types';
import { environment } from '../../../../environments/environment';
import { Person, PersonDocument, PersonFilters, PersonMetadata, PersonPayload } from '../models/person.model';

/**
 * Única puerta de entrada del módulo hacia /api/persons.
 * Ningún componente debería llamar a `ApiService` directamente.
 */
@Injectable({ providedIn: 'root' })
export class PersonService {
  private readonly api = inject(ApiService);
  private readonly http = inject(HttpClient);
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

  // ---------------------------------------------------------------- Foto

  uploadPhoto(id: number | string, photo: File): Observable<Person> {
    const formData = new FormData();
    formData.append('photo', photo);

    return this.api.post<Person>(`${this.resource}/${id}/photo`, formData);
  }

  deletePhoto(id: number | string): Observable<Person> {
    return this.api.delete<Person>(`${this.resource}/${id}/photo`);
  }

  // ---------------------------------------------------------- Documentos

  uploadDocument(id: number | string, file: File, type: string): Observable<PersonDocument> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    return this.http
      .post<Envelope<PersonDocument>>(`${environment.apiUrl}/${this.resource}/${id}/documents`, formData)
      .pipe(map((response) => response.data));
  }

  deleteDocument(personId: number | string, documentId: number): Observable<void> {
    return this.api.delete(`${this.resource}/${personId}/documents/${documentId}`);
  }

  // ------------------------------------------------------------- Entrevista

  getAudits(id: number | string): Observable<any[]> {
    return this.api.get<any[]>(`${this.resource}/${id}/audits`);
  }

  downloadPdf(id: number | string): Observable<Blob> {
    return this.http.get(`${environment.apiUrl}/${this.resource}/${id}/pdf`, {
      responseType: 'blob'
    });
  }

  // ---------------------------------------------------------------- Exportación Global

  private cleanFilters(filters: Partial<PersonFilters>): any {
    const params: any = {};
    Object.keys(filters).forEach(k => {
      const v = (filters as any)[k];
      if (v !== null && v !== undefined && v !== '') {
        params[k] = typeof v === 'boolean' ? (v ? '1' : '0') : String(v);
      }
    });
    return params;
  }

  exportExcel(filters: Partial<PersonFilters>): Observable<Blob> {
    return this.http.get(`${environment.apiUrl}/${this.resource}/export/excel`, {
      params: this.cleanFilters(filters),
      responseType: 'blob'
    });
  }

  exportPdf(filters: Partial<PersonFilters>): Observable<Blob> {
    return this.http.get(`${environment.apiUrl}/${this.resource}/export/pdf`, {
      params: this.cleanFilters(filters),
      responseType: 'blob'
    });
  }

  // ---------------------------------------------------------------- Importación Masiva

  uploadCsvMasivo(file: File): Observable<{ jobId: string }> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<{ jobId: string }>(`${environment.apiUrl}/${this.resource}/import/csv`, formData);
  }

  getImportStatus(jobId: string): Observable<{ status: string; progress: number; total: number }> {
    return this.http.get<{ status: string; progress: number; total: number }>(
      `${environment.apiUrl}/${this.resource}/import/csv/${jobId}/status`
    );
  }
}

