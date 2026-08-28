import { DatePipe } from '@angular/common';
import { Component, OnInit, computed, effect, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { MatTableModule } from '@angular/material/table';
import { Router, RouterLink } from '@angular/router';
import { BoutiqueContextService } from '../../../core/boutique/boutique-context.service';
import { commandeStatutClasse, commandeStatutLabel } from '../../../core/models/commande-statut.util';
import { CommandeResume, StatutCommande } from '../../../core/models/commande.model';
import { Fournisseur } from '../../../core/models/fournisseur.model';
import { CommandeService } from '../commande.service';
import { FournisseurService } from '../fournisseur.service';

@Component({
  selector: 'sp-commande-list',
  standalone: true,
  imports: [
    DatePipe,
    RouterLink,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatSelectModule,
  ],
  templateUrl: './commande-list.component.html',
  styleUrl: './commande-list.component.scss',
})
export class CommandeListComponent implements OnInit {
  private readonly commandeService = inject(CommandeService);
  private readonly fournisseurService = inject(FournisseurService);
  private readonly boutiqueContext = inject(BoutiqueContextService);
  private readonly router = inject(Router);

  readonly commandes = signal<CommandeResume[]>([]);
  readonly loading = signal(false);
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;
  readonly boutiques = this.boutiqueContext.boutiques;
  readonly canCreer = computed(() => this.selectedBoutique() !== null);

  readonly fournisseurs = signal<Fournisseur[]>([]);
  readonly filterIdFournisseur = signal<number | null>(null);

  readonly displayedColumns = computed(() =>
    this.selectedBoutique()
      ? ['idCommande', 'fournisseur', 'dateCreation', 'statut', 'actions']
      : ['idCommande', 'fournisseur', 'boutique', 'dateCreation', 'statut', 'actions'],
  );

  readonly commandesAffichees = computed(() => {
    const idFournisseur = this.filterIdFournisseur();

    return idFournisseur === null
      ? this.commandes()
      : this.commandes().filter((c) => c.fournisseur.idFournisseur === idFournisseur);
  });

  // effect() reliably reacts to later boutique switches, but its own first
  // run isn't a dependable place to kick off the initial fetch — ngOnInit
  // owns that instead, and this flag stops the effect from double-firing it.
  private isFirstRun = true;

  constructor() {
    effect(() => {
      const idBoutique = this.boutiqueContext.selectedId();

      if (this.isFirstRun) {
        this.isFirstRun = false;

        return;
      }

      this.reload(idBoutique);
    });
  }

  ngOnInit(): void {
    this.reload(this.boutiqueContext.selectedId());
    this.fournisseurService.list().subscribe((fournisseurs) => this.fournisseurs.set(fournisseurs));
  }

  reload(idBoutique: number | null): void {
    this.loading.set(true);
    this.commandeService.list(idBoutique ?? undefined).subscribe({
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
    return commandeStatutClasse(statut);
  }

  statutLabel(statut: StatutCommande): string {
    return commandeStatutLabel(statut);
  }
}
