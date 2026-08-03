import { provideHttpClient, withFetch, withInterceptors } from '@angular/common/http';
import { ApplicationConfig, provideBrowserGlobalErrorListeners } from '@angular/core';
import { provideRouter, withComponentInputBinding, withInMemoryScrolling } from '@angular/router';
import { providePrimeNG } from 'primeng/config';
import Lara from '@primeng/themes/lara';
import { definePreset } from '@primeuix/themes';
import { ConfirmationService, MessageService } from 'primeng/api';

import { routes } from './app.routes';
import { errorInterceptor } from './core/interceptors/error.interceptor';

/** Alinea la paleta primaria de PrimeNG con el azul de marca (`--c-primary`). */
const AppTheme = definePreset(Lara, {
  semantic: {
    primary: {
      50: '#eef5fc',
      100: '#d9e9f7',
      200: '#b3d3ef',
      300: '#85b8e3',
      400: '#4f93d1',
      500: '#2971b8',
      600: '#1f5aa5',
      700: '#1c4c8a',
      800: '#1a3f70',
      900: '#163f73',
      950: '#0d2547',
    },
  },
});

export const appConfig: ApplicationConfig = {
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideRouter(
      routes,
      // Permite recibir los parámetros de ruta como `input()` en las páginas.
      withComponentInputBinding(),
      withInMemoryScrolling({ scrollPositionRestoration: 'top', anchorScrolling: 'enabled' }),
    ),
    provideHttpClient(withFetch(), withInterceptors([errorInterceptor])),
    // Registra el tema de PrimeNG: sin esto los componentes se pintan sin estilos.
    providePrimeNG({
      ripple: true,
      theme: {
        preset: AppTheme,
        options: {
          darkModeSelector: false,
        },
      },
      license:
        'eyJpZCI6ImUyY2UwMmUxLTc3NGYtNDliNC05OWVhLWMzOGNjYjA3YjM0ZiIsInByb2R1Y3QiOiJwcmltZXVpIiwidGllciI6ImNvbW11bml0eSIsInR5cGUiOiJkZXYiLCJpYXQiOjE3ODU3NTYzNjksImV4cCI6MTgxNzI5MjM2OX0.5sTI5jPN7zIOlxzX5PqMZCr2OJyQtRRSBQxnNYEVa_nGQ8itTuXHniA92RKSLbg9MbwkF8jpdDYbotVs7V9QAA',
    }),
    MessageService,
    ConfirmationService,
  ],
};
