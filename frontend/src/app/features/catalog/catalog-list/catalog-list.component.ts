import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
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
export class CatalogListComponent implements OnInit {
  private readonly catalogService = inject(CatalogService);
  private readonly dialog = inject(MatDialog);

  readonly displayedColumns = ['nom', 'categorie', 'codeBarre', 'prixAchat', 'unite'];
  readonly produits = signal<Produit[]>([]);
  readonly loading = signal(false);
  readonly searchTerm = signal('');

  ngOnInit(): void {
    this.reload();
  }

  reload(): void {
    this.loading.set(true);
    this.catalogService.list({ nom: this.searchTerm() || undefined }).subscribe({
      next: (res) => {
        this.produits.set(res.items);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  openScanDialog(): void {
    const ref = this.dialog.open(CatalogScanComponent, { width: '420px' });

    ref.afterClosed().subscribe((produit: Produit | undefined) => {
      if (produit) {
        this.reload();
      }
    });
  }

  openCreateDialog(): void {
    const ref = this.dialog.open(CatalogFormComponent, { width: '420px' });

    ref.afterClosed().subscribe((produit: Produit | undefined) => {
      if (produit) {
        this.reload();
      }
    });
  }
}
