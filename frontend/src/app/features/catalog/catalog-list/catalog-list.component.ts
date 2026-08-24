import { Component, effect, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { BoutiqueContextService } from '../../../core/boutique/boutique-context.service';
import { Produit } from '../../../core/models/produit.model';
import { CatalogFormComponent } from '../catalog-form/catalog-form.component';
import { CatalogScanComponent } from '../catalog-scan/catalog-scan.component';
import { CatalogService } from '../catalog.service';

@Component({
  selector: 'sp-catalog-list',
  standalone: true,
  imports: [
    FormsModule,
    MatTableModule,
    MatButtonModule,
    MatIconModule,
    MatFormFieldModule,
    MatInputModule,
    MatDialogModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-list.component.html',
  styleUrl: './catalog-list.component.scss',
})
export class CatalogListComponent {
  private readonly catalogService = inject(CatalogService);
  private readonly dialog = inject(MatDialog);
  private readonly boutiqueContext = inject(BoutiqueContextService);

  readonly displayedColumns = ['nom', 'categorie', 'codeBarre', 'prixAchat', 'stock', 'statut'];
  readonly produits = signal<Produit[]>([]);
  readonly loading = signal(false);
  readonly searchTerm = signal('');
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;

  constructor() {
    // Re-fetch whenever the header's "Ma boutique" selection changes, so the
    // per-boutique stock/status columns always reflect the active context.
    effect(() => {
      this.boutiqueContext.selectedId();
      this.reload();
    });
  }

  reload(): void {
    this.loading.set(true);
    this.catalogService
      .list({
        nom: this.searchTerm() || undefined,
        idBoutique: this.boutiqueContext.selectedId() ?? undefined,
      })
      .subscribe({
        next: (res) => {
          this.produits.set(res.items);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  statutClasse(produit: Produit): string {
    if (!produit.stock) {
      return '';
    }

    if (produit.stock.quantiteActuelle === 0) {
      return 'sp-status-badge--rupture';
    }

    return produit.stock.sousSeuil ? 'sp-status-badge--critique' : 'sp-status-badge--ok';
  }

  statutLabel(produit: Produit): string {
    if (!produit.stock) {
      return '—';
    }

    if (produit.stock.quantiteActuelle === 0) {
      return 'rupture';
    }

    return produit.stock.sousSeuil ? 'critique' : 'ok';
  }

  openScanDialog(): void {
    const ref = this.dialog.open(CatalogScanComponent, {
      width: '420px',
      data: { idBoutique: this.boutiqueContext.selectedId() },
    });

    ref.afterClosed().subscribe((produit: Produit | undefined) => {
      if (produit) {
        this.reload();
      }
    });
  }

  openCreateDialog(): void {
    const ref = this.dialog.open(CatalogFormComponent, {
      width: '420px',
      data: { idBoutique: this.boutiqueContext.selectedId() },
    });

    ref.afterClosed().subscribe((produit: Produit | undefined) => {
      if (produit) {
        this.reload();
      }
    });
  }
}
