import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Produit } from '../../../core/models/produit.model';
import { CatalogService } from '../catalog.service';

export interface CatalogAdjustDialogData {
  idProduit: number;
  idBoutique: number;
  nom: string;
  quantiteActuelle: number;
}

@Component({
  selector: 'sp-catalog-adjust',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    MatDialogModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './catalog-adjust.component.html',
  styleUrl: './catalog-adjust.component.scss',
})
export class CatalogAdjustComponent {
  private readonly fb = inject(FormBuilder);
  private readonly catalogService = inject(CatalogService);
  private readonly dialogRef = inject(MatDialogRef<CatalogAdjustComponent>);
  readonly data = inject<CatalogAdjustDialogData>(MAT_DIALOG_DATA);

  readonly loading = signal(false);
  readonly errorMessage = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    quantite: [0, [Validators.required, Validators.pattern(/^-?\d+$/)]],
  });

  submit(): void {
    const quantite = Number(this.form.getRawValue().quantite);

    if (this.form.invalid || quantite === 0) {
      this.form.markAllAsTouched();
      this.errorMessage.set('La quantité doit être différente de zéro.');

      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);

    this.catalogService.ajuster(this.data.idProduit, { idBoutique: this.data.idBoutique, quantite }).subscribe({
      next: (produit: Produit) => {
        this.loading.set(false);
        this.dialogRef.close(produit);
      },
      error: (err: unknown) => {
        this.loading.set(false);
        const status = err instanceof Object && 'status' in err ? (err as { status: number }).status : 0;
        this.errorMessage.set(status === 409 ? 'Stock insuffisant pour ce retrait.' : "Impossible d'ajuster le stock.");
      },
    });
  }
}
