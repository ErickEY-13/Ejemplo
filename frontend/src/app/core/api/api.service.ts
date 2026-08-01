import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../environments/environment';
import { Envelope, Paginated } from './api.types';

/** Valores admitidos en la query string. Los `null`/`undefined` se descartan. */
export type QueryParams = Record<string, string | number | boolean | null | undefined>;

/**
 * Cliente HTTP común a todos los módulos.
 *
 * Centraliza la URL base y el desempaquetado del sobre `{ data: ... }` que
 * devuelven los API Resources de Laravel, para que los servicios de cada
 * módulo trabajen directamente con sus modelos.
 */
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  /** Listado paginado (conserva `meta`). */
  list<T>(path: string, params: QueryParams = {}): Observable<Paginated<T>> {
    return this.http.get<Paginated<T>>(this.url(path), { params: this.toHttpParams(params) });
  }

  /** Recurso único; devuelve ya el contenido de `data`. */
  get<T>(path: string, params: QueryParams = {}): Observable<T> {
    return this.http
      .get<Envelope<T>>(this.url(path), { params: this.toHttpParams(params) })
      .pipe(map((response) => response.data));
  }

  post<T>(path: string, body: unknown): Observable<T> {
    return this.http.post<Envelope<T>>(this.url(path), body).pipe(map((response) => response.data));
  }

  patch<T>(path: string, body: unknown): Observable<T> {
    return this.http.patch<Envelope<T>>(this.url(path), body).pipe(map((response) => response.data));
  }

  put<T>(path: string, body: unknown): Observable<T> {
    return this.http.put<Envelope<T>>(this.url(path), body).pipe(map((response) => response.data));
  }

  /**
   * La mayoría de los DELETE de la API responden 204 sin cuerpo, pero
   * algunos (como quitar la foto de un vehículo) devuelven el recurso
   * actualizado envuelto en `{ data: ... }`. Cubre ambos casos.
   */
  delete<T = void>(path: string): Observable<T> {
    return this.http
      .delete<Envelope<T> | null>(this.url(path))
      .pipe(map((response) => (response ? response.data : (undefined as T))));
  }

  private url(path: string): string {
    return `${this.baseUrl}/${path.replace(/^\/+/, '')}`;
  }

  /**
   * Convierte el objeto de filtros en query string.
   *
   * Los valores vacíos se omiten (para no filtrar por ellos) y los booleanos
   * se envían como 1/0: la regla `boolean` de Laravel compara de forma
   * estricta y rechazaría las cadenas "true"/"false".
   */
  private toHttpParams(params: QueryParams): HttpParams {
    let httpParams = new HttpParams();

    for (const [key, value] of Object.entries(params)) {
      if (value === null || value === undefined || value === '') {
        continue;
      }

      httpParams = httpParams.set(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value));
    }

    return httpParams;
  }
}
