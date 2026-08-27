import { Component, OnInit, effect, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AuthService } from '../../../core/auth/auth.service';
import { BoutiqueContextService } from '../../../core/boutique/boutique-context.service';
import { Produit } from '../../../core/models/produit.model';
import { CatalogAdjustComponent } from '../catalog-adjust/catalog-adjust.component';
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
    MatTooltipModule,
  ],
  templateUrl: './catalog-list.component.html',
  styleUrl: './catalog-list.component.scss',
})
export class CatalogListComponent implements OnInit {
  private readonly catalogService = inject(CatalogService);
  private readonly dialog = inject(MatDialog);
  private readonly boutiqueContext = inject(BoutiqueContextService);
  private readonly authService = inject(AuthService);

  readonly displayedColumns = ['nom', 'categorie', 'codeBarre', 'prixAchat', 'stock', 'statut', 'actions'];
  readonly produits = signal<Produit[]>([]);
  readonly loading = signal(false);
  readonly searchTerm = signal('');
  readonly selectedBoutique = this.boutiqueContext.selectedBoutique;
  readonly isVendeurSeul = this.authService.isVendeurSeul;

  // effect() reliably reacts to later boutique switches, but its own first
  // run isn't a dependable place to kick off the initial fetch — ngOnInit
  // owns that instead, and this flag stops the effect from double-firing it.
  private isFirstRun = true;

  constructor() {
    // Re-fetch whenever the header's "Ma boutique" selection changes, so the
    // per-boutique stock/status columns always reflect the active context.
    effect(() => {
      this.boutiqueContext.selectedId();

      if (this.isFirstRun) {
        this.isFirstRun = false;

        return;
      }

      this.reload();
    });
  }

  ngOnInit(): void {
    this.reload();
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

  openAdjustDialog(produit: Produit): void {
    const idBoutique = this.boutiqueContext.selectedId();

    if (idBoutique === null) {
      return;
    }

    const ref = this.dialog.open(CatalogAdjustComponent, {
      width: '420px',
      data: {
        idProduit: produit.idProduit,
        idBoutique,
        nom: produit.nom,
        quantiteActuelle: produit.stock?.quantiteActuelle ?? 0,
      },
    });

    ref.afterClosed().subscribe((updated: Produit | undefined) => {
      if (updated) {
        this.reload();
      }
    });
  }
}
