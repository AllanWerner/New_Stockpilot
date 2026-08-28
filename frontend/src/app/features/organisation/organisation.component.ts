import { Component, OnInit, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AuthService } from '../../core/auth/auth.service';
import { Boutique } from '../../core/models/boutique.model';
import { Employe } from '../../core/models/employe.model';
import { OrganisationAffecterFormComponent } from './organisation-affecter-form/organisation-affecter-form.component';
import { OrganisationBoutiqueFormComponent } from './organisation-boutique-form/organisation-boutique-form.component';
import { OrganisationEmployeFormComponent } from './organisation-employe-form/organisation-employe-form.component';
import { OrganisationService } from './organisation.service';

@Component({
  selector: 'sp-organisation',
  standalone: true,
  imports: [
    MatTabsModule,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatChipsModule,
    MatDialogModule,
    MatProgressSpinnerModule,
    MatTooltipModule,
  ],
  templateUrl: './organisation.component.html',
  styleUrl: './organisation.component.scss',
})
export class OrganisationComponent implements OnInit {
  private readonly organisationService = inject(OrganisationService);
  private readonly dialog = inject(MatDialog);
  private readonly authService = inject(AuthService);

  readonly employeColumns = ['nom', 'email', 'role', 'boutiques', 'statut', 'actions'];
  readonly boutiqueColumns = ['nom', 'adresse', 'ville', 'statut', 'actions'];

  readonly employes = signal<Employe[]>([]);
  readonly boutiques = signal<Boutique[]>([]);
  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.organisationService.listEmployes().subscribe({
      next: (employes) => this.employes.set(employes),
    });
    this.organisationService.listBoutiques().subscribe({
      next: (boutiques) => {
        this.boutiques.set(boutiques);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  ouvrirNouvelEmploye(): void {
    const ref = this.dialog.open(OrganisationEmployeFormComponent, { width: '420px' });

    ref.afterClosed().subscribe((employe: Employe | undefined) => {
      if (employe) {
        this.reload();
      }
    });
  }

  ouvrirNouvelleBoutique(): void {
    const ref = this.dialog.open(OrganisationBoutiqueFormComponent, { width: '420px' });

    ref.afterClosed().subscribe((boutique: Boutique | undefined) => {
      if (boutique) {
        this.reload();
      }
    });
  }

  ouvrirAffecter(boutique: Boutique): void {
    const ref = this.dialog.open(OrganisationAffecterFormComponent, {
      width: '420px',
      data: { boutique, employes: this.employes() },
    });

    ref.afterClosed().subscribe((updated: boolean | undefined) => {
      if (updated) {
        this.reload();
      }
    });
  }

  estMoi(employe: Employe): boolean {
    return employe.idEmploye === this.authService.currentUser()?.idEmploye;
  }

  toggleEmployeActif(employe: Employe): void {
    this.errorMessage.set(null);
    const action = employe.actif
      ? this.organisationService.desactiverEmploye(employe.idEmploye)
      : this.organisationService.activerEmploye(employe.idEmploye);

    action.subscribe({
      next: () => this.reload(),
      error: (err: unknown) => this.errorMessage.set(this.messageErreur(err)),
    });
  }

  supprimerEmploye(employe: Employe): void {
    if (!confirm(`Supprimer définitivement ${employe.prenom} ${employe.nom} ?`)) {
      return;
    }

    this.errorMessage.set(null);
    this.organisationService.supprimerEmploye(employe.idEmploye).subscribe({
      next: () => this.reload(),
      error: (err: unknown) => this.errorMessage.set(this.messageErreur(err)),
    });
  }

  toggleBoutiqueActif(boutique: Boutique): void {
    this.errorMessage.set(null);
    const action = boutique.actif
      ? this.organisationService.desactiverBoutique(boutique.idBoutique)
      : this.organisationService.activerBoutique(boutique.idBoutique);

    action.subscribe({
      next: () => this.reload(),
      error: (err: unknown) => this.errorMessage.set(this.messageErreur(err)),
    });
  }

  supprimerBoutique(boutique: Boutique): void {
    if (!confirm(`Supprimer définitivement la boutique ${boutique.nom} ?`)) {
      return;
    }

    this.errorMessage.set(null);
    this.organisationService.supprimerBoutique(boutique.idBoutique).subscribe({
      next: () => this.reload(),
      error: (err: unknown) => this.errorMessage.set(this.messageErreur(err)),
    });
  }

  private messageErreur(err: unknown): string {
    if (err instanceof Object && 'error' in err) {
      const body = (err as { error?: { error?: string } }).error;

      if (body?.error) {
        return body.error;
      }
    }

    return "Une erreur est survenue.";
  }
}
