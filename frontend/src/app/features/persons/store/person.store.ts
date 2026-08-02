import { computed, inject } from '@angular/core';
import { signalStore, withState, withMethods, patchState, withComputed, withHooks } from '@ngrx/signals';
import { rxMethod } from '@ngrx/signals/rxjs-interop';
import { pipe, switchMap, tap, catchError, of } from 'rxjs';
import { Person, PersonFilters, DEFAULT_PERSON_FILTERS, PersonMetadata } from '../models/person.model';
import { PersonService } from '../services/person.service';
import { Paginated, EMPTY_META, PaginationMeta } from '../../../core/api/api.types';

export interface PersonState {
  items: Person[];
  meta: PaginationMeta;
  filters: PersonFilters;
  loading: boolean;
  metadata: PersonMetadata | null;
  reloadToken: number;
}

const initialState: PersonState = {
  items: [],
  meta: EMPTY_META,
  filters: { ...DEFAULT_PERSON_FILTERS },
  loading: false,
  metadata: null,
  reloadToken: 0,
};

export const PersonStore = signalStore(
  { providedIn: 'root' },
  withState(initialState),
  withComputed((store) => ({
    query: computed(() => ({
      filters: store.filters(),
      token: store.reloadToken(),
    })),
    hasFilters: computed(() => {
      const { search, document_type, area, contract_type, is_active, with_trashed } = store.filters();
      return search !== '' || document_type !== '' || area !== '' || contract_type !== '' || is_active !== '' || with_trashed;
    }),
  })),
  withMethods((store) => {
    const personService = inject(PersonService);
    return {
      patchFilters: (patch: Partial<PersonFilters>) => {
        patchState(store, {
          filters: { ...store.filters(), ...patch, page: patch.page ?? 1 }
        });
      },
      goToPage: (page: number) => {
        patchState(store, {
          filters: { ...store.filters(), page }
        });
      },
      sortBy: (column: string) => {
        const filters = store.filters();
        patchState(store, {
          filters: {
            ...filters,
            sort: column,
            direction: (filters.sort === column && filters.direction === 'asc' ? 'desc' : 'asc') as 'asc' | 'desc',
            page: 1,
          }
        });
      },
      resetFilters: () => {
        patchState(store, { filters: { ...DEFAULT_PERSON_FILTERS } });
      },
      reload: () => {
        patchState(store, { reloadToken: store.reloadToken() + 1 });
      },
      loadMetadata: () => {
        personService.metadata().subscribe({
          next: (metadata) => patchState(store, { metadata }),
          error: () => patchState(store, { metadata: null })
        });
      },
    loadPersons: rxMethod<{ filters: PersonFilters; token: number }>(
      pipe(
        tap(() => patchState(store, { loading: true })),
        switchMap(({ filters }) => personService.list(filters).pipe(
          tap((response) => patchState(store, { items: response.data, meta: response.meta, loading: false })),
          catchError(() => {
            patchState(store, { items: [], meta: EMPTY_META, loading: false });
            return of(null);
          })
        ))
      )
      ),
    };
  }),
  withHooks({
    onInit(store) {
      store.loadMetadata();
      // NgRx Signals automáticamente re-evaluará rxMethod cuando el signal 'query' cambie
      store.loadPersons(store.query);
    }
  })
);
