import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';
import { gerantGuard } from './core/auth/gerant.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: '',
    loadComponent: () => import('./core/layout/shell.component').then((m) => m.ShellComponent),
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent),
      },
      {
        path: 'catalogue',
        loadComponent: () =>
          import('./features/catalog/catalog-list/catalog-list.component').then((m) => m.CatalogListComponent),
      },
      {
        path: 'commandes',
        loadComponent: () =>
          import('./features/commande/commande-list/commande-list.component').then((m) => m.CommandeListComponent),
      },
      {
        path: 'commandes/nouvelle',
        loadComponent: () =>
          import('./features/commande/commande-create/commande-create.component').then(
            (m) => m.CommandeCreateComponent,
          ),
      },
      {
        path: 'commandes/:id',
        loadComponent: () =>
          import('./features/commande/commande-detail/commande-detail.component').then(
            (m) => m.CommandeDetailComponent,
          ),
      },
      {
        path: 'notifications',
        loadComponent: () =>
          import('./features/notifications/notification-list/notification-list.component').then(
            (m) => m.NotificationListComponent,
          ),
      },
      {
        path: 'organisation',
        canActivate: [gerantGuard],
        loadComponent: () =>
          import('./features/organisation/organisation.component').then((m) => m.OrganisationComponent),
      },
      {
        path: 'compte',
        loadComponent: () => import('./features/compte/compte.component').then((m) => m.CompteComponent),
      },
      {
        path: 'mouvements',
        canActivate: [gerantGuard],
        loadComponent: () =>
          import('./features/mouvement-historique/mouvement-historique.component').then(
            (m) => m.MouvementHistoriqueComponent,
          ),
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
