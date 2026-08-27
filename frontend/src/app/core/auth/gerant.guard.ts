import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { map } from 'rxjs';
import { AuthService } from './auth.service';

// Reloads the profile itself (rather than reading the currentUser signal
// synchronously) so a direct/hard-refresh navigation to a gérant-only route
// is resolved correctly even before ShellComponent's own ngOnInit has had a
// chance to populate the signal.
export const gerantGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  return authService.chargerProfil().pipe(map((me) => (me.role === 'GERANT' ? true : router.parseUrl('/dashboard'))));
};
