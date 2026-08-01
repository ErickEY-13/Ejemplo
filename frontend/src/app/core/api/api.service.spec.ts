import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

import { ApiService } from './api.service';

describe('ApiService', () => {
  let api: ApiService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [provideHttpClient(), provideHttpClientTesting()],
    });

    api = TestBed.inject(ApiService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('omite los filtros vacíos y envía los booleanos como 1/0', () => {
    api.list('persons', { search: '', page: 1, with_trashed: false, is_active: true }).subscribe();

    const request = http.expectOne((r) => r.url === '/api/persons');

    expect(request.request.params.has('search')).toBe(false);
    expect(request.request.params.get('page')).toBe('1');
    expect(request.request.params.get('with_trashed')).toBe('0');
    expect(request.request.params.get('is_active')).toBe('1');

    request.flush({ data: [], meta: {} });
  });

  it('desempaqueta el sobre { data } de los API Resources', async () => {
    const result = firstValue(api.get<{ id: number }>('persons/1'));

    http.expectOne('/api/persons/1').flush({ data: { id: 1 } });

    expect(await result).toEqual({ id: 1 });
  });

  it('normaliza las barras sobrantes de la ruta', () => {
    api.delete('/persons/9').subscribe();

    http.expectOne('/api/persons/9').flush(null, { status: 204, statusText: 'No Content' });
  });
});

function firstValue<T>(source: { subscribe: (observer: { next: (v: T) => void }) => unknown }): Promise<T> {
  return new Promise<T>((resolve) => source.subscribe({ next: resolve }));
}
