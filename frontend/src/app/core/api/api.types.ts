/**
 * Tipos compartidos por todos los módulos para hablar con la API de Laravel.
 */

/** Metadatos de paginación que devuelve un ResourceCollection de Laravel. */
export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

/** Respuesta paginada: `PersonResource::collection(...)`. */
export interface Paginated<T> {
  data: T[];
  meta: PaginationMeta;
}

/** Respuesta de un único recurso: `PersonResource::make(...)`. */
export interface Envelope<T> {
  data: T;
}

/** Errores de validación de Laravel (respuesta 422). */
export type ValidationErrors = Record<string, string[]>;

/** Error normalizado por el interceptor, para que las páginas no vean HttpErrorResponse. */
export interface ApiError {
  status: number;
  message: string;
  /** Solo viene relleno en los 422. */
  errors: ValidationErrors | null;
}

/** Opción de un desplegable (`{ value, label }` de los enums de PHP). */
export interface SelectOption {
  value: string;
  label: string;
}

export const EMPTY_META: PaginationMeta = {
  current_page: 1,
  from: null,
  last_page: 1,
  per_page: 15,
  to: null,
  total: 0,
};
