import { DatePipe } from '@angular/common';
import { Component, computed, effect, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { Router, RouterLink } from '@angular/router';
import { BoutiqueContextService } from '../../../core/boutique/boutique-context.service';
import { CommandeResume, StatutCommande } from '../../../core/models/commande.model';
import { CommandeService } from '../commande.service';

@Component({
  selector: 'sp-commande-list',
  standalone: true,
  imports: [DatePipe, RouterLink, MatTableModule, MatButtonModule, MatIconModule, MatProgressSpinnerModule],
  templateUrl: './commande-list.component.html',
  styleUrl: './commande-list.component.scss',
})
export class CommandeListComponent {
  private readonly commandeService = inject(CommandeService);
  private readonly boutiqueContext = inject(BoutiqueContextService);
  private readonly router = inject(Router);

  readonly displayedColumns = ['idCommande', 'fournisseur', 'dateCreation', 'statut', 'actions'];
  readonly commandes = signal<CommandeResume[]>([]);
  readonly loading = signal(false);
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;
  readonly canCreer = computed(() => this.selectedBoutique() !== null);

  constructor() {
    effect(() => {
      const idBoutique = this.boutiqueContext.selectedId();

      if (idBoutique !== null) {
        this.reload(idBoutique);
      } else {
        this.commandes.set([]);
      }
    });
  }

  reload(idBoutique: number): void {
    this.loading.set(true);
    this.commandeService.list(idBoutique).subscribe({
      next: (commandes) => {
        this.commandes.set(commandes);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  ouvrir(idCommande: number): void {
    this.router.navigate(['/commandes', idCommande]);
  }

  statutClasse(statut: StatutCommande): string {
    return (
      {
        BROUILLON: 'sp-status-badge--critique',
        ENVOYEE: 'sp-status-badge--critique',
        RECUE_PARTIELLE: 'sp-status-badge--critique',
        RECUE: 'sp-status-badge--ok',
      } satisfies Record<StatutCommande, string>
    )[statut];
  }

  statutLabel(statut: StatutCommande): string {
    return (
      {
        BROUILLON: 'brouillon',
        ENVOYEE: 'envoyée',
        RECUE_PARTIELLE: 'reçue partiellement',
        RECUE: 'reçue',
      } satisfies Record<StatutCommande, string>
    )[statut];
  }
}
