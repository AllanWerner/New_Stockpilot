import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Boutique } from '../models/boutique.model';

/**
 * Holds the list of boutiques the current employee can act on, and which one
 * is "active" — powers the persistent "Ma boutique" selector in the header
 * (Jalon 2 wireframes) and scopes per-boutique stock displays across screens.
 * A null selection means the consolidated multi-boutique view (gérant only).
 */
@Injectable({ providedIn: 'root' })
export class BoutiqueContextService {
  private readonly http = inject(HttpClient);

  private readonly boutiquesSignal = signal<Boutique[]>([]);
  private readonly selectedIdSignal = signal<number | null>(null);

  readonly boutiques = this.boutiquesSignal.asReadonly();
  readonly selectedId = this.selectedIdSignal.asReadonly();
  readonly selectedBoutique = computed(
    () => this.boutiquesSignal().find((b) => b.idBoutique === this.selectedIdSignal()) ?? null,
  );

  charger() {
    return this.http.get<Boutique[]>(`${environment.apiUrl}/boutiques`).pipe(
      tap((boutiques) => {
        // The API also returns inactive boutiques (the org-management page
        // needs those to reactivate them) — the selector only ever offers
        // active ones.
        const actives = boutiques.filter((b) => b.actif);
        this.boutiquesSignal.set(actives);

        if (actives.length === 1) {
          this.selectedIdSignal.set(actives[0].idBoutique);
        }
      }),
    );
  }

  selectionner(idBoutique: number | null): void {
    this.selectedIdSignal.set(idBoutique);
  }
}
