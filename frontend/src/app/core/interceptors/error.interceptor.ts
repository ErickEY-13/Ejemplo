import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

import { ApiError } from '../api/api.types';
import { NotificationService } from '../notifications/notification.service';

/**
 * Traduce cualquier fallo HTTP a un `ApiError` uniforme y avisa al usuario.
 *
 * Los 422 (validación) NO generan toast: los pinta el propio formulario
 * campo a campo, que es donde resultan útiles.
 */
export const errorInterceptor: HttpInterceptorFn = (request, next) => {
  const notifications = inject(NotificationService);

  return next(request).pipe(
    catchError((response: HttpErrorResponse) => {
      const apiError = toApiError(response);

      if (apiError.status !== 422) {
        notifications.error(apiError.message);
      }

      return throwError(() => apiError);
    }),
  );
};

function toApiError(response: HttpErrorResponse): ApiError {
  // status 0 = el navegador nunca llegó a hablar con el servidor.
  if (response.status === 0) {
    return {
      status: 0,
      message: 'No se pudo conectar con el servidor. Comprueba que el backend esté levantado.',
      errors: null,
    };
  }

  const body = response.error as { message?: string; errors?: Record<string, string[]> } | null;

  return {
    status: response.status,
    message: body?.message?.trim() || defaultMessage(response.status),
    errors: response.status === 422 ? (body?.errors ?? {}) : null,
  };
}

function defaultMessage(status: number): string {
  switch (status) {
    case 401:
      return 'No tienes autorización para realizar esta acción.';
    case 403:
      return 'Acceso denegado.';
    case 404:
      return 'No se encontró el recurso solicitado.';
    case 409:
      return 'El recurso ha cambiado desde la última vez que lo cargaste.';
    case 429:
      return 'Demasiadas peticiones seguidas. Inténtalo de nuevo en unos segundos.';
    default:
      return status >= 500
        ? 'Se produjo un error en el servidor. Inténtalo de nuevo más tarde.'
        : 'La petición no se pudo completar.';
  }
}
