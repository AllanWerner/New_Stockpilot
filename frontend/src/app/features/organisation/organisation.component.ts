import { Component, OnInit, inject, signal } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatChipsModule } from '@angular/material/chips';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { MatTabsModule } from '@angular/material/tabs';
import { MatTooltipModule } from '@angular/material/tooltip';
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

  readonly employeColumns = ['nom', 'email', 'role', 'boutiques'];
  readonly boutiqueColumns = ['nom', 'adresse', 'ville', 'actions'];

  readonly employes = signal<Employe[]>([]);
  readonly boutiques = signal<Boutique[]>([]);
  readonly loading = signal(false);

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
}
